<?php

namespace App\Models\Master;

use App\Models\Sales\OrderItem;
use App\Models\System\Outlet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    protected $fillable = [
        'outlet_id',
        'product_id',
        'variant_name',
        'sku',
        'image_path',
        'base_selling_price',
        'requires_printing',
        'printing_cost_per_unit',
        'direct_labor_cost_per_unit',
        'packaging_process_cost_per_good_unit',
        'other_direct_cost_per_unit',
        'active',
    ];

    protected $casts = [
        'base_selling_price'                   => 'decimal:2',
        'requires_printing'                    => 'boolean',
        'printing_cost_per_unit'               => 'decimal:2',
        'direct_labor_cost_per_unit'           => 'decimal:2',
        'packaging_process_cost_per_good_unit' => 'decimal:2',
        'other_direct_cost_per_unit'           => 'decimal:2',
        'active'                               => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(function (ProductVariant $variant) {
            $variant->product?->syncEffectivePrice();
        });

        static::deleted(function (ProductVariant $variant) {
            $variant->product?->syncEffectivePrice();
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function bomItems(): HasMany
    {
        return $this->hasMany(BomItem::class, 'variant_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'variant_id');
    }

    public function productionBatches(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(
            \App\Models\Production\ProductionBatch::class,
            OrderItem::class,
            'variant_id',
            'order_item_id'
        );
    }

    public function getFullNameAttribute(): string
    {
        return $this->product->product_name . ' - ' . $this->variant_name;
    }

    public function getEstimatedCostAttribute(): array
    {
        return app(\App\Services\Finance\VariantCostCalculationService::class)->calculateEstimatedCost($this);
    }

    public function getLatestActualHppAttribute(): array
    {
        return app(\App\Services\Finance\VariantCostCalculationService::class)->getLatestActualHpp($this);
    }

    public function getReferenceCostAttribute(): ?string
    {
        $ref = app(\App\Services\Finance\VariantCostCalculationService::class)->getReferenceCost($this);
        return $ref['reference_cost'];
    }

    public function getReferenceCostDataAttribute(): array
    {
        return app(\App\Services\Finance\VariantCostCalculationService::class)->getReferenceCost($this);
    }

    public function getImageUrlAttribute(): ?string
    {
        if (empty($this->image_path)) {
            return null;
        }

        if (str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://')) {
            return $this->image_path;
        }

        if (str_starts_with($this->image_path, 'images/')) {
            return asset($this->image_path);
        }

        return asset('storage/' . $this->image_path);
    }

    public function getEffectiveImageUrlAttribute(): ?string
    {
        return $this->image_url ?? ($this->product?->featured_image ? asset('storage/' . $this->product->featured_image) : null);
    }
}
