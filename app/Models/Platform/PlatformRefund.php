<?php

namespace App\Models\Platform;

use App\Models\System\Outlet;
use App\Models\System\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformRefund extends Model
{
    public const TYPE_FULL_100_PERCENT = 'FULL_100_PERCENT';
    public const TYPE_PROPORTIONAL     = 'PROPORTIONAL';

    public const STATUS_REQUESTED = 'REQUESTED';
    public const STATUS_APPROVED  = 'APPROVED';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_REJECTED  = 'REJECTED';

    protected $fillable = [
        'outlet_id',
        'platform_invoice_id',
        'ad_campaign_id',
        'refund_type',
        'amount',
        'reason',
        'status',
        'destination_bank_name',
        'destination_account_number',
        'destination_account_name',
        'transfer_proof_path',
        'approved_by',
        'approved_at',
        'completed_by',
        'completed_at',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'approved_at'  => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PlatformInvoice::class, 'platform_invoice_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class, 'ad_campaign_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
