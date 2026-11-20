<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BomTemplateItem extends Model
{
    protected $fillable = [
        'bom_template_id',
        'material_id',
        'qty_per_unit',
    ];

    protected $casts = [
        'qty_per_unit' => 'decimal:4',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(BomTemplate::class, 'bom_template_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
