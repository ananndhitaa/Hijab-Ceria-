<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Finance\OpeningCashBalance;
use App\Services\System\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OpeningCashBalanceController extends Controller
{
    /**
     * Display legacy opening cash balance view in Read-Only mode for Admin.
     * Redirects Owner to the authoritative /owner/opening-cash route.
     */
    public function edit(): View|RedirectResponse
    {
        // Authoritative redirect for Owner to owner route
        if (auth()->user()?->role === UserRole::OWNER) {
            return redirect()->route('owner.opening-cash.create')
                ->with('info', 'Penetapan dan pengelolaan saldo awal kas operasional dilakukan melalui menu Owner.');
        }

        $isIsolationEnabled = (bool) config('nayara.tenant_isolation_enabled', false);
        $outletId = $this->getActiveTenantOutletId();

        $current = $outletId !== null
            ? OpeningCashBalance::forOutlet($outletId)->latest('opening_date')->latest('id')->first()
            : OpeningCashBalance::latest('opening_date')->latest('id')->first();

        return view('admin.financial-records.opening-cash', compact('current', 'isIsolationEnabled'));
    }

    /**
     * Handle legacy store attempts.
     * Strictly blocks Admin mutations when multi-tenant isolation is active.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()?->role === UserRole::OWNER) {
            abort(403, 'Akses ditolak: Penetapan saldo awal kas dilakukan secara permanen melalui menu Owner.');
        }

        if (config('nayara.tenant_isolation_enabled', false)) {
            abort(403, 'Akses ditolak: Penetapan saldo awal kas hanya dapat dilakukan oleh Owner satu kali saat onboarding tenant.');
        }

        $outletId = $this->getActiveTenantOutletId();
        $isIsolationEnabled = false;

        $rules = [
            'amount'       => ['required', 'numeric', 'gte:0'],
            'opening_date' => ['required', 'date'],
            'notes'        => ['nullable', 'string', 'max:500'],
        ];

        $messages = [
            'amount.required'       => 'Nominal saldo awal kas wajib diisi.',
            'amount.numeric'        => 'Nominal saldo awal kas harus berupa angka.',
            'amount.gte'            => 'Nominal saldo awal kas tidak boleh negatif.',
            'opening_date.required' => 'Tanggal saldo awal kas wajib diisi.',
            'opening_date.date'     => 'Format tanggal saldo awal kas tidak valid.',
            'opening_date.unique'   => 'Tanggal saldo awal kas untuk outlet ini sudah terdaftar.',
            'notes.max'             => 'Catatan maksimal 500 karakter.',
        ];

        $validated = $request->validate($rules, $messages);

        if ($isIsolationEnabled && $outletId !== null) {
            $duplicateCutOff = OpeningCashBalance::forOutlet($outletId)
                ->whereDate('opening_date', $validated['opening_date'])
                ->exists();

            if ($duplicateCutOff) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'opening_date' => 'Tanggal saldo awal kas untuk outlet ini sudah terdaftar.',
                ]);
            }
        }

        DB::transaction(function () use ($validated, $request, $outletId, $isIsolationEnabled) {
            if ($isIsolationEnabled && $outletId !== null) {
                // Serialize opening balance updates for this tenant
                OpeningCashBalance::forOutlet($outletId)
                    ->lockForUpdate()
                    ->latest('opening_date')
                    ->latest('id')
                    ->first();

                OpeningCashBalance::create([
                    'outlet_id'    => $outletId,
                    'amount'       => $validated['amount'],
                    'opening_date' => $validated['opening_date'],
                    'notes'        => $validated['notes'] ?? null,
                    'created_by'   => $request->user()->id,
                ]);
            } else {
                OpeningCashBalance::create([
                    'amount'       => $validated['amount'],
                    'opening_date' => $validated['opening_date'],
                    'notes'        => $validated['notes'] ?? null,
                    'created_by'   => $request->user()->id,
                ]);
            }
        });

        return redirect()->route('admin.financial-records.index')
            ->with('success', 'Saldo awal kas operasional berhasil diperbarui.');
    }

    /**
     * Resolve active tenant outlet ID when tenant isolation is enabled.
     * Fails closed (aborts 403) when enabled but user/tenant is invalid.
     */
    protected function getActiveTenantOutletId(): ?int
    {
        if (! config('nayara.tenant_isolation_enabled', false)) {
            return null;
        }

        try {
            return app(TenantContext::class)->currentOutletId();
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage() ?: 'Akses ditolak: Pengguna tidak memiliki akses tenant yang sah.');
        }
    }
}
