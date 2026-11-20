<?php

namespace App\Models\Finance;

use App\Enums\ExpenseType;
use App\Models\Sales\Order;
use App\Models\System\Outlet;
use App\Models\System\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class BusinessExpenseHistory
 *
 * Immutable audit snapshot representing historical evidence of a BusinessExpense amendment.
 * This record is strictly an audit trail and must never be treated as live cash movement.
 */
class BusinessExpenseHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'business_expense_id',
        'outlet_id',
        'actor_user_id',
        'reason',
        'old_amount',
        'new_amount',
        'old_expense_date',
        'new_expense_date',
        'old_order_id',
        'new_order_id',
        'old_expense_type',
        'new_expense_type',
        'old_campaign_name',
        'new_campaign_name',
        'old_description',
        'new_description',
        'created_at',
    ];

    protected $casts = [
        'old_amount'       => 'decimal:2',
        'new_amount'       => 'decimal:2',
        'old_expense_date' => 'date',
        'new_expense_date' => 'date',
        'old_expense_type' => ExpenseType::class,
        'new_expense_type' => ExpenseType::class,
        'created_at'       => 'datetime',
    ];

    public function expense(): BelongsTo
    {
        return $this->belongsTo(BusinessExpense::class, 'business_expense_id');
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function actorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function oldOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'old_order_id');
    }

    public function newOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'new_order_id');
    }

    /**
     * Scope query to records belonging to a specific trusted outlet.
     */
    public function scopeForOutlet(Builder $query, int $outletId): Builder
    {
        return $query->where('business_expense_histories.outlet_id', $outletId);
    }
}
