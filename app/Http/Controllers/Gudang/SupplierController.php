<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gudang\StoreSupplierRequest;
use App\Http\Requests\Gudang\UpdateSupplierRequest;
use App\Models\Master\Material;
use App\Models\Master\Supplier;
use App\Services\System\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $outletId = $this->getActiveTenantOutletId();

        $search = $request->input('search');
        $activeFilter = $request->input('active');

        $suppliersQuery = $outletId !== null
            ? Supplier::forOutlet($outletId)
            : Supplier::query();

        $suppliers = $suppliersQuery
            ->with(['materials' => function ($q) {
                $q->orderBy('material_name');
            }])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('supplier_name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhereHas('materials', function ($mq) use ($search) {
                          $mq->where('material_name', 'like', "%{$search}%");
                      });
                });
            })
            ->when($activeFilter !== null && $activeFilter !== '', function ($query) use ($activeFilter) {
                $query->where('active', (bool) $activeFilter);
            })
            ->orderBy('supplier_name')
            ->paginate(15)
            ->withQueryString();

        return view('gudang.suppliers.index', compact('suppliers', 'search', 'activeFilter'));
    }

    public function create(): View
    {
        $outletId = $this->getActiveTenantOutletId();

        $materials = Material::with('unit')
            ->when($outletId !== null, fn ($q) => $q->where('outlet_id', $outletId))
            ->where('active', true)
            ->orderBy('material_name')
            ->get();

        return view('gudang.suppliers.create', compact('materials'));
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $outletId = $this->getActiveTenantOutletId();

        $data = $request->validated();
        $data['active'] = $request->boolean('active', true);
        if ($outletId !== null) {
            $data['outlet_id'] = $outletId;
        }

        $materialIds = $request->input('material_ids', []);

        return DB::transaction(function () use ($data, $materialIds, $outletId) {
            if ($outletId !== null && ! empty($materialIds)) {
                $validCount = Material::whereIn('id', $materialIds)
                    ->where('outlet_id', $outletId)
                    ->count();
                if ($validCount !== count($materialIds)) {
                    abort(422, 'Material yang dipilih tidak valid untuk outlet ini.');
                }
            }

            $supplier = Supplier::create($data);
            $supplier->materials()->sync($materialIds);

            return redirect()->route('gudang.suppliers.show', $supplier)
                ->with('success', 'Supplier berhasil ditambahkan.');
        });
    }

    public function show(Supplier $supplier): View
    {
        $outletId = $this->getActiveTenantOutletId();

        if ($outletId !== null) {
            abort_unless($supplier->outlet_id !== null && (int) $supplier->outlet_id === $outletId, 404);
        }

        $supplier->load(['materials' => function ($q) use ($outletId) {
            $q->when($outletId !== null, fn ($mq) => $mq->where('materials.outlet_id', $outletId))
                ->with('unit');
        }]);

        return view('gudang.suppliers.show', compact('supplier'));
    }

    public function edit(Request $request, Supplier $supplier): View
    {
        $outletId = $this->getActiveTenantOutletId();

        if ($outletId !== null) {
            abort_unless($supplier->outlet_id !== null && (int) $supplier->outlet_id === $outletId, 404);
        }

        $from = $request->query('from') === 'show' ? 'show' : 'index';
        $supplier->load('materials');

        $materials = Material::with('unit')
            ->when($outletId !== null, fn ($q) => $q->where('outlet_id', $outletId))
            ->orderBy('material_name')
            ->get();

        return view('gudang.suppliers.edit', compact('supplier', 'materials', 'from'));
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $outletId = $this->getActiveTenantOutletId();

        if ($outletId !== null) {
            abort_unless($supplier->outlet_id !== null && (int) $supplier->outlet_id === $outletId, 404);
        }

        $data = $request->validated();
        $data['active'] = $request->boolean('active', true);
        unset($data['outlet_id']);

        $materialIds = $request->input('material_ids', []);

        return DB::transaction(function () use ($supplier, $data, $materialIds, $outletId) {
            if ($outletId !== null && ! empty($materialIds)) {
                $validCount = Material::whereIn('id', $materialIds)
                    ->where('outlet_id', $outletId)
                    ->count();
                if ($validCount !== count($materialIds)) {
                    abort(422, 'Material yang dipilih tidak valid untuk outlet ini.');
                }
            }

            $supplier->update($data);
            $supplier->materials()->sync($materialIds);

            return redirect()->route('gudang.suppliers.show', $supplier)
                ->with('success', 'Perubahan supplier berhasil disimpan.');
        });
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $outletId = $this->getActiveTenantOutletId();

        if ($outletId !== null) {
            abort_unless($supplier->outlet_id !== null && (int) $supplier->outlet_id === $outletId, 404);
        }

        if ($supplier->purchases()->exists()) {
            return redirect()->route('gudang.suppliers.index')
                ->with('error', 'Supplier tidak dapat dihapus karena memiliki riwayat pembelian. Anda dapat menonaktifkannya.');
        }

        $supplier->delete();

        return redirect()->route('gudang.suppliers.index')
            ->with('success', 'Supplier berhasil dihapus.');
    }

    public function toggleActive(Supplier $supplier): RedirectResponse
    {
        $outletId = $this->getActiveTenantOutletId();

        if ($outletId !== null) {
            abort_unless($supplier->outlet_id !== null && (int) $supplier->outlet_id === $outletId, 404);
        }

        $supplier->update(['active' => ! $supplier->active]);

        $message = $supplier->active
            ? 'Supplier berhasil diaktifkan.'
            : 'Supplier berhasil dinonaktifkan.';

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
            return app(TenantContext::class)->currentOutletId();
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage() ?: 'Akses ditolak: Pengguna tidak memiliki akses tenant yang sah.');
        }
    }
}
