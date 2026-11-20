<?php

namespace App\Models\Platform;

use App\Models\System\Outlet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutletListingSlot extends Model
{
    protected $fillable = [
        'outlet_id',
        'purchased_slots',
    ];

    protected $casts = [
        'purchased_slots' => 'integer',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }
}
