<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBomItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user || $user->role !== UserRole::ADMIN || $user->status !== \App\Enums\UserStatus::ACTIVE) {
            return false;
        }

        if (config('nayara.tenant_isolation_enabled', false)) {
            $variant = $this->route('variant');
            if ($variant instanceof \App\Models\Master\ProductVariant) {
                try {
                    $outletId = app(\App\Services\System\TenantContext::class)->currentOutletId();
                    if ($variant->outlet_id === null || (int) $variant->outlet_id !== (int) $outletId) {
                        abort(404);
                    }
                } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
                    abort(403, $e->getMessage() ?: 'Akses ditolak: Pengguna tidak memiliki akses tenant yang sah.');
                }
            }
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'qty_per_unit' => ['required', 'numeric', 'gt:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'qty_per_unit.required' => 'Kuantitas per unit wajib diisi.',
            'qty_per_unit.numeric'  => 'Kuantitas per unit harus berupa angka.',
            'qty_per_unit.gt'       => 'Kuantitas per unit harus lebih besar dari 0.',
        ];
    }
}
