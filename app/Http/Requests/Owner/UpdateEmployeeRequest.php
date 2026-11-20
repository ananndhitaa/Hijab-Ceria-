<?php

namespace App\Http\Requests\Owner;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\System\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user || $user->role !== UserRole::OWNER || empty($user->outlet_id)) {
            return false;
        }

        $target = $this->route('employee');
        $targetUser = $target instanceof User ? $target : User::find($target);

        // Disallow editing if employee not found or target is not in allowed roles
        if (! $targetUser || ! in_array($targetUser->role, [UserRole::ADMIN, UserRole::GUDANG, UserRole::PRODUKSI], true)) {
            return false;
        }

        // Fail-closed: Must belong to the exact same outlet as the authenticated Owner
        if ((int) $targetUser->outlet_id !== (int) $user->outlet_id) {
            return false;
        }

        return true;
    }

    public function rules(): array
    {
        $target = $this->route('employee');
        $targetId = $target instanceof User ? $target->id : $target;

        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($targetId),
            ],
            'role'     => [
                'required',
                Rule::in([
                    UserRole::ADMIN->value,
                    UserRole::GUDANG->value,
                    UserRole::PRODUKSI->value,
                ]),
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
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
            'email.unique'      => 'Email ini sudah terdaftar pada akun lain.',
            'role.required'     => 'Role karyawan wajib dipilih.',
            'role.in'           => 'Role yang diizinkan hanya Admin, Gudang, atau Produksi.',
            'password.min'      => 'Password minimal terdiri dari 8 karakter.',
            'password.confirmed'=> 'Konfirmasi password tidak cocok.',
            'status.required'   => 'Status aktif karyawan wajib dipilih.',
            'status.in'         => 'Status karyawan tidak valid.',
        ];
    }
}
