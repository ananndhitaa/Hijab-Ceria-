<?php

namespace App\Models\Platform;

use App\Models\System\Outlet;
use App\Models\System\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenantRegistration extends Model
{
    public const STATUS_PENDING        = 'PENDING';
    public const STATUS_NEEDS_REVISION = 'NEEDS_REVISION';
    public const STATUS_APPROVED       = 'APPROVED';
    public const STATUS_REJECTED       = 'REJECTED';

    protected $fillable = [
        'company_name',
        'owner_name',
        'email',
        'phone_wa',
        'address',
        'notes',
        'password',
        'status',
        'revision_notes',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
        'outlet_id',
        'owner_user_id',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'outlet_id');
    }

    public function ownerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(TenantRegistrationHistory::class, 'tenant_registration_id')->orderBy('created_at', 'desc');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isNeedsRevision(): bool
    {
        return $this->status === self::STATUS_NEEDS_REVISION;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING        => 'Menunggu Review',
            self::STATUS_NEEDS_REVISION => 'Perlu Revisi',
            self::STATUS_APPROVED       => 'Disetujui',
            self::STATUS_REJECTED       => 'Ditolak',
            default                     => $this->status,
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING        => 'badge-warning',
            self::STATUS_NEEDS_REVISION => 'badge-amber',
            self::STATUS_APPROVED       => 'badge-success',
            self::STATUS_REJECTED       => 'badge-danger',
            default                     => 'badge-secondary',
        };
    }
}
