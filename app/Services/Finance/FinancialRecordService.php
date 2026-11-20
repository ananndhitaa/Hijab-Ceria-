<?php

namespace App\Services\Finance;

use App\Enums\ExpenseType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\PurchaseApprovalStatus;
use App\Enums\PurchaseStatus;
use App\Models\Finance\BusinessExpense;
use App\Models\Finance\BusinessIncome;
use App\Models\Finance\OpeningCashBalance;
use App\Models\Inventory\Purchase;
use App\Models\Inventory\PurchasePayment;
use App\Models\Sales\Order;
use App\Models\Sales\Payment;
use App\Services\Sales\InvoiceService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class FinancialRecordService
{
    public const INFLOW_TYPES = [
        'dp', 'customer_dp',
        'pelunasan', 'customer_settlement', 'settlement',
        'lainnya', 'customer_other',
        'pemasukan_lain', 'business_income_other',
        'modal_tambahan', 'capital_injection',
        'pendapatan_non_penjualan', 'non_sales_income',
    ];

    public const OUTFLOW_TYPES = [
        'supplier_payment', 'pembayaran_supplier',
        'purchase_payment',
        'marketing',
        'operating', 'operasional',
        'interest', 'bunga',
        'tax', 'pajak',
        'depreciation', 'penyusutan', 'depresiasi',
        'amortization', 'amortisasi',
    ];

    /**
     * Infer authoritative cashflow direction ('in' or 'out') from a transaction type.
     */
    public static function getFlowForType(?string $type): ?string
    {
        if (empty($type) || $type === 'all') {
            return null;
        }

        $normalized = strtolower(trim($type));

        if (in_array($normalized, self::INFLOW_TYPES, true)) {
            return 'in';
        }

        if (in_array($normalized, self::OUTFLOW_TYPES, true)) {
            return 'out';
        }

        return null;
    }

    public function __construct(
        protected OrderHppCalculationService $orderHppService,
        protected OrderProfitabilityService $profitabilityService,
        protected ?InvoiceService $invoiceService = null
    ) {
        $this->invoiceService = $invoiceService ?? app(InvoiceService::class);
    }

    /**
     * Get consolidated financial records & operational summary (Tahap 4A & 4B).
     *
     * Invariants & Rules:
     * 1. Uang Masuk = Hanya pembayaran customer yang terkonfirmasi (status == CONFIRMED).
     * 2. Uang Keluar = Beban usaha (BusinessExpense) + Pembayaran supplier (PurchasePayment).
     * 3. Pembuatan PO & Penerimaan barang Gudang TIDAK mengurangi kas secara prematur.
     * 4. Kas berkurang HANYA ketika pembayaran supplier riil (PurchasePayment) dicatat.
     * 5. Saldo Awal Kas menetapkan posisi kas awal operasional (bukan revenue, bukan profit).
     * 6. Kas Tersedia Sekarang = Saldo Awal + Total Masuk All-Time - Total Keluar All-Time.
     * 7. Arus Bersih Periode = Total Masuk Periode - Total Keluar Periode (BCMath exact, BUKAN Net Profit).
     * 8. HPP/Modal Produksi menggunakan engine kanonikal Tahap 3 (HPP != Kas).
     *
     * @param array{
     *     start_date?: string|null,
     *     end_date?: string|null,
     *     type?: string|null,
     *     search?: string|null,
     *     per_page?: int|null,
     *     page?: int|null
     * } $filters
     * @return array<string, mixed>
     */
    public function getConsolidatedRecords(array $filters = [], ?int $outletId = null): array
    {
        $isIsolationEnabled = (bool) config('nayara.tenant_isolation_enabled', false);

        $startDate = !empty($filters['start_date']) ? $filters['start_date'] : null;
        $endDate   = !empty($filters['end_date']) ? $filters['end_date'] : null;
        $search    = !empty($filters['search']) ? trim($filters['search']) : (!empty($filters['q']) ? trim($filters['q']) : null);
        $perPage   = (int) ($filters['per_page'] ?? 15);
        $page      = (int) ($filters['page'] ?? Paginator::resolveCurrentPage('page') ?: 1);

        // Normalize Flow ('all', 'in', 'out')
        $rawFlow = strtolower(trim($filters['flow'] ?? ''));
        $rawType = strtolower(trim($filters['type'] ?? ''));

        if ((empty($rawFlow) || $rawFlow === 'all') && in_array($rawType, ['masuk', 'keluar', 'in', 'out', 'all_in', 'all_out'], true)) {
            $rawFlow = $rawType;
            $rawType = 'all';
        }

        if (in_array($rawFlow, ['masuk', 'in', 'uang_masuk', 'all_in'], true)) {
            $flow = 'in';
        } elseif (in_array($rawFlow, ['keluar', 'out', 'uang_keluar', 'all_out'], true)) {
            $flow = 'out';
        } else {
            $flow = 'all';
        }

        // Bidirectional filter synchronization:
        // 1. If flow is unconstrained ('all'), specific transaction type infers effective flow ('in' or 'out').
        // 2. If flow was explicitly chosen ('in' or 'out') and selected type is incompatible, reset type to 'all'.
        $typeInferredFlow = self::getFlowForType($rawType);

        if ($typeInferredFlow !== null) {
            if ($flow === 'all') {
                $flow = $typeInferredFlow;
            } elseif ($flow !== $typeInferredFlow) {
                $rawType = 'all';
            }
        }

        $type = $rawType ?: 'all';

        if ($isIsolationEnabled && $outletId === null) {
            return [
                'records'     => new LengthAwarePaginator(collect(), 0, $perPage, $page, [
                    'path'  => Paginator::resolveCurrentPath(),
                    'query' => request()->query(),
                ]),
                'all_records' => collect(),
                'summary'     => [
                    'total_masuk'  => '0.00',
                    'total_keluar' => '0.00',
                    'arus_bersih'  => '0.00',
                ],
                'cash_position' => [
                    'opening_balance'     => null,
                    'has_opening_balance' => false,
                    'opening_record'      => null,
                    'opening_date'        => null,
                    'kas_tersedia'        => '0.00',
                    'all_time_masuk'      => '0.00',
                    'all_time_keluar'     => '0.00',
                ],
                'sales_summary' => [
                    'total_penjualan' => '0.00',
                    'sudah_dibayar'   => '0.00',
                    'belum_dibayar'   => '0.00',
                ],
                'procurement_summary' => [
                    'total_po'           => '0.00',
                    'po_paid'            => '0.00',
                    'po_outstanding'     => '0.00',
                    'is_sufficient'      => false,
                    'sufficiency_status' => 'INSUFFICIENT',
                    'projected_cash'     => '0.00',
                    'shortage'           => '0.00',
                    'unpaid_purchases'   => collect(),
                ],
                'modal_summary' => [
                    'total_hpp'      => '0.00',
                    'ready_count'    => 0,
                    'unready_count'  => 0,
                    'is_fully_ready' => true,
                ],
                'filters' => [
                    'start_date' => $startDate,
                    'end_date'   => $endDate,
                    'flow'       => $flow,
                    'type'       => $type,
                    'search'     => $search,
                ],
            ];
        }

        $items = collect();

        // ── 0. Authoritative Opening Cash & Cut-off Reference (Phase 2 & 3) ──
        $openingRecord = $isIsolationEnabled
            ? OpeningCashBalance::forOutlet($outletId)->latest('opening_date')->latest('id')->first()
            : OpeningCashBalance::latest('opening_date')->latest('id')->first();
        $hasOpening = $openingRecord !== null;
        $openingAmount = $hasOpening ? (string) $openingRecord->amount : null;
        $openingDate = $hasOpening ? $openingRecord->opening_date->format('Y-m-d') : null;

        // ── 1. Fetch UANG MASUK: Confirmed Customer Payments & Business Income ─
        if ($flow === 'all' || $flow === 'in') {
            // 1a. Customer Payments
            $businessIncomeTypes = ['pemasukan_lain', 'business_income_other', 'modal_tambahan', 'capital_injection', 'pendapatan_non_penjualan', 'non_sales_income'];
            if (!in_array($type, $businessIncomeTypes)) {
                $paymentQuery = Payment::with([
                    'invoice.order.customer',
                    'invoice.order.items.variant.product',
                    'invoice.payments',
                    'confirmedByUser',
                ])->where('status', PaymentStatus::CONFIRMED);

                if ($isIsolationEnabled) {
                    $paymentQuery->whereHas('invoice.order', function ($oq) use ($outletId) {
                        $oq->where('outlet_id', $outletId);
                    });
                }

                if (in_array($type, ['dp', 'customer_dp'])) {
                    $paymentQuery->where('payment_type', PaymentType::DP);
                } elseif (in_array($type, ['pelunasan', 'customer_settlement', 'settlement'])) {
                    $paymentQuery->where('payment_type', PaymentType::PELUNASAN);
                } elseif (in_array($type, ['lainnya', 'customer_other'])) {
                    $paymentQuery->where('payment_type', PaymentType::LAINNYA);
                }

                if ($startDate) {
                    $paymentQuery->whereDate('payment_date', '>=', $startDate);
                }
                if ($endDate) {
                    $paymentQuery->whereDate('payment_date', '<=', $endDate);
                }
                if ($search) {
                    $paymentQuery->where(function ($q) use ($search) {
                        $q->where('reference_no', 'like', "%{$search}%")
                            ->orWhere('notes', 'like', "%{$search}%")
                            ->orWhereHas('invoice', function ($iq) use ($search) {
                                $iq->where('invoice_no', 'like', "%{$search}%")
                                    ->orWhereHas('order', function ($oq) use ($search) {
                                        $oq->where('order_no', 'like', "%{$search}%")
                                            ->orWhereHas('customer', function ($cq) use ($search) {
                                                $cq->where('customer_name', 'like', "%{$search}%");
                                            });
                                    });
                            });
                        $searchLower = strtolower($search);
                        if (str_contains($searchLower, 'pelunasan')) {
                            $q->orWhere('payment_type', PaymentType::PELUNASAN);
                        }
                        if (str_contains($searchLower, 'dp') || str_contains($searchLower, 'uang muka')) {
                            $q->orWhere('payment_type', PaymentType::DP);
                        }
                        if (str_contains($searchLower, 'lainnya')) {
                            $q->orWhere('payment_type', PaymentType::LAINNYA);
                        }
                    });
                }

                $payments = $paymentQuery->get();

                foreach ($payments as $pay) {
                    $invoice = $pay->invoice;
                    $order = $invoice?->order;
                    $customerName = $order?->customer?->customer_name;
                    $orderNo = $order?->order_no;
                    $invoiceNo = $invoice?->invoice_no;

                    $typeLabel = $pay->payment_type ? $pay->payment_type->label() : 'Pembayaran Customer';
                    $desc = 'Pembayaran ' . $typeLabel;
                    if ($orderNo) {
                        $desc .= ' - Order #' . $orderNo;
                    }
                    if ($customerName) {
                        $desc .= ' (' . $customerName . ')';
                    }
                    if ($pay->notes) {
                        $desc .= ': ' . $pay->notes;
                    }

                    $payDate = $pay->payment_date ? $pay->payment_date->toDateString() : $pay->created_at->toDateString();
                    $isHistorical = $hasOpening && strcmp($payDate, $openingDate) < 0;

                    $orderItems = [];
                    $totalOrderQty = '0';
                    $totalOrderAmount = '0.00';

                    if ($order && $order->items) {
                        foreach ($order->items as $oItem) {
                            $variant = $oItem->variant;
                            $product = $variant?->product;
                            $productName = $product?->product_name ?: ('Produk #' . ($oItem->variant_id ?? $oItem->id));
                            $variantName = $variant?->variant_name;

                            // Subtotal calculated with exact BCMath from transaction snapshot qty_ordered * unit_selling_price
                            $itemSubtotal = bcmul((string) $oItem->qty_ordered, (string) $oItem->unit_selling_price, 2);
                            $totalOrderQty = bcadd($totalOrderQty, (string) $oItem->qty_ordered, 4);
                            $totalOrderAmount = bcadd($totalOrderAmount, $itemSubtotal, 2);

                            $orderItems[] = [
                                'item_id'              => $oItem->id,
                                'product_name'         => $productName,
                                'variant_name'         => $variantName,
                                'full_name'            => $variantName ? ($productName . ' - ' . $variantName) : $productName,
                                'qty'                  => (string) $oItem->qty_ordered,
                                'unit'                 => 'pcs',
                                'qty_formatted'        => \App\Support\QuantityFormatter::format($oItem->qty_ordered, 'pcs'),
                                'unit_price'           => (string) $oItem->unit_selling_price,
                                'unit_price_formatted' => 'Rp ' . number_format((float) $oItem->unit_selling_price, 0, ',', '.'),
                                'subtotal'             => $itemSubtotal,
                                'subtotal_formatted'   => 'Rp ' . number_format((float) $itemSubtotal, 0, ',', '.'),
                            ];
                        }
                    }

                    $paymentHistory = [];
                    $paymentSummary = null;

                    if ($invoice) {
                        $invoiceTotal = (string) $invoice->total_amount;
                        $totalConfirmedPaid = $this->invoiceService->calculatePaidAmount($invoice);
                        $outstandingBalance = $this->invoiceService->calculateOutstandingBalance($invoice);

                        $allPayments = $invoice->relationLoaded('payments')
                            ? $invoice->payments
                            : $invoice->payments()->get();

                        // Sort ascending (oldest first): payment_date ASC, then confirmed_at/created_at ASC, then id ASC
                        $sortedPayments = $allPayments->sort(function ($a, $b) {
                            $dateA = $a->payment_date ? $a->payment_date->toDateString() : ($a->created_at ? $a->created_at->toDateString() : '');
                            $dateB = $b->payment_date ? $b->payment_date->toDateString() : ($b->created_at ? $b->created_at->toDateString() : '');
                            if ($dateA !== $dateB) {
                                return strcmp($dateA, $dateB);
                            }
                            $timeA = $a->confirmed_at ? $a->confirmed_at->timestamp : ($a->created_at ? $a->created_at->timestamp : 0);
                            $timeB = $b->confirmed_at ? $b->confirmed_at->timestamp : ($b->created_at ? $b->created_at->timestamp : 0);
                            if ($timeA !== $timeB) {
                                return $timeA <=> $timeB;
                            }
                            return $a->id <=> $b->id;
                        })->values();

                        foreach ($sortedPayments as $histPay) {
                            $pDate = $histPay->payment_date ? $histPay->payment_date->toDateString() : ($histPay->created_at ? $histPay->created_at->toDateString() : null);
                            $pTime = $histPay->confirmed_at ?? $histPay->created_at;

                            $pTypeLabel = $histPay->payment_type instanceof PaymentType
                                ? $histPay->payment_type->label()
                                : ($histPay->payment_type ? (string) $histPay->payment_type : 'Pembayaran');

                            $statusCode = $histPay->status instanceof PaymentStatus
                                ? $histPay->status->value
                                : (string) $histPay->status;

                            $pStatusLabel = match ($statusCode) {
                                'CONFIRMED' => 'Terkonfirmasi',
                                'PENDING'   => 'Menunggu Konfirmasi',
                                'CANCELLED' => 'Dibatalkan',
                                default     => $statusCode,
                            };

                            $pStatusBadge = match ($statusCode) {
                                'CONFIRMED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'PENDING'   => 'bg-amber-50 text-amber-700 border-amber-200',
                                'CANCELLED' => 'bg-red-50 text-red-700 border-red-200',
                                default     => 'bg-gray-50 text-gray-700 border-gray-200',
                            };

                            $isCurrent = ((int) $histPay->id === (int) $pay->id);

                            $paymentHistory[] = [
                                'payment_id'           => $histPay->id,
                                'is_current'           => $isCurrent,
                                'reference_no'         => $histPay->reference_no ?: ('PAY-' . $histPay->id),
                                'date'                 => $pDate,
                                'date_formatted'       => $pDate ? \App\Support\DateTimeFormatter::formatDate($pDate, 'd/m/Y') : '-',
                                'time_formatted'       => $pTime ? \App\Support\DateTimeFormatter::formatTime($pTime) : null,
                                'type'                 => $histPay->payment_type instanceof PaymentType ? $histPay->payment_type->value : (string) $histPay->payment_type,
                                'type_label'           => $pTypeLabel,
                                'amount'               => (string) $histPay->amount,
                                'amount_formatted'     => 'Rp ' . number_format((float) $histPay->amount, 0, ',', '.'),
                                'status'               => $statusCode,
                                'status_label'         => $pStatusLabel,
                                'status_badge'         => $pStatusBadge,
                                'is_confirmed'         => ($statusCode === PaymentStatus::CONFIRMED->value),
                            ];
                        }

                        $summaryStatusLabel = 'Belum Lunas';
                        $summaryBadge = 'bg-amber-50 text-amber-700 border-amber-200';
                        $isLunas = false;

                        if ($invoice->status === \App\Enums\InvoiceStatus::CANCELLED) {
                            $summaryStatusLabel = 'Dibatalkan';
                            $summaryBadge = 'bg-red-50 text-red-700 border-red-200';
                        } elseif (bccomp($outstandingBalance, '0.00', 2) <= 0) {
                            $summaryStatusLabel = 'Lunas';
                            $summaryBadge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                            $isLunas = true;
                        } elseif (bccomp($totalConfirmedPaid, '0.00', 2) > 0) {
                            $summaryStatusLabel = 'Belum Lunas / Sebagian';
                            $summaryBadge = 'bg-amber-50 text-amber-700 border-amber-200';
                        }

                        $paymentSummary = [
                            'total_invoice_amount'           => $invoiceTotal,
                            'total_invoice_amount_formatted' => 'Rp ' . number_format((float) $invoiceTotal, 0, ',', '.'),
                            'total_paid'                     => $totalConfirmedPaid,
                            'total_paid_formatted'           => 'Rp ' . number_format((float) $totalConfirmedPaid, 0, ',', '.'),
                            'outstanding'                    => $outstandingBalance,
                            'status_label'                   => $summaryStatusLabel,
                            'status_badge'                   => $summaryBadge,
                            'is_lunas'                       => $isLunas,
                        ];
                    }

                    $items->push([
                        'id'                           => 'pay_' . $pay->id,
                        'source_type'                  => 'PAYMENT',
                        'source_id'                    => $pay->id,
                        'date'                         => $payDate,
                        'type'                         => 'MASUK',
                        'type_label'                   => 'Uang Masuk',
                        'category'                     => $typeLabel,
                        'reference_code'               => $invoiceNo ?: ($pay->reference_no ?: 'PAY-' . $pay->id),
                        'reference_url'                => $pay->invoice_id ? route('admin.invoices.show', $pay->invoice_id) : null,
                        'order_no'                     => $orderNo,
                        'customer_name'                => $customerName,
                        'supplier_name'                => null,
                        'notes'                        => $pay->notes,
                        'description'                  => $desc,
                        'amount_in'                    => (string) $pay->amount,
                        'amount_out'                   => '0.00',
                        'net_amount'                   => (string) $pay->amount,
                        'actor_name'                   => $pay->confirmedByUser?->name,
                        'timestamp'                    => $pay->confirmed_at ?? $pay->created_at,
                        'sort_date'                    => $payDate,
                        'sort_timestamp'               => $pay->payment_date ? $pay->payment_date->timestamp : $pay->created_at->timestamp,
                        'is_historical'                => $isHistorical,
                        'purchase_items'               => [],
                        'order_items'                  => $orderItems,
                        'total_order_qty_formatted'    => !empty($orderItems) ? \App\Support\QuantityFormatter::format($totalOrderQty, 'pcs') : null,
                        'total_order_amount'           => !empty($orderItems) ? $totalOrderAmount : null,
                        'total_order_amount_formatted' => !empty($orderItems) ? ('Rp ' . number_format((float) $totalOrderAmount, 0, ',', '.')) : null,
                        'payment_history'              => $paymentHistory,
                        'payment_summary'              => $paymentSummary,
                    ]);
                }
            }

            // 1b. Business Income (Manual Non-Invoice Cash Inflow)
            $customerPaymentTypes = ['dp', 'customer_dp', 'pelunasan', 'customer_settlement', 'settlement', 'lainnya', 'customer_other'];
            if (!in_array($type, $customerPaymentTypes, true)) {
                $incomeQuery = $isIsolationEnabled
                    ? BusinessIncome::forOutlet($outletId)->with('createdByUser')
                    : BusinessIncome::with('createdByUser');

                if (in_array($type, ['pemasukan_lain', 'business_income_other'])) {
                    $incomeQuery->where('category', BusinessIncome::CATEGORY_PEMASUKAN_LAIN);
                } elseif (in_array($type, ['modal_tambahan', 'capital_injection'])) {
                    $incomeQuery->where('category', BusinessIncome::CATEGORY_MODAL_TAMBAHAN);
                } elseif (in_array($type, ['pendapatan_non_penjualan', 'non_sales_income'])) {
                    $incomeQuery->where('category', BusinessIncome::CATEGORY_NON_PENJUALAN);
                }

                if ($startDate) {
                    $incomeQuery->whereDate('income_date', '>=', $startDate);
                }
                if ($endDate) {
                    $incomeQuery->whereDate('income_date', '<=', $endDate);
                }
                if ($search) {
                    $incomeQuery->where(function ($q) use ($search) {
                        $q->where('income_no', 'like', "%{$search}%")
                            ->orWhere('category', 'like', "%{$search}%")
                            ->orWhere('notes', 'like', "%{$search}%");
                    });
                }

                $incomes = $incomeQuery->get();

                foreach ($incomes as $inc) {
                    $incDate = $inc->income_date ? $inc->income_date->toDateString() : $inc->created_at->toDateString();
                    $isHistorical = $hasOpening && strcmp($incDate, $openingDate) < 0;

                    $desc = 'Pemasukan: ' . $inc->category;
                    if ($inc->notes) {
                        $desc .= ' (' . $inc->notes . ')';
                    }

                    $items->push([
                        'id'               => 'inc_' . $inc->id,
                        'source_type'      => 'BUSINESS_INCOME',
                        'source_id'        => $inc->id,
                        'date'             => $incDate,
                        'type'             => 'MASUK',
                        'type_label'       => 'Uang Masuk',
                        'category'         => $inc->category,
                        'reference_code'   => $inc->income_no,
                        'reference_url'    => null,
                        'order_no'         => null,
                        'customer_name'    => null,
                        'supplier_name'    => null,
                        'notes'            => $inc->notes,
                        'description'      => $desc,
                        'amount_in'        => (string) $inc->amount,
                        'amount_out'       => '0.00',
                        'net_amount'       => (string) $inc->amount,
                        'actor_name'       => $inc->createdByUser?->name,
                        'timestamp'        => $inc->created_at,
                        'sort_date'        => $incDate,
                        'sort_timestamp'   => $inc->income_date ? $inc->income_date->timestamp : $inc->created_at->timestamp,
                        'is_historical'                => $isHistorical,
                        'purchase_items'               => [],
                        'order_items'                  => [],
                        'total_order_qty_formatted'    => null,
                        'total_order_amount'           => null,
                        'total_order_amount_formatted' => null,
                        'payment_history'              => [],
                        'payment_summary'              => null,
                    ]);
                }
            }
        }

        // ── 2. Fetch UANG KELUAR: Business Expenses ──────────────────────
        $expenseTypeMapping = [
            'marketing'        => ExpenseType::MARKETING,
            'operasional'      => ExpenseType::OPERATING,
            'operating'        => ExpenseType::OPERATING,
            'bunga'            => ExpenseType::INTEREST,
            'interest'         => ExpenseType::INTEREST,
            'pajak'            => ExpenseType::TAX,
            'tax'              => ExpenseType::TAX,
            'penyusutan'       => ExpenseType::DEPRECIATION,
            'depresiasi'       => ExpenseType::DEPRECIATION,
            'depreciation'     => ExpenseType::DEPRECIATION,
            'amortisasi'       => ExpenseType::AMORTIZATION,
            'amortization'     => ExpenseType::AMORTIZATION,
        ];

        $poPaymentTypes = ['pembayaran_supplier', 'supplier_payment', 'purchase_payment'];
        $canFetchOutflow = ($flow === 'all' || $flow === 'out');
        $isSpecificInflowType = in_array($type, self::INFLOW_TYPES, true);
        $isSpecificPoPaymentType = in_array($type, $poPaymentTypes, true);

        if ($canFetchOutflow && ! $isSpecificInflowType && ! $isSpecificPoPaymentType) {
            $expenseQuery = $isIsolationEnabled
                ? BusinessExpense::forOutlet($outletId)->with(['order.customer', 'createdByUser'])
                : BusinessExpense::with(['order.customer', 'createdByUser']);

            if (isset($expenseTypeMapping[$type])) {
                $expenseQuery->where('expense_type', $expenseTypeMapping[$type]);
            }

            if ($startDate) {
                $expenseQuery->whereDate('expense_date', '>=', $startDate);
            }
            if ($endDate) {
                $expenseQuery->whereDate('expense_date', '<=', $endDate);
            }
            if ($search) {
                $expenseQuery->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                        ->orWhere('campaign_name', 'like', "%{$search}%")
                        ->orWhereHas('order', function ($oq) use ($search) {
                            $oq->where('order_no', 'like', "%{$search}%")
                                ->orWhereHas('customer', function ($cq) use ($search) {
                                    $cq->where('customer_name', 'like', "%{$search}%");
                                });
                        });

                    $expenseTypeMatches = [];
                    foreach (ExpenseType::cases() as $case) {
                        if (stripos($case->label(), $search) !== false
                            || stripos('Beban ' . $case->label(), $search) !== false
                            || stripos($case->value, $search) !== false) {
                            $expenseTypeMatches[] = $case->value;
                        }
                    }
                    if (!empty($expenseTypeMatches)) {
                        $q->orWhereIn('expense_type', $expenseTypeMatches);
                    }

                    if (preg_match('/(?:exp-)?0*([1-9]\d*)/i', $search, $expMatches)) {
                        $q->orWhere('id', (int) $expMatches[1]);
                    }
                });
            }

            $expenses = $expenseQuery->get();

            foreach ($expenses as $exp) {
                $order = $exp->order;
                $customerName = $order?->customer?->customer_name;
                $orderNo = $order?->order_no;

                $desc = $exp->description;
                if ($exp->campaign_name) {
                    $desc .= ' [Kampanye: ' . $exp->campaign_name . ']';
                }
                if ($orderNo) {
                    $desc .= ' (Order #' . $orderNo . ($customerName ? ' - ' . $customerName : '') . ')';
                }

                $expDate = $exp->expense_date ? $exp->expense_date->toDateString() : $exp->created_at->toDateString();
                $isHistorical = $hasOpening && strcmp($expDate, $openingDate) < 0;

                $items->push([
                    'id'               => 'exp_' . $exp->id,
                    'source_type'      => 'EXPENSE',
                    'source_id'        => $exp->id,
                    'date'             => $expDate,
                    'type'             => 'KELUAR',
                    'type_label'       => 'Uang Keluar',
                    'category'         => 'Beban ' . $exp->expense_type->label(),
                    'reference_code'   => 'EXP-' . str_pad((string) $exp->id, 5, '0', STR_PAD_LEFT),
                    'reference_url'    => route('admin.expenses.show', $exp->id),
                    'order_no'         => $orderNo,
                    'customer_name'    => $customerName,
                    'supplier_name'    => null,
                    'notes'            => null,
                    'description'      => $desc,
                    'amount_in'        => '0.00',
                    'amount_out'       => (string) $exp->amount,
                    'net_amount'       => '-' . $exp->amount,
                    'actor_name'       => $exp->createdByUser?->name,
                    'timestamp'        => $exp->created_at,
                    'sort_date'        => $expDate,
                    'sort_timestamp'   => $exp->expense_date ? $exp->expense_date->timestamp : $exp->created_at->timestamp,
                    'is_historical'                => $isHistorical,
                    'purchase_items'               => [],
                    'order_items'                  => [],
                    'total_order_qty_formatted'    => null,
                    'total_order_amount'           => null,
                    'total_order_amount_formatted' => null,
                    'payment_history'              => [],
                    'payment_summary'              => null,
                ]);
            }
        }

        // ── 3. Fetch UANG KELUAR: Supplier / Purchase Payments ───────
        $isSpecificExpenseType = isset($expenseTypeMapping[$type]);

        if ($canFetchOutflow && ! $isSpecificInflowType && ! $isSpecificExpenseType) {
            $purchasePaymentQuery = PurchasePayment::with([
                'purchase.supplier',
                'purchase.items.material.unit',
                'createdByUser',
            ]);

            if ($isIsolationEnabled) {
                $purchasePaymentQuery->whereHas('purchase', function ($pq) use ($outletId) {
                    $pq->where('outlet_id', $outletId);
                });
            }

            if ($startDate) {
                $purchasePaymentQuery->whereDate('payment_date', '>=', $startDate);
            }
            if ($endDate) {
                $purchasePaymentQuery->whereDate('payment_date', '<=', $endDate);
            }
            if ($search) {
                $purchasePaymentQuery->where(function ($q) use ($search) {
                    $q->where('reference_no', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhereHas('purchase', function ($pq) use ($search) {
                            $pq->where('purchase_no', 'like', "%{$search}%")
                                ->orWhereHas('supplier', function ($sq) use ($search) {
                                    $sq->where('supplier_name', 'like', "%{$search}%");
                                });
                            if (preg_match('/(?:po-)?0*([1-9]\d*)/i', $search, $poMatches)) {
                                $pq->orWhere('id', (int) $poMatches[1]);
                            }
                        });
                    if (stripos('Pembayaran Supplier', $search) !== false) {
                        $q->orWhereNotNull('id');
                    }
                });
            }

            $purchasePayments = $purchasePaymentQuery->get();

            foreach ($purchasePayments as $pPay) {
                $purchase = $pPay->purchase;
                $supplierName = $purchase?->supplier?->supplier_name;
                $purchaseNo = $purchase?->purchase_no ?: ('PO-' . $pPay->purchase_id);

                $desc = 'Pembayaran Supplier: ' . ($supplierName ?: 'Supplier') . ' - PO #' . $purchaseNo;
                if ($pPay->notes) {
                    $desc .= ' (' . $pPay->notes . ')';
                }

                $pPayDate = $pPay->payment_date ? $pPay->payment_date->toDateString() : $pPay->created_at->toDateString();
                $isHistorical = $hasOpening && strcmp($pPayDate, $openingDate) < 0;

                $purchaseItems = [];
                if ($purchase && $purchase->items) {
                    foreach ($purchase->items as $pItem) {
                        $material = $pItem->material;
                        $matName = $material?->material_name ?: ('Material #' . $pItem->material_id);
                        $unitSymbol = $material?->unit?->symbol ?: ($material?->unit?->unit_name ?: '');

                        // Subtotal using BCMath
                        $itemSubtotal = bcmul((string) $pItem->qty, (string) $pItem->unit_price, 2);

                        $purchaseItems[] = [
                            'material_id'          => $pItem->material_id,
                            'material_name'        => $matName,
                            'qty'                  => (string) $pItem->qty,
                            'unit'                 => $unitSymbol,
                            'qty_formatted'        => \App\Support\QuantityFormatter::format($pItem->qty, $unitSymbol),
                            'unit_price'           => (string) $pItem->unit_price,
                            'unit_price_formatted' => 'Rp ' . number_format((float) $pItem->unit_price, 0, ',', '.'),
                            'subtotal'             => $itemSubtotal,
                            'subtotal_formatted'   => 'Rp ' . number_format((float) $itemSubtotal, 0, ',', '.'),
                        ];
                    }
                }

                $items->push([
                    'id'               => 'po_pay_' . $pPay->id,
                    'source_type'      => 'PURCHASE_PAYMENT',
                    'source_id'        => $pPay->id,
                    'date'             => $pPayDate,
                    'type'             => 'KELUAR',
                    'type_label'       => 'Uang Keluar',
                    'category'         => 'Pembayaran Supplier',
                    'reference_code'   => $purchaseNo,
                    'reference_url'    => route('gudang.purchases.show', $pPay->purchase_id),
                    'order_no'         => null,
                    'customer_name'    => null,
                    'supplier_name'    => $supplierName,
                    'notes'            => $pPay->notes,
                    'description'      => $desc,
                    'amount_in'        => '0.00',
                    'amount_out'       => (string) $pPay->amount,
                    'net_amount'       => '-' . $pPay->amount,
                    'actor_name'       => $pPay->createdByUser?->name,
                    'timestamp'        => $pPay->created_at,
                    'sort_date'        => $pPayDate,
                    'sort_timestamp'   => $pPay->payment_date ? $pPay->payment_date->timestamp : $pPay->created_at->timestamp,
                    'is_historical'                => $isHistorical,
                    'purchase_items'               => $purchaseItems,
                    'order_items'                  => [],
                    'total_order_qty_formatted'    => null,
                    'total_order_amount'           => null,
                    'total_order_amount_formatted' => null,
                    'payment_history'              => [],
                    'payment_summary'              => null,
                    'transfer_proof_url'           => $pPay->transfer_proof_path ? route('admin.purchases.payments.proof', $pPay) : null,
                ]);
            }
        }

        // ── 4. Sort consolidated items chronologically descending ─────────
        $sortedItems = $items->sort(function ($a, $b) {
            if ($a['sort_date'] !== $b['sort_date']) {
                return strcmp($b['sort_date'], $a['sort_date']);
            }
            return strcmp($b['id'], $a['id']);
        })->values();

        // ── 5. Calculate Period Summary using exact BCMath ────────────────
        $totalMasuk = '0.00';
        $totalKeluar = '0.00';

        foreach ($sortedItems as $item) {
            if ($item['type'] === 'MASUK') {
                $totalMasuk = bcadd($totalMasuk, $item['amount_in'], 2);
            } elseif ($item['type'] === 'KELUAR') {
                $totalKeluar = bcadd($totalKeluar, $item['amount_out'], 2);
            }
        }

        $arusBersih = bcsub($totalMasuk, $totalKeluar, 2);

        // ── 6. Paginate items ─────────────────────────────────────────────
        $slicedItems = $sortedItems->slice(($page - 1) * $perPage, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $slicedItems,
            $sortedItems->count(),
            $perPage,
            $page,
            [
                'path'  => Paginator::resolveCurrentPath(),
                'query' => request()->query(),
            ]
        );

        // ── 7. Calculate Cash Position & Opening Balance (Phase 2, 3, 4) ─
        $kasTersedia = $this->calculateCurrentCash($openingRecord, $isIsolationEnabled ? $outletId : null);

        $allTimeMasuk = '0.00';
        $allTimePaymentsQuery = Payment::where('status', PaymentStatus::CONFIRMED);
        if ($isIsolationEnabled) {
            $allTimePaymentsQuery->whereHas('invoice.order', fn($q) => $q->where('outlet_id', $outletId));
        }
        $allTimePayments = $allTimePaymentsQuery->get(['amount']);
        foreach ($allTimePayments as $atp) {
            $allTimeMasuk = bcadd($allTimeMasuk, (string) $atp->amount, 2);
        }

        $allTimeIncomesQuery = $isIsolationEnabled
            ? BusinessIncome::forOutlet($outletId)
            : BusinessIncome::query();
        $allTimeIncomes = $allTimeIncomesQuery->get(['amount']);
        foreach ($allTimeIncomes as $ati) {
            $allTimeMasuk = bcadd($allTimeMasuk, (string) $ati->amount, 2);
        }

        $allTimeExpenses = '0.00';
        $allTimeExpensesQuery = $isIsolationEnabled
            ? BusinessExpense::forOutlet($outletId)
            : BusinessExpense::query();
        $allTimeExpenseRows = $allTimeExpensesQuery->get(['amount']);
        foreach ($allTimeExpenseRows as $ate) {
            $allTimeExpenses = bcadd($allTimeExpenses, (string) $ate->amount, 2);
        }

        $allTimePoPaymentsQuery = PurchasePayment::query();
        if ($isIsolationEnabled) {
            $allTimePoPaymentsQuery->whereHas('purchase', fn($q) => $q->where('outlet_id', $outletId));
        }
        $allTimePoPaymentRows = $allTimePoPaymentsQuery->get(['amount']);
        $allTimePoPayments = '0.00';
        foreach ($allTimePoPaymentRows as $atpp) {
            $allTimePoPayments = bcadd($allTimePoPayments, (string) $atpp->amount, 2);
        }

        $allTimeKeluar = bcadd($allTimeExpenses, $allTimePoPayments, 2);

        // ── 8. Calculate Sales Summary (Phase 2, 3, 4) ────────────────────
        $salesBase = $isIsolationEnabled ? Order::forOutlet($outletId) : Order::query();
        $salesQuery = $salesBase->with('items')
            ->whereNotIn('status', [OrderStatus::KOMUNIKASI, 'CANCELLED']);

        if ($startDate) {
            $salesQuery->whereDate('order_date', '>=', $startDate);
        }
        if ($endDate) {
            $salesQuery->whereDate('order_date', '<=', $endDate);
        }

        $salesOrders = $salesQuery->get();
        $totalPenjualan = '0.00';

        foreach ($salesOrders as $order) {
            $orderTotal = '0.00';
            foreach ($order->items as $item) {
                $subtotal = bcmul((string) $item->qty_ordered, (string) $item->unit_selling_price, 2);
                $orderTotal = bcadd($orderTotal, $subtotal, 2);
            }
            $totalPenjualan = bcadd($totalPenjualan, $orderTotal, 2);
        }

        // Sudah Dibayar Customer (Confirmed payments in period or overall)
        $paidQuery = Payment::where('status', PaymentStatus::CONFIRMED);
        if ($isIsolationEnabled) {
            $paidQuery->whereHas('invoice.order', fn($q) => $q->where('outlet_id', $outletId));
        }
        if ($startDate) {
            $paidQuery->whereDate('payment_date', '>=', $startDate);
        }
        if ($endDate) {
            $paidQuery->whereDate('payment_date', '<=', $endDate);
        }
        $paidRows = $paidQuery->get(['amount']);
        $sudahDibayar = '0.00';
        foreach ($paidRows as $pr) {
            $sudahDibayar = bcadd($sudahDibayar, (string) $pr->amount, 2);
        }

        $belumDibayar = bcsub($totalPenjualan, $sudahDibayar, 2);
        if (bccomp($belumDibayar, '0.00', 2) < 0) {
            $belumDibayar = '0.00';
        }

        // ── 9. Calculate Procurement & Cash Sufficiency (Phase 4, 5, 15) ─
        $procurementSummary = $this->calculateProcurementSummary($kasTersedia, $isIsolationEnabled ? $outletId : null);
        $totalPo           = $procurementSummary['total_po'];
        $poPaid            = $procurementSummary['po_paid'];
        $poOutstanding     = $procurementSummary['po_outstanding'];
        $isSufficient      = $procurementSummary['is_sufficient'];
        $sufficiencyStatus = $procurementSummary['sufficiency_status'];
        $projectedCash     = $procurementSummary['projected_cash'];
        $shortage          = $procurementSummary['shortage'];
        $unpaidPurchases   = $procurementSummary['unpaid_purchases'];

        // ── 10. Calculate Modal / HPP Produksi (Phase 15, 16) ─────────────
        $hppBase = $isIsolationEnabled ? Order::forOutlet($outletId) : Order::query();
        $hppOrdersQuery = $hppBase->with([
            'items.variant.product',
            'items.productionBatches.qcResult',
            'items.productionBatches.batchMaterials.material',
            'items.productionBatches.batchCosts',
        ])->whereNotIn('status', [OrderStatus::KOMUNIKASI, 'CANCELLED']);

        if ($startDate) {
            $hppOrdersQuery->whereDate('order_date', '>=', $startDate);
        }
        if ($endDate) {
            $hppOrdersQuery->whereDate('order_date', '<=', $endDate);
        }

        $hppOrders = $hppOrdersQuery->get();
        $totalHppFinal = '0.00';
        $unreadyCount = 0;
        $readyCount = 0;

        foreach ($hppOrders as $hOrder) {
            $hppRes = $this->orderHppService->calculateOrderHpp($hOrder);
            if ($hppRes['is_ready'] && !empty($hppRes['total_hpp'])) {
                $totalHppFinal = bcadd($totalHppFinal, (string) $hppRes['total_hpp'], 2);
                $readyCount++;
            } else {
                $unreadyCount++;
            }
        }

        return [
            'records'     => $paginator,
            'all_records' => $sortedItems,
            'summary'     => [
                'total_masuk'  => $totalMasuk,
                'total_keluar' => $totalKeluar,
                'arus_bersih'  => $arusBersih,
            ],
            'cash_position' => [
                'opening_balance'     => $openingAmount,
                'has_opening_balance' => $hasOpening,
                'opening_record'      => $openingRecord,
                'opening_date'        => $openingDate,
                'kas_tersedia'        => $kasTersedia,
                'all_time_masuk'      => $allTimeMasuk,
                'all_time_keluar'     => $allTimeKeluar,
            ],
            'sales_summary' => [
                'total_penjualan' => $totalPenjualan,
                'sudah_dibayar'   => $sudahDibayar,
                'belum_dibayar'   => $belumDibayar,
            ],
            'procurement_summary' => [
                'total_po'           => $totalPo,
                'po_paid'            => $poPaid,
                'po_outstanding'     => $poOutstanding,
                'is_sufficient'      => $isSufficient,
                'sufficiency_status' => $sufficiencyStatus,
                'projected_cash'     => $projectedCash,
                'shortage'           => $shortage,
                'unpaid_purchases'   => $unpaidPurchases,
            ],
            'modal_summary' => [
                'hpp_final'     => $totalHppFinal,
                'ready_count'   => $readyCount,
                'unready_count' => $unreadyCount,
            ],
            'filters'     => [
                'start_date' => $startDate,
                'end_date'   => $endDate,
                'flow'       => $flow,
                'type'       => $type,
                'search'     => $search,
                'q'          => $search,
            ],
        ];
    }

    /**
     * Calculate current operational cash starting from the cut-off date using BCMath (scale 2).
     *
     * Invariants (Authoritative Cut-Off Rule):
     * 1. Saldo Awal Kas adalah posisi kas nyata pada AWAL HARI opening_date.
     * 2. Seluruh transaksi dengan tanggal >= opening_date memengaruhi Kas Tersedia.
     * 3. Seluruh transaksi dengan tanggal < opening_date adalah HISTORIS dan TIDAK
     *    memengaruhi Kas Tersedia saat ini (sudah terefleksi di saldo awal).
     * 4. Jika Saldo Awal Kas belum ditentukan, Kas Tersedia adalah NULL / belum tersedia.
     */
    public function calculateCurrentCash(?OpeningCashBalance $openingRecord = null, ?int $outletId = null): ?string
    {
        $isIsolationEnabled = (bool) config('nayara.tenant_isolation_enabled', false);

        if ($isIsolationEnabled) {
            // Fail-closed: Isolation enabled requires a legitimate tenant outlet ID
            if ($outletId === null) {
                return null;
            }

            // Explicit opening record safety (Section I):
            // 1. Must belong to the specified outlet
            // 2. NULL ownership or foreign tenant ownership must be rejected
            if ($openingRecord !== null) {
                if ($openingRecord->outlet_id === null || (int) $openingRecord->outlet_id !== $outletId) {
                    return null;
                }
                $opening = $openingRecord;
            } else {
                $opening = OpeningCashBalance::forOutlet($outletId)
                    ->latest('opening_date')
                    ->latest('id')
                    ->first();
            }

            // Missing opening policy (Section J): Fail-closed if no opening record for tenant
            if (! $opening) {
                return null;
            }

            $openingDate = $opening->opening_date->format('Y-m-d');
            $openingAmount = (string) $opening->amount;

            // 1. Confirmed Customer Payments ON / AFTER cut-off scoped to tenant
            $applicableMasuk = '0.00';
            $payments = Payment::where('status', PaymentStatus::CONFIRMED)
                ->whereDate('payment_date', '>=', $openingDate)
                ->whereHas('invoice.order', fn ($q) => $q->where('orders.outlet_id', $outletId))
                ->get(['amount']);

            foreach ($payments as $p) {
                $applicableMasuk = bcadd($applicableMasuk, (string) $p->amount, 2);
            }

            // 1b. Business Incomes ON / AFTER cut-off scoped to tenant (no global fallback)
            $incomes = BusinessIncome::forOutlet($outletId)
                ->whereDate('income_date', '>=', $openingDate)
                ->get(['amount']);

            foreach ($incomes as $inc) {
                $applicableMasuk = bcadd($applicableMasuk, (string) $inc->amount, 2);
            }

            // 2. Business Expenses ON / AFTER cut-off scoped to tenant (no global fallback)
            $applicableExpenses = '0.00';
            $expenses = BusinessExpense::forOutlet($outletId)
                ->whereDate('expense_date', '>=', $openingDate)
                ->get(['amount']);

            foreach ($expenses as $e) {
                $applicableExpenses = bcadd($applicableExpenses, (string) $e->amount, 2);
            }

            // 3. Supplier Payments ON / AFTER cut-off scoped to tenant
            $applicablePoPayments = '0.00';
            $poPayments = PurchasePayment::whereDate('payment_date', '>=', $openingDate)
                ->whereHas('purchase', fn ($q) => $q->where('purchases.outlet_id', $outletId))
                ->get(['amount']);

            foreach ($poPayments as $pop) {
                $applicablePoPayments = bcadd($applicablePoPayments, (string) $pop->amount, 2);
            }

            $applicableKeluar = bcadd($applicableExpenses, $applicablePoPayments, 2);

            return bcadd($openingAmount, bcsub($applicableMasuk, $applicableKeluar, 2), 2);
        }

        // --- LEGACY BRANCH (nayara.tenant_isolation_enabled = false) ---
        // Preserves exact single-tenant behavior without requiring finance.outlet_id columns
        $opening = $openingRecord ?? OpeningCashBalance::latest('opening_date')->latest('id')->first();
        if (! $opening) {
            return null;
        }

        $openingDate = $opening->opening_date->format('Y-m-d');
        $openingAmount = (string) $opening->amount;

        // 1. Confirmed Customer Payments ON / AFTER cut-off
        $applicableMasuk = '0.00';
        $paymentQuery = Payment::where('status', PaymentStatus::CONFIRMED)
            ->whereDate('payment_date', '>=', $openingDate);
        if ($outletId !== null) {
            $paymentQuery->whereHas('invoice.order', fn ($q) => $q->where('orders.outlet_id', $outletId));
        }
        $payments = $paymentQuery->get(['amount']);
        foreach ($payments as $p) {
            $applicableMasuk = bcadd($applicableMasuk, (string) $p->amount, 2);
        }

        // 1b. Business Incomes ON / AFTER cut-off (legacy un-scoped)
        $incomes = BusinessIncome::whereDate('income_date', '>=', $openingDate)
            ->get(['amount']);
        foreach ($incomes as $inc) {
            $applicableMasuk = bcadd($applicableMasuk, (string) $inc->amount, 2);
        }

        // 2. Business Expenses ON / AFTER cut-off (legacy un-scoped)
        $applicableExpenses = '0.00';
        $expenses = BusinessExpense::whereDate('expense_date', '>=', $openingDate)
            ->get(['amount']);
        foreach ($expenses as $e) {
            $applicableExpenses = bcadd($applicableExpenses, (string) $e->amount, 2);
        }

        // 3. Supplier Payments ON / AFTER cut-off
        $applicablePoPayments = '0.00';
        $poPaymentQuery = PurchasePayment::whereDate('payment_date', '>=', $openingDate);
        if ($outletId !== null) {
            $poPaymentQuery->whereHas('purchase', fn ($q) => $q->where('purchases.outlet_id', $outletId));
        }
        $poPayments = $poPaymentQuery->get(['amount']);
        foreach ($poPayments as $pop) {
            $applicablePoPayments = bcadd($applicablePoPayments, (string) $pop->amount, 2);
        }

        $applicableKeluar = bcadd($applicableExpenses, $applicablePoPayments, 2);

        return bcadd($openingAmount, bcsub($applicableMasuk, $applicableKeluar, 2), 2);
    }

    /**
     * Calculate procurement commitment & cash sufficiency (canonical supplier obligations).
     *
     * @return array{
     *     total_po: string,
     *     po_paid: string,
     *     po_outstanding: string,
     *     is_sufficient: bool,
     *     sufficiency_status: string,
     *     projected_cash: string|null,
     *     shortage: string|null,
     *     unpaid_purchases: \Illuminate\Support\Collection
     * }
     */
    public function calculateProcurementSummary(?string $kasTersedia = null, ?int $outletId = null): array
    {
        $isIsolationEnabled = (bool) config('nayara.tenant_isolation_enabled', false);

        if ($isIsolationEnabled) {
            if ($outletId === null) {
                return [
                    'total_po'           => '0.00',
                    'po_paid'            => '0.00',
                    'po_outstanding'     => '0.00',
                    'is_sufficient'      => false,
                    'sufficiency_status' => 'BELUM_DAPAT_DIHITUNG',
                    'projected_cash'     => null,
                    'shortage'           => null,
                    'unpaid_purchases'   => collect(),
                ];
            }

            $openingRecord = OpeningCashBalance::forOutlet($outletId)
                ->latest('opening_date')
                ->latest('id')
                ->first();
            $hasOpening = $openingRecord !== null;

            // Missing opening fails closed: cash cannot be determined
            if (! $hasOpening) {
                $kasTersedia = null;
            } elseif ($kasTersedia === null) {
                $kasTersedia = $this->calculateCurrentCash($openingRecord, $outletId);
            }

            $purchases = Purchase::with(['items', 'payments', 'supplier', 'approvedByUser'])
                ->where('purchases.outlet_id', $outletId)
                ->where('status', '!=', PurchaseStatus::CANCELLED)
                ->where('approval_status', PurchaseApprovalStatus::APPROVED)
                ->latest('purchase_date')
                ->get();
        } else {
            $openingRecord = OpeningCashBalance::latest('opening_date')->latest('id')->first();
            $hasOpening = $openingRecord !== null;
            if ($kasTersedia === null && $hasOpening) {
                $kasTersedia = $this->calculateCurrentCash($openingRecord, $outletId);
            }

            $purchases = Purchase::with(['items', 'payments', 'supplier', 'approvedByUser'])
                ->when($outletId !== null, fn ($q) => $q->where('purchases.outlet_id', $outletId))
                ->where('status', '!=', PurchaseStatus::CANCELLED)
                ->where('approval_status', PurchaseApprovalStatus::APPROVED)
                ->latest('purchase_date')
                ->get();
        }

        $totalPo = '0.00';
        $poPaid = '0.00';
        $unpaidPurchases = collect();

        foreach ($purchases as $purchase) {
            $poTotal = $purchase->total_amount_string;
            $poPPaid = $purchase->paid_amount;
            $poOut = $purchase->outstanding_amount;

            $totalPo = bcadd($totalPo, $poTotal, 2);
            $poPaid = bcadd($poPaid, $poPPaid, 2);

            if (bccomp($poOut, '0.00', 2) > 0) {
                $unpaidPoSufficient = $hasOpening && ($kasTersedia !== null) && bccomp($kasTersedia, $poOut, 2) >= 0;
                $unpaidPurchases->push([
                    'purchase'              => $purchase,
                    'purchase_id'           => $purchase->id,
                    'purchase_no'           => $purchase->purchase_no,
                    'supplier_name'         => $purchase->supplier?->supplier_name ?? '-',
                    'purchase_date'         => $purchase->purchase_date ? $purchase->purchase_date->format('d/m/Y') : '-',
                    'total_amount'          => $poTotal,
                    'paid_amount'           => $poPPaid,
                    'outstanding_amount'    => $poOut,
                    'is_sufficient'         => $unpaidPoSufficient,
                    'sufficiency_status'    => $hasOpening ? ($unpaidPoSufficient ? 'CUKUP' : 'TIDAK_CUKUP') : 'BELUM_DAPAT_DIHITUNG',
                    'approval_status'       => $purchase->approval_status?->value ?? 'APPROVED',
                    'approval_status_label' => $purchase->approval_status === PurchaseApprovalStatus::APPROVED ? 'Disetujui Owner' : ($purchase->approval_status?->label() ?? 'Disetujui'),
                    'current_cash'          => $kasTersedia ?? '0.00',
                    'approved_by_name'      => $purchase->approvedByUser?->name ?? 'Owner',
                    'approved_at'           => $purchase->approved_at ? $purchase->approved_at->format('d/m/Y H:i') : '-',
                    'max_payment'           => $hasOpening && ($kasTersedia !== null)
                        ? (bccomp($kasTersedia, $poOut, 2) >= 0 ? $poOut : (bccomp($kasTersedia, '0.00', 2) > 0 ? $kasTersedia : '0.00'))
                        : null,
                ]);
            }
        }

        $poOutstanding = bcsub($totalPo, $poPaid, 2);
        if (bccomp($poOutstanding, '0.00', 2) < 0) {
            $poOutstanding = '0.00';
        }

        if ($hasOpening && $kasTersedia !== null) {
            $isSufficient = bccomp($kasTersedia, $poOutstanding, 2) >= 0;
            $sufficiencyStatus = $isSufficient ? 'CUKUP' : 'TIDAK_CUKUP';
            $projectedCash = bcsub($kasTersedia, $poOutstanding, 2);
            $shortage = $isSufficient ? '0.00' : bcsub($poOutstanding, $kasTersedia, 2);
        } else {
            $isSufficient = false;
            $sufficiencyStatus = 'BELUM_DAPAT_DIHITUNG';
            $projectedCash = null;
            $shortage = null;
        }

        return [
            'total_po'           => $totalPo,
            'po_paid'            => $poPaid,
            'po_outstanding'     => $poOutstanding,
            'is_sufficient'      => $isSufficient,
            'sufficiency_status' => $sufficiencyStatus,
            'projected_cash'     => $projectedCash,
            'shortage'           => $shortage,
            'unpaid_purchases'   => $unpaidPurchases,
        ];
    }

    /**
     * Calculate Owner procurement commitment & funds available to commit (Phase 8 & 17).
     *
     * Formula:
     * available_to_commit = current_cash - other approved/unpaid purchase obligations
     * (excluding the current PO being reviewed to avoid double-counting).
     *
     * @param int|null $excludePurchaseId
     * @return array{
     *     current_cash: string,
     *     committed_unpaid: string,
     *     available_to_commit: string,
     *     has_opening: bool
     * }
     */
    public function calculateAvailableToCommit(?int $excludePurchaseId = null, ?int $outletId = null): array
    {
        $isIsolationEnabled = (bool) config('nayara.tenant_isolation_enabled', false);

        if ($isIsolationEnabled) {
            // Fail-closed: Tenant isolation requires valid outlet ID
            if ($outletId === null) {
                return [
                    'current_cash'        => '0.00',
                    'committed_unpaid'    => '0.00',
                    'available_to_commit' => '0.00',
                    'has_opening'         => false,
                ];
            }

            $openingRecord = OpeningCashBalance::forOutlet($outletId)
                ->latest('opening_date')
                ->latest('id')
                ->first();
            $hasOpening = $openingRecord !== null;

            if (! $hasOpening) {
                return [
                    'current_cash'        => '0.00',
                    'committed_unpaid'    => '0.00',
                    'available_to_commit' => '0.00',
                    'has_opening'         => false,
                ];
            }

            $currentCash = $this->calculateCurrentCash($openingRecord, $outletId) ?? '0.00';

            $query = Purchase::with(['items', 'payments'])
                ->where('purchases.outlet_id', $outletId)
                ->where('status', '!=', PurchaseStatus::CANCELLED)
                ->where('approval_status', PurchaseApprovalStatus::APPROVED);

            if ($excludePurchaseId !== null) {
                $query->where('id', '!=', $excludePurchaseId);
            }

            $purchases = $query->get();

            $committedUnpaid = '0.00';
            foreach ($purchases as $p) {
                $outstanding = $p->outstanding_amount;
                if (bccomp($outstanding, '0.00', 2) > 0) {
                    $committedUnpaid = bcadd($committedUnpaid, $outstanding, 2);
                }
            }

            $availableToCommit = bcsub($currentCash, $committedUnpaid, 2);

            return [
                'current_cash'        => $currentCash,
                'committed_unpaid'    => $committedUnpaid,
                'available_to_commit' => $availableToCommit,
                'has_opening'         => true,
            ];
        }

        // --- LEGACY BRANCH (nayara.tenant_isolation_enabled = false) ---
        $openingRecord = OpeningCashBalance::latest('opening_date')->latest('id')->first();
        $hasOpening = $openingRecord !== null;
        $currentCash = $hasOpening ? ($this->calculateCurrentCash($openingRecord, $outletId) ?? '0.00') : '0.00';

        $query = Purchase::with(['items', 'payments'])
            ->when($outletId !== null, fn ($q) => $q->where('purchases.outlet_id', $outletId))
            ->where('status', '!=', PurchaseStatus::CANCELLED)
            ->where('approval_status', PurchaseApprovalStatus::APPROVED);

        if ($excludePurchaseId !== null) {
            $query->where('id', '!=', $excludePurchaseId);
        }

        $purchases = $query->get();

        $committedUnpaid = '0.00';
        foreach ($purchases as $p) {
            $outstanding = $p->outstanding_amount;
            if (bccomp($outstanding, '0.00', 2) > 0) {
                $committedUnpaid = bcadd($committedUnpaid, $outstanding, 2);
            }
        }

        $availableToCommit = bcsub($currentCash, $committedUnpaid, 2);

        return [
            'current_cash'        => $currentCash,
            'committed_unpaid'    => $committedUnpaid,
            'available_to_commit' => $availableToCommit,
            'has_opening'         => $hasOpening,
        ];
    }
}
