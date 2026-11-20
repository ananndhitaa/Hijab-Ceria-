<?php

namespace App\Models\Sales;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'order_id',
        'invoice_no',
        'invoice_date',
        'total_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'total_amount' => 'decimal:2',
        'status'       => InvoiceStatus::class,
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Parent-derived outlet ownership (follows parent Order).
     */
    public function getOutletAttribute(): ?\App\Models\System\Outlet
    {
        return $this->order?->outlet;
    }

    // ── Computed ──────────────────────────────────────────────────────

    public function getConfirmedPaidAmountAttribute(): float
    {
        return (float) $this->payments()
            ->where('status', 'CONFIRMED')
            ->sum('amount');
    }

    public function getOutstandingBalanceAttribute(): float
    {
        return max(0, (float) $this->total_amount - $this->confirmed_paid_amount);
    }

    public function isFullyPaid(): bool
    {
        return bccomp(app(\App\Services\Sales\InvoiceService::class)->calculateOutstandingBalance($this), '0.00', 2) === 0;
    }

    public function hasConfirmedDp(): bool
    {
        return app(\App\Services\Sales\InvoiceService::class)->hasConfirmedDp($this);
    }
}
