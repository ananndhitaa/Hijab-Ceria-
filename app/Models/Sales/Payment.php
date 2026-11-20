<?php

namespace App\Models\Sales;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\System\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'invoice_id',
        'payment_type',
        'amount',
        'payment_date',
        'payment_method',
        'reference_no',
        'status',
        'notes',
        'confirmed_by',
        'confirmed_at',
    ];

    protected $casts = [
        'payment_type' => PaymentType::class,
        'status'       => PaymentStatus::class,
        'amount'       => 'decimal:2',
        'payment_date' => 'date',
        'confirmed_at' => 'datetime',
    ];

    /**
     * Accessor ensuring canonical payment method display (PRD v1.5).
     */
    public function getPaymentMethodAttribute(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $upper = strtoupper(trim($value));
        if (str_starts_with($upper, 'TRANSFER') || in_array($upper, ['BCA', 'MANDIRI', 'BNI', 'BRI'])) {
            return PaymentMethod::TRANSFER_BANK->value;
        }
        if (in_array($upper, ['CASH', 'TUNAI'])) {
            return PaymentMethod::TUNAI->value;
        }
        if ($upper === 'QRIS') {
            return PaymentMethod::QRIS->value;
        }

        return $value;
    }

    /**
     * Mutator ensuring canonical payment method persistence (PRD v1.5).
     */
    public function setPaymentMethodAttribute(mixed $value): void
    {
        if ($value instanceof PaymentMethod) {
            $this->attributes['payment_method'] = $value->value;
            return;
        }

        if (is_string($value)) {
            $upper = strtoupper(trim($value));
            if (str_starts_with($upper, 'TRANSFER') || in_array($upper, ['BCA', 'MANDIRI', 'BNI', 'BRI'])) {
                $this->attributes['payment_method'] = PaymentMethod::TRANSFER_BANK->value;
                return;
            }
            if (in_array($upper, ['CASH', 'TUNAI'])) {
                $this->attributes['payment_method'] = PaymentMethod::TUNAI->value;
                return;
            }
            if ($upper === 'QRIS') {
                $this->attributes['payment_method'] = PaymentMethod::QRIS->value;
                return;
            }
        }

        $this->attributes['payment_method'] = $value;
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Parent-derived outlet ownership (follows parent Invoice -> Order).
     */
    public function getOutletAttribute(): ?\App\Models\System\Outlet
    {
        return $this->invoice?->order?->outlet;
    }

    public function confirmedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function isConfirmed(): bool
    {
        return $this->status === PaymentStatus::CONFIRMED;
    }
}
