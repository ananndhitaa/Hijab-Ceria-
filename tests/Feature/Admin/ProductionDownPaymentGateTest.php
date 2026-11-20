<?php

namespace Tests\Feature\Admin;

use App\Enums\BatchStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Master\BomItem;
use App\Models\Master\Customer;
use App\Models\Master\Material;
use App\Models\Inventory\MaterialStock;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Master\Unit;
use App\Models\Production\ProductionBatch;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\Sales\Payment;
use App\Models\System\User;
use App\Services\Production\BatchService;
use App\Services\Production\ShipmentService;
use App\Services\Sales\InvoiceService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionDownPaymentGateTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $gudang;
    private Customer $customer;
    private Material $material;
    private ProductVariant $variant;
    private BatchService $batchService;
    private InvoiceService $invoiceService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->batchService = app(BatchService::class);
        $this->invoiceService = app(InvoiceService::class);

        $this->admin = User::factory()->create([
            'role'   => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->gudang = User::factory()->create([
            'role'   => UserRole::GUDANG,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->customer = Customer::create([
            'customer_name' => 'Toko Fatimah Hijab',
            'phone'         => '081234567890',
            'address'       => 'Jl. Malioboro No. 45, Yogyakarta',
        ]);

        $unit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);

        $this->material = Material::create([
            'unit_id'       => $unit->id,
            'material_name' => 'Kain Voal Polos Premium',
            'min_stock'     => 10,
            'active'        => true,
        ]);

        MaterialStock::create([
            'material_id'   => $this->material->id,
            'qty_on_hand'   => 500.0000,
            'avg_unit_cost' => 30000.0000,
        ]);

        $product = Product::create(['product_name' => 'Hijab Voal Polos Premium', 'active' => true]);

        $this->variant = ProductVariant::create([
            'product_id'                           => $product->id,
            'variant_name'                         => 'Olive Green',
            'sku'                                  => 'HJB-VP-OG-001',
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => '0.00',
            'direct_labor_cost_per_unit'           => '5000.00',
            'packaging_process_cost_per_good_unit' => '1000.00',
            'other_direct_cost_per_unit'           => '500.00',
            'active'                               => true,
        ]);

        BomItem::create([
            'variant_id'   => $this->variant->id,
            'material_id'  => $this->material->id,
            'qty_per_unit' => '1.2000',
        ]);
    }

    private function createOrder(OrderStatus $status = OrderStatus::DEAL, float $qty = 50): Order
    {
        static $seq = 1;
        $order = Order::create([
            'customer_id'  => $this->customer->id,
            'order_no'     => sprintf('SO-TEST-%04d', $seq++),
            'order_date'   => '2026-09-30',
            'status'       => $status,
            'total_amount' => $qty * 75000,
        ]);

        $order->items()->create([
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => $qty,
            'unit_selling_price' => '75000.00',
            'subtotal'           => $qty * 75000,
        ]);

        return $order;
    }

    // ── 1. DEAL without invoice cannot create batch ───────────────────────

    public function test_deal_order_without_invoice_cannot_create_batch(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 50);
        $item = $order->items->first();

        // 1. Model & Service check
        $this->assertFalse($order->hasConfirmedDownPayment());
        $this->assertFalse($this->batchService->canCreateForOrder($order));
        $this->assertFalse($this->batchService->canCreateForOrderItem($item, $order));

        $dpStatus = $order->getDownPaymentStatus();
        $this->assertFalse($dpStatus['has_confirmed_dp']);
        $this->assertSame('NO_INVOICE', $dpStatus['status_code']);
        $this->assertSame('Terbitkan invoice dan konfirmasi pembayaran DP sebelum membuat batch.', $dpStatus['message']);

        // 2. Controller GET check
        $response = $this->actingAs($this->admin)->get(route('admin.orders.batches.create', $order));
        $response->assertRedirect(route('admin.orders.show', $order));
        $response->assertSessionHas('error', 'Batch produksi belum dapat dibuat karena pembayaran DP belum diterima.');

        // 3. Controller POST direct check
        $postRes = $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 50,
        ]);
        $postRes->assertRedirect();
        $postRes->assertSessionHas('error', 'Batch produksi belum dapat dibuat karena pembayaran DP belum diterima.');
        $this->assertDatabaseCount('production_batches', 0);
    }

    // ── 2. Invoice without payment cannot create batch ────────────────────

    public function test_invoice_without_payment_cannot_create_batch(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 50);
        $item = $order->items->first();

        Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-NOPAY-001',
            'invoice_date' => now()->toDateString(),
            'total_amount' => '3750000.00',
            'status'       => InvoiceStatus::UNPAID,
        ]);

        $this->assertFalse($order->hasConfirmedDownPayment());
        $this->assertFalse($this->batchService->canCreateForOrder($order));

        $dpStatus = $order->getDownPaymentStatus();
        $this->assertFalse($dpStatus['has_confirmed_dp']);
        $this->assertSame('NO_PAYMENT', $dpStatus['status_code']);
        $this->assertSame('Menunggu pembayaran DP', $dpStatus['message']);

        $postRes = $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 50,
        ]);
        $postRes->assertSessionHas('error', 'Batch produksi belum dapat dibuat karena pembayaran DP belum diterima.');
        $this->assertDatabaseCount('production_batches', 0);
    }

    // ── 3. Pending DP cannot create batch ─────────────────────────────────

    public function test_pending_dp_cannot_create_batch(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 50);
        $item = $order->items->first();

        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-PEND-001',
            'invoice_date' => now()->toDateString(),
            'total_amount' => '3750000.00',
            'status'       => InvoiceStatus::UNPAID,
        ]);

        Payment::create([
            'invoice_id'   => $invoice->id,
            'payment_no'   => 'PAY-PEND-001',
            'payment_date' => now()->toDateString(),
            'amount'       => '1500000.00',
            'payment_type' => PaymentType::DP,
            'status'       => PaymentStatus::PENDING,
        ]);

        $this->assertFalse($order->hasConfirmedDownPayment());
        $this->assertFalse($this->batchService->canCreateForOrder($order));

        $dpStatus = $order->getDownPaymentStatus();
        $this->assertFalse($dpStatus['has_confirmed_dp']);
        $this->assertSame('PENDING', $dpStatus['status_code']);
        $this->assertSame('Pembayaran DP menunggu konfirmasi', $dpStatus['message']);

        $postRes = $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 50,
        ]);
        $postRes->assertSessionHas('error', 'Batch produksi belum dapat dibuat karena pembayaran DP belum diterima.');
        $this->assertDatabaseCount('production_batches', 0);
    }

    // ── 4. Cancelled payment cannot create batch ──────────────────────────

    public function test_cancelled_payment_cannot_create_batch(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 50);
        $item = $order->items->first();

        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-CANC-001',
            'invoice_date' => now()->toDateString(),
            'total_amount' => '3750000.00',
            'status'       => InvoiceStatus::UNPAID,
        ]);

        Payment::create([
            'invoice_id'   => $invoice->id,
            'payment_no'   => 'PAY-CANC-001',
            'payment_date' => now()->toDateString(),
            'amount'       => '1500000.00',
            'payment_type' => PaymentType::DP,
            'status'       => PaymentStatus::CANCELLED,
        ]);

        $this->assertFalse($order->hasConfirmedDownPayment());
        $this->assertFalse($this->batchService->canCreateForOrder($order));

        $dpStatus = $order->getDownPaymentStatus();
        $this->assertFalse($dpStatus['has_confirmed_dp']);
        $this->assertSame('CANCELLED_ONLY', $dpStatus['status_code']);
        $this->assertSame('Menunggu pembayaran DP', $dpStatus['message']);

        $postRes = $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 50,
        ]);
        $postRes->assertSessionHas('error', 'Batch produksi belum dapat dibuat karena pembayaran DP belum diterima.');
        $this->assertDatabaseCount('production_batches', 0);
    }

    // ── 5. Confirmed payment > 0 allows batch ─────────────────────────────

    public function test_confirmed_payment_greater_than_zero_allows_batch(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 50);
        $item = $order->items->first();

        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-CONF-001',
            'invoice_date' => now()->toDateString(),
            'total_amount' => '3750000.00',
            'status'       => InvoiceStatus::PARTIAL,
        ]);

        Payment::create([
            'invoice_id'   => $invoice->id,
            'payment_no'   => 'PAY-CONF-001',
            'payment_date' => now()->toDateString(),
            'amount'       => '1000000.00',
            'payment_type' => PaymentType::DP,
            'status'       => PaymentStatus::CONFIRMED,
        ]);

        $this->assertTrue($order->hasConfirmedDownPayment());
        $this->assertTrue($this->batchService->canCreateForOrder($order));
        $this->assertTrue($this->batchService->canCreateForOrderItem($item, $order));

        $dpStatus = $order->getDownPaymentStatus();
        $this->assertTrue($dpStatus['has_confirmed_dp']);
        $this->assertSame('CONFIRMED', $dpStatus['status_code']);
        $this->assertSame('DP telah diterima.', $dpStatus['message']);

        // GET batch create page is accessible
        $getRes = $this->actingAs($this->admin)->get(route('admin.orders.batches.create', $order));
        $getRes->assertStatus(200);

        // POST batch create succeeds
        $postRes = $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 50,
        ]);

        $postRes->assertSessionHasNoErrors();
        $batch = ProductionBatch::first();
        $this->assertNotNull($batch);
        $this->assertSame('50.0000', (string) $batch->target_qty);
        $this->assertSame(OrderStatus::BAHAN, $order->fresh()->status);
    }

    // ── 6. PARTIAL invoice with confirmed DP allows batch ─────────────────

    public function test_partial_invoice_with_confirmed_dp_allows_batch(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 100);
        $item = $order->items->first();

        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-PART-001',
            'invoice_date' => now()->toDateString(),
            'total_amount' => '7500000.00',
            'status'       => InvoiceStatus::PARTIAL,
        ]);

        // Customer pays 30% DP: Rp 2.250.000 (No percentage requirement, any confirmed > 0 is valid)
        Payment::create([
            'invoice_id'   => $invoice->id,
            'payment_no'   => 'PAY-PART-001',
            'payment_date' => now()->toDateString(),
            'amount'       => '2250000.00',
            'payment_type' => PaymentType::DP,
            'status'       => PaymentStatus::CONFIRMED,
        ]);

        $this->assertTrue($order->hasConfirmedDownPayment());
        $this->assertFalse($order->isFullyPaid()); // Not fully paid, but DP is confirmed!
        $this->assertTrue($this->batchService->canCreateForOrder($order));

        $res = $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 100,
        ]);
        $res->assertSessionHasNoErrors();
        $this->assertDatabaseCount('production_batches', 1);
    }

    // ── 7. Fully paid invoice allows batch ─────────────────────────────────

    public function test_fully_paid_invoice_allows_batch(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 100);
        $item = $order->items->first();

        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-FULL-001',
            'invoice_date' => now()->toDateString(),
            'total_amount' => '7500000.00',
            'status'       => InvoiceStatus::PAID,
        ]);

        Payment::create([
            'invoice_id'   => $invoice->id,
            'payment_no'   => 'PAY-FULL-001',
            'payment_date' => now()->toDateString(),
            'amount'       => '7500000.00',
            'payment_type' => PaymentType::PELUNASAN,
            'status'       => PaymentStatus::CONFIRMED,
        ]);

        $this->assertTrue($order->hasConfirmedDownPayment());
        $this->assertTrue($order->isFullyPaid());
        $this->assertTrue($this->batchService->canCreateForOrder($order));

        $res = $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 100,
        ]);
        $res->assertSessionHasNoErrors();
        $this->assertDatabaseCount('production_batches', 1);
    }

    // ── 8. Batch button disabled before DP on order detail page ───────────

    public function test_batch_button_disabled_in_ui_before_dp(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 50);

        // Case A: No Invoice
        $responseA = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));
        $responseA->assertStatus(200);
        $responseA->assertSee('Buat Invoice');
        $responseA->assertSee('disabled');
        $responseA->assertDontSee('Menunggu DP');
        $responseA->assertDontSee('Menunggu Pembayaran DP');
        $responseA->assertDontSee('Menunggu pembayaran DP');

        // Case B: Invoice exists, no payment
        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-UI-001',
            'invoice_date' => now()->toDateString(),
            'total_amount' => '3750000.00',
            'status'       => InvoiceStatus::UNPAID,
        ]);

        $responseB = $this->actingAs($this->admin)->get(route('admin.orders.show', $order->fresh(['invoice.payments'])));
        $responseB->assertStatus(200);
        $responseB->assertSee('disabled');
        $responseB->assertDontSee('Menunggu DP');
        $responseB->assertDontSee('Menunggu Pembayaran DP');
        $responseB->assertDontSee('Menunggu pembayaran DP');

        // Case C: Payment pending
        $payment = Payment::create([
            'invoice_id'   => $invoice->id,
            'payment_no'   => 'PAY-UI-001',
            'payment_date' => now()->toDateString(),
            'amount'       => '1500000.00',
            'payment_type' => PaymentType::DP,
            'status'       => PaymentStatus::PENDING,
        ]);

        $responseC = $this->actingAs($this->admin)->get(route('admin.orders.show', $order->fresh(['invoice.payments'])));
        $responseC->assertStatus(200);
        $responseC->assertSee('disabled');
        $responseC->assertDontSee('Menunggu DP');
        $responseC->assertDontSee('Menunggu Pembayaran DP');
        $responseC->assertDontSee('Pembayaran DP menunggu konfirmasi');

        // Case D: Payment confirmed -> Button enabled
        $payment->update(['status' => PaymentStatus::CONFIRMED]);
        $invoice->update(['status' => InvoiceStatus::PARTIAL]);

        $responseD = $this->actingAs($this->admin)->get(route('admin.orders.show', $order->fresh(['invoice.payments'])));
        $responseD->assertStatus(200);
        $responseD->assertSee(route('admin.orders.batches.create', $order));
    }

    // ── 9. Per-item "+ Buat Batch" disabled before DP in UI ───────────────

    public function test_per_item_batch_button_disabled_in_ui_before_dp(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 50);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));
        $response->assertStatus(200);
        $response->assertSee('cursor-not-allowed', false);
        $response->assertDontSee('Menunggu DP');
        $response->assertDontSee('Menunggu Pembayaran DP');
    }

    // ── 10. Direct POST blocked before DP ─────────────────────────────────

    public function test_direct_post_blocked_before_dp(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 50);
        $item = $order->items->first();

        // Crafted direct POST without invoice or payment
        $response = $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 50,
        ]);

        $response->assertSessionHas('error', 'Batch produksi belum dapat dibuat karena pembayaran DP belum diterima.');
        $this->assertDatabaseCount('production_batches', 0);
        $this->assertSame(OrderStatus::DEAL, $order->fresh()->status);
    }

    // ── 11. Existing Admin-only batch authorization remains PASS ──────────

    public function test_non_admin_cannot_access_or_post_batch_creation(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 50);
        $item = $order->items->first();

        // Even with confirmed DP, Gudang cannot create batch
        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-AUTH-001',
            'invoice_date' => now()->toDateString(),
            'total_amount' => '3750000.00',
            'status'       => InvoiceStatus::PARTIAL,
        ]);
        Payment::create([
            'invoice_id'   => $invoice->id,
            'payment_no'   => 'PAY-AUTH-001',
            'payment_date' => now()->toDateString(),
            'amount'       => '1500000.00',
            'payment_type' => PaymentType::DP,
            'status'       => PaymentStatus::CONFIRMED,
        ]);

        $this->actingAs($this->gudang)->get(route('admin.orders.batches.create', $order))->assertStatus(403);
        $this->actingAs($this->gudang)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 50,
        ])->assertStatus(403);
    }

    // ── 12. Multiple batches allowed after valid DP according to remaining qty

    public function test_multiple_batches_allowed_after_valid_dp_until_fully_batched(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 100);
        $item = $order->items->first();

        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-MULTI-001',
            'invoice_date' => now()->toDateString(),
            'total_amount' => '7500000.00',
            'status'       => InvoiceStatus::PARTIAL,
        ]);
        Payment::create([
            'invoice_id'   => $invoice->id,
            'payment_no'   => 'PAY-MULTI-001',
            'payment_date' => now()->toDateString(),
            'amount'       => '3000000.00',
            'payment_type' => PaymentType::DP,
            'status'       => PaymentStatus::CONFIRMED,
        ]);

        // Batch 1: 40 pcs
        $res1 = $this->batchService->createBatch($order, [
            'order_item_id' => $item->id,
            'target_qty'    => 40,
        ], $this->admin);
        $this->assertNotNull($res1['batch']);
        $this->assertSame(OrderStatus::BAHAN, $order->fresh()->status);
        $this->assertTrue($this->batchService->canCreateForOrder($order->fresh()));

        // Batch 2: 60 pcs (remaining)
        $res2 = $this->batchService->createBatch($order->fresh(), [
            'order_item_id' => $item->id,
            'target_qty'    => 60,
        ], $this->admin);
        $this->assertNotNull($res2['batch']);

        // Now remaining is 0 -> cannot create more batches
        $this->assertFalse($this->batchService->canCreateForOrder($order->fresh()));
        $this->assertFalse($this->batchService->hasRemainingCapacity($order->fresh()));
    }

    // ── 13. Shipment still requires FULL PAYMENT ──────────────────────────

    public function test_shipment_still_requires_full_payment_even_when_dp_confirmed(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 20);
        $item = $order->items->first();

        // 1. Partial DP confirmed
        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-SHIP-001',
            'invoice_date' => now()->toDateString(),
            'total_amount' => '1500000.00',
            'status'       => InvoiceStatus::PARTIAL,
        ]);
        Payment::create([
            'invoice_id'   => $invoice->id,
            'payment_no'   => 'PAY-SHIP-001',
            'payment_date' => now()->toDateString(),
            'amount'       => '750000.00',
            'payment_type' => PaymentType::DP,
            'status'       => PaymentStatus::CONFIRMED,
        ]);

        // Production batch is allowed with DP
        $this->assertTrue($order->hasConfirmedDownPayment());
        $this->assertTrue($this->batchService->canCreateForOrder($order));

        $res = $this->batchService->createBatch($order, [
            'order_item_id' => $item->id,
            'target_qty'    => 20,
        ], $this->admin);
        $batch = $res['batch'];

        // Batch completes QC & packaging
        $batch->update(['status' => BatchStatus::SELESAI]);
        $prodResult = \App\Models\Production\ProductionResult::create([
            'batch_id'    => $batch->id,
            'actual_qty'  => 20,
            'result_date' => now()->toDateString(),
            'recorded_by' => $this->admin->id,
        ]);
        \App\Models\Production\QcResult::create([
            'production_result_id' => $prodResult->id,
            'good_qty'             => 20,
            'reject_qty'           => 0,
            'qc_date'              => now()->toDateString(),
            'qc_by'                => $this->admin->id,
        ]);

        // Shipment check
        $shipmentService = app(ShipmentService::class);
        $eligibility = $shipmentService->getEligibility($order->fresh());

        // Physical fulfillment is true, but commercial payment settlement is FALSE!
        $this->assertTrue($eligibility['all_items_fulfilled']);
        $this->assertFalse($eligibility['is_payment_settled']);
        $this->assertFalse($eligibility['is_eligible']);
        $this->assertContains('Pesanan belum dapat dikirim karena pembayaran belum lunas.', $eligibility['reasons']);

        // After Pelunasan payment -> is_payment_settled becomes true!
        Payment::create([
            'invoice_id'   => $invoice->id,
            'payment_no'   => 'PAY-SHIP-002',
            'payment_date' => now()->toDateString(),
            'amount'       => '750000.00',
            'payment_type' => PaymentType::PELUNASAN,
            'status'       => PaymentStatus::CONFIRMED,
        ]);
        $invoice->update(['status' => InvoiceStatus::PAID]);

        $eligibilityAfter = $shipmentService->getEligibility($order->fresh(['invoice.payments']));
        $this->assertTrue($eligibilityAfter['is_payment_settled']);
    }

    // ── 14. Permanent tracking token unchanged ────────────────────────────

    public function test_permanent_tracking_token_remains_active(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 20);
        $tokenService = app(\App\Services\Tracking\TrackingTokenService::class);
        $rawToken = $tokenService->createForOrder($order);

        $this->assertTrue($order->hasActiveTrackingToken());

        // Public tracking page is viewable
        $response = $this->get('/track/' . $rawToken);
        $response->assertStatus(200);
        $response->assertSee($order->order_no);
    }
}
