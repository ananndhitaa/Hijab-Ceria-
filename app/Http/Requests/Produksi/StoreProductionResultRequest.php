<?php

namespace App\Http\Requests\Produksi;

use App\Models\Production\ProductionBatch;
use App\Services\System\TenantContext;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductionResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (config('nayara.tenant_isolation_enabled', false)) {
            $outletId = app(TenantContext::class)->currentOutletId();

            $batch = $this->route('batch');
            if (! $batch instanceof ProductionBatch) {
                $batch = ProductionBatch::find($batch);
            }

            if (! $batch || ! $batch->hasConsistentTenantRelations($outletId)) {
                abort(404);
            }
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'actual_qty'       => ['required', 'numeric', 'gt:0', 'max:99999999999999.9999'],
            'result_date'      => ['required', 'date'],
            'notes'            => ['nullable', 'string', 'max:1000'],
            'material_usage'   => ['nullable', 'array'],
            'material_usage.*' => ['required', 'numeric', 'min:0', 'max:99999999999999.9999'],
        ];
    }

    public function messages(): array
    {
        return [
            'actual_qty.required'     => 'Kuantitas hasil produksi aktual wajib diisi.',
            'actual_qty.numeric'      => 'Kuantitas hasil produksi harus berupa angka yang valid.',
            'actual_qty.gt'           => 'Kuantitas hasil produksi harus lebih besar dari 0.',
            'actual_qty.max'          => 'Kuantitas hasil produksi melebihi batas maksimum sistem.',
            'result_date.required'    => 'Tanggal hasil produksi wajib diisi.',
            'result_date.date'        => 'Format tanggal hasil produksi tidak valid.',
            'notes.max'               => 'Catatan hasil produksi maksimal 1000 karakter.',
            'material_usage.*.required'=> 'Kuantitas pemakaian bahan wajib diisi.',
            'material_usage.*.numeric' => 'Kuantitas pemakaian bahan harus berupa angka yang valid.',
            'material_usage.*.min'     => 'Kuantitas pemakaian bahan tidak boleh bernilai negatif.',
            'material_usage.*.max'     => 'Kuantitas pemakaian bahan melebihi batas maksimum sistem.',
        ];
    }
}
