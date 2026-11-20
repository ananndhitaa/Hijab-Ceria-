<?php

namespace App\Http\Requests\Gudang;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user
            && $user->role === \App\Enums\UserRole::GUDANG
            && $user->status === \App\Enums\UserStatus::ACTIVE;
    }

    public function rules(): array
    {
        return [
            'unit_id'       => ['required', 'exists:units,id'],
            'material_name' => ['required', 'string', 'max:255'],
            'min_stock'     => ['required', 'numeric', 'min:0'],
            'active'        => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'unit_id.required'       => 'Satuan material wajib dipilih.',
            'unit_id.exists'         => 'Satuan yang dipilih tidak valid.',
            'material_name.required' => 'Nama material wajib diisi.',
            'material_name.max'      => 'Nama material maksimal 255 karakter.',
            'min_stock.required'     => 'Stok minimum wajib diisi.',
            'min_stock.numeric'      => 'Stok minimum harus berupa angka.',
            'min_stock.min'          => 'Stok minimum tidak boleh negatif.',
        ];
    }
}
