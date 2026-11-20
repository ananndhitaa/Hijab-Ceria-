<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required'    => 'Email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $this->ensureIsNotRateLimited($request);

        if (! Auth::attempt([
            'email'    => $request->email,
            'password' => $request->password,
            'status'   => \App\Enums\UserStatus::ACTIVE->value,
        ], $request->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));

        /** @var \App\Models\System\User $user */
        $user = Auth::user();

        // Critical Tenant Activation Gate (Section D):
        // When tenant isolation is disabled (false), onboarded tenant users (from registration or secondary tenants)
        // must NOT gain unrestricted access to the legacy ERP.
        if (! config('nayara.tenant_isolation_enabled', false) && ! $user->isSuperAdmin()) {
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
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                throw ValidationException::withMessages([
                    'email' => 'Sistem multi-tenant saat ini belum diaktifkan pada lingkungan operasional ini. Akses tenant mandiri hanya tersedia saat isolasi multi-tenant aktif.',
                ]);
            }
        }

        $request->session()->regenerate();

        return $this->redirectBasedOnRole($user);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function redirectBasedOnRole($user): RedirectResponse
    {
        return match ($user->role) {
            UserRole::SUPER_ADMIN => redirect()->route('platform.dashboard'),
            UserRole::ADMIN       => redirect()->route('admin.dashboard'),
            UserRole::GUDANG      => redirect()->route('gudang.dashboard'),
            UserRole::PRODUKSI    => redirect()->route('produksi.dashboard'),
            UserRole::OWNER       => redirect()->route('owner.dashboard'),
            UserRole::CUSTOMER    => redirect()->route('customer.dashboard'),
            default               => redirect()->route('login'),
        };
    }

    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.",
        ]);
    }

    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->string('email')) . '|' . $request->ip());
    }
}
