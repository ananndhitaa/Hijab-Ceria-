<?php

namespace App\Services\Sales;

use App\Enums\OrderStatus;
use App\Models\Sales\Order;
use Illuminate\Database\Eloquent\Collection;

class SalesReportService
{
    public function __construct(
        private InvoiceService $invoiceService
    ) {}

    /**
     * Realized sales order statuses that count towards Sales Revenue (PRD v1.5).
     * Strictly excludes KOMUNIKASI (pre-deal negotiation).
     */
    public const REVENUE_STATUSES = [
        OrderStatus::DEAL,
        OrderStatus::BAHAN,
        OrderStatus::PRINTING,
        OrderStatus::PRODUKSI,
        OrderStatus::QC,
        OrderStatus::PACKING,
        OrderStatus::DIKIRIM,
        OrderStatus::SELESAI,
    ];

    /**
     * Generate structured sales report data filtered by order_date (Asia/Jakarta).
     *
     * @param string $startDate (Y-m-d)
     * @param string $endDate (Y-m-d)
     * @return array{
     *   orders: Collection,
     *   rows: array,
     *   totalRevenue: string,
     *   totalPaid: string,
     *   totalOutstanding: string,
     *   totalQty: int,
     *   startDate: string,
     *   endDate: string
     * }
     */
    public function generateReport(string $startDate, string $endDate): array
    {
        $orders = Order::with([
            'customer',
            'items.variant.product',
            'invoice.payments',
        ])
        ->whereIn('status', self::REVENUE_STATUSES)
        ->whereBetween('order_date', [$startDate, $endDate])
        ->orderBy('order_date', 'desc')
        ->orderBy('id', 'desc')
        ->get();

        $totalRevenue = '0.00';
        $totalPaid = '0.00';
        $totalOutstanding = '0.00';
        $totalQty = 0;
        $rows = [];

        foreach ($orders as $order) {
            // Revenue calculated from snapshot selling prices of order items
            $orderRevenue = '0.00';
            $orderQty = 0;

            foreach ($order->items as $item) {
                $qty = (int) $item->qty_ordered;
                $price = (string) $item->unit_selling_price;
                $subtotal = bcmul((string) $qty, $price, 2);
                $orderRevenue = bcadd($orderRevenue, $subtotal, 2);
                $orderQty += $qty;
            }

            $totalRevenue = bcadd($totalRevenue, $orderRevenue, 2);
            $totalQty += $orderQty;

            $hasInvoice = $order->invoice !== null;
            if ($hasInvoice) {
                $invoice = $order->invoice;
                $paid = $this->invoiceService->calculatePaidAmount($invoice);
                $outstanding = $this->invoiceService->calculateOutstandingBalance($invoice);

                $totalPaid = bcadd($totalPaid, $paid, 2);
                $totalOutstanding = bcadd($totalOutstanding, $outstanding, 2);

                $invoiceNo = $invoice->invoice_no;
                $invoiceStatus = $invoice->status->label();
            } else {
                $paid = '0.00';
                $outstanding = null; // Unbilled order: outstanding is not applicable

                $invoiceNo = 'Belum dibuat';
                $invoiceStatus = '—';
            }

            $rows[] = [
                'order'          => $order,
                'order_date'     => $order->order_date ? $order->order_date->format('Y-m-d') : '',
                'order_no'       => $order->order_no,
                'customer_name'  => $order->customer->customer_name ?? '-',
                'total_qty'      => $orderQty,
                'revenue'        => $orderRevenue,
                'has_invoice'    => $hasInvoice,
                'invoice_no'     => $invoiceNo,
                'invoice_status' => $invoiceStatus,
                'paid_amount'    => $paid,
                'outstanding'    => $outstanding,
            ];
        }

        return [
            'orders'           => $orders,
            'rows'             => $rows,
            'totalRevenue'     => $totalRevenue,
            'totalPaid'        => $totalPaid,
            'totalOutstanding' => $totalOutstanding,
            'totalQty'         => $totalQty,
            'startDate'        => $startDate,
            'endDate'          => $endDate,
        ];
    }
}
