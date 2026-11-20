<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Sales\Order;
use App\Services\Finance\OrderProfitabilityService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfitabilityController extends Controller
{
    public function __construct(
        private OrderProfitabilityService $profitabilityService
    ) {}

    /**
     * Display list of Sales Orders with profitability metrics.
     */
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $statusFilter = $request->input('status');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $ordersQuery = Order::with([
            'customer',
            'items.variant.product',
            'items.productionBatches.qcResult',
            'items.productionBatches.batchMaterials',
            'items.productionBatches.batchCosts',
            'businessExpenses.createdByUser',
        ])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('order_no', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($cq) use ($search) {
                            $cq->where('customer_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($startDate, function ($query, $startDate) {
                $query->whereDate('order_date', '>=', $startDate);
            })
            ->when($endDate, function ($query, $endDate) {
                $query->whereDate('order_date', '<=', $endDate);
            })
            ->latest('id');

        $orders = $ordersQuery->paginate(15)->withQueryString();

        // Calculate live profitability for each order on current page
        $profitabilityResults = [];
        $readyOrdersCount = 0;
        $notReadyOrdersCount = 0;
        $totalRevenue = '0.00';
        $totalGrossProfit = '0.00';
        $totalNetProfit = '0.00';

        foreach ($orders as $order) {
            $prof = $this->profitabilityService->calculateOrderProfitability($order);
            $profitabilityResults[$order->id] = $prof;
        }

        // Apply status filter in-memory for page display if requested
        $displayOrders = $orders;
        if ($statusFilter === 'READY') {
            $displayOrders = $orders->filter(fn($o) => ($profitabilityResults[$o->id]['is_ready'] ?? false) === true);
        } elseif ($statusFilter === 'NOT_READY') {
            $displayOrders = $orders->filter(fn($o) => ($profitabilityResults[$o->id]['is_ready'] ?? false) === false);
        }

        // Compute summaries across displayed orders
        foreach ($displayOrders as $order) {
            $prof = $profitabilityResults[$order->id];

            // Cancelled and corrupt orders are excluded from revenue & profit totals
            if ($prof['status'] === 'ORDER_CANCELLED' || $prof['status'] === 'DATA_INTEGRITY_ERROR') {
                continue;
            }

            // Total Revenue: SUM of all valid non-cancelled orders (independent of HPP readiness)
            if ($prof['revenue_amount'] !== null) {
                $totalRevenue = bcadd($totalRevenue, (string) $prof['revenue_amount'], 2);
            }

            if ($prof['is_ready']) {
                $readyOrdersCount++;
                $totalGrossProfit = bcadd($totalGrossProfit, (string) $prof['gross_profit'], 2);
                $totalNetProfit = bcadd($totalNetProfit, (string) $prof['net_profit'], 2);
            } else {
                $notReadyOrdersCount++;
            }
        }

        return view('owner.profitability.index', compact(
            'search',
            'statusFilter',
            'startDate',
            'endDate',
            'orders',
            'displayOrders',
            'profitabilityResults',
            'readyOrdersCount',
            'notReadyOrdersCount',
            'totalRevenue',
            'totalGrossProfit',
            'totalNetProfit'
        ));
    }

    /**
     * Display detailed profitability breakdown for a single Sales Order.
     */
    public function show(Order $order): View
    {
        $order->load([
            'customer',
            'items.variant.product',
            'items.productionBatches.qcResult',
            'items.productionBatches.batchMaterials.material.unit',
            'items.productionBatches.batchCosts.recordedByUser',
            'businessExpenses.createdByUser',
        ]);

        $profitability = $this->profitabilityService->calculateOrderProfitability($order);
        $itemBreakdown = $this->profitabilityService->calculateOrderItemBreakdown($order);
        $totalQtySold = (string) $order->items->sum('qty_ordered');

        return view('owner.profitability.order-show', compact('order', 'profitability', 'itemBreakdown', 'totalQtySold'));
    }
}
