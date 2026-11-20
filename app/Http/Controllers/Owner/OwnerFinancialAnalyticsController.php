<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\Platform\FinancialAnalyticsService;
use App\Services\Platform\TenantEntitlementService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OwnerFinancialAnalyticsController extends Controller
{
    public function __construct(
        protected FinancialAnalyticsService $analyticsService,
        protected TenantEntitlementService $entitlementService
    ) {}

    /**
     * Display Owner financial analytics dashboard.
     */
    public function index(Request $request): View
    {
        $outletId = (int) (auth()->user()?->outlet_id ?? 0);

        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        $hasEntitlement = $outletId > 0 && $this->entitlementService->hasFinancialAnalyticsEntitlement($outletId);
        $analytics = $this->analyticsService->getAnalytics($outletId, $startDate, $endDate);

        $pageTitle = 'Financial Analytics';
        $breadcrumb = 'Executive Monitoring > Financial Analytics';

        return view('owner.analytics.index', compact('analytics', 'startDate', 'endDate', 'hasEntitlement', 'pageTitle', 'breadcrumb'));
    }

    /**
     * Export financial analytics data to CSV (entitlement required).
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $outletId = (int) (auth()->user()?->outlet_id ?? 0);

        if ($outletId === 0 || ! $this->entitlementService->canExportFinancialReports($outletId)) {
            throw ValidationException::withMessages([
                'entitlement' => ['Fitur ekspor laporan memerlukan paket Premium atau Financial Analytics Add-On aktif.'],
            ]);
        }

        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        return $this->analyticsService->exportCsv($outletId, $startDate, $endDate);
    }

    /**
     * Export/print financial analytics to PDF (entitlement required).
     */
    public function exportPdf(Request $request): Response
    {
        $outletId = (int) (auth()->user()?->outlet_id ?? 0);

        if ($outletId === 0 || ! $this->entitlementService->canExportFinancialReports($outletId)) {
            throw ValidationException::withMessages([
                'entitlement' => ['Fitur ekspor laporan PDF memerlukan paket Premium atau Financial Analytics Add-On aktif.'],
            ]);
        }

        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        return $this->analyticsService->exportPdfHtml($outletId, $startDate, $endDate);
    }
}
