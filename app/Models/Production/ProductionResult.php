<?php

namespace App\Models\Production;

use App\Models\System\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductionResult extends Model
{
    protected $fillable = [
        'batch_id',
        'actual_qty',
        'result_date',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'actual_qty'  => 'decimal:4',
        'result_date' => 'date',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductionBatch::class, 'batch_id');
    }

    public function qcResult(): HasOne
    {
        return $this->hasOne(QcResult::class, 'production_result_id');
    }

    public function recordedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
