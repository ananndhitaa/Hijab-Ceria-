<?php

namespace App\Services\Finance;

use App\Enums\BatchStatus;
use App\Models\Production\ProductionBatch;
use App\Models\Sales\Order;
use App\Services\Production\BatchService;

class OrderHppCalculationService
{
    public function __construct(
        private BatchCostCalculationService $batchCostService,
        private BatchService $batchService
    ) {}

    /**
     * Calculate authoritative Order HPP and verify aggregation readiness.
     *
     * Invariants & Rules:
     * - Order HPP = SUM(total_hpp of ALL relevant non-CANCELLED ProductionBatches).
     * - Includes split batches, original batches, replacement batches, and all-reject batches.
     * - Excludes CANCELLED batches.
     * - Order HPP is READY only when:
     *   1. All OrderItems have remaining_to_plan == 0 (no unfulfilled production requirement).
     *   2. Every non-cancelled batch has reached SELESAI.
     *   3. Every non-cancelled batch HPP is READY.
     * - total_good_qty = SUM(QcResult.good_qty) across all relevant non-cancelled batches.
     * - Order HPP per Good Piece = Order HPP / total_good_qty (if total_good_qty > 0) else NULL.
     * - Does NOT modify Order revenue, qty_ordered, or write to order_financials.
     * - Uses exact BCMath precision.
     *
     * @param Order $order
     * @return array{
     *     status: string,
     *     is_ready: bool,
     *     total_hpp: string|null,
     *     total_material_cost: string|null,
     *     total_direct_cost: string|null,
     *     total_good_qty: string,
     *     hpp_per_good_pcs: string|null,
     *     error: string|null,
     *     batches_count: int,
     *     batches_ready_count: int,
     *     batches: array<int, array<string, mixed>>,
     *     cancelled_batches: array<int, array<string, mixed>>
     * }
     */
    public function calculateOrderHpp(Order $order): array
    {
        $order->load([
            'items.variant.product',
            'items.productionBatches.qcResult',
            'items.productionBatches.batchMaterials.material',
            'items.productionBatches.batchCosts',
        ]);

        $hasRemainingRequirement = false;
        foreach ($order->items as $item) {
            $coverage = $this->batchService->calculateProductionCoverage($item);
            if (bccomp($coverage['remaining_to_plan'], '0.0000', 4) > 0) {
                $hasRemainingRequirement = true;
            }
        }

        $allBatches = $order->items->flatMap->productionBatches;
        $activeBatches = $allBatches->where('status', '!=', BatchStatus::CANCELLED);
        $cancelledBatches = $allBatches->where('status', BatchStatus::CANCELLED);

        $batchDetails = [];
        $cancelledDetails = [];
        $totalHpp = '0.00';
        $totalMaterialCost = '0.00';
        $totalDirectCost = '0.00';
        $totalGoodQty = '0.0000';
        $allBatchesReady = true;
        $readyCount = 0;
        $firstUnreadyError = null;
        $firstUnreadyStatus = null;

        foreach ($activeBatches as $batch) {
            $batchHpp = $this->batchCostService->calculateBatchHpp($batch);
            $qcResult = $batch->qcResult;
            $goodQty = $qcResult ? (string) $qcResult->good_qty : '0.0000';

            $totalGoodQty = bcadd($totalGoodQty, $goodQty, 4);

            $detail = [
                'batch_id'         => $batch->id,
                'batch_no'         => $batch->batch_no,
                'variant_id'       => $batch->orderItem?->variant_id,
                'variant_name'     => $batch->orderItem?->variant?->variant_name ?? '-',
                'sku'              => $batch->orderItem?->variant?->sku ?? '-',
                'status'           => $batch->status,
                'target_qty'       => (string) $batch->target_qty,
                'good_qty'         => $goodQty,
                'material_cost'    => $batchHpp['material_cost'],
                'direct_cost'      => $batchHpp['direct_cost'],
                'total_hpp'        => $batchHpp['total_hpp'],
                'hpp_per_good_pcs' => $batchHpp['hpp_per_good_pcs'],
                'hpp_status'       => $batchHpp['status'],
                'is_ready'         => $batchHpp['is_ready'],
                'error'            => $batchHpp['error'],
            ];

            $batchDetails[] = $detail;

            if ($batchHpp['is_ready']) {
                $readyCount++;
                $totalHpp = bcadd($totalHpp, (string) $batchHpp['total_hpp'], 2);
                $totalMaterialCost = bcadd($totalMaterialCost, (string) $batchHpp['material_cost'], 2);
                $totalDirectCost = bcadd($totalDirectCost, (string) $batchHpp['direct_cost'], 2);
            } else {
                $allBatchesReady = false;
                if ($firstUnreadyError === null) {
                    $firstUnreadyError = $batchHpp['error'];
                    $firstUnreadyStatus = $batchHpp['status'];
                }
            }
        }

        foreach ($cancelledBatches as $cBatch) {
            $cancelledDetails[] = [
                'batch_id'     => $cBatch->id,
                'batch_no'     => $cBatch->batch_no,
                'variant_name' => $cBatch->orderItem?->variant?->variant_name ?? '-',
                'target_qty'   => (string) $cBatch->target_qty,
                'status'       => $cBatch->status,
            ];
        }

        // Check if there are no active batches at all
        if ($activeBatches->isEmpty()) {
            return [
                'status'              => 'ORDER_NO_ACTIVE_BATCHES',
                'is_ready'            => false,
                'total_hpp'           => null,
                'total_material_cost' => null,
                'total_direct_cost'   => null,
                'total_good_qty'      => '0.0000',
                'hpp_per_good_pcs'    => null,
                'error'               => 'Pesanan ini belum memiliki batch produksi aktif.',
                'batches_count'       => 0,
                'batches_ready_count' => 0,
                'batches'             => [],
                'cancelled_batches'   => $cancelledDetails,
            ];
        }

        // Check readiness conditions
        if ($hasRemainingRequirement) {
            return [
                'status'              => 'ORDER_FULFILLMENT_REMAINING',
                'is_ready'            => false,
                'total_hpp'           => null,
                'total_material_cost' => null,
                'total_direct_cost'   => null,
                'total_good_qty'      => number_format((float) $totalGoodQty, 4, '.', ''),
                'hpp_per_good_pcs'    => null,
                'error'               => 'Masih terdapat kuantitas pesanan yang belum terpenuhi / belum dijadwalkan produksi.',
                'batches_count'       => $activeBatches->count(),
                'batches_ready_count' => $readyCount,
                'batches'             => $batchDetails,
                'cancelled_batches'   => $cancelledDetails,
            ];
        }

        if (! $allBatchesReady) {
            return [
                'status'              => 'ORDER_BATCHES_NOT_READY',
                'is_ready'            => false,
                'total_hpp'           => null,
                'total_material_cost' => null,
                'total_direct_cost'   => null,
                'total_good_qty'      => number_format((float) $totalGoodQty, 4, '.', ''),
                'hpp_per_good_pcs'    => null,
                'error'               => $firstUnreadyError ?? 'Terdapat batch produksi yang belum berstatus HPP Final.',
                'batches_count'       => $activeBatches->count(),
                'batches_ready_count' => $readyCount,
                'batches'             => $batchDetails,
                'cancelled_batches'   => $cancelledDetails,
            ];
        }

        // All conditions satisfied: Order HPP is complete and READY
        $orderHppPerGood = null;
        if (bccomp($totalGoodQty, '0.0000', 4) > 0) {
            $orderHppPerGood = bcdiv($totalHpp, $totalGoodQty, 4);
        }

        return [
            'status'              => 'READY',
            'is_ready'            => true,
            'total_hpp'           => number_format((float) $totalHpp, 2, '.', ''),
            'total_material_cost' => number_format((float) $totalMaterialCost, 2, '.', ''),
            'total_direct_cost'   => number_format((float) $totalDirectCost, 2, '.', ''),
            'total_good_qty'      => number_format((float) $totalGoodQty, 4, '.', ''),
            'hpp_per_good_pcs'    => $orderHppPerGood ? number_format((float) $orderHppPerGood, 4, '.', '') : null,
            'error'               => null,
            'batches_count'       => $activeBatches->count(),
            'batches_ready_count' => $readyCount,
            'batches'             => $batchDetails,
            'cancelled_batches'   => $cancelledDetails,
        ];
    }
}
