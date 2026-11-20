<?php

namespace App\Models\Platform;

use App\Models\Master\Product;
use App\Models\System\Outlet;
use App\Models\System\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdCampaign extends Model
{
    public const TYPE_SHOP_AD    = 'SHOP_AD';
    public const TYPE_PRODUCT_AD = 'PRODUCT_AD';

    public const STATUS_DRAFT            = 'DRAFT';
    public const STATUS_PENDING_REVIEW   = 'PENDING_REVIEW';
    public const STATUS_NEEDS_REVISION   = 'NEEDS_REVISION';
    public const STATUS_APPROVED         = 'APPROVED';
    public const STATUS_PAYMENT_PENDING  = 'PAYMENT_PENDING';
    public const STATUS_SCHEDULED        = 'SCHEDULED';
    public const STATUS_ACTIVE           = 'ACTIVE';
    public const STATUS_COMPLETED        = 'COMPLETED';
    public const STATUS_CANCELLED        = 'CANCELLED';
    public const STATUS_REJECTED         = 'REJECTED';
    public const STATUS_SUSPENDED        = 'SUSPENDED';

    protected $fillable = [
        'outlet_id',
        'created_by',
        'ad_type',
        'product_id',
        'title',
        'tagline',
        'banner_path',
        'total_budget',
        'duration_days',
        'effective_bid_per_day',
        'starts_at',
        'ends_at',
        'status',
        'schedule_change_count',
        'creative_revision_count',
        'platform_invoice_id',
        'verified_payment_at',
        'review_notes',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'total_budget'            => 'decimal:2',
        'duration_days'           => 'integer',
        'effective_bid_per_day'   => 'decimal:2',
        'starts_at'               => 'datetime',
        'ends_at'                 => 'datetime',
        'schedule_change_count'   => 'integer',
        'creative_revision_count' => 'integer',
        'verified_payment_at'     => 'datetime',
        'reviewed_at'             => 'datetime',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PlatformInvoice::class, 'platform_invoice_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(AdCampaignRevision::class);
    }

    public function isShopAd(): bool
    {
        return $this->ad_type === self::TYPE_SHOP_AD;
    }

    public function isProductAd(): bool
    {
        return $this->ad_type === self::TYPE_PRODUCT_AD;
    }

    public function isCurrentlyAiring(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        $now = now();
        return $this->starts_at && $this->ends_at
            && $now->greaterThanOrEqualTo($this->starts_at)
            && $now->lessThan($this->ends_at);
    }

    public function hasAired(): bool
    {
        return $this->starts_at && now()->greaterThan($this->starts_at);
    }
}
