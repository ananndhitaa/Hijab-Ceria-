<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBusinessIncomeRequest;
use App\Models\Finance\BusinessIncome;
use App\Services\System\DocumentNumberGenerator;
use App\Services\System\TenantContext;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

use Illuminate\Support\Facades\DB;

class BusinessIncomeController extends Controller
{
    public function create(): View
    {
        $this->getActiveTenantOutletId();

        $categories = BusinessIncome::CATEGORIES;

        return view('admin.incomes.create', compact('categories'));
    }

    public function store(
        StoreBusinessIncomeRequest $request,
        DocumentNumberGenerator $numberGenerator
    ): RedirectResponse {
        $outletId = $this->getActiveTenantOutletId();
        $isIsolationEnabled = (bool) config('nayara.tenant_isolation_enabled', false);
        $validated = $request->validated();
        $date = Carbon::parse($validated['income_date']);

        $incomeNo = DB::transaction(function () use ($validated, $date, $numberGenerator, $outletId, $isIsolationEnabled) {
            if ($isIsolationEnabled && $outletId !== null) {
                // Level 1: Stable Tenant Cash Mutex - Lock authorized tenant Outlet row
                $lockedOutlet = \App\Models\System\Outlet::whereKey($outletId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $lockedOutlet->isActive()) {
                    abort(403, 'Akses ditolak: Outlet tenant tidak aktif.');
                }
            }

            $incomeNo = $numberGenerator->generate('INC', $date);

            $payload = [
                'income_no'   => $incomeNo,
                'income_date' => $validated['income_date'],
                'category'    => $validated['category'],
                'amount'      => $validated['amount'],
                'notes'       => $validated['notes'] ?? null,
                'created_by'  => auth()->id(),
            ];

            if ($isIsolationEnabled && $outletId !== null) {
                $payload['outlet_id'] = $outletId;
            }

            BusinessIncome::create($payload);

            return $incomeNo;
        });

        return redirect()
            ->route('admin.financial-records.index')
            ->with('success', "Pemasukan kas {$incomeNo} berhasil dicatat.");
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
