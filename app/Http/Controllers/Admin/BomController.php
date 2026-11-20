<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBomItemRequest;
use App\Http\Requests\Admin\UpdateBomItemRequest;
use App\Models\Master\BomItem;
use App\Models\Master\BomTemplate;
use App\Models\Master\ProductVariant;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BomController extends Controller
{
    public function store(StoreBomItemRequest $request, ProductVariant $variant): RedirectResponse
    {
        $outletId = $this->getActiveTenantOutletId();
        if ($outletId !== null) {
            abort_unless($variant->outlet_id !== null && (int) $variant->outlet_id === $outletId, 404);

            $material = \App\Models\Master\Material::find($request->input('material_id'));
            if (! $material || $material->outlet_id === null || (int) $material->outlet_id !== $outletId) {
                abort(422, 'Material yang dipilih tidak valid untuk outlet ini.');
            }
        }

        try {
            $variant->bomItems()->create([
                'material_id'  => $request->input('material_id'),
                'qty_per_unit' => $request->input('qty_per_unit'),
            ]);

            return redirect()->route('admin.variants.show', $variant)
                ->with('success', 'Material berhasil ditambahkan ke BOM.');
        } catch (QueryException $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['material_id' => 'Material ini sudah terdaftar dalam BOM varian ini.']);
        }
    }

    public function update(UpdateBomItemRequest $request, ProductVariant $variant, BomItem $bomItem): RedirectResponse
    {
        $outletId = $this->getActiveTenantOutletId();
        if ($outletId !== null) {
            abort_unless($variant->outlet_id !== null && (int) $variant->outlet_id === $outletId, 404);
        }

        // Enforce strict relationship membership (scoped binding)
        $targetBomItem = $variant->bomItems()->findOrFail($bomItem->id);

        $targetBomItem->update([
            'qty_per_unit' => $request->input('qty_per_unit'),
        ]);

        return redirect()->route('admin.variants.show', $variant)
            ->with('success', 'Kuantitas material BOM berhasil diperbarui.');
    }

    public function destroy(ProductVariant $variant, BomItem $bomItem): RedirectResponse
    {
        $outletId = $this->getActiveTenantOutletId();
        if ($outletId !== null) {
            abort_unless($variant->outlet_id !== null && (int) $variant->outlet_id === $outletId, 404);
        }

        // Enforce strict relationship membership (scoped binding)
        $targetBomItem = $variant->bomItems()->findOrFail($bomItem->id);

        $targetBomItem->delete();

        return redirect()->route('admin.variants.show', $variant)
            ->with('success', 'Material berhasil dihapus dari BOM.');
    }

    public function applyTemplate(Request $request, ProductVariant $variant): RedirectResponse
    {
        $outletId = $this->getActiveTenantOutletId();
        if ($outletId !== null) {
            abort_unless($variant->outlet_id !== null && (int) $variant->outlet_id === $outletId, 404);
        }

        $validated = $request->validate([
            'bom_template_id'  => ['required', 'exists:bom_templates,id'],
            'replace_existing' => ['nullable', 'boolean'],
        ], [
            'bom_template_id.required' => 'Pilih template BOM yang akan diterapkan.',
            'bom_template_id.exists'   => 'Template BOM tidak ditemukan.',
        ]);

        return DB::transaction(function () use ($validated, $variant, $outletId) {
            // 1. Lock target variant
            /** @var ProductVariant $lockedVariant */
            $lockedVariant = ProductVariant::where('id', $variant->id)->lockForUpdate()->firstOrFail();

            // 2. Lock selected BomTemplate
            /** @var BomTemplate|null $lockedTemplate */
            $lockedTemplate = BomTemplate::where('id', $validated['bom_template_id'])->lockForUpdate()->first();

            if (! $lockedTemplate) {
                throw ValidationException::withMessages([
                    'bom_template_id' => 'Template BOM tidak ditemukan.',
                ]);
            }

            // 2b. Tenant isolation check on template
            if ($outletId !== null) {
                if ($lockedTemplate->outlet_id === null || (int) $lockedTemplate->outlet_id !== $outletId) {
                    throw ValidationException::withMessages([
                        'bom_template_id' => 'Template BOM tidak valid untuk outlet ini.',
                    ]);
                }
            }

            // 3. Verify template is active and belongs to SAME product
            if (! $lockedTemplate->is_active) {
                throw ValidationException::withMessages([
                    'bom_template_id' => 'Template BOM sedang tidak aktif.',
                ]);
            }

            if ((int) $lockedTemplate->product_id !== (int) $lockedVariant->product_id) {
                throw ValidationException::withMessages([
                    'bom_template_id' => 'Template BOM tidak sesuai dengan produk varian ini.',
                ]);
            }

            // 4. Read template items consistently
            $templateItems = $lockedTemplate->items()->with('material')->get();

            // 5. Verify template is not empty
            if ($templateItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'bom_template_id' => 'Template BOM ini belum memiliki daftar material kebutuhan.',
                ]);
            }

            // 6. Verify every referenced material still exists, is active, and belongs to tenant
            foreach ($templateItems as $item) {
                if (! $item->material || ! $item->material->active) {
                    throw ValidationException::withMessages([
                        'bom_template_id' => "Template memuat material nonaktif ({$item->material?->material_name}). Perbarui template terlebih dahulu.",
                    ]);
                }

                if ($outletId !== null) {
                    if ($item->material->outlet_id === null || (int) $item->material->outlet_id !== $outletId) {
                        throw ValidationException::withMessages([
                            'bom_template_id' => "Template memuat material milik outlet lain ({$item->material->material_name}).",
                        ]);
                    }
                }
            }

            // 7. Check existing variant BOM
            $existingCount = $lockedVariant->bomItems()->count();
            $shouldReplace = (bool) ($validated['replace_existing'] ?? false);

            if ($existingCount > 0 && ! $shouldReplace) {
                throw ValidationException::withMessages([
                    'replace_existing' => 'Varian sudah memiliki resep BOM. Centang konfirmasi penggantian untuk menimpa BOM saat ini.',
                ]);
            }

            // 8. If explicitly replacing: delete current variant BOM inside same transaction
            if ($existingCount > 0 && $shouldReplace) {
                $lockedVariant->bomItems()->delete();
            }

            // 9. Copy template items into concrete variant bom_items using Eloquent relationship
            foreach ($templateItems as $item) {
                $lockedVariant->bomItems()->create([
                    'material_id'  => $item->material_id,
                    'qty_per_unit' => $item->qty_per_unit,
                ]);
            }

            return redirect()->route('admin.variants.show', $lockedVariant)
                ->with('success', "Resep dari template '{$lockedTemplate->name}' berhasil diterapkan ke varian ini ({$templateItems->count()} material).");
        });
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
