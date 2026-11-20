<?php

namespace App\Models\Platform;

use App\Models\System\Outlet;
use App\Models\System\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformServiceRequest extends Model
{
    public const TYPE_SETUP_ONBOARDING    = 'SETUP_ONBOARDING';
    public const TYPE_TRAINING_STAFF      = 'TRAINING_STAFF';
    public const TYPE_CUSTOM_CONSULTATION = 'CUSTOM_CONSULTATION';

    public const STATUS_SUBMITTED   = 'SUBMITTED';
    public const STATUS_IN_REVIEW   = 'IN_REVIEW';
    public const STATUS_SCOPED      = 'SCOPED';
    public const STATUS_SCHEDULED   = 'SCHEDULED';
    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';
    public const STATUS_COMPLETED   = 'COMPLETED';
    public const STATUS_CANCELLED   = 'CANCELLED';

    protected $fillable = [
        'outlet_id',
        'created_by',
        'service_type',
        'scope_details',
        'preferred_date',
        'status',
        'custom_quote_amount',
        'super_admin_notes',
    ];

    protected $casts = [
        'preferred_date'      => 'date',
        'custom_quote_amount' => 'decimal:2',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
