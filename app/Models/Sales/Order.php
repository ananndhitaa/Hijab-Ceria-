<?php

namespace App\Models\Sales;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Finance\BusinessExpense;
use App\Models\Finance\OrderFinancial;
use App\Models\Master\Customer;
use App\Models\System\OrderStatusHistory;
use App\Models\System\OrderTrackingToken;
use App\Models\System\Shipment;
use App\Models\System\Outlet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'outlet_id',
        'customer_id',
        'order_no',
        'order_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'order_date' => 'date',
        'status'     => OrderStatus::class,
    ];

    // ── Scopes ────────────────────────────────────────────────────────

    /**
     * Scope orders that have confirmed Deal status (active invoice with confirmed payments > 0).
     */
    public function scopeDeal(Builder $query): Builder
    {
        return $query->whereHas('invoice', function ($iq) {
            $iq->where('status', '!=', InvoiceStatus::CANCELLED)
               ->whereHas('payments', function ($pq) {
                   $pq->where('status', PaymentStatus::CONFIRMED)
                      ->where('amount', '>', 0);
               });
        });
    }

    /**
     * Scope orders awaiting deal confirmation / down payment.
     */
    public function scopeAwaitingDeal(Builder $query): Builder
    {
        return $query->whereDoesntHave('invoice', function ($iq) {
            $iq->where('status', '!=', InvoiceStatus::CANCELLED)
               ->whereHas('payments', function ($pq) {
                   $pq->where('status', PaymentStatus::CONFIRMED)
                      ->where('amount', '>', 0);
               });
        });
    }

    /**
     * Scope orders to a specific legitimate outlet.
     */
    public function scopeForOutlet(Builder $query, int $outletId): Builder
    {
        return $query->where('orders.outlet_id', $outletId);
    }

    // ── Relationships ─────────────────────────────────────────────────

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function latestShipment(): HasOne
    {
        return $this->hasOne(Shipment::class)->latestOfMany();
    }

    public function review(): HasOne
    {
        return $this->hasOne(\App\Models\Platform\OrderReview::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function trackingTokens(): HasMany
    {
        return $this->hasMany(OrderTrackingToken::class);
    }

    public function hasActiveTrackingToken(): bool
    {
        return $this->trackingTokens()->valid()->exists();
    }

    public function financial(): HasOne
    {
        return $this->hasOne(OrderFinancial::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(BusinessExpense::class);
    }

    public function businessExpenses(): HasMany
    {
        return $this->hasMany(BusinessExpense::class);
    }

    // ── Computed Attributes ───────────────────────────────────────────

    public function getTotalAmountAttribute(): float
    {
        return $this->items->sum(fn ($item) => (float) $item->qty_ordered * (float) $item->unit_selling_price);
    }

    public function getTotalQtyAttribute(): float
    {
        return (float) $this->items->sum('qty_ordered');
    }

    public function isFullyPaid(): bool
    {
        return app(\App\Services\Sales\InvoiceService::class)->isOrderFullyPaid($this);
    }

    public function hasConfirmedDownPayment(): bool
    {
        return app(\App\Services\Sales\InvoiceService::class)->hasConfirmedDownPayment($this);
    }

    /**
     * Authoritative Deal status derivation for THIS specific Sales Order:
     * An order is Deal if it has an active (non-cancelled) invoice with confirmed payments > 0.
     * Evaluated strictly on this order's own payments, independent of any other orders.
     */
    public function isDeal(): bool
    {
        return $this->hasConfirmedDownPayment();
    }

    /**
     * Canonical operational status label consistent across Dashboard Admin and Performa Admin.
     * For KOMUNIKASI, returns 'Menunggu Deal'.
     * For other statuses, returns the canonical status label.
     */
    public function getStatusLabelAttribute(): string
    {
        if ($this->status === OrderStatus::KOMUNIKASI) {
            return 'Menunggu Deal';
        }

        return $this->status->label();
    }

    /**
     * Badge CSS class for the order status.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return $this->status->badgeClass();
    }

    public function getDownPaymentStatus(): array
    {
        return app(\App\Services\Sales\InvoiceService::class)->getDownPaymentStatus($this);
    }

    public function isPackingCompleted(): bool
    {
        return app(\App\Services\Production\ShipmentService::class)->isPackingCompleted($this);
    }

    public function getFulfillmentDisplayState(): string
    {
        return app(\App\Services\Production\ShipmentService::class)->getFulfillmentDisplayState($this);
    }

    public function getFulfillmentPresentation(bool $isCustomerPortal = false): array
    {
        return app(\App\Services\Production\ShipmentService::class)->getFulfillmentPresentation($this, $isCustomerPortal);
    }
}
