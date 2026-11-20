<?php

namespace App\Services\Platform;

use App\Models\Finance\BusinessExpense;
use App\Models\Sales\Order;
use App\Services\Finance\OwnerDashboardAnalyticsService;
use App\Services\Finance\PeriodFinancialService;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancialAnalyticsService
{
    public function __construct(
        protected TenantEntitlementService $entitlementService,
        protected OwnerDashboardAnalyticsService $dashboardAnalyticsService,
        protected PeriodFinancialService $periodFinancialService
    ) {}

    /**
     * Get complete financial analytics for a tenant outlet over a date range.
     * Advanced charts are provided; checks entitlement for premium analytics flags.
     */
    public function getAnalytics(int $outletId, string $startDate, string $endDate): array
    {
        $hasEntitlement = $this->entitlementService->hasFinancialAnalyticsEntitlement($outletId);

        // Core financial figures (available free)
        $summary = $this->periodFinancialService->calculatePeriodFinancials($startDate, $endDate, $outletId);

        // Advanced charts & trends (entitlement provides full breakdown)
        $dashboardData = [];
        if ($hasEntitlement) {
            $dashboardData = $this->dashboardAnalyticsService->getDashboardAnalytics(
                startDate: $startDate,
                endDate: $endDate,
                trailingMonths: 6,
                outletId: $outletId
            );
        }

        $confirmedPayments = $summary['payment_received'] ?? '0.00';
        $cashOut = $summary['total_pengeluaran'] ?? '0.00';

        return [
            'has_entitlement' => $hasEntitlement,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'metrics' => [
                'revenue' => $summary['revenue'] ?? '0.00',
                'confirmed_payments' => number_format((float) $confirmedPayments, 2, '.', ''),
                'hpp' => $summary['hpp'] ?? null,
                'finalized_hpp' => $summary['finalized_hpp'] ?? ($summary['hpp_final_available'] ?? '0.00'),
                'is_hpp_final' => (bool) ($summary['is_ready'] ?? false),
                'gross_profit' => $summary['gross_profit'] ?? null,
                'gross_margin' => $summary['gross_margin'] ?? null,
                'expenses' => $summary['business_expenses'] ?? '0.00',
                'net_profit' => $summary['net_profit'] ?? null,
                'net_margin' => $summary['net_margin'] ?? null,
                'cash_in' => number_format((float) $confirmedPayments, 2, '.', ''),
                'cash_out' => number_format((float) $cashOut, 2, '.', ''),
                'piutang_customer' => $summary['piutang_customer'] ?? '0.00',
                'kas_tersedia' => $summary['kas_tersedia'] ?? '0.00',
                'komitmen_po' => $summary['komitmen_po'] ?? '0.00',
                'supplier_payments' => $summary['supplier_payments'] ?? '0.00',
                'roi' => $summary['roi'] ?? null,
                'roi_label' => $summary['roi_label'] ?? 'Belum dapat dihitung',
                'cash_to_supplier_ratio' => $summary['roi'] ?? null,
                'cash_to_supplier_ratio_label' => $summary['roi_label'] ?? 'Belum dapat dihitung',
            ],
            'summary' => $summary,
            'charts' => $dashboardData,
        ];
    }

    /**
     * Export financial summary as CSV/Excel with strict server-side entitlement check.
     *
     * @throws ValidationException
     */
    public function exportCsv(int $outletId, string $startDate, string $endDate): StreamedResponse
    {
        if (! $this->entitlementService->canExportFinancialReports($outletId)) {
            throw ValidationException::withMessages([
                'entitlement' => ['Fitur ekspor laporan memerlukan paket Premium atau Financial Analytics Add-On aktif.'],
            ]);
        }

        $analytics = $this->getAnalytics($outletId, $startDate, $endDate);
        $metrics = $analytics['metrics'];

        $filename = "laporan-keuangan-outlet-{$outletId}-{$startDate}-sd-{$endDate}.csv";

        return response()->streamDownload(function () use ($analytics, $metrics, $startDate, $endDate, $outletId) {
            $handle = fopen('php://output', 'w');
            // BOM for UTF-8 Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['LAPORAN KEUANGAN BISNIS NAYARA SAAS']);
            fputcsv($handle, ['Outlet ID', $outletId]);
            fputcsv($handle, ['Periode', "{$startDate} s/d {$endDate}"]);
            fputcsv($handle, ['Waktu Unduh', now()->format('Y-m-d H:i:s')]);
            fputcsv($handle, []);

            fputcsv($handle, ['RINGKASAN METRIK', 'NILAI (RP)']);
            fputcsv($handle, ['Pendapatan (Revenue)', $metrics['revenue']]);
            fputcsv($handle, ['Pembayaran Terkonfirmasi', $metrics['confirmed_payments']]);
            fputcsv($handle, ['HPP (Harga Pokok Penjualan)', $metrics['hpp'] ?? 'Belum Final']);
            fputcsv($handle, ['Laba Kotor (Gross Profit)', $metrics['gross_profit'] ?? 'Belum Final']);
            fputcsv($handle, ['Beban Operasional (Expenses)', $metrics['expenses']]);
            fputcsv($handle, ['Laba Bersih (Net Profit)', $metrics['net_profit'] ?? 'Belum Final']);
            fputcsv($handle, ['Arus Kas Masuk', $metrics['cash_in']]);
            fputcsv($handle, ['Arus Kas Keluar', $metrics['cash_out']]);
            fputcsv($handle, []);

            // Trailing revenue trend
            if (! empty($analytics['charts']['revenue_trend'])) {
                fputcsv($handle, ['TREN PENDAPATAN BULANAN', 'PENDAPATAN']);
                foreach ($analytics['charts']['revenue_trend'] as $row) {
                    fputcsv($handle, [$row['month'] ?? '', $row['revenue'] ?? '0.00']);
                }
                fputcsv($handle, []);
            }

            // Expense categories
            if (! empty($analytics['charts']['expense_composition']['categories'])) {
                fputcsv($handle, ['KOMPOSISI BEBAN OPERASIONAL', 'JUMLAH']);
                foreach ($analytics['charts']['expense_composition']['categories'] as $cat) {
                    fputcsv($handle, [$cat['label'] ?? '', $cat['amount'] ?? '0.00']);
                }
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Export printable financial summary (PDF/Print view) with strict entitlement check.
     *
     * @throws ValidationException
     */
    public function exportPdfHtml(int $outletId, string $startDate, string $endDate): Response
    {
        if (! $this->entitlementService->canExportFinancialReports($outletId)) {
            throw ValidationException::withMessages([
                'entitlement' => ['Fitur ekspor laporan PDF memerlukan paket Premium atau Financial Analytics Add-On aktif.'],
            ]);
        }

        $analytics = $this->getAnalytics($outletId, $startDate, $endDate);

        $html = view('owner.analytics.export-pdf', compact('analytics', 'outletId', 'startDate', 'endDate'))->render();

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }
}
