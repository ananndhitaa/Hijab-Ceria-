<?php

namespace App\Services\Production;

use App\Enums\BatchStatus;
use App\Models\Production\ProductionBatch;
use App\Models\Production\ProductionResult;
use App\Models\Production\QcResult;
use App\Models\System\User;
use App\Services\Sales\OrderStatusService;
use App\Support\DateTimeFormatter;
use DomainException;
use Illuminate\Support\Facades\DB;

class QcResultService
{
    public function __construct(
        private OrderStatusService $orderStatusService
    ) {}

    /**
     * Record the final QC inspection result for a batch and advance it to PACKING.
     *
     * Invariants & Rules (P4, revised per Pembimbing review):
     * - Runs inside a DB transaction with pessimistic row locking (lockForUpdate).
     * - Current Batch status must be strictly QC.
     * - ProductionResult must exist on the batch.
     * - Only uninspected ProductionResult can receive a new QcResult.
     * - QC only inspects units that were actually produced (<= actual_qty).
     * - Unproduced shortfall is NEVER treated as reject.
     * - Good + Reject = Inspected Qty <= Actual Qty.
     * - If reject_qty is provided, validates good_qty + reject_qty <= actual_qty.
     *   If omitted, reject_qty is derived as actual_qty - good_qty.
     * - QC timestamp (qc_at, UTC) and qc_date are system-generated at the moment QC is recorded.
     * - qc_by is server-derived from authenticated actor.
     * - Zero inventory mutation (no StockMovement, MaterialStock, or used_qty change).
     * - Batch transitions: QC -> PACKING.
     * - Order operational status aggregated via OrderStatusService (capped at PACKING).
     * - No auto-transition to SELESAI (SELESAI owned by P5).
     * - No Rework: reject is a final defect.
     *
     * @param ProductionBatch $batch
     * @param string|float|int $goodQty
     * @param string|null $notes
     * @param User|int $actor
     * @param string|null $qcDate
     * @param string|float|int|null $rejectQty
     * @return QcResult
     * @throws DomainException
     */
    public function recordFinalQc(
        ProductionBatch $batch,
        string|float|int $goodQty,
        ?string $notes,
        User|int $actor,
        ?string $qcDate = null,
        string|float|int|null $rejectQty = null
    ): QcResult {
        return DB::transaction(function () use ($batch, $goodQty, $notes, $actor, $qcDate, $rejectQty) {
            /** @var ProductionBatch $lockedBatch */
            $lockedBatch = ProductionBatch::where('id', $batch->id)
                ->lockForUpdate()
                ->firstOrFail();

            // 1. Validate batch status is QC
            if ($lockedBatch->status !== BatchStatus::QC) {
                throw new DomainException("Hasil QC hanya dapat dicatat saat batch dalam status QC (status saat ini: {$lockedBatch->status->label()}).");
            }

            // 2. Load and verify uninspected ProductionResult
            /** @var ProductionResult|null $prodResult */
            $prodResult = ProductionResult::where('batch_id', $lockedBatch->id)
                ->whereDoesntHave('qcResult')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $prodResult) {
                $prodResult = ProductionResult::where('batch_id', $lockedBatch->id)
                    ->latest('id')
                    ->lockForUpdate()
                    ->first();
            }

            if (! $prodResult) {
                throw new DomainException('Hasil produksi belum dicatat untuk batch ini.');
            }

            // 3. Prevent duplicate QC result on the same production result
            if (QcResult::where('production_result_id', $prodResult->id)->lockForUpdate()->exists()) {
                throw new DomainException('Hasil QC untuk hasil produksi ini sudah dicatat.');
            }

            // 3b. QC Eligibility Date Gate: QC cannot be performed before authoritative actual production date
            $productionDate = $lockedBatch->getActualProductionDate();
            $todayBusiness = DateTimeFormatter::toDisplayTimezone(now())->toDateString();

            if ($todayBusiness < $productionDate) {
                throw new DomainException('QC belum dapat dilakukan sebelum tanggal produksi.');
            }

            // 4. Validate good_qty type and non-negativity
            $goodQtyStr = (string) $goodQty;

            if (! is_numeric($goodQtyStr) || bccomp($goodQtyStr, '0.0000', 4) < 0) {
                throw new DomainException('Kuantitas produk Good harus berupa angka non-negatif (>= 0).');
            }

            if (str_contains($goodQtyStr, '.') && rtrim(substr($goodQtyStr, strpos($goodQtyStr, '.') + 1), '0') !== '') {
                throw new DomainException('Kuantitas produk Good harus berupa bilangan bulat (pcs).');
            }

            $actualQtyStr = (string) $prodResult->actual_qty;

            // 5. Invariant: good_qty + reject_qty <= actual_qty; shortfall is never reject
            if ($rejectQty !== null && $rejectQty !== '') {
                $rejectQtyStr = (string) $rejectQty;

                if (! is_numeric($rejectQtyStr) || bccomp($rejectQtyStr, '0.0000', 4) < 0) {
                    throw new DomainException('Kuantitas produk Reject harus berupa angka non-negatif (>= 0).');
                }

                if (str_contains($rejectQtyStr, '.') && rtrim(substr($rejectQtyStr, strpos($rejectQtyStr, '.') + 1), '0') !== '') {
                    throw new DomainException('Kuantitas produk Reject harus berupa bilangan bulat (pcs).');
                }

                $totalInspected = bcadd($goodQtyStr, $rejectQtyStr, 4);
                if (bccomp($totalInspected, $actualQtyStr, 4) > 0) {
                    $formattedInspected = rtrim(rtrim(number_format((float) $totalInspected, 4), '0'), '.');
                    $formattedActual = rtrim(rtrim(number_format((float) $actualQtyStr, 4), '0'), '.');
                    throw new DomainException("Total unit diperiksa ({$formattedInspected} pcs) tidak boleh melebihi hasil produksi aktual ({$formattedActual} pcs).");
                }
            } else {
                if (bccomp($goodQtyStr, $actualQtyStr, 4) > 0) {
                    $formattedGood = rtrim(rtrim(number_format((float) $goodQtyStr, 4), '0'), '.');
                    $formattedActual = rtrim(rtrim(number_format((float) $actualQtyStr, 4), '0'), '.');
                    throw new DomainException("Jumlah lolos QC ({$formattedGood}) tidak boleh melebihi hasil produksi aktual ({$formattedActual} pcs).");
                }

                $rejectQtyStr = bcsub($actualQtyStr, $goodQtyStr, 4);
            }

            // 6. QC dates:
            // - qc_date: business QC date chosen by Staff Produksi (defaults to authoritative actual production date)
            //   In-service guard: qc_date must not be before productionDate
            $resolvedQcDate = ($qcDate && trim($qcDate) !== '') ? trim($qcDate) : $productionDate;

            if ($resolvedQcDate < $productionDate) {
                throw new DomainException("Tanggal QC ({$resolvedQcDate}) tidak boleh sebelum tanggal produksi aktual ({$productionDate}).");
            }

            // - qc_at: actual submission timestamp for audit (now() stored in UTC)
            $qcAt = now();

            // 7. Resolve actor & normalize notes
            $actorUser = $actor instanceof User ? $actor : User::find($actor);
            $actorId = $actorUser?->id ?? (is_numeric($actor) ? (int) $actor : null);
            $normalizedNotes = (is_string($notes) && trim($notes) !== '') ? trim($notes) : null;

            // 8. Persist QcResult
            $qcResult = QcResult::create([
                'production_result_id' => $prodResult->id,
                'good_qty'             => $goodQtyStr,
                'reject_qty'           => $rejectQtyStr,
                'qc_date'              => $resolvedQcDate,
                'qc_at'                => $qcAt,
                'notes'                => $normalizedNotes,
                'qc_by'                => $actorId,
            ]);

            // 9. Transition Batch: QC -> PACKING
            $lockedBatch->status = BatchStatus::PACKING;
            $lockedBatch->save();

            // 10. Synchronize aggregate Order operational status
            $formattedGood = rtrim(rtrim(number_format((float) $goodQtyStr, 4), '0'), '.');
            $formattedReject = rtrim(rtrim(number_format((float) $rejectQtyStr, 4), '0'), '.');

            if ($lockedBatch->orderItem && $lockedBatch->orderItem->order) {
                $this->orderStatusService->syncOperationalStatus(
                    $lockedBatch->orderItem->order,
                    $actorUser,
                    "Pemeriksaan QC Batch {$lockedBatch->batch_no} selesai (Good: {$formattedGood} pcs, Reject: {$formattedReject} pcs) dan masuk tahap Packing."
                );
            }

            $resolvedOutletId = (int) ($lockedBatch->orderItem?->order?->outlet_id ?? 0);

            // 11. Tenant Activity Log
            \App\Services\System\TenantActivityLogger::log(
                outletId: $resolvedOutletId,
                user: $actorUser,
                module: 'QC',
                actionType: 'RECORD_QC',
                description: "Pemeriksaan QC Batch {$lockedBatch->batch_no} selesai (Good: {$formattedGood} pcs, Reject: {$formattedReject} pcs) oleh {$actorUser?->name}.",
                referenceNo: $lockedBatch->batch_no,
                subject: $qcResult,
                afterValues: [
                    'batch_no'   => $lockedBatch->batch_no,
                    'good_qty'   => (float) $goodQtyStr,
                    'reject_qty' => (float) $rejectQtyStr,
                    'qc_date'    => $resolvedQcDate,
                ]
            );

            // 12. Notifications for Owner & Admin
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
                        type: 'QC_RESULT_RECORDED',
                        title: 'Hasil QC Dicatat',
                        message: "Pemeriksaan QC Batch {$lockedBatch->batch_no} selesai (Good: {$formattedGood} pcs, Reject: {$formattedReject} pcs).",
                        actionUrl: route('admin.batches.show', $lockedBatch, false)
                    );
                }
            }

            return $qcResult;
        });
    }
}
