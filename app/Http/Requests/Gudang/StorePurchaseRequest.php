<?php

namespace App\Http\Requests\Gudang;

use App\Enums\UserRole;
use App\Models\Master\Material;
use App\Models\Master\Supplier;
use App\Models\Production\ProductionBatch;
use App\Services\System\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user() || $this->user()->role !== UserRole::GUDANG) {
            return false;
        }

        if (config('nayara.tenant_isolation_enabled', false)) {
            app(TenantContext::class)->currentOutletId();
        }

        return true;
    }

    public function rules(): array
    {
        $isolationEnabled = config('nayara.tenant_isolation_enabled', false);
        $outletId = null;

        if ($isolationEnabled) {
            $outletId = app(TenantContext::class)->currentOutletId();
        }

        return [
            'supplier_id'          => [
                'required',
                'integer',
                Rule::exists('suppliers', 'id')->where('active', true)->when($isolationEnabled && $outletId !== null, function ($rule) use ($outletId) {
                    $rule->where('outlet_id', $outletId);
                }),
            ],
            'batch_id'             => [
                'nullable',
                'integer',
                Rule::exists('production_batches', 'id'),
            ],
            'purchase_date'        => ['required', 'date'],
            'notes'                => ['nullable', 'string', 'max:1000'],
            'items'                => ['required', 'array', 'min:1'],
            'items.*.material_id'  => [
                'required',
                'integer',
                'distinct',
                Rule::exists('materials', 'id')->where('active', true)->when($isolationEnabled && $outletId !== null, function ($rule) use ($outletId) {
                    $rule->where('outlet_id', $outletId);
                }),
            ],
            'items.*.qty'          => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price'   => ['required', 'numeric', 'gt:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id.required'         => 'Supplier wajib dipilih.',
            'supplier_id.exists'           => 'Supplier yang dipilih tidak valid atau sedang dinonaktifkan.',
            'batch_id.exists'              => 'Batch produksi yang dipilih tidak valid.',
            'purchase_date.required'       => 'Tanggal Purchase Order wajib diisi.',
            'purchase_date.date'           => 'Format tanggal Purchase Order tidak valid.',
            'notes.max'                    => 'Catatan maksimal 1000 karakter.',
            'items.required'               => 'Daftar item material wajib diisi.',
            'items.array'                  => 'Format data item material tidak valid.',
            'items.min'                    => 'Purchase Order harus memiliki minimal 1 item material.',
            'items.*.material_id.required' => 'Material wajib dipilih untuk setiap baris pesanan.',
            'items.*.material_id.exists'   => 'Material yang dipilih tidak valid atau sedang dinonaktifkan.',
            'items.*.material_id.distinct' => 'Material tidak boleh duplikat dalam satu Purchase Order.',
            'items.*.qty.required'         => 'Kuantitas (Qty) wajib diisi.',
            'items.*.qty.numeric'          => 'Kuantitas (Qty) harus berupa angka.',
            'items.*.qty.gt'               => 'Kuantitas (Qty) harus lebih besar dari 0.',
            'items.*.unit_price.required'  => 'Harga beli satuan wajib diisi.',
            'items.*.unit_price.numeric'   => 'Harga beli satuan harus berupa angka.',
            'items.*.unit_price.gt'        => 'Harga beli satuan harus lebih besar dari 0.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $isolationEnabled = config('nayara.tenant_isolation_enabled', false);
            $outletId = null;

            if ($isolationEnabled) {
                try {
                    $outletId = app(TenantContext::class)->currentOutletId();
                } catch (\Throwable) {
                    return;
                }
            }

            $supplierId = $this->input('supplier_id');
            if (! $supplierId) {
                return;
            }

            $supplierQuery = Supplier::with('materials');
            if ($isolationEnabled && $outletId !== null) {
                $supplierQuery->where('outlet_id', $outletId);
            }
            $supplier = $supplierQuery->find($supplierId);
            if (! $supplier) {
                return;
            }

            $batchId = $this->input('batch_id');
            if ($batchId && $isolationEnabled && $outletId !== null) {
                $batch = ProductionBatch::find($batchId);
                if (! $batch || ! $batch->hasConsistentTenantRelations($outletId)) {
                    $validator->errors()->add('batch_id', 'Batch produksi tidak valid atau bukan milik outlet aktif.');
                }
            }

            $validMaterialIds = $supplier->materials->pluck('id')->all();
            $items = $this->input('items', []);

            if (! is_array($items)) {
                return;
            }

            foreach ($items as $index => $item) {
                if (empty($item['material_id'])) {
                    continue;
                }

                $matId = (int) $item['material_id'];
                if (! in_array($matId, $validMaterialIds, true)) {
                    $material = Material::find($matId);
                    $materialName = $material ? $material->material_name : "ID {$matId}";
                    $validator->errors()->add(
                        "items.{$index}.material_id",
                        "Material {$materialName} tidak terdaftar pada supplier {$supplier->supplier_name}."
                    );
                }

                if ($isolationEnabled && $outletId !== null) {
                    $material = Material::find($matId);
                    if ($material && ($material->outlet_id === null || (int) $material->outlet_id !== $outletId)) {
                        $validator->errors()->add(
                            "items.{$index}.material_id",
                            'Material tidak valid atau bukan milik outlet aktif.'
                        );
                    }
                }
            }
        });
    }
}
