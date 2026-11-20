<?php

namespace App\Models\Finance;

use App\Enums\CostType;
use App\Models\Production\ProductionBatch;
use App\Models\System\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BatchCost extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'batch_id',
        'cost_type',
        'description',
        'amount',
        'recorded_at',
        'recorded_by',
    ];

    protected $casts = [
        'cost_type'   => CostType::class,
        'amount'      => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductionBatch::class, 'batch_id');
    }

    public function recordedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
