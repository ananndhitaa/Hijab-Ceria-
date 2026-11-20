<?php

namespace App\Http\Controllers\Auth;

use App\Enums\PasswordResetStatus;
use App\Http\Controllers\Controller;
use App\Models\System\PasswordResetRequest;
use App\Models\System\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function showForm(): View
    {
        return view('auth.forgot-password');
    }

    public function submitRequest(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email'    => 'Format email tidak valid.',
        ]);

        $this->ensureIsNotRateLimited($request);

        $user = User::where('email', $request->email)->first();

        if ($user) {
            // Prevent duplicate pending requests for same user
            $existingPending = PasswordResetRequest::where('user_id', $user->id)
                ->where('status', PasswordResetStatus::PENDING)
                ->exists();

            if (! $existingPending) {
                PasswordResetRequest::create([
                    'user_id'      => $user->id,
                    'status'       => PasswordResetStatus::PENDING,
                    'requested_at' => now(),
                ]);
            }
        }

        RateLimiter::hit($this->throttleKey($request), 300);

        // Always show the same generic message regardless of whether the email exists
        return redirect()->route('password.request')
            ->with('status', 'Jika email Anda terdaftar dalam sistem, permintaan reset password telah dikirim ke Administrator untuk ditinjau.');
    }

    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 3)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        abort(429, "Terlalu banyak permintaan. Coba lagi dalam {$seconds} detik.");
    }

    protected function throttleKey(Request $request): string
    {
        return 'forgot-password|' . Str::transliterate(Str::lower($request->string('email')) . '|' . $request->ip());
    }
}
