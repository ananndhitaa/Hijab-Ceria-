<?php

namespace App\Models\System;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Master\Customer;
use App\Models\System\PasswordResetRequest;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Create a new factory instance for the model.
     * Override needed because model moved to System\ subdomain
     * while factory stays at Database\Factories\UserFactory.
     */
    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'outlet_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'role'              => UserRole::class,
            'status'            => UserStatus::class,
        ];
    }

    // ── Scopes ────────────────────────────────────────────────────────

    public function scopeForOutlet(\Illuminate\Database\Eloquent\Builder $query, int $outletId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('outlet_id', $outletId);
    }

    // ── Relationships ─────────────────────────────────────────────────

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class);
    }

    public function passwordResetRequests(): HasMany
    {
        return $this->hasMany(PasswordResetRequest::class);
    }

    public function assignedProductionBatches(): HasMany
    {
        return $this->hasMany(\App\Models\Production\ProductionBatch::class, 'production_employee_id');
    }

    // ── Role Helpers ──────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isGudang(): bool
    {
        return $this->role === UserRole::GUDANG;
    }

    public function isProduksi(): bool
    {
        return $this->role === UserRole::PRODUKSI;
    }

    public function isOwner(): bool
    {
        return $this->role === UserRole::OWNER;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SUPER_ADMIN;
    }

    public function isCustomer(): bool
    {
        return $this->role === UserRole::CUSTOMER;
    }

    public function isInternal(): bool
    {
        return in_array($this->role, [
            UserRole::ADMIN,
            UserRole::GUDANG,
            UserRole::PRODUKSI,
            UserRole::OWNER,
        ]);
    }

    public function hasRole(UserRole|string ...$roles): bool
    {
        $roleValues = array_map(
            fn ($r) => $r instanceof UserRole ? $r->value : $r,
            $roles
        );

        return in_array($this->role->value, $roleValues);
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::ACTIVE;
    }
}
