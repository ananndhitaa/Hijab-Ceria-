<?php

namespace App\Models\Master;

use App\Models\Inventory\Purchase;
use App\Models\System\Outlet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'outlet_id',
        'supplier_name',
        'phone',
        'address',
        'email',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Scope suppliers to a specific legitimate outlet.
     */
    public function scopeForOutlet(\Illuminate\Database\Eloquent\Builder $query, int $outletId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('suppliers.outlet_id', $outletId);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function materials(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Material::class, 'supplier_materials')
            ->withTimestamps();
    }
}
