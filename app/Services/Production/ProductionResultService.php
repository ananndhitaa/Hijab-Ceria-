<?php

namespace App\Services\Production;

use App\Enums\BatchStatus;
use App\Enums\MovementType;
use App\Models\Inventory\MaterialStock;
use App\Models\Inventory\StockMovement;
use App\Models\Production\ProductionBatch;
use App\Models\Production\ProductionResult;
use App\Models\System\User;
use App\Services\Sales\OrderStatusService;
use DomainException;
use Illuminate\Support\Facades\DB;

class ProductionResultService
{
    public function __construct(
        private OrderStatusService $orderStatusService
    ) {}

    /**
     * Record the final actual production result along with actual material usage for a batch and advance it to QC.
     *
     * Invariants & Rules (P3 + P6A + Supervisor Revision Phase 2):
     * - Runs inside an atomic DB transaction with pessimistic row locking (lockForUpdate).
     * - Current Batch status must be strictly PRODUKSI.
     * - Material allocation integrity must remain valid (isMaterialReady).
     * - Exactly one final ProductionResult per batch (double-submit / duplicate rejected).
     * - Every BatchMaterial must have an explicit used_qty >= 0 and <= allocated_qty.
     * - No supplementary material issue: used_qty cannot exceed allocated_qty (must use Tambah Bahan dari Gudang first).
     * - If allocated_qty > used_qty, surplus (allocated_qty - used_qty) is automatically returned to Warehouse stock (StockMovement IN).
     * - No double stock OUT: initial allocation is not deducted again upon recording usage.
     * - Sets batch_materials.used_qty and markers material_usage_recorded_at / material_usage_recorded_by.
     * - actual_qty must be numeric, > 0, precision DECIMAL(18,4). (Target batch is a planning baseline, actual may be below, equal, or above target).
     * - result_date must be valid date.
     * - recorded_by is server-derived from authenticated actor.
     * - Batch transitions: PRODUKSI -> QC.
     * - Order operational status aggregated via OrderStatusService.
     * - No QcResult auto-creation (QC owned by P4).
     * - Atomic rollback: If any check or sub-operation fails, rolls back everything.
     *
     * @param ProductionBatch $batch
     * @param string|float|int $actualQty
     * @param string $resultDate
     * @param string|null $notes
     * @param User|int $actor
     * @param array<int, string|float|int> $materialUsage Keyed by batch_material_id => used_qty
     * @return ProductionResult
     * @throws DomainException
     */
    public function recordFinalResult(
        ProductionBatch $batch,
        string|float|int $actualQty,
        string $resultDate,
        ?string $notes,
        User|int $actor,
        array $materialUsage = []
    ): ProductionResult {
        return DB::transaction(function () use ($batch, $actualQty, $resultDate, $notes, $actor, $materialUsage) {
            /** @var ProductionBatch $lockedBatch */
            $lockedBatch = ProductionBatch::where('id', $batch->id)
                ->lockForUpdate()
                ->firstOrFail();

            // 1. Validate batch status is PRODUKSI
            if ($lockedBatch->status !== BatchStatus::PRODUKSI) {
                throw new DomainException("Hasil produksi hanya dapat dicatat saat batch dalam status PRODUKSI (status saat ini: {$lockedBatch->status->label()}).");
            }

            // 1b. Guard: PIC Produksi must be assigned before recording production result
            if (empty($lockedBatch->production_employee_id)) {
                throw new DomainException('PIC Produksi belum ditentukan. Tetapkan karyawan Produksi sebelum mencatat hasil produksi.');
            }

            // 2. Validate material allocation integrity
            $lockedBatch->load(['batchMaterials' => function ($q) {
                $q->lockForUpdate()->with('material');
            }]);

            if (! $lockedBatch->isMaterialReady()) {
                throw new DomainException('Integritas bahan batch tidak valid atau alokasi belum lengkap.');
            }

            // 3. Handle uninspected result or continuation
            $uninspectedResult = ProductionResult::where('batch_id', $lockedBatch->id)
                ->whereDoesntHave('qcResult')
                ->lockForUpdate()
                ->first();

            $actorUser = $actor instanceof User ? $actor : User::find($actor);
            $actorId = $actorUser?->id ?? (is_numeric($actor) ? (int) $actor : null);

            if ($uninspectedResult) {
                // Batch was resumed from QC to PRODUKSI before QC was performed.
                // Update the uninspected result with the new total actual quantity and advance to QC.
                $actualQtyStr = (string) $actualQty;
                if (! is_numeric($actualQtyStr) || bccomp($actualQtyStr, '0.0000', 4) <= 0) {
                    throw new DomainException('Kuantitas hasil produksi aktual harus berupa angka positif lebih besar dari 0.');
                }

                $uninspectedResult->actual_qty = $actualQtyStr;
                $uninspectedResult->result_date = $resultDate;
                if ($notes) {
                    $uninspectedResult->notes = $uninspectedResult->notes ? ($uninspectedResult->notes . ' | ' . $notes) : $notes;
                }
                $uninspectedResult->save();

                // Transition batch to QC
                $lockedBatch->status = BatchStatus::QC;
                $lockedBatch->save();

                if ($lockedBatch->orderItem && $lockedBatch->orderItem->order) {
                    $this->orderStatusService->syncOperationalStatus(
                        $lockedBatch->orderItem->order,
                        $actorUser,
                        "Hasil produksi Batch {$lockedBatch->batch_no} diperbarui menjadi {$actualQtyStr} pcs dan dilanjutkan ke tahap QC."
                    );
                }

                return $uninspectedResult;
            }

            $isContinuation = ProductionResult::where('batch_id', $lockedBatch->id)->lockForUpdate()->exists();

            $batchMaterials = $lockedBatch->batchMaterials;

            // 4. Validate material usage completeness & integrity & return surplus to warehouse (Step P6A + Phase 2)
            // On initial run, material usage is mandatory and surplus is returned to warehouse.
            // On continuation run, material usage was already finalized, so surplus is not returned again.
            if (! $isContinuation && $batchMaterials->isNotEmpty()) {
                if (count($materialUsage) !== $batchMaterials->count()) {
                    throw new DomainException('Data pemakaian bahan aktual tidak lengkap untuk seluruh bahan batch.');
                }

                foreach ($materialUsage as $keyId => $usageVal) {
                    if (! $batchMaterials->contains('id', (int) $keyId)) {
                        throw new DomainException('Terdapat data pemakaian bahan yang tidak valid atau bukan milik batch ini.');
                    }
                }

                // Deterministically lock MaterialStocks for these materials (sorted by material_id to prevent deadlocks)
                $materialIds = $batchMaterials->pluck('material_id')->unique()->sort()->values()->all();
                $materialStocks = MaterialStock::whereIn('material_id', $materialIds)
                    ->orderBy('material_id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('material_id');

                $returnDate = now('Asia/Jakarta')->toDateString();

                foreach ($batchMaterials as $bm) {
                    if (! array_key_exists($bm->id, $materialUsage)) {
                        $matName = $bm->material?->material_name ?? "ID #{$bm->material_id}";
                        throw new DomainException("Data pemakaian untuk material {$matName} belum diisi.");
                    }

                    $usedQtyStr = (string) $materialUsage[$bm->id];
                    $matName = $bm->material?->material_name ?? "ID #{$bm->material_id}";

                    if (! is_numeric($usedQtyStr) || bccomp($usedQtyStr, '0.0000', 4) < 0) {
                        throw new DomainException("Kuantitas pemakaian bahan {$matName} harus berupa angka non-negatif (>= 0).");
                    }

                    $allocatedStr = (string) $bm->allocated_qty;
                    if (bccomp($usedQtyStr, $allocatedStr, 4) > 0) {
                        $allocatedFormatted = rtrim(rtrim(number_format((float) $bm->allocated_qty, 4), '0'), '.');
                        $usedFormatted = rtrim(rtrim(number_format((float) $usedQtyStr, 4), '0'), '.');
                        throw new DomainException("Pemakaian bahan {$matName} ({$usedFormatted}) tidak boleh melebihi kuantitas yang dialokasikan ({$allocatedFormatted}).");
                    }

                    // Sisa bahan yang tidak terpakai otomatis dikembalikan ke stok Gudang
                    $returnQty = bcsub($allocatedStr, $usedQtyStr, 4);
                    if (bccomp($returnQty, '0.0000', 4) > 0) {
                        $stock = $materialStocks->get($bm->material_id);
                        if (! $stock) {
                            throw new DomainException("Integritas data stok tidak valid: data stok untuk material \"{$matName}\" tidak ditemukan.");
                        }

                        $qtyBefore = (string) $stock->qty_on_hand;
                        $qtyAfter = bcadd($qtyBefore, $returnQty, 4);
                        $avgCost = (string) $stock->avg_unit_cost;
                        $rawTotalCost = bcmul($returnQty, $avgCost, 8);
                        $totalCostSnapshot = number_format((float) $rawTotalCost, 2, '.', '');

                        // Stock Gudang bertambah sisa bahan (avg_unit_cost tidak diubah)
                        $stock->update([
                            'qty_on_hand' => $qtyAfter,
                        ]);

                        // Catat StockMovement IN (Pengembalian sisa bahan produksi)
                        StockMovement::create([
                            'material_id'         => $bm->material_id,
                            'movement_type'       => MovementType::IN,
                            'qty'                 => $returnQty,
                            'unit_cost_snapshot'  => $avgCost,
                            'total_cost_snapshot' => $totalCostSnapshot,
                            'qty_before'          => $qtyBefore,
                            'qty_after'           => $qtyAfter,
                            'reference_type'      => 'production_batches',
                            'reference_id'        => $lockedBatch->id,
                            'movement_date'       => $returnDate,
                            'notes'               => "Pengembalian sisa bahan {$matName} untuk Batch {$lockedBatch->batch_no}",
                            'created_by'          => $actorId,
                        ]);
                    }

                    $bm->used_qty = $usedQtyStr;
                    $bm->save();
                }
            } elseif ($isContinuation && $batchMaterials->isNotEmpty() && ! empty($materialUsage)) {
                foreach ($materialUsage as $keyId => $usageVal) {
                    if ($bm = $batchMaterials->firstWhere('id', (int) $keyId)) {
                        $usedQtyStr = (string) $usageVal;
                        if (is_numeric($usedQtyStr) && bccomp($usedQtyStr, '0.0000', 4) >= 0) {
                            $bm->used_qty = bcadd((string) ($bm->used_qty ?? '0.0000'), $usedQtyStr, 4);
                            $bm->save();
                        }
                    }
                }
            }

            // 5. Validate actual_qty
            $actualQtyStr = (string) $actualQty;
            if (! is_numeric($actualQtyStr) || bccomp($actualQtyStr, '0.0000', 4) <= 0) {
                throw new DomainException('Kuantitas hasil produksi aktual harus berupa angka positif lebih besar dari 0.');
            }

            // Note: Batch target_qty is a planning baseline, not a hard production cap.
            // Actual production quantity may be below, equal to, or above the target batch.

            // 6. Validate result_date
            if (! strtotime($resultDate)) {
                throw new DomainException('Format tanggal hasil produksi tidak valid.');
            }

            // 7. Resolve actor & normalize notes
            $normalizedNotes = (is_string($notes) && trim($notes) !== '') ? trim($notes) : null;

            // 8. Set Batch material usage markers
            $lockedBatch->material_usage_recorded_at = now();
            $lockedBatch->material_usage_recorded_by = $actorId;

            // 9. Persist ProductionResult
            $result = ProductionResult::create([
                'batch_id'    => $lockedBatch->id,
                'actual_qty'  => $actualQtyStr,
                'result_date' => $resultDate,
                'notes'       => $normalizedNotes,
                'recorded_by' => $actorId,
            ]);

            // 10. Transition Batch: PRODUKSI -> QC
            $lockedBatch->status = BatchStatus::QC;
            $lockedBatch->save();

            // 11. Synchronize aggregate Order operational status
            $formattedQty = rtrim(rtrim(number_format((float) $actualQtyStr, 4), '0'), '.');
            if ($lockedBatch->orderItem && $lockedBatch->orderItem->order) {
                $this->orderStatusService->syncOperationalStatus(
                    $lockedBatch->orderItem->order,
                    $actorUser,
                    "Hasil produksi Batch {$lockedBatch->batch_no} selesai dicatat ({$formattedQty} pcs) dan masuk tahap QC."
                );
            }

            $resolvedOutletId = (int) ($lockedBatch->orderItem?->order?->outlet_id ?? 0);

            // 12. Tenant Activity Log
            \App\Services\System\TenantActivityLogger::log(
                outletId: $resolvedOutletId,
                user: $actorUser,
                module: 'PRODUKSI',
                actionType: 'RECORD_PRODUCTION',
                description: "Hasil produksi aktual Batch {$lockedBatch->batch_no} dicatat ({$formattedQty} pcs) oleh {$actorUser?->name}.",
                referenceNo: $lockedBatch->batch_no,
                subject: $result,
                afterValues: [
                    'batch_no'   => $lockedBatch->batch_no,
                    'actual_qty' => (float) $actualQtyStr,
                    'result_date'=> $resultDate,
                ]
            );

            // 13. Notifications for Owner & Admin
            if ($resolvedOutletId > 0) {
                $managers = User::where('outlet_id', $resolvedOutletId)
                    ->whereIn('role', [\App\Enums\UserRole::OWNER, \App\Enums\UserRole::ADMIN])
                    ->where('status', \App\Enums\UserStatus::ACTIVE)
                    ->get();

                $notifService = app(\App\Services\Platform\PlatformNotificationService::class);
                foreach ($managers as $mgr) {
                    $notifService->notify(
                        outletId: $resolvedOutletId,
                        userId: $mgr->id,
                        type: 'PRODUCTION_RESULT_RECORDED',
                        title: 'Hasil Produksi Dicatat',
                        message: "Hasil produksi Batch {$lockedBatch->batch_no} ({$formattedQty} pcs) dicatat oleh {$actorUser?->name} dan menunggu QC.",
                        actionUrl: route('admin.batches.show', $lockedBatch, false)
                    );
                }
            }

            return $result;
        });
    }
}
