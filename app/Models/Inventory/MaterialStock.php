<?php

namespace App\Models\Inventory;

use App\Models\Master\Material;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialStock extends Model
{
    protected $fillable = [
        'material_id',
        'qty_on_hand',
        'avg_unit_cost',
    ];

    protected $casts = [
        'qty_on_hand'   => 'decimal:4',
        'avg_unit_cost' => 'decimal:4',
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function getTotalValueAttribute(): float
    {
        return (float) $this->qty_on_hand * (float) $this->avg_unit_cost;
    }
}
