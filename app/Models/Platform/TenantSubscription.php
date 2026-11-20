<?php

namespace App\Models\Platform;

use App\Models\System\Outlet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantSubscription extends Model
{
    public const TYPE_PREMIUM         = 'PREMIUM';
    public const TYPE_ANALYTICS_ADDON = 'ANALYTICS_ADDON';

    public const STATUS_ACTIVE        = 'ACTIVE';
    public const STATUS_EXPIRED       = 'EXPIRED';
    public const STATUS_UPGRADED      = 'UPGRADED';
    public const STATUS_CANCELLED     = 'CANCELLED';

    protected $fillable = [
        'outlet_id',
        'type',
        'starts_at',
        'expires_at',
        'status',
        'platform_invoice_id',
    ];

    protected $casts = [
        'starts_at'  => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PlatformInvoice::class, 'platform_invoice_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && now()->lessThanOrEqualTo($this->expires_at);
    }

    public function isPremium(): bool
    {
        return $this->type === self::TYPE_PREMIUM;
    }

    public function isAnalyticsAddon(): bool
    {
        return $this->type === self::TYPE_ANALYTICS_ADDON;
    }
}
