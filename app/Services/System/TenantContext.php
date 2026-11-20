<?php

namespace App\Services\System;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\System\Outlet;
use App\Models\System\User;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Component for resolving legitimate tenant (Outlet) context.
 *
 * Design principles:
 * - Fail-closed: rejections throw AuthorizationException immediately.
 * - Single source of truth: database relationship users.outlet_id -> outlets.id.
 * - Untrusted client input: request parameters, query strings, headers, cookies are completely ignored.
 * - Stateless request lifecycle: no static mutable state, no cross-request tenant leakage.
 * - Zero fallback: never fall back to legacy outlet, first outlet, or hardcoded IDs.
 */
class TenantContext
{
    /**
     * Resolve the legitimate Outlet for the given user.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function outletFor(?User $user): Outlet
    {
        // 1. Fail-closed: Must be an authenticated user
        if (! $user) {
            throw new AuthorizationException('Akses ditolak: Pengguna belum terotentikasi.');
        }

        // 2. Fail-closed: User status must be active
        if ($user->status !== UserStatus::ACTIVE && $user->status !== UserStatus::ACTIVE->value) {
            throw new AuthorizationException('Akses ditolak: Akun pengguna sedang tidak aktif.');
        }

        // 3. Fail-closed: User role must be a known valid UserRole
        if (! $this->isValidUserRole($user)) {
            throw new AuthorizationException('Akses ditolak: Role pengguna tidak valid.');
        }

        // 4. Fail-closed: Platform role (SUPER_ADMIN) must NEVER access tenant ERP operational boundaries
        if ($user->role === UserRole::SUPER_ADMIN || $user->role === UserRole::SUPER_ADMIN->value) {
            throw new AuthorizationException('Akses ditolak: Super Admin tidak memiliki akses langsung ke data operasional tenant.');
        }

        // 5. Fail-closed: User must have an outlet_id assigned in database
        if ($user->outlet_id === null) {
            throw new AuthorizationException('Akses ditolak: Pengguna tidak terikat ke outlet konveksi mana pun.');
        }

        // 5. Query outlet explicitly by foreign key (prevents reliance on client input or stale cache)
        /** @var Outlet|null $outlet */
        $outlet = Outlet::find($user->outlet_id);

        if (! $outlet) {
            throw new AuthorizationException('Akses ditolak: Outlet yang terhubung ke pengguna tidak ditemukan.');
        }

        // 6. Fail-closed: Outlet status must be ACTIVE
        if (! $outlet->isActive()) {
            throw new AuthorizationException("Akses ditolak: Outlet '{$outlet->outlet_name}' sedang dinonaktifkan.");
        }

        return $outlet;
    }

    /**
     * Resolve the legitimate outlet ID for the given user.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function outletIdFor(?User $user): int
    {
        return $this->outletFor($user)->id;
    }

    /**
     * Determine whether the given user has a valid, active outlet.
     */
    public function hasValidOutlet(?User $user): bool
    {
        try {
            $this->outletFor($user);
            return true;
        } catch (AuthorizationException) {
            return false;
        }
    }

    /**
     * Resolve the legitimate Outlet for the currently authenticated user.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function currentOutlet(): Outlet
    {
        return $this->outletFor(auth()->user());
    }

    /**
     * Resolve the legitimate outlet ID for the currently authenticated user.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function currentOutletId(): int
    {
        return $this->outletIdFor(auth()->user());
    }

    /**
     * Stateless static helper to resolve outlet for a user.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public static function resolveOutlet(?User $user = null): Outlet
    {
        return (new self())->outletFor($user ?? auth()->user());
    }

    /**
     * Stateless static helper to resolve outlet ID for a user.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public static function resolveOutletId(?User $user = null): int
    {
        return (new self())->outletIdFor($user ?? auth()->user());
    }

    /**
     * Helper to validate user role against official UserRole enum cases.
     */
    protected function isValidUserRole(User $user): bool
    {
        $roleValue = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        $validRoles = array_map(fn (UserRole $role) => $role->value, UserRole::cases());

        return in_array($roleValue, $validRoles, true);
    }
}
