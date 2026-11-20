<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\Finance\BusinessIncome;
use App\Services\System\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBusinessIncomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user() || $this->user()->role !== UserRole::ADMIN) {
            return false;
        }

        if (config('nayara.tenant_isolation_enabled', false)) {
            try {
                app(TenantContext::class)->currentOutletId();
            } catch (\Exception) {
                return false;
            }
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'income_date' => ['required', 'date'],
            'category'    => ['required', 'string', Rule::in(BusinessIncome::CATEGORIES)],
            'amount'      => ['required', 'numeric', 'gt:0'],
            'notes'       => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'income_date.required' => 'Tanggal pemasukan wajib diisi.',
            'income_date.date'     => 'Format tanggal pemasukan tidak valid.',
            'category.required'    => 'Kategori pemasukan wajib dipilih.',
            'category.in'          => 'Kategori pemasukan tidak valid.',
            'amount.required'      => 'Nominal pemasukan wajib diisi.',
            'amount.numeric'       => 'Nominal pemasukan harus berupa angka.',
            'amount.gt'            => 'Nominal pemasukan harus lebih besar dari 0.',
            'notes.max'            => 'Keterangan maksimal 1000 karakter.',
        ];
    }
}
