<?php

namespace App\Models\Master;

use App\Models\Inventory\MaterialStock;
use App\Models\Inventory\PurchaseItem;
use App\Models\Inventory\StockMovement;
use App\Models\Production\BatchMaterial;
use App\Models\System\Outlet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Material extends Model
{
    protected $fillable = [
        'outlet_id',
        'unit_id',
        'material_name',
        'min_stock',
        'active',
    ];

    protected $casts = [
        'min_stock' => 'decimal:4',
        'active'    => 'boolean',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function stock(): HasOne
    {
        return $this->hasOne(MaterialStock::class);
    }

    public function bomItems(): HasMany
    {
        return $this->hasMany(BomItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function batchMaterials(): HasMany
    {
        return $this->hasMany(BatchMaterial::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'supplier_materials')
            ->withTimestamps();
    }

    // ── Helpers ───────────────────────────────────────────────────────

    public function getCurrentQtyAttribute(): float
    {
        return (float) ($this->stock?->qty_on_hand ?? 0);
    }

    public function getCurrentAvgCostAttribute(): float
    {
        return (float) ($this->stock?->avg_unit_cost ?? 0);
    }

    public function getStockStatusAttribute(): string
    {
        $stock = (string) ($this->stock?->qty_on_hand ?? '0');
        $minStock = (string) ($this->min_stock ?? '0');

        if (bccomp($stock, '0', 4) <= 0) {
            return 'HABIS';
        }

        if (bccomp($stock, $minStock, 4) <= 0) {
            return 'RENDAH';
        }

        return 'AMAN';
    }

    public function getStockStatusBadgeAttribute(): string
    {
        return match($this->stock_status) {
            'HABIS'  => 'badge-danger',
            'RENDAH' => 'badge-warning',
            'AMAN'   => 'badge-success',
        };
    }

    public function getFormattedQtyAttribute(): string
    {
        $val = (float) ($this->stock?->qty_on_hand ?? 0);
        return number_format($val, fmod($val, 1) !== 0.0 ? 2 : 0, ',', '.');
    }

    public function getFormattedMinStockAttribute(): string
    {
        $val = (float) ($this->min_stock ?? 0);
        return number_format($val, fmod($val, 1) !== 0.0 ? 2 : 0, ',', '.');
    }

    public function isOutOfStock(): bool
    {
        $stock = (string) ($this->stock?->qty_on_hand ?? '0');

        return bccomp($stock, '0', 4) <= 0;
    }

    public function isLowStock(): bool
    {
        $stock = (string) ($this->stock?->qty_on_hand ?? '0');
        $minStock = (string) ($this->min_stock ?? '0');

        return bccomp($stock, '0', 4) > 0 && bccomp($stock, $minStock, 4) <= 0;
    }

    public function isSafeStock(): bool
    {
        $stock = (string) ($this->stock?->qty_on_hand ?? '0');
        $minStock = (string) ($this->min_stock ?? '0');

        return bccomp($stock, $minStock, 4) > 0;
    }

    /**
     * Scope query to materials belonging to a specific trusted tenant outlet.
     */
    public function scopeForOutlet(Builder $query, int $outletId): Builder
    {
        return $query->where('materials.outlet_id', $outletId);
    }
}
