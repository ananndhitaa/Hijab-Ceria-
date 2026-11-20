<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === UserRole::ADMIN;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code') && is_string($this->code)) {
            $trimmed = trim($this->code);
            $this->merge([
                'code' => $trimmed !== '' ? strtoupper($trimmed) : null,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'product_name' => ['required', 'string', 'max:255'],
            'code'         => ['nullable', 'string', 'min:2', 'max:5', 'alpha_num', 'unique:products,code'],
            'description'  => ['nullable', 'string'],
            'active'       => ['nullable', 'boolean'],
            'public_price' => ['nullable', 'numeric', 'min:0'],
            'price'        => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_name.required' => 'Nama produk wajib diisi.',
            'product_name.max'      => 'Nama produk maksimal 255 karakter.',
            'code.unique'           => 'Kode produk sudah digunakan. Gunakan kode lain.',
            'code.min'              => 'Kode produk minimal 2 karakter.',
            'code.max'              => 'Kode produk maksimal 5 karakter.',
            'code.alpha_num'        => 'Kode produk hanya boleh berisi huruf dan angka tanpa spasi.',
        ];
    }
}
