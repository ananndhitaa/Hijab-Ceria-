<?php

namespace App\Models\Production;

use App\Models\System\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * QC hanya GOOD dan REJECT — TIDAK ADA REWORK.
 */
class QcResult extends Model
{
    protected $fillable = [
        'production_result_id',
        'good_qty',
        'reject_qty',
        'qc_date',
        'qc_at',
        'notes',
        'qc_by',
    ];

    protected $casts = [
        'good_qty'   => 'decimal:4',
        'reject_qty' => 'decimal:4',
        'qc_date'    => 'date',
        'qc_at'      => 'datetime',
    ];

    public function productionResult(): BelongsTo
    {
        return $this->belongsTo(ProductionResult::class, 'production_result_id');
    }

    public function qcByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'qc_by');
    }

    public function inspector(): BelongsTo
    {
        return $this->qcByUser();
    }

    public function getInspectedQtyAttribute(): float
    {
        return $this->total_checked;
    }

    public function getTotalCheckedAttribute(): float
    {
        return (float) $this->good_qty + (float) $this->reject_qty;
    }

    public function getGoodRateAttribute(): ?float
    {
        $total = $this->total_checked;
        if ($total == 0) {
            return null;
        }

        return ((float) $this->good_qty / $total) * 100;
    }
}
