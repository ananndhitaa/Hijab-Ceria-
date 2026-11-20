<?php

namespace App\Http\Controllers\Owner;

use App\Enums\BatchStatus;
use App\Http\Controllers\Controller;
use App\Models\Production\ProductionBatch;
use App\Models\Sales\Order;
use App\Services\Finance\BatchCostCalculationService;
use App\Services\Finance\OrderHppCalculationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HppController extends Controller
{
    public function __construct(
        private BatchCostCalculationService $batchCostService,
        private OrderHppCalculationService $orderHppService
    ) {}

    /**
     * Display list of Batches and Orders for HPP monitoring.
     */
    public function index(Request $request): View
    {
        $tab = $request->input('tab', 'batches');
        $search = $request->input('search');
        $statusFilter = $request->input('status');

        $batchesQuery = ProductionBatch::with([
            'orderItem.order.customer',
            'orderItem.variant.product',
            'qcResult',
            'productionResults',
            'latestResult',
            'batchMaterials.material',
            'batchCosts',
        ])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('batch_no', 'like', "%{$search}%")
                        ->orWhereHas('orderItem.order', function ($oq) use ($search) {
                            $oq->where('order_no', 'like', "%{$search}%")
                                ->orWhereHas('customer', function ($cq) use ($search) {
                                    $cq->where('customer_name', 'like', "%{$search}%");
                                });
                        })
                        ->orWhereHas('orderItem.variant', function ($vq) use ($search) {
                            $vq->where('sku', 'like', "%{$search}%")
                                ->orWhere('variant_name', 'like', "%{$search}%")
                                ->orWhereHas('product', function ($pq) use ($search) {
                                    $pq->where('product_name', 'like', "%{$search}%");
                                });
                        });
                });
            });

        if ($statusFilter === 'READY') {
            $readyBatchIds = $this->batchCostService->getReadyBatchIds();
            $batchesQuery->whereIn('id', $readyBatchIds);
        } elseif ($statusFilter === 'NOT_READY') {
            $readyBatchIds = $this->batchCostService->getReadyBatchIds();
            $batchesQuery->where('status', '!=', BatchStatus::CANCELLED)
                ->whereNotIn('id', $readyBatchIds);
        } elseif ($statusFilter === 'CANCELLED') {
            $batchesQuery->where('status', BatchStatus::CANCELLED);
        }

        $batches = $batchesQuery
            ->latest('id')
            ->paginate(15, ['*'], 'batches_page')
            ->withQueryString();

        $orders = Order::with([
            'customer',
            'items.variant.product',
            'items.productionBatches.qcResult',
            'items.productionBatches.batchMaterials',
            'items.productionBatches.batchCosts',
        ])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('order_no', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($cq) use ($search) {
                            $cq->where('customer_name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('id')
            ->paginate(15, ['*'], 'orders_page')
            ->withQueryString();

        // Calculate HPP results for batches on current page
        $batchHppResults = [];
        foreach ($batches as $b) {
            $batchHppResults[$b->id] = $this->batchCostService->calculateBatchHpp($b);
        }

        // Calculate HPP results for orders on current page
        $orderHppResults = [];
        foreach ($orders as $o) {
            $orderHppResults[$o->id] = $this->orderHppService->calculateOrderHpp($o);
        }

        return view('owner.hpp.index', compact(
            'tab',
            'search',
            'statusFilter',
            'batches',
            'orders',
            'batchHppResults',
            'orderHppResults'
        ));
    }

    /**
     * Display detailed HPP breakdown for a single Batch.
     */
    public function showBatch(ProductionBatch $batch): View
    {
        $batch->load([
            'orderItem.order.customer',
            'orderItem.variant.product',
            'productionResults.qcResult',
            'batchMaterials.material.unit',
            'materialUsageRecordedByUser',
        ]);

        $batchHpp = $this->batchCostService->calculateBatchHpp($batch);
        $materialBreakdown = $this->batchCostService->calculateMaterialCost($batch);

        return view('owner.hpp.batch-show', compact(
            'batch',
            'batchHpp',
            'materialBreakdown'
        ));
    }

    /**
     * Display aggregated HPP breakdown for a Sales Order.
     */
    public function showOrder(Order $order): View
    {
        $order->load([
            'customer',
            'items.variant.product',
            'items.productionBatches.qcResult',
            'items.productionBatches.batchMaterials.material.unit',
        ]);

        $orderHpp = $this->orderHppService->calculateOrderHpp($order);

        return view('owner.hpp.order-show', compact('order', 'orderHpp'));
    }
}
