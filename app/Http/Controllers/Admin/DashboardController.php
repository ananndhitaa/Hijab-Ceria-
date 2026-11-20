<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Finance\BusinessExpense;
use App\Models\Master\Customer;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Production\ProductionBatch;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Services\Sales\InvoiceService;
use App\Services\System\DashboardActionService;
use App\Services\System\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(InvoiceService $invoiceService, ?DashboardActionService $actionService = null): View
    {
        $outletId = $this->getActiveTenantOutletId();

        $actionService = $actionService ?? app(DashboardActionService::class);
        $adminActions = $actionService->getAdminActions();
        $totalCustomers = Customer::count();
        $totalProducts  = Product::count();
        $totalVariants  = ProductVariant::count();
        $totalMaterials = Material::count();

        $ordersBase = $outletId !== null ? Order::forOutlet($outletId) : Order::query();

        $totalOrders           = (clone $ordersBase)->count();
        $activeOrders          = (clone $ordersBase)->where('status', '!=', OrderStatus::SELESAI)->count();
        $komunikasiOrdersCount = (clone $ordersBase)->where('status', OrderStatus::KOMUNIKASI)->count();
        $dealAwaitingBatchCount = (clone $ordersBase)->where('status', OrderStatus::DEAL)
            ->whereDoesntHave('items.productionBatches')
            ->count();

        $invoicesBase = $outletId !== null
            ? Invoice::whereHas('order', fn($q) => $q->where('outlet_id', $outletId))
            : Invoice::query();

        $unsettledInvoiceCount = (clone $invoicesBase)->whereIn('status', [InvoiceStatus::UNPAID, InvoiceStatus::PARTIAL])->count();

        $activeInvoicesList = (clone $invoicesBase)->whereIn('status', [InvoiceStatus::UNPAID, InvoiceStatus::PARTIAL])
            ->with('payments')
            ->get();
        $totalOutstanding = '0.00';
        foreach ($activeInvoicesList as $inv) {
            $totalOutstanding = bcadd($totalOutstanding, $invoiceService->calculateOutstandingBalance($inv), 2);
        }

        // 9-Stage OrderStatus Pipeline summary
        $pipelineCountsRaw = (clone $ordersBase)->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $statusPipelineCounts = [];
        foreach (OrderStatus::kanbanColumns() as $colStatus) {
            $statusPipelineCounts[$colStatus->value] = (int) ($pipelineCountsRaw[$colStatus->value] ?? 0);
        }

        // Decimal-safe current month business expenses
        $expensesBase = $outletId !== null
            ? BusinessExpense::forOutlet($outletId)
            : BusinessExpense::query();

        $monthlyExpensesAmounts = (clone $expensesBase)->whereMonth('expense_date', now('Asia/Jakarta')->month)
            ->whereYear('expense_date', now('Asia/Jakarta')->year)
            ->pluck('amount');

        $monthlyExpenses = '0.00';
        foreach ($monthlyExpensesAmounts as $amt) {
            $monthlyExpenses = bcadd($monthlyExpenses, (string) $amt, 2);
        }

        $recentOrders   = (clone $ordersBase)->with(['customer', 'items'])->latest('order_date')->latest('id')->take(5)->get();

        $recentBatchesQuery = ProductionBatch::with(['orderItem.order.customer', 'orderItem.variant.product']);
        if ($outletId !== null) {
            $recentBatchesQuery->whereHas('orderItem.order', fn($q) => $q->where('outlet_id', $outletId));
        }
        $recentBatches  = $recentBatchesQuery->latest('id')->take(5)->get();

        $recentExpenses = (clone $expensesBase)->with(['order.customer'])
            ->latest('expense_date')
            ->latest('id')
            ->take(5)
            ->get();

        return view('dashboard.admin', compact(
            'totalCustomers',
            'totalProducts',
            'totalVariants',
            'totalMaterials',
            'totalOrders',
            'activeOrders',
            'komunikasiOrdersCount',
            'dealAwaitingBatchCount',
            'unsettledInvoiceCount',
            'totalOutstanding',
            'statusPipelineCounts',
            'monthlyExpenses',
            'recentOrders',
            'recentBatches',
            'recentExpenses',
            'adminActions'
        ));
    }

    /**
     * Resolve active tenant outlet ID when tenant isolation is enabled.
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
}
