<?php

namespace App\Http\Controllers\Produksi;

use App\Enums\BatchStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Produksi\StoreQcResultRequest;
use App\Models\Production\ProductionBatch;
use App\Services\Production\QcResultService;
use App\Services\System\TenantContext;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class QcResultController extends Controller
{
    public function create(ProductionBatch $batch): View|RedirectResponse
    {
        $outletId = $this->getActiveTenantOutletId();

        if ($outletId !== null) {
            abort_unless($batch->hasConsistentTenantRelations($outletId), 404);
        }

        if ($batch->production_employee_id !== null && (int) $batch->production_employee_id !== (int) auth()->id()) {
            abort(403, 'Akses ditolak: Batch ini ditugaskan kepada karyawan lain.');
        }

        if ($batch->status !== BatchStatus::QC) {
            return redirect()->route('produksi.batches.show', $batch)
                ->with('error', "Pemeriksaan QC hanya dapat dilakukan saat batch dalam status QC (status saat ini: {$batch->status->label()}).");
        }

        if (! $batch->productionResult()->exists()) {
            return redirect()->route('produksi.batches.show', $batch)
                ->with('error', 'Hasil produksi belum dicatat untuk batch ini.');
        }

        $hasPendingQc = $batch->productionResults()->whereDoesntHave('qcResult')->exists();
        if (! $hasPendingQc) {
            return redirect()->route('produksi.batches.show', $batch)
                ->with('error', 'Hasil QC batch sudah dicatat.');
        }

        $todayBusiness = \App\Support\DateTimeFormatter::toDisplayTimezone(now())->toDateString();
        $productionDate = $batch->getActualProductionDate();

        if ($todayBusiness < $productionDate) {
            return redirect()->route('produksi.batches.show', $batch)
                ->with('error', 'QC belum dapat dilakukan sebelum tanggal produksi.');
        }

        $batch->load([
            'orderItem.order.customer',
            'orderItem.variant.product',
            'orderItem.productionBatches.qcResult',
            'productionResult.recordedByUser',
        ]);

        $orderItem = $batch->orderItem;
        $orderItemQtyOrdered = (float) ($orderItem?->qty_ordered ?? 0);

        $siblingConfirmedGood = 0.0;
        $siblingPendingQty = 0.0;

        if ($orderItem) {
            foreach ($orderItem->productionBatches as $siblingBatch) {
                if ($siblingBatch->id === $batch->id || $siblingBatch->status === BatchStatus::CANCELLED) {
                    continue;
                }

                if ($siblingBatch->qcResult !== null) {
                    $siblingConfirmedGood += (float) $siblingBatch->qcResult->good_qty;
                } else {
                    $siblingPendingQty += (float) $siblingBatch->target_qty;
                }
            }
        }

        return view('produksi.batches.qc.create', compact(
            'batch',
            'orderItemQtyOrdered',
            'siblingConfirmedGood',
            'siblingPendingQty'
        ));
    }

    public function store(
        StoreQcResultRequest $request,
        ProductionBatch $batch,
        QcResultService $qcService
    ): RedirectResponse {
        $outletId = $this->getActiveTenantOutletId();

        if ($outletId !== null) {
            abort_unless($batch->hasConsistentTenantRelations($outletId), 404);
        }

        if ($batch->production_employee_id !== null && (int) $batch->production_employee_id !== (int) auth()->id()) {
            abort(403, 'Akses ditolak: Batch ini ditugaskan kepada karyawan lain.');
        }

        try {
            $qcService->recordFinalQc(
                $batch,
                $request->input('good_qty'),
                $request->input('notes'),
                $request->user(),
                $request->input('qc_date')
            );

            return redirect()->route('produksi.batches.show', $batch)
                ->with('success', "Hasil QC Batch {$batch->batch_no} berhasil dicatat dan batch dilanjutkan ke tahap Packing.");
        } catch (DomainException $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
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
