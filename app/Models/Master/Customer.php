<?php

namespace App\Models\Master;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Sales\Order;
use App\Models\System\Outlet;
use App\Models\System\User;
use App\Support\PhoneNormalizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'outlet_id',
        'user_id',
        'customer_name',
        'first_contact_date',
        'phone',
        'address',
        'notes',
        'handled_by_user_id',
        'email',
        'active',
    ];

    protected $casts = [
        'first_contact_date' => 'date',
        'active'             => 'boolean',
    ];

    // ── Relationships ─────────────────────────────────────────────────

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by_user_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────

    /**
     * Eagerly compute whether customer has a confirmed deal without N+1 queries.
     */
    public function scopeWithDealStatus(Builder $query): Builder
    {
        return $query->withExists([
            'orders as has_confirmed_deal' => function ($q) {
                $q->whereHas('invoice', function ($iq) {
                    $iq->where('status', '!=', InvoiceStatus::CANCELLED)
                       ->whereHas('payments', function ($pq) {
                           $pq->where('status', PaymentStatus::CONFIRMED)
                              ->where('amount', '>', 0);
                       });
                });
            }
        ]);
    }

    // ── Helpers & Derived Status ──────────────────────────────────────

    public function hasAccount(): bool
    {
        return $this->user_id !== null;
    }

    public function totalOrders(): int
    {
        return $this->orders()->count();
    }

    /**
     * Authoritative Deal status derivation:
     * DEAL: customer has at least 1 valid Sales Order with confirmed payment > 0 on an active invoice.
     * BELUM DEAL: otherwise.
     */
    public function isDeal(): bool
    {
        if (isset($this->attributes['has_confirmed_deal'])) {
            return (bool) $this->attributes['has_confirmed_deal'];
        }

        if ($this->relationLoaded('orders')) {
            return $this->orders->contains(fn (Order $order) => $order->isDeal());
        }

        return $this->orders()
            ->whereHas('invoice', function ($iq) {
                $iq->where('status', '!=', InvoiceStatus::CANCELLED)
                   ->whereHas('payments', function ($pq) {
                       $pq->where('status', PaymentStatus::CONFIRMED)
                          ->where('amount', '>', 0);
                   });
            })
            ->exists();
    }

    public function getNameAttribute(): string
    {
        return $this->customer_name ?? '';
    }

    public function getDisplayNameAttribute(): string
    {
        return filled($this->customer_name) ? $this->customer_name : 'Nama belum diisi';
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->isDeal() ? 'Deal' : 'Belum Deal';
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return $this->isDeal()
            ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
            : 'bg-amber-50 text-amber-700 border border-amber-200';
    }

    public function formattedPhone(): string
    {
        return PhoneNormalizer::format($this->phone);
    }

    public static function findByNormalizedPhone(?string $phone, ?int $exceptId = null): ?self
    {
        $norm = PhoneNormalizer::normalize($phone);
        if (! $norm) {
            return null;
        }

        return self::when($exceptId, fn($q) => $q->where('id', '!=', $exceptId))
            ->get()
            ->first(function ($c) use ($norm) {
                return PhoneNormalizer::normalize($c->phone) === $norm;
            });
    }
}
