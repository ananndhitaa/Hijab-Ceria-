<?php

namespace Tests\Feature\Produksi;

use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Enums\ShipmentStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Inventory\MaterialStock;
use App\Models\Inventory\StockMovement;
use App\Models\Master\Customer;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Master\Unit;
use App\Models\Production\BatchMaterial;
use App\Models\Production\ProductionBatch;
use App\Models\Production\ProductionResult;
use App\Models\Production\QcResult;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\OrderStatusHistory;
use App\Models\System\Shipment;
use App\Models\System\User;
use App\Services\Tracking\TrackingTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentWorkflowFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $produksiUser;
    private Customer $customer;
    private Product $product;
    private ProductVariant $variant;
    private Material $kain;
    private MaterialStock $kainStock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->produksiUser = User::factory()->create([
            'role'   => UserRole::PRODUKSI,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->customer = Customer::create([
            'customer_name' => 'Ny. Sarah Amelia',
            'phone'         => '081299988877',
            'address'       => 'Jl. Riau No. 45, Bandung',
        ]);

        $unit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $this->kain = Material::create([
            'material_name' => 'Kain Silk Sutra',
            'unit_id'       => $unit->id,
            'cost_per_unit' => 45000,
            'active'        => true,
        ]);

        $this->kainStock = MaterialStock::create([
            'material_id'   => $this->kain->id,
            'qty_on_hand'   => 500.0000,
            'avg_unit_cost' => 45000.0000,
        ]);

        $this->product = Product::create(['product_name' => 'Pashmina Silk Premium', 'active' => true]);
        $this->variant = ProductVariant::create([
            'product_id'        => $this->product->id,
            'variant_name'      => 'Dusty Mauve',
            'sku'               => 'PSH-SLK-DM',
            'requires_printing' => false,
            'active'            => true,
        ]);
    }

    private function createOrderReadyForShipment(float $qtyOrdered = 20, float $goodQty = 20, float $rejectQty = 0, bool $paid = true): array
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'ORD-SHIP-' . uniqid(),
            'order_date'  => now()->toDateString(),
            'status'      => OrderStatus::PACKING,
        ]);

        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => $qtyOrdered,
            'unit_selling_price' => 75000,
            'subtotal'           => $qtyOrdered * 75000,
        ]);

        if ($paid) {
            $totalAmount = bcmul((string) $qtyOrdered, '75000', 2);
            $invoice = \App\Models\Sales\Invoice::create([
                'order_id'     => $order->id,
                'invoice_no'   => 'INV-' . uniqid(),
                'invoice_date' => now()->toDateString(),
                'total_amount' => $totalAmount,
                'status'       => \App\Enums\InvoiceStatus::PAID,
            ]);

            \App\Models\Sales\Payment::create([
                'invoice_id'   => $invoice->id,
                'payment_no'   => 'PAY-' . uniqid(),
                'payment_date' => now()->toDateString(),
                'amount'       => $totalAmount,
                'payment_type' => \App\Enums\PaymentType::PELUNASAN,
                'status'       => \App\Enums\PaymentStatus::CONFIRMED,
            ]);
        }

        $batch = ProductionBatch::create([
            'order_item_id'     => $item->id,
            'batch_no'          => 'BAT-SH-' . uniqid(),
            'target_qty'        => $qtyOrdered,
            'status'            => BatchStatus::SELESAI,
            'requires_printing' => false,
        ]);

        BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->kain->id,
            'required_qty'  => $qtyOrdered * 2,
            'allocated_qty' => $qtyOrdered * 2,
            'used_qty'      => $qtyOrdered * 2,
        ]);

        $prodResult = ProductionResult::create([
            'batch_id'    => $batch->id,
            'actual_qty'  => $goodQty + $rejectQty,
            'result_date' => now()->toDateString(),
            'recorded_by' => $this->produksiUser->id,
        ]);

        QcResult::create([
            'production_result_id' => $prodResult->id,
            'good_qty'             => $goodQty,
            'reject_qty'           => $rejectQty,
            'qc_date'              => now()->toDateString(),
            'qc_by'                => $this->produksiUser->id,
        ]);

        return [$order, $item, $batch];
    }

    public function test_order_packing_with_all_batches_finished_and_full_good_qty_is_eligible_for_shipment(): void
    {
        [$order] = $this->createOrderReadyForShipment(20, 20, 0);

        // 1. Check Index shows order in Antrean Siap Kirim
        $response = $this->actingAs($this->produksiUser)
            ->get(route('produksi.shipments.index', ['tab' => 'ready']));

        $response->assertOk();
        $response->assertSee($order->order_no);
        $response->assertSee('Siapkan Pengiriman');

        // 2. Open Create Form
        $createResponse = $this->actingAs($this->produksiUser)
            ->get(route('produksi.shipments.create', ['order_id' => $order->id]));

        $createResponse->assertOk();
        $createResponse->assertSee($order->order_no);
        $createResponse->assertSee('Siap Kirim');
        $createResponse->assertSee($this->customer->address);
    }

    public function test_batch_still_in_progress_blocks_shipment_preparation(): void
    {
        [$order, , $batch] = $this->createOrderReadyForShipment(20, 20, 0);

        // Set batch back to PRODUKSI
        $batch->update(['status' => BatchStatus::PRODUKSI]);

        $response = $this->actingAs($this->produksiUser)
            ->get(route('produksi.shipments.create', ['order_id' => $order->id]));

        $response->assertRedirect(route('produksi.shipments.index'));
        $response->assertSessionHas('error');
    }

    public function test_short_good_quantity_blocks_shipment_preparation_and_creation(): void
    {
        // Ordered 20, but only 18 Good, 2 Reject
        [$order] = $this->createOrderReadyForShipment(20, 18, 2);

        $response = $this->actingAs($this->produksiUser)
            ->get(route('produksi.shipments.create', ['order_id' => $order->id]));

        $response->assertRedirect(route('produksi.shipments.index'));
        $response->assertSessionHas('error');

        // Direct POST to store also rejected
        $storeResponse = $this->actingAs($this->produksiUser)
            ->post(route('produksi.shipments.store'), [
                'order_id' => $order->id,
                'courier'  => 'JNE',
            ]);

        $storeResponse->assertRedirect();
        $storeResponse->assertSessionHas('error');
        $this->assertEquals(0, Shipment::where('order_id', $order->id)->count());
    }

    public function test_creating_shipment_as_pending_without_tracking_no_is_allowed_and_order_remains_packing(): void
    {
        [$order] = $this->createOrderReadyForShipment(20, 20, 0);

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.shipments.store'), [
                'order_id'          => $order->id,
                'courier'           => 'J&T Express',
                'courier_service'   => 'EZ Regular',
                'tracking_no'       => null, // Tracking no intentionally empty for draft
                'shipping_cost'     => 18000,
                'recipient_address' => 'Jl. Buah Batu No. 10, Bandung',
                'action'            => 'draft',
            ]);

        $shipment = Shipment::where('order_id', $order->id)->first();
        $this->assertNotNull($shipment);
        $this->assertEquals(ShipmentStatus::PENDING, $shipment->status);
        $this->assertEquals('J&T Express', $shipment->courier);
        $this->assertNull($shipment->tracking_no);
        $this->assertEquals('18000.00', (string) $shipment->shipping_cost);

        $response->assertRedirect(route('produksi.shipments.show', $shipment));
        $response->assertSessionHas('success');

        // Order status must remain PACKING
        $this->assertEquals(OrderStatus::PACKING, $order->fresh()->status);
    }

    public function test_attempt_shipped_without_tracking_no_is_rejected(): void
    {
        [$order] = $this->createOrderReadyForShipment(20, 20, 0);

        // Case 1: Direct Store with action = 'ship' and empty tracking_no
        $storeResponse = $this->actingAs($this->produksiUser)
            ->post(route('produksi.shipments.store'), [
                'order_id'          => $order->id,
                'courier'           => 'JNE',
                'tracking_no'       => '',
                'recipient_address' => $this->customer->address,
                'action'            => 'ship',
            ]);

        $storeResponse->assertSessionHasErrors(['tracking_no']);
        $this->assertEquals(0, Shipment::where('order_id', $order->id)->count());
        $this->assertEquals(OrderStatus::PACKING, $order->fresh()->status);

        // Case 2: Mark existing PENDING shipment as SHIPPED without providing tracking_no
        $shipment = Shipment::create([
            'order_id'          => $order->id,
            'courier'           => 'JNE',
            'tracking_no'       => null,
            'recipient_address' => $this->customer->address,
            'status'            => ShipmentStatus::PENDING,
        ]);

        $shipResponse = $this->actingAs($this->produksiUser)
            ->post(route('produksi.shipments.ship', $shipment), [
                'tracking_no' => '',
            ]);

        $shipResponse->assertRedirect();
        $shipResponse->assertSessionHas('error', 'Nomor resi wajib diisi sebelum pengiriman ditandai Dikirim.');

        // Shipment remains PENDING, Order remains PACKING
        $this->assertEquals(ShipmentStatus::PENDING, $shipment->fresh()->status);
        $this->assertEquals(OrderStatus::PACKING, $order->fresh()->status);
    }

    public function test_providing_tracking_no_allows_shipped_and_transitions_order_to_dikirim(): void
    {
        [$order] = $this->createOrderReadyForShipment(20, 20, 0);

        $shipment = Shipment::create([
            'order_id'          => $order->id,
            'courier'           => 'SiCepat',
            'courier_service'   => 'BEST',
            'tracking_no'       => null,
            'shipping_cost'     => 22000,
            'recipient_address' => $this->customer->address,
            'status'            => ShipmentStatus::PENDING,
        ]);

        $historiesCountBefore = OrderStatusHistory::where('order_id', $order->id)->count();

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.shipments.ship', $shipment), [
                'courier'     => 'SiCepat Express',
                'tracking_no' => '0029384756',
            ]);

        $response->assertRedirect(route('produksi.shipments.show', $shipment));
        $response->assertSessionHas('success');

        $freshShipment = $shipment->fresh();
        $this->assertEquals(ShipmentStatus::SHIPPED, $freshShipment->status);
        $this->assertEquals('0029384756', $freshShipment->tracking_no);
        $this->assertNotNull($freshShipment->shipped_at);

        // Order must transition to DIKIRIM
        $this->assertEquals(OrderStatus::DIKIRIM, $order->fresh()->status);

        // Exactly one new OrderStatusHistory created
        $historiesCountAfter = OrderStatusHistory::where('order_id', $order->id)->count();
        $this->assertEquals($historiesCountBefore + 1, $historiesCountAfter);

        $latestHistory = OrderStatusHistory::where('order_id', $order->id)->latest()->first();
        $this->assertEquals(OrderStatus::DIKIRIM, $latestHistory->status);
        $this->assertStringContainsString('SiCepat Express', $latestHistory->notes);
        $this->assertStringContainsString('0029384756', $latestHistory->notes);
    }

    public function test_marking_shipment_delivered_advances_order_to_selesai_with_one_history(): void
    {
        [$order] = $this->createOrderReadyForShipment(20, 20, 0);

        $shipment = Shipment::create([
            'order_id'          => $order->id,
            'courier'           => 'JNE',
            'courier_service'   => 'REG',
            'tracking_no'       => 'JNE12345678',
            'shipping_cost'     => 15000,
            'recipient_address' => $this->customer->address,
            'status'            => ShipmentStatus::SHIPPED,
            'shipped_at'        => now(),
        ]);
        $order->update(['status' => OrderStatus::DIKIRIM]);

        $historiesCountBefore = OrderStatusHistory::where('order_id', $order->id)->count();

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.shipments.deliver', $shipment), [
                'notes' => 'Paket diterima oleh Pak Budi (Security).',
            ]);

        $response->assertRedirect(route('produksi.shipments.show', $shipment));
        $response->assertSessionHas('success');

        $freshShipment = $shipment->fresh();
        $this->assertEquals(ShipmentStatus::DELIVERED, $freshShipment->status);
        $this->assertNotNull($freshShipment->delivered_at);
        $this->assertEquals('Paket diterima oleh Pak Budi (Security).', $freshShipment->notes);

        // Order must transition to SELESAI
        $this->assertEquals(OrderStatus::SELESAI, $order->fresh()->status);

        // Exactly one new OrderStatusHistory created
        $historiesCountAfter = OrderStatusHistory::where('order_id', $order->id)->count();
        $this->assertEquals($historiesCountBefore + 1, $historiesCountAfter);

        $latestHistory = OrderStatusHistory::where('order_id', $order->id)->latest()->first();
        $this->assertEquals(OrderStatus::SELESAI, $latestHistory->status);
        $this->assertEquals('Paket diterima oleh Pak Budi (Security).', $latestHistory->notes);
    }

    public function test_double_submit_on_ship_and_deliver_is_idempotent_and_safe(): void
    {
        [$order] = $this->createOrderReadyForShipment(20, 20, 0);

        $shipment = Shipment::create([
            'order_id'          => $order->id,
            'courier'           => 'JNE',
            'tracking_no'       => 'JNE-IDEMP-01',
            'recipient_address' => $this->customer->address,
            'status'            => ShipmentStatus::PENDING,
        ]);

        // 1st Ship
        $this->actingAs($this->produksiUser)->post(route('produksi.shipments.ship', $shipment));
        $this->assertEquals(OrderStatus::DIKIRIM, $order->fresh()->status);
        $histories1 = OrderStatusHistory::where('order_id', $order->id)->count();

        // 2nd Ship (Idempotent)
        $this->actingAs($this->produksiUser)->post(route('produksi.shipments.ship', $shipment));
        $this->assertEquals(OrderStatus::DIKIRIM, $order->fresh()->status);
        $this->assertEquals($histories1, OrderStatusHistory::where('order_id', $order->id)->count());

        // 1st Deliver
        $this->actingAs($this->produksiUser)->post(route('produksi.shipments.deliver', $shipment));
        $this->assertEquals(OrderStatus::SELESAI, $order->fresh()->status);
        $histories2 = OrderStatusHistory::where('order_id', $order->id)->count();
        $this->assertEquals($histories1 + 1, $histories2);

        // 2nd Deliver (Idempotent)
        $this->actingAs($this->produksiUser)->post(route('produksi.shipments.deliver', $shipment));
        $this->assertEquals(OrderStatus::SELESAI, $order->fresh()->status);
        $this->assertEquals($histories2, OrderStatusHistory::where('order_id', $order->id)->count());
    }

    public function test_duplicate_active_shipment_creation_is_rejected(): void
    {
        [$order] = $this->createOrderReadyForShipment(20, 20, 0);

        // 1st Shipment created
        Shipment::create([
            'order_id'          => $order->id,
            'courier'           => 'JNE',
            'tracking_no'       => 'JNE-ACTIVE-1',
            'recipient_address' => $this->customer->address,
            'status'            => ShipmentStatus::PENDING,
        ]);

        // Attempt 2nd Shipment
        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.shipments.store'), [
                'order_id' => $order->id,
                'courier'  => 'J&T',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals(1, Shipment::where('order_id', $order->id)->count());
    }

    public function test_multi_item_order_short_on_one_item_blocks_shipment(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'ORD-MULTI-' . uniqid(),
            'order_date'  => now()->toDateString(),
            'status'      => OrderStatus::PACKING,
        ]);

        $variant2 = ProductVariant::create([
            'product_id'        => $this->product->id,
            'variant_name'      => 'Sage Green',
            'sku'               => 'PSH-SLK-SG',
            'requires_printing' => false,
            'active'            => true,
        ]);

        // Item 1: 10 ordered, 10 good -> Fully satisfied
        $item1 = OrderItem::create(['order_id' => $order->id, 'variant_id' => $this->variant->id, 'qty_ordered' => 10, 'unit_selling_price' => 50000]);
        $batch1 = ProductionBatch::create(['order_item_id' => $item1->id, 'batch_no' => 'BAT-M1', 'target_qty' => 10, 'status' => BatchStatus::SELESAI, 'requires_printing' => false]);
        $pr1 = ProductionResult::create(['batch_id' => $batch1->id, 'actual_qty' => 10, 'result_date' => now()->toDateString(), 'recorded_by' => $this->produksiUser->id]);
        QcResult::create(['production_result_id' => $pr1->id, 'good_qty' => 10, 'reject_qty' => 0, 'qc_date' => now()->toDateString(), 'qc_by' => $this->produksiUser->id]);

        // Item 2: 10 ordered, 8 good, 2 reject -> Short
        $item2 = OrderItem::create(['order_id' => $order->id, 'variant_id' => $variant2->id, 'qty_ordered' => 10, 'unit_selling_price' => 50000]);
        $batch2 = ProductionBatch::create(['order_item_id' => $item2->id, 'batch_no' => 'BAT-M2', 'target_qty' => 10, 'status' => BatchStatus::SELESAI, 'requires_printing' => false]);
        $pr2 = ProductionResult::create(['batch_id' => $batch2->id, 'actual_qty' => 10, 'result_date' => now()->toDateString(), 'recorded_by' => $this->produksiUser->id]);
        QcResult::create(['production_result_id' => $pr2->id, 'good_qty' => 8, 'reject_qty' => 2, 'qc_date' => now()->toDateString(), 'qc_by' => $this->produksiUser->id]);

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.shipments.store'), [
                'order_id' => $order->id,
                'courier'  => 'JNE',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals(0, Shipment::where('order_id', $order->id)->count());
    }

    public function test_good_surplus_qualifies_for_shipment_and_does_not_mutate_order_quantity(): void
    {
        // 20 ordered, 22 good output produced
        [$order, $item] = $this->createOrderReadyForShipment(20, 22, 0);

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.shipments.store'), [
                'order_id'          => $order->id,
                'courier'           => 'JNE',
                'tracking_no'       => 'JNE-SURPLUS-01',
                'recipient_address' => $this->customer->address,
                'action'            => 'ship',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(OrderStatus::DIKIRIM, $order->fresh()->status);
        $this->assertEquals('20.0000', (string) $item->fresh()->qty_ordered);
        $this->assertEquals(20, $order->fresh()->total_qty);
    }

    public function test_shipment_recipient_address_is_a_snapshot(): void
    {
        [$order] = $this->createOrderReadyForShipment(20, 20, 0);

        $shipment = Shipment::create([
            'order_id'          => $order->id,
            'courier'           => 'JNE',
            'recipient_address' => 'Jl. Alamat Awal No. 1, Bandung',
            'status'            => ShipmentStatus::SHIPPED,
        ]);

        // Customer edits address in master data
        $this->customer->update(['address' => 'Jl. Alamat Baru No. 99, Jakarta']);

        $this->assertEquals('Jl. Alamat Awal No. 1, Bandung', $shipment->fresh()->recipient_address);
    }

    public function test_public_customer_tracking_displays_progression_without_confidential_leak(): void
    {
        [$order] = $this->createOrderReadyForShipment(20, 20, 0);

        $tokenService = app(TrackingTokenService::class);
        $token = $tokenService->createForOrder($order);

        // 1. Stage PACKING -> 'Pengemasan Produk'
        $res1 = $this->get("/track/{$token}");
        $res1->assertOk();
        $res1->assertSee('Pengemasan Produk');

        // 2. Stage DIKIRIM
        $order->update(['status' => OrderStatus::DIKIRIM]);
        $res2 = $this->get("/track/{$token}");
        $res2->assertOk();
        $res2->assertSee('Pesanan Dikirim');

        // 3. Stage SELESAI
        $order->update(['status' => OrderStatus::SELESAI]);
        $res3 = $this->get("/track/{$token}");
        $res3->assertOk();
        $res3->assertSee('Pesanan Selesai');

        // Confidential metrics NOT exposed
        $res3->assertDontSee('HPP');
        $res3->assertDontSee('Profit');
        $res3->assertDontSee('BatchCost');
    }

    public function test_shipping_workflow_has_zero_inventory_and_financial_side_effects(): void
    {
        [$order] = $this->createOrderReadyForShipment(20, 20, 0);

        $stockBefore = $this->kainStock->fresh()->qty_on_hand;
        $costBefore = $this->kainStock->fresh()->avg_unit_cost;
        $movementCountBefore = StockMovement::count();

        // Create & Ship
        $this->actingAs($this->produksiUser)->post(route('produksi.shipments.store'), [
            'order_id'          => $order->id,
            'courier'           => 'JNE Cargo',
            'tracking_no'       => 'JNE-FIN-01',
            'recipient_address' => $this->customer->address,
            'shipping_cost'     => 50000,
            'action'            => 'ship',
        ]);

        $shipment = Shipment::where('order_id', $order->id)->first();

        // Deliver
        $this->actingAs($this->produksiUser)->post(route('produksi.shipments.deliver', $shipment));

        // Inventory verification
        $this->assertEquals($stockBefore, $this->kainStock->fresh()->qty_on_hand);
        $this->assertEquals($costBefore, $this->kainStock->fresh()->avg_unit_cost);
        $this->assertEquals($movementCountBefore, StockMovement::count());

        // Financial verification: shipment preserves invoice without creating side-effect expenses
        $this->assertNotNull($order->fresh()->invoice);
        $this->assertEquals(1500000, (float) $order->fresh()->invoice->total_amount);
        $this->assertEquals(0, \App\Models\Finance\BusinessExpense::where('order_id', $order->id)->count());
    }

    public function test_strict_role_authorization_for_shipment_routes(): void
    {
        [$order] = $this->createOrderReadyForShipment(20, 20, 0);
        $shipment = Shipment::create([
            'order_id' => $order->id,
            'courier'  => 'JNE',
            'status'   => ShipmentStatus::PENDING,
        ]);

        $rolesForbidden = [
            UserRole::ADMIN,
            UserRole::GUDANG,
            UserRole::OWNER,
            UserRole::CUSTOMER,
        ];

        foreach ($rolesForbidden as $role) {
            $user = User::factory()->create(['role' => $role, 'status' => UserStatus::ACTIVE]);

            $this->actingAs($user)->get(route('produksi.shipments.index'))->assertForbidden();
            $this->actingAs($user)->get(route('produksi.shipments.create', ['order_id' => $order->id]))->assertForbidden();
            $this->actingAs($user)->get(route('produksi.shipments.show', $shipment))->assertForbidden();
            $this->actingAs($user)->post(route('produksi.shipments.store'), ['order_id' => $order->id])->assertForbidden();
            $this->actingAs($user)->post(route('produksi.shipments.ship', $shipment))->assertForbidden();
            $this->actingAs($user)->post(route('produksi.shipments.deliver', $shipment))->assertForbidden();
        }

        // Guest redirect to login
        auth()->logout();
        $this->get(route('produksi.shipments.index'))->assertRedirect(route('login'));
    }

    public function test_shipment_show_page_shipped_state_presentation(): void
    {
        [$order] = $this->createOrderReadyForShipment(20, 20, 0);
        $shipment = Shipment::create([
            'order_id'          => $order->id,
            'courier'           => 'JNE Express',
            'courier_service'   => 'Reguler',
            'tracking_no'       => 'JNE-SHOW-12345',
            'shipping_cost'     => 15000,
            'recipient_address' => $this->customer->address,
            'status'            => ShipmentStatus::SHIPPED,
            'shipped_at'        => now(),
        ]);
        $order->update(['status' => OrderStatus::DIKIRIM]);

        $response = $this->actingAs($this->produksiUser)
            ->get(route('produksi.shipments.show', $shipment));

        $response->assertOk();
        $response->assertSee('Detail Pengiriman');
        $response->assertSee($order->order_no);
        $response->assertSee('DIKIRIM');
        $response->assertSee('JNE Express');
        $response->assertSee('JNE-SHOW-12345');
        $response->assertSee($this->customer->customer_name);
        $response->assertSee('Tandai Diterima');
        $response->assertSee('Timeline Pengiriman');
        $response->assertSee('Item Pesanan');
        $response->assertDontSee('Catatan Pengiriman');
    }

    public function test_shipment_show_page_delivered_state_presentation(): void
    {
        [$order] = $this->createOrderReadyForShipment(20, 20, 0);
        $shipment = Shipment::create([
            'order_id'          => $order->id,
            'courier'           => 'SiCepat',
            'tracking_no'       => 'SCP-999888',
            'recipient_address' => $this->customer->address,
            'status'            => ShipmentStatus::DELIVERED,
            'shipped_at'        => now()->subDay(),
            'delivered_at'      => now(),
            'notes'             => 'Diterima oleh resepsionis.',
        ]);
        $order->update(['status' => OrderStatus::SELESAI]);

        $response = $this->actingAs($this->produksiUser)
            ->get(route('produksi.shipments.show', $shipment));

        $response->assertOk();
        $response->assertSee('Pengiriman Selesai');
        $response->assertSee('Pesanan: Selesai');
        $response->assertSee('Catatan Pengiriman');
        $response->assertSee('Diterima oleh resepsionis.');
        $response->assertDontSee('Tandai Diterima');
        $response->assertDontSee('Tandai Dikirim');
    }

    public function test_shipment_show_page_multi_item_order_presentation(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'ORD-MULTI-SHOW-' . uniqid(),
            'order_date'  => now()->toDateString(),
            'status'      => OrderStatus::DIKIRIM,
        ]);

        $var1 = ProductVariant::create([
            'product_id'        => $this->product->id,
            'variant_name'      => 'Dusty Mauve L',
            'sku'               => 'PSH-DM-L',
            'requires_printing' => false,
            'active'            => true,
        ]);
        $var2 = ProductVariant::create([
            'product_id'        => $this->product->id,
            'variant_name'      => 'Dusty Mauve XL',
            'sku'               => 'PSH-DM-XL',
            'requires_printing' => false,
            'active'            => true,
        ]);

        OrderItem::create(['order_id' => $order->id, 'variant_id' => $var1->id, 'qty_ordered' => 15, 'unit_selling_price' => 75000]);
        OrderItem::create(['order_id' => $order->id, 'variant_id' => $var2->id, 'qty_ordered' => 10, 'unit_selling_price' => 75000]);

        $shipment = Shipment::create([
            'order_id'          => $order->id,
            'courier'           => 'J&T Express',
            'tracking_no'       => 'JT-MULTI-01',
            'recipient_address' => $this->customer->address,
            'status'            => ShipmentStatus::SHIPPED,
            'shipped_at'        => now(),
        ]);

        $response = $this->actingAs($this->produksiUser)
            ->get(route('produksi.shipments.show', $shipment));

        $response->assertOk();
        $response->assertSee('Dusty Mauve L');
        $response->assertSee('PSH-DM-L');
        $response->assertSee('15 pcs');
        $response->assertSee('Dusty Mauve XL');
        $response->assertSee('PSH-DM-XL');
        $response->assertSee('10 pcs');
        $response->assertSee('Total: 25 pcs');
    }
}
