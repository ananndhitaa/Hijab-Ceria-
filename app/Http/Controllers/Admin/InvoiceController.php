<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Services\Sales\InvoiceService;
use App\Support\CsvSanitizer;
use DomainException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    public function index(Request $request, InvoiceService $invoiceService): View
    {
        $search = $request->input('search');
        $statusFilter = $request->input('status');

        $invoices = Invoice::with(['order.customer', 'payments'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_no', 'like', "%{$search}%")
                        ->orWhereHas('order', function ($oq) use ($search) {
                            $oq->where('order_no', 'like', "%{$search}%")
                                ->orWhereHas('customer', function ($cq) use ($search) {
                                    $cq->where('customer_name', 'like', "%{$search}%");
                                });
                        });
                });
            })
            ->when($statusFilter, function ($query, $status) {
                $query->where('status', $status);
            })
            ->latest('invoice_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $statuses = InvoiceStatus::cases();

        return view('admin.invoices.index', compact('invoices', 'search', 'statusFilter', 'statuses', 'invoiceService'));
    }

    public function show(Invoice $invoice, InvoiceService $invoiceService): View
    {
        $invoice->load([
            'order.customer',
            'order.items.variant.product',
            'payments',
        ]);

        $paidAmount = $invoiceService->calculatePaidAmount($invoice);
        $outstandingBalance = $invoiceService->calculateOutstandingBalance($invoice);
        $dpAmount = $invoiceService->calculateDpAmount($invoice);
        $hasConfirmedDp = $invoiceService->hasConfirmedDp($invoice);

        return view('admin.invoices.show', compact('invoice', 'paidAmount', 'outstandingBalance', 'dpAmount', 'hasConfirmedDp'));
    }

    public function print(Invoice $invoice, InvoiceService $invoiceService): View
    {
        $invoice->load([
            'order.customer',
            'order.outlet',
            'order.items.variant.product',
            'payments.confirmedByUser',
        ]);

        $paidAmount = $invoiceService->calculatePaidAmount($invoice);
        $outstandingBalance = $invoiceService->calculateOutstandingBalance($invoice);
        $dpAmount = $invoiceService->calculateDpAmount($invoice);
        $hasConfirmedDp = $invoiceService->hasConfirmedDp($invoice);

        return view('admin.invoices.print', compact('invoice', 'paidAmount', 'outstandingBalance', 'dpAmount', 'hasConfirmedDp'));
    }

    public function exportCsv(Invoice $invoice, InvoiceService $invoiceService): StreamedResponse
    {
        $invoice->load([
            'order.customer',
            'order.items.variant.product',
            'payments',
        ]);

        $paidAmount = $invoiceService->calculatePaidAmount($invoice);
        $outstandingBalance = $invoiceService->calculateOutstandingBalance($invoice);

        $filename = "invoice_{$invoice->invoice_no}.csv";

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () use ($invoice, $paidAmount, $outstandingBalance) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Nomor Invoice',
                'Tanggal Invoice',
                'Status Invoice',
                'Nomor Sales Order',
                'Customer',
                'Telepon',
                'Produk',
                'Varian',
                'SKU',
                'Qty',
                'Harga Satuan',
                'Subtotal',
                'Total Invoice',
                'Total Terbayar',
                'Sisa Tagihan',
            ]);

            foreach ($invoice->order->items as $item) {
                $row = [
                    $invoice->invoice_no,
                    $invoice->invoice_date ? $invoice->invoice_date->format('d/m/Y') : '',
                    $invoice->status->label(),
                    $invoice->order->order_no ?? '',
                    $invoice->order->customer->customer_name ?? '',
                    $invoice->order->customer->phone ?? '',
                    $item->variant->product->product_name ?? '',
                    $item->variant->variant_name ?? '',
                    $item->variant->sku ?? '',
                    $item->qty_ordered,
                    $item->unit_selling_price,
                    $item->subtotal,
                    $invoice->total_amount,
                    $paidAmount,
                    $outstandingBalance,
                ];

                fputcsv($handle, CsvSanitizer::sanitizeRow($row));
            }

            fclose($handle);
        }, 200, $headers);
    }

    public function store(Request $request, Order $order, InvoiceService $invoiceService): RedirectResponse
    {
        try {
            $invoice = $invoiceService->createForOrder($order);

            return redirect()->route('admin.invoices.show', $invoice)
                ->with('success', "Invoice {$invoice->invoice_no} berhasil diterbitkan untuk pesanan {$order->order_no}.");
        } catch (DomainException $e) {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', $e->getMessage());
        } catch (UniqueConstraintViolationException $e) {
            report($e);

            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'Nomor dokumen mengalami konflik saat dibuat. Silakan coba kembali.');
        }
    }
}
