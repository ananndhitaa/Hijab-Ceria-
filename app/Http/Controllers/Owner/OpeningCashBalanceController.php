<?php

namespace App\Http\Controllers\Owner;

use App\Enums\PurchaseApprovalStatus;
use App\Enums\PurchaseStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Finance\BusinessExpense;
use App\Models\Finance\BusinessIncome;
use App\Models\Finance\OpeningCashBalance;
use App\Models\Inventory\Purchase;
use App\Models\Inventory\PurchasePayment;
use App\Models\Sales\Payment;
use App\Models\System\Outlet;
use App\Services\System\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OpeningCashBalanceController extends Controller
{
    /**
     * Display the initial opening cash form or permanent summary.
     */
    public function create(): View
    {
        if (! config('nayara.tenant_isolation_enabled', false)) {
            abort(404, 'Fitur saldo awal kas multi-tenant tidak aktif.');
        }

        $outletId = $this->getActiveTenantOutletId();

        $current = OpeningCashBalance::forOutlet($outletId)->first();

        $hasFinancialHistory = false;
        if (! $current) {
            $hasFinancialHistory = $this->outletHasFinancialHistory($outletId);
        }

        return view('owner.opening-cash.create', compact('current', 'hasFinancialHistory'));
    }

    /**
     * Store the single, permanent initial opening cash balance for the tenant.
     */
    public function store(Request $request): RedirectResponse
    {
        if (! config('nayara.tenant_isolation_enabled', false)) {
            abort(404, 'Fitur saldo awal kas multi-tenant tidak aktif.');
        }

        if ($request->user()?->role !== UserRole::OWNER) {
            abort(403, 'Akses ditolak: Hanya Owner yang berwenang menetapkan saldo awal kas.');
        }

        $outletId = $this->getActiveTenantOutletId();

        $validated = $request->validate([
            'amount'            => ['required', 'numeric', 'gte:0'],
            'opening_date'      => ['required', 'date', 'before_or_equal:today'],
            'notes'             => ['nullable', 'string', 'max:500'],
            'verified_by_owner' => ['accepted'],
        ], [
            'amount.required'             => 'Nominal saldo awal kas wajib diisi.',
            'amount.numeric'              => 'Nominal saldo awal kas harus berupa angka.',
            'amount.gte'                  => 'Nominal saldo awal kas tidak boleh negatif.',
            'opening_date.required'       => 'Tanggal saldo awal kas wajib diisi.',
            'opening_date.date'           => 'Format tanggal saldo awal kas tidak valid.',
            'opening_date.before_or_equal'=> 'Tanggal saldo awal kas tidak boleh di masa depan.',
            'verified_by_owner.accepted'  => 'Anda wajib mengonfirmasi verifikasi fisik/rekening saldo awal kas.',
            'notes.max'                   => 'Catatan maksimal 500 karakter.',
        ]);

        DB::transaction(function () use ($validated, $request, $outletId) {
            // Lock tenant Outlet row to serialize onboarding for this tenant
            /** @var Outlet $outlet */
            $outlet = Outlet::whereKey($outletId)->lockForUpdate()->firstOrFail();

            if (! $outlet->isActive()) {
                abort(403, 'Akses ditolak: Outlet tidak aktif.');
            }

            // Check if opening cash already exists for this tenant
            if (OpeningCashBalance::forOutlet($outletId)->exists()) {
                throw ValidationException::withMessages([
                    'amount' => 'Saldo awal kas untuk outlet ini sudah ditetapkan dan bersifat permanen (satu kali).',
                ]);
            }

            // Audit whether tenant already has active financial history (Section J)
            if ($this->outletHasFinancialHistory($outletId)) {
                throw ValidationException::withMessages([
                    'amount' => 'Outlet telah memiliki riwayat transaksi keuangan aktif. Penetapan saldo awal kas memerlukan prosedur rekonsiliasi historis resmi.',
                ]);
            }

            // Insert exactly one OpeningCashBalance row stamped server-side
            OpeningCashBalance::create([
                'outlet_id'    => $outletId,
                'amount'       => $validated['amount'],
                'opening_date' => $validated['opening_date'],
                'notes'        => $validated['notes'] ?? null,
                'created_by'   => $request->user()->id,
            ]);
        });

        return redirect()->route('owner.opening-cash.create')
            ->with('success', 'Saldo awal kas operasional berhasil ditetapkan secara permanen.');
    }

    /**
     * Audit whether the tenant already has financial history that would conflict with a new opening balance.
     */
    protected function outletHasFinancialHistory(int $outletId): bool
    {
        return Payment::whereHas('invoice.order', fn ($q) => $q->where('outlet_id', $outletId))->exists()
            || PurchasePayment::whereHas('purchase', fn ($q) => $q->where('outlet_id', $outletId))->exists()
            || BusinessIncome::where('outlet_id', $outletId)->exists()
            || BusinessExpense::where('outlet_id', $outletId)->exists()
            || Purchase::where('outlet_id', $outletId)
                ->where('status', '!=', PurchaseStatus::CANCELLED)
                ->where('approval_status', PurchaseApprovalStatus::APPROVED)
                ->exists();
    }

    /**
     * Resolve active tenant outlet ID for authenticated OWNER.
     * Fails closed (aborts 403) on invalid/unbound user or inactive outlet.
     */
    protected function getActiveTenantOutletId(): int
    {
        try {
            return app(TenantContext::class)->currentOutletId();
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage() ?: 'Akses ditolak: Pengguna tidak memiliki akses tenant yang sah.');
        }
    }
}
