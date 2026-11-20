<?php

namespace Tests\Feature\Produksi;

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
use App\Models\System\OrderStatusHistory;
use App\Models\System\Shipment;
use App\Models\System\User;
use App\Services\Production\ShipmentService;
use App\Services\Sales\InvoiceService;
use App\Services\Sales\OrderStatusService;
use App\Services\Tracking\TrackingTokenService;
use App\Support\DateTimeFormatter;
use Carbon\Carbon;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderDeliveredTerminalStateTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $produksi;
    private User $customerUser;
    private Customer $customer;
    private ProductVariant $variant;
    private ShipmentService $shipmentService;
    private OrderStatusService $orderStatusService;
    private TrackingTokenService $tokenService;
    private InvoiceService $invoiceService;

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

        $this->customerUser = User::factory()->create([
            'role'   => UserRole::CUSTOMER,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->customer = Customer::create([
            'user_id'       => $this->customerUser->id,
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
        $this->orderStatusService = app(OrderStatusService::class);
        $this->tokenService = app(TrackingTokenService::class);
        $this->invoiceService = app(InvoiceService::class);
    }

    /**
     * Helper to create a fully packed and fully paid order.
     */
    private function createPackedAndPaidOrder(int $qty = 50, float $unitPrice = 75000): Order
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-' . date('Ym') . '-9991',
            'order_date'  => '2026-09-30',
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
            'batch_no'          => 'BAT-' . date('Ym') . '-9991',
            'order_item_id'     => $item->id,
            'target_qty'        => $qty,
            'actual_qty'        => $qty,
            'status'            => BatchStatus::SELESAI,
            'requires_printing' => false,
        ]);

        $result = ProductionResult::create([
            'batch_id'    => $batch->id,
            'result_date' => '2026-09-30',
            'produced_by' => $this->produksi->id,
            'actual_qty'  => $qty,
        ]);

        QcResult::create([
            'batch_id'             => $batch->id,
            'production_result_id' => $result->id,
            'qc_date'              => '2026-09-30',
            'inspected_by'         => $this->produksi->id,
            'good_qty'             => $qty,
            'reject_qty'           => 0,
        ]);

        $totalAmount = bcmul((string) $qty, (string) $unitPrice, 2);
        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-' . date('Ym') . '-9991',
            'invoice_date' => '2026-09-30',
            'total_amount' => $totalAmount,
            'status'       => InvoiceStatus::PAID,
        ]);

        Payment::create([
            'invoice_id'   => $invoice->id,
            'payment_no'   => 'PAY-DP-9991',
            'payment_date' => '2026-09-30',
            'amount'       => bcdiv($totalAmount, '2', 2),
            'payment_type' => PaymentType::DP,
            'status'       => PaymentStatus::CONFIRMED,
        ]);

        Payment::create([
            'invoice_id'   => $invoice->id,
            'payment_no'   => 'PAY-PEL-9991',
            'payment_date' => '2026-09-30',
            'amount'       => bcdiv($totalAmount, '2', 2),
            'payment_type' => PaymentType::PELUNASAN,
            'status'       => PaymentStatus::CONFIRMED,
        ]);

        return $order;
    }

    // ── 1. markAsShipped -> Order DIKIRIM ────────────────────────────────────

    public function test_mark_as_shipped_transitions_order_to_dikirim(): void
    {
        $order = $this->createPackedAndPaidOrder();

        $shipment = $this->shipmentService->createShipment($order, [
            'courier'           => 'JNE',
            'courier_service'   => 'Reguler',
            'recipient_name'    => 'Aisyah',
            'recipient_phone'   => '081234567890',
            'recipient_address' => 'Bandung',
        ], $this->produksi);

        $this->assertSame(ShipmentStatus::PENDING, $shipment->status);
        $this->assertSame(OrderStatus::PACKING, $order->fresh()->status);

        $shippedShipment = $this->shipmentService->markAsShipped($shipment, $this->produksi, [
            'tracking_no' => 'JNE1234567890',
        ]);

        $this->assertSame(ShipmentStatus::SHIPPED, $shippedShipment->status);
        $this->assertSame(OrderStatus::DIKIRIM, $order->fresh()->status);

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'status'   => OrderStatus::DIKIRIM->value,
        ]);
    }

    // ── 2 & 3. markAsDelivered -> Order SELESAI, timestamp preserved ─────────

    public function test_mark_as_delivered_transitions_order_to_selesai_and_preserves_timestamp(): void
    {
        $order = $this->createPackedAndPaidOrder();

        $shipment = $this->shipmentService->createShipment($order, [
            'courier'           => 'JNE',
            'recipient_name'    => 'Aisyah',
            'recipient_phone'   => '081234567890',
            'recipient_address' => 'Bandung',
        ], $this->produksi);

        $this->shipmentService->markAsShipped($shipment, $this->produksi, [
            'tracking_no' => 'JNE1234567890',
        ]);

        $fixedDeliveredTime = Carbon::create(2026, 9, 30, 14, 6, 13, 'Asia/Jakarta');
        Carbon::setTestNow($fixedDeliveredTime);

        $deliveredShipment = $this->shipmentService->markAsDelivered($shipment, $this->produksi, 'Pesanan telah diterima customer.');

        $this->assertSame(ShipmentStatus::DELIVERED, $deliveredShipment->status);
        $this->assertNotNull($deliveredShipment->delivered_at);
        $this->assertSame(OrderStatus::SELESAI, $order->fresh()->status);

        // Check OrderStatusHistory record has exact delivery timestamp and user
        $history = OrderStatusHistory::where('order_id', $order->id)
            ->where('status', OrderStatus::SELESAI)
            ->latest('id')
            ->first();

        $this->assertNotNull($history);
        $this->assertSame($this->produksi->id, $history->changed_by_user_id);
        $this->assertEquals($fixedDeliveredTime->timestamp, $history->changed_at->timestamp);
        $this->assertSame('Pesanan telah diterima customer.', $history->notes);

        Carbon::setTestNow();
    }

    // ── 4 & 5. Idempotent repeat delivery confirmation ────────────────────────

    public function test_mark_as_delivered_is_idempotent(): void
    {
        $order = $this->createPackedAndPaidOrder();

        $shipment = $this->shipmentService->createShipment($order, [
            'courier'           => 'JNE',
            'recipient_name'    => 'Aisyah',
            'recipient_phone'   => '081234567890',
            'recipient_address' => 'Bandung',
        ], $this->produksi);

        $this->shipmentService->markAsShipped($shipment, $this->produksi, [
            'tracking_no' => 'JNE1234567890',
        ]);

        $time1 = Carbon::create(2026, 9, 30, 14, 6, 13, 'Asia/Jakarta');
        Carbon::setTestNow($time1);

        $this->shipmentService->markAsDelivered($shipment, $this->produksi, 'Diterima oleh Pak Satpam.');

        $historiesCountAfterFirst = OrderStatusHistory::where('order_id', $order->id)
            ->where('status', OrderStatus::SELESAI)
            ->count();
        $this->assertSame(1, $historiesCountAfterFirst);

        // Advance clock and call markAsDelivered again
        $time2 = Carbon::create(2026, 9, 30, 15, 0, 0, 'Asia/Jakarta');
        Carbon::setTestNow($time2);

        $repeatedShipment = $this->shipmentService->markAsDelivered($shipment, $this->produksi, 'Repeat delivery');

        $this->assertSame(ShipmentStatus::DELIVERED, $repeatedShipment->status);
        $this->assertEquals($time1->timestamp, $repeatedShipment->delivered_at->timestamp);

        // No duplicate history
        $historiesCountAfterSecond = OrderStatusHistory::where('order_id', $order->id)
            ->where('status', OrderStatus::SELESAI)
            ->count();
        $this->assertSame(1, $historiesCountAfterSecond);

        Carbon::setTestNow();
    }

    // ── 6 & 7. Public tracking shows completed terminal state without in-progress ──

    public function test_public_tracking_shows_terminal_completed_state(): void
    {
        $order = $this->createPackedAndPaidOrder();

        $shipment = $this->shipmentService->createShipment($order, [
            'courier'           => 'JNE',
            'courier_service'   => 'Reguler',
            'recipient_name'    => 'Aisyah',
            'recipient_phone'   => '081234567890',
            'recipient_address' => 'Bandung',
        ], $this->produksi);

        $this->shipmentService->markAsShipped($shipment, $this->produksi, [
            'tracking_no' => 'JNE1234567890',
        ]);

        $this->shipmentService->markAsDelivered($shipment, $this->produksi);

        $token = $this->tokenService->createForOrder($order);

        $response = $this->get(route('track.show', $token));
        $response->assertOk();

        // Must NOT say 'Sedang dalam tahapan ini' anywhere on the page
        $response->assertDontSee('Sedang dalam tahapan ini');

        // Must show 'Pesanan Dikirim' and 'Pesanan Selesai'
        $response->assertSee('Pesanan Dikirim');
        $response->assertSee('Pesanan Selesai');

        // Must show final completion banner message
        $response->assertSee('Pesanan telah selesai dan diterima oleh customer.');

        // Must show courier and tracking details
        $response->assertSee('JNE1234567890');
        $response->assertSee('Waktu Diterima');
    }

    // ── 8. Customer Portal shows completed state and matches Public Tracking ──

    public function test_customer_portal_shows_completed_terminal_state(): void
    {
        $order = $this->createPackedAndPaidOrder();

        $shipment = $this->shipmentService->createShipment($order, [
            'courier'           => 'JNE',
            'courier_service'   => 'Reguler',
            'recipient_name'    => 'Aisyah',
            'recipient_phone'   => '081234567890',
            'recipient_address' => 'Bandung',
        ], $this->produksi);

        $this->shipmentService->markAsShipped($shipment, $this->produksi, [
            'tracking_no' => 'JNE1234567890',
        ]);

        $this->shipmentService->markAsDelivered($shipment, $this->produksi);

        $response = $this->actingAs($this->customerUser)
            ->get(route('customer.orders.show', $order));

        $response->assertOk();

        // Must NOT say 'Sedang dalam tahapan ini'
        $response->assertDontSee('Sedang dalam tahapan ini');

        // Header status badge says 'Selesai'
        $response->assertSee('Selesai');

        // Must show final completion message
        $response->assertSee('Pesanan telah selesai dan diterima oleh customer.');

        // Must show shipment details
        $response->assertSee('JNE1234567890');
        $response->assertSee('Waktu Diterima');
    }

    // ── 9. Kanban Board places completed order in SELESAI column ──────────────

    public function test_admin_kanban_places_completed_order_in_selesai_column(): void
    {
        $order = $this->createPackedAndPaidOrder();

        $shipment = $this->shipmentService->createShipment($order, [
            'courier'           => 'JNE',
            'recipient_name'    => 'Aisyah',
            'recipient_phone'   => '081234567890',
            'recipient_address' => 'Bandung',
        ], $this->produksi);

        $this->shipmentService->markAsShipped($shipment, $this->produksi, [
            'tracking_no' => 'JNE1234567890',
        ]);

        $this->shipmentService->markAsDelivered($shipment, $this->produksi);

        $response = $this->actingAs($this->admin)->get(route('admin.kanban.index', ['search' => $order->order_no]));
        $response->assertOk();

        // View data has columns
        $columns = $response->viewData('columns');
        $this->assertArrayHasKey('SELESAI', $columns);
        $this->assertTrue($columns['SELESAI']['orders']->contains('id', $order->id));
        $this->assertFalse($columns['DIKIRIM']['orders']->contains('id', $order->id));
    }

    // ── 10. Shipment detail remains DITERIMA with no action button ────────────

    public function test_shipment_detail_page_shows_diterima_without_action_button(): void
    {
        $order = $this->createPackedAndPaidOrder();

        $shipment = $this->shipmentService->createShipment($order, [
            'courier'           => 'JNE',
            'recipient_name'    => 'Aisyah',
            'recipient_phone'   => '081234567890',
            'recipient_address' => 'Bandung',
        ], $this->produksi);

        $this->shipmentService->markAsShipped($shipment, $this->produksi, [
            'tracking_no' => 'JNE1234567890',
        ]);

        $this->shipmentService->markAsDelivered($shipment, $this->produksi);

        $response = $this->actingAs($this->produksi)->get(route('produksi.shipments.show', $shipment));
        $response->assertOk();

        $response->assertSee('Pengiriman Selesai');
        $response->assertSee('Terkirim');
        $response->assertSee('Pesanan: Selesai');
        // 'Tandai Diterima' button is gone
        $response->assertDontSee('Tandai Diterima');
    }

    // ── 11. Permanent tracking token unchanged ────────────────────────────────

    public function test_permanent_tracking_token_remains_valid_after_delivery(): void
    {
        $order = $this->createPackedAndPaidOrder();
        $token = $this->tokenService->createForOrder($order);

        $shipment = $this->shipmentService->createShipment($order, [
            'courier'           => 'JNE',
            'recipient_name'    => 'Aisyah',
            'recipient_phone'   => '081234567890',
            'recipient_address' => 'Bandung',
        ], $this->produksi);

        $this->shipmentService->markAsShipped($shipment, $this->produksi, [
            'tracking_no' => 'JNE1234567890',
        ]);

        $this->shipmentService->markAsDelivered($shipment, $this->produksi);

        // Same token URL is still HTTP 200
        $response = $this->get(route('track.show', $token));
        $response->assertOk();
        $response->assertSee($order->order_no);
        $response->assertSee('Pesanan telah selesai dan diterima oleh customer.');
    }

    // ── 12. Self-healing when shipment is DELIVERED but order was DIKIRIM ──────

    public function test_self_healing_idempotency_repairs_order_if_lagging(): void
    {
        $order = $this->createPackedAndPaidOrder();

        $shipment = $this->shipmentService->createShipment($order, [
            'courier'           => 'JNE',
            'recipient_name'    => 'Aisyah',
            'recipient_phone'   => '081234567890',
            'recipient_address' => 'Bandung',
        ], $this->produksi);

        $this->shipmentService->markAsShipped($shipment, $this->produksi, [
            'tracking_no' => 'JNE1234567890',
        ]);

        // Manually simulate a legacy edge case where shipment is DELIVERED but Order remained DIKIRIM
        $deliveryTime = Carbon::create(2026, 9, 30, 14, 6, 0, 'Asia/Jakarta')->utc();
        $shipment->update([
            'status'       => ShipmentStatus::DELIVERED,
            'delivered_at' => $deliveryTime,
        ]);
        $order->update(['status' => OrderStatus::DIKIRIM]);

        $this->assertSame(ShipmentStatus::DELIVERED, $shipment->fresh()->status);
        $this->assertSame(OrderStatus::DIKIRIM, $order->fresh()->status);

        // Calling markAsDelivered repairs the order to SELESAI using existing delivered_at
        $this->shipmentService->markAsDelivered($shipment, $this->produksi);

        $this->assertSame(OrderStatus::SELESAI, $order->fresh()->status);
        $history = OrderStatusHistory::where('order_id', $order->id)
            ->where('status', OrderStatus::SELESAI)
            ->first();
        $this->assertNotNull($history);
        $this->assertEquals($deliveryTime->timestamp, $history->changed_at->timestamp);
    }

    // ── 13. Shipment payment gate still PASS ──────────────────────────────────

    public function test_shipment_payment_gate_blocks_partial_and_allows_full(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-' . date('Ym') . '-9992',
            'order_date'  => '2026-09-30',
            'status'      => OrderStatus::PACKING,
        ]);

        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => 20,
            'unit_selling_price' => 50000,
            'subtotal'           => 1000000,
        ]);

        $batch = ProductionBatch::create([
            'batch_no'          => 'BAT-' . date('Ym') . '-9992',
            'order_item_id'     => $item->id,
            'target_qty'        => 20,
            'actual_qty'        => 20,
            'status'            => BatchStatus::SELESAI,
            'requires_printing' => false,
        ]);

        $result = ProductionResult::create([
            'batch_id'    => $batch->id,
            'result_date' => '2026-09-30',
            'produced_by' => $this->produksi->id,
            'actual_qty'  => 20,
        ]);

        QcResult::create([
            'batch_id'             => $batch->id,
            'production_result_id' => $result->id,
            'qc_date'              => '2026-09-30',
            'inspected_by'         => $this->produksi->id,
            'good_qty'             => 20,
            'reject_qty'           => 0,
        ]);

        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-' . date('Ym') . '-9992',
            'invoice_date' => '2026-09-30',
            'total_amount' => '1000000.00',
            'status'       => InvoiceStatus::PARTIAL,
        ]);

        Payment::create([
            'invoice_id'   => $invoice->id,
            'payment_no'   => 'PAY-DP-9992',
            'payment_date' => '2026-09-30',
            'amount'       => '500000.00',
            'payment_type' => PaymentType::DP,
            'status'       => PaymentStatus::CONFIRMED,
        ]);

        // Attempting to create shipment for partially paid order must fail
        $this->assertFalse($this->shipmentService->canPrepareShipment($order));

        // Add settlement payment
        Payment::create([
            'invoice_id'   => $invoice->id,
            'payment_no'   => 'PAY-PEL-9992',
            'payment_date' => '2026-09-30',
            'amount'       => '500000.00',
            'payment_type' => PaymentType::PELUNASAN,
            'status'       => PaymentStatus::CONFIRMED,
        ]);
        $invoice->update(['status' => InvoiceStatus::PAID]);

        // Now shipment is allowed
        $this->assertTrue($this->shipmentService->canPrepareShipment($order));
    }

    // ── 14. Timezone display still WIB ────────────────────────────────────────

    public function test_timezone_display_shows_wib_for_timestamps(): void
    {
        $order = $this->createPackedAndPaidOrder();

        $shipment = $this->shipmentService->createShipment($order, [
            'courier'           => 'JNE',
            'courier_service'   => 'Reguler',
            'recipient_name'    => 'Aisyah',
            'recipient_phone'   => '081234567890',
            'recipient_address' => 'Bandung',
        ], $this->produksi);

        $this->shipmentService->markAsShipped($shipment, $this->produksi, [
            'tracking_no' => 'JNE1234567890',
        ]);

        $this->shipmentService->markAsDelivered($shipment, $this->produksi);

        // Check public tracking view renders WIB
        $token = $this->tokenService->createForOrder($order);
        $response = $this->get(route('track.show', $token));
        $response->assertOk();
        $response->assertSee('WIB');

        // Check customer portal view renders WIB
        $portalResponse = $this->actingAs($this->customerUser)
            ->get(route('customer.orders.show', $order));
        $portalResponse->assertOk();
        $portalResponse->assertSee('WIB');
    }
}
