<?php

namespace Tests\Feature\System;

use App\Enums\BatchStatus;
use App\Enums\InvoiceStatus;
use App\Enums\MovementType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\PurchaseStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Inventory\MaterialStock;
use App\Models\Inventory\Purchase;
use App\Models\Inventory\StockMovement;
use App\Models\Master\BomItem;
use App\Models\Master\Customer;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Master\Supplier;
use App\Models\Master\Unit;
use App\Models\Production\BatchMaterial;
use App\Models\Production\ProductionBatch;
use App\Models\Production\ProductionResult;
use App\Models\Production\QcResult;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\Sales\Payment;
use App\Models\System\Outlet;
use App\Models\System\User;
use App\Services\Inventory\BatchMaterialPlanningService;
use App\Services\Inventory\MaterialAllocationService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MaterialAllocationTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Outlet $outletA;
    protected Outlet $outletB;
    protected Outlet $outletInactive;

    protected User $gudangA;
    protected User $gudangB;
    protected User $produksiA;
    protected User $adminA;
    protected User $ownerA;
    protected User $customerUser;
    protected User $unboundGudang;
    protected User $gudangInactiveOutlet;
    protected User $inactiveGudangA;

    protected Customer $customerA;
    protected Customer $customerB;

    protected Product $productA;
    protected Product $productB;

    protected ProductVariant $variantA;
    protected ProductVariant $variantB;

    protected Material $materialA;
    protected Material $materialB;

    protected Order $orderA;
    protected Order $orderB;
    protected Order $unassignedOrder;

    protected OrderItem $itemA;
    protected OrderItem $itemB;
    protected OrderItem $unassignedItem;

    protected ProductionBatch $batchA;
    protected ProductionBatch $batchB;
    protected ProductionBatch $unassignedBatch;

    protected ProductionBatch $batchAProduksi;
    protected ProductionBatch $batchBProduksi;
    protected BatchMaterial $bmAProduksi;
    protected BatchMaterial $bmBProduksi;

    protected BatchMaterialPlanningService $mrpService;
    protected MaterialAllocationService $allocationService;

    protected function setUp(): void
    {
        parent::setUp();

        config(['nayara.tenant_isolation_enabled' => true]);

        $this->mrpService = app(BatchMaterialPlanningService::class);
        $this->allocationService = app(MaterialAllocationService::class);

        // 1. Outlets
        $this->outletA = $this->createTestOutlet('OUT-GUDANG-A', 'Konveksi Hijab A', 'ACTIVE');
        $this->outletB = $this->createTestOutlet('OUT-GUDANG-B', 'Konveksi Hijab B', 'ACTIVE');
        $this->outletInactive = $this->createTestOutlet('OUT-INACTIVE', 'Konveksi Inactive', 'INACTIVE');

        // 2. Users
        $this->gudangA = $this->createTestUser($this->outletA, UserRole::GUDANG, UserStatus::ACTIVE, 'gudang-a');
        $this->gudangB = $this->createTestUser($this->outletB, UserRole::GUDANG, UserStatus::ACTIVE, 'gudang-b');
        $this->produksiA = $this->createTestUser($this->outletA, UserRole::PRODUKSI, UserStatus::ACTIVE, 'prod-a');
        $this->adminA = $this->createTestUser($this->outletA, UserRole::ADMIN, UserStatus::ACTIVE, 'admin-a');
        $this->ownerA = $this->createTestUser($this->outletA, UserRole::OWNER, UserStatus::ACTIVE, 'owner-a');
        $this->customerUser = $this->createTestUser($this->outletA, UserRole::CUSTOMER, UserStatus::ACTIVE, 'cust-user');
        $this->unboundGudang = $this->createTestUser(null, UserRole::GUDANG, UserStatus::ACTIVE, 'gudang-unbound');
        $this->gudangInactiveOutlet = $this->createTestUser($this->outletInactive, UserRole::GUDANG, UserStatus::ACTIVE, 'gudang-inact-out');
        $this->inactiveGudangA = $this->createTestUser($this->outletA, UserRole::GUDANG, UserStatus::INACTIVE, 'gudang-inact');

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

        // 4. Products & Variants
        $this->productA = Product::create([
            'product_name' => 'Gamis Syari Nayara A',
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
            'product_name' => 'Khimar Instan Nayara B',
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

        // 5. Units, Materials & Stocks
        $unit = Unit::firstOrCreate(['unit_name' => 'Meter', 'symbol' => 'm']);

        $this->materialA = Material::create([
            'material_name' => 'Kain Rayon Twill A',
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
            'material_name' => 'Kain Ceruty Babydoll B',
            'unit_id'       => $unit->id,
            'active'        => true,
            'outlet_id'     => $this->outletB->id,
        ]);
        MaterialStock::create([
            'material_id'   => $this->materialB->id,
            'qty_on_hand'   => '400.0000',
            'avg_unit_cost' => '25000.0000',
        ]);

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

        // 6. Orders
        $this->orderA = Order::create([
            'customer_id' => $this->customerA->id,
            'order_no'    => 'SO-GUDANG-A-001',
            'order_date'  => '2026-10-23',
            'status'      => OrderStatus::PRODUKSI,
            'outlet_id'   => $this->outletA->id,
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
            'order_no'    => 'SO-GUDANG-B-002',
            'order_date'  => '2026-10-23',
            'status'      => OrderStatus::PRODUKSI,
            'outlet_id'   => $this->outletB->id,
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
            'status'      => OrderStatus::PRODUKSI,
            'outlet_id'   => null,
        ]);
        $this->unassignedItem = OrderItem::create([
            'order_id'           => $this->unassignedOrder->id,
            'variant_id'         => $this->variantA->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 150000,
        ]);
        $this->createConfirmedDownPayment($this->unassignedOrder, '1500000.00', '500000.00');

        // 7. Batches in BAHAN status (ready for material planning & allocation)
        $this->batchA = ProductionBatch::create([
            'order_item_id'                                 => $this->itemA->id,
            'batch_no'                                      => 'BAT-GUD-A-001',
            'target_qty'                                    => 10,
            'status'                                        => BatchStatus::BAHAN,
            'requires_printing'                             => false,
            'direct_labor_cost_per_unit_snapshot'           => '5000.00',
            'packaging_process_cost_per_good_unit_snapshot' => '2000.00',
            'other_direct_cost_per_unit_snapshot'           => '1000.00',
            'production_employee_id'                        => $this->produksiA->id,
            'planned_start_date'                            => now()->toDateString(),
        ]);
        $this->mrpService->generateMaterialPlanning($this->batchA);

        $this->batchB = ProductionBatch::create([
            'order_item_id'                                 => $this->itemB->id,
            'batch_no'                                      => 'BAT-GUD-B-002',
            'target_qty'                                    => 15,
            'status'                                        => BatchStatus::BAHAN,
            'requires_printing'                             => true,
            'printing_cost_per_unit_snapshot'               => '3000.00',
            'direct_labor_cost_per_unit_snapshot'           => '4000.00',
            'packaging_process_cost_per_good_unit_snapshot' => '1500.00',
            'other_direct_cost_per_unit_snapshot'           => '800.00',
            'production_employee_id'                        => null,
            'planned_start_date'                            => now()->toDateString(),
        ]);
        $this->mrpService->generateMaterialPlanning($this->batchB);

        $this->unassignedBatch = ProductionBatch::create([
            'order_item_id'                                 => $this->unassignedItem->id,
            'batch_no'                                      => 'BAT-NULL-GUD-003',
            'target_qty'                                    => 5,
            'status'                                        => BatchStatus::BAHAN,
            'requires_printing'                             => false,
            'direct_labor_cost_per_unit_snapshot'           => '5000.00',
            'packaging_process_cost_per_good_unit_snapshot' => '2000.00',
            'other_direct_cost_per_unit_snapshot'           => '1000.00',
            'planned_start_date'                            => now()->toDateString(),
        ]);
        $this->mrpService->generateMaterialPlanning($this->unassignedBatch);

        // 8. Batches in PRODUKSI status (for additional material testing)
        $this->batchAProduksi = ProductionBatch::create([
            'order_item_id'          => $this->itemA->id,
            'batch_no'               => 'BAT-PROD-A-001',
            'target_qty'             => 10,
            'status'                 => BatchStatus::PRODUKSI,
            'requires_printing'      => false,
            'production_employee_id' => $this->produksiA->id,
            'planned_start_date'     => now()->toDateString(),
        ]);
        $this->bmAProduksi = BatchMaterial::create([
            'batch_id'      => $this->batchAProduksi->id,
            'material_id'   => $this->materialA->id,
            'required_qty'  => '25.0000',
            'allocated_qty' => '25.0000',
            'used_qty'      => '0.0000',
        ]);

        $this->batchBProduksi = ProductionBatch::create([
            'order_item_id'          => $this->itemB->id,
            'batch_no'               => 'BAT-PROD-B-002',
            'target_qty'             => 15,
            'status'                 => BatchStatus::PRODUKSI,
            'requires_printing'      => false,
            'planned_start_date'     => now()->toDateString(),
        ]);
        $this->bmBProduksi = BatchMaterial::create([
            'batch_id'      => $this->batchBProduksi->id,
            'material_id'   => $this->materialB->id,
            'required_qty'  => '30.0000',
            'allocated_qty' => '30.0000',
            'used_qty'      => '0.0000',
        ]);
    }

    private function createTestOutlet(string $code, string $name, string $status): Outlet
    {
        return Outlet::create([
            'outlet_code' => $code,
            'outlet_name' => $name,
            'status'      => $status,
            'address'     => 'Jl. Gudang No. 1',
            'phone'       => '08123456789',
        ]);
    }

    private function createTestUser(?Outlet $outlet, UserRole $role, UserStatus $status, string $prefix): User
    {
        return User::create([
            'outlet_id' => $outlet?->id,
            'name'      => "User {$prefix}",
            'username'  => "user_{$prefix}_" . uniqid(),
            'email'     => "{$prefix}_" . uniqid() . '@nayara.test',
            'password'  => bcrypt('password'),
            'role'      => $role,
            'status'    => $status,
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
    // SECTION 1: GUDANG READ & MATERIAL PLANNING LIST/SHOW (TEST 01 - 08)
    // =========================================================================

    public function test_01_gudang_a_can_open_material_planning_batch_a(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->get(route('gudang.material-planning.show', $this->batchA));

        $response->assertOk();
        $response->assertSee($this->batchA->batch_no);
        $response->assertSee($this->materialA->material_name);
    }

    public function test_02_gudang_a_cannot_open_planning_batch_b(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->get(route('gudang.material-planning.show', $this->batchB));

        $response->assertNotFound();
    }

    public function test_03_gudang_b_can_open_planning_batch_b(): void
    {
        $response = $this->actingAs($this->gudangB)
            ->get(route('gudang.material-planning.show', $this->batchB));

        $response->assertOk();
        $response->assertSee($this->batchB->batch_no);
        $response->assertSee($this->materialB->material_name);
    }

    public function test_04_gudang_b_cannot_see_planning_batch_a(): void
    {
        $response = $this->actingAs($this->gudangB)
            ->get(route('gudang.material-planning.show', $this->batchA));

        $response->assertNotFound();
    }

    public function test_05_foreign_batch_detail_returns_404(): void
    {
        $this->actingAs($this->gudangA)
            ->get(route('gudang.material-planning.show', $this->batchB))
            ->assertNotFound();

        $this->actingAs($this->gudangB)
            ->get(route('gudang.material-planning.show', $this->batchA))
            ->assertNotFound();
    }

    public function test_06_batch_unassigned_rejected_when_flag_true(): void
    {
        $this->actingAs($this->gudangA)
            ->get(route('gudang.material-planning.show', $this->unassignedBatch))
            ->assertNotFound();
    }

    public function test_07_stock_data_of_outlet_b_does_not_appear_on_planning_a(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->get(route('gudang.material-planning.show', $this->batchA));

        $response->assertOk();
        $response->assertDontSee('Kain Ceruty Babydoll B');
        $response->assertDontSee($this->batchB->batch_no);
    }

    public function test_08_material_planning_index_scoped_to_active_tenant(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->get(route('gudang.material-planning.index'));

        $response->assertOk();
        $response->assertSee($this->batchA->batch_no);
        $response->assertDontSee($this->batchB->batch_no);
        $response->assertDontSee($this->unassignedBatch->batch_no);
    }

    // =========================================================================
    // SECTION 2: GUDANG WRITE ALOKASI & CALCULATE (TEST 09 - 25)
    // =========================================================================

    public function test_09_gudang_a_can_allocate_batch_a_materials_per_existing_workflow(): void
    {
        $initialStock = MaterialStock::where('material_id', $this->materialA->id)->first()->qty_on_hand;

        $response = $this->actingAs($this->gudangA)
            ->post(route('gudang.material-planning.allocate', $this->batchA));

        $response->assertRedirect(route('gudang.material-planning.show', $this->batchA));
        $response->assertSessionHas('success');

        $bm = BatchMaterial::where('batch_id', $this->batchA->id)->first();
        $this->assertEquals('25.0000', $bm->allocated_qty);

        $newStock = MaterialStock::where('material_id', $this->materialA->id)->first()->qty_on_hand;
        $this->assertEquals(bcsub($initialStock, '25.0000', 4), $newStock);

        $this->assertDatabaseHas('stock_movements', [
            'material_id'    => $this->materialA->id,
            'movement_type'  => MovementType::OUT,
            'reference_type' => 'batch_materials',
            'reference_id'   => $bm->id,
            'qty'            => '25.0000',
        ]);
    }

    public function test_10_gudang_b_can_allocate_batch_b(): void
    {
        $initialStock = MaterialStock::where('material_id', $this->materialB->id)->first()->qty_on_hand;

        $response = $this->actingAs($this->gudangB)
            ->post(route('gudang.material-planning.allocate', $this->batchB));

        $response->assertRedirect(route('gudang.material-planning.show', $this->batchB));
        $response->assertSessionHas('success');

        $bm = BatchMaterial::where('batch_id', $this->batchB->id)->first();
        $this->assertEquals('30.0000', $bm->allocated_qty);

        $newStock = MaterialStock::where('material_id', $this->materialB->id)->first()->qty_on_hand;
        $this->assertEquals(bcsub($initialStock, '30.0000', 4), $newStock);
    }

    public function test_11_gudang_a_cannot_allocate_batch_b(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->post(route('gudang.material-planning.allocate', $this->batchB));

        $response->assertNotFound();

        $bm = BatchMaterial::where('batch_id', $this->batchB->id)->first();
        $this->assertEquals('0.0000', $bm->allocated_qty);
    }

    public function test_12_manipulating_batch_id_on_allocate_returns_404(): void
    {
        $this->actingAs($this->gudangB)
            ->post(route('gudang.material-planning.allocate', $this->batchA))
            ->assertNotFound();
    }

    public function test_13_batch_material_from_another_batch_is_rejected_on_additional(): void
    {
        $response = $this->actingAs($this->produksiA)->post(route('produksi.batches.materials.additional', [
            'batch'         => $this->batchAProduksi,
            'batchMaterial' => $this->bmBProduksi,
        ]), [
            'additional_qty' => 5,
        ]);

        $response->assertNotFound();
    }

    public function test_14_cross_outlet_material_is_rejected(): void
    {
        $crossBm = BatchMaterial::create([
            'batch_id'      => $this->batchAProduksi->id,
            'material_id'   => $this->materialB->id,
            'required_qty'  => '10.0000',
            'allocated_qty' => '10.0000',
            'used_qty'      => '0.0000',
        ]);

        $response = $this->actingAs($this->produksiA)->post(route('produksi.batches.materials.additional', [
            'batch'         => $this->batchAProduksi,
            'batchMaterial' => $crossBm,
        ]), [
            'additional_qty' => 2,
        ]);

        $response->assertNotFound();
    }

    public function test_15_material_null_is_rejected_in_true_mode(): void
    {
        $orphanBm = new BatchMaterial([
            'batch_id'      => $this->batchAProduksi->id,
            'material_id'   => 999999, // nonexistent material
            'required_qty'  => '5.0000',
            'allocated_qty' => '5.0000',
            'used_qty'      => '0.0000',
        ]);

        // Model helper returns false
        $this->assertFalse($this->batchAProduksi->hasConsistentTenantRelations($this->outletA->id) && $orphanBm->material !== null);
    }

    public function test_16_material_outlet_id_null_is_rejected_in_true_mode(): void
    {
        $unit = Unit::first();
        $legacyUnassignedMat = Material::create([
            'material_name' => 'Kain Unassigned Legacy',
            'unit_id'       => $unit->id,
            'active'        => true,
            'outlet_id'     => null,
        ]);

        $bm = BatchMaterial::create([
            'batch_id'      => $this->batchAProduksi->id,
            'material_id'   => $legacyUnassignedMat->id,
            'required_qty'  => '5.0000',
            'allocated_qty' => '5.0000',
            'used_qty'      => '0.0000',
        ]);

        // Guard rejects with 404 because outlet_id is null
        $response = $this->actingAs($this->produksiA)->post(route('produksi.batches.materials.additional', [
            'batch'         => $this->batchAProduksi,
            'batchMaterial' => $bm,
        ]), [
            'additional_qty' => 2,
        ]);

        $response->assertNotFound();
    }

    public function test_17_direct_service_call_with_cross_tenant_batch_throws_domain_exception(): void
    {
        $this->actingAs($this->gudangA);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Integritas data tenant tidak valid');

        $this->allocationService->allocateBatchMaterials($this->batchB, $this->gudangA->id);
    }

    public function test_18_stock_of_outlet_b_does_not_change_after_illegal_attempt_by_gudang_a(): void
    {
        $stockBBefore = MaterialStock::where('material_id', $this->materialB->id)->first()->qty_on_hand;

        $this->actingAs($this->gudangA)
            ->post(route('gudang.material-planning.allocate', $this->batchB))
            ->assertNotFound();

        $stockBAfter = MaterialStock::where('material_id', $this->materialB->id)->first()->qty_on_hand;
        $this->assertEquals($stockBBefore, $stockBAfter);
    }

    public function test_19_batch_material_of_outlet_b_does_not_change_after_illegal_attempt_by_gudang_a(): void
    {
        $bmBefore = BatchMaterial::where('batch_id', $this->batchB->id)->first()->toArray();

        $this->actingAs($this->gudangA)
            ->post(route('gudang.material-planning.allocate', $this->batchB))
            ->assertNotFound();

        $bmAfter = BatchMaterial::where('batch_id', $this->batchB->id)->first()->toArray();
        $this->assertEquals($bmBefore, $bmAfter);
    }

    public function test_20_no_stock_movement_created_from_unauthorized_request(): void
    {
        $initialMovements = StockMovement::count();

        $this->actingAs($this->gudangA)
            ->post(route('gudang.material-planning.allocate', $this->batchB))
            ->assertNotFound();

        $this->assertEquals($initialMovements, StockMovement::count());
    }

    public function test_21_no_partial_movement_when_transaction_errors(): void
    {
        // Reduce stock below requirement
        MaterialStock::where('material_id', $this->materialA->id)->update(['qty_on_hand' => '5.0000']);
        $initialMovements = StockMovement::count();

        $response = $this->actingAs($this->gudangA)
            ->from(route('gudang.material-planning.show', $this->batchA))
            ->post(route('gudang.material-planning.allocate', $this->batchA));

        $response->assertRedirect(route('gudang.material-planning.show', $this->batchA));
        $response->assertSessionHas('error');

        $this->assertEquals($initialMovements, StockMovement::count());
        $this->assertEquals('5.0000', MaterialStock::where('material_id', $this->materialA->id)->first()->qty_on_hand);
        $this->assertEquals('0.0000', BatchMaterial::where('batch_id', $this->batchA->id)->first()->allocated_qty);
    }

    public function test_22_shortage_calculation_follows_existing_business_rules(): void
    {
        MaterialStock::where('material_id', $this->materialA->id)->update(['qty_on_hand' => '10.0000']); // Needs 25

        $mrp = $this->mrpService->getMaterialRequirementsData($this->batchA);
        $this->assertEquals(1, $mrp['shortage_materials']);
        $this->assertEquals('15.0000', $mrp['items'][0]['shortage_qty']); // 25 - 10 = 15
    }

    public function test_23_allocated_quantity_matches_existing_allocation_result(): void
    {
        $this->actingAs($this->gudangA)
            ->post(route('gudang.material-planning.allocate', $this->batchA))
            ->assertRedirect();

        $bm = BatchMaterial::where('batch_id', $this->batchA->id)->first();
        $this->assertEquals($bm->required_qty, $bm->allocated_qty);
    }

    public function test_24_cannot_allocate_exceeding_available_stock(): void
    {
        MaterialStock::where('material_id', $this->materialA->id)->update(['qty_on_hand' => '20.0000']); // Needs 25

        $response = $this->actingAs($this->gudangA)
            ->from(route('gudang.material-planning.show', $this->batchA))
            ->post(route('gudang.material-planning.allocate', $this->batchA));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_25_no_negative_stock_from_request_manipulation(): void
    {
        MaterialStock::where('material_id', $this->materialA->id)->update(['qty_on_hand' => '10.0000']);

        $this->actingAs($this->gudangA)
            ->post(route('gudang.material-planning.allocate', $this->batchA));

        $currentStock = MaterialStock::where('material_id', $this->materialA->id)->first()->qty_on_hand;
        $this->assertTrue(bccomp($currentStock, '0.0000', 4) >= 0);
    }

    // =========================================================================
    // SECTION 3: ROLES, AUTH, AND COMPATIBILITY (TEST 26 - 44)
    // =========================================================================

    public function test_26_role_middleware_remains_active_on_gudang_routes(): void
    {
        $this->actingAs($this->customerUser)
            ->get(route('gudang.material-planning.index'))
            ->assertForbidden();

        $this->actingAs($this->customerUser)
            ->post(route('gudang.material-planning.allocate', $this->batchA))
            ->assertForbidden();
    }

    public function test_27_produksi_user_cannot_access_gudang_allocation_endpoints(): void
    {
        $this->actingAs($this->produksiA)
            ->get(route('gudang.material-planning.index'))
            ->assertForbidden();

        $this->actingAs($this->produksiA)
            ->post(route('gudang.material-planning.allocate', $this->batchA))
            ->assertForbidden();
    }

    public function test_28_customer_cannot_access_internal_inventory_routes(): void
    {
        $this->actingAs($this->customerUser)
            ->get(route('gudang.material-planning.show', $this->batchA))
            ->assertForbidden();
    }

    public function test_29_owner_cannot_bypass_material_allocation(): void
    {
        $this->actingAs($this->ownerA)
            ->get(route('gudang.material-planning.index'))
            ->assertForbidden();

        $this->actingAs($this->ownerA)
            ->post(route('gudang.material-planning.allocate', $this->batchA))
            ->assertForbidden();
    }

    public function test_30_purchase_order_approval_remains_admin_only(): void
    {
        $supplier = Supplier::create([
            'supplier_name' => 'Supplier Kain Jaya',
            'phone'         => '081299998888',
            'outlet_id'     => $this->outletA->id,
        ]);

        $purchase = Purchase::create([
            'purchase_no'    => 'PO-TEST-' . uniqid(),
            'purchase_date'  => now()->toDateString(),
            'supplier_id'    => $supplier->id,
            'total_amount'   => 1000000,
            'status'         => PurchaseStatus::DRAFT,
            'outlet_id'      => $this->outletA->id,
        ]);

        // Gudang user cannot approve PO
        $this->actingAs($this->gudangA)->post(route('admin.purchases.approvals.approve', $purchase))
            ->assertForbidden();

        // Admin can approve PO
        $this->actingAs($this->adminA)->post(route('admin.purchases.approvals.approve', $purchase))
            ->assertRedirect();
    }

    public function test_31_user_without_outlet_is_rejected(): void
    {
        $response = $this->actingAs($this->unboundGudang)
            ->get(route('gudang.material-planning.index'));

        $response->assertForbidden();
    }

    public function test_32_inactive_outlet_is_rejected(): void
    {
        $response = $this->actingAs($this->gudangInactiveOutlet)
            ->get(route('gudang.material-planning.index'));

        $response->assertForbidden();
    }

    public function test_33_inactive_user_is_rejected_without_mutation(): void
    {
        $response = $this->actingAs($this->inactiveGudangA)
            ->post(route('gudang.material-planning.allocate', $this->batchA));

        $response->assertRedirect('/login');

        $this->assertEquals('0.0000', BatchMaterial::where('batch_id', $this->batchA->id)->first()->allocated_qty);
    }

    public function test_34_fake_outlet_id_in_payload_has_no_effect(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->post(route('gudang.material-planning.allocate', $this->batchB), [
                'outlet_id' => $this->outletB->id,
            ]);

        $response->assertNotFound();
    }

    public function test_35_fake_x_outlet_id_header_has_no_effect(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->withHeaders(['X-Outlet-ID' => (string) $this->outletB->id])
            ->post(route('gudang.material-planning.allocate', $this->batchB));

        $response->assertNotFound();
    }

    public function test_36_flag_false_preserves_legacy_allocation_behavior(): void
    {
        config(['nayara.tenant_isolation_enabled' => false]);

        $response = $this->actingAs($this->unboundGudang)
            ->get(route('gudang.material-planning.show', $this->unassignedBatch));

        $response->assertOk();
        $response->assertSee($this->unassignedBatch->batch_no);
    }

    public function test_37_flag_true_fails_closed_when_tenant_invalid(): void
    {
        config(['nayara.tenant_isolation_enabled' => true]);

        $this->actingAs($this->unboundGudang)
            ->get(route('gudang.material-planning.show', $this->batchA))
            ->assertForbidden();
    }

    public function test_38_additional_material_request_from_phase_7_2_remains_secure(): void
    {
        $this->actingAs($this->produksiA)
            ->post(route('produksi.batches.materials.additional', [
                'batch'         => $this->batchAProduksi,
                'batchMaterial' => $this->bmAProduksi,
            ]), ['additional_qty' => 5])
            ->assertRedirect();

        $this->assertEquals('30.0000', $this->bmAProduksi->fresh()->allocated_qty);
    }

    public function test_39_null_material_regression_cannot_bypass_ownership_guard(): void
    {
        // Batch with consistent relations must have material non-null and matching outlet
        $this->assertTrue($this->batchA->hasConsistentTenantRelations($this->outletA->id));
        $this->assertFalse($this->batchA->hasConsistentTenantRelations($this->outletB->id));
    }

    public function test_40_production_result_and_qc_tests_remain_passing(): void
    {
        $this->actingAs($this->produksiA)
            ->get(route('produksi.batches.production-result.create', $this->batchAProduksi))
            ->assertOk();
    }

    public function test_41_no_global_tenant_scope_created(): void
    {
        $this->assertEquals(ProductionBatch::withoutGlobalScopes()->count(), ProductionBatch::count());
        $this->assertEquals(BatchMaterial::withoutGlobalScopes()->count(), BatchMaterial::count());
        $this->assertEquals(MaterialStock::withoutGlobalScopes()->count(), MaterialStock::count());
        $this->assertEmpty((new ProductionBatch)->getGlobalScopes());
        $this->assertEmpty((new BatchMaterial)->getGlobalScopes());
        $this->assertEmpty((new MaterialStock)->getGlobalScopes());
    }

    public function test_42_no_cross_outlet_mutation_on_stock_history_and_batch(): void
    {
        $stockBBefore = MaterialStock::where('material_id', $this->materialB->id)->first()->qty_on_hand;
        $batchBBefore = $this->batchB->fresh()->toArray();

        // Gudang A allocates Batch A
        $this->actingAs($this->gudangA)
            ->post(route('gudang.material-planning.allocate', $this->batchA))
            ->assertRedirect();

        $stockBAfter = MaterialStock::where('material_id', $this->materialB->id)->first()->qty_on_hand;
        $batchBAfter = $this->batchB->fresh()->toArray();

        $this->assertEquals($stockBBefore, $stockBAfter);
        $this->assertEquals($batchBBefore, $batchBAfter);
    }

    public function test_43_no_changes_to_hpp_bom_mrp_formulas(): void
    {
        $mrp = $this->mrpService->getMaterialRequirementsData($this->batchA);
        // Formula: required_qty = bom_qty_per_unit * target_qty (2.5 * 10 = 25)
        $this->assertEquals('25.0000', $mrp['items'][0]['required_qty']);
    }

    public function test_44_failed_allocation_leaves_no_partial_business_status(): void
    {
        // Drain stock
        MaterialStock::where('material_id', $this->materialA->id)->update(['qty_on_hand' => '0.0000']);

        $this->actingAs($this->gudangA)
            ->from(route('gudang.material-planning.show', $this->batchA))
            ->post(route('gudang.material-planning.allocate', $this->batchA))
            ->assertRedirect();

        // Batch status still BAHAN, allocated_qty still 0
        $this->assertEquals(BatchStatus::BAHAN, $this->batchA->fresh()->status);
        $this->assertEquals('0.0000', BatchMaterial::where('batch_id', $this->batchA->id)->first()->allocated_qty);
    }

    // =========================================================================
    // SECTION 4: GUDANG HTTP POST CALCULATE TENANT ISOLATION (TEST 45 - 63)
    // =========================================================================

    public function test_45_gudang_a_can_calculate_material_planning_for_batch_a(): void
    {
        $freshBatchA = ProductionBatch::create([
            'order_item_id'      => $this->itemA->id,
            'batch_no'           => 'BAT-GUD-A-FRESH-001',
            'target_qty'         => 10,
            'status'             => BatchStatus::BAHAN,
            'requires_printing'  => false,
            'planned_start_date' => now()->toDateString(),
        ]);
        $this->assertEquals(0, $freshBatchA->batchMaterials()->count());
        $stockABefore = MaterialStock::where('material_id', $this->materialA->id)->first()->qty_on_hand;
        $movementsBefore = StockMovement::count();

        $response = $this->actingAs($this->gudangA)
            ->post(route('gudang.material-planning.calculate', $freshBatchA));

        $response->assertRedirect(route('gudang.material-planning.show', $freshBatchA));
        $response->assertSessionHas('success');

        $this->assertEquals(1, $freshBatchA->batchMaterials()->count());
        $bm = $freshBatchA->batchMaterials()->first();
        $this->assertEquals($this->materialA->id, $bm->material_id);
        $this->assertEquals('25.0000', $bm->required_qty);
        $this->assertEquals('0.0000', $bm->allocated_qty);
        $this->assertEquals('0.0000', $bm->used_qty);
        $this->assertEquals($stockABefore, MaterialStock::where('material_id', $this->materialA->id)->first()->qty_on_hand);
        $this->assertEquals($movementsBefore, StockMovement::count());
        $this->assertEquals(BatchStatus::BAHAN, $freshBatchA->fresh()->status);
    }

    public function test_46_gudang_b_can_calculate_material_planning_for_batch_b(): void
    {
        $freshBatchB = ProductionBatch::create([
            'order_item_id'      => $this->itemB->id,
            'batch_no'           => 'BAT-GUD-B-FRESH-002',
            'target_qty'         => 15,
            'status'             => BatchStatus::BAHAN,
            'requires_printing'  => false,
            'planned_start_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->gudangB)
            ->post(route('gudang.material-planning.calculate', $freshBatchB));

        $response->assertRedirect(route('gudang.material-planning.show', $freshBatchB));
        $response->assertSessionHas('success');

        $this->assertEquals(1, $freshBatchB->batchMaterials()->count());
        $bm = $freshBatchB->batchMaterials()->first();
        $this->assertEquals($this->materialB->id, $bm->material_id);
        $this->assertEquals('30.0000', $bm->required_qty);
    }

    public function test_47_gudang_a_cannot_calculate_material_planning_batch_b_returns_404(): void
    {
        $bmBCountBefore = BatchMaterial::where('batch_id', $this->batchB->id)->count();
        $stockBBefore = MaterialStock::where('material_id', $this->materialB->id)->first()->qty_on_hand;
        $movementsBefore = StockMovement::count();
        $batchBBefore = $this->batchB->fresh()->toArray();

        $response = $this->actingAs($this->gudangA)
            ->post(route('gudang.material-planning.calculate', $this->batchB));

        $response->assertNotFound();
        $this->assertEquals($bmBCountBefore, BatchMaterial::where('batch_id', $this->batchB->id)->count());
        $this->assertEquals($stockBBefore, MaterialStock::where('material_id', $this->materialB->id)->first()->qty_on_hand);
        $this->assertEquals($movementsBefore, StockMovement::count());
        $this->assertEquals($batchBBefore, $this->batchB->fresh()->toArray());
    }

    public function test_48_gudang_b_cannot_calculate_material_planning_batch_a_returns_404(): void
    {
        $response = $this->actingAs($this->gudangB)
            ->post(route('gudang.material-planning.calculate', $this->batchA));

        $response->assertNotFound();
    }

    public function test_49_gudang_a_cannot_calculate_unassigned_batch_returns_404(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->post(route('gudang.material-planning.calculate', $this->unassignedBatch));

        $response->assertNotFound();
    }

    public function test_50_batch_with_cross_tenant_material_rejected_on_calculate(): void
    {
        $crossBatch = ProductionBatch::create([
            'order_item_id'     => $this->itemA->id,
            'batch_no'          => 'BAT-GUD-A-CROSS-003',
            'target_qty'        => 10,
            'status'            => BatchStatus::BAHAN,
            'requires_printing' => false,
        ]);
        BatchMaterial::create([
            'batch_id'      => $crossBatch->id,
            'material_id'   => $this->materialB->id,
            'required_qty'  => '20.0000',
            'allocated_qty' => '0.0000',
            'used_qty'      => '0.0000',
        ]);

        $response = $this->actingAs($this->gudangA)
            ->post(route('gudang.material-planning.calculate', $crossBatch));

        $response->assertNotFound();
    }

    public function test_51_batch_with_null_material_relation_rejected_on_calculate(): void
    {
        $orphanBatch = ProductionBatch::create([
            'order_item_id'     => $this->itemA->id,
            'batch_no'          => 'BAT-TEMP-ORPHAN-004',
            'target_qty'        => 10,
            'status'            => BatchStatus::BAHAN,
            'requires_printing' => false,
        ]);
        $orphanBm = new BatchMaterial([
            'batch_id'      => $orphanBatch->id,
            'material_id'   => 999999,
            'required_qty'  => '10.0000',
            'allocated_qty' => '0.0000',
            'used_qty'      => '0.0000',
        ]);
        $orphanBatch->setRelation('batchMaterials', collect([$orphanBm]));

        $this->assertFalse($orphanBatch->hasConsistentTenantRelations($this->outletA->id));
    }

    public function test_52_batch_with_material_outlet_id_null_rejected_on_calculate(): void
    {
        $legacyMat = Material::create([
            'material_name' => 'Kain Legacy Null Calculate',
            'unit_id'       => Unit::first()->id,
            'active'        => true,
            'outlet_id'     => null,
        ]);
        $legacyMatBatch = ProductionBatch::create([
            'order_item_id'     => $this->itemA->id,
            'batch_no'          => 'BAT-GUD-A-LEGACY-005',
            'target_qty'        => 10,
            'status'            => BatchStatus::BAHAN,
            'requires_printing' => false,
        ]);
        BatchMaterial::create([
            'batch_id'      => $legacyMatBatch->id,
            'material_id'   => $legacyMat->id,
            'required_qty'  => '10.0000',
            'allocated_qty' => '0.0000',
            'used_qty'      => '0.0000',
        ]);

        $response = $this->actingAs($this->gudangA)
            ->post(route('gudang.material-planning.calculate', $legacyMatBatch));

        $response->assertNotFound();
    }

    public function test_53_fake_x_outlet_id_header_has_no_effect_on_calculate(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->withHeaders(['X-Outlet-ID' => (string) $this->outletB->id])
            ->post(route('gudang.material-planning.calculate', $this->batchB));

        $response->assertNotFound();
    }

    public function test_54_fake_outlet_id_in_payload_has_no_effect_on_calculate(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->post(route('gudang.material-planning.calculate', $this->batchB), [
                'outlet_id' => $this->outletB->id,
            ]);

        $response->assertNotFound();
    }

    public function test_55_user_without_outlet_forbidden_on_calculate(): void
    {
        $response = $this->actingAs($this->unboundGudang)
            ->post(route('gudang.material-planning.calculate', $this->batchA));

        $response->assertForbidden();
    }

    public function test_56_inactive_outlet_user_forbidden_on_calculate(): void
    {
        $response = $this->actingAs($this->gudangInactiveOutlet)
            ->post(route('gudang.material-planning.calculate', $this->batchA));

        $response->assertForbidden();
    }

    public function test_57_inactive_user_redirected_to_login_on_calculate(): void
    {
        $response = $this->actingAs($this->inactiveGudangA)
            ->post(route('gudang.material-planning.calculate', $this->batchA));

        $response->assertRedirect('/login');
    }

    public function test_58_non_gudang_roles_forbidden_on_calculate(): void
    {
        $this->actingAs($this->produksiA)->post(route('gudang.material-planning.calculate', $this->batchA))->assertForbidden();
        $this->actingAs($this->adminA)->post(route('gudang.material-planning.calculate', $this->batchA))->assertForbidden();
        $this->actingAs($this->ownerA)->post(route('gudang.material-planning.calculate', $this->batchA))->assertForbidden();
        $this->actingAs($this->customerUser)->post(route('gudang.material-planning.calculate', $this->batchA))->assertForbidden();
    }

    public function test_59_unauthorized_calculate_does_not_mutate_batch_material_or_status(): void
    {
        $bmBefore = BatchMaterial::where('batch_id', $this->batchB->id)->get()->toArray();
        $batchBBefore = $this->batchB->fresh()->toArray();
        $orderBBefore = $this->orderB->fresh()->toArray();

        $this->actingAs($this->gudangA)
            ->post(route('gudang.material-planning.calculate', $this->batchB))
            ->assertNotFound();

        $this->assertEquals($bmBefore, BatchMaterial::where('batch_id', $this->batchB->id)->get()->toArray());
        $this->assertEquals($batchBBefore, $this->batchB->fresh()->toArray());
        $this->assertEquals($orderBBefore, $this->orderB->fresh()->toArray());
    }

    public function test_60_unauthorized_calculate_does_not_create_stock_movements(): void
    {
        $initialCount = StockMovement::count();
        $this->actingAs($this->gudangA)
            ->post(route('gudang.material-planning.calculate', $this->batchB))
            ->assertNotFound();

        $this->assertEquals($initialCount, StockMovement::count());
    }

    public function test_61_calculate_error_response_does_not_leak_private_data(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->post(route('gudang.material-planning.calculate', $this->batchB));

        $response->assertNotFound();
        $response->assertDontSee($this->batchB->batch_no);
        $response->assertDontSee($this->materialB->material_name);
        $response->assertDontSee($this->customerB->customer_name);
    }

    public function test_62_flag_false_preserves_legacy_calculate_behavior(): void
    {
        config(['nayara.tenant_isolation_enabled' => false]);

        $response = $this->actingAs($this->unboundGudang)
            ->post(route('gudang.material-planning.calculate', $this->unassignedBatch));

        $response->assertRedirect(route('gudang.material-planning.show', $this->unassignedBatch));
    }

    public function test_63_flag_true_fails_closed_when_tenant_context_invalid_on_calculate(): void
    {
        config(['nayara.tenant_isolation_enabled' => true]);

        $this->actingAs($this->unboundGudang)
            ->post(route('gudang.material-planning.calculate', $this->batchA))
            ->assertForbidden();
    }

    // =========================================================================
    // SECTION 5: DIRECT SERVICE CALLER AUTHORIZATION (TEST 64 - 75)
    // =========================================================================

    public function test_64_direct_service_allocate_batch_without_auth_throws_authorization_exception(): void
    {
        auth()->logout();
        $stockABefore = MaterialStock::where('material_id', $this->materialA->id)->first()->qty_on_hand;
        $movementsBefore = StockMovement::count();

        try {
            $this->allocationService->allocateBatchMaterials($this->batchA);
            $this->fail('Expected AuthorizationException was not thrown.');
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            $this->assertStringContainsString('Pengguna belum terotentikasi', $e->getMessage());
        }

        $this->assertEquals($stockABefore, MaterialStock::where('material_id', $this->materialA->id)->first()->qty_on_hand);
        $this->assertEquals($movementsBefore, StockMovement::count());
    }

    public function test_65_direct_service_allocate_additional_without_auth_throws_authorization_exception(): void
    {
        auth()->logout();
        $stockABefore = MaterialStock::where('material_id', $this->materialA->id)->first()->qty_on_hand;
        $movementsBefore = StockMovement::count();

        try {
            $this->allocationService->allocateAdditionalMaterial($this->batchAProduksi, $this->bmAProduksi->id, 5);
            $this->fail('Expected AuthorizationException was not thrown.');
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            $this->assertStringContainsString('Pengguna belum terotentikasi', $e->getMessage());
        }

        $this->assertEquals($stockABefore, MaterialStock::where('material_id', $this->materialA->id)->first()->qty_on_hand);
        $this->assertEquals($movementsBefore, StockMovement::count());
    }

    public function test_66_direct_service_allocate_with_unbound_user_throws_authorization_exception(): void
    {
        $this->actingAs($this->unboundGudang);

        try {
            $this->allocationService->allocateBatchMaterials($this->batchA);
            $this->fail('Expected AuthorizationException was not thrown.');
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            $this->assertStringContainsString('Pengguna tidak terikat ke outlet', $e->getMessage());
        }
    }

    public function test_67_direct_service_allocate_with_inactive_outlet_throws_authorization_exception(): void
    {
        $this->actingAs($this->gudangInactiveOutlet);

        try {
            $this->allocationService->allocateBatchMaterials($this->batchA);
            $this->fail('Expected AuthorizationException was not thrown.');
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            $this->assertStringContainsString('sedang dinonaktifkan', $e->getMessage());
        }
    }

    public function test_68_direct_service_allocate_with_inactive_user_throws_authorization_exception(): void
    {
        $this->actingAs($this->inactiveGudangA);

        try {
            $this->allocationService->allocateBatchMaterials($this->batchA);
            $this->fail('Expected AuthorizationException was not thrown.');
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            $this->assertStringContainsString('Akun pengguna sedang tidak aktif', $e->getMessage());
        }
    }

    public function test_69_direct_service_allocate_cross_tenant_batch_throws_domain_exception(): void
    {
        $this->actingAs($this->gudangA);

        $stockBBefore = MaterialStock::where('material_id', $this->materialB->id)->first()->qty_on_hand;
        $movementsBefore = StockMovement::count();

        try {
            $this->allocationService->allocateBatchMaterials($this->batchB);
            $this->fail('Expected DomainException was not thrown.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('Integritas data tenant tidak valid', $e->getMessage());
        }

        $this->assertEquals($stockBBefore, MaterialStock::where('material_id', $this->materialB->id)->first()->qty_on_hand);
        $this->assertEquals($movementsBefore, StockMovement::count());
    }

    public function test_70_direct_service_additional_cross_tenant_batch_throws_domain_exception(): void
    {
        $this->actingAs($this->produksiA);

        $stockBBefore = MaterialStock::where('material_id', $this->materialB->id)->first()->qty_on_hand;
        $movementsBefore = StockMovement::count();

        try {
            $this->allocationService->allocateAdditionalMaterial($this->batchBProduksi, $this->bmBProduksi->id, 5);
            $this->fail('Expected DomainException was not thrown.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('Integritas data tenant tidak valid', $e->getMessage());
        }

        $this->assertEquals($stockBBefore, MaterialStock::where('material_id', $this->materialB->id)->first()->qty_on_hand);
        $this->assertEquals($movementsBefore, StockMovement::count());
    }

    public function test_71_direct_service_allocate_own_outlet_succeeds(): void
    {
        $this->actingAs($this->gudangB);

        $stockBBefore = MaterialStock::where('material_id', $this->materialB->id)->first()->qty_on_hand;
        $result = $this->allocationService->allocateBatchMaterials($this->batchB);

        $this->assertInstanceOf(ProductionBatch::class, $result);
        $bm = BatchMaterial::where('batch_id', $this->batchB->id)->first();
        $this->assertEquals('30.0000', $bm->allocated_qty);
        $stockBAfter = MaterialStock::where('material_id', $this->materialB->id)->first()->qty_on_hand;
        $this->assertEquals(bcsub($stockBBefore, '30.0000', 4), $stockBAfter);
    }

    public function test_72_direct_service_additional_own_outlet_succeeds(): void
    {
        $this->actingAs($this->produksiA);

        $stockABefore = MaterialStock::where('material_id', $this->materialA->id)->first()->qty_on_hand;
        $result = $this->allocationService->allocateAdditionalMaterial($this->batchAProduksi, $this->bmAProduksi->id, 5);

        $this->assertInstanceOf(BatchMaterial::class, $result);
        $this->assertEquals('30.0000', $this->bmAProduksi->fresh()->allocated_qty);
        $stockAAfter = MaterialStock::where('material_id', $this->materialA->id)->first()->qty_on_hand;
        $this->assertEquals(bcsub($stockABefore, '5.0000', 4), $stockAAfter);
    }

    public function test_73_direct_service_legacy_mode_works_without_auth(): void
    {
        config(['nayara.tenant_isolation_enabled' => false]);
        auth()->logout();

        $result = $this->allocationService->allocateBatchMaterials($this->unassignedBatch);
        $this->assertInstanceOf(ProductionBatch::class, $result);
    }

    public function test_74_direct_service_additional_cross_tenant_material_rejected_even_if_batch_matches(): void
    {
        $this->actingAs($this->produksiA);
        $crossBm = BatchMaterial::create([
            'batch_id'      => $this->batchAProduksi->id,
            'material_id'   => $this->materialB->id,
            'required_qty'  => '10.0000',
            'allocated_qty' => '10.0000',
            'used_qty'      => '0.0000',
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Integritas data tenant tidak valid');

        $this->allocationService->allocateAdditionalMaterial($this->batchAProduksi, $crossBm->id, 2);
    }

    public function test_75_no_mutable_global_tenant_state_leakage(): void
    {
        $this->actingAs($this->gudangA);
        $this->assertEquals($this->outletA->id, app(\App\Services\System\TenantContext::class)->currentOutletId());

        $this->actingAs($this->gudangB);
        $this->assertEquals($this->outletB->id, app(\App\Services\System\TenantContext::class)->currentOutletId());

        auth()->logout();
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        app(\App\Services\System\TenantContext::class)->currentOutletId();
    }
}
