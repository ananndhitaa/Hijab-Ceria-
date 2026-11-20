<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOrderRequest;
use App\Http\Requests\Admin\UpdateOrderRequest;
use App\Models\Master\Customer;
use App\Models\Master\ProductVariant;
use App\Models\Sales\Order;
use App\Models\System\OrderStatusHistory;
use App\Models\System\Outlet;
use App\Services\Finance\OrderProfitabilityService;
use App\Services\Finance\VariantCostCalculationService;
use App\Services\Production\BatchService;
use App\Services\Sales\InvoiceService;
use App\Services\Sales\OrderStatusService;
use App\Services\System\DocumentNumberGenerator;
use App\Services\System\TenantContext;
use App\Services\Tracking\TrackingTokenService;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $outletId = $this->getActiveTenantOutletId();

        $search = $request->input('search');
        $statusFilter = $request->input('status');

        $orders = Order::with(['customer', 'items.variant.product'])
            ->when($outletId !== null, function ($query) use ($outletId) {
                $query->forOutlet($outletId);
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('order_no', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($cq) use ($search) {
                            $cq->where('customer_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($statusFilter, function ($query, $status) {
                $query->where('status', $status);
            })
            ->latest('order_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $statuses = OrderStatus::cases();

        return view('admin.orders.index', compact('orders', 'search', 'statusFilter', 'statuses'));
    }

    public function create(VariantCostCalculationService $variantCostService): View
    {
        $outletId = $this->getActiveTenantOutletId();

        $customers = Customer::when($outletId !== null, fn ($q) => $q->where('outlet_id', $outletId))
            ->orderBy('customer_name')
            ->get();

        $variants = ProductVariant::whereHas('product', function ($q) use ($outletId) {
            $q->where('active', true);
            if ($outletId !== null) {
                $q->where('outlet_id', $outletId);
            }
        })
            ->when($outletId !== null, fn ($q) => $q->where('outlet_id', $outletId))
            ->with(['product', 'bomItems.material.stock'])
            ->orderBy('variant_name')
            ->get();

        $variantsPayload = $variants->map(function ($v) use ($variantCostService) {
            $ref = $variantCostService->getReferenceCost($v);
            $arr = $v->toArray();
            $arr['reference_cost'] = $ref['reference_cost'];
            $arr['reference_cost_source'] = $ref['source'];
            $arr['reference_cost_label'] = $ref['label'];
            $arr['is_reference_cost_available'] = $ref['is_available'];
            return $arr;
        });

        return view('admin.orders.create', compact('customers', 'variants', 'variantsPayload'));
    }

    public function store(
        StoreOrderRequest $request,
        DocumentNumberGenerator $numberGen,
        TrackingTokenService $tokenService,
        VariantCostCalculationService $variantCostService,
        InvoiceService $invoiceService,
        \App\Services\Sales\PaymentService $paymentService
    ): RedirectResponse {
        $rawToken = null;
        $outletId = $this->getActiveTenantOutletId();

        try {
            $order = DB::transaction(function () use (
                $request,
                $numberGen,
                $tokenService,
                $variantCostService,
                $invoiceService,
                $paymentService,
                $outletId,
                &$rawToken
            ) {
                if ($outletId !== null) {
                    // Level 1: Stable Tenant Cash & Order Mutex - Lock authorized tenant Outlet row first
                    /** @var Outlet $lockedOutlet */
                    $lockedOutlet = Outlet::whereKey($outletId)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if (! $lockedOutlet->isActive()) {
                        abort(403, 'Akses ditolak: Outlet tenant tidak aktif.');
                    }

                    $customer = Customer::find($request->customer_id);
                    if (! $customer || (int) $customer->outlet_id !== (int) $outletId) {
                        throw new DomainException('Customer yang dipilih tidak valid.');
                    }
                }

                $orderNo = $numberGen->generate('SO');

                $orderData = [
                    'customer_id' => $request->customer_id,
                    'order_no'    => $orderNo,
                    'order_date'  => $request->order_date,
                    'status'      => OrderStatus::KOMUNIKASI,
                    'notes'       => $request->notes,
                ];

                if ($outletId !== null) {
                    $orderData['outlet_id'] = $outletId;
                }

                $order = Order::create($orderData);

                $variantIds = collect($request->items)->pluck('variant_id')->all();
                $variants = ProductVariant::with('product')->whereIn('id', $variantIds)->get()->keyBy('id');

                if ($outletId !== null) {
                    foreach ($variantIds as $vId) {
                        $variant = $variants->get($vId);
                        if (! $variant || (int) $variant->outlet_id !== (int) $outletId || (int) $variant->product?->outlet_id !== (int) $outletId) {
                            throw new DomainException('Varian produk tidak ditemukan atau produk induk tidak aktif.');
                        }
                    }
                }

                foreach ($request->items as $item) {
                    $variant = $variants->get($item['variant_id']);
                    $unitPrice = isset($item['unit_selling_price']) && $item['unit_selling_price'] !== '' && $item['unit_selling_price'] !== null
                        ? $item['unit_selling_price']
                        : $variant->base_selling_price;

                    $ref = $variantCostService->getReferenceCost($variant);
                    if (! $ref['has_reference'] || $ref['reference_cost'] === null) {
                        throw new DomainException('HPP / Modal Acuan variant belum tersedia. Lengkapi BOM dan modal bahan terlebih dahulu sebelum produk dijual.');
                    }

                    if (bccomp((string) $unitPrice, (string) $ref['reference_cost'], 2) <= 0) {
                        throw new DomainException('Harga jual produk tidak boleh sama dengan atau lebih rendah dari HPP / Modal.');
                    }

                    $order->items()->create([
                        'variant_id'         => $item['variant_id'],
                        'qty_ordered'        => $item['qty_ordered'],
                        'unit_selling_price' => $unitPrice,
                    ]);
                }

                OrderStatusHistory::create([
                    'order_id'           => $order->id,
                    'status'             => OrderStatus::KOMUNIKASI,
                    'changed_by_user_id' => $request->user()->id,
                    'changed_at'         => now(),
                    'notes'              => 'Pesanan dibuat oleh Admin.',
                ]);

                // Atomic SO + Invoice + DP recording if customer already paid DP
                if ($request->filled('dp_amount') && bccomp((string) $request->dp_amount, '0.00', 2) > 0) {
                    $order->status = OrderStatus::DEAL;
                    $order->save();

                    OrderStatusHistory::create([
                        'order_id'           => $order->id,
                        'status'             => OrderStatus::DEAL,
                        'changed_by_user_id' => $request->user()->id,
                        'changed_at'         => now(),
                        'notes'              => 'Deal dikonfirmasi dengan pembayaran DP saat pembuatan Sales Order.',
                    ]);

                    $invoice = $invoiceService->createForOrder($order);

                    $paymentService->recordPayment($invoice, [
                        'payment_type'   => \App\Enums\PaymentType::DP,
                        'amount'         => $request->dp_amount,
                        'payment_date'   => $request->input('payment_date', $request->order_date),
                        'payment_method' => $request->input('payment_method', \App\Enums\PaymentMethod::TRANSFER_BANK->value),
                        'notes'          => $request->input('payment_notes'),
                    ]);
                }

                $rawToken = $tokenService->createForOrder($order);

                \App\Services\System\TenantActivityLogger::log(
                    outletId: (int) ($order->outlet_id ?? 0),
                    user: $request->user(),
                    module: 'SALES_ORDER',
                    actionType: 'CREATE_ORDER',
                    description: "Sales Order {$order->order_no} dibuat oleh {$request->user()->name}.",
                    referenceNo: $order->order_no,
                    subject: $order
                );

                return $order;
            });

            $trackingUrl = url('/track/' . $rawToken);

            return redirect()->route('admin.orders.show', $order)
                ->with('success', "Sales Order {$order->order_no} berhasil dibuat.")
                ->with('raw_tracking_url', $trackingUrl);
        } catch (DomainException $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        } catch (UniqueConstraintViolationException $e) {
            report($e);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Nomor dokumen mengalami konflik saat dibuat. Silakan coba kembali.');
        }
    }

    public function show(
        Order $order,
        OrderStatusService $statusService,
        InvoiceService $invoiceService,
        BatchService $batchService,
        OrderProfitabilityService $profitabilityService,
        VariantCostCalculationService $variantCostService
    ): View {
        $outletId = $this->getActiveTenantOutletId();
        $this->validateOrderTenantOwnership($order, $outletId);

        $order->load([
            'customer.user',
            'items.variant.product',
            'items.variant.bomItems.material.stock',
            'items.productionBatches.qcResult',
            'items.productionBatches.latestResult',
            'items.productionBatches.batchMaterials.material',
            'items.productionBatches.batchCosts',
            'statusHistories.user',
            'trackingTokens',
            'invoice.payments',
            'expenses.createdByUser',
        ]);

        $this->validateOrderTenantOwnership($order, $outletId);

        $canEdit = $statusService->canEdit($order);
        $canConfirmDeal = $statusService->canConfirmDeal($order);
        $canCreateInvoice = $invoiceService->canCreateForOrder($order);
        $canCreateBatch = $batchService->canCreateForOrder($order);
        $hasRemainingCapacity = $batchService->hasRemainingCapacity($order);
        $dpStatus = $invoiceService->getDownPaymentStatus($order);

        // Decimal-safe sum of attributed business expenses using BCMath
        $totalAttributedExpenses = '0.00';
        foreach ($order->expenses as $expense) {
            $totalAttributedExpenses = bcadd($totalAttributedExpenses, (string) $expense->amount, 2);
        }

        // Authoritative order profitability from existing finance service
        $profitability = $profitabilityService->calculateOrderProfitability($order);

        // Attach item-level reference cost and margin evaluation + calculate order-level estimation
        $totalEstimatedHpp = '0.00';
        $hasCompleteEstimatedHpp = true;

        foreach ($order->items as $item) {
            $item->price_margin_eval = $variantCostService->evaluatePriceMargin($item->variant, $item->unit_selling_price);

            $refCost = $item->price_margin_eval['reference_cost'] ?? null;
            if ($refCost !== null && is_numeric($refCost)) {
                $itemEstimatedHpp = bcmul((string) $item->qty_ordered, (string) $refCost, 2);
                $item->estimated_total_hpp = $itemEstimatedHpp;
                $totalEstimatedHpp = bcadd($totalEstimatedHpp, $itemEstimatedHpp, 2);
            } else {
                $item->estimated_total_hpp = null;
                $hasCompleteEstimatedHpp = false;
            }
        }

        $revenue = (string) ($profitability['revenue_amount'] ?? $order->total_amount);
        $estimatedGrossProfit = null;
        $estimatedGrossMarginPercent = null;

        if ($hasCompleteEstimatedHpp && $order->items->isNotEmpty()) {
            $estimatedGrossProfit = bcsub($revenue, $totalEstimatedHpp, 2);
            if (bccomp($revenue, '0.00', 2) > 0) {
                $estimatedGrossMarginPercent = bcdiv(bcmul($estimatedGrossProfit, '100', 4), $revenue, 2);
            }
        }

        $orderEstimation = [
            'is_available'                   => $hasCompleteEstimatedHpp && $order->items->isNotEmpty(),
            'revenue_amount'                 => $revenue,
            'estimated_reference_hpp'        => $totalEstimatedHpp,
            'estimated_gross_profit'         => $estimatedGrossProfit,
            'estimated_gross_margin_percent' => $estimatedGrossMarginPercent,
        ];

        return view('admin.orders.show', compact(
            'order',
            'canEdit',
            'canConfirmDeal',
            'canCreateInvoice',
            'canCreateBatch',
            'hasRemainingCapacity',
            'dpStatus',
            'invoiceService',
            'batchService',
            'totalAttributedExpenses',
            'profitability',
            'variantCostService',
            'orderEstimation'
        ));
    }

    public function edit(
        Order $order,
        OrderStatusService $statusService,
        VariantCostCalculationService $variantCostService
    ): View|RedirectResponse {
        $outletId = $this->getActiveTenantOutletId();
        $this->validateOrderTenantOwnership($order, $outletId);

        if (! $statusService->canEdit($order)) {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', "Pesanan dengan status {$order->status->label()} tidak dapat diedit.");
        }

        $order->load(['customer', 'items.variant.product']);
        $this->validateOrderTenantOwnership($order, $outletId);

        $customers = Customer::when($outletId !== null, fn ($q) => $q->where('outlet_id', $outletId))
            ->orderBy('customer_name')
            ->get();

        $variants = ProductVariant::whereHas('product', function ($q) use ($outletId) {
            $q->where('active', true);
            if ($outletId !== null) {
                $q->where('outlet_id', $outletId);
            }
        })
            ->when($outletId !== null, fn ($q) => $q->where('outlet_id', $outletId))
            ->with(['product', 'bomItems.material.stock'])
            ->orderBy('variant_name')
            ->get();

        $variantsPayload = $variants->map(function ($v) use ($variantCostService) {
            $ref = $variantCostService->getReferenceCost($v);
            $arr = $v->toArray();
            $arr['reference_cost'] = $ref['reference_cost'];
            $arr['reference_cost_source'] = $ref['source'];
            $arr['reference_cost_label'] = $ref['label'];
            $arr['is_reference_cost_available'] = $ref['is_available'];
            return $arr;
        });

        return view('admin.orders.edit', compact('order', 'customers', 'variants', 'variantsPayload', 'variantCostService'));
    }

    public function update(
        UpdateOrderRequest $request,
        Order $order,
        OrderStatusService $statusService,
        VariantCostCalculationService $variantCostService
    ): RedirectResponse {
        $outletId = $this->getActiveTenantOutletId();
        $this->validateOrderTenantOwnership($order, $outletId);

        if (! $statusService->canEdit($order)) {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'Pesanan tidak dapat diedit karena status sudah diproses atau telah memiliki batch produksi.');
        }

        try {
            DB::transaction(function () use ($request, $order, $variantCostService, $outletId) {
                if ($outletId !== null) {
                    $customer = Customer::find($request->customer_id);
                    if (! $customer || (int) $customer->outlet_id !== (int) $outletId) {
                        throw new DomainException('Customer yang dipilih tidak valid.');
                    }
                }

                $order->update([
                    'customer_id' => $request->customer_id,
                    'order_date'  => $request->order_date,
                    'notes'       => $request->notes,
                ]);

                $order->loadMissing('items');
                $existingItems = $order->items->keyBy('id');

                $variantIds = collect($request->items)->pluck('variant_id')->all();
                $variants = ProductVariant::with('product')->whereIn('id', $variantIds)->get()->keyBy('id');

                if ($outletId !== null) {
                    foreach ($variantIds as $vId) {
                        $variant = $variants->get($vId);
                        if (! $variant || (int) $variant->outlet_id !== (int) $outletId || (int) $variant->product?->outlet_id !== (int) $outletId) {
                            throw new DomainException('Varian produk tidak ditemukan atau produk induk tidak aktif.');
                        }
                    }
                }

                // Replace order items atomically
                $order->items()->delete();

                foreach ($request->items as $item) {
                    $orderItemId = $item['order_item_id'] ?? null;
                    $unitPrice = null;

                    if ($orderItemId !== null && $existing = $existingItems->get($orderItemId)) {
                        if ((int) $item['variant_id'] === (int) $existing->variant_id) {
                            // Preserved historical snapshot unless explicit new transaction price provided
                            $unitPrice = isset($item['unit_selling_price']) && $item['unit_selling_price'] !== '' && $item['unit_selling_price'] !== null
                                ? $item['unit_selling_price']
                                : $existing->unit_selling_price;
                        }
                    }

                    $variant = $variants->get($item['variant_id']);

                    if ($unitPrice === null) {
                        // New item or changed variant: fetch submitted price or master price
                        $unitPrice = isset($item['unit_selling_price']) && $item['unit_selling_price'] !== '' && $item['unit_selling_price'] !== null
                            ? $item['unit_selling_price']
                            : $variant->base_selling_price;
                    }

                    $ref = $variantCostService->getReferenceCost($variant);
                    if (! $ref['has_reference'] || $ref['reference_cost'] === null) {
                        throw new DomainException('HPP / Modal Acuan variant belum tersedia. Lengkapi BOM dan modal bahan terlebih dahulu sebelum produk dijual.');
                    }

                    if (bccomp((string) $unitPrice, (string) $ref['reference_cost'], 2) <= 0) {
                        throw new DomainException('Harga jual produk tidak boleh sama dengan atau lebih rendah dari HPP / Modal.');
                    }

                    $order->items()->create([
                        'variant_id'         => $item['variant_id'],
                        'qty_ordered'        => $item['qty_ordered'],
                        'unit_selling_price' => $unitPrice,
                    ]);
                }
            });

            return redirect()->route('admin.orders.show', $order)
                ->with('success', "Sales Order {$order->order_no} berhasil diperbarui.");
        } catch (DomainException $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function confirmDeal(
        Order $order,
        Request $request,
        OrderStatusService $statusService
    ): RedirectResponse|JsonResponse {
        $outletId = $this->getActiveTenantOutletId();
        $this->validateOrderTenantOwnership($order, $outletId);

        if ($outletId !== null) {
            $order->loadMissing(['customer', 'items.variant.product']);
            $this->validateOrderTenantOwnership($order, $outletId);

            if ($order->customer && (int) $order->customer->outlet_id !== (int) $outletId) {
                abort(404);
            }

            foreach ($order->items as $item) {
                if ($item->variant) {
                    if ((int) $item->variant->outlet_id !== (int) $outletId) {
                        abort(404);
                    }
                    if ($item->variant->product && (int) $item->variant->product?->outlet_id !== (int) $outletId) {
                        abort(404);
                    }
                }
            }
        }

        try {
            $updatedOrder = $statusService->confirmDeal($order, $request->user(), $request->input('notes'));

            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success'  => true,
                    'order_id' => $updatedOrder->id,
                    'order_no' => $updatedOrder->order_no,
                    'status'   => $updatedOrder->status->value,
                    'message'  => "Status pesanan {$updatedOrder->order_no} berhasil dikonfirmasi menjadi DEAL.",
                ]);
            }

            return redirect()->route('admin.orders.show', $order)
                ->with('success', "Status pesanan {$order->order_no} berhasil dikonfirmasi menjadi DEAL.");
        } catch (DomainException $e) {
            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->route('admin.orders.show', $order)
                ->with('error', $e->getMessage());
        }
    }

    public function regenerateTrackingToken(
        Order $order,
        TrackingTokenService $tokenService
    ): RedirectResponse {
        $outletId = $this->getActiveTenantOutletId();
        $this->validateOrderTenantOwnership($order, $outletId);

        $rawToken = $tokenService->regenerateForOrder($order);
        $trackingUrl = url('/track/' . $rawToken);

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Tautan tracking baru berhasil dibuat.')
            ->with('raw_tracking_url', $trackingUrl);
    }

    /**
     * Resolve the active tenant outlet ID when tenant isolation is enabled.
     * Returns null when tenant isolation is disabled (legacy single-tenant mode).
     * Fails closed (aborts 403) when enabled but user/tenant is invalid.
     */
    protected function getActiveTenantOutletId(): ?int
    {
        if (! config('nayara.tenant_isolation_enabled', false)) {
            return null;
        }

        try {
            return app(TenantContext::class)->currentOutletId();
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage() ?: 'Akses ditolak: Pengguna tidak memiliki akses tenant yang sah.');
        }
    }

    /**
     * Validate order ownership against the active tenant when isolation is enabled.
     * Fails closed with 404 (anti-IDOR) if order belongs to another outlet or has null outlet.
     */
    protected function validateOrderTenantOwnership(Order $order, ?int $outletId): void
    {
        if ($outletId === null) {
            return;
        }

        if ($order->outlet_id === null || (int) $order->outlet_id !== (int) $outletId) {
            abort(404);
        }

        // Cross-outlet relation inconsistency check: fail-closed if customer or variant belongs to another outlet
        if ($order->relationLoaded('customer') && $order->customer?->outlet_id !== null && (int) $order->customer->outlet_id !== (int) $outletId) {
            abort(404);
        }

        if ($order->relationLoaded('items')) {
            foreach ($order->items as $item) {
                if ($item->relationLoaded('variant') && $item->variant?->outlet_id !== null && (int) $item->variant->outlet_id !== (int) $outletId) {
                    abort(404);
                }
            }
        }
    }
}
