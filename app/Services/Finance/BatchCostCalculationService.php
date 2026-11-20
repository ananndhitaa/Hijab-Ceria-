<?php

namespace App\Services\Finance;

use App\Enums\BatchStatus;
use App\Enums\CostType;
use App\Enums\MovementType;
use App\Models\Inventory\StockMovement;
use App\Models\Production\BatchMaterial;
use App\Models\Production\ProductionBatch;

class BatchCostCalculationService
{
    /**
     * Calculate authoritative material cost for a batch based on actual used_qty
     * and the allocation-time StockMovement OUT snapshot.
     *
     * Invariants & Rules:
     * - Returns explicit status: 'READY', 'USAGE_UNRECORDED', or 'INTEGRITY_ERROR'.
     * - If material_usage_recorded_at is NULL, material cost is NOT READY (returns null amount, not Rp 0).
     * - Every allocated BatchMaterial MUST have exactly 1 StockMovement OUT snapshot.
     * - zero unit cost (unit_cost_snapshot = 0) is legitimately valid (Rp 0).
     * - Negative unit cost is corrupt/invalid (integrity error).
     * - Uses used_qty, NOT allocated_qty.
     * - Uses exact BCMath precision.
     *
     * @param ProductionBatch $batch
     * @return array{
     *     status: string,
     *     amount: string|null,
     *     error: string|null,
     *     items: array<int, array{
     *         batch_material_id: int,
     *         material_id: int,
     *         material_name: string,
     *         allocated_qty: string,
     *         used_qty: string,
     *         unit_cost_snapshot: string,
     *         subtotal_cost: string,
     *         stock_movement_id: int|null
     *     }>
     * }
     */
    public function calculateMaterialCost(ProductionBatch $batch): array
    {
        $batch->loadMissing(['batchMaterials.material']);
        $batchMaterials = $batch->batchMaterials;

        if ($batchMaterials->isEmpty()) {
            return [
                'status' => 'READY',
                'amount' => '0.00',
                'error'  => null,
                'items'  => [],
            ];
        }

        // Invariant: If usage not recorded yet, cost is NOT READY
        if ($batch->material_usage_recorded_at === null) {
            return [
                'status' => 'USAGE_UNRECORDED',
                'amount' => null,
                'error'  => 'Pemakaian bahan aktual belum dicatat untuk batch ini.',
                'items'  => [],
            ];
        }

        $bmIds = $batchMaterials->pluck('id')->all();
        $matIds = $batchMaterials->pluck('material_id')->unique()->all();

        // 1. Initial allocation StockMovement OUT (reference_type: batch_materials)
        $initialMovements = StockMovement::where('reference_type', 'batch_materials')
            ->whereIn('reference_id', $bmIds)
            ->where('movement_type', MovementType::OUT)
            ->get()
            ->groupBy('reference_id');

        // 2. Additional allocation StockMovement OUT (reference_type: production_batches)
        $additionalMovements = StockMovement::where('reference_type', 'production_batches')
            ->where('reference_id', $batch->id)
            ->whereIn('material_id', $matIds)
            ->where('movement_type', MovementType::OUT)
            ->orderBy('id')
            ->get()
            ->groupBy('material_id');

        $totalCost = '0.0000';
        $items = [];

        foreach ($batchMaterials as $bm) {
            $bmInitMovements = $initialMovements->get($bm->id, collect());

            // Invariant: Exactly one authoritative initial OUT movement per BatchMaterial
            if ($bmInitMovements->count() !== 1) {
                return [
                    'status' => 'INTEGRITY_ERROR',
                    'amount' => null,
                    'error'  => "Integritas snapshot alokasi tidak valid: BatchMaterial ID {$bm->id} memiliki {$bmInitMovements->count()} mutasi OUT (harus tepat 1).",
                    'items'  => [],
                ];
            }

            $initMovement = $bmInitMovements->first();
            $initSnapshotCost = (string) $initMovement->unit_cost_snapshot;
            $usedQty = (string) $bm->used_qty;

            // Invariant: Negative unit cost is invalid
            if (bccomp($initSnapshotCost, '0.0000', 4) < 0) {
                return [
                    'status' => 'INTEGRITY_ERROR',
                    'amount' => null,
                    'error'  => "Integritas harga modal tidak valid: StockMovement ID {$initMovement->id} memiliki harga snapshot negatif ({$initSnapshotCost}).",
                    'items'  => [],
                ];
            }

            // Build chronological issue layers: Layer 1 (initial), Layer 2..k (additional)
            $initQty = ltrim((string) $initMovement->qty, '-');
            $layers = [
                [
                    'qty'         => $initQty,
                    'unit_cost'   => $initSnapshotCost,
                    'movement_id' => $initMovement->id,
                ],
            ];

            $bmAdditionalMovements = $additionalMovements->get($bm->material_id, collect());
            foreach ($bmAdditionalMovements as $addMv) {
                $addSnapshotCost = (string) $addMv->unit_cost_snapshot;
                if (bccomp($addSnapshotCost, '0.0000', 4) < 0) {
                    return [
                        'status' => 'INTEGRITY_ERROR',
                        'amount' => null,
                        'error'  => "Integritas harga modal tidak valid: StockMovement ID {$addMv->id} memiliki harga snapshot negatif ({$addSnapshotCost}).",
                        'items'  => [],
                    ];
                }

                $layers[] = [
                    'qty'         => ltrim((string) $addMv->qty, '-'),
                    'unit_cost'   => $addSnapshotCost,
                    'movement_id' => $addMv->id,
                ];
            }

            // Calculate total issued quantity across all layers
            $totalIssuedQty = '0.0000';
            foreach ($layers as $layer) {
                $totalIssuedQty = bcadd($totalIssuedQty, $layer['qty'], 4);
            }

            // Invariant: used_qty must not exceed total issued quantity
            if (bccomp($usedQty, '0.0000', 4) < 0 || bccomp($usedQty, $totalIssuedQty, 4) > 0) {
                return [
                    'status' => 'INTEGRITY_ERROR',
                    'amount' => null,
                    'error'  => "Integritas kuantitas pemakaian tidak valid: Pemakaian bahan ({$usedQty}) melebihi kuantitas alokasi ({$totalIssuedQty}) pada BatchMaterial ID {$bm->id}.",
                    'items'  => [],
                ];
            }

            // Sequential consumption: consume actual used_qty across issue layers (FIFO issue lots)
            // Any unused/returned material remains unconsumed and is not counted as cost.
            $remainingUsed = $usedQty;
            $bmSubtotal = '0.0000';

            foreach ($layers as $layer) {
                if (bccomp($remainingUsed, '0.0000', 4) <= 0) {
                    break;
                }

                $layerQty = $layer['qty'];
                $layerCost = $layer['unit_cost'];

                if (bccomp($remainingUsed, $layerQty, 4) >= 0) {
                    $consumedFromLayer = $layerQty;
                    $remainingUsed = bcsub($remainingUsed, $layerQty, 4);
                } else {
                    $consumedFromLayer = $remainingUsed;
                    $remainingUsed = '0.0000';
                }

                $layerSubtotal = bcmul($consumedFromLayer, $layerCost, 4);
                $bmSubtotal = bcadd($bmSubtotal, $layerSubtotal, 4);
            }

            $totalCost = bcadd($totalCost, $bmSubtotal, 4);

            $effectiveSnapshot = bccomp($usedQty, '0.0000', 4) > 0
                ? bcdiv($bmSubtotal, $usedQty, 4)
                : $initSnapshotCost;

            $items[] = [
                'batch_material_id'  => $bm->id,
                'material_id'        => $bm->material_id,
                'material_name'      => $bm->material?->material_name ?? "Material #{$bm->material_id}",
                'allocated_qty'      => (string) $bm->allocated_qty,
                'used_qty'           => $usedQty,
                'unit_cost_snapshot' => number_format((float) $effectiveSnapshot, 4, '.', ''),
                'subtotal_cost'      => number_format((float) $bmSubtotal, 2, '.', ''),
                'stock_movement_id'  => $initMovement->id,
            ];
        }

        return [
            'status' => 'READY',
            'amount' => number_format((float) $totalCost, 2, '.', ''),
            'error'  => null,
            'items'  => $items,
        ];
    }

