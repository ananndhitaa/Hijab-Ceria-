<?php

namespace App\Models\Inventory;

use App\Enums\MovementType;
use App\Models\Master\Material;
use App\Models\System\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    protected $fillable = [
        'material_id',
        'movement_type',
        'qty',
        'unit_cost_snapshot',
        'total_cost_snapshot',
        'qty_before',
        'qty_after',
        'reference_type',
        'reference_id',
        'movement_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'movement_type'       => MovementType::class,
        'qty'                 => 'decimal:4',
        'unit_cost_snapshot'  => 'decimal:4',
        'total_cost_snapshot' => 'decimal:2',
        'qty_before'          => 'decimal:4',
        'qty_after'           => 'decimal:4',
        'movement_date'       => 'date',
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo('reference');
    }

    /**
     * Scope query to stock movements belonging to a specific trusted tenant outlet via material ownership.
     */
    public function scopeForOutlet(Builder $query, int $outletId): Builder
    {
        return $query->whereHas('material', function (Builder $mq) use ($outletId) {
            $mq->where('materials.outlet_id', $outletId);
        });
    }
}
