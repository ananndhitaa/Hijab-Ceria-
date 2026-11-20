<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductVariantRequest;
use App\Http\Requests\Admin\UpdateProductVariantRequest;
use App\Models\Master\BomTemplate;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Services\Master\ProductVariantSkuGenerator;
use App\Services\System\TenantContext;
use App\Support\QuantityFormatter;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductVariantController extends Controller
{
    public function store(
        StoreProductVariantRequest $request,
        Product $product,
        ProductVariantSkuGenerator $skuGenerator
    ): RedirectResponse {
        $outletId = $this->getActiveTenantOutletId();
        if ($outletId !== null) {
            if ($product->outlet_id === null || (int) $product->outlet_id !== $outletId) {
                abort(404);
            }
        }

        $validated = $request->validated();
        $requiresPrinting = (bool) $validated['requires_printing'];

        try {
            if (! empty($validated['base_selling_price'])) {
                $tempVariant = new ProductVariant($validated);
                $tempVariant->product_id = $product->id;
                $tempVariant->setRelation('product', $product);
                if ($outletId !== null) {
                    $tempVariant->outlet_id = $outletId;
                }
                $costService = app(\App\Services\Finance\VariantCostCalculationService::class);
                $ref = $costService->getReferenceCost($tempVariant);
                if ($ref['has_reference'] && $ref['reference_cost'] !== null) {
                    if (bccomp((string) $validated['base_selling_price'], (string) $ref['reference_cost'], 2) <= 0) {
                        $formattedHpp = \App\Support\MoneyFormatter::rupiah($ref['reference_cost']);
                        throw new \DomainException("Harga jual harus lebih tinggi dari HPP / Modal Acuan ({$formattedHpp} per pcs).");
                    }
                }
            }

            // Strict ownership: always create variant through parent product relationship with auto-generated SKU
            $variant = DB::transaction(function () use ($product, $validated, $requiresPrinting, $skuGenerator, $request, $outletId) {
                $sku = $skuGenerator->generate($product, $validated['variant_name']);

                $imagePath = null;
                if ($request->hasFile('image')) {
                    $imagePath = $request->file('image')->store('products/variants', 'public');
                }

                $variantAttributes = [
                    'variant_name'                         => $validated['variant_name'],
                    'sku'                                  => $sku,
                    'image_path'                           => $imagePath,
                    'base_selling_price'                   => $validated['base_selling_price'] ?? null,
                    'requires_printing'                    => $requiresPrinting,
                    'printing_cost_per_unit'               => $requiresPrinting ? ($validated['printing_cost_per_unit'] ?? null) : '0.00',
                    'direct_labor_cost_per_unit'           => $validated['direct_labor_cost_per_unit'] ?? null,
                    'packaging_process_cost_per_good_unit' => $validated['packaging_process_cost_per_good_unit'] ?? null,
                    'other_direct_cost_per_unit'           => $validated['other_direct_cost_per_unit'] ?? null,
                    'active'                               => $request->has('active') ? (bool) $request->input('active') : true,
                ];

                if ($outletId !== null) {
                    $variantAttributes['outlet_id'] = $outletId;
                }

                return $product->variants()->create($variantAttributes);
            }, 3);

            return redirect()->route('admin.variants.show', $variant)
                ->with('success', 'Varian produk berhasil ditambahkan.');
        } catch (\DomainException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(ProductVariant $variant, \App\Services\Finance\VariantCostCalculationService $costService): View
    {
        $outletId = $this->getActiveTenantOutletId();
        if ($outletId !== null) {
            $this->authorizeVariantTenant($variant, $outletId);
        }

        $variant->load([
            'product',
            'bomItems.material.unit',
            'bomItems.material.stock',
            'productionBatches' => function ($bq) {
                $bq->where('status', \App\Enums\BatchStatus::SELESAI)->orderByLatestCompletion();
            },
        ]);

        $estimatedCost = $costService->calculateEstimatedCost($variant);
        $latestActualHpp = $costService->getLatestActualHpp($variant);
        $referenceCost = $costService->getReferenceCost($variant);
        $marginEval = $costService->evaluatePriceMargin($variant, $variant->base_selling_price);

        // Active materials for selection in BOM form (tenant-scoped)
        $materialsQuery = Material::with('unit')
            ->where('active', true)
            ->orderBy('material_name');

        if ($outletId !== null) {
            $materialsQuery->where('outlet_id', $outletId);
        }

        $materials = $materialsQuery->get();

        // Product-scoped active BOM templates with non-empty items (tenant-scoped)
        $templatesQuery = BomTemplate::with(['items.material.unit'])
            ->where('product_id', $variant->product_id)
            ->where('is_active', true)
            ->whereHas('items')
            ->orderBy('name');

        if ($outletId !== null) {
            $templatesQuery->where('outlet_id', $outletId);
        }

        $templates = $templatesQuery->get();

        $templatePayload = $templates->map(function ($template) {
            return [
                'id' => $template->id,
                'name' => $template->name,
                'items' => $template->items->map(function ($item) {
                    return [
                        'material_name' => $item->material->material_name ?? 'Material',
                        'unit' => $item->material->unit->unit_name ?? $item->material->unit->symbol ?? '-',
                        'qty_formatted' => QuantityFormatter::format($item->qty_per_unit),
                    ];
                }),
            ];
        });

        return view('admin.variants.show', compact('variant', 'materials', 'templates', 'templatePayload', 'estimatedCost', 'latestActualHpp', 'referenceCost', 'marginEval'));
    }

    public function edit(ProductVariant $variant, \App\Services\Finance\VariantCostCalculationService $costService): View
    {
        $outletId = $this->getActiveTenantOutletId();
        if ($outletId !== null) {
            $this->authorizeVariantTenant($variant, $outletId);
        }

        $variant->load([
            'product',
            'bomItems.material.stock',
            'productionBatches' => function ($bq) {
                $bq->where('status', \App\Enums\BatchStatus::SELESAI)->orderByLatestCompletion();
            },
        ]);

        $referenceCost = $costService->getReferenceCost($variant);

        return view('admin.variants.edit', compact('variant', 'referenceCost'));
    }

    public function update(UpdateProductVariantRequest $request, ProductVariant $variant): RedirectResponse
    {
        $outletId = $this->getActiveTenantOutletId();
        if ($outletId !== null) {
            $this->authorizeVariantTenant($variant, $outletId);
        }

        $validated = $request->validated();
        unset($validated['product_id']); // Prevent changing parent product
        unset($validated['sku']);        // Prevent changing SKU (SKU is immutable)
        unset($validated['outlet_id']);  // Prevent altering tenant ownership

        $requiresPrinting = (bool) $validated['requires_printing'];
        $validated['requires_printing'] = $requiresPrinting;
        $validated['printing_cost_per_unit'] = $requiresPrinting
            ? ($validated['printing_cost_per_unit'] ?? null)
            : '0.00';
        $validated['direct_labor_cost_per_unit'] = $validated['direct_labor_cost_per_unit'] ?? null;
        $validated['packaging_process_cost_per_good_unit'] = $validated['packaging_process_cost_per_good_unit'] ?? null;
        $validated['other_direct_cost_per_unit'] = $validated['other_direct_cost_per_unit'] ?? null;
        $validated['base_selling_price'] = $validated['base_selling_price'] ?? null;
        $validated['active'] = $request->has('active') ? (bool) $request->input('active') : false;

        if ($request->hasFile('image')) {
            $newImage = $request->file('image')->store('products/variants', 'public');
            if ($variant->image_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($variant->image_path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($variant->image_path);
            }
            $validated['image_path'] = $newImage;
        } elseif ($request->boolean('remove_image')) {
            if ($variant->image_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($variant->image_path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($variant->image_path);
            }
            $validated['image_path'] = null;
        }

        try {
            if (! empty($validated['base_selling_price'])) {
                $tempVariant = clone $variant;
                $tempVariant->fill($validated);
                $costService = app(\App\Services\Finance\VariantCostCalculationService::class);
                $ref = $costService->getReferenceCost($tempVariant);
                if ($ref['has_reference'] && $ref['reference_cost'] !== null) {
                    if (bccomp((string) $validated['base_selling_price'], (string) $ref['reference_cost'], 2) <= 0) {
                        $formattedHpp = \App\Support\MoneyFormatter::rupiah($ref['reference_cost']);
                        throw new \DomainException("Harga jual harus lebih tinggi dari HPP / Modal Acuan ({$formattedHpp} per pcs).");
                    }
                }
            }

            $variant->update($validated);

            return redirect()->route('admin.variants.show', $variant)
                ->with('success', 'Data varian berhasil diperbarui.');
        } catch (\DomainException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
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

    /**
     * Enforce strict tenant boundary for a product variant.
     * Fails closed (404) if variant or parent product does not match authenticated outlet.
     */
    protected function authorizeVariantTenant(ProductVariant $variant, int $outletId): void
    {
        if (
            $variant->outlet_id === null ||
            (int) $variant->outlet_id !== $outletId ||
            ! $variant->product ||
            $variant->product->outlet_id === null ||
            (int) $variant->product->outlet_id !== $outletId
        ) {
            abort(404);
        }
    }
}
