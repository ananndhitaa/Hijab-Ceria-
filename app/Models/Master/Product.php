<?php

namespace App\Models\Master;

use App\Models\System\Outlet;
use App\Services\Master\ProductCodeGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'outlet_id',
        'product_name',
        'name',
        'code',
        'product_code',
        'description',
        'active',
        'is_published',
        'public_description',
        'public_price',
        'featured_image',
        'public_category',
    ];

    protected $casts = [
        'active'       => 'boolean',
        'is_published' => 'boolean',
        'public_price' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            if (empty($product->code) && !empty($product->product_name)) {
                /** @var ProductCodeGenerator $generator */
                $generator = app(ProductCodeGenerator::class);
                $product->code = $generator->generate($product->product_name);
            }
        });
    }

    public function getProductCodeAttribute(): ?string
    {
        return $this->code;
    }

    public function setProductCodeAttribute(?string $value): void
    {
        $this->attributes['code'] = $value;
    }

    public function getNameAttribute(): ?string
    {
        return $this->product_name;
    }

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['product_name'] = $value;
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function bomTemplates(): HasMany
    {
        return $this->hasMany(BomTemplate::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    /**
     * Get pricing summary based on actual variants and/or public price.
     * Single source of truth across Manajemen Produk, Etalase Owner, and Public Storefront.
     *
     * @return array{
     *     has_price: bool,
     *     is_range: bool,
     *     min_price: float|null,
     *     max_price: float|null,
     *     formatted: string,
     *     label: string
     * }
     */
    public function getPriceSummary(): array
    {
        $variants = $this->relationLoaded('variants') ? $this->variants : $this->variants()->get();

        $activeVariants = $variants->where('active', true);
        $priceCollection = $activeVariants->isNotEmpty() ? $activeVariants : $variants;

        $prices = $priceCollection
            ->pluck('base_selling_price')
            ->filter(fn($p) => $p !== null && (float) $p > 0)
            ->map(fn($p) => (float) $p)
            ->values();

        // 1. If product has variants with prices configured:
        if ($variants->isNotEmpty() && $prices->isNotEmpty()) {
            $min = (float) $prices->min();
            $max = (float) $prices->max();

            if ($min === $max) {
                return [
                    'has_price' => true,
                    'is_range'  => false,
                    'min_price' => $min,
                    'max_price' => $max,
                    'formatted' => 'Rp ' . number_format($min, 0, ',', '.'),
                    'label'     => 'Harga Konveksi',
                ];
            }

            $minFormatted = 'Rp ' . number_format($min, 0, ',', '.');
            $maxFormatted = 'Rp ' . number_format($max, 0, ',', '.');

            return [
                'has_price' => true,
                'is_range'  => true,
                'min_price' => $min,
                'max_price' => $max,
                'formatted' => "{$minFormatted} - {$maxFormatted}",
                'label'     => 'Rentang Harga',
            ];
        }

        // 2. Product without variants (or variants have no price set), fallback to product-level public_price:
        if ($this->public_price !== null && (float) $this->public_price > 0) {
            $price = (float) $this->public_price;
            return [
                'has_price' => true,
                'is_range'  => false,
                'min_price' => $price,
                'max_price' => $price,
                'formatted' => 'Rp ' . number_format($price, 0, ',', '.'),
                'label'     => 'Harga Produk',
            ];
        }

        // 3. Price not configured yet
        return [
            'has_price' => false,
            'is_range'  => false,
            'min_price' => null,
            'max_price' => null,
            'formatted' => 'Harga belum diatur',
            'label'     => 'Status Harga',
        ];
    }

    /**
     * Convenient accessor for formatted price display.
     */
    public function getFormattedPriceAttribute(): string
    {
        return $this->getPriceSummary()['formatted'];
    }

    /**
     * Synchronize product's public_price with the actual price from variants.
     * If product has variants with pricing, sets public_price to the lowest variant price.
     * Does NOT alter public_price if there are no variants with prices.
     */
    public function syncEffectivePrice(): void
    {
        $variants = $this->variants()->where('active', true)->get();
        if ($variants->isEmpty()) {
            $variants = $this->variants()->get();
        }

        $prices = $variants->pluck('base_selling_price')
            ->filter(fn($p) => $p !== null && (float) $p > 0)
            ->map(fn($p) => (float) $p);

        if ($prices->isNotEmpty()) {
            $minPrice = $prices->min();
            if ($this->public_price != $minPrice) {
                $this->forceFill(['public_price' => $minPrice])->saveQuietly();
            }
        }
    }
}
