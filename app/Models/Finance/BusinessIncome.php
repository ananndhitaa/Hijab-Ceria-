<?php

namespace App\Models\Finance;

use App\Models\System\Outlet;
use App\Models\System\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessIncome extends Model
{
    public const CATEGORY_PEMASUKAN_LAIN = 'Pemasukan Lain';
    public const CATEGORY_MODAL_TAMBAHAN = 'Modal Tambahan';
    public const CATEGORY_NON_PENJUALAN  = 'Pendapatan Non-Penjualan';

    public const CATEGORIES = [
        self::CATEGORY_PEMASUKAN_LAIN,
        self::CATEGORY_MODAL_TAMBAHAN,
        self::CATEGORY_NON_PENJUALAN,
    ];

    protected $fillable = [
        'outlet_id',
        'income_no',
        'income_date',
        'category',
        'amount',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'income_date' => 'date',
        'amount'      => 'decimal:2',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope query to records belonging to a specific trusted outlet.
     */
    public function scopeForOutlet(Builder $query, int $outletId): Builder
    {
        return $query->where('business_incomes.outlet_id', $outletId);
    }
}
