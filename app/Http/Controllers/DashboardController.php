<?php

namespace App\Http\Controllers;

use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Production\ProductionBatch;
use App\Models\Sales\Order;
use App\Services\Finance\OwnerDashboardAnalyticsService;
use App\Services\Finance\PeriodFinancialService;
use App\Services\System\TenantContext;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private ?PeriodFinancialService $periodFinancialService = null,
        private ?OwnerDashboardAnalyticsService $analyticsService = null
    ) {}

    
public function index(Request $request): RedirectResponse
{
    $user = $request->user();

    return match ($user->role) {
        UserRole::SUPER_ADMIN => redirect()->route('platform.dashboard'),
        UserRole::ADMIN       => redirect()->route('admin.dashboard'),
        UserRole::GUDANG      => redirect()->route('gudang.dashboard'),
        UserRole::PRODUKSI    => redirect()->route('produksi.dashboard'),
        UserRole::OWNER       => redirect()->route('owner.dashboard'),
        UserRole::CUSTOMER    => redirect()->route('customer.dashboard'),

        default => abort(403, 'Role pengguna tidak memiliki akses dashboard.'),
    };
}


    public function produksi(): View
    {
        return view('dashboard.produksi');
    }

    public function owner(Request $request): View
    {
        $outletId = $this->getActiveTenantOutletId();

        $preset = $request->input('preset', 'this_month');
        $now = Carbon::now('Asia/Jakarta');

        [$startDate, $endDate] = match ($preset) {
            'today'       => [$now->toDateString(), $now->toDateString()],
            'last_7_days' => [$now->copy()->subDays(6)->toDateString(), $now->toDateString()],
            'this_month'  => [
                $now->copy()->startOfMonth()->toDateString(),
                $now->copy()->endOfMonth()->toDateString(),
            ],
            'last_month'  => [
                $now->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                $now->copy()->subMonthNoOverflow()->endOfMonth()->toDateString(),
            ],
            'all'         => [
                '2020-01-01',
                $now->toDateString(),
            ],
            'custom'      => [
                $request->input('start_date', $now->copy()->startOfMonth()->toDateString()),
                $request->input('end_date', $now->copy()->endOfMonth()->toDateString()),
            ],
            default       => [
                $now->copy()->startOfMonth()->toDateString(),
                $now->copy()->endOfMonth()->toDateString(),
            ],
        };

        if (empty($startDate)) {
            $startDate = $now->copy()->startOfMonth()->toDateString();
        }
        if (empty($endDate)) {
            $endDate = $now->copy()->endOfMonth()->toDateString();
        }

        $service = $this->periodFinancialService ?? app(PeriodFinancialService::class);
        $financials = $service->calculatePeriodFinancials($startDate, $endDate, $outletId);

        $trendMonths = (int) $request->input('trend_months', 6);
        if (! in_array($trendMonths, [6, 12], true)) {
            $trendMonths = 6;
        }

        $entitlementService = app(\App\Services\Platform\TenantEntitlementService::class);
        $effectiveOutletId = $outletId ?? auth()->user()?->outlet_id;
        $hasAnalyticsEntitlement = $effectiveOutletId !== null
            ? $entitlementService->hasFinancialAnalyticsEntitlement((int) $effectiveOutletId)
            : false;

        if ($hasAnalyticsEntitlement) {
            $analyticsSvc = $this->analyticsService ?? app(OwnerDashboardAnalyticsService::class);
            $analytics = $analyticsSvc->getDashboardAnalytics($startDate, $endDate, $trendMonths, $outletId);
        } else {
            $analytics = [
                'has_entitlement' => false,
                'trend_scope_label' => "Tren {$trendMonths} bulan",
                'revenue_trend' => [],
                'profit_comparison' => [],
                'expense_composition' => [
                    'has_data' => false,
                    'categories' => [],
                    'total' => '0.00',
                    'total_formatted' => 'Rp 0',
                ],
                'top_products' => [],
                'top_customers' => [],
            ];
        }

        $totalOrders = $outletId !== null
            ? Order::forOutlet($outletId)->count()
            : Order::count();

        $activeBatches = $outletId !== null
            ? ProductionBatch::whereHas('orderItem.order', fn($q) => $q->where('outlet_id', $outletId))->whereNotIn('status', [
                BatchStatus::SELESAI,
                BatchStatus::CANCELLED,
            ])->count()
            : ProductionBatch::whereNotIn('status', [
                BatchStatus::SELESAI,
                BatchStatus::CANCELLED,
            ])->count();

        $shippingOrders = $outletId !== null
            ? Order::forOutlet($outletId)->where('status', OrderStatus::DIKIRIM)->count()
            : Order::where('status', OrderStatus::DIKIRIM)->count();

        $completedOrders = $outletId !== null
            ? Order::forOutlet($outletId)->where('status', OrderStatus::SELESAI)->count()
            : Order::where('status', OrderStatus::SELESAI)->count();

        $actionService = app(\App\Services\System\DashboardActionService::class);
        $ownerActions = $actionService->getOwnerActions(5);
        $kasTersedia = $financials['kas_tersedia'] ?? null;

        return view('dashboard.owner', compact(
            'financials',
            'analytics',
            'hasAnalyticsEntitlement',
            'trendMonths',
            'preset',
            'startDate',
            'endDate',
            'totalOrders',
            'activeBatches',
            'shippingOrders',
            'completedOrders',
            'kasTersedia',
            'ownerActions'
        ));
    }

    public function customer(Request $request): View
    {
        $user = $request->user();
        $customer = $user->customer;

        if (! $customer) {
            return view('dashboard.customer', [
                'customer'             => null,
                'activeOrdersCount'    => 0,
                'completedOrdersCount' => 0,
                'totalBelanja'         => '0.00',
                'recentOrders'         => collect(),
            ]);
        }

        $activeStatuses = [
            OrderStatus::KOMUNIKASI,
            OrderStatus::DEAL,
            OrderStatus::BAHAN,
            OrderStatus::PRINTING,
            OrderStatus::PRODUKSI,
            OrderStatus::QC,
            OrderStatus::PACKING,
            OrderStatus::DIKIRIM,
        ];

        $activeOrdersCount = $customer->orders()
            ->whereIn('status', $activeStatuses)
            ->count();

        $completedOrdersCount = $customer->orders()
            ->where('status', OrderStatus::SELESAI)
            ->count();

        $allOrders = $customer->orders()->with('items')->get();
        $totalBelanja = '0.00';
        foreach ($allOrders as $ord) {
            foreach ($ord->items as $item) {
                $sub = bcmul((string) $item->qty_ordered, (string) $item->unit_selling_price, 2);
                $totalBelanja = bcadd($totalBelanja, $sub, 2);
            }
        }

        $recentOrders = $customer->orders()
            ->with(['items.variant.product', 'invoice'])
            ->latest('order_date')
            ->latest('id')
            ->take(5)
            ->get();

        return view('dashboard.customer', compact(
            'customer',
            'activeOrdersCount',
            'completedOrdersCount',
            'totalBelanja',
            'recentOrders'
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
