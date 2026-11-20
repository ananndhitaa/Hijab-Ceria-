<?php

namespace App\Services\Finance;

use App\Enums\ExpenseType;
use App\Enums\PaymentStatus;
use App\Models\Finance\BusinessExpense;
use App\Models\Finance\OpeningCashBalance;
use App\Models\Inventory\PurchasePayment;
use App\Models\Sales\Order;
use App\Models\Sales\Payment;
use App\Services\Sales\InvoiceService;
use Carbon\Carbon;

class PeriodFinancialService
{
    private FinancialRecordService $financialRecordService;
    private InvoiceService $invoiceService;

    public function __construct(
        private OrderHppCalculationService $orderHppService,
        private OrderProfitabilityService $profitabilityService,
        ?FinancialRecordService $financialRecordService = null,
        ?InvoiceService $invoiceService = null
    ) {
        $this->financialRecordService = $financialRecordService ?? app(FinancialRecordService::class);
        $this->invoiceService = $invoiceService ?? app(InvoiceService::class);
    }

    /**
     * Calculate comprehensive period financial metrics for a specified date range.
     *
     * Authoritative Rules (Supervisor Clarification & Domain Separation):
     * 1. Total Penjualan (Revenue Period) = SUM(qty_ordered * unit_selling_price) for non-cancelled, valid orders.
     * 2. Pendapatan Diterima = SUM(Payment amount WHERE status == CONFIRMED and payment_date in period).
     * 3. Piutang / Belum Dibayar Customer = Authoritative remaining invoice/order balance for orders belonging to period.
     *    - Invoice total - CONFIRMED payments on that invoice (PENDING/CANCELLED excluded).
     *    - Cross-period safe: September sales with October payment reflects real outstanding without distortion.
     * 4. Total Pengeluaran (Actual Cash Outflow) = Supplier Payments (PurchasePayment) + Business Expenses (BusinessExpense).
     *    - HPP is NOT added to Total Pengeluaran to prevent double counting of material purchases.
     * 5. Authoritative ROI per Supervisor:
     *    ROI = (Pendapatan Diterima / Total Pengeluaran) * 100%
     *    Zero-denominator rule: If Total Pengeluaran == 0, ROI is null / "Belum dapat dihitung".
     * 6. Profitability Metrics (Accrual basis):
     *    - HPP Period = SUM(Order HPP) if all orders in period have HPP READY; else null (unready).
     *    - Gross Profit = Total Penjualan - HPP Final (allows negative).
     *    - Net Profit = Gross Profit - Business Expenses (allows negative).
     *    - Total Cost = HPP Final + Business Expenses.
     * 7. Kas Tersedia = Canonical cut-off cash starting from OpeningCashBalance (Rp100.000.000) + post-cutoff delta.
     * 8. Komitmen PO = Total PO - PO Paid (outstanding obligations to suppliers) with cash sufficiency status.
     * 9. MER = Revenue / Marketing Spend (null if marketing spend == 0).
     * 10. Zero persistence to order_financials.
     *
     * @param string $startDate 'Y-m-d' in Asia/Jakarta
     * @param string $endDate   'Y-m-d' in Asia/Jakarta
     * @param int|null $outletId
     * @return array<string, mixed>
     */
    public function calculatePeriodFinancials(string $startDate, string $endDate, ?int $outletId = null): array
    {
        $isIsolationEnabled = (bool) config('nayara.tenant_isolation_enabled', false);

        // 1. Invariant: Validate date boundaries in Asia/Jakarta
        $start = Carbon::parse($startDate, 'Asia/Jakarta')->startOfDay();
        $end = Carbon::parse($endDate, 'Asia/Jakarta')->endOfDay();

        if ($start->gt($end)) {
            return [
                'status'                 => 'INVALID_DATE_RANGE',
                'is_ready'               => false,
                'error'                  => 'Tanggal mulai tidak boleh melebihi tanggal akhir.',
                'period'                 => [
                    'start_date' => $start->toDateString(),
                    'end_date'   => $end->toDateString(),
                ],
                'ready_orders_count'     => 0,
                'unready_orders_count'   => 0,
                'cancelled_orders_count' => 0,
                'unready_orders'         => [],
                'valid_orders'           => [],
                // Sales & Receivables
                'revenue'                => '0.00',
                'total_penjualan'        => '0.00',
                'total_qty_sold'         => '0',
                'payment_received'       => '0.00',
                'pendapatan_diterima'    => '0.00',
                'piutang_customer'       => '0.00',
                'belum_dibayar_customer' => '0.00',
                // Production HPP
                'hpp'                    => null,
                'hpp_final_available'    => '0.00',
                // Profitability & Margins
                'gross_profit'           => null,
                'gross_margin'           => null,
                'net_profit'             => null,
                'net_margin'             => null,
                'total_cost'             => null,
                'ebitda'                 => null,
                'mer'                    => null,
                'marketing_spend'        => '0.00',
                // Expenditures & ROI
                'supplier_payments'      => '0.00',
                'business_expenses'      => '0.00',
                'total_pengeluaran'      => '0.00',
                'total_expenses'         => '0.00',
                'roi'                    => null,
                'roi_label'              => 'Belum dapat dihitung',
                'roi_helper'             => 'Belum ada pengeluaran pada periode ini.',
                // Cash & PO Obligations
                'kas_tersedia'           => null,
                'procurement_summary'    => null,
                'komitmen_po'            => '0.00',
                'expenses'               => [
                    'marketing'         => '0.00',
                    'operating'         => '0.00',
                    'supplier'          => '0.00',
                    'interest'          => '0.00',
                    'tax'               => '0.00',
                    'depreciation'      => '0.00',
                    'amortization'      => '0.00',
                    'total'             => '0.00',
                    'supplier_payments' => '0.00',
                    'total_pengeluaran' => '0.00',
                ],
            ];
        }

        if ($isIsolationEnabled && $outletId === null) {
            return [
                'status'                 => 'UNAUTHORIZED',
                'is_ready'               => false,
                'error'                  => 'Akses ditolak: Konteks outlet tenant tidak valid.',
                'period'                 => [
                    'start_date' => $start->toDateString(),
                    'end_date'   => $end->toDateString(),
                ],
                'ready_orders_count'        => 0,
                'unready_orders_count'      => 0,
                'total_orders'              => 0,
                'finalized_orders_count'    => 0,
                'pending_orders_count'      => 0,
                'pending_finalization_count'=> 0,
                'cancelled_orders_count'    => 0,
                'unready_orders'            => [],
                'valid_orders'              => [],
                'finalized_orders'          => [],
                'revenue'                   => '0.00',
                'total_penjualan'           => '0.00',
                'total_qty_sold'            => '0',
                'payment_received'          => '0.00',
                'pendapatan_diterima'       => '0.00',
                'piutang_customer'          => '0.00',
                'belum_dibayar_customer'    => '0.00',
                'hpp'                       => null,
                'hpp_final_available'      => '0.00',
                'finalized_revenue'         => '0.00',
                'finalized_hpp'             => '0.00',
                'finalized_gross_profit'    => null,
                'finalized_gross_margin'    => null,
                'gross_profit'              => null,
                'gross_margin'              => null,
                'net_profit'                => null,
                'net_margin'                => null,
                'total_cost'                => null,
                'mer'                       => null,
                'marketing_spend'           => '0.00',
                'ebitda'                    => null,
                'supplier_payments'         => '0.00',
                'business_expenses'         => '0.00',
                'total_pengeluaran'         => '0.00',
                'total_expenses'            => '0.00',
                'roi'                       => null,
                'roi_label'                 => 'Belum dapat dihitung',
                'roi_helper'                => 'Akses ditolak: Konteks outlet tenant tidak valid.',
                'kas_tersedia'              => null,
                'procurement_summary'       => null,
                'komitmen_po'               => '0.00',
                'expenses'                  => [
                    'marketing'         => '0.00',
                    'operating'         => '0.00',
                    'interest'          => '0.00',
                    'tax'               => '0.00',
                    'depreciation'      => '0.00',
                    'amortization'      => '0.00',
                    'total'             => '0.00',
                    'supplier_payments' => '0.00',
                    'total_pengeluaran' => '0.00',
                ],
            ];
        }

        $startDateStr = $start->toDateString();
        $endDateStr = $end->toDateString();

        // 2. Query Orders within period (by order_date inclusive)
        $ordersBase = ($isIsolationEnabled && $outletId !== null)
            ? Order::forOutlet($outletId)
            : Order::query();

        $orders = $ordersBase->with([
            'customer',
            'items.variant.product',
            'items.productionBatches.qcResult',
            'items.productionBatches.batchMaterials',
            'items.productionBatches.batchCosts',
            'invoice.payments',
        ])
            ->whereDate('order_date', '>=', $startDateStr)
            ->whereDate('order_date', '<=', $endDateStr)
            ->latest('id')
            ->get();

        $totalRevenue = '0.00';
        $totalQtySold = '0';
        $totalPiutangCustomer = '0.00';
        $readyOrdersCount = 0;
        $unreadyOrdersCount = 0;
        $cancelledOrdersCount = 0;
        $unreadyOrders = [];
        $validOrders = [];
        $finalizedOrders = [];
        $totalHppAvailable = '0.00';
        $finalizedRevenue = '0.00';
        $finalizedHpp = '0.00';

        foreach ($orders as $order) {
            $rawStatus = $order->getRawOriginal('status') ?? ($order->status instanceof \BackedEnum ? $order->status->value : (string) $order->status);
            if (strtoupper((string) $rawStatus) === 'CANCELLED') {
                $cancelledOrdersCount++;
                continue;
            }

            // Calculate Order Revenue
            $revRes = $this->profitabilityService->calculateRevenue($order);
            if ($revRes['status'] === 'DATA_INTEGRITY_ERROR') {
                continue;
            }

            $orderRevenue = $revRes['amount'];
            $totalRevenue = bcadd($totalRevenue, (string) $orderRevenue, 2);
            $totalQtySold = bcadd($totalQtySold, (string) $order->items->sum('qty_ordered'), 0);

            // Authoritative Outstanding Customer Liability (Piutang Pelanggan)
            // For sales/orders belonging to the selected period, calculate remaining outstanding as of current data
            $orderOutstanding = '0.00';
            if ($order->invoice && $order->invoice->status !== \App\Enums\InvoiceStatus::CANCELLED) {
                $orderOutstanding = $this->invoiceService->calculateOutstandingBalance($order->invoice);
            } else {
                $orderOutstanding = (string) $orderRevenue;
            }
            $totalPiutangCustomer = bcadd($totalPiutangCustomer, $orderOutstanding, 2);

            // Calculate Order HPP
            $orderHpp = $this->orderHppService->calculateOrderHpp($order);

            $orderData = [
                'order'      => $order,
                'order_no'   => $order->order_no,
                'customer'   => $order->customer?->customer_name ?? '-',
                'order_date' => $order->order_date ? $order->order_date->format('d/m/Y') : '-',
                'revenue'    => $orderRevenue,
                'hpp_status' => $orderHpp['status'],
                'is_ready'   => $orderHpp['is_ready'],
                'hpp_amount' => $orderHpp['is_ready'] ? $orderHpp['total_hpp'] : null,
                'error'      => $orderHpp['error'] ?? null,
            ];

            if ($orderHpp['is_ready']) {
                $readyOrdersCount++;
                $totalHppAvailable = bcadd($totalHppAvailable, (string) $orderHpp['total_hpp'], 2);
                $finalizedOrders[] = $orderData;
                $finalizedRevenue = bcadd($finalizedRevenue, (string) $orderRevenue, 2);
                $finalizedHpp = bcadd($finalizedHpp, (string) $orderHpp['total_hpp'], 2);
            } else {
                $unreadyOrdersCount++;
                $unreadyOrders[] = $orderData;
            }

            $validOrders[] = $orderData;
        }

        $totalOrdersCount = count($validOrders);
        $finalizedOrdersCount = $readyOrdersCount;
        $pendingOrdersCount = $unreadyOrdersCount;

        // Finalized Gross Profit and Margin (from finalized orders ONLY - strict accounting safety)
        $finalizedGrossProfit = null;
        $finalizedGrossMargin = null;

        if ($finalizedOrdersCount > 0) {
            $finalizedGrossProfit = bcsub($finalizedRevenue, $finalizedHpp, 2);
            if (bccomp($finalizedRevenue, '0.00', 2) > 0) {
                $fgmRatio = bcdiv($finalizedGrossProfit, $finalizedRevenue, 6);
                $finalizedGrossMargin = number_format((float) bcmul($fgmRatio, '100', 4), 4, '.', '');
            }
        }

        // Period Profit Status: READY only if unreadyOrdersCount === 0
        $isPeriodProfitReady = ($unreadyOrdersCount === 0);
        $periodStatus = $isPeriodProfitReady ? 'READY' : 'NOT_READY';

        // 3. Query Business Expenses within period (by expense_date inclusive)
        $expensesBase = ($isIsolationEnabled && $outletId !== null)
            ? BusinessExpense::forOutlet($outletId)
            : BusinessExpense::query();

        $expenses = $expensesBase->whereDate('expense_date', '>=', $startDateStr)
            ->whereDate('expense_date', '<=', $endDateStr)
            ->get();

        $marketingExpense = '0.00';
        $operatingExpense = '0.00';
        $interestExpense = '0.00';
        $taxExpense = '0.00';
        $depreciationExpense = '0.00';
        $amortizationExpense = '0.00';

        foreach ($expenses as $exp) {
            $amt = (string) $exp->amount;
            $type = $exp->expense_type instanceof ExpenseType ? $exp->expense_type : ExpenseType::tryFrom((string) $exp->expense_type);

            match ($type) {
                ExpenseType::MARKETING    => $marketingExpense = bcadd($marketingExpense, $amt, 2),
                ExpenseType::OPERATING    => $operatingExpense = bcadd($operatingExpense, $amt, 2),
                ExpenseType::INTEREST     => $interestExpense = bcadd($interestExpense, $amt, 2),
                ExpenseType::TAX          => $taxExpense = bcadd($taxExpense, $amt, 2),
                ExpenseType::DEPRECIATION => $depreciationExpense = bcadd($depreciationExpense, $amt, 2),
                ExpenseType::AMORTIZATION => $amortizationExpense = bcadd($amortizationExpense, $amt, 2),
                default                   => null,
            };
        }

        $businessExpensesTotal = '0.00';
        $businessExpensesTotal = bcadd($businessExpensesTotal, $marketingExpense, 2);
        $businessExpensesTotal = bcadd($businessExpensesTotal, $operatingExpense, 2);
        $businessExpensesTotal = bcadd($businessExpensesTotal, $interestExpense, 2);
        $businessExpensesTotal = bcadd($businessExpensesTotal, $taxExpense, 2);
        $businessExpensesTotal = bcadd($businessExpensesTotal, $depreciationExpense, 2);
        $businessExpensesTotal = bcadd($businessExpensesTotal, $amortizationExpense, 2);

        // 4. Query Supplier Payments (PurchasePayment within period by payment_date inclusive)
        $poPaymentsBase = PurchasePayment::query();
        if ($isIsolationEnabled && $outletId !== null) {
            $poPaymentsBase->whereHas('purchase', fn($q) => $q->where('outlet_id', $outletId));
        }
        $poPayments = $poPaymentsBase->whereDate('payment_date', '>=', $startDateStr)
            ->whereDate('payment_date', '<=', $endDateStr)
            ->get(['amount']);

        $supplierPayments = '0.00';
        foreach ($poPayments as $pop) {
            $supplierPayments = bcadd($supplierPayments, (string) $pop->amount, 2);
        }

        // Total Actual Cash Outflow / Total Pengeluaran = Supplier Payments + Business Expenses
        $totalPengeluaran = bcadd($supplierPayments, $businessExpensesTotal, 2);

        // 5. Query Cash Collection (Pendapatan Diterima: CONFIRMED customer payments within period)
        $paymentsBase = Payment::where('status', PaymentStatus::CONFIRMED);
        if ($isIsolationEnabled && $outletId !== null) {
            $paymentsBase->whereHas('invoice.order', fn($q) => $q->where('outlet_id', $outletId));
        }
        $payments = $paymentsBase
            ->whereDate('payment_date', '>=', $startDateStr)
            ->whereDate('payment_date', '<=', $endDateStr)
            ->get(['amount']);

        $paymentReceived = '0.00';
        foreach ($payments as $pay) {
            $paymentReceived = bcadd($paymentReceived, (string) $pay->amount, 2);
        }

        // Piutang / Belum Dibayar Customer: Real authoritative remaining customer liability
        $piutangCustomer = $totalPiutangCustomer;

        // 6. Calculate Authoritative Standardized ROI (Supervisor Direction)
        // Formula: ROI = (TOTAL PENDAPATAN / TOTAL PENGELUARAN) * 100%
        // Total Pendapatan ($totalRevenue): Seluruh transaksi penjualan sah (non-batal), termasuk piutang yang belum dibayar.
        // Total Pengeluaran ($totalPengeluaran): Seluruh pengeluaran bisnis yang tercatat dan diakui dalam periode terkait.
        // Zero-denominator rule: Total Pengeluaran == 0 => null / "N/A"
        $roi = null;
        $roiLabel = 'Belum dapat dihitung';
        $roiHelper = 'Belum ada pengeluaran pada periode ini.';

        if (bccomp($totalPengeluaran, '0.00', 2) > 0) {
            $roiRatio = bcdiv($totalRevenue, $totalPengeluaran, 6);
            $roi = number_format((float) bcmul($roiRatio, '100', 4), 4, '.', '');
            $roiLabel = number_format((float) $roi, 2, ',', '.') . '%';
            $roiHelper = null;
        }

        // 7. Canonical Current Cash & Procurement Commitments
        if ($isIsolationEnabled && $outletId !== null) {
            $openingRecord = OpeningCashBalance::forOutlet($outletId)->latest('opening_date')->latest('id')->first();
            $kasTersedia = $this->financialRecordService->calculateCurrentCash($openingRecord, $outletId);
            $procurementSummary = $this->financialRecordService->calculateProcurementSummary($kasTersedia, $outletId);
        } else {
            $kasTersedia = $this->financialRecordService->calculateCurrentCash();
            $procurementSummary = $this->financialRecordService->calculateProcurementSummary($kasTersedia);
        }
        $komitmenPo = $procurementSummary['po_outstanding'] ?? '0.00';

        // 8. Calculate Period Profitability Metrics (Accrual basis)
        if ($isPeriodProfitReady) {
            $hppPeriod = $totalHppAvailable;

            // Gross Profit = Revenue - HPP (allows negative)
            $grossProfit = bcsub($totalRevenue, $hppPeriod, 2);

            // Gross Margin = (Gross Profit / Revenue) * 100
            $grossMargin = null;
            if (bccomp($totalRevenue, '0.00', 2) > 0) {
                $gmRatio = bcdiv($grossProfit, $totalRevenue, 6);
                $grossMargin = number_format((float) bcmul($gmRatio, '100', 4), 4, '.', '');
            }

            // Net Profit = Gross Profit - Business Expenses (allows negative)
            $netProfit = bcsub($grossProfit, $businessExpensesTotal, 2);

            // Net Margin = (Net Profit / Revenue) * 100
            $netMargin = null;
            if (bccomp($totalRevenue, '0.00', 2) > 0) {
                $nmRatio = bcdiv($netProfit, $totalRevenue, 6);
                $netMargin = number_format((float) bcmul($nmRatio, '100', 4), 4, '.', '');
            }

            // Total Cost (Accrual production cost + expenses)
            $totalCost = bcadd($hppPeriod, $businessExpensesTotal, 2);

            // EBITDA = Revenue - HPP - Marketing - Operating
            $operatingAndMarketing = bcadd($marketingExpense, $operatingExpense, 2);
            $ebitda = bcsub($grossProfit, $operatingAndMarketing, 2);
        } else {
            $hppPeriod = null;
            $grossProfit = null;
            $grossMargin = null;
            $netProfit = null;
            $netMargin = null;
            $totalCost = null;
            $ebitda = null;
        }

        // 9. MER = Revenue / Marketing Spend
        $mer = null;
        if (bccomp($marketingExpense, '0.00', 2) > 0) {
            $merRatio = bcdiv($totalRevenue, $marketingExpense, 4);
            $mer = number_format((float) $merRatio, 4, '.', '');
        }

        return [
            'period' => [
                'start_date' => $startDateStr,
                'end_date'   => $endDateStr,
            ],
            'status'                 => $periodStatus,
            'is_ready'               => $isPeriodProfitReady,
            'error'                  => $isPeriodProfitReady ? null : 'Terdapat pesanan yang HPP produksinya belum final pada periode ini.',
            'ready_orders_count'        => $readyOrdersCount,
            'unready_orders_count'      => $unreadyOrdersCount,
            'total_orders'              => $totalOrdersCount,
            'finalized_orders_count'    => $finalizedOrdersCount,
            'pending_orders_count'      => $pendingOrdersCount,
            'pending_finalization_count'=> $pendingOrdersCount,
            'cancelled_orders_count'    => $cancelledOrdersCount,
            'unready_orders'            => $unreadyOrders,
            'valid_orders'              => $validOrders,
            'finalized_orders'          => $finalizedOrders,
            // Sales & Receivables
            'revenue'                   => $totalRevenue,
            'total_penjualan'           => $totalRevenue,
            'total_qty_sold'            => $totalQtySold,
            'payment_received'          => $paymentReceived,
            'pendapatan_diterima'       => $paymentReceived,
            'piutang_customer'          => $piutangCustomer,
            'belum_dibayar_customer'    => $piutangCustomer,
            // HPP / Modal Produksi
            'hpp'                       => $hppPeriod,
            'hpp_final_available'      => $totalHppAvailable,
            // Finalized Metrics (Accurate partial profitability from finalized orders only)
            'finalized_revenue'         => $finalizedRevenue,
            'finalized_hpp'             => $finalizedHpp,
            'finalized_gross_profit'    => $finalizedGrossProfit,
            'finalized_gross_margin'    => $finalizedGrossMargin,
            // Profits & Margins
            'gross_profit'           => $grossProfit,
            'gross_margin'           => $grossMargin,
            'net_profit'             => $netProfit,
            'net_margin'             => $netMargin,
            'total_cost'             => $totalCost,
            'mer'                    => $mer,
            'marketing_spend'        => $marketingExpense,
            'ebitda'                 => $ebitda,
            // Expenditures & ROI / Rasio Penerimaan Kas terhadap Pembayaran Supplier
            'supplier_payments'      => $supplierPayments,
            'business_expenses'      => $businessExpensesTotal,
            'total_pengeluaran'      => $totalPengeluaran,
            'total_expenses'         => $totalPengeluaran,
            'roi'                    => $roi,
            'roi_label'              => $roiLabel,
            'roi_helper'             => $roiHelper,
            'cash_to_supplier_ratio' => $roi,
            'cash_to_supplier_ratio_label' => $roiLabel,
            'cash_to_supplier_ratio_name' => 'Rasio Penerimaan Kas terhadap Pembayaran Supplier',
            // Cash & PO Obligations
            'kas_tersedia'           => $kasTersedia,
            'procurement_summary'    => $procurementSummary,
            'komitmen_po'            => $komitmenPo,
            // Expense Breakdown
            'expenses'               => [
                'marketing'         => $marketingExpense,
                'operating'         => $operatingExpense,
                'interest'          => $interestExpense,
                'tax'               => $taxExpense,
                'depreciation'      => $depreciationExpense,
                'amortization'      => $amortizationExpense,
                'total'             => $businessExpensesTotal,
                'supplier_payments' => $supplierPayments,
                'total_pengeluaran' => $totalPengeluaran,
            ],
        ];
    }
}
