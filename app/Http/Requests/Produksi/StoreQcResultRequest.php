<?php

namespace App\Http\Requests\Produksi;

use App\Models\Production\ProductionBatch;
use App\Services\System\TenantContext;
use Illuminate\Foundation\Http\FormRequest;

class StoreQcResultRequest extends FormRequest
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
        $batch = $this->route('batch');
        $actualQty = $batch?->productionResults()->whereDoesntHave('qcResult')->latest('id')->value('actual_qty')
            ?? $batch?->productionResult?->actual_qty;

        $goodRules = ['required', 'integer', 'min:0', 'max:99999999999999'];
        if ($actualQty !== null) {
            $goodRules[] = 'lte:' . (int) $actualQty;
        }

        return [
            'good_qty' => $goodRules,
            'qc_date'  => ['nullable', 'date', 'date_format:Y-m-d'],
            'notes'    => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'good_qty.required'   => 'Kuantitas produk Good wajib diisi.',
            'good_qty.integer'    => 'Kuantitas produk Good harus berupa bilangan bulat (pcs).',
            'good_qty.numeric'    => 'Kuantitas produk Good harus berupa angka yang valid.',
            'good_qty.min'        => 'Kuantitas produk Good tidak boleh bernilai negatif.',
            'good_qty.lte'        => 'Jumlah lolos QC tidak boleh melebihi hasil produksi aktual.',
            'good_qty.max'        => 'Kuantitas produk Good melebihi batas maksimum sistem.',
            'reject_qty.integer'  => 'Kuantitas produk Reject harus berupa bilangan bulat (pcs).',
            'reject_qty.min'      => 'Kuantitas produk Reject tidak boleh bernilai negatif.',
            'reject_qty.max'      => 'Kuantitas produk Reject melebihi batas maksimum sistem.',
            'qc_date.date'        => 'Format Tanggal QC tidak valid.',
            'qc_date.date_format' => 'Format Tanggal QC harus YYYY-MM-DD.',
            'notes.max'           => 'Catatan QC maksimal 1000 karakter.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $batch = $this->route('batch');
            if ($batch instanceof \App\Models\Production\ProductionBatch) {
                if (! $batch->productionResult()->exists()) {
                    return;
                }

                $actualProductionDate = $batch->getActualProductionDate();

                // 1. QC eligibility gate: today must not be before actual production date
                $todayBusiness = \App\Support\DateTimeFormatter::toDisplayTimezone(now())->toDateString();
                if ($todayBusiness < $actualProductionDate) {
                    $validator->errors()->add('good_qty', 'QC belum dapat dilakukan sebelum tanggal produksi.');
                }

                // 2. Authoritative QC Date rule: submitted qc_date must not be before actual production date
                $submittedQcDate = $this->input('qc_date');
                if ($submittedQcDate && $submittedQcDate < $actualProductionDate) {
                    $validator->errors()->add('qc_date', 'Tanggal QC tidak boleh sebelum tanggal produksi aktual.');
                }
            }
        });
    }
}
