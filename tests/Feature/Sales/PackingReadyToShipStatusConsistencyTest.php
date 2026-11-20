<?php

namespace Tests\Feature\Sales;

use App\Enums\BatchStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\ShipmentStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Master\Customer;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Production\ProductionBatch;
use App\Models\Production\ProductionResult;
use App\Models\Production\QcResult;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\Sales\Payment;
use App\Models\System\Shipment;
use App\Models\System\User;
use App\Services\Production\ShipmentService;
use App\Services\Sales\InvoiceService;
use App\Services\Tracking\TrackingTokenService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackingReadyToShipStatusConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $produksi;
    private Customer $customer;
    private ProductVariant $variant;
    private ShipmentService $shipmentService;
    private InvoiceService $invoiceService;
    private TrackingTokenService $tokenService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role'   => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->produksi = User::factory()->create([
            'role'   => UserRole::PRODUKSI,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->customer = Customer::create([
            'customer_name' => 'Toko Aisyah Hijab',
            'phone'         => '081234567890',
            'address'       => 'Jl. Sukajadi No. 123, Bandung',
        ]);

        $product = Product::create([
            'product_name' => 'Hijab Voal Polos Premium',
            'active'       => true,
        ]);

        $this->variant = ProductVariant::create([
            'product_id'        => $product->id,
            'variant_name'      => 'Dusty Pink',
            'sku'               => 'HJB-VP-DP-001',
            'requires_printing' => false,
            'active'            => true,
        ]);

        $this->shipmentService = app(ShipmentService::class);
        $this->invoiceService = app(InvoiceService::class);
        $this->tokenService = app(TrackingTokenService::class);
    }

    /**
     * Create an order at PACKING stage.
     */
    private function createOrderAtPacking(int $qty = 50, float $unitPrice = 75000, BatchStatus $batchStatus = BatchStatus::SELESAI): Order
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-TEST-' . uniqid(),
            'order_date'  => now()->toDateString(),
            'status'      => OrderStatus::PACKING,
        ]);

        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => $qty,
            'unit_selling_price' => $unitPrice,
            'subtotal'           => $qty * $unitPrice,
        ]);

        $batch = ProductionBatch::create([
            'order_item_id'     => $item->id,
            'batch_no'          => 'BAT-' . uniqid(),
            'target_qty'        => $qty,
            'status'            => $batchStatus,
            'requires_printing' => false,
        ]);

        $prodResult = ProductionResult::create([
            'batch_id'    => $batch->id,
            'actual_qty'  => $qty,
            'result_date' => now()->toDateString(),
            'recorded_by' => $this->produksi->id,
        ]);

        QcResult::create([
            'production_result_id' => $prodResult->id,
            'good_qty'             => $qty,
            'reject_qty'           => 0,
            'qc_date'              => now()->toDateString(),
            'qc_by'                => $this->produksi->id,
        ]);

        return $order;
    }

    /**
     * Attach an invoice and optional payments to an order.
     */
    private function attachInvoice(Order $order, string $totalAmount, array $confirmedPayments = []): Invoice
    {
        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-' . uniqid(),
            'invoice_date' => now()->toDateString(),
            'total_amount' => $totalAmount,
            'status'       => InvoiceStatus::UNPAID,
        ]);

        foreach ($confirmedPayments as $index => $paymentData) {
            Payment::create([
                'invoice_id'   => $invoice->id,
                'payment_no'   => 'PAY-' . uniqid() . '-' . $index,
                'payment_date' => now()->toDateString(),
                'amount'       => $paymentData['amount'],
                'payment_type' => $paymentData['type'] ?? PaymentType::DP,
                'status'       => PaymentStatus::CONFIRMED,
            ]);
        }

        $paidAmount = $this->invoiceService->calculatePaidAmount($invoice);
        if (bccomp($paidAmount, '0.00', 2) > 0) {
            $invoice->status = bccomp($paidAmount, $totalAmount, 2) >= 0 ? InvoiceStatus::PAID : InvoiceStatus::PARTIAL;
            $invoice->save();
        }

        return $invoice;
    }

    // ── 1. Packing in progress -> tracking says packing current ───────────

    public function test_packing_in_progress_tracking_shows_packing_as_current(): void
    {
        // Batch is still in PACKING status (not SELESAI)
        $order = $this->createOrderAtPacking(50, 75000, BatchStatus::PACKING);
        $this->attachInvoice($order, '3750000.00', [
            ['amount' => '3750000.00', 'type' => PaymentType::PELUNASAN],
        ]);

        $this->assertFalse($order->isPackingCompleted());
        $this->assertSame('PACKING_IN_PROGRESS', $order->getFulfillmentDisplayState());

        $token = $this->tokenService->createForOrder($order);
        $response = $this->get(route('track.show', $token));

        $response->assertOk();
        $response->assertSee('Pengemasan Produk');
        $response->assertSee('Sedang dalam tahapan ini');
        $response->assertDontSee('Siap Dikirim');
    }

    // ── 2. Packing completed + partial payment -> tracking packing completed, not shipped

    public function test_packing_completed_with_partial_payment_tracking_shows_packing_completed_not_shipped(): void
    {
        // Batch SELESAI, but only DP paid
        $order = $this->createOrderAtPacking(50, 75000, BatchStatus::SELESAI);
        $this->attachInvoice($order, '3750000.00', [
            ['amount' => '1500000.00', 'type' => PaymentType::DP],
        ]);

        $this->assertTrue($order->isPackingCompleted());
        $this->assertFalse($order->isFullyPaid());
        $this->assertSame('WAITING_PAYMENT', $order->getFulfillmentDisplayState());

        $token = $this->tokenService->createForOrder($order);
        $response = $this->get(route('track.show', $token));

        $response->assertOk();
        $response->assertSee('Pengemasan Produk');
        $response->assertSee('Selesai');
        $response->assertSee('Pesanan telah selesai dikemas.');
        // Stage DIKIRIM should remain upcoming
        $response->assertDontSee('Sedang dalam tahapan ini</p>' . "\n" . '                                <p class="text-sm font-semibold text-[#8E1B5C]">' . "\n" . '                                    Pesanan Dikirim');
    }

    // ── 3. Packing completed + full payment -> tracking says ready/waiting shipment

    public function test_packing_completed_with_full_payment_tracking_shows_ready_to_ship(): void
    {
        // Batch SELESAI and fully paid
        $order = $this->createOrderAtPacking(50, 75000, BatchStatus::SELESAI);
        $this->attachInvoice($order, '3750000.00', [
            ['amount' => '1875000.00', 'type' => PaymentType::DP],
            ['amount' => '1875000.00', 'type' => PaymentType::PELUNASAN],
        ]);

        $this->assertTrue($order->isPackingCompleted());
        $this->assertTrue($order->isFullyPaid());
        $this->assertSame('READY_TO_SHIP', $order->getFulfillmentDisplayState());

        $token = $this->tokenService->createForOrder($order);
        $response = $this->get(route('track.show', $token));

        $response->assertOk();
        $response->assertSee('Pengemasan Produk');
        $response->assertSee('Selesai');
        $response->assertSee('Siap Dikirim');
        $response->assertSee('Pesanan telah selesai dikemas dan siap dikirim.');
        // Stage DIKIRIM should remain upcoming, not yet dispatched
        $response->assertDontSee('Sedang dalam tahapan ini</p>' . "\n" . '                                <p class="text-sm font-semibold text-[#8E1B5C]">' . "\n" . '                                    Pesanan Dikirim');
    }

    // ── 4. Full payment alone does not mark shipped ───────────────────────

    public function test_full_payment_alone_does_not_mark_order_as_shipped(): void
    {
        $order = $this->createOrderAtPacking(50, 75000, BatchStatus::SELESAI);
        $this->attachInvoice($order, '3750000.00', [
            ['amount' => '3750000.00', 'type' => PaymentType::PELUNASAN],
        ]);

        $this->assertSame(OrderStatus::PACKING, $order->fresh()->status);
        $this->assertNotSame(OrderStatus::DIKIRIM, $order->fresh()->status);
    }

    // ── 5. Shipment creation/markAsShipped -> tracking advances to DIKIRIM ─

    public function test_shipment_dispatch_advances_tracking_to_dikirim(): void
    {
        $order = $this->createOrderAtPacking(50, 75000, BatchStatus::SELESAI);
        $this->attachInvoice($order, '3750000.00', [
            ['amount' => '3750000.00', 'type' => PaymentType::PELUNASAN],
        ]);

        $token = $this->tokenService->createForOrder($order);

        // 1. Create shipment
        $shipment = $this->shipmentService->createShipment($order, [
            'courier'           => 'JNE Express',
            'courier_service'   => 'REG',
            'recipient_name'    => 'Aisyah',
            'recipient_phone'   => '081234567890',
            'recipient_address' => 'Jl. Sukajadi No. 123, Bandung',
        ], $this->produksi);

        // 2. Dispatch shipment
        $this->shipmentService->markAsShipped($shipment, $this->produksi, [
            'tracking_no' => 'JNE-BDG-998877',
            'shipped_at'  => now(),
        ]);

        $this->assertSame(OrderStatus::DIKIRIM, $order->fresh()->status);
        $this->assertSame('SHIPPED', $order->fresh()->getFulfillmentDisplayState());

        // 3. Check public tracking
        $response = $this->get(route('track.show', $token));
        $response->assertOk();
        $response->assertSee('JNE-BDG-998877');
        $response->assertSee('JNE Express');
        $response->assertSee('Pesanan Dikirim');
        $response->assertSee('Sedang dalam tahapan ini');
    }

    // ── 6. Kanban and tracking use consistent derived state ───────────────

    public function test_kanban_and_tracking_use_consistent_derived_state(): void
    {
        // Case A: Waiting payment
        $orderUnpaid = $this->createOrderAtPacking(40, 75000, BatchStatus::SELESAI);
        $this->attachInvoice($orderUnpaid, '3000000.00', [
            ['amount' => '1000000.00', 'type' => PaymentType::DP],
        ]);

        $kanbanUnpaid = $this->actingAs($this->admin)->get(route('admin.kanban.index'));
        $kanbanUnpaid->assertSee($orderUnpaid->order_no);
        $kanbanUnpaid->assertSee('Menunggu Pelunasan');

        $tokenUnpaid = $this->tokenService->createForOrder($orderUnpaid);
        $trackUnpaid = $this->get(route('track.show', $tokenUnpaid));
        $trackUnpaid->assertSee('Menunggu Pelunasan');

        // Case B: Ready to ship
        $orderPaid = $this->createOrderAtPacking(40, 75000, BatchStatus::SELESAI);
        $this->attachInvoice($orderPaid, '3000000.00', [
            ['amount' => '3000000.00', 'type' => PaymentType::PELUNASAN],
        ]);

        $kanbanPaid = $this->actingAs($this->admin)->get(route('admin.kanban.index'));
        $kanbanPaid->assertSee($orderPaid->order_no);
        $kanbanPaid->assertSee('Siap Dikirim');

        $tokenPaid = $this->tokenService->createForOrder($orderPaid);
        $trackPaid = $this->get(route('track.show', $tokenPaid));
        $trackPaid->assertSee('Siap Dikirim');
    }

    // ── 7. Customer Portal and public tracking consistent ─────────────────

    public function test_customer_portal_and_public_tracking_are_consistent(): void
    {
        $customerUser = User::factory()->create([
            'role'   => UserRole::CUSTOMER,
            'status' => UserStatus::ACTIVE,
        ]);
        $this->customer->update(['user_id' => $customerUser->id]);

        $order = $this->createOrderAtPacking(50, 75000, BatchStatus::SELESAI);
        $this->attachInvoice($order, '3750000.00', [
            ['amount' => '3750000.00', 'type' => PaymentType::PELUNASAN],
        ]);

        // Customer portal
        $portalResponse = $this->actingAs($customerUser)->get(route('customer.orders.show', $order));
        $portalResponse->assertOk();
        $portalResponse->assertSee('Pengemasan Produk');
        $portalResponse->assertSee('Selesai');
        $portalResponse->assertSee('Siap Dikirim');

        // Public tracking
        $token = $this->tokenService->createForOrder($order);
        $trackingResponse = $this->get(route('track.show', $token));
        $trackingResponse->assertOk();
        $trackingResponse->assertSee('Pengemasan Produk');
        $trackingResponse->assertSee('Selesai');
        $trackingResponse->assertSee('Siap Dikirim');
    }

    // ── 8. Partial payment shipment gate remains blocked ──────────────────

    public function test_partial_payment_shipment_gate_remains_blocked(): void
    {
        $order = $this->createOrderAtPacking(50, 75000, BatchStatus::SELESAI);
        $this->attachInvoice($order, '3750000.00', [
            ['amount' => '1500000.00', 'type' => PaymentType::DP],
        ]);

        $this->assertFalse($this->shipmentService->canPrepareShipment($order));

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Pesanan belum dapat dikirim karena pembayaran belum lunas.');

        $this->shipmentService->createShipment($order, [
            'courier'           => 'JNE',
            'recipient_name'    => 'Aisyah',
            'recipient_phone'   => '081234567890',
            'recipient_address' => 'Bandung',
        ], $this->produksi);
    }

    // ── 9. Full payment shipment eligible ─────────────────────────────────

    public function test_full_payment_shipment_eligible(): void
    {
        $order = $this->createOrderAtPacking(50, 75000, BatchStatus::SELESAI);
        $this->attachInvoice($order, '3750000.00', [
            ['amount' => '3750000.00', 'type' => PaymentType::PELUNASAN],
        ]);

        $this->assertTrue($this->shipmentService->canPrepareShipment($order));

        // In Produksi Shipment queue, button is enabled
        $response = $this->actingAs($this->produksi)->get(route('produksi.shipments.index', ['tab' => 'ready']));
        $response->assertOk();
        $response->assertSee($order->order_no);
        $response->assertSee('Siap Dikirim');
        $response->assertSee(route('produksi.shipments.create', ['order_id' => $order->id]));
    }

    // ── 10. Permanent tracking token unchanged ────────────────────────────

    public function test_permanent_tracking_token_remains_unchanged(): void
    {
        $order = $this->createOrderAtPacking(50, 75000, BatchStatus::SELESAI);
        $this->attachInvoice($order, '3750000.00', [
            ['amount' => '1875000.00', 'type' => PaymentType::DP],
        ]);

        $token1 = $this->tokenService->createForOrder($order);

        // Add second payment (settling the invoice)
        $invoice = $order->invoice;
        Payment::create([
            'invoice_id'   => $invoice->id,
            'payment_no'   => 'PAY-SETTLE-001',
            'payment_date' => now()->toDateString(),
            'amount'       => '1875000.00',
            'payment_type' => PaymentType::PELUNASAN,
            'status'       => PaymentStatus::CONFIRMED,
        ]);
        $invoice->update([
            'status' => InvoiceStatus::PAID,
        ]);

        // Token must still be active and unchanged
        $validToken = $this->tokenService->findValidToken($token1);
        $this->assertNotNull($validToken);
        $this->assertSame($order->id, $validToken->order_id);

        $response = $this->get(route('track.show', $token1));
        $response->assertOk();
        $response->assertSee('Siap Dikirim');
    }
}
