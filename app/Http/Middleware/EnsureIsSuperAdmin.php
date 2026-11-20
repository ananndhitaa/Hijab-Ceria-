<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->status !== UserStatus::ACTIVE && $user->status !== UserStatus::ACTIVE->value) {
            abort(403, 'Akses ditolak: Akun pengguna sedang dinonaktifkan.');
        }

        if ($user->role !== UserRole::SUPER_ADMIN && $user->role !== UserRole::SUPER_ADMIN->value) {
            abort(403, 'Akses ditolak: Hanya Super Admin platform yang memiliki akses ke area ini.');
        }

        return $next($request);
    }
}
