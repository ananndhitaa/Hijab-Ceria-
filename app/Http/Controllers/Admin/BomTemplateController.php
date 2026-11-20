<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Master\BomTemplate;
use App\Models\Master\BomTemplateItem;
use App\Models\Master\Material;
use App\Models\Master\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BomTemplateController extends Controller
{
    public function index(Request $request): View
    {
        $search    = $request->input('search');
        $productId = $request->input('product_id');

        $templates = BomTemplate::with(['product'])
            ->withCount('items')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($productId, function ($query, $productId) {
                $query->where('product_id', $productId);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $products = Product::where('active', true)->orderBy('product_name')->get();

        return view('admin.bom-templates.index', compact('templates', 'products', 'search', 'productId'));
    }

    public function create(Request $request): View
    {
        $products = Product::where('active', true)->orderBy('product_name')->get();
        $selectedProductId = $request->input('product_id');

        return view('admin.bom-templates.create', compact('products', 'selectedProductId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id'  => ['required', 'exists:products,id'],
            'name'        => [
                'required',
                'string',
                'max:255',
                Rule::unique('bom_templates', 'name')->where('product_id', $request->input('product_id')),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'product_id.required' => 'Produk wajib dipilih.',
            'product_id.exists'   => 'Produk tidak ditemukan.',
            'name.required'       => 'Nama template wajib diisi.',
            'name.unique'         => 'Nama template sudah digunakan untuk produk ini.',
        ]);

        $validated['is_active'] = true;

        $template = BomTemplate::create($validated);

        return redirect()->route('admin.bom-templates.show', $template)
            ->with('success', "Template BOM '{$template->name}' berhasil dibuat. Silakan tambahkan material kebutuhan.");
    }

    public function show(BomTemplate $bomTemplate): View
    {
        $bomTemplate->load(['product', 'items.material.unit']);

        $existingMaterialIds = $bomTemplate->items->pluck('material_id')->all();

        $materials = Material::with('unit')
            ->where('active', true)
            ->whereNotIn('id', $existingMaterialIds)
            ->orderBy('material_name')
            ->get();

        return view('admin.bom-templates.show', compact('bomTemplate', 'materials'));
    }

    public function edit(BomTemplate $bomTemplate): View
    {
        $bomTemplate->load('product');
        return view('admin.bom-templates.edit', compact('bomTemplate'));
    }

    public function update(Request $request, BomTemplate $bomTemplate): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => [
                'required',
                'string',
                'max:255',
                Rule::unique('bom_templates', 'name')
                    ->where('product_id', $bomTemplate->product_id)
                    ->ignore($bomTemplate->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active'   => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Nama template wajib diisi.',
            'name.unique'   => 'Nama template sudah digunakan untuk produk ini.',
        ]);

        $validated['is_active'] = $request->has('is_active') ? (bool) $request->input('is_active') : false;

        $bomTemplate->update($validated);

        return redirect()->route('admin.bom-templates.show', $bomTemplate)
            ->with('success', 'Informasi template BOM berhasil diperbarui.');
    }

    public function toggleActive(BomTemplate $bomTemplate): RedirectResponse
    {
        $bomTemplate->update([
            'is_active' => ! $bomTemplate->is_active,
        ]);

        $statusText = $bomTemplate->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->back()
            ->with('success', "Template BOM '{$bomTemplate->name}' berhasil {$statusText}.");
    }

    public function storeItem(Request $request, BomTemplate $bomTemplate): RedirectResponse
    {
        return DB::transaction(function () use ($request, $bomTemplate) {
            /** @var BomTemplate $lockedTemplate */
            $lockedTemplate = BomTemplate::where('id', $bomTemplate->id)->lockForUpdate()->firstOrFail();

            $validated = $request->validate([
                'material_id'  => [
                    'required',
                    Rule::exists('materials', 'id')->where('active', true),
                    Rule::unique('bom_template_items', 'material_id')->where('bom_template_id', $lockedTemplate->id),
                ],
                'qty_per_unit' => ['required', 'numeric', 'gt:0'],
            ], [
                'material_id.required' => 'Material wajib dipilih.',
                'material_id.exists'   => 'Material tidak ditemukan atau sedang dinonaktifkan.',
                'material_id.unique'   => 'Material ini sudah ada dalam template ini.',
                'qty_per_unit.required'=> 'Kuantitas per unit wajib diisi.',
                'qty_per_unit.numeric' => 'Kuantitas per unit harus berupa angka.',
                'qty_per_unit.gt'      => 'Kuantitas per unit harus lebih besar dari 0.',
            ]);

            $lockedTemplate->items()->create([
                'material_id'  => $validated['material_id'],
                'qty_per_unit' => $validated['qty_per_unit'],
            ]);

            return redirect()->route('admin.bom-templates.show', $lockedTemplate)
                ->with('success', 'Material berhasil ditambahkan ke template BOM.');
        });
    }

    public function updateItem(Request $request, BomTemplate $bomTemplate, BomTemplateItem $item): RedirectResponse
    {
        return DB::transaction(function () use ($request, $bomTemplate, $item) {
            /** @var BomTemplate $lockedTemplate */
            $lockedTemplate = BomTemplate::where('id', $bomTemplate->id)->lockForUpdate()->firstOrFail();

            // Strict relationship scoping: item must belong to locked parent template
            $targetItem = $lockedTemplate->items()->findOrFail($item->id);

            $validated = $request->validate([
                'qty_per_unit' => ['required', 'numeric', 'gt:0'],
            ], [
                'qty_per_unit.required' => 'Kuantitas per unit wajib diisi.',
                'qty_per_unit.numeric'  => 'Kuantitas per unit harus berupa angka.',
                'qty_per_unit.gt'       => 'Kuantitas per unit harus lebih besar dari 0.',
            ]);

            $targetItem->update([
                'qty_per_unit' => $validated['qty_per_unit'],
            ]);

            return redirect()->route('admin.bom-templates.show', $lockedTemplate)
                ->with('success', 'Kuantitas material template berhasil diperbarui.');
        });
    }

    public function destroyItem(BomTemplate $bomTemplate, BomTemplateItem $item): RedirectResponse
    {
        return DB::transaction(function () use ($bomTemplate, $item) {
            /** @var BomTemplate $lockedTemplate */
            $lockedTemplate = BomTemplate::where('id', $bomTemplate->id)->lockForUpdate()->firstOrFail();

            // Strict relationship scoping: item must belong to locked parent template
            $targetItem = $lockedTemplate->items()->findOrFail($item->id);
            $targetItem->delete();

            return redirect()->route('admin.bom-templates.show', $lockedTemplate)
                ->with('success', 'Material berhasil dihapus dari template BOM.');
        });
    }
}
