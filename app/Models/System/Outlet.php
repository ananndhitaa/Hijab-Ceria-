<?php

namespace App\Models\System;

use App\Models\Inventory\Purchase;
use App\Models\Master\BomTemplate;
use App\Models\Master\Customer;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Master\Supplier;
use App\Models\Sales\Order;
use Database\Factories\OutletFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Outlet extends Model
{
    /** @use HasFactory<OutletFactory> */
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): OutletFactory
    {
        return OutletFactory::new();
    }

    protected $fillable = [
        'outlet_name',
        'outlet_code',
        'slug',
        'phone_wa',
        'address',
        'description',
        'logo_path',
        'status',
        'is_public_showcase_enabled',
    ];

    protected function casts(): array
    {
        return [
            'is_public_showcase_enabled' => 'boolean',
        ];
    }

    public function isActive(): bool
    {
        return strtoupper((string) $this->status) === 'ACTIVE';
    }

    public function getNameAttribute(): string
    {
        return $this->outlet_name ?? '';
    }

    public function getPhoneAttribute(): ?string
    {
        return $this->phone_wa;
    }

    // ── Relationships ─────────────────────────────────────────────────

    /**
     * Get all users associated with this outlet.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function productVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function bomTemplates(): HasMany
    {
        return $this->hasMany(BomTemplate::class);
    }

    public function documentSequences(): HasMany
    {
        return $this->hasMany(DocumentSequence::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(\App\Models\Platform\OwnerComplaint::class);
    }
}
