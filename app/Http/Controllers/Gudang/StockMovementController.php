<?php

namespace App\Http\Controllers\Gudang;

use App\Enums\MovementType;
use App\Http\Controllers\Controller;
use App\Models\Inventory\PurchaseItem;
use App\Models\Inventory\StockMovement;
use App\Models\Master\Material;
use App\Services\System\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    /**
     * Display a read-only listing of stock movements for Gudang.
     */
    public function index(Request $request): View
    {
        $outletId = $this->getActiveTenantOutletId();

        $materialFilter = $request->input('material_id');
        $typeFilter = $request->input('movement_type');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $movementsQuery = $outletId !== null
            ? StockMovement::forOutlet($outletId)
            : StockMovement::query();

        $movements = $movementsQuery
            ->with(['material.unit', 'createdByUser'])
            ->when($materialFilter, function ($query, $materialId) use ($outletId) {
                if ($outletId !== null) {
                    $query->where('material_id', $materialId)
                        ->whereHas('material', fn ($mq) => $mq->where('materials.outlet_id', $outletId));
                } else {
                    $query->where('material_id', $materialId);
                }
            })
            ->when($typeFilter, function ($query, $type) {
                $query->where('movement_type', $type);
            })
            ->when($dateFrom, function ($query, $from) {
                $query->whereDate('movement_date', '>=', $from);
            })
            ->when($dateTo, function ($query, $to) {
                $query->whereDate('movement_date', '<=', $to);
            })
            ->latest('movement_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        // Eager-load purchase references for purchase_items with tenant scoping
        $purchaseItemIds = $movements->where('reference_type', 'purchase_items')
            ->pluck('reference_id')
            ->filter()
            ->unique()
            ->all();

        $purchaseItemsMap = [];
        if (! empty($purchaseItemIds)) {
            $purchaseItemsMap = PurchaseItem::with('purchase')
                ->whereIn('id', $purchaseItemIds)
                ->when($outletId !== null, function ($q) use ($outletId) {
                    $q->whereHas('purchase', fn ($pq) => $pq->where('outlet_id', $outletId));
                })
                ->get()
                ->keyBy('id');
        }

        // Eager-load batch references for batch_materials with tenant scoping
        $batchMaterialIds = $movements->where('reference_type', 'batch_materials')
            ->pluck('reference_id')
            ->filter()
            ->unique()
            ->all();

        $batchMaterialsMap = [];
        if (! empty($batchMaterialIds)) {
            $batchMaterialsMap = \App\Models\Production\BatchMaterial::with('batch')
                ->whereIn('id', $batchMaterialIds)
                ->when($outletId !== null, function ($q) use ($outletId) {
                    $q->where(function ($sub) use ($outletId) {
                        $sub->whereHas('batch', fn ($bq) => $bq->forOutlet($outletId))
                            ->orWhereHas('material', fn ($mq) => $mq->where('materials.outlet_id', $outletId));
                    });
                })
                ->get()
                ->keyBy('id');
        }

        // Eager-load batch references for production_batches with tenant scoping
        $productionBatchIds = $movements->where('reference_type', 'production_batches')
            ->pluck('reference_id')
            ->filter()
            ->unique()
            ->all();

        $batchesMap = [];
        if (! empty($productionBatchIds)) {
            $batchesMap = \App\Models\Production\ProductionBatch::whereIn('id', $productionBatchIds)
                ->when($outletId !== null, function ($q) use ($outletId) {
                    $q->forOutlet($outletId);
                })
                ->get()
                ->keyBy('id');
        }

        $materialsQuery = $outletId !== null
            ? Material::forOutlet($outletId)
            : Material::query();
        $materials = $materialsQuery->orderBy('material_name')->get();
        $movementTypes = MovementType::cases();

        return view('gudang.stock-movements.index', compact(
            'movements',
            'materials',
            'movementTypes',
            'purchaseItemsMap',
            'batchMaterialsMap',
            'batchesMap',
            'materialFilter',
            'typeFilter',
            'dateFrom',
            'dateTo'
        ));
    }

    /**
     * Resolve the active tenant outlet ID when tenant isolation is enabled.
     * Returns null when tenant isolation is disabled (legacy single-tenant mode).
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
