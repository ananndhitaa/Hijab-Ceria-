<?php

namespace App\Http\Controllers\Produksi;

use App\Enums\OrderStatus;
use App\Enums\ShipmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Produksi\StoreShipmentRequest;
use App\Models\Sales\Order;
use App\Models\System\Shipment;
use App\Services\Production\ShipmentService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShipmentController extends Controller
{
    public function __construct(
        private ShipmentService $shipmentService
    ) {}

    /**
     * Display the Shipment Work Queue for Produksi/QC.
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'ready');
        $search = $request->query('search');

        // 1. Ready-to-ship orders (PACKING with all batches finished and good qty fulfilled)
        $packingOrders = Order::where('status', OrderStatus::PACKING)
            ->with(['customer', 'items.variant.product', 'items.productionBatches.productionResults.qcResult', 'invoice.payments'])
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->get();

        $readyOrders = $packingOrders->filter(function (Order $order) {
            return $this->shipmentService->isProductionFulfilled($order);
        });

        // 2. Query shipments
        $shipmentsQuery = Shipment::with([
            'order.customer',
            'order.items.variant.product',
        ])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('tracking_no', 'like', "%{$search}%")
                        ->orWhere('courier', 'like', "%{$search}%")
                        ->orWhereHas('order', function ($oq) use ($search) {
                            $oq->where('order_no', 'like', "%{$search}%")
                                ->orWhereHas('customer', function ($cq) use ($search) {
                                    $cq->where('customer_name', 'like', "%{$search}%");
                                });
                        });
                });
            })
            ->when($tab === 'active', function ($query) {
                $query->whereIn('status', [
                    ShipmentStatus::PENDING->value,
                    ShipmentStatus::PACKED->value,
                    ShipmentStatus::SHIPPED->value,
                ]);
            })
            ->when($tab === 'delivered', function ($query) {
                $query->where('status', ShipmentStatus::DELIVERED->value);
            })
            ->when($tab === 'returned', function ($query) {
                $query->where('status', ShipmentStatus::RETURNED->value);
            })
            ->orderByDesc('id');

        $shipments = $shipmentsQuery->paginate(15)->withQueryString();

        // 3. Metric counts
        $readyCount = $readyOrders->count();
        $activeCount = Shipment::whereIn('status', [
            ShipmentStatus::PENDING->value,
            ShipmentStatus::PACKED->value,
            ShipmentStatus::SHIPPED->value,
        ])->count();
        $deliveredCount = Shipment::where('status', ShipmentStatus::DELIVERED->value)->count();

        return view('produksi.shipments.index', compact(
            'tab',
            'search',
            'readyOrders',
            'shipments',
            'readyCount',
            'activeCount',
            'deliveredCount'
        ));
    }

    /**
     * Show form to prepare a shipment for a specific Sales Order.
     *
     * @param Request $request
     * @return View|RedirectResponse
     */
    public function create(Request $request): View|RedirectResponse
    {
        $orderId = $request->query('order_id');

        if (! $orderId) {
            return redirect()->route('produksi.shipments.index')
                ->with('error', 'Silakan pilih pesanan dari antrean Siap Dikirim.');
        }

        /** @var Order $order */
        $order = Order::with([
            'customer',
            'items.variant.product',
            'items.productionBatches.productionResults.qcResult',
        ])->findOrFail($orderId);

        $eligibility = $this->shipmentService->getEligibility($order);

        if (! $eligibility['is_eligible']) {
            $reasonText = implode(' ', $eligibility['reasons']);
            return redirect()->route('produksi.shipments.index')
                ->with('error', "Pesanan {$order->order_no} belum siap untuk dikirim: {$reasonText}");
        }

        return view('produksi.shipments.create', compact('order', 'eligibility'));
    }

    /**
     * Store a newly created Shipment.
     *
     * @param StoreShipmentRequest $request
     * @return RedirectResponse
     */
    public function store(StoreShipmentRequest $request): RedirectResponse
    {
        /** @var Order $order */
        $order = Order::findOrFail($request->validated('order_id'));

        try {
            $shipment = $this->shipmentService->createShipment(
                $order,
                $request->validated(),
                $request->user()
            );

            if ($request->input('action') === 'ship') {
                $this->shipmentService->markAsShipped($shipment, $request->user(), $request->validated());

                return redirect()->route('produksi.shipments.show', $shipment)
                    ->with('success', "Pengiriman untuk pesanan {$order->order_no} berhasil disiapkan dan ditandai DIKIRIM.");
            }

            return redirect()->route('produksi.shipments.show', $shipment)
                ->with('success', "Draf pengiriman untuk pesanan {$order->order_no} berhasil dibuat (Menunggu Pengiriman).");
        } catch (DomainException $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Display shipment details and available status actions.
     *
     * @param Shipment $shipment
     * @return View
     */
    public function show(Shipment $shipment): View
    {
        $shipment->load([
            'order.customer',
            'order.items.variant.product',
            'order.items.productionBatches.productionResults.qcResult',
        ]);

        return view('produksi.shipments.show', compact('shipment'));
    }

    /**
     * Transition Shipment to SHIPPED and advance Order to DIKIRIM.
     *
     * @param Request $request
     * @param Shipment $shipment
     * @return RedirectResponse
     */
    public function ship(Request $request, Shipment $shipment): RedirectResponse
    {
        $validated = $request->validate([
            'courier'         => ['nullable', 'string', 'max:100'],
            'courier_service' => ['nullable', 'string', 'max:100'],
            'tracking_no'     => ['nullable', 'string', 'max:100'],
            'notes'           => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->shipmentService->markAsShipped($shipment, $request->user(), $validated);

            return redirect()->route('produksi.shipments.show', $shipment)
                ->with('success', "Pengiriman {$shipment->order->order_no} berhasil diperbarui menjadi DIKIRIM.");
        } catch (DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Transition Shipment to DELIVERED and advance Order to SELESAI.
     *
     * @param Request $request
     * @param Shipment $shipment
     * @return RedirectResponse
     */
    public function deliver(Request $request, Shipment $shipment): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->shipmentService->markAsDelivered($shipment, $request->user(), $validated['notes'] ?? null);

            return redirect()->route('produksi.shipments.show', $shipment)
                ->with('success', "Pesanan {$shipment->order->order_no} telah berhasil diterima (Selesai).");
        } catch (DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