    /**
     * Calculate comprehensive Batch HPP automatically from operational source data
     * and immutable batch cost snapshots.
     *
     * Invariants & Rules:
     * 1. Batch.status !== CANCELLED (BATCH_CANCELLED)
     * 2. Batch.status === BatchStatus::SELESAI (Lifecycle complete)
     * 3. material_usage_recorded_at IS NOT NULL & Material cost integrity is 'READY'
     * 4. Authoritative ProductionResult exists on the completed batch
     * 5. Authoritative QcResult exists on the completed batch
     * 6. Required cost rate snapshots exist on the batch (no Admin confirmation needed)
     *
     * Calculations:
     * - Printing Cost = actual_qty * printing_cost_per_unit_snapshot (if requires_printing, else 0)
     * - Direct Labor Cost = actual_qty * direct_labor_cost_per_unit_snapshot
     * - Packaging Process Cost = good_qty * packaging_process_cost_per_good_unit_snapshot
     * - Other Direct Cost = actual_qty * other_direct_cost_per_unit_snapshot
     * - Total HPP = Material Cost + Printing + Direct Labor + Packaging Process + Other Direct
     * - HPP per Good Pcs = Total HPP / good_qty (if good_qty > 0) else null
     *
     * @param ProductionBatch $batch
     * @return array{
     *     status: string,
     *     material_cost: string|null,
     *     direct_cost: string|null,
     *     total_hpp: string|null,
     *     hpp_per_good_pcs: string|null,
     *     is_ready: bool,
     *     error: string|null,
     *     breakdown: array<string, mixed>
     * }
     */
    public function calculateBatchHpp(ProductionBatch $batch): array
    {
        // 1. Cancelled Batch check
        if ($batch->status === BatchStatus::CANCELLED) {
            return [
                'status'           => 'BATCH_CANCELLED',
                'material_cost'    => null,
                'direct_cost'      => null,
                'total_hpp'        => null,
                'hpp_per_good_pcs' => null,
                'is_ready'         => false,
                'error'            => 'Batch dibatalkan dan tidak dihitung dalam HPP.',
                'breakdown'        => [],
            ];
        }

        // 2. Lifecycle check: Batch must be fully completed
        if ($batch->status !== BatchStatus::SELESAI) {
            return [
                'status'           => 'BATCH_NOT_COMPLETED',
                'material_cost'    => null,
                'direct_cost'      => null,
                'total_hpp'        => null,
                'hpp_per_good_pcs' => null,
                'is_ready'         => false,
                'error'            => 'Proses batch belum selesai (status saat ini: ' . $batch->status->label() . ').',
                'breakdown'        => [],
            ];
        }

        // 3. Material usage & cost integrity check
        $materialRes = $this->calculateMaterialCost($batch);

        if ($materialRes['status'] === 'USAGE_UNRECORDED') {
            return [
                'status'           => 'MATERIAL_USAGE_UNRECORDED',
                'material_cost'    => null,
                'direct_cost'      => null,
                'total_hpp'        => null,
                'hpp_per_good_pcs' => null,
                'is_ready'         => false,
                'error'            => $materialRes['error'],
                'breakdown'        => [],
            ];
        }

        if ($materialRes['status'] === 'INTEGRITY_ERROR') {
            return [
                'status'           => 'MATERIAL_INTEGRITY_ERROR',
                'material_cost'    => null,
                'direct_cost'      => null,
                'total_hpp'        => null,
                'hpp_per_good_pcs' => null,
                'is_ready'         => false,
                'error'            => $materialRes['error'],
                'breakdown'        => [],
            ];
        }

        // 4. Production Result check
        $batch->loadMissing(['productionResults', 'latestResult', 'qcResult', 'orderItem.variant']);
        $prodResult = $batch->latestResult
            ?? ($batch->relationLoaded('productionResults') ? $batch->productionResults->sortByDesc('id')->first() : $batch->productionResults()->latest()->first());

        if (! $prodResult) {
            return [
                'status'           => 'PRODUCTION_RESULT_MISSING',
                'material_cost'    => $materialRes['amount'],
                'direct_cost'      => null,
                'total_hpp'        => null,
                'hpp_per_good_pcs' => null,
                'is_ready'         => false,
                'error'            => 'Integritas data produksi tidak valid: Hasil produksi aktual belum dicatat.',
                'breakdown'        => [],
            ];
        }

        // 5. QC Result check
        $qcResult = $batch->qcResult;
        if (! $qcResult) {
            return [
                'status'           => 'QC_RESULT_MISSING',
                'material_cost'    => $materialRes['amount'],
                'direct_cost'      => null,
                'total_hpp'        => null,
                'hpp_per_good_pcs' => null,
                'is_ready'         => false,
                'error'            => 'Integritas data produksi tidak valid: Batch selesai tetapi hasil QC tidak ditemukan.',
                'breakdown'        => [],
            ];
        }

        // 6. Cost snapshots presence check
        if (! $batch->hasCompleteCostSnapshots()) {
            return [
                'status'           => 'COSTING_SNAPSHOT_MISSING',
                'material_cost'    => $materialRes['amount'],
                'direct_cost'      => null,
                'total_hpp'        => null,
                'hpp_per_good_pcs' => null,
                'is_ready'         => false,
                'error'            => 'Konfigurasi biaya produksi belum tersedia untuk batch ini.',
                'breakdown'        => [],
            ];
        }

        // 7. Automatic calculation from operational data & immutable snapshots
        $actualQty = (string) $prodResult->actual_qty;
        $goodQty = (string) $qcResult->good_qty;

        $printingCost = '0.00';
        if ($batch->effective_requires_printing) {
            $printingCost = bcmul($actualQty, (string) ($batch->printing_cost_per_unit_snapshot ?? '0.00'), 2);
        }

        $laborCost = bcmul($actualQty, (string) $batch->direct_labor_cost_per_unit_snapshot, 2);
        $packagingCost = bcmul($goodQty, (string) $batch->packaging_process_cost_per_good_unit_snapshot, 2);
        $otherCost = bcmul($actualQty, (string) $batch->other_direct_cost_per_unit_snapshot, 2);

        $totalDirectCost = bcadd(
            bcadd(
                bcadd($printingCost, $laborCost, 2),
                $packagingCost,
                2
            ),
            $otherCost,
            2
        );

        $materialCost = $materialRes['amount'];
        $totalHpp = bcadd($materialCost, $totalDirectCost, 2);

        // HPP per Good Piece (Good output from QC)
        $hppPerGood = null;
        if (bccomp($goodQty, '0.0000', 4) > 0) {
            $hppPerGood = bcdiv($totalHpp, $goodQty, 4);
        }

        return [
            'status'           => 'READY',
            'material_cost'    => $materialCost,
            'direct_cost'      => $totalDirectCost,
            'total_hpp'        => $totalHpp,
            'hpp_per_good_pcs' => $hppPerGood ? number_format((float) $hppPerGood, 4, '.', '') : null,
            'is_ready'         => true,
            'error'            => null,
            'breakdown'        => [
                'material'          => $materialRes['items'],
                'material_cost'     => $materialCost,
                'printing'          => $printingCost,
                'direct_labor'      => $laborCost,
                'packaging_process' => $packagingCost,
                'other_direct'      => $otherCost,
                'total_direct_cost' => $totalDirectCost,
                'total_batch_cost'  => $totalHpp,
                'good_qty'          => $goodQty,
                'actual_qty'        => $actualQty,
                'hpp_per_good_pcs'  => $hppPerGood ? number_format((float) $hppPerGood, 4, '.', '') : null,
                'direct_costs'      => [
                    CostType::PRINTING->value     => $printingCost,
                    CostType::DIRECT_LABOR->value => $laborCost,
                    CostType::PACKAGING->value    => $packagingCost,
                    CostType::OTHER_DIRECT->value => $otherCost,
                ],
            ],
        ];
    }

