<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Master\Product;
use App\Services\System\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $outletId = $this->getActiveTenantOutletId();
        $search = $request->input('search');

        $products = Product::withCount(['variants' => function ($q) use ($outletId) {
                if ($outletId !== null) {
                    $q->where('outlet_id', $outletId);
                }
            }])
            ->when($outletId !== null, function ($query) use ($outletId) {
                $query->where('outlet_id', $outletId);
            })
            ->when($search, function ($query, $search) {
                $query->where('product_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.products.index', compact('products', 'search'));
    }

    public function create(): View
    {
        return view('admin.products.create');
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $outletId = $this->getActiveTenantOutletId();
        $validated = $request->validated();
        $validated['active'] = $request->has('active') ? (bool) $request->input('active') : true;

        if ($request->filled('price')) {
            $validated['public_price'] = $request->input('price');
        }

        if ($outletId !== null) {
            $validated['outlet_id'] = $outletId;
        }

        $product = Product::create($validated);

        return redirect()->route('admin.products.show', $product)
            ->with('success', 'Produk berhasil dibuat. Silakan tambahkan varian produk.');
    }

    public function show(Product $product, \App\Services\Finance\VariantCostCalculationService $costService): View
    {
        $outletId = $this->getActiveTenantOutletId();
        if ($outletId !== null) {
            abort_unless($product->outlet_id !== null && (int) $product->outlet_id === $outletId, 404);
        }

        $product->load(['variants' => function ($q) use ($outletId) {
            if ($outletId !== null) {
                $q->where('outlet_id', $outletId);
            }
            $q->with(['bomItems.material.stock', 'productionBatches' => function ($bq) {
                $bq->where('status', \App\Enums\BatchStatus::SELESAI)->orderByLatestCompletion();
            }])->withCount('bomItems');
        }]);

        foreach ($product->variants as $variant) {
            $variant->estimated_cost_data = $costService->calculateEstimatedCost($variant);
            $variant->latest_actual_hpp_data = $costService->getLatestActualHpp($variant);
            $variant->reference_cost_data = $costService->getReferenceCost($variant);
            $variant->margin_eval = $variant->base_selling_price !== null
                ? $costService->evaluatePriceMargin($variant, (string) $variant->base_selling_price)
                : null;
        }

        return view('admin.products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        $outletId = $this->getActiveTenantOutletId();
        if ($outletId !== null) {
            abort_unless($product->outlet_id !== null && (int) $product->outlet_id === $outletId, 404);
        }

        return view('admin.products.edit', compact('product'));
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $outletId = $this->getActiveTenantOutletId();
        if ($outletId !== null) {
            abort_unless($product->outlet_id !== null && (int) $product->outlet_id === $outletId, 404);
        }

        $validated = $request->validated();
        unset($validated['code']);         // Code is immutable once assigned
        unset($validated['product_code']);
        unset($validated['outlet_id']);

        $validated['active'] = $request->has('active') ? (bool) $request->input('active') : false;

        if ($request->filled('price')) {
            $validated['public_price'] = $request->input('price');
        }

        $product->update($validated);
        $product->syncEffectivePrice();

        return redirect()->route('admin.products.show', $product)
            ->with('success', 'Data produk berhasil diperbarui.');
    }

    public function toggleActive(Product $product): RedirectResponse
    {
        $outletId = $this->getActiveTenantOutletId();
        if ($outletId !== null) {
            abort_unless($product->outlet_id !== null && (int) $product->outlet_id === $outletId, 404);
        }

        $product->update(['active' => !$product->active]);

        $statusText = $product->active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->back()
            ->with('success', "Produk \"{$product->product_name}\" berhasil {$statusText}.");
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
