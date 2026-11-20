<?php

namespace App\Services\Production;

use App\Enums\BatchStatus;
use App\Models\Production\ProductionBatch;
use App\Models\System\User;
use App\Services\Sales\OrderStatusService;
use DomainException;
use Illuminate\Support\Facades\DB;

class BatchWorkflowService
{
    public function __construct(
        private OrderStatusService $orderStatusService
    ) {}

    /**
     * Start a batch from BAHAN to its determined first operational stage (PRINTING or PRODUKSI).
     *
     * Invariants & Rules:
     * - Operates inside a DB transaction with row-level lock (lockForUpdate).
     * - Current status must be BAHAN (prevents double-start).
     * - All batch materials must be fully allocated by warehouse (allocated_qty == required_qty > 0).
     * - Resolves route from immutable Batch snapshot. If legacy snapshot is NULL, performs one-time
     *   initialization from master ProductVariant (fails if master is also NULL).
     * - Next status is strictly server-authoritative (true -> PRINTING, false -> PRODUKSI).
     * - Calls OrderStatusService::syncOperationalStatus to advance Order status appropriately.
     *
     * @param ProductionBatch $batch
     * @param User $user
     * @return ProductionBatch
     * @throws DomainException
     */
    public function startBatch(ProductionBatch $batch, User $user): ProductionBatch
    {
        return DB::transaction(function () use ($batch, $user) {
            /** @var ProductionBatch $lockedBatch */
            $lockedBatch = ProductionBatch::where('id', $batch->id)
                ->lockForUpdate()
                ->firstOrFail();

            // 1. Verify batch status is BAHAN
            if ($lockedBatch->status !== BatchStatus::BAHAN) {
                throw new DomainException("Batch produksi tidak dalam status BAHAN (status saat ini: {$lockedBatch->status->label()}).");
            }

            // 2. Resolve / initialize requires_printing snapshot if legacy null
            if ($lockedBatch->requires_printing === null) {
                $lockedBatch->loadMissing(['orderItem.variant']);
                $variant = $lockedBatch->orderItem?->variant;

                if ($variant === null || $variant->requires_printing === null) {
                    throw new DomainException('Rute produksi varian belum ditetapkan.');
                }

                $lockedBatch->requires_printing = (bool) $variant->requires_printing;
                $lockedBatch->save();
            }

            // 3. Verify material allocation readiness
            $lockedBatch->load(['batchMaterials' => function ($q) {
                $q->lockForUpdate();
            }]);

            if (! $lockedBatch->isMaterialReady()) {
                throw new DomainException('Bahan batch belum selesai dialokasikan oleh Gudang.');
            }

            // 4. Determine next stage based on immutable batch snapshot
            $nextStatus = $lockedBatch->requires_printing ? BatchStatus::PRINTING : BatchStatus::PRODUKSI;

            // 5. Update Batch status
            $lockedBatch->status = $nextStatus;
            $lockedBatch->save();

            // 6. Synchronize Order operational status
            if ($lockedBatch->orderItem && $lockedBatch->orderItem->order) {
                $this->orderStatusService->syncOperationalStatus(
                    $lockedBatch->orderItem->order,
                    $user,
                    "Batch {$lockedBatch->batch_no} masuk tahap {$nextStatus->label()}."
                );
            }

            \App\Services\System\TenantActivityLogger::log(
                outletId: (int) ($lockedBatch->orderItem?->order?->outlet_id ?? 0),
                user: $user,
                module: 'PRODUKSI',
                actionType: 'START_BATCH',
                description: "Karyawan {$user->name} memulai pengerjaan Batch {$lockedBatch->batch_no} (Status: {$nextStatus->label()}).",
                referenceNo: $lockedBatch->batch_no,
                subject: $lockedBatch
            );

            return $lockedBatch;
        });
    }

