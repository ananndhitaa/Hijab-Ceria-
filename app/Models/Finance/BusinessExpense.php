<?php

namespace App\Models\Finance;

use App\Enums\ExpenseType;
use App\Models\Sales\Order;
use App\Models\System\Outlet;
use App\Models\System\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessExpense extends Model
{
    protected $fillable = [
        'outlet_id',
        'order_id',
        'expense_date',
        'expense_type',
        'campaign_name',
        'description',
        'amount',
        'created_by',
    ];

    protected $casts = [
        'expense_type' => ExpenseType::class,
        'amount'       => 'decimal:2',
        'expense_date' => 'date',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function histories(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Finance\BusinessExpenseHistory::class, 'business_expense_id')
            ->latest('created_at')
            ->latest('id');
    }

    /**
     * Scope query to records belonging to a specific trusted outlet.
     */
    public function scopeForOutlet(Builder $query, int $outletId): Builder
    {
        return $query->where('business_expenses.outlet_id', $outletId);
    }

    public function isAttributedToOrder(): bool
    {
        return $this->order_id !== null;
    }

    public function isMarketingExpense(): bool
    {
        return $this->expense_type === ExpenseType::MARKETING;
    }
}
