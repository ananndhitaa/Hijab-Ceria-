<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\Finance\PeriodFinancialService;
use App\Services\System\TenantContext;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PerformanceController extends Controller
{
    public function __construct(
        private PeriodFinancialService $periodFinancialService
    ) {}

    /**
     * Display detailed Period Financial Performance analysis for Owner.
     */
    public function index(Request $request): View
    {
        $outletId = $this->getActiveTenantOutletId();

        $preset = $request->input('preset', 'this_month');
        $now = Carbon::now('Asia/Jakarta');

        [$startDate, $endDate] = match ($preset) {
            'today'       => [$now->toDateString(), $now->toDateString()],
            'last_7_days' => [$now->copy()->subDays(6)->toDateString(), $now->toDateString()],
            'this_month'  => [
                $now->copy()->startOfMonth()->toDateString(),
                $now->copy()->endOfMonth()->toDateString(),
            ],
            'last_month'  => [
                $now->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                $now->copy()->subMonthNoOverflow()->endOfMonth()->toDateString(),
            ],
            'all'         => [
                '2020-01-01',
                $now->toDateString(),
            ],
            'custom'      => [
                $request->input('start_date', $now->copy()->startOfMonth()->toDateString()),
                $request->input('end_date', $now->copy()->endOfMonth()->toDateString()),
            ],
            default       => [
                $now->copy()->startOfMonth()->toDateString(),
                $now->copy()->endOfMonth()->toDateString(),
            ],
        };

        // If custom was submitted with empty values, fallback gracefully
        if (empty($startDate)) {
            $startDate = $now->copy()->startOfMonth()->toDateString();
        }
        if (empty($endDate)) {
            $endDate = $now->copy()->endOfMonth()->toDateString();
        }

        $financials = $this->periodFinancialService->calculatePeriodFinancials($startDate, $endDate, $outletId);

        return view('owner.performance.index', compact(
            'financials',
            'preset',
            'startDate',
            'endDate'
        ));
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
