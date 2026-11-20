<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gudang\StoreMaterialRequest;
use App\Http\Requests\Gudang\UpdateMaterialRequest;
use App\Models\Inventory\MaterialStock;
use App\Models\Master\Material;
use App\Models\Master\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaterialController extends Controller
{
    public function index(Request $request): View
    {
        $outletId = $this->getActiveTenantOutletId();

        $search = $request->input('search');
        $statusFilter = $request->input('stock_status');
        $activeFilter = $request->input('active');

        $materialsQuery = $outletId !== null
            ? Material::forOutlet($outletId)
            : Material::query();

        $materialsQuery
            ->with(['unit', 'stock'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('material_name', 'like', "%{$search}%")
                      ->orWhereHas('unit', function ($uq) use ($search) {
                          $uq->where('unit_name', 'like', "%{$search}%")
                             ->orWhere('symbol', 'like', "%{$search}%");
                      });
                });
            })
            ->when($activeFilter !== null && $activeFilter !== '', function ($query) use ($activeFilter) {
                $query->where('active', (bool) $activeFilter);
            });

        // Filter by stock status if provided
        if ($statusFilter && in_array($statusFilter, ['HABIS', 'RENDAH', 'AMAN'], true)) {
            if ($statusFilter === 'HABIS') {
                $materialsQuery->where(function ($q) {
                    $q->whereHas('stock', function ($sq) {
                        $sq->where('qty_on_hand', '<=', 0);
                    })->orWhereDoesntHave('stock');
                });
            } elseif ($statusFilter === 'RENDAH') {
                $materialsQuery->whereHas('stock', function ($q) {
                    $q->where('qty_on_hand', '>', 0)
                      ->whereColumn('qty_on_hand', '<=', 'materials.min_stock');
                });
            } elseif ($statusFilter === 'AMAN') {
                $materialsQuery->whereHas('stock', function ($q) {
                    $q->whereColumn('qty_on_hand', '>', 'materials.min_stock');
                });
            }
        }

        $materials = $materialsQuery->orderBy('material_name')->paginate(15)->withQueryString();

        return view('gudang.materials.index', compact('materials', 'search', 'statusFilter', 'activeFilter'));
    }

    public function create(): View
    {
        $units = Unit::orderBy('unit_name')->get();

        return view('gudang.materials.create', compact('units'));
    }

    public function store(StoreMaterialRequest $request): RedirectResponse
    {
        $outletId = $this->getActiveTenantOutletId();

        $data = $request->validated();
        $data['active'] = $request->boolean('active', true);
        if ($outletId !== null) {
            $data['outlet_id'] = $outletId;
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            $material = Material::create($data);

            // Ensure MaterialStock record exists with default 0
            MaterialStock::firstOrCreate(
                ['material_id' => $material->id],
                ['qty_on_hand' => 0, 'avg_unit_cost' => 0]
            );

            return redirect()->route('gudang.materials.show', $material)
                ->with('success', 'Material berhasil ditambahkan.');
        });
    }

    public function show(Material $material): View
    {
        $outletId = $this->getActiveTenantOutletId();

        if ($outletId !== null) {
            abort_unless($material->outlet_id !== null && (int) $material->outlet_id === $outletId, 404);
        }

        $material->load(['unit', 'stock', 'suppliers']);

        $recentMovements = \App\Models\Inventory\StockMovement::where('material_id', $material->id)
            ->with('createdByUser')
            ->latest('movement_date')
            ->latest('id')
            ->take(5)
            ->get();

        $latestReceivedItem = \App\Models\Inventory\PurchaseItem::where('material_id', $material->id)
            ->whereHas('purchase', function ($q) use ($outletId) {
                $q->where('status', \App\Enums\PurchaseStatus::RECEIVED)
                  ->when($outletId !== null, fn ($pq) => $pq->where('outlet_id', $outletId));
            })
            ->with('purchase.supplier')
            ->latest('id')
            ->first();

        $qtyOnHand = (string) ($material->stock?->qty_on_hand ?? '0');
        $avgUnitCost = (string) ($material->stock?->avg_unit_cost ?? '0');
        $totalStockValue = bcmul($qtyOnHand, $avgUnitCost, 2);

        return view('gudang.materials.show', compact('material', 'recentMovements', 'latestReceivedItem', 'totalStockValue'));
    }

    public function edit(Request $request, Material $material): View
    {
        $outletId = $this->getActiveTenantOutletId();

        if ($outletId !== null) {
            abort_unless($material->outlet_id !== null && (int) $material->outlet_id === $outletId, 404);
        }

        $units = Unit::orderBy('unit_name')->get();
        $from = $request->query('from') === 'show' ? 'show' : 'index';

        return view('gudang.materials.edit', compact('material', 'units', 'from'));
    }

    public function update(UpdateMaterialRequest $request, Material $material): RedirectResponse
    {
        $outletId = $this->getActiveTenantOutletId();

        if ($outletId !== null) {
            abort_unless($material->outlet_id !== null && (int) $material->outlet_id === $outletId, 404);
        }

        $data = $request->validated();
        $data['active'] = $request->boolean('active', true);
        unset($data['outlet_id']); // Cannot reassign tenant ownership

        $material->update($data);

        return redirect()->route('gudang.materials.show', $material)
            ->with('success', 'Perubahan material berhasil disimpan.');
    }

    public function destroy(Material $material): RedirectResponse
    {
        $outletId = $this->getActiveTenantOutletId();

        if ($outletId !== null) {
            abort_unless($material->outlet_id !== null && (int) $material->outlet_id === $outletId, 404);
        }

        $hasStock = bccomp((string) ($material->stock?->qty_on_hand ?? '0'), '0', 4) > 0;

        if (
            $hasStock ||
            $material->bomItems()->exists() ||
            $material->batchMaterials()->exists() ||
            $material->purchaseItems()->exists() ||
            $material->stockMovements()->exists()
        ) {
            return redirect()->route('gudang.materials.index')
                ->with('error', 'Material tidak dapat dihapus karena memiliki riwayat transaksi/stok/BOM. Anda dapat menonaktifkannya.');
        }

        $material->delete();

        return redirect()->route('gudang.materials.index')
            ->with('success', 'Material berhasil dihapus.');
    }

    public function toggleActive(Material $material): RedirectResponse
    {
        $outletId = $this->getActiveTenantOutletId();

        if ($outletId !== null) {
            abort_unless($material->outlet_id !== null && (int) $material->outlet_id === $outletId, 404);
        }

        $material->update(['active' => ! $material->active]);

        $message = $material->active
            ? 'Material berhasil diaktifkan.'
            : 'Material berhasil dinonaktifkan.';

        return back()->with('success', $message);
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
            return app(\App\Services\System\TenantContext::class)->currentOutletId();
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            abort(403, $e->getMessage() ?: 'Akses ditolak: Pengguna tidak memiliki akses tenant yang sah.');
        }
    }
}
