<?php

namespace App\Http\Requests\Owner;

use App\Enums\UserRole;
use App\Models\System\User;
use Illuminate\Foundation\Http\FormRequest;

class ResetEmployeePasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        // Fail-closed: Only Owner with legitimate outlet is authorized
        if (! $user || $user->role !== UserRole::OWNER || empty($user->outlet_id)) {
            return false;
        }

        $target = $this->route('employee');
        $targetUser = $target instanceof User ? $target : User::find($target);

        // Target must exist
        if (! $targetUser) {
            return false;
        }

        // Target must be an internal employee (ADMIN, GUDANG, PRODUKSI)
        if (! in_array($targetUser->role, [UserRole::ADMIN, UserRole::GUDANG, UserRole::PRODUKSI], true)) {
            return false;
        }

        // Fail-closed: Must belong to same outlet
        if ((int) $targetUser->outlet_id !== (int) $user->outlet_id) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.required'  => 'Password baru wajib diisi.',
            'password.min'       => 'Password minimal terdiri dari 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ];
    }
}
