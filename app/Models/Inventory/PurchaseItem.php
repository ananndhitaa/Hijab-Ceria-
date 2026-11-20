<?php

namespace App\Models\Inventory;

use App\Enums\MovementType;
use App\Models\Master\Material;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id',
        'material_id',
        'qty',
        'unit_price',
        'qty_received',
    ];

    protected $casts = [
        'qty'          => 'decimal:4',
        'unit_price'   => 'decimal:2',
        'qty_received' => 'decimal:4',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * The StockMovement IN record created when this item was received.
     *
     * Uses reference_type/reference_id (non-morph string convention).
     */
    public function stockMovementIn(): HasOne
    {
        return $this->hasOne(StockMovement::class, 'reference_id')
            ->where('reference_type', 'purchase_items')
            ->where('movement_type', MovementType::IN);
    }

    public function getSubtotalAttribute(): float
    {
        return (float) $this->qty * (float) $this->unit_price;
    }

    public function getQtyRemainingAttribute(): float
    {
        return max(0, (float) $this->qty - (float) $this->qty_received);
    }
}
