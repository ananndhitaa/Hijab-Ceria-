<?php

namespace App\Http\Requests\Gudang;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === \App\Enums\UserRole::GUDANG;
    }

    public function rules(): array
    {
        $unitId = $this->route('unit') instanceof \App\Models\Master\Unit
            ? $this->route('unit')->id
            : $this->route('unit');

        return [
            'unit_name' => ['required', 'string', 'max:255'],
            'symbol'    => ['required', 'string', 'max:20', Rule::unique('units', 'symbol')->ignore($unitId)],
        ];
    }

    public function messages(): array
    {
        return [
            'unit_name.required' => 'Nama satuan wajib diisi.',
            'unit_name.max'      => 'Nama satuan maksimal 255 karakter.',
            'symbol.required'    => 'Simbol/kode satuan wajib diisi.',
            'symbol.max'         => 'Simbol/kode satuan maksimal 20 karakter.',
            'symbol.unique'      => 'Simbol/kode satuan sudah terdaftar.',
        ];
    }
}
