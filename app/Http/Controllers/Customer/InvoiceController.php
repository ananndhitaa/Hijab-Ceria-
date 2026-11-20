<?php

namespace App\Http\Controllers\Customer;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\Sales\Invoice;
use App\Services\Sales\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService
    ) {}

    /**
     * Display a paginated listing of invoices belonging strictly to the authenticated customer.
     */
    public function index(Request $request): View
    {
        $customer = $request->user()?->customer;
        $search = trim((string) $request->input('search', ''));
        $statusFilter = $request->input('status');
        $statuses = InvoiceStatus::cases();

        if (! $customer) {
            $invoices = Invoice::whereRaw('1 = 0')->paginate(10)->withQueryString();
            $stats = [
                'total_count'       => 0,
                'total_amount'      => '0.00',
                'unpaid_count'      => 0,
                'unpaid_balance'    => '0.00',
                'paid_count'        => 0,
            ];

            return view('customer.invoices.index', compact('invoices', 'search', 'statusFilter', 'statuses', 'stats'));
        }

        // Strongly scoped query: Customer can only retrieve invoices for their own orders
        $query = Invoice::whereHas('order', function ($q) use ($customer) {
            $q->where('customer_id', $customer->id);
        })->with(['order', 'payments']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                  ->orWhereHas('order', function ($orderQ) use ($search) {
                      $orderQ->where('order_no', 'like', "%{$search}%");
                  });
            });
        }

        if (! empty($statusFilter) && InvoiceStatus::tryFrom($statusFilter)) {
            $query->where('status', $statusFilter);
        }

        $invoices = $query->latest('invoice_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        // Calculate KPI summary for this customer
        $allInvoices = Invoice::whereHas('order', function ($q) use ($customer) {
            $q->where('customer_id', $customer->id);
        })->with('payments')->get();

        $totalCount = $allInvoices->count();
        $totalAmount = '0.00';
        $unpaidCount = 0;
        $unpaidBalance = '0.00';
        $paidCount = 0;

        foreach ($allInvoices as $inv) {
            $totalAmount = bcadd($totalAmount, (string) $inv->total_amount, 2);
            $paid = $this->invoiceService->calculatePaidAmount($inv);
            $outstanding = $this->invoiceService->calculateOutstandingBalance($inv);

            if ($inv->status === InvoiceStatus::PAID) {
                $paidCount++;
            } else {
                $unpaidCount++;
                $unpaidBalance = bcadd($unpaidBalance, $outstanding, 2);
            }
        }

        $stats = [
            'total_count'    => $totalCount,
            'total_amount'   => $totalAmount,
            'unpaid_count'   => $unpaidCount,
            'unpaid_balance' => $unpaidBalance,
            'paid_count'     => $paidCount,
        ];

        return view('customer.invoices.index', compact('invoices', 'search', 'statusFilter', 'statuses', 'stats'));
    }

    /**
     * Display printable invoice strictly belonging to the authenticated customer.
     */
    public function print(Invoice $invoice): View
    {
        $customer = request()->user()?->customer;
        if (! $customer || (int) $invoice->order?->customer_id !== (int) $customer->id) {
            abort(403, 'Akses ditolak: Faktur ini bukan milik akun pelanggan Anda.');
        }

        $invoice->load([
            'order.customer',
            'order.outlet',
            'order.items.variant.product',
            'payments.confirmedByUser',
        ]);

        $paidAmount = $this->invoiceService->calculatePaidAmount($invoice);
        $outstandingBalance = $this->invoiceService->calculateOutstandingBalance($invoice);
        $dpAmount = $this->invoiceService->calculateDpAmount($invoice);
        $hasConfirmedDp = $this->invoiceService->hasConfirmedDp($invoice);

        return view('admin.invoices.print', compact('invoice', 'paidAmount', 'outstandingBalance', 'dpAmount', 'hasConfirmedDp'));
    }
}

