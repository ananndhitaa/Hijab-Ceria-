<?php

namespace App\Http\Requests\Admin;

use App\Enums\ExpenseType;
use App\Enums\UserRole;
use App\Services\System\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
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

    protected function prepareForValidation(): void
    {
        $orderId = $this->input('order_id');
        if ($orderId === '' || $orderId === '0' || $orderId === 0) {
            $orderId = null;
        }

        $campaignName = $this->input('campaign_name');
        if ($campaignName === '') {
            $campaignName = null;
        }

        // If expense_type is not MARKETING, normalize campaign_name to NULL
        $expenseType = $this->input('expense_type');
        if ($expenseType !== ExpenseType::MARKETING->value && $expenseType !== ExpenseType::MARKETING) {
            $campaignName = null;
        }

        $this->merge([
            'order_id'      => $orderId,
            'campaign_name' => $campaignName,
        ]);
    }

    public function rules(): array
    {
        $orderRule = ['nullable', 'integer'];
        if (config('nayara.tenant_isolation_enabled', false)) {
            try {
                $outletId = app(TenantContext::class)->currentOutletId();
                $orderRule[] = Rule::exists('orders', 'id')->where(function ($q) use ($outletId) {
                    $q->where('outlet_id', $outletId);
                });
            } catch (\Exception) {
                $orderRule[] = 'prohibited';
            }
        } else {
            $orderRule[] = 'exists:orders,id';
        }

        return [
            'order_id'      => $orderRule,
            'expense_date'  => ['required', 'date'],
            'expense_type'  => ['required', Rule::enum(ExpenseType::class)],
            'campaign_name' => ['nullable', 'string', 'max:255'],
            'description'   => ['required', 'string', 'max:1000'],
            'amount'        => ['required', 'numeric', 'gt:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.exists'       => 'Pesanan yang dipilih tidak valid atau tidak terdaftar pada outlet Anda.',
            'expense_date.required' => 'Tanggal pengeluaran wajib diisi.',
            'expense_date.date'     => 'Format tanggal pengeluaran tidak valid.',
            'expense_type.required' => 'Jenis pengeluaran wajib dipilih.',
            'expense_type.enum'     => 'Jenis pengeluaran yang dipilih tidak valid.',
            'description.required'  => 'Deskripsi pengeluaran wajib diisi.',
            'description.max'       => 'Deskripsi pengeluaran maksimal 1000 karakter.',
            'campaign_name.max'     => 'Nama kampanye maksimal 255 karakter.',
            'amount.required'       => 'Nominal pengeluaran wajib diisi.',
            'amount.numeric'        => 'Nominal pengeluaran harus berupa angka.',
            'amount.gt'             => 'Nominal pengeluaran harus lebih besar dari 0.',
            'order_id.exists'       => 'Pesanan yang dipilih tidak valid atau tidak ditemukan.',
        ];
    }
}
