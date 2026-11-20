<?php

namespace App\Http\Requests\Platform;

use App\Models\Platform\TenantRegistration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublicTenantRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public free registration
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:150'],
            'owner_name'   => ['required', 'string', 'max:150'],
            'email'        => [
                'required',
                'string',
                'email',
                'max:150',
                'unique:users,email',
                Rule::unique('tenant_registrations', 'email')->where(function ($query) {
                    return $query->whereIn('status', [
                        TenantRegistration::STATUS_PENDING,
                        TenantRegistration::STATUS_NEEDS_REVISION,
                    ]);
                }),
            ],
            'phone_wa'     => ['required', 'string', 'max:50'],
            'address'      => ['nullable', 'string', 'max:1000'],
            'notes'        => ['nullable', 'string', 'max:1000'],
            'password'     => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required' => 'Nama konveksi/perusahaan wajib diisi.',
            'owner_name.required'   => 'Nama pemilik (Owner) wajib diisi.',
            'email.required'        => 'Email bisnis wajib diisi.',
            'email.email'           => 'Format email tidak valid.',
            'email.unique'          => 'Email sudah terdaftar atau memiliki pengajuan aktif yang sedang diproses.',
            'phone_wa.required'     => 'Nomor telepon/WhatsApp wajib diisi.',
            'password.required'     => 'Password wajib diisi.',
            'password.min'          => 'Password minimal harus 8 karakter.',
            'password.confirmed'    => 'Konfirmasi password tidak cocok.',
        ];
    }
}
