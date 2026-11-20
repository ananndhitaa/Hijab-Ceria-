<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gudang\StockOpnameRequest;
use App\Models\Master\Material;
use App\Services\Inventory\StockOpnameService;
use App\Services\System\TenantContext;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StockOpnameController extends Controller
{
    public function create(Material $material): View
    {
        $outletId = $this->getActiveTenantOutletId();
        $this->validateMaterialTenantOwnership($material, $outletId);

        $material->load(['unit', 'stock']);

        return view('gudang.materials.stock-opname', compact('material'));
    }

    public function store(StockOpnameRequest $request, Material $material, StockOpnameService $service): RedirectResponse
    {
        $outletId = $this->getActiveTenantOutletId();
        $this->validateMaterialTenantOwnership($material, $outletId);

        try {
            $movement = $service->adjustStock(
                $material,
                $request->input('physical_qty'),
                $request->input('notes'),
                $request->user()?->id
            );

            if ($movement === null) {
                return redirect()->route('gudang.materials.show', $material)
                    ->with('info', 'Stok fisik sesuai dengan stok sistem. Tidak ada penyesuaian.');
            }

            return redirect()->route('gudang.materials.show', $material)
                ->with('success', 'Stock opname berhasil disimpan. Stok sistem telah disesuaikan.');
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
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

    /**
     * Validate material tenant ownership against the active tenant.
     * Fails closed with 404 (anti-IDOR) if material belongs to another outlet or has null outlet.
     */
    protected function validateMaterialTenantOwnership(Material $material, ?int $outletId): void
    {
        if ($outletId === null) {
            return;
        }

        if ($material->outlet_id === null || (int) $material->outlet_id !== (int) $outletId) {
            abort(404, 'Material tidak ditemukan untuk outlet ini.');
        }
    }
}
