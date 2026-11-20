<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Sales\SalesReportService;
use App\Support\CsvSanitizer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesReportController extends Controller
{
    public function index(Request $request, SalesReportService $reportService): View
    {
        $startDate = $request->input('start_date', Carbon::now('Asia/Jakarta')->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now('Asia/Jakarta')->endOfMonth()->toDateString());

        $report = $reportService->generateReport($startDate, $endDate);

        return view('admin.reports.sales', compact('report', 'startDate', 'endDate'));
    }

    public function exportCsv(Request $request, SalesReportService $reportService): StreamedResponse
    {
        $startDate = $request->input('start_date', Carbon::now('Asia/Jakarta')->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now('Asia/Jakarta')->endOfMonth()->toDateString());

        $report = $reportService->generateReport($startDate, $endDate);

        $filename = "laporan_penjualan_{$startDate}_{$endDate}.csv";

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () use ($report) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel
            fwrite($handle, "\xEF\xBB\xBF");

            // Header row
            fputcsv($handle, [
                'Tanggal Order',
                'No Sales Order',
                'Customer',
                'Total Qty',
                'Total Pendapatan',
                'No Invoice',
                'Status Invoice',
                'Total Terbayar',
                'Sisa Tagihan',
            ]);

            foreach ($report['rows'] as $row) {
                $csvRow = [
                    $row['order_date'],
                    $row['order_no'],
                    $row['customer_name'],
                    $row['total_qty'],
                    $row['revenue'],
                    $row['invoice_no'],
                    $row['invoice_status'],
                    $row['paid_amount'],
                    $row['outstanding'] ?? '—',
                ];

                fputcsv($handle, CsvSanitizer::sanitizeRow($csvRow));
            }

            // Summary row at the bottom
            $summaryRow = [
                'TOTAL',
                '',
                '',
                $report['totalQty'],
                $report['totalRevenue'],
                '',
                '',
                $report['totalPaid'],
                $report['totalOutstanding'],
            ];

            fputcsv($handle, CsvSanitizer::sanitizeRow($summaryRow));

            fclose($handle);
        }, 200, $headers);
    }
}
