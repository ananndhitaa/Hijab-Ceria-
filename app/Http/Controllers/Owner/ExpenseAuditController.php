<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Finance\BusinessExpense;
use App\Models\Finance\BusinessExpenseHistory;
use App\Services\System\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\View\View;

class ExpenseAuditController extends Controller
{
    /**
     * Display the read-only expense audit history for authorized tenant Owner.
     */
    public function show(BusinessExpense $expense): View
    {
        if (! config('nayara.tenant_isolation_enabled', false)) {
            abort(404, 'Fitur riwayat audit multi-tenant tidak aktif.');
        }

        $outletId = $this->getActiveTenantOutletId();

        // Cross-tenant guard & unassigned legacy expense guard
        if ($expense->outlet_id === null || (int) $expense->outlet_id !== $outletId) {
            abort(404, 'Pengeluaran bisnis tidak ditemukan untuk outlet ini.');
        }

        $expense->load(['createdByUser', 'order.customer']);

        $histories = BusinessExpenseHistory::forOutlet($outletId)
            ->where('business_expense_id', $expense->id)
            ->with(['actorUser', 'oldOrder', 'newOrder'])
            ->orderByDesc('id')
            ->get();

        return view('owner.expenses.histories', compact('expense', 'histories'));
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
