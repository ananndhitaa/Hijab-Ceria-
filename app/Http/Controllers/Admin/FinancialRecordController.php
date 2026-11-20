<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Finance\FinancialRecordService;
use App\Services\System\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinancialRecordController extends Controller
{
    public function __construct(
        protected FinancialRecordService $financialRecordService
    ) {}

    /**
     * Display consolidated financial records (Uang Masuk, Uang Keluar, Arus Bersih).
     */
    public function index(Request $request): View
    {
        $outletId = $this->getActiveTenantOutletId();

        $filters = [
            'start_date' => $request->query('start_date'),
            'end_date'   => $request->query('end_date'),
            'flow'       => $request->query('flow'),
            'type'       => $request->query('type', 'all'),
            'search'     => $request->query('search', $request->query('q')),
            'q'          => $request->query('q', $request->query('search')),
            'per_page'   => 15,
            'page'       => (int) $request->query('page', 1),
        ];

        $data = $this->financialRecordService->getConsolidatedRecords($filters, $outletId);

        return view('admin.financial-records.index', [
            'records'             => $data['records'],
            'summary'             => $data['summary'],
            'cashPosition'        => $data['cash_position'],
            'salesSummary'        => $data['sales_summary'],
            'procurementSummary'  => $data['procurement_summary'],
            'modalSummary'        => $data['modal_summary'],
            'filters'             => $data['filters'],
        ]);
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
