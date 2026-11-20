<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Services\System\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user() || $this->user()->role !== UserRole::ADMIN) {
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
            'customer_id' => [
                'required',
                Rule::exists('customers', 'id')->when($isolationEnabled && $outletId !== null, function ($rule) use ($outletId) {
                    $rule->where('outlet_id', $outletId);
                }),
            ],
            'order_date'  => ['required', 'date'],
            'notes'       => ['nullable', 'string', 'max:1000'],
            'items'       => ['required', 'array', 'min:1'],
            'items.*.variant_id' => [
                'required',
                'distinct',
                Rule::exists('product_variants', 'id')->where(function ($query) use ($isolationEnabled, $outletId) {
                    $query->whereIn('product_id', Product::where('active', true)->select('id'));
                    if ($isolationEnabled && $outletId !== null) {
                        $query->where('outlet_id', $outletId)
                            ->whereIn('product_id', Product::where('active', true)->where('outlet_id', $outletId)->select('id'));
                    }
                }),
            ],
            'items.*.qty_ordered' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_selling_price' => ['nullable', 'numeric', 'gt:0'],
            'dp_amount'      => ['nullable', 'numeric', 'gt:0'],
            'payment_date'   => ['nullable', 'required_with:dp_amount', 'date'],
            'payment_method' => ['nullable', 'required_with:dp_amount', 'string'],
            'payment_notes'  => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (! is_array($this->items)) {
                return;
            }

            // Aturan Konveksi: Kunci minimal pemesanan 10 pcs per pesanan
            $totalQty = collect($this->items)->sum(function ($item) {
                return is_numeric($item['qty_ordered'] ?? null) ? (float) $item['qty_ordered'] : 0;
            });

            if ($totalQty < 10) {
                $validator->errors()->add(
                    'items',
                    'Minimal pemesanan konveksi adalah 10 pcs. Total kuantitas pesanan saat ini baru ' . $totalQty . ' pcs.'
                );
            }

            $isolationEnabled = config('nayara.tenant_isolation_enabled', false);
            $outletId = $isolationEnabled ? app(TenantContext::class)->currentOutletId() : null;

            if ($isolationEnabled && $outletId !== null) {
                if ($this->filled('customer_id')) {
                    $customer = \App\Models\Master\Customer::find($this->customer_id);
                    if (! $customer || (int) $customer->outlet_id !== (int) $outletId) {
                        $validator->errors()->add('customer_id', 'Customer yang dipilih tidak valid.');
                    }
                }
            }

            $variantIds = collect($this->items)->pluck('variant_id')->filter()->all();
            $variants = ProductVariant::with('product')->whereIn('id', $variantIds)->get()->keyBy('id');
            $costService = app(\App\Services\Finance\VariantCostCalculationService::class);

            foreach ($this->items as $index => $item) {
                if (empty($item['variant_id'])) {
                    continue;
                }

                $variant = $variants->get($item['variant_id']);
                if (! $variant) {
                    continue;
                }

                if ($isolationEnabled && $outletId !== null) {
                    if ((int) $variant->outlet_id !== (int) $outletId || (int) $variant->product?->outlet_id !== (int) $outletId) {
                        $validator->errors()->add(
                            "items.{$index}.variant_id",
                            'Varian produk tidak ditemukan atau produk induk tidak aktif.'
                        );
                        continue;
                    }
                }

                $sellingPrice = isset($item['unit_selling_price']) && $item['unit_selling_price'] !== '' && $item['unit_selling_price'] !== null
                    ? (string) $item['unit_selling_price']
                    : ($variant->base_selling_price !== null ? (string) $variant->base_selling_price : null);

                if ($sellingPrice === null || bccomp($sellingPrice, '0.00', 2) <= 0) {
                    $validator->errors()->add(
                        "items.{$index}.variant_id",
                        'Harga jual varian belum ditetapkan. Atur harga pada Master Produk terlebih dahulu.'
                    );
                    continue;
                }

                // Reference HPP check: Must be available before order can be created
                $ref = $costService->getReferenceCost($variant);
                if (! $ref['has_reference'] || $ref['reference_cost'] === null) {
                    $validator->errors()->add(
                        "items.{$index}.variant_id",
                        'HPP / Modal Acuan variant belum tersedia. Lengkapi BOM dan modal bahan terlebih dahulu sebelum produk dijual.'
                    );
                    continue;
                }

                // Selling price must be strictly greater than Reference HPP
                if (bccomp($sellingPrice, (string) $ref['reference_cost'], 2) <= 0) {
                    $validator->errors()->add(
                        "items.{$index}.variant_id",
                        'Harga jual produk tidak boleh sama dengan atau lebih rendah dari HPP / Modal.'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'Customer wajib dipilih.',
            'customer_id.exists'   => 'Customer yang dipilih tidak valid.',
            'order_date.required'  => 'Tanggal order wajib diisi.',
            'order_date.date'      => 'Format tanggal order tidak valid.',
            'notes.max'            => 'Catatan maksimal 1000 karakter.',
            'items.required'       => 'Minimal satu item produk harus ditambahkan.',
            'items.array'          => 'Format item pesanan tidak valid.',
            'items.min'            => 'Minimal satu item produk harus ditambahkan.',
            'items.*.variant_id.required' => 'Varian produk wajib dipilih.',
            'items.*.variant_id.exists'   => 'Varian produk tidak ditemukan atau produk induk tidak aktif.',
            'items.*.variant_id.distinct' => 'Terdapat varian produk yang duplikat dalam daftar pesanan.',
            'items.*.qty_ordered.required' => 'Jumlah pesanan wajib diisi.',
            'items.*.qty_ordered.numeric'  => 'Jumlah pesanan harus berupa angka.',
            'items.*.qty_ordered.gt'       => 'Jumlah pesanan harus lebih besar dari 0.',
        ];
    }
}
