<?php

namespace App\Services\Finance;

use App\Enums\ExpenseType;
use App\Enums\OrderStatus;
use App\Models\Finance\BusinessExpense;
use App\Models\Master\Customer;
use App\Models\Master\ProductVariant;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OwnerDashboardAnalyticsService
{
    private const INDONESIAN_MONTHS_SHORT = [
        1  => 'Jan',
        2  => 'Feb',
        3  => 'Mar',
        4  => 'Apr',
        5  => 'Mei',
        6  => 'Jun',
        7  => 'Jul',
        8  => 'Agu',
        9  => 'Sep',
        10 => 'Okt',
        11 => 'Nov',
        12 => 'Des',
    ];

    private const INDONESIAN_MONTHS_FULL = [
        1  => 'Januari',
        2  => 'Februari',
        3  => 'Maret',
        4  => 'April',
        5  => 'Mei',
        6  => 'Juni',
        7  => 'Juli',
        8  => 'Agustus',
        9  => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    public function __construct(
        private PeriodFinancialService $periodFinancialService,
        private OrderHppCalculationService $orderHppService,
        private OrderProfitabilityService $profitabilityService
    ) {}

    /**
     * Prepare complete read-only chart analytics for the Owner dashboard.
     *
     * Invariants & Rules:
     * - Revenue = SUM(order_items.qty_ordered * order_items.unit_selling_price) for non-cancelled orders.
     * - Cancelled orders are strictly excluded from all sales-based datasets.
     * - Monthly profit comparison distinguishes:
     *   A. NO ORDERS: has_orders = false, is_final = false, revenue = '0.00', hpp = null, gross_profit = null
     *   B. ORDERS EXIST BUT HPP NOT FINAL: has_orders = true, is_final = false, revenue = actual, hpp = null, gross_profit = null
     *   C. ORDERS EXIST AND ALL FINAL: has_orders = true, is_final = true, revenue = actual, hpp = actual, gross_profit = actual
     * - Expense composition groups by the 6 exact ExpenseType enum cases.
     * - Top products ranks top 5 variants by transaction revenue within selected period.
     * - Top customers ranks top 5 customers by transaction revenue within selected period.
     * - Trailing months end in the calendar month containing $endDate.
     * - All monetary arithmetic uses BCMath.
     * - Completely side-effect free: zero database writes or cache mutations.
     *
     * @param string $startDate 'Y-m-d'
     * @param string $endDate   'Y-m-d'
     * @param int    $trailingMonths Default 6 (e.g. 6 or 12)
     * @return array<string, mixed>
     */
    public function getDashboardAnalytics(string $startDate, string $endDate, int $trailingMonths = 6, ?int $outletId = null): array
    {
        $isIsolationEnabled = (bool) config('nayara.tenant_isolation_enabled', false);

        $trailingMonths = in_array($trailingMonths, [6, 12], true) ? $trailingMonths : 6;
        $endCarbon = Carbon::parse($endDate, 'Asia/Jakarta');
        $endMonthLabel = self::INDONESIAN_MONTHS_FULL[$endCarbon->month] . ' ' . $endCarbon->year;

        if ($isIsolationEnabled && $outletId === null) {
            return [
                'trailing_months'      => $trailingMonths,
                'trend_scope_label'    => "Tren {$trailingMonths} bulan hingga {$endMonthLabel}",
                'revenue_trend'        => [],
                'profit_comparison'    => [],
                'expense_composition'  => [
                    'categories'      => [],
                    'total'           => '0.00',
                    'total_numeric'   => 0.0,
                    'total_formatted' => 'Rp 0',
                    'has_data'        => false,
                ],
                'top_products'         => [],
                'top_customers'        => [],
            ];
        }

        // Trailing calendar months timeline ending in the month of $endDate
        $monthsTimeline = $this->buildMonthsTimeline($endCarbon, $trailingMonths);

        // Fetch monthly order analytics across the trailing range
        $monthlyPerformance = $this->calculateMonthlyPerformance($monthsTimeline, $isIsolationEnabled ? $outletId : null);

        // Expense Composition within selected dashboard period ($startDate to $endDate)
        $expenseComposition = $this->calculateExpenseComposition($startDate, $endDate, $isIsolationEnabled ? $outletId : null);

        // Top 5 Products by Revenue within selected dashboard period
        $topProducts = $this->calculateTopProducts($startDate, $endDate, $isIsolationEnabled ? $outletId : null);

        // Top 5 Customers by Revenue within selected dashboard period
        $topCustomers = $this->calculateTopCustomers($startDate, $endDate, $isIsolationEnabled ? $outletId : null);

        return [
            'trailing_months'      => $trailingMonths,
            'trend_scope_label'    => "Tren {$trailingMonths} bulan hingga {$endMonthLabel}",
            'revenue_trend'        => $monthlyPerformance['revenue_trend'],
            'profit_comparison'    => $monthlyPerformance['profit_comparison'],
            'expense_composition'  => $expenseComposition,
            'top_products'         => $topProducts,
            'top_customers'        => $topCustomers,
        ];
    }

    /**
     * Build chronological months timeline.
     *
     * @return array<int, array{period: string, label: string, start_date: string, end_date: string, month: int, year: int}>
     */
    private function buildMonthsTimeline(Carbon $endCarbon, int $monthsCount): array
    {
        $timeline = [];
        $endMonthAnchor = $endCarbon->copy()->endOfMonth();

        for ($i = $monthsCount - 1; $i >= 0; $i--) {
            $month = $endMonthAnchor->copy()->subMonthsNoOverflow($i);
            $year = $month->year;
            $m = $month->month;

            $timeline[] = [
                'period'     => $month->format('Y-m'),
                'label'      => self::INDONESIAN_MONTHS_SHORT[$m] . ' ' . $year,
                'full_label' => self::INDONESIAN_MONTHS_FULL[$m] . ' ' . $year,
                'start_date' => $month->copy()->startOfMonth()->toDateString(),
                'end_date'   => $month->copy()->endOfMonth()->toDateString(),
                'month'      => $m,
                'year'       => $year,
            ];
        }

        return $timeline;
    }

    /**
     * Calculate Revenue Trend and Profit Comparison with monthly readiness.
     *
     * @param array<int, array<string, mixed>> $timeline
     * @param int|null $outletId
     * @return array{revenue_trend: array<int, array<string, mixed>>, profit_comparison: array<int, array<string, mixed>>}
     */
    private function calculateMonthlyPerformance(array $timeline, ?int $outletId = null): array
    {
        if (empty($timeline)) {
            return ['revenue_trend' => [], 'profit_comparison' => []];
        }

        $firstStartDate = $timeline[0]['start_date'];
        $lastEndDate = $timeline[count($timeline) - 1]['end_date'];

        $ordersBase = ($outletId !== null)
            ? Order::forOutlet($outletId)
            : Order::query();

        // Eager load all valid orders in the trailing period to prevent N+1
        $orders = $ordersBase->with([
            'customer',
            'items.variant.product',
            'items.productionBatches.qcResult',
            'items.productionBatches.batchMaterials.material',
            'items.productionBatches.batchCosts',
        ])
            ->whereDate('order_date', '>=', $firstStartDate)
            ->whereDate('order_date', '<=', $lastEndDate)
            ->whereRaw('UPPER(status) != ?', ['CANCELLED'])
            ->get();

        // Group orders by 'Y-m' in memory
        $ordersByMonth = [];
        foreach ($orders as $order) {
            $rawStatus = $order->getRawOriginal('status') ?? ($order->status instanceof \BackedEnum ? $order->status->value : (string) $order->status);
            if (strtoupper((string) $rawStatus) === 'CANCELLED') {
                continue;
            }

            $monthKey = $order->order_date ? $order->order_date->format('Y-m') : null;
            if ($monthKey) {
                $ordersByMonth[$monthKey][] = $order;
            }
        }

        $revenueTrend = [];
        $profitComparison = [];

        foreach ($timeline as $monthInfo) {
            $period = $monthInfo['period'];
            $label = $monthInfo['label'];
            $monthOrders = $ordersByMonth[$period] ?? [];

            if (empty($monthOrders)) {
                // A. NO ORDERS
                $revenueTrend[] = [
                    'period'            => $period,
                    'label'             => $label,
                    'revenue'           => '0.00',
                    'revenue_numeric'   => 0,
                    'revenue_formatted' => 'Rp 0',
                    'has_orders'        => false,
                ];

                $profitComparison[] = [
                    'period'                  => $period,
                    'label'                   => $label,
                    'full_label'              => $monthInfo['full_label'],
                    'revenue'                 => '0.00',
                    'revenue_numeric'         => 0,
                    'revenue_formatted'       => 'Rp 0',
                    'hpp'                     => null,
                    'hpp_numeric'             => null,
                    'hpp_formatted'           => null,
                    'gross_profit'            => null,
                    'gross_profit_numeric'    => null,
                    'gross_profit_formatted'  => null,
                    'has_orders'              => false,
                    'is_final'                => false,
                    'status_label'            => 'Belum ada transaksi',
                ];
                continue;
            }

            // Orders exist in this month
            $monthRevenue = '0.00';
            $monthHpp = '0.00';
            $allOrdersFinal = true;

            foreach ($monthOrders as $order) {
                $revRes = $this->profitabilityService->calculateRevenue($order);
                if ($revRes['status'] !== 'DATA_INTEGRITY_ERROR') {
                    $monthRevenue = bcadd($monthRevenue, (string) $revRes['amount'], 2);
                }

                $orderHpp = $this->orderHppService->calculateOrderHpp($order);
                if ($orderHpp['is_ready'] && $orderHpp['total_hpp'] !== null) {
                    $monthHpp = bcadd($monthHpp, (string) $orderHpp['total_hpp'], 2);
                } else {
                    $allOrdersFinal = false;
                }
            }

            $revenueTrend[] = [
                'period'            => $period,
                'label'             => $label,
                'revenue'           => $monthRevenue,
                'revenue_numeric'   => (float) $monthRevenue,
                'revenue_formatted' => $this->formatRupiah($monthRevenue),
                'has_orders'        => true,
            ];

            if ($allOrdersFinal) {
                // C. ORDERS EXIST AND ALL HPP READY
                $grossProfit = bcsub($monthRevenue, $monthHpp, 2);

                $profitComparison[] = [
                    'period'                 => $period,
                    'label'                  => $label,
                    'full_label'             => $monthInfo['full_label'],
                    'revenue'                => $monthRevenue,
                    'revenue_numeric'        => (float) $monthRevenue,
                    'revenue_formatted'      => $this->formatRupiah($monthRevenue),
                    'hpp'                    => $monthHpp,
                    'hpp_numeric'            => (float) $monthHpp,
                    'hpp_formatted'          => $this->formatRupiah($monthHpp),
                    'gross_profit'           => $grossProfit,
                    'gross_profit_numeric'   => (float) $grossProfit,
                    'gross_profit_formatted' => $this->formatRupiah($grossProfit),
                    'has_orders'             => true,
                    'is_final'               => true,
                    'status_label'           => 'Final',
                ];
            } else {
                // B. ORDERS EXIST BUT HPP NOT FINAL
                $profitComparison[] = [
                    'period'                 => $period,
                    'label'                  => $label,
                    'full_label'             => $monthInfo['full_label'],
                    'revenue'                => $monthRevenue,
                    'revenue_numeric'        => (float) $monthRevenue,
                    'revenue_formatted'      => $this->formatRupiah($monthRevenue),
                    'hpp'                    => null,
                    'hpp_numeric'            => null,
                    'hpp_formatted'          => null,
                    'gross_profit'           => null,
                    'gross_profit_numeric'   => null,
                    'gross_profit_formatted' => null,
                    'has_orders'             => true,
                    'is_final'               => false,
                    'status_label'           => 'HPP belum final',
                ];
            }
        }

        return [
            'revenue_trend'     => $revenueTrend,
            'profit_comparison' => $profitComparison,
        ];
    }

    /**
     * Calculate Expense Composition for selected period ($startDate to $endDate).
     *
     * @return array{
     *     categories: array<int, array<string, mixed>>,
     *     total: string,
     *     total_numeric: float,
     *     total_formatted: string,
     *     has_data: bool
     * }
     */
    private function calculateExpenseComposition(string $startDate, string $endDate, ?int $outletId = null): array
    {
        $expensesBase = ($outletId !== null)
            ? BusinessExpense::forOutlet($outletId)
            : BusinessExpense::query();

        $expenses = $expensesBase->whereDate('expense_date', '>=', $startDate)
            ->whereDate('expense_date', '<=', $endDate)
            ->get();

        $amounts = [
            ExpenseType::MARKETING->value    => '0.00',
            ExpenseType::OPERATING->value    => '0.00',
            ExpenseType::INTEREST->value     => '0.00',
            ExpenseType::TAX->value          => '0.00',
            ExpenseType::DEPRECIATION->value => '0.00',
            ExpenseType::AMORTIZATION->value => '0.00',
        ];

        foreach ($expenses as $exp) {
            $type = $exp->expense_type instanceof ExpenseType
                ? $exp->expense_type->value
                : (string) $exp->expense_type;

            if (isset($amounts[$type])) {
                $amounts[$type] = bcadd($amounts[$type], (string) $exp->amount, 2);
            }
        }

        $total = '0.00';
        foreach ($amounts as $amt) {
            $total = bcadd($total, $amt, 2);
        }

        $categoriesConfig = [
            ExpenseType::MARKETING->value    => ['label' => 'Marketing',   'color' => '#8E1B5C'],
            ExpenseType::OPERATING->value    => ['label' => 'Operasional', 'color' => '#B52F74'],
            ExpenseType::INTEREST->value     => ['label' => 'Bunga',       'color' => '#D96B9B'],
            ExpenseType::TAX->value          => ['label' => 'Pajak',       'color' => '#68113F'],
            ExpenseType::DEPRECIATION->value => ['label' => 'Depresiasi',  'color' => '#64748B'],
            ExpenseType::AMORTIZATION->value => ['label' => 'Amortisasi',  'color' => '#A3969D'],
        ];

        $categories = [];
        foreach ($categoriesConfig as $type => $cfg) {
            $amount = $amounts[$type] ?? '0.00';
            $categories[] = [
                'type'             => $type,
                'label'            => $cfg['label'],
                'color'            => $cfg['color'],
                'amount'           => $amount,
                'amount_numeric'   => (float) $amount,
                'amount_formatted' => $this->formatRupiah($amount),
            ];
        }

        $hasData = bccomp($total, '0.00', 2) > 0;

        return [
            'categories'      => $categories,
            'total'           => $total,
            'total_numeric'   => (float) $total,
            'total_formatted' => $this->formatRupiah($total),
            'has_data'        => $hasData,
        ];
    }

    /**
     * Calculate Top 5 Product Variants by Revenue within selected period.
     *
     * @return array<int, array{variant_id: int, label: string, revenue: string, revenue_numeric: float, revenue_formatted: string, qty: float, qty_formatted: string}>
     */
    private function calculateTopProducts(string $startDate, string $endDate, ?int $outletId = null): array
    {
        $query = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->whereRaw('UPPER(o.status) != ?', ['CANCELLED'])
            ->whereDate('o.order_date', '>=', $startDate)
            ->whereDate('o.order_date', '<=', $endDate);

        if ($outletId !== null) {
            $query->where('o.outlet_id', '=', $outletId);
        }

        $rows = $query->select([
                'oi.variant_id',
                DB::raw('SUM(oi.qty_ordered * oi.unit_selling_price) as total_revenue'),
                DB::raw('SUM(oi.qty_ordered) as total_qty'),
            ])
            ->groupBy('oi.variant_id')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $variantIds = $rows->pluck('variant_id')->filter()->all();
        $variants = ProductVariant::with('product')->findMany($variantIds)->keyBy('id');

        $result = [];
        foreach ($rows as $row) {
            $variant = $variants->get($row->variant_id);
            if ($variant && $variant->product) {
                $label = $variant->product->product_name . ' — ' . $variant->variant_name;
            } elseif ($variant) {
                $label = $variant->variant_name;
            } else {
                $label = 'Varian #' . $row->variant_id;
            }

            $revenueStr = number_format((float) $row->total_revenue, 2, '.', '');
            $qtyFloat = (float) $row->total_qty;

            $result[] = [
                'variant_id'        => (int) $row->variant_id,
                'label'             => $label,
                'revenue'           => $revenueStr,
                'revenue_numeric'   => (float) $revenueStr,
                'revenue_formatted' => $this->formatRupiah($revenueStr),
                'qty'               => $qtyFloat,
                'qty_formatted'     => number_format($qtyFloat, 0, ',', '.') . ' pcs',
            ];
        }

        return $result;
    }

    /**
     * Calculate Top 5 Customers by Revenue within selected period.
     *
     * @return array<int, array{customer_id: int, name: string, revenue: string, revenue_numeric: float, revenue_formatted: string, order_count: int, order_count_label: string}>
     */
    private function calculateTopCustomers(string $startDate, string $endDate, ?int $outletId = null): array
    {
        $query = DB::table('orders as o')
            ->join('order_items as oi', 'oi.order_id', '=', 'o.id')
            ->whereRaw('UPPER(o.status) != ?', ['CANCELLED'])
            ->whereDate('o.order_date', '>=', $startDate)
            ->whereDate('o.order_date', '<=', $endDate);

        if ($outletId !== null) {
            $query->where('o.outlet_id', '=', $outletId);
        }

        $rows = $query->select([
                'o.customer_id',
                DB::raw('COUNT(DISTINCT o.id) as order_count'),
                DB::raw('SUM(oi.qty_ordered * oi.unit_selling_price) as total_revenue'),
            ])
            ->groupBy('o.customer_id')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $customerIds = $rows->pluck('customer_id')->filter()->all();
        $customers = Customer::findMany($customerIds)->keyBy('id');

        $result = [];
        foreach ($rows as $row) {
            $customer = $customers->get($row->customer_id);
            $name = $customer?->customer_name ?? ('Customer #' . $row->customer_id);
            $revenueStr = number_format((float) $row->total_revenue, 2, '.', '');
            $orderCount = (int) $row->order_count;

            $result[] = [
                'customer_id'       => (int) $row->customer_id,
                'name'              => $name,
                'revenue'           => $revenueStr,
                'revenue_numeric'   => (float) $revenueStr,
                'revenue_formatted' => $this->formatRupiah($revenueStr),
                'order_count'       => $orderCount,
                'order_count_label' => $orderCount . ' pesanan',
            ];
        }

        return $result;
    }

    /**
     * Format Rupiah value with negative support.
     */
    private function formatRupiah(?string $amount): ?string
    {
        if ($amount === null) {
            return null;
        }

        $val = (float) $amount;
        if (bccomp($amount, '0.00', 2) < 0) {
            return '-Rp ' . number_format(abs($val), 0, ',', '.');
        }

        return 'Rp ' . number_format($val, 0, ',', '.');
    }
}
