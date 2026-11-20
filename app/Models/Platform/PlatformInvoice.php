<?php

namespace App\Models\Platform;

use App\Models\System\Outlet;
use App\Models\System\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PlatformInvoice extends Model
{
    public const SERVICE_PREMIUM           = 'PREMIUM';
    public const SERVICE_ANALYTICS_ADDON   = 'ANALYTICS_ADDON';
    public const SERVICE_LISTING_SLOTS     = 'LISTING_SLOTS';
    public const SERVICE_UPGRADE_PREMIUM   = 'UPGRADE_PREMIUM';
    public const SERVICE_SHOP_AD           = 'SHOP_AD';
    public const SERVICE_PRODUCT_AD        = 'PRODUCT_AD';
    public const SERVICE_SETUP_TRAINING    = 'SETUP_TRAINING';

    public const STATUS_UNPAID             = 'UNPAID';
    public const STATUS_PAYMENT_SUBMITTED  = 'PAYMENT_SUBMITTED';
    public const STATUS_VERIFIED           = 'VERIFIED';
    public const STATUS_EXPIRED            = 'EXPIRED';
    public const STATUS_CANCELLED          = 'CANCELLED';
    public const STATUS_REFUNDED           = 'REFUNDED';

    protected $fillable = [
        'invoice_no',
        'outlet_id',
        'created_by',
        'service_type',
        'package_duration_months',
        'amount',
        'status',
        'payment_method',
        'payment_proof_path',
        'payment_submitted_at',
        'expires_at',
        'verified_at',
        'verified_by',
        'metadata',
    ];

    protected $casts = [
        'amount'                  => 'decimal:2',
        'package_duration_months' => 'integer',
        'payment_submitted_at'    => 'datetime',
        'expires_at'              => 'datetime',
        'verified_at'             => 'datetime',
        'metadata'                => 'array',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(TenantSubscription::class, 'platform_invoice_id');
    }

    public function isPaid(): bool
    {
        return in_array($this->status, [self::STATUS_PAYMENT_SUBMITTED, self::STATUS_VERIFIED]);
    }

    public function isVerified(): bool
    {
        return $this->status === self::STATUS_VERIFIED;
    }

    public function isUnpaid(): bool
    {
        return $this->status === self::STATUS_UNPAID;
    }

    public function isPaymentSubmitted(): bool
    {
        return $this->status === self::STATUS_PAYMENT_SUBMITTED;
    }

    public function isExpired(): bool
    {
        if ($this->status === self::STATUS_EXPIRED) {
            return true;
        }
        if ($this->isUnpaid() && $this->expires_at && now()->isAfter($this->expires_at)) {
            return true;
        }
        return false;
    }
}
