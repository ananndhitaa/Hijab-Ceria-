<?php

namespace App\Models\Production;

use App\Models\System\Outlet;
use App\Models\System\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchPicHistory extends Model
{
    protected $fillable = [
        'batch_id',
        'outlet_id',
        'assigned_by',
        'from_employee_id',
        'to_employee_id',
        'assigned_at',
        'notes',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductionBatch::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function fromEmployee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_employee_id');
    }

    public function toEmployee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_employee_id');
    }

    public function scopeForOutlet(Builder $query, int $outletId): Builder
    {
        return $query->where('outlet_id', $outletId);
    }
}

