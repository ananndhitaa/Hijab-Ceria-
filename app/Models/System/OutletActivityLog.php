<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class OutletActivityLog extends Model
{
    protected $fillable = [
        'outlet_id',
        'user_id',
        'user_name',
        'user_role',
        'module',
        'action_type',
        'reference_no',
        'subject_type',
        'subject_id',
        'description',
        'before_values',
        'after_values',
    ];

    protected $casts = [
        'before_values' => 'array',
        'after_values'  => 'array',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope query to a specific outlet.
     */
    public function scopeForOutlet(Builder $query, int $outletId): Builder
    {
        return $query->where('outlet_id', $outletId);
    }
}

