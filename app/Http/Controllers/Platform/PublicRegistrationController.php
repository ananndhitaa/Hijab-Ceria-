<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\PublicTenantRegistrationRequest;
use App\Models\Platform\TenantRegistration;
use App\Models\Platform\TenantRegistrationHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PublicRegistrationController extends Controller
{
    /**
     * Show the free public registration form for independent convection companies.
     */
    public function create(): View
    {
        return view('platform.auth.register_tenant');
    }

    /**
     * Handle public tenant registration submission.
     */
    public function store(PublicTenantRegistrationRequest $request): RedirectResponse
    {
        $this->ensureIsNotRateLimited($request);

        $validated = $request->validated();

        $registration = DB::transaction(function () use ($validated) {
            $reg = TenantRegistration::create([
                'company_name' => strip_tags(trim($validated['company_name'])),
                'owner_name'   => strip_tags(trim($validated['owner_name'])),
                'email'        => strtolower(trim($validated['email'])),
                'phone_wa'     => strip_tags(trim($validated['phone_wa'])),
                'address'      => isset($validated['address']) ? strip_tags(trim($validated['address'])) : null,
                'notes'        => isset($validated['notes']) ? strip_tags(trim($validated['notes'])) : null,
                'password'     => Hash::make($validated['password']),
                'status'       => TenantRegistration::STATUS_PENDING,
            ]);

            TenantRegistrationHistory::create([
                'tenant_registration_id' => $reg->id,
                'action'                 => 'SUBMIT',
                'from_status'            => null,
                'to_status'              => TenantRegistration::STATUS_PENDING,
                'notes'                  => 'Pengajuan pendaftaran mandiri (Free Registration) berhasil dibuat.',
                'actor_id'               => null,
            ]);

            return $reg;
        });

        RateLimiter::hit($this->throttleKey($request));

        return redirect()->route('tenant.register.success')
            ->with('registration_id', $registration->id)
            ->with('company_name', $registration->company_name)
            ->with('email', $registration->email);
    }

    /**
     * Show registration submission success page.
     */
    public function success(): View
    {
        return view('platform.auth.register_success');
    }

    /**
     * Applicant status lookup form & result.
     */
    public function checkStatus(Request $request): View
    {
        $registration = null;

        if ($request->filled('email') && $request->filled('phone_wa')) {
            $registration = TenantRegistration::with('histories')
                ->where('email', strtolower(trim($request->input('email'))))
                ->where('phone_wa', trim($request->input('phone_wa')))
                ->latest()
                ->first();
        }

        return view('platform.auth.register_status', compact('registration'));
    }

    /**
     * Resubmit application after correcting requested revision.
     */
    public function resubmit(Request $request, TenantRegistration $registration): RedirectResponse
    {
        if (! $registration->isNeedsRevision()) {
            return redirect()->back()->with('error', 'Pengajuan ini tidak berada dalam status revisi.');
        }

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:150'],
            'owner_name'   => ['required', 'string', 'max:150'],
            'phone_wa'     => ['required', 'string', 'max:50'],
            'address'      => ['nullable', 'string', 'max:1000'],
            'notes'        => ['nullable', 'string', 'max:1000'],
        ], [
            'company_name.required' => 'Nama konveksi/perusahaan wajib diisi.',
            'owner_name.required'   => 'Nama pemilik wajib diisi.',
            'phone_wa.required'     => 'Nomor telepon/WA wajib diisi.',
        ]);

        DB::transaction(function () use ($registration, $validated) {
            $oldStatus = $registration->status;

            $registration->update([
                'company_name' => strip_tags(trim($validated['company_name'])),
                'owner_name'   => strip_tags(trim($validated['owner_name'])),
                'phone_wa'     => strip_tags(trim($validated['phone_wa'])),
                'address'      => isset($validated['address']) ? strip_tags(trim($validated['address'])) : null,
                'notes'        => isset($validated['notes']) ? strip_tags(trim($validated['notes'])) : null,
                'status'       => TenantRegistration::STATUS_PENDING,
            ]);

            TenantRegistrationHistory::create([
                'tenant_registration_id' => $registration->id,
                'action'                 => 'RESUBMIT',
                'from_status'            => $oldStatus,
                'to_status'              => TenantRegistration::STATUS_PENDING,
                'notes'                  => 'Pemohon telah memperbarui data dan mengajukan ulang pendaftaran.',
                'actor_id'               => null,
            ]);
        });

        return redirect()->route('tenant.register.status', [
            'email'    => $registration->email,
            'phone_wa' => $registration->phone_wa,
        ])->with('success', 'Perbaikan pendaftaran berhasil dikirim. Tim Super Admin akan meninjau kembali pengajuan Anda.');
    }

    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => "Terlalu banyak pengajuan pendaftaran dari perangkat ini. Harap tunggu {$seconds} detik.",
        ]);
    }

    protected function throttleKey(Request $request): string
    {
        return 'tenant_reg|' . $request->ip();
    }
}
