<?php

namespace App\Http\Requests\Gudang;

use App\Models\Master\Material;
use App\Models\Master\Supplier;
use App\Services\System\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->user()?->role !== \App\Enums\UserRole::GUDANG) {
            return false;
        }

        if (config('nayara.tenant_isolation_enabled', false)) {
            try {
                $outletId = app(TenantContext::class)->currentOutletId();
            } catch (\Throwable) {
                return false;
            }

            $supplier = $this->route('supplier');
            if ($supplier instanceof Supplier) {
                if ($supplier->outlet_id === null || (int) $supplier->outlet_id !== (int) $outletId) {
                    abort(404, 'Supplier tidak ditemukan.');
                }
            }
        }

        return true;
    }

    public function rules(): array
    {
        $outletId = null;
        if (config('nayara.tenant_isolation_enabled', false)) {
            try {
                $outletId = app(TenantContext::class)->currentOutletId();
            } catch (\Throwable) {
                $outletId = -1;
            }
        }

        $materialRule = ['integer'];
        if ($outletId !== null) {
            $materialRule[] = Rule::exists('materials', 'id')->where('outlet_id', $outletId);
        } else {
            $materialRule[] = 'exists:materials,id';
        }

        return [
            'supplier_name'  => ['required', 'string', 'max:255'],
            'phone'          => ['nullable', 'string', 'max:20'],
            'address'        => ['nullable', 'string'],
            'email'          => ['nullable', 'email', 'max:255'],
            'active'         => ['nullable', 'boolean'],
            'material_ids'   => ['nullable', 'array'],
            'material_ids.*' => $materialRule,
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! config('nayara.tenant_isolation_enabled', false)) {
                return;
            }

            try {
                $outletId = app(TenantContext::class)->currentOutletId();
            } catch (\Throwable) {
                $validator->errors()->add('tenant', 'Tenant context tidak valid.');
                return;
            }

            $materialIds = $this->input('material_ids', []);
            if (! empty($materialIds) && is_array($materialIds)) {
                $invalidCount = Material::whereIn('id', $materialIds)
                    ->where(function ($q) use ($outletId) {
                        $q->whereNull('outlet_id')
                          ->orWhere('outlet_id', '!=', $outletId);
                    })
                    ->count();

                if ($invalidCount > 0) {
                    $validator->errors()->add('material_ids', 'Satu atau lebih material yang dipilih tidak valid untuk outlet ini.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'supplier_name.required' => 'Nama supplier wajib diisi.',
            'supplier_name.max'      => 'Nama supplier maksimal 255 karakter.',
            'phone.max'              => 'Nomor telepon maksimal 20 karakter.',
            'email.email'            => 'Format email tidak valid.',
            'email.max'              => 'Email maksimal 255 karakter.',
            'material_ids.array'     => 'Format data material yang disuplai tidak valid.',
            'material_ids.*.exists'  => 'Material yang dipilih tidak valid dalam master material.',
        ];
    }
}