    /**
     * Determine whether a given production batch HPP status is authoritative READY.
     *
     * @param ProductionBatch $batch
     * @return bool
     */
    public function isBatchHppReady(ProductionBatch $batch): bool
    {
        return ($this->calculateBatchHpp($batch)['status'] ?? null) === 'READY';
    }

    /**
     * Resolve the IDs of all batches whose authoritative HPP status is READY.
     * Uses a fast database pre-filter for potential candidates,
     * then validates each candidate through calculateBatchHpp().
     *
     * @return array<int>
     */
    public function getReadyBatchIds(): array
    {
        $candidates = ProductionBatch::query()
            ->where('status', BatchStatus::SELESAI)
            ->whereNotNull('material_usage_recorded_at')
            ->whereNotNull('direct_labor_cost_per_unit_snapshot')
            ->whereNotNull('packaging_process_cost_per_good_unit_snapshot')
            ->whereNotNull('other_direct_cost_per_unit_snapshot')
            ->where(function ($q) {
                $q->where('requires_printing', false)
                    ->orWhereNotNull('printing_cost_per_unit_snapshot')
                    ->orWhereNull('requires_printing');
            })
            ->whereHas('qcResult')
            ->whereHas('productionResults')
            ->with([
                'orderItem.variant',
                'batchMaterials.material',
                'productionResults',
                'latestResult',
                'qcResult',
            ])
            ->get();

        $readyBatchIds = [];
        foreach ($candidates as $candidate) {
            if ($this->isBatchHppReady($candidate)) {
                $readyBatchIds[] = (int) $candidate->id;
            }
        }

        return $readyBatchIds;
    }
}
