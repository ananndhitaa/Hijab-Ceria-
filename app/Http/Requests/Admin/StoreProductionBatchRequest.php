<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductionBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user() || $this->user()->role !== UserRole::ADMIN) {
            return false;
        }

        if (config('nayara.tenant_isolation_enabled', false)) {
            $outletId = app(\App\Services\System\TenantContext::class)->currentOutletId();

            $order = $this->route('order');
            if (! $order instanceof \App\Models\Sales\Order) {
                $order = \App\Models\Sales\Order::find($order);
            }

            if (! $order || $order->outlet_id === null || (int) $order->outlet_id !== (int) $outletId) {
                abort(404);
            }

            if ($order->customer && $order->customer->outlet_id !== null && (int) $order->customer->outlet_id !== (int) $outletId) {
                abort(404);
            }
        }

        return true;
    }

    public function rules(): array
    {
        $order = $this->route('order');
        $orderId = $order?->id ?? $order;

        $isolationEnabled = config('nayara.tenant_isolation_enabled', false);
        $outletId = null;
        if ($isolationEnabled) {
            try {
                $outletId = app(\App\Services\System\TenantContext::class)->currentOutletId();
            } catch (\Throwable) {
                $outletId = null;
            }
        }

        return [
            'order_item_id' => [
                'required',
                'integer',
                Rule::exists('order_items', 'id')->where('order_id', $orderId),
                function ($attribute, $value, $fail) use ($isolationEnabled, $outletId) {
                    if ($isolationEnabled && $outletId !== null) {
                        $item = \App\Models\Sales\OrderItem::with(['variant.product', 'variant.bomItems.material'])->find($value);
                        if (! $item) {
                            return;
                        }
                        if ($item->variant && $item->variant->outlet_id !== null && (int) $item->variant->outlet_id !== (int) $outletId) {
                            $fail('Item pesanan yang dipilih tidak valid atau bukan milik pesanan ini.');
                        }
                        if ($item->variant?->product && $item->variant->product->outlet_id !== null && (int) $item->variant->product->outlet_id !== (int) $outletId) {
                            $fail('Item pesanan yang dipilih tidak valid atau bukan milik pesanan ini.');
                        }
                        if ($item->variant) {
                            foreach ($item->variant->bomItems as $bomItem) {
                                if ($bomItem->material && $bomItem->material->outlet_id !== null && (int) $bomItem->material->outlet_id !== (int) $outletId) {
                                    $fail('Item pesanan yang dipilih tidak valid atau bukan milik pesanan ini.');
                                    break;
                                }
                            }
                        }
                    }
                },
            ],
            'target_qty'         => ['required', 'numeric', 'gt:0'],
            'planned_start_date' => ['nullable', 'date'],
            'planned_end_date'   => ['required', 'date', 'after_or_equal:planned_start_date'],
            'notes'              => ['nullable', 'string', 'max:1000'],
            'production_employee_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(function ($query) use ($isolationEnabled, $outletId) {
                    $query->where('role', UserRole::PRODUKSI->value)
                        ->where('status', UserStatus::ACTIVE->value);
                    if ($isolationEnabled && $outletId !== null) {
                        $query->where('outlet_id', $outletId);
                    }
                }),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'order_item_id.required'           => 'Item pesanan wajib dipilih.',
            'order_item_id.exists'             => 'Item pesanan yang dipilih tidak valid atau bukan milik pesanan ini.',
            'target_qty.required'              => 'Target kuantitas produksi wajib diisi.',
            'target_qty.numeric'               => 'Target kuantitas harus berupa angka.',
            'target_qty.gt'                    => 'Target kuantitas harus lebih besar dari 0.',
            'planned_start_date.date'          => 'Format tanggal mulai rencana tidak valid.',
            'planned_end_date.required'        => 'Target tanggal selesai produksi wajib diisi.',
            'planned_end_date.date'            => 'Format tanggal selesai rencana tidak valid.',
            'planned_end_date.after_or_equal'  => 'Tanggal selesai rencana tidak boleh mendahului tanggal mulai.',
            'notes.max'                        => 'Catatan maksimal 1000 karakter.',
            'production_employee_id.exists'    => 'Karyawan produksi yang dipilih tidak valid atau bukan role Produksi.',
        ];
    }
}
