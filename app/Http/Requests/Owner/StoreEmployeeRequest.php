<?php

namespace App\Http\Requests\Owner;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        // Fail-closed: Must be authenticated OWNER bound to a legitimate outlet
        return $user && $user->role === UserRole::OWNER && ! empty($user->outlet_id);
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role'     => [
                'required',
                Rule::in([
                    UserRole::ADMIN->value,
                    UserRole::GUDANG->value,
                    UserRole::PRODUKSI->value,
                ]),
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'status'   => [
                'required',
                Rule::in([
                    UserStatus::ACTIVE->value,
                    UserStatus::INACTIVE->value,
                ]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'     => 'Nama karyawan wajib diisi.',
            'name.max'          => 'Nama karyawan maksimal 255 karakter.',
            'email.required'    => 'Email / username wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'email.unique'      => 'Email ini sudah terdaftar pada sistem.',
            'role.required'     => 'Role karyawan wajib dipilih.',
            'role.in'           => 'Role yang diizinkan hanya Admin, Gudang, atau Produksi.',
            'password.required' => 'Password awal wajib diisi.',
            'password.min'      => 'Password minimal terdiri dari 8 karakter.',
            'password.confirmed'=> 'Konfirmasi password tidak cocok.',
            'status.required'   => 'Status aktif karyawan wajib dipilih.',
            'status.in'         => 'Status karyawan tidak valid.',
        ];
    }
}
