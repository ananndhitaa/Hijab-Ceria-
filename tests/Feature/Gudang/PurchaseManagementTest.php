<?php

namespace Tests\Feature\Gudang;

use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Enums\PurchaseStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Inventory\MaterialStock;
use App\Models\Inventory\Purchase;
use App\Models\Inventory\PurchaseItem;
use App\Models\Inventory\StockMovement;
use App\Models\Master\BomItem;
use App\Models\Master\Customer;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Master\Supplier;
use App\Models\Master\Unit;
use App\Models\Production\ProductionBatch;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\User;
use App\Services\Inventory\BatchMaterialPlanningService;
use App\Services\Inventory\PurchaseOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $gudangUser;
    private User $adminUser;
    private User $produksiUser;
    private User $ownerUser;
    private User $customerUser;
    private Supplier $supplier;
    private Material $kainVoal;
    private Material $benang;
    private Unit $meter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gudangUser = User::factory()->create([
            'name'   => 'Staff Gudang',
            'email'  => 'gudang@test.com',
            'role'   => UserRole::GUDANG,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->adminUser = User::factory()->create([
            'name'   => 'Administrator',
            'email'  => 'admin@test.com',
            'role'   => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->produksiUser = User::factory()->create([
            'name'   => 'Staff Produksi',
            'email'  => 'produksi@test.com',
            'role'   => UserRole::PRODUKSI,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->ownerUser = User::factory()->create([
            'name'   => 'Owner',
            'email'  => 'owner@test.com',
            'role'   => UserRole::OWNER,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->customerUser = User::factory()->create([
            'name'   => 'Customer',
            'email'  => 'customer@test.com',
            'role'   => UserRole::CUSTOMER,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->meter = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);

        $this->supplier = Supplier::create([
            'supplier_name' => 'PT. Bahan Tekstil',
            'phone'         => '021-888888',
            'active'        => true,
        ]);

        $this->kainVoal = Material::create([
            'unit_id'       => $this->meter->id,
            'material_name' => 'Kain Voal Premium',
            'min_stock'     => 50,
            'active'        => true,
        ]);
        MaterialStock::create(['material_id' => $this->kainVoal->id, 'qty_on_hand' => 100, 'avg_unit_cost' => 35000]);

        $this->benang = Material::create([
            'unit_id'       => $this->meter->id,
            'material_name' => 'Benang Jahit',
            'min_stock'     => 10,
            'active'        => true,
        ]);
        MaterialStock::create(['material_id' => $this->benang->id, 'qty_on_hand' => 20, 'avg_unit_cost' => 15000]);

        $this->supplier->materials()->attach([$this->kainVoal->id, $this->benang->id]);
    }

    public function test_gudang_can_list_purchases_and_filter(): void
    {
        $service = app(PurchaseOrderService::class);

        $po1 = $service->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [['material_id' => $this->kainVoal->id, 'qty' => 10, 'unit_price' => 35000]],
        ]);

        $po2 = $service->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [['material_id' => $this->benang->id, 'qty' => 5, 'unit_price' => 15000]],
        ]);
        $po2->update([
            'approval_status' => \App\Enums\PurchaseApprovalStatus::APPROVED,
            'approved_at'     => now(),
        ]);
        \App\Models\Inventory\PurchasePayment::create([
            'purchase_id'    => $po2->id,
            'payment_date'   => $po2->purchase_date->toDateString(),
            'amount'         => $po2->total_amount,
            'payment_method' => 'TRANSFER',
            'created_by'     => $this->adminUser->id,
        ]);
        $po2->update(['status' => PurchaseStatus::ORDERED]);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.purchases.index'));
        $response->assertStatus(200);
        $response->assertSee($po1->purchase_no);
        $response->assertSee($po2->purchase_no);
        $response->assertSee('PT. Bahan Tekstil');

        // Filter status ORDERED
        $filtered = $this->actingAs($this->gudangUser)->get(route('gudang.purchases.index', ['status' => 'ORDERED']));
        $filtered->assertStatus(200);
        $filtered->assertSee($po2->purchase_no);
        $filtered->assertDontSee($po1->purchase_no);
    }

    public function test_gudang_can_view_create_form(): void
    {
        $response = $this->actingAs($this->gudangUser)->get(route('gudang.purchases.create'));
        $response->assertStatus(200);
        $response->assertSee('Buat Purchase Order Baru');
        $response->assertSee('PT. Bahan Tekstil');
        $response->assertSee('Kain Voal Premium');
    }

    public function test_gudang_can_create_draft_purchase_order(): void
    {
        $payload = [
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-08-28',
            'notes'         => 'Pengadaan material berkala',
            'items'         => [
                [
                    'material_id' => $this->kainVoal->id,
                    'qty'         => '40',
                    'unit_price'  => '35000',
                ],
                [
                    'material_id' => $this->benang->id,
                    'qty'         => '10',
                    'unit_price'  => '15000',
                ],
            ],
        ];

        $response = $this->actingAs($this->gudangUser)->post(route('gudang.purchases.store'), $payload);

        $purchase = Purchase::first();
        $this->assertNotNull($purchase);
        $response->assertRedirect(route('gudang.purchases.show', $purchase));
        $response->assertSessionHas('success');

        $this->assertEquals(PurchaseStatus::DRAFT, $purchase->status);
        $this->assertCount(2, $purchase->items);
        $this->assertDatabaseHas('purchases', [
            'id'          => $purchase->id,
            'supplier_id' => $this->supplier->id,
            'status'      => 'DRAFT',
        ]);
        $this->assertDatabaseHas('purchase_items', [
            'purchase_id'  => $purchase->id,
            'material_id'  => $this->kainVoal->id,
            'qty'          => '40.0000',
            'unit_price'   => '35000.00',
            'qty_received' => '0.0000',
        ]);
    }

    public function test_mrp_integration_prefills_live_shortage_and_allows_ordering_more(): void
    {
        // 1. Setup Master & BOM: 1 pcs hijab takes 1.29m Kain Voal
        $customer = Customer::create(['customer_name' => 'Toko Hijab']);
        $product = Product::create(['product_name' => 'Voal Polos', 'active' => true]);
        $variant = ProductVariant::create([
            'product_id'   => $product->id,
            'variant_name' => 'Hitam',
            'sku'          => 'HJB-001',
            'active'       => true,
        ]);
        BomItem::create([
            'variant_id'   => $variant->id,
            'material_id'  => $this->kainVoal->id,
            'qty_per_unit' => 1.2900,
        ]);

        // 2. Setup Batch for 100 pcs (Required = 129m)
        $order = Order::create([
            'customer_id' => $customer->id,
            'order_no'    => 'SO-202608-0001',
            'order_date'  => '2026-08-28',
            'status'      => OrderStatus::DEAL,
        ]);
        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 100,
            'unit_selling_price' => 85000,
        ]);
        $batch = ProductionBatch::create([
            'order_item_id' => $orderItem->id,
            'batch_no'      => 'BAT-202608-0001',
            'target_qty'    => 100,
            'status'        => BatchStatus::BAHAN,
        ]);

        // 3. Generate MRP Snapshot: Current stock = 100, Required = 129, Shortage = 29
        $mrpService = app(BatchMaterialPlanningService::class);
        $mrpService->generateMaterialPlanning($batch);

        // 4. Open create PO with ?batch_id=
        $createResponse = $this->actingAs($this->gudangUser)->get(route('gudang.purchases.create', ['batch_id' => $batch->id]));
        $createResponse->assertStatus(200);
        $createResponse->assertSee('BAT-202608-0001');
        $createResponse->assertSee('29.0000'); // pre-filled shortage qty

        // 5. Gudang submits PO ordering 50m (exceeds shortage 29m for MOQ/safety stock)
        $payload = [
            'supplier_id'   => $this->supplier->id,
            'batch_id'      => $batch->id,
            'purchase_date' => '2026-08-28',
            'notes'         => 'Order dari kekurangan batch BAT-202608-0001',
            'items'         => [
                [
                    'material_id' => $this->kainVoal->id,
                    'qty'         => '50',
                    'unit_price'  => '35000',
                ],
            ],
        ];

        $storeResponse = $this->actingAs($this->gudangUser)->post(route('gudang.purchases.store'), $payload);
        $storeResponse->assertSessionHas('success');

        $purchase = Purchase::first();
        $this->assertNotNull($purchase);
        $this->assertEquals('50.0000', $purchase->items->first()->qty);

        // 6. Approve and Pay, auto-transitions to ORDERED
        $purchase->update([
            'status'          => PurchaseStatus::ORDERED,
            'approval_status' => \App\Enums\PurchaseApprovalStatus::APPROVED,
            'approved_at'     => now(),
        ]);
        \App\Models\Inventory\PurchasePayment::create([
            'purchase_id'    => $purchase->id,
            'payment_date'   => $purchase->purchase_date->toDateString(),
            'amount'         => $purchase->total_amount,
            'payment_method' => 'TRANSFER',
            'created_by'     => $this->adminUser->id,
        ]);

        // 7. Verify stock and shortage are NOT mutated
        $this->assertEquals('100.0000', $this->kainVoal->fresh(['stock'])->stock->qty_on_hand);
        $this->assertEquals(0, StockMovement::count());

        // Live MRP shortage remains 29m (KURANG) because stock was not yet physically received
        $mrpDataAfter = $mrpService->getMaterialRequirementsData($batch);
        $this->assertEquals('29.0000', $mrpDataAfter['items'][0]['shortage_qty']);
        $this->assertEquals('KURANG', $mrpDataAfter['items'][0]['status_label']);
    }

    public function test_gudang_can_view_show_page(): void
    {
        $service = app(PurchaseOrderService::class);
        $purchase = $service->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [['material_id' => $this->kainVoal->id, 'qty' => 10, 'unit_price' => 35000]],
        ]);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.purchases.show', $purchase));
        $response->assertStatus(200);
        $response->assertSee($purchase->purchase_no);
        $response->assertSee('PT. Bahan Tekstil');
        $response->assertSee('Kain Voal Premium');
        $response->assertSee('350.000,00');
    }

    public function test_gudang_can_edit_draft_po_and_cannot_edit_ordered_po(): void
    {
        $service = app(PurchaseOrderService::class);
        $purchase = $service->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [['material_id' => $this->kainVoal->id, 'qty' => 10, 'unit_price' => 35000]],
        ]);

        // Edit page loads for DRAFT
        $editResponse = $this->actingAs($this->gudangUser)->get(route('gudang.purchases.edit', $purchase));
        $editResponse->assertStatus(200);

        // Update DRAFT PO
        $updateResponse = $this->actingAs($this->gudangUser)->put(route('gudang.purchases.update', $purchase), [
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-08-29',
            'items'         => [['material_id' => $this->kainVoal->id, 'qty' => 20, 'unit_price' => 36000]],
        ]);
        $updateResponse->assertRedirect(route('gudang.purchases.show', $purchase));
        $this->assertEquals('20.0000', $purchase->fresh()->items->first()->qty);

        // Confirm to ORDERED
        $purchase->update([
            'approval_status' => \App\Enums\PurchaseApprovalStatus::APPROVED,
            'approved_at'     => now(),
        ]);
        \App\Models\Inventory\PurchasePayment::create([
            'purchase_id'    => $purchase->id,
            'payment_date'   => $purchase->purchase_date->toDateString(),
            'amount'         => $purchase->fresh()->total_amount,
            'payment_method' => 'TRANSFER',
            'created_by'     => $this->adminUser->id,
        ]);
        $purchase->update(['status' => PurchaseStatus::ORDERED]);

        // Attempting to access edit on ORDERED redirects with error
        $blockedEdit = $this->actingAs($this->gudangUser)->get(route('gudang.purchases.edit', $purchase));
        $blockedEdit->assertRedirect(route('gudang.purchases.show', $purchase));
        $blockedEdit->assertSessionHas('error');
    }

    public function test_gudang_can_confirm_and_cancel_purchase_order(): void
    {
        $service = app(PurchaseOrderService::class);
        $purchase = $service->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [['material_id' => $this->kainVoal->id, 'qty' => 10, 'unit_price' => 35000]],
        ]);

        // Unapproved/unpaid DRAFT cannot be confirmed
        $this->assertFalse($service->canConfirm($purchase));

        // Approve and pay purchase in full -> auto ORDERED
        $purchase->update([
            'status'          => PurchaseStatus::ORDERED,
            'approval_status' => \App\Enums\PurchaseApprovalStatus::APPROVED,
            'approved_at'     => now(),
        ]);
        \App\Models\Inventory\PurchasePayment::create([
            'purchase_id'    => $purchase->id,
            'payment_date'   => $purchase->purchase_date->toDateString(),
            'amount'         => $purchase->total_amount,
            'payment_method' => 'TRANSFER',
            'created_by'     => $this->adminUser->id,
        ]);

        $this->assertEquals(PurchaseStatus::ORDERED, $purchase->fresh()->status);

        // Cancel route on another draft
        $draftToCancel = $service->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [['material_id' => $this->kainVoal->id, 'qty' => 10, 'unit_price' => 35000]],
        ]);
        $cancelResponse = $this->actingAs($this->gudangUser)->post(route('gudang.purchases.cancel', $draftToCancel));
        $cancelResponse->assertRedirect(route('gudang.purchases.show', $draftToCancel));
        $cancelResponse->assertSessionHas('success');
        $this->assertEquals(PurchaseStatus::CANCELLED, $draftToCancel->fresh()->status);
    }

    public function test_tampered_purchase_no_and_qty_received_are_ignored(): void
    {
        $payload = [
            'purchase_no'   => 'PO-TAMPERED-9999',
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [
                [
                    'material_id'  => $this->kainVoal->id,
                    'qty'          => '10',
                    'unit_price'   => '35000',
                    'qty_received' => '100', // tampered
                ],
            ],
        ];

        $this->actingAs($this->gudangUser)->post(route('gudang.purchases.store'), $payload);

        $purchase = Purchase::first();
        $this->assertNotEquals('PO-TAMPERED-9999', $purchase->purchase_no);
        $this->assertMatchesRegularExpression('/^PO-\d{6}-\d{4}$/', $purchase->purchase_no);
        $this->assertEquals('0.0000', $purchase->items->first()->qty_received);
    }

    public function test_authorization_non_gudang_and_guests_are_denied(): void
    {
        $service = app(PurchaseOrderService::class);
        $purchase = $service->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [['material_id' => $this->kainVoal->id, 'qty' => 10, 'unit_price' => 35000]],
        ]);

        // Guest redirected
        $this->get(route('gudang.purchases.index'))->assertRedirect(route('login'));
        $this->post(route('gudang.purchases.store'), [])->assertRedirect(route('login'));

        // Non-gudang roles get 403
        $this->actingAs($this->adminUser)->get(route('gudang.purchases.index'))->assertStatus(403);
        $this->actingAs($this->produksiUser)->get(route('gudang.purchases.index'))->assertStatus(403);
        $this->actingAs($this->ownerUser)->get(route('gudang.purchases.index'))->assertStatus(403);
        $this->actingAs($this->customerUser)->get(route('gudang.purchases.index'))->assertStatus(403);

        $this->actingAs($this->adminUser)->post(route('gudang.purchases.cancel', $purchase))->assertStatus(403);

        // Inactive Gudang user blocked
        $inactiveGudang = User::factory()->create([
            'role'   => UserRole::GUDANG,
            'status' => UserStatus::INACTIVE,
        ]);
        $this->actingAs($inactiveGudang)->get(route('gudang.purchases.index'))->assertRedirect(route('login'));
    }

    public function test_gudang_can_create_po_with_batch_id_under_tenant_isolation(): void
    {
        config(['nayara.tenant_isolation_enabled' => true]);

        $outlet = \App\Models\System\Outlet::create([
            'outlet_name' => 'Nayara Outlet 1',
            'outlet_code' => 'OUT-01',
            'status'      => 'active',
        ]);

        $tenantGudang = User::factory()->create([
            'name'      => 'Gudang Tenant',
            'email'     => 'gudang_tenant@test.com',
            'role'      => UserRole::GUDANG,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $outlet->id,
        ]);

        $supplier = Supplier::create([
            'outlet_id'     => $outlet->id,
            'supplier_name' => 'Supplier Tenant',
            'phone'         => '08123456789',
            'active'        => true,
        ]);

        $material = Material::create([
            'outlet_id'     => $outlet->id,
            'unit_id'       => $this->meter->id,
            'material_name' => 'Kain Tenant',
            'min_stock'     => 10,
            'active'        => true,
        ]);
        MaterialStock::create(['material_id' => $material->id, 'qty_on_hand' => 0, 'avg_unit_cost' => 20000]);
        $supplier->materials()->attach($material->id);

        $customer = Customer::create(['outlet_id' => $outlet->id, 'customer_name' => 'Customer Tenant']);
        $product = Product::create(['outlet_id' => $outlet->id, 'product_name' => 'Hijab Tenant', 'active' => true]);
        $variant = ProductVariant::create(['outlet_id' => $outlet->id, 'product_id' => $product->id, 'variant_name' => 'Hitam', 'sku' => 'TEN-01', 'active' => true]);
        $order = Order::create(['outlet_id' => $outlet->id, 'customer_id' => $customer->id, 'order_no' => 'SO-TEN-001', 'order_date' => '2026-11-10', 'status' => OrderStatus::DEAL]);
        $orderItem = OrderItem::create(['order_id' => $order->id, 'variant_id' => $variant->id, 'qty_ordered' => 10, 'unit_selling_price' => 50000]);
        $batch = ProductionBatch::create(['order_item_id' => $orderItem->id, 'batch_no' => 'BAT-TEN-001', 'target_qty' => 10, 'status' => BatchStatus::BAHAN]);

        $payload = [
            'supplier_id'   => $supplier->id,
            'batch_id'      => $batch->id,
            'purchase_date' => '2026-11-10',
            'notes'         => 'PO dari kekurangan batch produksi',
            'items'         => [
                [
                    'material_id' => $material->id,
                    'qty'         => '12.5000',
                    'unit_price'  => '25000',
                ],
            ],
        ];

        $response = $this->actingAs($tenantGudang)->post(route('gudang.purchases.store'), $payload);

        $purchase = Purchase::where('batch_id', $batch->id)->first();
        $this->assertNotNull($purchase, 'Purchase Order should be created successfully without QueryException on outlet_id');
        $response->assertRedirect(route('gudang.purchases.show', $purchase));
        $this->assertEquals($outlet->id, $purchase->outlet_id);
        $this->assertEquals($batch->id, $purchase->batch_id);
        $this->assertEquals('12.5000', $purchase->items->first()->qty);
    }
}
