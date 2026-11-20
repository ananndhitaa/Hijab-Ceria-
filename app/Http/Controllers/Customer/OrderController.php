<?php

namespace App\Http\Controllers\Customer;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Sales\Order;
use App\Services\Sales\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService
    ) {}

    /**
     * Display a listing of orders belonging to the authenticated customer.
     */
    public function index(Request $request): View
    {
        $customer = $request->user()->customer;
        $search = trim((string) $request->input('search', ''));
        $statusFilter = $request->input('status');
        $statuses = OrderStatus::cases();

        if (! $customer) {
            $orders = Order::whereRaw('1 = 0')->paginate(10)->withQueryString();

            return view('customer.orders.index', compact('orders', 'search', 'statusFilter', 'statuses'));
        }

        // Ownership-scoped query: Customer can only retrieve their own orders
        $query = $customer->orders()
            ->with(['items.variant.product', 'invoice.payments', 'latestShipment']);

        if ($search !== '') {
            $query->where('order_no', 'like', "%{$search}%");
        }

        if (! empty($statusFilter) && OrderStatus::tryFrom($statusFilter)) {
            $query->where('status', $statusFilter);
        }

        $orders = $query->latest('order_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('customer.orders.index', compact('orders', 'search', 'statusFilter', 'statuses'));
    }

    /**
     * Display the specified order details for the authenticated customer.
     * Uses ownership-scoped query retrieval to guarantee IDOR protection (returns 404).
     */
    public function show(Request $request, int|string $order): View
    {
        $customer = $request->user()->customer;

        // An unlinked customer user cannot view any order (safe 404)
        if (! $customer) {
            abort(404);
        }

        // Strongly scoped query: customer->orders()->findOrFail($order)
        // If the order belongs to another customer or does not exist, throws ModelNotFoundException (HTTP 404)
        $order = $customer->orders()
            ->with([
                'items.variant.product',
                'invoice.payments',
                'latestShipment',
                'review',
            ])
            ->findOrFail($order);

        // Authoritative invoice & payment calculation using InvoiceService (decimal-safe BCMath)
        $paidAmount = '0.00';
        $outstandingBalance = '0.00';
        if ($order->invoice) {
            $paidAmount = $this->invoiceService->calculatePaidAmount($order->invoice);
            $outstandingBalance = $this->invoiceService->calculateOutstandingBalance($order->invoice);
        }

        // Decimal-safe order total using snapshot items
        $orderTotal = '0.00';
        $totalQty = '0';
        foreach ($order->items as $item) {
            $sub = bcmul((string) $item->qty_ordered, (string) $item->unit_selling_price, 2);
            $orderTotal = bcadd($orderTotal, $sub, 2);
            $totalQty = bcadd($totalQty, (string) $item->qty_ordered, 4);
        }

        return view('customer.orders.show', compact(
            'order',
            'paidAmount',
            'outstandingBalance',
            'orderTotal',
            'totalQty'
        ));
    }

    /**
     * Store or update a customer review for the specified order.
     */
    public function storeReview(Request $request, int|string $order): \Illuminate\Http\RedirectResponse
    {
        $customer = $request->user()->customer;
        if (! $customer) {
            abort(404);
        }

        $order = $customer->orders()->findOrFail($order);

        $validated = $request->validate([
            'rating'      => ['required', 'integer', 'min:1', 'max:5'],
            'review_text' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'rating.required'      => 'Bintang penilaian wajib dipilih (1-5).',
            'rating.min'           => 'Penilaian minimal 1 bintang.',
            'rating.max'           => 'Penilaian maksimal 5 bintang.',
            'review_text.required' => 'Teks ulasan wajib diisi.',
            'review_text.min'      => 'Teks ulasan minimal 5 karakter.',
            'review_text.max'      => 'Teks ulasan maksimal 1000 karakter.',
        ]);

        \App\Models\Platform\OrderReview::updateOrCreate(
            ['order_id' => $order->id],
            [
                'customer_id'   => $customer->id,
                'outlet_id'     => $order->outlet_id,
                'user_id'       => $request->user()->id,
                'customer_name' => $customer->customer_name,
                'customer_role' => 'Pelanggan Terverifikasi',
                'customer_city' => $customer->address ?: 'Indonesia',
                'rating'        => $validated['rating'],
                'review_text'   => $validated['review_text'],
                'avatar_path'   => 'images/storefront/user-aisyah.jpg',
                'is_featured'   => true,
                'is_approved'   => true,
            ]
        );

        return redirect()->route('customer.orders.show', $order)
            ->with('success', "Terima kasih! Ulasan Anda untuk pesanan {$order->order_no} berhasil dikirim.");
    }
}
