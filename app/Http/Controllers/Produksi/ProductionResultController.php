<?php

namespace App\Http\Controllers\Produksi;

use App\Enums\BatchStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Produksi\StoreProductionResultRequest;
use App\Models\Production\BatchMaterial;
use App\Models\Production\ProductionBatch;
use App\Services\Inventory\MaterialAllocationService;
use App\Services\Production\ProductionResultService;
use App\Services\System\TenantContext;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductionResultController extends Controller
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

        if ($batch->status !== BatchStatus::PRODUKSI) {
            return redirect()->route('produksi.batches.show', $batch)
                ->with('error', "Hasil produksi hanya dapat dicatat saat batch dalam status PRODUKSI (status saat ini: {$batch->status->label()}).");
        }

        if (empty($batch->production_employee_id)) {
            return redirect()->route('produksi.batches.show', $batch)
                ->with('error', 'PIC Produksi belum ditentukan. Tetapkan karyawan Produksi sebelum mencatat hasil produksi.');
        }

        $uninspectedResult = $batch->productionResults()->whereDoesntHave('qcResult')->first();

        if (! $batch->isMaterialReady()) {
            return redirect()->route('produksi.batches.show', $batch)
                ->with('error', 'Bahan batch belum selesai dialokasikan oleh Gudang.');
        }

        $batch->load([
            'orderItem.order.customer',
            'orderItem.variant.product',
            'batchMaterials.material.unit',
            'batchMaterials.material.stock',
        ]);

        return view('produksi.batches.result.create', compact('batch', 'uninspectedResult'));
    }

    public function store(
        StoreProductionResultRequest $request,
        ProductionBatch $batch,
        ProductionResultService $resultService
    ): RedirectResponse {
        $outletId = $this->getActiveTenantOutletId();

        if ($outletId !== null) {
            abort_unless($batch->hasConsistentTenantRelations($outletId), 404);
        }

        if ($batch->production_employee_id !== null && (int) $batch->production_employee_id !== (int) auth()->id()) {
            abort(403, 'Akses ditolak: Batch ini ditugaskan kepada karyawan lain.');
        }

        try {
            $resultService->recordFinalResult(
                $batch,
                $request->input('actual_qty'),
                $request->input('result_date'),
                $request->input('notes'),
                $request->user(),
                $request->input('material_usage', [])
            );

            return redirect()->route('produksi.batches.show', $batch)
                ->with('success', "Pemakaian bahan dan hasil produksi Batch {$batch->batch_no} berhasil dicatat dan batch dilanjutkan ke tahap QC.");
        } catch (DomainException $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Request and execute additional material allocation from warehouse stock for a batch in PRODUKSI status.
     */
    public function requestAdditionalMaterial(
        Request $request,
        ProductionBatch $batch,
        BatchMaterial $batchMaterial,
        MaterialAllocationService $allocationService
    ): RedirectResponse {
        $outletId = $this->getActiveTenantOutletId();

        if ($outletId !== null) {
            abort_unless($batch->hasConsistentTenantRelations($outletId), 404);
            abort_if((int) $batchMaterial->batch_id !== (int) $batch->id, 404);
            abort_unless(
                $batchMaterial->material
                    && $batchMaterial->material->outlet_id !== null
                    && (int) $batchMaterial->material->outlet_id === $outletId,
                404
            );
        }

        if ($batch->production_employee_id !== null && (int) $batch->production_employee_id !== (int) auth()->id()) {
            abort(403, 'Akses ditolak: Batch ini ditugaskan kepada karyawan lain.');
        }

        $validated = $request->validate([
            'additional_qty' => ['required', 'numeric', 'gt:0'],
        ], [
            'additional_qty.required' => 'Jumlah tambahan bahan wajib diisi.',
            'additional_qty.gt'       => 'Jumlah tambahan bahan harus lebih besar dari 0.',
        ]);

        if ($batchMaterial->batch_id !== $batch->id) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Bahan yang diminta bukan milik batch ini.');
        }

        try {
            $updatedBm = $allocationService->allocateAdditionalMaterial(
                $batch,
                $batchMaterial->id,
                $validated['additional_qty'],
                $request->user()?->id
            );

            $matName = $updatedBm->material?->material_name ?? 'Material';
            $unit = $updatedBm->material?->unit?->symbol ?? 'unit';
            $addedFormatted = rtrim(rtrim(number_format((float) $validated['additional_qty'], 4), '0'), '.');
            $newAllocFormatted = rtrim(rtrim(number_format((float) $updatedBm->allocated_qty, 4), '0'), '.');

            return redirect()->back()
                ->with('success', "Berhasil menambahkan {$addedFormatted} {$unit} {$matName} dari Gudang. Total alokasi batch kini {$newAllocFormatted} {$unit}.");
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