    /**
     * Complete the PRINTING stage and transition batch to PRODUKSI.
     *
     * Invariants & Rules (P2):
     * - Operates inside a DB transaction with row-level lock (lockForUpdate).
     * - Current status must be exactly PRINTING (rejects all other statuses, prevents double-submit).
     * - Batch immutable route snapshot must be TRUE (requires_printing === true). Non-printing batches rejected.
     * - Batch snapshot is authoritative (does not re-check live master ProductVariant).
     * - Material allocation integrity must remain satisfied (allocated_qty == required_qty > 0).
     * - Zero inventory mutation (no StockMovement OUT, no MaterialStock mutation, no used_qty change).
     * - Updates Batch status: PRINTING -> PRODUKSI.
     * - Calls OrderStatusService::syncOperationalStatus to update aggregate Order status.
     *
     * @param ProductionBatch $batch
     * @param User|null $user
     * @return ProductionBatch
     * @throws DomainException
     */
    public function completePrinting(ProductionBatch $batch, ?User $user = null): ProductionBatch
    {
        return DB::transaction(function () use ($batch, $user) {
            /** @var ProductionBatch $lockedBatch */
            $lockedBatch = ProductionBatch::where('id', $batch->id)
                ->lockForUpdate()
                ->firstOrFail();

            // 1. Verify batch status is PRINTING
            if ($lockedBatch->status !== BatchStatus::PRINTING) {
                throw new DomainException("Batch produksi tidak dalam tahap PRINTING (status saat ini: {$lockedBatch->status->label()}).");
            }

            // 2. Verify immutable batch snapshot requires printing
            if ($lockedBatch->requires_printing !== true) {
                throw new DomainException('Integritas alur kerja gagal: Batch ini tidak memiliki rute Printing.');
            }

            // 3. Verify material allocation integrity
            $lockedBatch->load(['batchMaterials' => function ($q) {
                $q->lockForUpdate();
            }]);

            if (! $lockedBatch->isMaterialReady()) {
                throw new DomainException('Integritas bahan batch tidak valid atau alokasi belum lengkap.');
            }

            // 4. Update Batch status: PRINTING -> PRODUKSI
            $lockedBatch->status = BatchStatus::PRODUKSI;
            $lockedBatch->save();

            // 5. Synchronize Order operational status
            if ($lockedBatch->orderItem && $lockedBatch->orderItem->order) {
                $this->orderStatusService->syncOperationalStatus(
                    $lockedBatch->orderItem->order,
                    $user,
                    "Batch {$lockedBatch->batch_no} selesai tahap Printing dan lanjut ke Produksi."
                );
            }

            return $lockedBatch;
        });
    }

    /**
     * Complete the PACKING stage and transition batch to SELESAI.
     *
     * Invariants & Rules (P5):
     * - Operates inside a DB transaction with row-level lock (lockForUpdate).
     * - Current status must be exactly PACKING (rejects all other statuses, double-submit safe).
     * - ProductionResult must exist on the batch.
     * - QcResult must exist on the ProductionResult.
     * - Validates QC integrity: good_qty >= 0, reject_qty >= 0, and good_qty + reject_qty == actual_qty.
     * - An all-reject batch (good = 0, reject = actual) is valid and allowed to complete PACKING -> SELESAI.
     * - Zero inventory mutation (no StockMovement, MaterialStock, or used_qty change).
     * - Updates Batch status: PACKING -> SELESAI.
     * - Calls OrderStatusService::syncOperationalStatus (Order candidate for SELESAI batch is PACKING;
     *   Order does NOT transition to SELESAI or DIKIRIM).
     *
     * @param ProductionBatch $batch
     * @param User|null $user
     * @return ProductionBatch
     * @throws DomainException
     */
    public function completePacking(ProductionBatch $batch, ?User $user = null): ProductionBatch
    {
        return DB::transaction(function () use ($batch, $user) {
            /** @var ProductionBatch $lockedBatch */
            $lockedBatch = ProductionBatch::where('id', $batch->id)
                ->lockForUpdate()
                ->firstOrFail();

            // 1. Verify batch status is PACKING
            if ($lockedBatch->status !== BatchStatus::PACKING) {
                throw new DomainException("Batch produksi tidak dalam tahap PACKING (status saat ini: {$lockedBatch->status->label()}).");
            }

            // 2. Verify ProductionResult exists
            $prodResult = \App\Models\Production\ProductionResult::where('batch_id', $lockedBatch->id)
                ->lockForUpdate()
                ->first();

            if (! $prodResult) {
                throw new DomainException('Hasil produksi belum dicatat untuk batch ini.');
            }

            // 3. Verify QcResult exists
            $qcResult = \App\Models\Production\QcResult::where('production_result_id', $prodResult->id)
                ->lockForUpdate()
                ->first();

            if (! $qcResult) {
                throw new DomainException('Hasil QC belum dicatat untuk batch ini.');
            }

            // 4. Revalidate QC integrity equation: good_qty + reject_qty == actual_qty
            $goodQtyStr = (string) $qcResult->good_qty;
            $rejectQtyStr = (string) $qcResult->reject_qty;
            $actualQtyStr = (string) $prodResult->actual_qty;

            if (bccomp($goodQtyStr, '0.0000', 4) < 0 || bccomp($rejectQtyStr, '0.0000', 4) < 0) {
                throw new DomainException('Integritas data QC rusak: kuantitas Good atau Reject bernilai negatif.');
            }

            $totalQc = bcadd($goodQtyStr, $rejectQtyStr, 4);
            if (bccomp($totalQc, $actualQtyStr, 4) !== 0) {
                throw new DomainException("Integritas data QC rusak: total Good ({$goodQtyStr}) dan Reject ({$rejectQtyStr}) tidak sama dengan hasil produksi ({$actualQtyStr}).");
            }

            // 5. Update Batch status: PACKING -> SELESAI
            $lockedBatch->status = BatchStatus::SELESAI;
            $lockedBatch->save();

            // 6. Synchronize Order operational status (capped at PACKING)
            if ($lockedBatch->orderItem && $lockedBatch->orderItem->order) {
                $this->orderStatusService->syncOperationalStatus(
                    $lockedBatch->orderItem->order,
                    $user,
                    "Batch {$lockedBatch->batch_no} selesai tahap Packing dan operasional batch SELESAI."
                );
            }

            \App\Services\System\TenantActivityLogger::log(
                outletId: (int) ($lockedBatch->orderItem?->order?->outlet_id ?? 0),
                user: $user,
                module: 'PRODUKSI',
                actionType: 'COMPLETE_BATCH',
                description: "Tahap packing Batch {$lockedBatch->batch_no} diselesaikan oleh " . ($user?->name ?? 'karyawan') . " (Batch SELESAI).",
                referenceNo: $lockedBatch->batch_no,
                subject: $lockedBatch
            );

            return $lockedBatch;
        });
    }

