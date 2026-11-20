<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Production\ProductionBatch;
use App\Models\Sales\Order;
use App\Services\Production\BatchService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KanbanController extends Controller
{
    public function index(Request $request, BatchService $batchService): View
    {
        $search        = $request->input('search');
        $dateFrom      = $request->input('date_from');
        $dateTo        = $request->input('date_to');
        $invoiceStatus = $request->input('invoice_status');

        // Base query for orders
        $baseOrderQuery = Order::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('order_no', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($cq) use ($search) {
                            $cq->where('customer_name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('items.variant', function ($vq) use ($search) {
                            $vq->where('variant_name', 'like', "%{$search}%")
                                ->orWhere('sku', 'like', "%{$search}%")
                                ->orWhereHas('product', function ($pq) use ($search) {
                                    $pq->where('product_name', 'like', "%{$search}%");
                                });
                        })
                        ->orWhereHas('items.productionBatches', function ($bq) use ($search) {
                            $bq->where('batch_no', 'like', "%{$search}%");
                        });
                });
            })
            ->when($dateFrom, function ($query, $dateFrom) {
                $query->whereDate('order_date', '>=', $dateFrom);
            })
            ->when($dateTo, function ($query, $dateTo) {
                $query->whereDate('order_date', '<=', $dateTo);
            })
            ->when($invoiceStatus, function ($query, $invoiceStatus) {
                if ($invoiceStatus === 'NONE') {
                    $query->doesntHave('invoice');
                } else {
                    $query->whereHas('invoice', function ($iq) use ($invoiceStatus) {
                        $iq->where('status', $invoiceStatus);
                    });
                }
            });

        // Base query for batches
        $baseBatchQuery = ProductionBatch::query()
            ->where('status', '!=', BatchStatus::CANCELLED)
            ->whereHas('orderItem.order', function ($oq) use ($dateFrom, $dateTo, $invoiceStatus) {
                $oq->when($dateFrom, fn($q) => $q->whereDate('order_date', '>=', $dateFrom))
                   ->when($dateTo, fn($q) => $q->whereDate('order_date', '<=', $dateTo))
                   ->when($invoiceStatus, function ($q, $invoiceStatus) {
                       if ($invoiceStatus === 'NONE') {
                           $q->doesntHave('invoice');
                       } else {
                           $q->whereHas('invoice', fn($iq) => $iq->where('status', $invoiceStatus));
                       }
                   });
            })
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
                            $vq->where('variant_name', 'like', "%{$search}%")
                                ->orWhere('sku', 'like', "%{$search}%")
                                ->orWhereHas('product', function ($pq) use ($search) {
                                    $pq->where('product_name', 'like', "%{$search}%");
                                });
                        });
                });
            });

        // Eager load data for orders
        $withOrderRelations = [
            'customer:id,customer_name,phone',
            'invoice:id,order_id,status,total_amount',
            'items:id,order_id,variant_id,qty_ordered',
            'items.productionBatches:id,order_item_id,batch_no,target_qty,status,planned_end_date',
            'items.variant:id,product_id,variant_name,sku',
            'items.variant.product:id,product_name',
        ];

        // Eager load data for batches
        $withBatchRelations = [
            'orderItem.order.customer:id,customer_name,phone',
            'orderItem.order.invoice:id,order_id,status,total_amount',
            'orderItem.order.items.productionBatches:id,order_item_id,batch_no,target_qty,status,planned_end_date',
            'orderItem.variant:id,product_id,variant_name,sku',
            'orderItem.variant.product:id,product_name',
        ];

        // Fetch batches
        $batches = (clone $baseBatchQuery)
            ->with($withBatchRelations)
            ->orderByDesc('id')
            ->get();
        $batchesByStatus = $batches->groupBy(fn($b) => $b->status->value);

        // Fetch active orders (KOMUNIKASI through DIKIRIM)
        $activeStatuses = array_filter(
            OrderStatus::cases(),
            fn($s) => $s !== OrderStatus::SELESAI
        );

        $activeOrders = (clone $baseOrderQuery)
            ->whereIn('status', array_map(fn($s) => $s->value, $activeStatuses))
            ->with($withOrderRelations)
            ->withCount('items')
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->get()
            ->groupBy(fn($order) => $order->status->value);

        // Fetch SELESAI column
        $selesaiTotal = (clone $baseOrderQuery)
            ->where('status', OrderStatus::SELESAI->value)
            ->count();
        $isSelesaiCapped = false;

        $selesaiQuery = (clone $baseOrderQuery)
            ->where('status', OrderStatus::SELESAI->value)
            ->with($withOrderRelations)
            ->withCount('items')
            ->orderByDesc('order_date')
            ->orderByDesc('id');

        if (! $search && ! $dateFrom && ! $dateTo && ! $invoiceStatus && $selesaiTotal > 20) {
            $selesaiOrders = $selesaiQuery->take(20)->get();
            $isSelesaiCapped = true;
        } else {
            $selesaiOrders = $selesaiQuery->get();
        }

        // Build guaranteed 9 columns structure
        $columns = [];
        foreach (OrderStatus::cases() as $status) {
            $statusKey = $status->value;

            switch ($status) {
                case OrderStatus::KOMUNIKASI:
                case OrderStatus::DEAL:
                case OrderStatus::DIKIRIM:
                    $ordersForCol = $activeOrders->get($statusKey, collect());
                    $cardsForCol = $ordersForCol;
                    $totalCount = $ordersForCol->count();
                    break;

                case OrderStatus::BAHAN:
                case OrderStatus::PRINTING:
                case OrderStatus::PRODUKSI:
                case OrderStatus::QC:
                    $batchStatusVal = $statusKey;
                    $batchesForCol = $batchesByStatus->get($batchStatusVal, collect());
                    // Unbatched orders that have no production batches yet
                    $unbatchedOrders = $activeOrders->get($statusKey, collect())->filter(
                        fn($o) => $o->items->flatMap->productionBatches->isEmpty()
                    );
                    $cardsForCol = $batchesForCol->concat($unbatchedOrders);
                    $totalCount = $cardsForCol->count();
                    $ordersForCol = $activeOrders->get($statusKey, collect());
                    break;

                case OrderStatus::PACKING:
                    $ordersInPacking = $activeOrders->get(OrderStatus::PACKING->value, collect());
                    // Independent batches in PACKING whose order is not yet PACKING
                    $independentPackingBatches = $batchesByStatus->get(BatchStatus::PACKING->value, collect())->filter(
                        fn($b) => $b->orderItem?->order?->status !== OrderStatus::PACKING
                    );
                    $cardsForCol = $ordersInPacking->concat($independentPackingBatches);
                    $totalCount = $cardsForCol->count();
                    $ordersForCol = $ordersInPacking;
                    break;

                case OrderStatus::SELESAI:
                    // Independent batches in SELESAI whose order is still in earlier active stages
                    $independentSelesaiBatches = $batchesByStatus->get(BatchStatus::SELESAI->value, collect())->filter(
                        fn($b) => $b->orderItem?->order && ! in_array($b->orderItem->order->status, [OrderStatus::PACKING, OrderStatus::DIKIRIM, OrderStatus::SELESAI], true)
                    );
                    $cardsForCol = $selesaiOrders->concat($independentSelesaiBatches);
                    $totalCount = $selesaiTotal + $independentSelesaiBatches->count();
                    $ordersForCol = $selesaiOrders;
                    break;
            }

            $columns[$statusKey] = [
                'status'      => $status,
                'label'       => $status->label(),
                'color'       => $status->color(),
                'badgeClass'  => $status->badgeClass(),
                'total_count' => $totalCount,
                'orders'      => $ordersForCol,
                'cards'       => $cardsForCol,
                'is_capped'   => ($status === OrderStatus::SELESAI && $isSelesaiCapped),
            ];
        }

        return view('admin.kanban.index', compact(
            'columns',
            'search',
            'dateFrom',
            'dateTo',
            'invoiceStatus',
            'batchService'
        ));
    }
}
