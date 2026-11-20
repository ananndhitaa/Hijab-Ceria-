<?php

namespace App\Http\Middleware;

use App\Services\System\TenantContext;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware for validating legitimate tenant (Outlet) context.
 *
 * Rules:
 * - Fails closed: authenticated user with active account and active outlet is strictly required.
 * - Single source of truth: resolves outlet via TenantContext from database relation, never from request.
 * - Stateless: does not store mutable state across requests.
 * - Complementary: does NOT replace RoleMiddleware or permission checks.
 */
class EnsureTenantOutlet
{
    public function __construct(
        protected TenantContext $tenantContext
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($request->expectsJson() || $request->is('api/*')) {
                abort(401, 'Unauthenticated.');
            }

            return redirect()->route('login');
        }

        try {
            // Resolves the legitimate outlet for the authenticated user.
            // Throws AuthorizationException if user is inactive, role invalid, outlet_id null, or outlet inactive.
            $this->tenantContext->outletFor($user);
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage() ?: 'Akses ditolak: Pengguna tidak memiliki akses tenant yang sah.');
        }

        return $next($request);
    }
}
