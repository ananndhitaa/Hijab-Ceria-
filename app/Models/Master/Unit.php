<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    protected $fillable = [
        'unit_name',
        'symbol',
    ];

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }
}
