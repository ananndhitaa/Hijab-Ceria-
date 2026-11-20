<?php

namespace App\Models\Sales;

use App\Models\Master\ProductVariant;
use App\Models\Production\ProductionBatch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'variant_id',
        'qty_ordered',
        'unit_selling_price',
    ];

    protected $casts = [
        'qty_ordered'        => 'decimal:4',
        'unit_selling_price' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function productionBatches(): HasMany
    {
        return $this->hasMany(ProductionBatch::class);
    }

    public function getSubtotalAttribute(): float
    {
        return (float) $this->qty_ordered * (float) $this->unit_selling_price;
    }
}
