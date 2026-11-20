<?php

namespace App\Http\Controllers\Gudang;

use App\Enums\BatchStatus;
use App\Http\Controllers\Controller;
use App\Models\Production\ProductionBatch;
use App\Services\Inventory\BatchMaterialPlanningService;
use App\Services\Inventory\MaterialAllocationService;
use App\Services\System\TenantContext;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaterialPlanningController extends Controller
{
    public function index(Request $request): View
    {
        $outletId = $this->getActiveTenantOutletId();

        $search = $request->input('search');
        $batchStatus = $request->input('batch_status') ?? $request->input('status');
        $readinessStatus = $request->input('readiness_status') ?? $request->input('planning_status');

        $batchesQuery = $outletId !== null
            ? ProductionBatch::forOutlet($outletId)
            : ProductionBatch::query();

        $batchesQuery->with([
            'orderItem.order.customer',
            'orderItem.variant.product',
            'batchMaterials.material.stock',
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
                      $vq->where('variant_name', 'like', "%{$search}%")
                         ->orWhereHas('product', function ($pq) use ($search) {
                             $pq->where('product_name', 'like', "%{$search}%");
                         });
                  });
            });
        });

        // Batch Status Filter (Semua Batch by default; Aktif / Selesai selectable)
        if ($batchStatus === 'SELESAI') {
            $batchesQuery->where('status', BatchStatus::SELESAI);
        } elseif ($batchStatus === 'AKTIF') {
            $batchesQuery->where('status', '!=', BatchStatus::SELESAI);
        }

        // Material Readiness Filter
        if ($readinessStatus && $readinessStatus !== 'ALL') {
            $batchesQuery->whereMaterialReadiness($readinessStatus);
        }

        // Operational Priority Ordering:
        // 1: Needs allocation with stock available (Siap Dialokasikan)
        // 2: Shortage / Needs purchasing (Perlu Pembelian)
        // 3: Other unallocated active
        // 4: Allocated active (Teralokasi)
        // 5: Completed historical (Selesai)
        // 6: Cancelled
        $batchesQuery->orderByRaw("
            CASE
                WHEN status = 'SELESAI' THEN 5
                WHEN status = 'CANCELLED' THEN 6
                WHEN (SELECT COUNT(*) FROM batch_materials bm WHERE bm.batch_id = production_batches.id AND bm.allocated_qty < bm.required_qty AND (bm.shortage_qty = 0 OR bm.shortage_qty IS NULL)) > 0 THEN 1
                WHEN (SELECT COUNT(*) FROM batch_materials bm WHERE bm.batch_id = production_batches.id AND bm.allocated_qty < bm.required_qty AND bm.shortage_qty > 0) > 0 THEN 2
                WHEN (SELECT COUNT(*) FROM batch_materials bm WHERE bm.batch_id = production_batches.id AND bm.allocated_qty < bm.required_qty) > 0 THEN 3
                ELSE 4
            END ASC, id DESC
        ");

        $batches = $batchesQuery->paginate(15)->withQueryString();

        $statusFilter = $batchStatus;

        return view('gudang.material-planning.index', compact('batches', 'search', 'batchStatus', 'readinessStatus', 'statusFilter'));
    }

    public function show(ProductionBatch $batch, BatchMaterialPlanningService $service): View
    {
        $outletId = $this->getActiveTenantOutletId();

        if ($outletId !== null) {
            abort_unless($batch->hasConsistentTenantRelations($outletId), 404);
        }

        $batch->load([
            'orderItem.order.customer',
            'orderItem.variant.product',
        ]);

        $mrpData = $service->getMaterialRequirementsData($batch);

        return view('gudang.material-planning.show', compact('batch', 'mrpData'));
    }

    public function calculate(ProductionBatch $batch, BatchMaterialPlanningService $service): RedirectResponse
    {
        $outletId = $this->getActiveTenantOutletId();

        if ($outletId !== null) {
            abort_unless($batch->hasConsistentTenantRelations($outletId), 404);
        }

        try {
            $result = $service->generateMaterialPlanning($batch);

            return redirect()->route('gudang.material-planning.show', $batch)
                ->with('success', $result['message']);
        } catch (DomainException $e) {
            return redirect()->route('gudang.material-planning.show', $batch)
                ->with('error', $e->getMessage());
        }
    }

    public function allocate(ProductionBatch $batch, MaterialAllocationService $allocationService): RedirectResponse
    {
        $outletId = $this->getActiveTenantOutletId();

        if ($outletId !== null) {
            abort_unless($batch->hasConsistentTenantRelations($outletId), 404);
        }

        try {
            $allocated = $allocationService->allocateBatchMaterials($batch, auth()->id());

            return redirect()->route('gudang.material-planning.show', $allocated)
                ->with('success', 'Bahan berhasil dialokasikan.');
        } catch (DomainException $e) {
            return redirect()->route('gudang.material-planning.show', $batch)
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
