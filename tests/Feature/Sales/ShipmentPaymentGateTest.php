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
use App\Models\Production\BatchMaterial;
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
use App\Services\Sales\OrderStatusService;
use App\Services\Tracking\TrackingTokenService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentPaymentGateTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $produksi;
    private Customer $customer;
    private ProductVariant $variant;
    private ShipmentService $shipmentService;
    private InvoiceService $invoiceService;
    private OrderStatusService $orderStatusService;

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
        $this->orderStatusService = app(OrderStatusService::class);
    }

    /**
     * Helper to create an order at PACKING stage with 100% finished production batches and full Good Qty.
     */
    private function createPackedOrder(int $qty = 40, float $unitPrice = 75000): Order
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
            'status'            => BatchStatus::SELESAI,
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

    // ── 1. Partial Payment Tests ──────────────────────────────────────────

    public function test_partial_payment_cannot_create_shipment(): void
    {
        $order = $this->createPackedOrder(40, 75000); // Total: 3.000.000

        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-' . uniqid(),
            'invoice_date' => now()->toDateString(),
            'total_amount' => '3000000.00',
            'status'       => InvoiceStatus::PARTIAL,
        ]);

        // DP of 1.500.000 confirmed (50%)
        Payment::create([
            'invoice_id'   => $invoice->id,
            'payment_no'   => 'PAY-' . uniqid(),
            'payment_date' => now()->toDateString(),
            'amount'       => '1500000.00',
            'payment_type' => PaymentType::DP,
            'status'       => PaymentStatus::CONFIRMED,
        ]);

        $eligibility = $this->shipmentService->getEligibility($order);
        $this->assertFalse($eligibility['is_eligible']);
        $this->assertFalse($eligibility['is_payment_settled']);
        $this->assertContains('Pesanan belum dapat dikirim karena pembayaran belum lunas.', $eligibility['reasons']);

        // Direct store attempt via Produksi should fail
        $response = $this->actingAs($this->produksi)
            ->post(route('produksi.shipments.store'), [
                'order_id'          => $order->id,
                'courier'           => 'JNE',
                'tracking_no'       => 'JNE123456',
                'recipient_address' => 'Jl. Sukajadi No. 123, Bandung',
            ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('shipments', ['order_id' => $order->id]);
        $this->assertSame(OrderStatus::PACKING, $order->fresh()->status);
    }

    public function test_partial_payment_cannot_transition_order_to_dikirim(): void
    {
        $order = $this->createPackedOrder(40, 75000);

        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-' . uniqid(),
            'invoice_date' => now()->toDateString(),
            'total_amount' => '3000000.00',
            'status'       => InvoiceStatus::PARTIAL,
        ]);

        Payment::create([
            'invoice_id'   => $invoice->id,
            'payment_no'   => 'PAY-' . uniqid(),
            'payment_date' => now()->toDateString(),
            'amount'       => '1500000.00',
            'payment_type' => PaymentType::DP,
            'status'       => PaymentStatus::CONFIRMED,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Pesanan belum dapat dikirim karena pembayaran belum lunas.');

        $this->orderStatusService->transitionToDikirimFromShipment($order, $this->produksi);
    }

    public function test_packing_completion_with_partial_payment_stays_in_packing_status(): void
    {
        $order = $this->createPackedOrder(40, 75000);

        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-' . uniqid(),
            'invoice_date' => now()->toDateString(),
            'total_amount' => '3000000.00',
            'status'       => InvoiceStatus::PARTIAL,
        ]);

        Payment::create([
            'invoice_id'   => $invoice->id,
            'payment_no'   => 'PAY-' . uniqid(),
            'payment_date' => now()->toDateString(),
            'amount'       => '1500000.00',
            'payment_type' => PaymentType::DP,
            'status'       => PaymentStatus::CONFIRMED,
        ]);

        // Synchronize operational status
        $this->orderStatusService->syncOperationalStatus($order);

        $this->assertSame(OrderStatus::PACKING, $order->fresh()->status);
        $this->assertNotSame(OrderStatus::DIKIRIM, $order->fresh()->status);
    }

    // ── 2. Missing & Unpaid Invoice Tests ─────────────────────────────────

    public function test_order_without_invoice_blocks_shipment(): void
    {
        $order = $this->createPackedOrder(40, 75000);

        $eligibility = $this->shipmentService->getEligibility($order);
        $this->assertFalse($eligibility['is_eligible']);
        $this->assertFalse($eligibility['is_payment_settled']);
        $this->assertContains('Pesanan belum dapat dikirim karena invoice belum diterbitkan.', $eligibility['reasons']);

        $response = $this->actingAs($this->produksi)
            ->post(route('produksi.shipments.store'), [
                'order_id'          => $order->id,
                'courier'           => 'JNE',
                'tracking_no'       => 'JNE123456',
                'recipient_address' => 'Jl. Sukajadi No. 123, Bandung',
            ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('shipments', ['order_id' => $order->id]);
    }

    public function test_unpaid_invoice_blocks_shipment(): void
    {
        $order = $this->createPackedOrder(40, 75000);

        Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-' . uniqid(),
            'invoice_date' => now()->toDateString(),
            'total_amount' => '3000000.00',
            'status'       => InvoiceStatus::UNPAID,
        ]);

        $eligibility = $this->shipmentService->getEligibility($order);
        $this->assertFalse($eligibility['is_eligible']);
        $this->assertFalse($eligibility['is_payment_settled']);
        $this->assertContains('Pesanan belum dapat dikirim karena pembayaran belum lunas.', $eligibility['reasons']);
    }

    // ── 3. Payment Status Tests (Pending / Rejected / Confirmed) ──────────

    public function test_pending_payment_does_not_satisfy_paid_requirement(): void
    {
        $order = $this->createPackedOrder(40, 75000);

        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-' . uniqid(),
            'invoice_date' => now()->toDateString(),
            'total_amount' => '3000000.00',
            'status'       => InvoiceStatus::UNPAID,
        ]);

        // Customer submitted proof for full nominal but it is PENDING
        Payment::create([
            'invoice_id'   => $invoice->id,
            'payment_no'   => 'PAY-' . uniqid(),
            'payment_date' => now()->toDateString(),
            'amount'       => '3000000.00',
            'payment_type' => PaymentType::PELUNASAN,
            'status'       => PaymentStatus::PENDING,
        ]);

        $this->assertFalse($this->invoiceService->isOrderFullyPaid($order));
        $this->assertFalse($this->shipmentService->canPrepareShipment($order));
    }

    public function test_rejected_payment_does_not_satisfy_paid_requirement(): void
    {
        $order = $this->createPackedOrder(40, 75000);

        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-' . uniqid(),
            'invoice_date' => now()->toDateString(),
            'total_amount' => '3000000.00',
            'status'       => InvoiceStatus::UNPAID,
        ]);

        // DP of 1.500.000 confirmed
        Payment::create([
            'invoice_id'   => $invoice->id,
            'payment_no'   => 'PAY-' . uniqid(),
            'payment_date' => now()->toDateString(),
            'amount'       => '1500000.00',
            'payment_type' => PaymentType::DP,
            'status'       => PaymentStatus::CONFIRMED,
        ]);

        // Second payment was CANCELLED
        Payment::create([
            'invoice_id'   => $invoice->id,
            'payment_no'   => 'PAY-' . uniqid(),
            'payment_date' => now()->toDateString(),
            'amount'       => '1500000.00',
            'payment_type' => PaymentType::PELUNASAN,
            'status'       => PaymentStatus::CANCELLED,
        ]);

        $this->assertFalse($this->invoiceService->isOrderFullyPaid($order));
        $this->assertFalse($this->shipmentService->canPrepareShipment($order));
    }

    public function test_fully_confirmed_paid_invoice_allows_shipment_action(): void
    {
        $order = $this->createPackedOrder(40, 75000);

        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-' . uniqid(),
            'invoice_date' => now()->toDateString(),
            'total_amount' => '3000000.00',
            'status'       => InvoiceStatus::PAID,
        ]);

        // Payment 1: DP confirmed
        Payment::create([
            'invoice_id'   => $invoice->id,
            'payment_no'   => 'PAY-' . uniqid(),
            'payment_date' => now()->toDateString(),
            'amount'       => '1500000.00',
            'payment_type' => PaymentType::DP,
            'status'       => PaymentStatus::CONFIRMED,
        ]);

        // Payment 2: Pelunasan confirmed
        Payment::create([
            'invoice_id'   => $invoice->id,
            'payment_no'   => 'PAY-' . uniqid(),
            'payment_date' => now()->toDateString(),
            'amount'       => '1500000.00',
            'payment_type' => PaymentType::PELUNASAN,
            'status'       => PaymentStatus::CONFIRMED,
        ]);

        $this->assertTrue($this->invoiceService->isOrderFullyPaid($order));
        $this->assertTrue($this->shipmentService->canPrepareShipment($order));

        $eligibility = $this->shipmentService->getEligibility($order);
        $this->assertTrue($eligibility['is_eligible']);
        $this->assertTrue($eligibility['is_payment_settled']);
        $this->assertEmpty($eligibility['reasons']);
    }

    // ── 4. Full Payment Alone Does Not Mark Shipped ───────────────────────

    public function test_full_payment_alone_does_not_mark_dikirim_before_shipment(): void
    {
        $order = $this->createPackedOrder(40, 75000);

        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-' . uniqid(),
            'invoice_date' => now()->toDateString(),
            'total_amount' => '3000000.00',
            'status'       => InvoiceStatus::PAID,
        ]);

        Payment::create([
            'invoice_id'   => $invoice->id,
            'payment_no'   => 'PAY-' . uniqid(),
            'payment_date' => now()->toDateString(),
            'amount'       => '3000000.00',
            'payment_type' => PaymentType::PELUNASAN,
            'status'       => PaymentStatus::CONFIRMED,
        ]);

        // Re-sync operational status
        $this->orderStatusService->syncOperationalStatus($order);

        // Crucial invariant: order remains PACKING until actual shipment dispatch
        $this->assertSame(OrderStatus::PACKING, $order->fresh()->status);
        $this->assertNotSame(OrderStatus::DIKIRIM, $order->fresh()->status);
    }

    // ── 5. Shipment Creation & Dispatch Transition ────────────────────────

    public function test_shipment_creation_and_dispatch_transitions_to_dikirim(): void
    {
        $order = $this->createPackedOrder(40, 75000);

        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-' . uniqid(),
            'invoice_date' => now()->toDateString(),
            'total_amount' => '3000000.00',
            'status'       => InvoiceStatus::PAID,
        ]);

        Payment::create([
            'invoice_id'   => $invoice->id,
            'payment_no'   => 'PAY-' . uniqid(),
            'payment_date' => now()->toDateString(),
            'amount'       => '3000000.00',
            'payment_type' => PaymentType::PELUNASAN,
            'status'       => PaymentStatus::CONFIRMED,
        ]);

        // 1. Create draft shipment
        $shipment = $this->shipmentService->createShipment($order, [
            'courier'           => 'JNE Express',
            'courier_service'   => 'REG',
            'tracking_no'       => 'JNE99887766',
            'recipient_address' => 'Jl. Sukajadi No. 123, Bandung',
        ], $this->produksi);

        $this->assertSame(ShipmentStatus::PENDING, $shipment->status);
        $this->assertSame(OrderStatus::PACKING, $order->fresh()->status);

        // 2. Mark as Shipped
        $this->shipmentService->markAsShipped($shipment, $this->produksi);

        $this->assertSame(ShipmentStatus::SHIPPED, $shipment->fresh()->status);
        $this->assertSame(OrderStatus::DIKIRIM, $order->fresh()->status);
    }

    // ── 6. UI Consistency: Kanban Admin ───────────────────────────────────

    public function test_kanban_placement_follows_actual_shipment_state(): void
    {
        $order = $this->createPackedOrder(40, 75000);

        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-' . uniqid(),
            'invoice_date' => now()->toDateString(),
            'total_amount' => '3000000.00',
            'status'       => InvoiceStatus::PARTIAL,
        ]);

        Payment::create([
            'invoice_id'   => $invoice->id,
            'payment_no'   => 'PAY-' . uniqid(),
            'payment_date' => now()->toDateString(),
            'amount'       => '1500000.00',
            'payment_type' => PaymentType::DP,
            'status'       => PaymentStatus::CONFIRMED,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.kanban.index'));
        $response->assertOk();

        // Must display in PACKING column with Menunggu Pelunasan and Inv: Bayar Sebagian
        $response->assertSee($order->order_no);
        $response->assertSee('Inv: Bayar Sebagian');
        $response->assertSee('Menunggu Pelunasan');
        $this->assertSame(OrderStatus::PACKING, $order->fresh()->status);
    }

    // ── 7. UI Consistency: Public Tracking Token ──────────────────────────

    public function test_public_tracking_token_does_not_show_dikirim_prematurely(): void
    {
        $order = $this->createPackedOrder(40, 75000);

        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-' . uniqid(),
            'invoice_date' => now()->toDateString(),
            'total_amount' => '3000000.00',
            'status'       => InvoiceStatus::PARTIAL,
        ]);

        Payment::create([
            'invoice_id'   => $invoice->id,
            'payment_no'   => 'PAY-' . uniqid(),
            'payment_date' => now()->toDateString(),
            'amount'       => '1500000.00',
            'payment_type' => PaymentType::DP,
            'status'       => PaymentStatus::CONFIRMED,
        ]);

        $tokenService = app(TrackingTokenService::class);
        $token = $tokenService->createForOrder($order);

        $response = $this->get("/track/{$token}");
        $response->assertOk();
        $response->assertSee($order->order_no);
        $response->assertSee('Pengemasan Produk');
        // DIKIRIM should NOT be marked as current stage
        $response->assertDontSee('Sedang dalam tahapan ini</p>' . "\n" . '                                <p class="text-sm font-semibold text-[#8E1B5C]">' . "\n" . '                                    Pesanan Dikirim');
    }

    // ── 8. UI Consistency: Customer Portal ────────────────────────────────

    public function test_customer_portal_does_not_show_dikirim_prematurely(): void
    {
        $customerUser = User::factory()->create([
            'role'   => UserRole::CUSTOMER,
            'status' => UserStatus::ACTIVE,
        ]);
        $this->customer->update(['user_id' => $customerUser->id]);

        $order = $this->createPackedOrder(40, 75000);

        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-' . uniqid(),
            'invoice_date' => now()->toDateString(),
            'total_amount' => '3000000.00',
            'status'       => InvoiceStatus::PARTIAL,
        ]);

        Payment::create([
            'invoice_id'   => $invoice->id,
            'payment_no'   => 'PAY-' . uniqid(),
            'payment_date' => now()->toDateString(),
            'amount'       => '1500000.00',
            'payment_type' => PaymentType::DP,
            'status'       => PaymentStatus::CONFIRMED,
        ]);

        $response = $this->actingAs($customerUser)->get(route('customer.orders.show', $order));
        $response->assertOk();
        $response->assertSee($order->order_no);
        $response->assertSee('Pengemasan');
        $this->assertSame(OrderStatus::PACKING, $order->fresh()->status);
        // Latest shipment card should not appear since no shipment was created
        $response->assertDontSee('Informasi Pengiriman');
    }

    // ── 9. Produksi Queue Button Gate ─────────────────────────────────────

    public function test_produksi_shipment_queue_disables_button_when_payment_is_partial(): void
    {
        $order = $this->createPackedOrder(40, 75000);

        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-' . uniqid(),
            'invoice_date' => now()->toDateString(),
            'total_amount' => '3000000.00',
            'status'       => InvoiceStatus::PARTIAL,
        ]);

        Payment::create([
            'invoice_id'   => $invoice->id,
            'payment_no'   => 'PAY-' . uniqid(),
            'payment_date' => now()->toDateString(),
            'amount'       => '1500000.00',
            'payment_type' => PaymentType::DP,
            'status'       => PaymentStatus::CONFIRMED,
        ]);

        $response = $this->actingAs($this->produksi)->get(route('produksi.shipments.index', ['tab' => 'ready']));
        $response->assertOk();
        $response->assertSee($order->order_no);
        $response->assertSee('Menunggu Pelunasan');
        $response->assertDontSee(route('produksi.shipments.create', ['order_id' => $order->id]));
    }
}
