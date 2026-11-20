<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === UserRole::ADMIN;
    }

    public function rules(): array
    {
        return [
            'variant_name' => ['required', 'string', 'max:255'],
            'base_selling_price' => ['nullable', 'numeric', 'min:0'],
            'requires_printing' => ['required', 'boolean'],
            'printing_cost_per_unit' => ['nullable', 'numeric', 'min:0'],
            'direct_labor_cost_per_unit' => ['nullable', 'numeric', 'min:0'],
            'packaging_process_cost_per_good_unit' => ['nullable', 'numeric', 'min:0'],
            'other_direct_cost_per_unit' => ['nullable', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'variant_name.required' => 'Nama varian wajib diisi.',
            'base_selling_price.numeric' => 'Harga jual master harus berupa angka.',
            'base_selling_price.min' => 'Harga jual master minimal 0.',
            'requires_printing.required' => 'Kebutuhan printing wajib dipilih.',
            'printing_cost_per_unit.numeric' => 'Biaya printing standar harus berupa angka.',
            'printing_cost_per_unit.min' => 'Biaya printing standar minimal 0.',
            'direct_labor_cost_per_unit.numeric' => 'Biaya tenaga kerja standar harus berupa angka.',
            'direct_labor_cost_per_unit.min' => 'Biaya tenaga kerja standar minimal 0.',
            'packaging_process_cost_per_good_unit.numeric' => 'Biaya packing standar harus berupa angka.',
            'packaging_process_cost_per_good_unit.min' => 'Biaya packing standar minimal 0.',
            'other_direct_cost_per_unit.numeric' => 'Biaya produksi tambahan harus berupa angka.',
            'other_direct_cost_per_unit.min' => 'Biaya produksi tambahan minimal 0.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (! $this->filled('base_selling_price')) {
                return;
            }

            $product = $this->route('product');
            if (! $product) {
                return;
            }

            $tempVariant = new \App\Models\Master\ProductVariant([
                'product_id'                           => $product->id,
                'variant_name'                         => $this->input('variant_name', 'Temp'),
                'requires_printing'                    => (bool) $this->input('requires_printing'),
                'printing_cost_per_unit'               => (bool) $this->input('requires_printing') ? $this->input('printing_cost_per_unit') : '0.00',
                'direct_labor_cost_per_unit'           => $this->input('direct_labor_cost_per_unit'),
                'packaging_process_cost_per_good_unit' => $this->input('packaging_process_cost_per_good_unit'),
                'other_direct_cost_per_unit'           => $this->input('other_direct_cost_per_unit'),
            ]);

            $costService = app(\App\Services\Finance\VariantCostCalculationService::class);
            $ref = $costService->getReferenceCost($tempVariant);

            if ($ref['has_reference'] && $ref['reference_cost'] !== null) {
                $price = (string) $this->input('base_selling_price');
                if (bccomp($price, (string) $ref['reference_cost'], 2) <= 0) {
                    $formattedHpp = \App\Support\MoneyFormatter::rupiah($ref['reference_cost']);
                    $validator->errors()->add(
                        'base_selling_price',
                        "Harga jual harus lebih tinggi dari HPP / Modal Acuan ({$formattedHpp} per pcs)."
                    );
                }
            }
        });
    }
}
