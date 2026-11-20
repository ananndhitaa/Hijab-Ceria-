<?php

namespace App\Models\Production;

use App\Models\Master\Material;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchMaterial extends Model
{
    protected $fillable = [
        'batch_id',
        'material_id',
        'required_qty',
        'stock_at_calc',
        'shortage_qty',
        'allocated_qty',
        'used_qty',
        'calculated_at',
    ];

    protected $casts = [
        'required_qty'  => 'decimal:4',
        'stock_at_calc' => 'decimal:4',
        'shortage_qty'  => 'decimal:4',
        'allocated_qty' => 'decimal:4',
        'used_qty'      => 'decimal:4',
        'calculated_at' => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductionBatch::class, 'batch_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * Status UI: Aman / Kurang / Perlu Order
     */
    public function getMrpStatusAttribute(): string
    {
        if ($this->shortage_qty > 0) {
            return 'Perlu Order';
        }

        if ($this->stock_at_calc <= $this->required_qty) {
            return 'Kurang';
        }

        return 'Aman';
    }

    public function getMrpStatusClassAttribute(): string
    {
        return match($this->mrp_status) {
            'Perlu Order' => 'badge-danger',
            'Kurang'      => 'badge-warning',
            default       => 'badge-success',
        };
    }
}
