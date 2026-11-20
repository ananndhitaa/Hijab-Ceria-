<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->isActive()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Akun Anda telah dinonaktifkan. Hubungi administrator.']);
        }

        // Section D: Gating onboarded tenant users from ERP routes when isolation is FALSE
        if ($request->user() && ! $request->user()->isSuperAdmin() && ! config('nayara.tenant_isolation_enabled', false)) {
            $user = $request->user();
            $isOnboardedTenant = false;
            if (\Illuminate\Support\Facades\Schema::hasTable('tenant_registrations')) {
                $isOnboardedTenant = \App\Models\Platform\TenantRegistration::where('outlet_id', $user->outlet_id)
                    ->orWhere('owner_user_id', $user->id)
                    ->exists();
            }
            if (! $isOnboardedTenant && $user->outlet_id !== null && $user->outlet_id > 1) {
                $isOnboardedTenant = true;
            }

            if ($isOnboardedTenant) {
                if ($request->is('admin/*', 'owner/*', 'gudang/*', 'produksi/*')) {
                    auth()->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect()->route('login')
                        ->withErrors(['email' => 'Sistem multi-tenant belum diaktifkan pada lingkungan ini. Akses tenant mandiri hanya tersedia saat isolasi multi-tenant aktif.']);
                }
            }
        }

        return $next($request);
    }
}