    /**
     * Resume production on the same batch with the same PIC to fulfill remaining shortfall.
     * Transitions batch status back to PRODUKSI.
     *
     * Invariants:
     * - Target quantity is preserved (never altered automatically).
     * - PIC is retained.
     * - Historical production results and QC records are preserved.
     * - Syncs aggregate Sales Order status.
     *
     * @param ProductionBatch $batch
     * @param User $actor
     * @param string|null $notes
     * @return ProductionBatch
     * @throws DomainException
     */
    public function resumeProduction(ProductionBatch $batch, User $actor, ?string $notes = null): ProductionBatch
    {
        return DB::transaction(function () use ($batch, $actor, $notes) {
            /** @var ProductionBatch $lockedBatch */
            $lockedBatch = ProductionBatch::where('id', $batch->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($lockedBatch->status, [BatchStatus::PACKING, BatchStatus::QC, BatchStatus::SELESAI_PARSIAL], true)) {
                throw new DomainException("Produksi lanjutan hanya dapat dilakukan dari status PACKING, QC, atau SELESAI_PARSIAL (status saat ini: {$lockedBatch->status->label()}).");
            }

            if ((float) $lockedBatch->target_qty <= $lockedBatch->total_actual_qty) {
                throw new DomainException('Target produksi batch sudah terpenuhi penuh. Produksi lanjutan tidak diperlukan.');
            }

            // Transition batch back to PRODUKSI
            $lockedBatch->status = BatchStatus::PRODUKSI;
            $lockedBatch->shortfall_qty = 0;
            $lockedBatch->close_reason = null;
            $lockedBatch->closed_by_user_id = null;
            $lockedBatch->closed_at = null;
            if ($notes) {
                $lockedBatch->notes = $lockedBatch->notes ? ($lockedBatch->notes . ' | ' . $notes) : $notes;
            }
            $lockedBatch->save();

            // Synchronize Order status
            if ($lockedBatch->orderItem && $lockedBatch->orderItem->order) {
                $this->orderStatusService->syncOperationalStatus(
                    $lockedBatch->orderItem->order,
                    $actor,
                    "Batch {$lockedBatch->batch_no} dilanjutkan produksinya untuk memenuhi sisa target."
                );
            }

            // Tenant activity log
            \App\Services\System\TenantActivityLogger::log(
                outletId: (int) ($lockedBatch->orderItem?->order?->outlet_id ?? 0),
                user: $actor,
                module: 'PRODUKSI',
                actionType: 'RESUME_PRODUCTION',
                description: "Batch {$lockedBatch->batch_no} dibuka kembali ke tahap Produksi oleh {$actor->name} untuk PIC {$lockedBatch->productionEmployee?->name}.",
                referenceNo: $lockedBatch->batch_no,
                subject: $lockedBatch
            );

            // Notify PIC
            $resolvedOutletId = (int) ($lockedBatch->orderItem?->order?->outlet_id ?? 0);
            if ($lockedBatch->production_employee_id && $resolvedOutletId > 0) {
                app(\App\Services\Platform\PlatformNotificationService::class)->notify(
                    outletId: $resolvedOutletId,
                    userId: $lockedBatch->production_employee_id,
                    type: 'BATCH_RESUMED',
                    title: 'Tugas Produksi Lanjutan',
                    message: "Batch {$lockedBatch->batch_no} telah dibuka kembali untuk melanjutkan produksi sisa target ({$lockedBatch->remaining_target_qty} pcs).",
                    actionUrl: route('produksi.batches.show', $lockedBatch, false)
                );
            }

            return $lockedBatch;
        });
    }

