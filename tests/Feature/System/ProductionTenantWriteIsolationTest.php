<?php

namespace Tests\Feature\System;

use App\Enums\BatchStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Inventory\MaterialStock;
use App\Models\Inventory\Purchase;
use App\Models\Inventory\StockMovement;
use App\Models\Master\BomItem;
use App\Models\Master\BomTemplate;
use App\Models\Master\Customer;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Master\Unit;
use App\Models\Production\BatchMaterial;
use App\Models\Production\ProductionBatch;
use App\Models\Production\ProductionResult;
use App\Models\Production\QcResult;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\Sales\Payment;
use App\Models\System\OrderStatusHistory;
use App\Models\System\Outlet;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductionTenantWriteIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Outlet $outletA;
    protected Outlet $outletB;
    protected Outlet $outletInactive;

    protected User $adminA;
    protected User $adminB;
    protected User $produksiA;
    protected User $produksiB;
    protected User $gudangA;
    protected User $ownerA;
    protected User $customerUser;
    protected User $inactiveAdminA;
    protected User $unboundAdmin;
    protected User $adminInactiveOutlet;

    protected Customer $customerA;
    protected Customer $customerB;

    protected Product $productA;
    protected Product $productB;

    protected ProductVariant $variantA;
    protected ProductVariant $variantB;

    protected Material $materialA;
    protected Material $materialB;

    protected BomTemplate $bomTemplateA;
    protected BomTemplate $bomTemplateB;

    protected Order $orderA;
    protected Order $orderB;
    protected Order $unassignedOrder;

    protected OrderItem $itemA;
    protected OrderItem $itemB;
    protected OrderItem $unassignedItem;

    protected ProductionBatch $batchA;
    protected ProductionBatch $batchB;
    protected ProductionBatch $unassignedBatch;

    protected function setUp(): void
    {
        parent::setUp();

        // Enable tenant isolation enforcement for security testing
        config(['nayara.tenant_isolation_enabled' => true]);

        // 1. Outlets
        $this->outletA = $this->createTestOutlet('OUT-PROD-A', 'Konveksi Hijab A', 'ACTIVE');
        $this->outletB = $this->createTestOutlet('OUT-PROD-B', 'Konveksi Hijab B', 'ACTIVE');
        $this->outletInactive = $this->createTestOutlet('OUT-INACTIVE', 'Konveksi Inactive', 'INACTIVE');

        // 2. Users
        $this->adminA = $this->createTestUser($this->outletA, UserRole::ADMIN, UserStatus::ACTIVE, 'admin-a');
        $this->adminB = $this->createTestUser($this->outletB, UserRole::ADMIN, UserStatus::ACTIVE, 'admin-b');
        $this->produksiA = $this->createTestUser($this->outletA, UserRole::PRODUKSI, UserStatus::ACTIVE, 'prod-a');
        $this->produksiB = $this->createTestUser($this->outletB, UserRole::PRODUKSI, UserStatus::ACTIVE, 'prod-b');
        $this->gudangA = $this->createTestUser($this->outletA, UserRole::GUDANG, UserStatus::ACTIVE, 'gudang-a');
        $this->ownerA = $this->createTestUser($this->outletA, UserRole::OWNER, UserStatus::ACTIVE, 'owner-a');
        $this->customerUser = $this->createTestUser($this->outletA, UserRole::CUSTOMER, UserStatus::ACTIVE, 'cust-user');
        $this->inactiveAdminA = $this->createTestUser($this->outletA, UserRole::ADMIN, UserStatus::INACTIVE, 'admin-inact');
        $this->unboundAdmin = $this->createTestUser(null, UserRole::ADMIN, UserStatus::ACTIVE, 'admin-unbound');
        $this->adminInactiveOutlet = $this->createTestUser($this->outletInactive, UserRole::ADMIN, UserStatus::ACTIVE, 'admin-inact-out');

        // 3. Customers
        $this->customerA = Customer::create([
            'customer_name' => 'Customer Nayara A',
            'phone'         => '081211112222',
            'outlet_id'     => $this->outletA->id,
        ]);
        $this->customerB = Customer::create([
            'customer_name' => 'Customer Nayara B',
            'phone'         => '081233334444',
            'outlet_id'     => $this->outletB->id,
        ]);

        // 4. Products & Variants (with routing & cost rate snapshots configured)
        $this->productA = Product::create([
            'product_name' => 'Gamis Syari A',
            'active'       => true,
            'outlet_id'    => $this->outletA->id,
        ]);
        $this->variantA = ProductVariant::create([
            'product_id'                           => $this->productA->id,
            'variant_name'                         => 'Mauve Size L',
            'sku'                                  => 'SKU-GAMIS-A-L',
            'base_selling_price'                   => 150000,
            'requires_printing'                    => false,
            'direct_labor_cost_per_unit'           => '5000.00',
            'packaging_process_cost_per_good_unit' => '2000.00',
            'other_direct_cost_per_unit'           => '1000.00',
            'active'                               => true,
            'outlet_id'                            => $this->outletA->id,
        ]);

        $this->productB = Product::create([
            'product_name' => 'Khimar Instan B',
            'active'       => true,
            'outlet_id'    => $this->outletB->id,
        ]);
        $this->variantB = ProductVariant::create([
            'product_id'                           => $this->productB->id,
            'variant_name'                         => 'Navy Size M',
            'sku'                                  => 'SKU-KHIMAR-B-M',
            'base_selling_price'                   => 85000,
            'requires_printing'                    => true,
            'printing_cost_per_unit'               => '3000.00',
            'direct_labor_cost_per_unit'           => '4000.00',
            'packaging_process_cost_per_good_unit' => '1500.00',
            'other_direct_cost_per_unit'           => '800.00',
            'active'                               => true,
            'outlet_id'                            => $this->outletB->id,
        ]);

        // 5. Units, Materials & Stock
        $unit = Unit::firstOrCreate(['unit_name' => 'Meter', 'symbol' => 'm']);

        $this->materialA = Material::create([
            'material_name' => 'Kain Rayon A',
            'unit_id'       => $unit->id,
            'active'        => true,
            'outlet_id'     => $this->outletA->id,
        ]);
        MaterialStock::create([
            'material_id'   => $this->materialA->id,
            'qty_on_hand'   => '500.0000',
            'avg_unit_cost' => '20000.0000',
        ]);

        $this->materialB = Material::create([
            'material_name' => 'Kain Ceruty B',
            'unit_id'       => $unit->id,
            'active'        => true,
            'outlet_id'     => $this->outletB->id,
        ]);
        MaterialStock::create([
            'material_id'   => $this->materialB->id,
            'qty_on_hand'   => '400.0000',
            'avg_unit_cost' => '25000.0000',
        ]);

        // 6. Master BOM Items for Variants
        BomItem::create([
            'variant_id'   => $this->variantA->id,
            'material_id'  => $this->materialA->id,
            'qty_per_unit' => '2.5000',
        ]);

        BomItem::create([
            'variant_id'   => $this->variantB->id,
            'material_id'  => $this->materialB->id,
            'qty_per_unit' => '2.0000',
        ]);

        // 7. BOM Templates (Master Reference)
        $this->bomTemplateA = BomTemplate::create([
            'name'       => 'BOM Gamis A',
            'product_id' => $this->productA->id,
            'outlet_id'  => $this->outletA->id,
            'is_active'  => true,
        ]);
        $this->bomTemplateB = BomTemplate::create([
            'name'       => 'BOM Khimar B',
            'product_id' => $this->productB->id,
            'outlet_id'  => $this->outletB->id,
            'is_active'  => true,
        ]);

        // 8. Sales Orders (in DEAL state with confirmed DP)
        $this->orderA = Order::create([
            'customer_id' => $this->customerA->id,
            'order_no'    => 'SO-PROD-A-001',
            'order_date'  => '2026-10-23',
            'status'      => OrderStatus::DEAL,
            'outlet_id'   => $this->outletA->id,
            'notes'       => 'Order konveksi A',
        ]);
        $this->itemA = OrderItem::create([
            'order_id'           => $this->orderA->id,
            'variant_id'         => $this->variantA->id,
            'qty_ordered'        => 20,
            'unit_selling_price' => 150000,
        ]);
        $this->createConfirmedDownPayment($this->orderA, '3000000.00', '1500000.00');

        $this->orderB = Order::create([
            'customer_id' => $this->customerB->id,
            'order_no'    => 'SO-PROD-B-002',
            'order_date'  => '2026-10-23',
            'status'      => OrderStatus::DEAL,
            'outlet_id'   => $this->outletB->id,
            'notes'       => 'Order konveksi B',
        ]);
        $this->itemB = OrderItem::create([
            'order_id'           => $this->orderB->id,
            'variant_id'         => $this->variantB->id,
            'qty_ordered'        => 30,
            'unit_selling_price' => 85000,
        ]);
        $this->createConfirmedDownPayment($this->orderB, '2550000.00', '1000000.00');

        $this->unassignedOrder = Order::create([
            'customer_id' => $this->customerA->id,
            'order_no'    => 'SO-LEGACY-NULL-003',
            'order_date'  => '2026-10-23',
            'status'      => OrderStatus::DEAL,
            'outlet_id'   => null,
            'notes'       => 'Legacy unassigned order',
        ]);
        $this->unassignedItem = OrderItem::create([
            'order_id'           => $this->unassignedOrder->id,
            'variant_id'         => $this->variantA->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 150000,
        ]);
        $this->createConfirmedDownPayment($this->unassignedOrder, '1500000.00', '500000.00');

        // 9. Production Batches
        $this->batchA = ProductionBatch::create([
            'order_item_id'                                 => $this->itemA->id,
            'batch_no'                                      => 'BAT-A-2026-001',
            'target_qty'                                    => 10,
            'status'                                        => BatchStatus::BAHAN,
            'requires_printing'                             => false,
            'direct_labor_cost_per_unit_snapshot'           => '5000.00',
            'packaging_process_cost_per_good_unit_snapshot' => '2000.00',
            'other_direct_cost_per_unit_snapshot'           => '1000.00',
            'production_employee_id'                        => $this->produksiA->id,
        ]);
        BatchMaterial::create([
            'batch_id'      => $this->batchA->id,
            'material_id'   => $this->materialA->id,
            'required_qty'  => '25.0000',
            'allocated_qty' => '25.0000',
            'used_qty'      => '0.0000',
        ]);

        $this->batchB = ProductionBatch::create([
            'order_item_id'                                 => $this->itemB->id,
            'batch_no'                                      => 'BAT-B-2026-002',
            'target_qty'                                    => 15,
            'status'                                        => BatchStatus::BAHAN,
            'requires_printing'                             => true,
            'printing_cost_per_unit_snapshot'               => '3000.00',
            'direct_labor_cost_per_unit_snapshot'           => '4000.00',
            'packaging_process_cost_per_good_unit_snapshot' => '1500.00',
            'other_direct_cost_per_unit_snapshot'           => '800.00',
            'production_employee_id'                        => $this->produksiB->id,
        ]);
        BatchMaterial::create([
            'batch_id'      => $this->batchB->id,
            'material_id'   => $this->materialB->id,
            'required_qty'  => '30.0000',
            'allocated_qty' => '30.0000',
            'used_qty'      => '0.0000',
        ]);

        $this->unassignedBatch = ProductionBatch::create([
            'order_item_id'                                 => $this->unassignedItem->id,
            'batch_no'                                      => 'BAT-NULL-2026-003',
            'target_qty'                                    => 5,
            'status'                                        => BatchStatus::BAHAN,
            'requires_printing'                             => false,
            'direct_labor_cost_per_unit_snapshot'           => '5000.00',
            'packaging_process_cost_per_good_unit_snapshot' => '2000.00',
            'other_direct_cost_per_unit_snapshot'           => '1000.00',
        ]);
        BatchMaterial::create([
            'batch_id'      => $this->unassignedBatch->id,
            'material_id'   => $this->materialA->id,
            'required_qty'  => '12.5000',
            'allocated_qty' => '12.5000',
            'used_qty'      => '0.0000',
        ]);
    }

    protected function createTestOutlet(string $code, string $name, string $status = 'ACTIVE'): Outlet
    {
        return Outlet::create([
            'outlet_name'                => $name,
            'outlet_code'                => $code,
            'slug'                       => strtolower($code),
            'status'                     => $status,
            'is_public_showcase_enabled' => true,
        ]);
    }

    protected function createTestUser(?Outlet $outlet, UserRole $role, UserStatus $status, string $suffix): User
    {
        return User::create([
            'name'      => "User {$suffix}",
            'email'     => "{$suffix}@nayara.test",
            'password'  => bcrypt('password'),
            'role'      => $role,
            'status'    => $status,
            'outlet_id' => $outlet?->id,
        ]);
    }

    protected function createConfirmedDownPayment(Order $order, string $totalAmount, string $dpAmount): void
    {
        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-' . uniqid(),
            'invoice_date' => now()->toDateString(),
            'total_amount' => $totalAmount,
            'status'       => InvoiceStatus::PARTIAL,
        ]);

        Payment::create([
            'invoice_id'   => $invoice->id,
            'payment_no'   => 'PAY-' . uniqid(),
            'payment_date' => now()->toDateString(),
            'amount'       => $dpAmount,
            'payment_type' => PaymentType::DP,
            'status'       => PaymentStatus::CONFIRMED,
        ]);
    }

    // =========================================================================
    // 48 TEST SCENARIOS (Section Q)
    // =========================================================================

    /**
     * 01. Admin Outlet A dapat membuat batch dari Order A yang sah.
     */
    public function test_01_admin_outlet_a_can_create_batch_for_valid_order_a(): void
    {
        $payload = [
            'order_item_id'          => $this->itemA->id,
            'target_qty'             => 5,
            'planned_start_date'     => now()->toDateString(),
            'planned_end_date'       => now()->addDays(3)->toDateString(),
            'production_employee_id' => $this->produksiA->id,
            'notes'                  => 'Batch 1 Konveksi A',
        ];

        $response = $this->actingAs($this->adminA)
            ->post(route('admin.orders.batches.store', $this->orderA), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('production_batches', [
            'order_item_id'          => $this->itemA->id,
            'target_qty'             => 5.0000,
            'status'                 => BatchStatus::BAHAN->value,
            'production_employee_id' => $this->produksiA->id,
        ]);
        $this->assertEquals(OrderStatus::BAHAN, $this->orderA->fresh()->status);
    }

    /**
     * 02. Batch baru mengikuti OrderItem dan Order A.
     */
    public function test_02_new_batch_inherits_order_item_and_order_a_ownership(): void
    {
        $payload = [
            'order_item_id'          => $this->itemA->id,
            'target_qty'             => 4,
            'planned_end_date'       => now()->addDays(2)->toDateString(),
            'production_employee_id' => $this->produksiA->id,
        ];

        $this->actingAs($this->adminA)
            ->post(route('admin.orders.batches.store', $this->orderA), $payload);

        $newBatch = ProductionBatch::where('order_item_id', $this->itemA->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($newBatch);
        $this->assertTrue($newBatch->belongsToOutlet($this->outletA->id));
        $this->assertFalse($newBatch->belongsToOutlet($this->outletB->id));
        $this->assertEquals($this->outletA->id, $newBatch->outlet->id);
    }

    /**
     * 03. Admin Outlet B dapat membuat batch dari Order B yang sah.
     */
    public function test_03_admin_outlet_b_can_create_batch_for_valid_order_b(): void
    {
        $payload = [
            'order_item_id'          => $this->itemB->id,
            'target_qty'             => 10,
            'planned_end_date'       => now()->addDays(4)->toDateString(),
            'production_employee_id' => $this->produksiB->id,
        ];

        $response = $this->actingAs($this->adminB)
            ->post(route('admin.orders.batches.store', $this->orderB), $payload);

        $response->assertRedirect();
        $newBatch = ProductionBatch::where('order_item_id', $this->itemB->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($newBatch);
        $this->assertTrue($newBatch->belongsToOutlet($this->outletB->id));
    }

    /**
     * 04. Admin Outlet A tidak dapat membuat batch dari Order B.
     */
    public function test_04_admin_outlet_a_cannot_create_batch_for_order_b(): void
    {
        $initialBatchCount = ProductionBatch::count();

        $payload = [
            'order_item_id'          => $this->itemB->id,
            'target_qty'             => 5,
            'planned_end_date'       => now()->addDays(3)->toDateString(),
            'production_employee_id' => $this->produksiA->id,
        ];

        $response = $this->actingAs($this->adminA)
            ->post(route('admin.orders.batches.store', $this->orderB), $payload);

        $response->assertStatus(404);
        $this->assertEquals($initialBatchCount, ProductionBatch::count());
    }

    /**
     * 05. Manipulasi Order ID lintas-outlet menghasilkan 404.
     */
    public function test_05_cross_tenant_order_id_manipulation_returns_404(): void
    {
        $payload = [
            'order_item_id'    => $this->itemA->id,
            'target_qty'       => 5,
            'planned_end_date' => now()->addDays(3)->toDateString(),
        ];

        $response = $this->actingAs($this->adminA)
            ->post("/admin/orders/{$this->orderB->id}/batches", $payload);

        $response->assertStatus(404);
    }

    /**
     * 06. Order dengan outlet_id NULL tidak dapat dibuatkan batch saat tenant mode TRUE.
     */
    public function test_06_order_with_null_outlet_id_cannot_create_batch_when_tenant_mode_true(): void
    {
        $payload = [
            'order_item_id'    => $this->unassignedItem->id,
            'target_qty'       => 2,
            'planned_end_date' => now()->addDays(3)->toDateString(),
        ];

        $response = $this->actingAs($this->adminA)
            ->post(route('admin.orders.batches.store', $this->unassignedOrder), $payload);

        $response->assertStatus(404);
    }

    /**
     * 07. Manipulasi OrderItem ID lintas-outlet ditolak.
     */
    public function test_07_manipulated_order_item_id_across_tenants_is_rejected(): void
    {
        $initialBatchCount = ProductionBatch::count();

        $payload = [
            'order_item_id'    => $this->itemB->id, // belongs to Order B / Outlet B
            'target_qty'       => 5,
            'planned_end_date' => now()->addDays(3)->toDateString(),
        ];

        $response = $this->actingAs($this->adminA)
            ->post(route('admin.orders.batches.store', $this->orderA), $payload);

        $response->assertSessionHasErrors('order_item_id');
        $this->assertEquals($initialBatchCount, ProductionBatch::count());
    }

    /**
     * 08. OrderItem harus benar-benar milik parent Order route.
     */
    public function test_08_order_item_must_strictly_belong_to_route_parent_order(): void
    {
        // Second order under Outlet A
        $orderA2 = Order::create([
            'customer_id' => $this->customerA->id,
            'order_no'    => 'SO-PROD-A-002',
            'order_date'  => '2026-10-23',
            'status'      => OrderStatus::DEAL,
            'outlet_id'   => $this->outletA->id,
        ]);
        $itemA2 = OrderItem::create([
            'order_id'           => $orderA2->id,
            'variant_id'         => $this->variantA->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 150000,
        ]);
        $this->createConfirmedDownPayment($orderA2, '1500000.00', '500000.00');

        $initialBatchCount = ProductionBatch::count();

        // Attempt to pass itemA2 to orderA route
        $payload = [
            'order_item_id'    => $itemA2->id,
            'target_qty'       => 5,
            'planned_end_date' => now()->addDays(3)->toDateString(),
        ];

        $response = $this->actingAs($this->adminA)
            ->post(route('admin.orders.batches.store', $this->orderA), $payload);

        $response->assertSessionHasErrors('order_item_id');
        $this->assertEquals($initialBatchCount, ProductionBatch::count());
    }

    /**
     * 09. Variant lintas-outlet ditolak.
     */
    public function test_09_cross_tenant_variant_is_rejected(): void
    {
        // Create an order item on orderA pointing to variantB (cross-tenant)
        $mismatchedItem = OrderItem::create([
            'order_id'           => $this->orderA->id,
            'variant_id'         => $this->variantB->id,
            'qty_ordered'        => 5,
            'unit_selling_price' => 85000,
        ]);

        $payload = [
            'order_item_id'    => $mismatchedItem->id,
            'target_qty'       => 2,
            'planned_end_date' => now()->addDays(2)->toDateString(),
        ];

        $response = $this->actingAs($this->adminA)
            ->post(route('admin.orders.batches.store', $this->orderA), $payload);

        $response->assertSessionHasErrors('order_item_id');
    }

    /**
     * 10. Parent Product lintas-outlet ditolak.
     */
    public function test_10_cross_tenant_parent_product_is_rejected(): void
    {
        // Variant has outletA, but its parent product has outletB
        $corruptedProduct = Product::create([
            'product_name' => 'Corrupted Product B',
            'active'       => true,
            'outlet_id'    => $this->outletB->id,
        ]);
        $corruptedVariant = ProductVariant::create([
            'product_id'                           => $corruptedProduct->id,
            'variant_name'                         => 'Corrupted Variant',
            'sku'                                  => 'SKU-CORRUPT-01',
            'base_selling_price'                   => 100000,
            'requires_printing'                    => false,
            'direct_labor_cost_per_unit'           => '5000.00',
            'packaging_process_cost_per_good_unit' => '2000.00',
            'other_direct_cost_per_unit'           => '1000.00',
            'active'                               => true,
            'outlet_id'                            => $this->outletA->id,
        ]);
        BomItem::create([
            'variant_id'   => $corruptedVariant->id,
            'material_id'  => $this->materialA->id,
            'qty_per_unit' => '2.0000',
        ]);

        $itemWithCorruptedProduct = OrderItem::create([
            'order_id'           => $this->orderA->id,
            'variant_id'         => $corruptedVariant->id,
            'qty_ordered'        => 5,
            'unit_selling_price' => 100000,
        ]);

        $payload = [
            'order_item_id'    => $itemWithCorruptedProduct->id,
            'target_qty'       => 2,
            'planned_end_date' => now()->addDays(2)->toDateString(),
        ];

        $response = $this->actingAs($this->adminA)
            ->post(route('admin.orders.batches.store', $this->orderA), $payload);

        $response->assertSessionHasErrors('order_item_id');
    }

    /**
     * 11. BOM Template lintas-outlet tidak dapat digunakan.
     */
    public function test_11_cross_tenant_bom_template_cannot_be_used(): void
    {
        // Attempt to pass extraneous or cross-tenant bom_template_id in payload
        $payload = [
            'order_item_id'    => $this->itemA->id,
            'target_qty'       => 2,
            'planned_end_date' => now()->addDays(2)->toDateString(),
            'bom_template_id'  => $this->bomTemplateB->id,
        ];

        // Ensure request does not adopt template B
        $this->actingAs($this->adminA)
            ->post(route('admin.orders.batches.store', $this->orderA), $payload);

        $batch = ProductionBatch::where('order_item_id', $this->itemA->id)->latest('id')->first();
        $this->assertNotNull($batch);
        // Batch materials must only use Material A, never Material B
        $materials = $batch->batchMaterials->pluck('material_id')->all();
        $this->assertContains($this->materialA->id, $materials);
        $this->assertNotContains($this->materialB->id, $materials);
    }

    /**
     * 12. Material lintas-outlet dalam BOM ditolak.
     */
    public function test_12_cross_tenant_material_in_bom_is_rejected(): void
    {
        // Create variant with a cross-tenant material in BOM
        $variantWithCrossBom = ProductVariant::create([
            'product_id'                           => $this->productA->id,
            'variant_name'                         => 'Cross BOM Variant',
            'sku'                                  => 'SKU-CROSS-BOM-01',
            'base_selling_price'                   => 120000,
            'requires_printing'                    => false,
            'direct_labor_cost_per_unit'           => '5000.00',
            'packaging_process_cost_per_good_unit' => '2000.00',
            'other_direct_cost_per_unit'           => '1000.00',
            'active'                               => true,
            'outlet_id'                            => $this->outletA->id,
        ]);
        BomItem::create([
            'variant_id'   => $variantWithCrossBom->id,
            'material_id'  => $this->materialB->id, // Outlet B's material!
            'qty_per_unit' => '3.0000',
        ]);

        $itemWithCrossBom = OrderItem::create([
            'order_id'           => $this->orderA->id,
            'variant_id'         => $variantWithCrossBom->id,
            'qty_ordered'        => 5,
            'unit_selling_price' => 120000,
        ]);

        $initialBatchCount = ProductionBatch::count();

        $payload = [
            'order_item_id'    => $itemWithCrossBom->id,
            'target_qty'       => 2,
            'planned_end_date' => now()->addDays(2)->toDateString(),
        ];

        $response = $this->actingAs($this->adminA)
            ->post(route('admin.orders.batches.store', $this->orderA), $payload);

        $response->assertSessionHasErrors('order_item_id');
        $this->assertEquals($initialBatchCount, ProductionBatch::count());
    }

    /**
     * 13. Material snapshot batch yang tidak konsisten ditolak jika relevan pada write target.
     */
    public function test_13_inconsistent_batch_material_snapshot_rejected_on_write(): void
    {
        // Batch A has a rogue BatchMaterial from Outlet B
        BatchMaterial::create([
            'batch_id'      => $this->batchA->id,
            'material_id'   => $this->materialB->id,
            'required_qty'  => '10.0000',
            'allocated_qty' => '10.0000',
            'used_qty'      => '0.0000',
        ]);

        $response = $this->actingAs($this->produksiA)
            ->post(route('produksi.batches.start', $this->batchA));

        $response->assertStatus(404);
        $this->assertEquals(BatchStatus::BAHAN, $this->batchA->fresh()->status);
    }

    /**
     * 14. PIC produksi dari outlet lain tidak dapat ditugaskan.
     */
    public function test_14_production_pic_from_other_outlet_cannot_be_assigned(): void
    {
        $payload = [
            'order_item_id'          => $this->itemA->id,
            'target_qty'             => 2,
            'planned_end_date'       => now()->addDays(2)->toDateString(),
            'production_employee_id' => $this->produksiB->id, // belongs to Outlet B!
        ];

        $response = $this->actingAs($this->adminA)
            ->post(route('admin.orders.batches.store', $this->orderA), $payload);

        $response->assertSessionHasErrors('production_employee_id');
    }

    /**
     * 15. Parameter outlet_id palsu tidak menentukan ownership.
     */
    public function test_15_spoofed_outlet_id_payload_does_not_override_ownership(): void
    {
        $payload = [
            'order_item_id'          => $this->itemA->id,
            'target_qty'             => 2,
            'planned_end_date'       => now()->addDays(2)->toDateString(),
            'outlet_id'              => $this->outletB->id, // Spoof Outlet B
            'production_employee_id' => $this->produksiA->id,
        ];

        $this->actingAs($this->adminA)
            ->post(route('admin.orders.batches.store', $this->orderA), $payload);

        $batch = ProductionBatch::where('order_item_id', $this->itemA->id)->latest('id')->first();
        $this->assertNotNull($batch);
        $this->assertTrue($batch->belongsToOutlet($this->outletA->id));
        $this->assertFalse($batch->belongsToOutlet($this->outletB->id));
    }

    /**
     * 16. Header X-Outlet-ID palsu tidak menentukan ownership.
     */
    public function test_16_spoofed_x_outlet_id_header_does_not_override_ownership(): void
    {
        $payload = [
            'order_item_id'          => $this->itemA->id,
            'target_qty'             => 2,
            'planned_end_date'       => now()->addDays(2)->toDateString(),
            'production_employee_id' => $this->produksiA->id,
        ];

        $this->actingAs($this->adminA)
            ->withHeaders(['X-Outlet-ID' => (string) $this->outletB->id])
            ->post(route('admin.orders.batches.store', $this->orderA), $payload);

        $batch = ProductionBatch::where('order_item_id', $this->itemA->id)->latest('id')->first();
        $this->assertNotNull($batch);
        $this->assertTrue($batch->belongsToOutlet($this->outletA->id));
    }

    /**
     * 17. Data batch dan batchMaterial tidak parsial saat validasi atau service gagal.
     */
    public function test_17_batch_and_batch_material_atomic_no_partial_records_on_failure(): void
    {
        $initialBatchCount = ProductionBatch::count();
        $initialBmCount = BatchMaterial::count();

        // Target qty exceeds remaining capacity (target 100 > ordered 20)
        $payload = [
            'order_item_id'          => $this->itemA->id,
            'target_qty'             => 100,
            'planned_end_date'       => now()->addDays(2)->toDateString(),
            'production_employee_id' => $this->produksiA->id,
        ];

        $response = $this->actingAs($this->adminA)
            ->post(route('admin.orders.batches.store', $this->orderA), $payload);

        $response->assertSessionHas('error');
        $this->assertEquals($initialBatchCount, ProductionBatch::count());
        $this->assertEquals($initialBmCount, BatchMaterial::count());
    }

    /**
     * 18. Aturan status Order untuk pembuatan batch tetap berlaku.
     */
    public function test_18_order_status_business_gate_retained(): void
    {
        $orderKomunikasi = Order::create([
            'customer_id' => $this->customerA->id,
            'order_no'    => 'SO-KOM-001',
            'order_date'  => '2026-10-23',
            'status'      => OrderStatus::KOMUNIKASI,
            'outlet_id'   => $this->outletA->id,
        ]);
        $item = OrderItem::create([
            'order_id'           => $orderKomunikasi->id,
            'variant_id'         => $this->variantA->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 150000,
        ]);

        $payload = [
            'order_item_id'    => $item->id,
            'target_qty'       => 5,
            'planned_end_date' => now()->addDays(2)->toDateString(),
        ];

        $response = $this->actingAs($this->adminA)
            ->post(route('admin.orders.batches.store', $orderKomunikasi), $payload);

        $response->assertSessionHas('error');
    }

    /**
     * 19. Persyaratan DP existing tetap berlaku.
     */
    public function test_19_down_payment_requirement_retained(): void
    {
        // Order in DEAL but no confirmed DP
        $orderNoDp = Order::create([
            'customer_id' => $this->customerA->id,
            'order_no'    => 'SO-NO-DP-001',
            'order_date'  => '2026-10-23',
            'status'      => OrderStatus::DEAL,
            'outlet_id'   => $this->outletA->id,
        ]);
        $item = OrderItem::create([
            'order_id'           => $orderNoDp->id,
            'variant_id'         => $this->variantA->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 150000,
        ]);

        $payload = [
            'order_item_id'    => $item->id,
            'target_qty'       => 5,
            'planned_end_date' => now()->addDays(2)->toDateString(),
        ];

        $response = $this->actingAs($this->adminA)
            ->post(route('admin.orders.batches.store', $orderNoDp), $payload);

        $response->assertSessionHas('error');
    }

    /**
     * 20. Jumlah batch tetap tunduk pada remaining quantity.
     */
    public function test_20_batch_quantity_constrained_by_remaining_quantity(): void
    {
        // Item A already has batchA with target_qty 10. Ordered is 20. Remaining is 10.
        // Attempting target_qty = 15 should be rejected.
        $payload = [
            'order_item_id'    => $this->itemA->id,
            'target_qty'       => 15,
            'planned_end_date' => now()->addDays(2)->toDateString(),
        ];

        $response = $this->actingAs($this->adminA)
            ->post(route('admin.orders.batches.store', $this->orderA), $payload);

        $response->assertSessionHas('error');
    }

    /**
     * 21. User Produksi Outlet A dapat memulai batch A yang sah.
     */
    public function test_21_produksi_user_outlet_a_can_start_valid_batch_a(): void
    {
        $this->orderA->update(['status' => OrderStatus::BAHAN]);
        $this->itemA->update(['qty_ordered' => 10]); // Fully covered by batchA (10 pcs), allowing order to advance

        $response = $this->actingAs($this->produksiA)
            ->post(route('produksi.batches.start', $this->batchA));

        $response->assertRedirect(route('produksi.batches.show', $this->batchA));
        $this->assertEquals(BatchStatus::PRODUKSI, $this->batchA->fresh()->status);
        $this->assertEquals(OrderStatus::PRODUKSI, $this->orderA->fresh()->status);
    }

    /**
     * 22. User Produksi Outlet A tidak dapat memulai batch B.
     */
    public function test_22_produksi_user_outlet_a_cannot_start_batch_b(): void
    {
        $response = $this->actingAs($this->produksiA)
            ->post(route('produksi.batches.start', $this->batchB));

        $response->assertStatus(404);
        $this->assertEquals(BatchStatus::BAHAN, $this->batchB->fresh()->status);
    }

    /**
     * 23. Start batch lintas-outlet menghasilkan 404.
     */
    public function test_23_start_batch_cross_tenant_returns_404(): void
    {
        // Produksi B attempting to start Batch A
        $response = $this->actingAs($this->produksiB)
            ->post(route('produksi.batches.start', $this->batchA));

        $response->assertStatus(404);
    }

    /**
     * 24. User Produksi Outlet A dapat complete printing Batch A sesuai state workflow valid.
     */
    public function test_24_produksi_user_outlet_a_can_complete_printing_batch_a(): void
    {
        // Setup Batch A with printing route
        $batchAPrinting = ProductionBatch::create([
            'order_item_id'                                 => $this->itemA->id,
            'batch_no'                                      => 'BAT-A-PRINT-001',
            'target_qty'                                    => 5,
            'status'                                        => BatchStatus::PRINTING,
            'requires_printing'                             => true,
            'printing_cost_per_unit_snapshot'               => '3000.00',
            'direct_labor_cost_per_unit_snapshot'           => '5000.00',
            'packaging_process_cost_per_good_unit_snapshot' => '2000.00',
            'other_direct_cost_per_unit_snapshot'           => '1000.00',
            'production_employee_id'                        => $this->produksiA->id,
        ]);
        BatchMaterial::create([
            'batch_id'      => $batchAPrinting->id,
            'material_id'   => $this->materialA->id,
            'required_qty'  => '12.5000',
            'allocated_qty' => '12.5000',
            'used_qty'      => '0.0000',
        ]);

        $response = $this->actingAs($this->produksiA)
            ->post(route('produksi.batches.complete-printing', $batchAPrinting));

        $response->assertRedirect(route('produksi.batches.show', $batchAPrinting));
        $this->assertEquals(BatchStatus::PRODUKSI, $batchAPrinting->fresh()->status);
    }

    /**
     * 25. Complete printing Batch B oleh user Outlet A ditolak.
     */
    public function test_25_complete_printing_batch_b_by_user_a_is_rejected_with_404(): void
    {
        $batchBPrinting = ProductionBatch::create([
            'order_item_id'                                 => $this->itemB->id,
            'batch_no'                                      => 'BAT-B-PRINT-002',
            'target_qty'                                    => 5,
            'status'                                        => BatchStatus::PRINTING,
            'requires_printing'                             => true,
            'printing_cost_per_unit_snapshot'               => '3000.00',
            'direct_labor_cost_per_unit_snapshot'           => '4000.00',
            'packaging_process_cost_per_good_unit_snapshot' => '1500.00',
            'other_direct_cost_per_unit_snapshot'           => '800.00',
            'production_employee_id'                        => $this->produksiB->id,
        ]);
        BatchMaterial::create([
            'batch_id'      => $batchBPrinting->id,
            'material_id'   => $this->materialB->id,
            'required_qty'  => '10.0000',
            'allocated_qty' => '10.0000',
            'used_qty'      => '0.0000',
        ]);

        $response = $this->actingAs($this->produksiA)
            ->post(route('produksi.batches.complete-printing', $batchBPrinting));

        $response->assertStatus(404);
        $this->assertEquals(BatchStatus::PRINTING, $batchBPrinting->fresh()->status);
    }

    /**
     * 26. User Produksi Outlet A dapat complete packing Batch A sesuai state workflow valid.
     */
    public function test_26_produksi_user_outlet_a_can_complete_packing_batch_a(): void
    {
        $batchAPacking = ProductionBatch::create([
            'order_item_id'                                 => $this->itemA->id,
            'batch_no'                                      => 'BAT-A-PACK-001',
            'target_qty'                                    => 5,
            'status'                                        => BatchStatus::PACKING,
            'requires_printing'                             => false,
            'direct_labor_cost_per_unit_snapshot'           => '5000.00',
            'packaging_process_cost_per_good_unit_snapshot' => '2000.00',
            'other_direct_cost_per_unit_snapshot'           => '1000.00',
            'production_employee_id'                        => $this->produksiA->id,
        ]);
        BatchMaterial::create([
            'batch_id'      => $batchAPacking->id,
            'material_id'   => $this->materialA->id,
            'required_qty'  => '12.5000',
            'allocated_qty' => '12.5000',
            'used_qty'      => '0.0000',
        ]);
        $prodResult = ProductionResult::create([
            'batch_id'    => $batchAPacking->id,
            'actual_qty'  => 5,
            'result_date' => now()->toDateString(),
            'notes'       => 'Hasil produksi fisik',
            'recorded_by' => $this->produksiA->id,
        ]);
        QcResult::create([
            'production_result_id' => $prodResult->id,
            'good_qty'             => 5,
            'reject_qty'           => 0,
            'qc_date'              => now()->toDateString(),
            'notes'                => 'QC pass',
            'qc_by'                => $this->produksiA->id,
        ]);

        $response = $this->actingAs($this->produksiA)
            ->post(route('produksi.batches.complete-packing', $batchAPacking));

        $response->assertRedirect(route('produksi.batches.show', $batchAPacking));
        $this->assertEquals(BatchStatus::SELESAI, $batchAPacking->fresh()->status);
    }

    /**
     * 27. Complete packing Batch B oleh user Outlet A ditolak.
     */
    public function test_27_complete_packing_batch_b_by_user_a_is_rejected_with_404(): void
    {
        $batchBPacking = ProductionBatch::create([
            'order_item_id'                                 => $this->itemB->id,
            'batch_no'                                      => 'BAT-B-PACK-002',
            'target_qty'                                    => 5,
            'status'                                        => BatchStatus::PACKING,
            'requires_printing'                             => true,
            'printing_cost_per_unit_snapshot'               => '3000.00',
            'direct_labor_cost_per_unit_snapshot'           => '4000.00',
            'packaging_process_cost_per_good_unit_snapshot' => '1500.00',
            'other_direct_cost_per_unit_snapshot'           => '800.00',
            'production_employee_id'                        => $this->produksiB->id,
        ]);
        BatchMaterial::create([
            'batch_id'      => $batchBPacking->id,
            'material_id'   => $this->materialB->id,
            'required_qty'  => '10.0000',
            'allocated_qty' => '10.0000',
            'used_qty'      => '0.0000',
        ]);
        $prodResult = ProductionResult::create([
            'batch_id'    => $batchBPacking->id,
            'actual_qty'  => 5,
            'result_date' => now()->toDateString(),
            'notes'       => 'Hasil produksi fisik B',
            'recorded_by' => $this->produksiB->id,
        ]);
        QcResult::create([
            'production_result_id' => $prodResult->id,
            'good_qty'             => 5,
            'reject_qty'           => 0,
            'qc_date'              => now()->toDateString(),
            'notes'                => 'QC pass B',
            'qc_by'                => $this->produksiB->id,
        ]);

        $response = $this->actingAs($this->produksiA)
            ->post(route('produksi.batches.complete-packing', $batchBPacking));

        $response->assertStatus(404);
        $this->assertEquals(BatchStatus::PACKING, $batchBPacking->fresh()->status);
    }

    /**
     * 28. Batch dengan parent Order unassigned ditolak.
     */
    public function test_28_batch_with_unassigned_parent_order_rejected(): void
    {
        $response = $this->actingAs($this->produksiA)
            ->post(route('produksi.batches.start', $this->unassignedBatch));

        $response->assertStatus(404);
    }

    /**
     * 29. Batch dengan Variant/Product tidak konsisten ditolak.
     */
    public function test_29_batch_with_inconsistent_variant_or_product_rejected(): void
    {
        // Batch under Order A, but item points to Variant B
        $mismatchedItem = OrderItem::create([
            'order_id'           => $this->orderA->id,
            'variant_id'         => $this->variantB->id,
            'qty_ordered'        => 5,
            'unit_selling_price' => 85000,
        ]);
        $mismatchedBatch = ProductionBatch::create([
            'order_item_id'     => $mismatchedItem->id,
            'batch_no'          => 'BAT-MISMATCH-001',
            'target_qty'        => 5,
            'status'            => BatchStatus::BAHAN,
            'requires_printing' => false,
        ]);
        BatchMaterial::create([
            'batch_id'      => $mismatchedBatch->id,
            'material_id'   => $this->materialA->id,
            'required_qty'  => '10.0000',
            'allocated_qty' => '10.0000',
            'used_qty'      => '0.0000',
        ]);

        $response = $this->actingAs($this->produksiA)
            ->post(route('produksi.batches.start', $mismatchedBatch));

        $response->assertStatus(404);
    }

    /**
     * 30. Batch dengan material lintas-outlet ditolak pada write path yang memakainya.
     */
    public function test_30_batch_with_cross_tenant_material_rejected_on_write(): void
    {
        // Create batch with Material B
        $batchWithMaterialB = ProductionBatch::create([
            'order_item_id'     => $this->itemA->id,
            'batch_no'          => 'BAT-CROSS-MAT-001',
            'target_qty'        => 5,
            'status'            => BatchStatus::BAHAN,
            'requires_printing' => false,
        ]);
        BatchMaterial::create([
            'batch_id'      => $batchWithMaterialB->id,
            'material_id'   => $this->materialB->id,
            'required_qty'  => '10.0000',
            'allocated_qty' => '10.0000',
            'used_qty'      => '0.0000',
        ]);

        $response = $this->actingAs($this->produksiA)
            ->post(route('produksi.batches.start', $batchWithMaterialB));

        $response->assertStatus(404);
    }

    /**
     * 31. User tanpa outlet ditolak sebelum write.
     */
    public function test_31_user_without_outlet_rejected_before_write(): void
    {
        $payload = [
            'order_item_id'    => $this->itemA->id,
            'target_qty'       => 2,
            'planned_end_date' => now()->addDays(2)->toDateString(),
        ];

        $response = $this->actingAs($this->unboundAdmin)
            ->post(route('admin.orders.batches.store', $this->orderA), $payload);

        $response->assertStatus(403);
    }

    /**
     * 32. Outlet INACTIVE ditolak.
     */
    public function test_32_inactive_outlet_user_rejected(): void
    {
        $payload = [
            'order_item_id'    => $this->itemA->id,
            'target_qty'       => 2,
            'planned_end_date' => now()->addDays(2)->toDateString(),
        ];

        $response = $this->actingAs($this->adminInactiveOutlet)
            ->post(route('admin.orders.batches.store', $this->orderA), $payload);

        $response->assertStatus(403);
    }

    /**
     * 33. User INACTIVE ditolak.
     */
    public function test_33_inactive_user_rejected(): void
    {
        $payload = [
            'order_item_id'    => $this->itemA->id,
            'target_qty'       => 2,
            'planned_end_date' => now()->addDays(2)->toDateString(),
        ];

        $response = $this->actingAs($this->inactiveAdminA)
            ->post(route('admin.orders.batches.store', $this->orderA), $payload);

        // EnsureUserIsActive redirects inactive user to login
        $response->assertRedirect('/login');
    }

    /**
     * 34. CUSTOMER tidak memperoleh akses internal produksi.
     */
    public function test_34_customer_user_has_no_access_to_internal_production_routes(): void
    {
        $payload = [
            'order_item_id'    => $this->itemA->id,
            'target_qty'       => 2,
            'planned_end_date' => now()->addDays(2)->toDateString(),
        ];

        // Customer to admin batch store
        $this->actingAs($this->customerUser)
            ->post(route('admin.orders.batches.store', $this->orderA), $payload)
            ->assertStatus(403);

        // Customer to produksi start
        $this->actingAs($this->customerUser)
            ->post(route('produksi.batches.start', $this->batchA))
            ->assertStatus(403);
    }

    /**
     * 35. RoleMiddleware existing tetap membatasi akses.
     */
    public function test_35_role_middleware_enforced_strictly(): void
    {
        // Produksi user cannot post to admin route
        $payload = [
            'order_item_id'    => $this->itemA->id,
            'target_qty'       => 2,
            'planned_end_date' => now()->addDays(2)->toDateString(),
        ];
        $this->actingAs($this->produksiA)
            ->post(route('admin.orders.batches.store', $this->orderA), $payload)
            ->assertStatus(403);

        // Gudang user cannot post to produksi start
        $this->actingAs($this->gudangA)
            ->post(route('produksi.batches.start', $this->batchA))
            ->assertStatus(403);
    }

    /**
     * 36. Owner tidak memperoleh bypass route produksi.
     */
    public function test_36_owner_cannot_bypass_production_routes(): void
    {
        $this->actingAs($this->ownerA)
            ->post(route('produksi.batches.start', $this->batchA))
            ->assertStatus(403);

        $this->actingAs($this->ownerA)
            ->post(route('produksi.batches.complete-printing', $this->batchA))
            ->assertStatus(403);

        $this->actingAs($this->ownerA)
            ->post(route('produksi.batches.complete-packing', $this->batchA))
            ->assertStatus(403);
    }

    /**
     * 37. PO approval tetap ADMIN.
     */
    public function test_37_po_approval_remains_admin_exclusive(): void
    {
        $supplier = \App\Models\Master\Supplier::create([
            'supplier_name' => 'Supplier Rayon',
            'contact_person'=> 'Pak Budi',
            'phone'         => '081299990000',
            'outlet_id'     => $this->outletA->id,
        ]);
        $purchase = Purchase::create([
            'purchase_no'    => 'PO-TEST-001',
            'supplier_id'    => $supplier->id,
            'purchase_date'  => now()->toDateString(),
            'total_amount'   => 1000000,
            'status'         => \App\Enums\PurchaseStatus::DRAFT,
            'outlet_id'      => $this->outletA->id,
        ]);

        // Produksi user cannot approve PO
        $this->actingAs($this->produksiA)
            ->post(route('admin.purchases.approvals.approve', $purchase))
            ->assertStatus(403);

        // Owner user cannot approve PO
        $this->actingAs($this->ownerA)
            ->post(route('admin.purchases.approvals.approve', $purchase))
            ->assertStatus(403);
    }

    /**
     * 38. Request unauthorized tidak mengubah batch status.
     */
    public function test_38_unauthorized_request_does_not_modify_batch_status(): void
    {
        $this->actingAs($this->produksiA)
            ->post(route('produksi.batches.start', $this->batchB));

        $this->assertEquals(BatchStatus::BAHAN, $this->batchB->fresh()->status);
    }

    /**
     * 39. Request unauthorized tidak mengubah Order status.
     */
    public function test_39_unauthorized_request_does_not_modify_order_status(): void
    {
        $this->actingAs($this->produksiA)
            ->post(route('produksi.batches.start', $this->batchB));

        $this->assertEquals(OrderStatus::DEAL, $this->orderB->fresh()->status);
    }

    /**
     * 40. Request unauthorized tidak mengubah stock movement.
     */
    public function test_40_unauthorized_request_does_not_modify_stock_movement(): void
    {
        $initialMovementCount = StockMovement::count();

        $this->actingAs($this->produksiA)
            ->post(route('produksi.batches.start', $this->batchB));

        $this->assertEquals($initialMovementCount, StockMovement::count());
    }

    /**
     * 41. Request unauthorized tidak membuat status history baru.
     */
    public function test_41_unauthorized_request_does_not_create_status_history(): void
    {
        $initialHistoryCount = OrderStatusHistory::count();

        $this->actingAs($this->produksiA)
            ->post(route('produksi.batches.start', $this->batchB));

        $this->assertEquals($initialHistoryCount, OrderStatusHistory::count());
    }

    /**
     * 42. Mode tenant isolation FALSE mempertahankan workflow produksi legacy.
     */
    public function test_42_tenant_isolation_false_preserves_legacy_workflow(): void
    {
        config(['nayara.tenant_isolation_enabled' => false]);

        // Legacy user with null outlet_id can create batch for unassigned order
        $legacyAdmin = $this->createTestUser(null, UserRole::ADMIN, UserStatus::ACTIVE, 'leg-adm');

        $payload = [
            'order_item_id'    => $this->unassignedItem->id,
            'target_qty'       => 2,
            'planned_end_date' => now()->addDays(2)->toDateString(),
        ];

        $response = $this->actingAs($legacyAdmin)
            ->post(route('admin.orders.batches.store', $this->unassignedOrder), $payload);

        $response->assertRedirect();
        $this->assertEquals(OrderStatus::BAHAN, $this->unassignedOrder->fresh()->status);
    }

    /**
     * 43. Mode tenant isolation TRUE fail-closed ketika tenant invalid.
     */
    public function test_43_tenant_isolation_true_fails_closed_when_tenant_invalid(): void
    {
        config(['nayara.tenant_isolation_enabled' => true]);

        $payload = [
            'order_item_id'    => $this->itemA->id,
            'target_qty'       => 2,
            'planned_end_date' => now()->addDays(2)->toDateString(),
        ];

        $response = $this->actingAs($this->unboundAdmin)
            ->post(route('admin.orders.batches.store', $this->orderA), $payload);

        $response->assertStatus(403);
    }

    /**
     * 44. Fase 6.1 READ endpoints tetap terisolasi tanpa regresi.
     */
    public function test_44_read_endpoints_remain_isolated_without_regression(): void
    {
        // Admin index
        $adminResp = $this->actingAs($this->adminA)->get(route('admin.batches.index'));
        $adminResp->assertOk();
        $adminResp->assertSee($this->batchA->batch_no);
        $adminResp->assertDontSee($this->batchB->batch_no);

        // Produksi show
        $this->actingAs($this->produksiA)->get(route('produksi.batches.show', $this->batchA))->assertOk();
        $this->actingAs($this->produksiA)->get(route('produksi.batches.show', $this->batchB))->assertStatus(404);
    }

    /**
     * 45. Tidak ada global tenant scope yang aktif diam-diam.
     */
    public function test_45_no_stealth_global_tenant_scope_active(): void
    {
        $this->assertEquals(
            ProductionBatch::withoutGlobalScopes()->count(),
            ProductionBatch::count()
        );
    }

    /**
     * 46. Tidak ada data outlet lain termodifikasi akibat aksi write yang tidak sah.
     */
    public function test_46_no_cross_tenant_data_modified_by_unauthorized_write(): void
    {
        $batchBBefore = $this->batchB->fresh()->toArray();
        $orderBBefore = $this->orderB->fresh()->toArray();

        // Admin A attempts to modify Order B
        $payload = [
            'order_item_id'    => $this->itemB->id,
            'target_qty'       => 5,
            'planned_end_date' => now()->addDays(2)->toDateString(),
        ];
        $this->actingAs($this->adminA)->post(route('admin.orders.batches.store', $this->orderB), $payload);

        // Produksi A attempts to start Batch B
        $this->actingAs($this->produksiA)->post(route('produksi.batches.start', $this->batchB));

        $batchBAfter = $this->batchB->fresh()->toArray();
        $orderBAfter = $this->orderB->fresh()->toArray();

        $this->assertEquals($batchBBefore, $batchBAfter);
        $this->assertEquals($orderBBefore, $orderBAfter);
    }

    /**
     * 47. Error di tengah write tidak meninggalkan state produksi parsial.
     */
    public function test_47_mid_write_service_error_leaves_no_partial_production_state(): void
    {
        $initialBatchCount = ProductionBatch::count();
        $initialBmCount = BatchMaterial::count();

        // Target qty 0 triggers domain exception inside transaction
        $payload = [
            'order_item_id'    => $this->itemA->id,
            'target_qty'       => 0,
            'planned_end_date' => now()->addDays(2)->toDateString(),
        ];

        $response = $this->actingAs($this->adminA)
            ->post(route('admin.orders.batches.store', $this->orderA), $payload);

        $this->assertEquals($initialBatchCount, ProductionBatch::count());
        $this->assertEquals($initialBmCount, BatchMaterial::count());
    }

    /**
     * 48. Perhitungan kuantitas, BOM, HPP, dan workflow bisnis existing tetap utuh.
     */
    public function test_48_business_calculation_and_costing_integrity_preserved(): void
    {
        $payload = [
            'order_item_id'          => $this->itemA->id,
            'target_qty'             => 4,
            'planned_end_date'       => now()->addDays(3)->toDateString(),
            'production_employee_id' => $this->produksiA->id,
        ];

        $this->actingAs($this->adminA)
            ->post(route('admin.orders.batches.store', $this->orderA), $payload);

        $batch = ProductionBatch::where('order_item_id', $this->itemA->id)->latest('id')->first();
        $this->assertNotNull($batch);

        // Cost snapshot matches variant configuration
        $this->assertEquals('5000.00', $batch->direct_labor_cost_per_unit_snapshot);
        $this->assertEquals('2000.00', $batch->packaging_process_cost_per_good_unit_snapshot);
        $this->assertEquals('1000.00', $batch->other_direct_cost_per_unit_snapshot);

        // Material requirement: 4 target * 2.5 BOM = 10.0000 required
        $bm = $batch->batchMaterials->first();
        $this->assertNotNull($bm);
        $this->assertEquals('10.0000', $bm->required_qty);
    }
}
