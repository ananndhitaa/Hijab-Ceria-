<?php

namespace App\Http\Requests\Gudang;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Services\System\TenantContext;
use Illuminate\Foundation\Http\FormRequest;

class StockOpnameRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user || $user->role !== UserRole::GUDANG) {
            return false;
        }

        if (config('nayara.tenant_isolation_enabled', false)) {
            if ($user->status !== UserStatus::ACTIVE) {
                return false;
            }

            try {
                app(TenantContext::class)->currentOutletId();
            } catch (\Exception) {
                return false;
            }
        }

        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('notes')) {
            $trimmed = trim((string) $this->notes);
            $this->merge([
                'notes' => $trimmed === '' ? null : $trimmed,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'physical_qty' => ['required', 'numeric', 'min:0', 'max:99999999999999.9999'],
            'notes'        => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'physical_qty.required' => 'Kuantitas fisik stok hasil hitung wajib diisi.',
            'physical_qty.numeric'  => 'Kuantitas fisik stok harus berupa angka yang valid.',
            'physical_qty.min'      => 'Kuantitas fisik stok tidak boleh bernilai negatif.',
            'physical_qty.max'      => 'Kuantitas fisik stok melebihi batas maksimum sistem.',
            'notes.required'        => 'Catatan atau alasan penyesuaian stok wajib diisi.',
            'notes.string'          => 'Catatan atau alasan penyesuaian stok harus berupa teks.',
            'notes.max'             => 'Catatan atau alasan penyesuaian stok maksimal 500 karakter.',
        ];
    }
}
