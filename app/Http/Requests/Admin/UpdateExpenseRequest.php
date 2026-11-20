<?php

namespace App\Http\Requests\Admin;

use App\Enums\ExpenseType;
use App\Enums\UserRole;
use App\Models\Finance\BusinessExpense;
use App\Services\System\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user() || $this->user()->role !== UserRole::ADMIN) {
            return false;
        }

        if (config('nayara.tenant_isolation_enabled', false)) {
            try {
                $outletId = app(TenantContext::class)->currentOutletId();
            } catch (\Exception) {
                return false;
            }

            $expense = $this->route('expense');
            if ($expense instanceof BusinessExpense) {
                if ($expense->outlet_id === null || (int) $expense->outlet_id !== $outletId) {
                    abort(404);
                }
                if ($expense->order_id !== null && $expense->order && (int) $expense->order->outlet_id !== $outletId) {
                    abort(404);
                }
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

        $rules = [
            'order_id'      => $orderRule,
            'expense_date'  => ['required', 'date'],
            'expense_type'  => ['required', Rule::enum(ExpenseType::class)],
            'campaign_name' => ['nullable', 'string', 'max:255'],
            'description'   => ['required', 'string', 'max:1000'],
            'amount'        => ['required', 'numeric', 'gt:0'],
        ];

        if (config('nayara.tenant_isolation_enabled', false)) {
            $rules['amendment_reason'] = ['required', 'string', 'min:10', 'max:500'];
        } else {
            $rules['amendment_reason'] = ['nullable', 'string', 'max:500'];
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! config('nayara.tenant_isolation_enabled', false)) {
                return;
            }

            try {
                $outletId = app(TenantContext::class)->currentOutletId();
            } catch (\Exception) {
                return;
            }

            $expense = $this->route('expense');
            if (! $expense instanceof BusinessExpense) {
                return;
            }

            // Resolve tenant opening cash balance
            $opening = \App\Models\Finance\OpeningCashBalance::forOutlet($outletId)
                ->latest('opening_date')
                ->latest('id')
                ->first();

            if (! $opening) {
                $validator->errors()->add(
                    'expense_date',
                    'Saldo awal kas tenant belum ditentukan. Koreksi tanggal pengeluaran diblokir demi integritas cut-off.'
                );
                return;
            }

            $newDateStr = $this->input('expense_date');
            if (! $newDateStr || ! strtotime($newDateStr)) {
                return;
            }

            $openingDate = $opening->opening_date->format('Y-m-d');
            $oldDate = $expense->expense_date->format('Y-m-d');
            $newDate = \Carbon\Carbon::parse($newDateStr)->format('Y-m-d');

            $oldIsPostCutoff = strcmp($oldDate, $openingDate) >= 0;
            $newIsPostCutoff = strcmp($newDate, $openingDate) >= 0;

            if ($oldIsPostCutoff && ! $newIsPostCutoff) {
                $validator->errors()->add(
                    'expense_date',
                    'Tanggal pengeluaran tidak boleh dipindahkan dari periode paska saldo awal ke sebelum saldo awal kas (cut-off crossing violation).'
                );
            } elseif (! $oldIsPostCutoff && $newIsPostCutoff) {
                $validator->errors()->add(
                    'expense_date',
                    'Tanggal pengeluaran tidak boleh dipindahkan dari periode sebelum saldo awal ke paska saldo awal kas (cut-off crossing violation).'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'order_id.exists'           => 'Pesanan yang dipilih tidak valid atau tidak terdaftar pada outlet Anda.',
            'expense_date.required'     => 'Tanggal pengeluaran wajib diisi.',
            'expense_date.date'         => 'Format tanggal pengeluaran tidak valid.',
            'expense_type.required'     => 'Jenis pengeluaran wajib dipilih.',
            'expense_type.enum'         => 'Jenis pengeluaran yang dipilih tidak valid.',
            'description.required'      => 'Deskripsi pengeluaran wajib diisi.',
            'description.max'           => 'Deskripsi pengeluaran maksimal 1000 karakter.',
            'campaign_name.max'         => 'Nama kampanye maksimal 255 karakter.',
            'amount.required'           => 'Nominal pengeluaran wajib diisi.',
            'amount.numeric'            => 'Nominal pengeluaran harus berupa angka.',
            'amount.gt'                 => 'Nominal pengeluaran harus lebih besar dari 0.',
            'amendment_reason.required' => 'Alasan koreksi pengeluaran wajib diisi untuk pencatatan riwayat audit.',
            'amendment_reason.min'      => 'Alasan koreksi pengeluaran minimal 10 karakter.',
            'amendment_reason.max'      => 'Alasan koreksi pengeluaran maksimal 500 karakter.',
        ];
    }
}