    /**
     * Close a batch partially when actual production is less than target and production will not be continued.
     * Transitions batch status to SELESAI_PARSIAL.
     *
     * Invariants:
     * - Target awal (target_qty) is NEVER modified.
     * - Records shortfall_qty = target_qty - total_actual_qty.
     * - Requires close_reason, closed_by_user_id, and closed_at timestamp.
     * - Preserves all QC results and inventory calculations.
     * - Does NOT automatically complete the parent Sales Order.
     *
     * @param ProductionBatch $batch
     * @param string $reason
     * @param User $actor
     * @return ProductionBatch
     * @throws DomainException
     */
    public function closeBatchPartially(ProductionBatch $batch, string $reason, User $actor): ProductionBatch
    {
        $trimmedReason = trim($reason);
        if ($trimmedReason === '') {
            throw new DomainException('Alasan penutupan batch parsial wajib diisi.');
        }

        return DB::transaction(function () use ($batch, $trimmedReason, $actor) {
            /** @var ProductionBatch $lockedBatch */
            $lockedBatch = ProductionBatch::where('id', $batch->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($lockedBatch->status, [BatchStatus::SELESAI, BatchStatus::CANCELLED], true)) {
                throw new DomainException("Batch dengan status {$lockedBatch->status->label()} tidak dapat ditutup secara parsial.");
            }

            $totalActual = $lockedBatch->total_actual_qty;
            $targetQty = (float) $lockedBatch->target_qty;

            if ($targetQty <= $totalActual) {
                throw new DomainException("Target produksi sudah terpenuhi ({$totalActual}/{$targetQty} pcs). Gunakan penyelesaian normal.");
            }

            $shortfall = max(0.0, $targetQty - $totalActual);

            $lockedBatch->status = BatchStatus::SELESAI_PARSIAL;
            $lockedBatch->shortfall_qty = $shortfall;
            $lockedBatch->close_reason = $trimmedReason;
            $lockedBatch->closed_by_user_id = $actor->id;
            $lockedBatch->closed_at = now();
            $lockedBatch->save();

            // Synchronize Order status (Order will stay at BAHAN or lowest incomplete stage)
            if ($lockedBatch->orderItem && $lockedBatch->orderItem->order) {
                $this->orderStatusService->syncOperationalStatus(
                    $lockedBatch->orderItem->order,
                    $actor,
                    "Batch {$lockedBatch->batch_no} ditutup parsial dengan kekurangan {$shortfall} pcs. Alasan: {$trimmedReason}."
                );
            }

            // Tenant activity log
            $resolvedOutletId = (int) ($lockedBatch->orderItem?->order?->outlet_id ?? 0);
            \App\Services\System\TenantActivityLogger::log(
                outletId: $resolvedOutletId,
                user: $actor,
                module: 'PRODUKSI',
                actionType: 'PARTIAL_CLOSE',
                description: "Batch {$lockedBatch->batch_no} ditutup parsial oleh {$actor->name} (Target: {$targetQty}, Aktual: {$totalActual}, Kekurangan: {$shortfall} pcs). Alasan: {$trimmedReason}",
                referenceNo: $lockedBatch->batch_no,
                subject: $lockedBatch,
                afterValues: [
                    'status'        => BatchStatus::SELESAI_PARSIAL->value,
                    'shortfall_qty' => $shortfall,
                    'close_reason'  => $trimmedReason,
                    'closed_by'     => $actor->id,
                    'closed_at'     => now()->toIso8601String(),
                ]
            );

            // Notify Owner & Admin
            if ($resolvedOutletId > 0) {
                $managers = \App\Models\System\User::where('outlet_id', $resolvedOutletId)
                    ->whereIn('role', [\App\Enums\UserRole::OWNER, \App\Enums\UserRole::ADMIN])
                    ->where('status', \App\Enums\UserStatus::ACTIVE)
                    ->get();

                $notifService = app(\App\Services\Platform\PlatformNotificationService::class);
                foreach ($managers as $mgr) {
                    $notifService->notify(
                        outletId: $resolvedOutletId,
                        userId: $mgr->id,
                        type: 'BATCH_PARTIAL_CLOSED',
                        title: 'Batch Diselesaikan Parsial',
                        message: "Batch {$lockedBatch->batch_no} ditutup parsial oleh {$actor->name} dengan kekurangan {$shortfall} pcs. Alasan: {$trimmedReason}",
                        actionUrl: route('admin.batches.show', $lockedBatch, false)
                    );
                }
            }

            return $lockedBatch;
        });
    }
}
