<?php

namespace Tests\Feature\System;

use App\Enums\BatchStatus;
use App\Enums\InvoiceStatus;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionResultQcTenantReadIsolationTest extends TestCase
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
    protected User $inactiveProduksiA;
    protected User $unboundProduksi;
    protected User $produksiInactiveOutlet;

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

    // Batches in PRODUKSI status (ready for ProductionResult create)
    protected ProductionBatch $batchAProduksi;
    protected ProductionBatch $batchBProduksi;
    protected ProductionBatch $unassignedBatchProduksi;

    // Batches in QC status (ready for QC create)
    protected ProductionBatch $batchAQc;
    protected ProductionBatch $batchBQc;
    protected ProductionResult $prodResultA;
    protected ProductionResult $prodResultB;

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
        $this->inactiveProduksiA = $this->createTestUser($this->outletA, UserRole::PRODUKSI, UserStatus::INACTIVE, 'prod-inact');
        $this->unboundProduksi = $this->createTestUser(null, UserRole::PRODUKSI, UserStatus::ACTIVE, 'prod-unbound');
        $this->produksiInactiveOutlet = $this->createTestUser($this->outletInactive, UserRole::PRODUKSI, UserStatus::ACTIVE, 'prod-inact-out');

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
            'order_no'    => 'SO-PROD-A-001',
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
            'order_no'    => 'SO-PROD-B-002',
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

        // 7. Batches in PRODUKSI (eligible for production-result.create)
        $this->batchAProduksi = ProductionBatch::create([
            'order_item_id'                                 => $this->itemA->id,
            'batch_no'                                      => 'BAT-A-PROD-001',
            'target_qty'                                    => 10,
            'status'                                        => BatchStatus::PRODUKSI,
            'requires_printing'                             => false,
            'direct_labor_cost_per_unit_snapshot'           => '5000.00',
            'packaging_process_cost_per_good_unit_snapshot' => '2000.00',
            'other_direct_cost_per_unit_snapshot'           => '1000.00',
            'production_employee_id'                        => $this->produksiA->id,
            'planned_start_date'                            => now()->toDateString(),
        ]);
        BatchMaterial::create([
            'batch_id'      => $this->batchAProduksi->id,
            'material_id'   => $this->materialA->id,
            'required_qty'  => '25.0000',
            'allocated_qty' => '25.0000',
            'used_qty'      => '0.0000',
        ]);

        $this->batchBProduksi = ProductionBatch::create([
            'order_item_id'                                 => $this->itemB->id,
            'batch_no'                                      => 'BAT-B-PROD-002',
            'target_qty'                                    => 15,
            'status'                                        => BatchStatus::PRODUKSI,
            'requires_printing'                             => true,
            'printing_cost_per_unit_snapshot'               => '3000.00',
            'direct_labor_cost_per_unit_snapshot'           => '4000.00',
            'packaging_process_cost_per_good_unit_snapshot' => '1500.00',
            'other_direct_cost_per_unit_snapshot'           => '800.00',
            'production_employee_id'                        => $this->produksiB->id,
            'planned_start_date'                            => now()->toDateString(),
        ]);
        BatchMaterial::create([
            'batch_id'      => $this->batchBProduksi->id,
            'material_id'   => $this->materialB->id,
            'required_qty'  => '30.0000',
            'allocated_qty' => '30.0000',
            'used_qty'      => '0.0000',
        ]);

        $this->unassignedBatchProduksi = ProductionBatch::create([
            'order_item_id'                                 => $this->unassignedItem->id,
            'batch_no'                                      => 'BAT-NULL-PROD-003',
            'target_qty'                                    => 5,
            'status'                                        => BatchStatus::PRODUKSI,
            'requires_printing'                             => false,
            'direct_labor_cost_per_unit_snapshot'           => '5000.00',
            'packaging_process_cost_per_good_unit_snapshot' => '2000.00',
            'other_direct_cost_per_unit_snapshot'           => '1000.00',
            'production_employee_id'                        => $this->produksiA->id,
            'planned_start_date'                            => now()->toDateString(),
        ]);
        BatchMaterial::create([
            'batch_id'      => $this->unassignedBatchProduksi->id,
            'material_id'   => $this->materialA->id,
            'required_qty'  => '12.5000',
            'allocated_qty' => '12.5000',
            'used_qty'      => '0.0000',
        ]);

        // 8. Batches in QC (eligible for qc.create)
        $this->batchAQc = ProductionBatch::create([
            'order_item_id'                                 => $this->itemA->id,
            'batch_no'                                      => 'BAT-A-QC-001',
            'target_qty'                                    => 10,
            'status'                                        => BatchStatus::QC,
            'requires_printing'                             => false,
            'direct_labor_cost_per_unit_snapshot'           => '5000.00',
            'packaging_process_cost_per_good_unit_snapshot' => '2000.00',
            'other_direct_cost_per_unit_snapshot'           => '1000.00',
            'production_employee_id'                        => $this->produksiA->id,
            'planned_start_date'                            => now()->toDateString(),
        ]);
        BatchMaterial::create([
            'batch_id'      => $this->batchAQc->id,
            'material_id'   => $this->materialA->id,
            'required_qty'  => '25.0000',
            'allocated_qty' => '25.0000',
            'used_qty'      => '25.0000',
        ]);
        $this->prodResultA = ProductionResult::create([
            'batch_id'    => $this->batchAQc->id,
            'actual_qty'  => 10,
            'result_date' => now()->toDateString(),
            'notes'       => 'Hasil produksi fisik A',
            'recorded_by' => $this->produksiA->id,
        ]);

        $this->batchBQc = ProductionBatch::create([
            'order_item_id'                                 => $this->itemB->id,
            'batch_no'                                      => 'BAT-B-QC-002',
            'target_qty'                                    => 15,
            'status'                                        => BatchStatus::QC,
            'requires_printing'                             => true,
            'printing_cost_per_unit_snapshot'               => '3000.00',
            'direct_labor_cost_per_unit_snapshot'           => '4000.00',
            'packaging_process_cost_per_good_unit_snapshot' => '1500.00',
            'other_direct_cost_per_unit_snapshot'           => '800.00',
            'production_employee_id'                        => $this->produksiB->id,
            'planned_start_date'                            => now()->toDateString(),
        ]);
        BatchMaterial::create([
            'batch_id'      => $this->batchBQc->id,
            'material_id'   => $this->materialB->id,
            'required_qty'  => '30.0000',
            'allocated_qty' => '30.0000',
            'used_qty'      => '30.0000',
        ]);
        $this->prodResultB = ProductionResult::create([
            'batch_id'    => $this->batchBQc->id,
            'actual_qty'  => 15,
            'result_date' => now()->toDateString(),
            'notes'       => 'Hasil produksi fisik B',
            'recorded_by' => $this->produksiB->id,
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
    // 36 TEST SCENARIOS (Section P)
    // =========================================================================

    /**
     * 01. User Outlet A dapat membuka form hasil produksi batch A yang eligible.
     */
    public function test_01_user_outlet_a_can_open_production_result_form_for_eligible_batch_a(): void
    {
        $response = $this->actingAs($this->produksiA)
            ->get(route('produksi.batches.production-result.create', $this->batchAProduksi));

        $response->assertOk();
        $response->assertViewIs('produksi.batches.result.create');
        $response->assertSee($this->batchAProduksi->batch_no);
        $response->assertSee($this->productA->product_name);
    }

    /**
     * 02. User Outlet B dapat membuka form hasil produksi batch B yang eligible.
     */
    public function test_02_user_outlet_b_can_open_production_result_form_for_eligible_batch_b(): void
    {
        $response = $this->actingAs($this->produksiB)
            ->get(route('produksi.batches.production-result.create', $this->batchBProduksi));

        $response->assertOk();
        $response->assertViewIs('produksi.batches.result.create');
        $response->assertSee($this->batchBProduksi->batch_no);
        $response->assertSee($this->productB->product_name);
    }

    /**
     * 03. User Outlet A tidak bisa membuka form hasil produksi batch B.
     */
    public function test_03_user_outlet_a_cannot_open_production_result_form_for_batch_b(): void
    {
        $response = $this->actingAs($this->produksiA)
            ->get(route('produksi.batches.production-result.create', $this->batchBProduksi));

        $response->assertStatus(404);
    }

    /**
     * 04. Foreign batch pada form hasil produksi menghasilkan 404.
     */
    public function test_04_foreign_batch_on_production_result_form_returns_404(): void
    {
        $response = $this->actingAs($this->produksiB)
            ->get(route('produksi.batches.production-result.create', $this->batchAProduksi));

        $response->assertStatus(404);
    }

    /**
     * 05. User Outlet A dapat membuka form QC batch A yang eligible.
     */
    public function test_05_user_outlet_a_can_open_qc_form_for_eligible_batch_a(): void
    {
        $response = $this->actingAs($this->produksiA)
            ->get(route('produksi.batches.qc.create', $this->batchAQc));

        $response->assertOk();
        $response->assertViewIs('produksi.batches.qc.create');
        $response->assertSee($this->batchAQc->batch_no);
        $response->assertSee($this->productA->product_name);
        $response->assertSee('10 pcs');
    }

    /**
     * 06. User Outlet B dapat membuka form QC batch B yang eligible.
     */
    public function test_06_user_outlet_b_can_open_qc_form_for_eligible_batch_b(): void
    {
        $response = $this->actingAs($this->produksiB)
            ->get(route('produksi.batches.qc.create', $this->batchBQc));

        $response->assertOk();
        $response->assertViewIs('produksi.batches.qc.create');
        $response->assertSee($this->batchBQc->batch_no);
        $response->assertSee($this->productB->product_name);
        $response->assertSee('15 pcs');
    }

    /**
     * 07. User Outlet A tidak bisa membuka form QC batch B.
     */
    public function test_07_user_outlet_a_cannot_open_qc_form_for_batch_b(): void
    {
        $response = $this->actingAs($this->produksiA)
            ->get(route('produksi.batches.qc.create', $this->batchBQc));

        $response->assertStatus(404);
    }

    /**
     * 08. Foreign batch pada form QC menghasilkan 404.
     */
    public function test_08_foreign_batch_on_qc_form_returns_404(): void
    {
        $response = $this->actingAs($this->produksiB)
            ->get(route('produksi.batches.qc.create', $this->batchAQc));

        $response->assertStatus(404);
    }

    /**
     * 09. Batch dengan Order outlet_id NULL ditolak.
     */
    public function test_09_batch_with_null_order_outlet_id_is_rejected(): void
    {
        // Production result form
        $this->actingAs($this->produksiA)
            ->get(route('produksi.batches.production-result.create', $this->unassignedBatchProduksi))
            ->assertStatus(404);

        // QC form
        $unassignedQcBatch = ProductionBatch::create([
            'order_item_id'     => $this->unassignedItem->id,
            'batch_no'          => 'BAT-NULL-QC-004',
            'target_qty'        => 5,
            'status'            => BatchStatus::QC,
            'requires_printing' => false,
        ]);
        ProductionResult::create([
            'batch_id'    => $unassignedQcBatch->id,
            'actual_qty'  => 5,
            'result_date' => now()->toDateString(),
            'recorded_by' => $this->produksiA->id,
        ]);

        $this->actingAs($this->produksiA)
            ->get(route('produksi.batches.qc.create', $unassignedQcBatch))
            ->assertStatus(404);
    }

    /**
     * 10. Batch dengan parent OrderItem invalid ditolak.
     */
    public function test_10_batch_with_invalid_parent_order_item_is_rejected(): void
    {
        // 1. In-memory model with non-existent order_item_id
        $orphanBatch = new ProductionBatch([
            'order_item_id'          => 999999,
            'batch_no'               => 'BAT-ORPHAN-001',
            'target_qty'             => 5,
            'status'                 => BatchStatus::PRODUKSI,
            'requires_printing'      => false,
            'production_employee_id' => $this->produksiA->id,
        ]);

        $this->assertFalse($orphanBatch->belongsToOutlet($this->outletA->id));
        $this->assertFalse($orphanBatch->hasConsistentTenantRelations($this->outletA->id));

        // 2. Persisted batch with foreign OrderItem not belonging to user's outlet
        $foreignItemBatch = ProductionBatch::create([
            'order_item_id'          => $this->itemB->id, // belongs to Order B
            'batch_no'               => 'BAT-FOREIGN-ITEM-001',
            'target_qty'             => 5,
            'status'                 => BatchStatus::PRODUKSI,
            'requires_printing'      => false,
            'production_employee_id' => $this->produksiA->id,
        ]);

        $this->actingAs($this->produksiA)
            ->get(route('produksi.batches.production-result.create', $foreignItemBatch))
            ->assertStatus(404);
    }

    /**
     * 11. Batch dengan Variant lintas-outlet ditolak.
     */
    public function test_11_batch_with_cross_tenant_variant_is_rejected(): void
    {
        // Item under Order A, but variant belongs to Outlet B
        $mismatchedItem = OrderItem::create([
            'order_id'           => $this->orderA->id,
            'variant_id'         => $this->variantB->id,
            'qty_ordered'        => 5,
            'unit_selling_price' => 85000,
        ]);
        $mismatchedBatch = ProductionBatch::create([
            'order_item_id'          => $mismatchedItem->id,
            'batch_no'               => 'BAT-MISMATCH-VAR-001',
            'target_qty'             => 5,
            'status'                 => BatchStatus::PRODUKSI,
            'requires_printing'      => false,
            'production_employee_id' => $this->produksiA->id,
        ]);
        BatchMaterial::create([
            'batch_id'      => $mismatchedBatch->id,
            'material_id'   => $this->materialA->id,
            'required_qty'  => '10.0000',
            'allocated_qty' => '10.0000',
            'used_qty'      => '0.0000',
        ]);

        $this->actingAs($this->produksiA)
            ->get(route('produksi.batches.production-result.create', $mismatchedBatch))
            ->assertStatus(404);
    }

    /**
     * 12. Batch dengan Product lintas-outlet ditolak.
     */
    public function test_12_batch_with_cross_tenant_product_is_rejected(): void
    {
        $corruptedProduct = Product::create([
            'product_name' => 'Foreign Product B',
            'active'       => true,
            'outlet_id'    => $this->outletB->id,
        ]);
        $corruptedVariant = ProductVariant::create([
            'product_id'                           => $corruptedProduct->id,
            'variant_name'                         => 'Foreign Product Variant',
            'sku'                                  => 'SKU-FOREIGN-P-01',
            'base_selling_price'                   => 100000,
            'requires_printing'                    => false,
            'direct_labor_cost_per_unit'           => '5000.00',
            'packaging_process_cost_per_good_unit' => '2000.00',
            'other_direct_cost_per_unit'           => '1000.00',
            'active'                               => true,
            'outlet_id'                            => $this->outletA->id,
        ]);
        $item = OrderItem::create([
            'order_id'           => $this->orderA->id,
            'variant_id'         => $corruptedVariant->id,
            'qty_ordered'        => 5,
            'unit_selling_price' => 100000,
        ]);
        $batch = ProductionBatch::create([
            'order_item_id'          => $item->id,
            'batch_no'               => 'BAT-CORRUPT-P-001',
            'target_qty'             => 5,
            'status'                 => BatchStatus::PRODUKSI,
            'requires_printing'      => false,
            'production_employee_id' => $this->produksiA->id,
        ]);
        BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->materialA->id,
            'required_qty'  => '10.0000',
            'allocated_qty' => '10.0000',
            'used_qty'      => '0.0000',
        ]);

        $this->actingAs($this->produksiA)
            ->get(route('produksi.batches.production-result.create', $batch))
            ->assertStatus(404);
    }

    /**
     * 13. Batch dengan Material lintas-outlet ditolak ketika relasi terkait ada.
     */
    public function test_13_batch_with_cross_tenant_material_is_rejected(): void
    {
        // Batch under Outlet A, but has BatchMaterial referencing Material B
        $batchWithMaterialB = ProductionBatch::create([
            'order_item_id'          => $this->itemA->id,
            'batch_no'               => 'BAT-CROSS-MAT-001',
            'target_qty'             => 5,
            'status'                 => BatchStatus::PRODUKSI,
            'requires_printing'      => false,
            'production_employee_id' => $this->produksiA->id,
        ]);
        BatchMaterial::create([
            'batch_id'      => $batchWithMaterialB->id,
            'material_id'   => $this->materialB->id, // Outlet B's material
            'required_qty'  => '10.0000',
            'allocated_qty' => '10.0000',
            'used_qty'      => '0.0000',
        ]);

        $this->actingAs($this->produksiA)
            ->get(route('produksi.batches.production-result.create', $batchWithMaterialB))
            ->assertStatus(404);
    }

    /**
     * 14. ProductionResult dari batch lain tidak boleh terbaca atau terpakai.
     */
    public function test_14_production_result_from_other_batch_cannot_be_read_or_leaked(): void
    {
        // When Produksi A views Batch A QC form, it only sees Batch A's result
        $response = $this->actingAs($this->produksiA)
            ->get(route('produksi.batches.qc.create', $this->batchAQc));

        $response->assertOk();
        $response->assertSee('10 pcs');
        $response->assertDontSee('15 pcs');
    }

    /**
     * 15. QcResult dari batch lain tidak boleh terbaca.
     */
    public function test_15_qc_result_from_other_batch_cannot_be_read(): void
    {
        // Create QC result for Batch B
        QcResult::create([
            'production_result_id' => $this->prodResultB->id,
            'good_qty'             => 14,
            'reject_qty'           => 1,
            'qc_date'              => now()->toDateString(),
            'notes'                => 'Secret QC Notes Outlet B',
            'qc_by'                => $this->produksiB->id,
        ]);

        $response = $this->actingAs($this->produksiA)
            ->get(route('produksi.batches.qc.create', $this->batchAQc));

        $response->assertOk();
        $response->assertDontSee('Secret QC Notes Outlet B');
    }

    /**
     * 16. Request outlet_id palsu tidak mengubah tenant.
     */
    public function test_16_spoofed_outlet_id_param_does_not_override_tenant(): void
    {
        $response = $this->actingAs($this->produksiA)
            ->get(route('produksi.batches.production-result.create', [
                'batch'     => $this->batchBProduksi->id,
                'outlet_id' => $this->outletB->id,
            ]));

        $response->assertStatus(404);
    }

    /**
     * 17. Header X-Outlet-ID palsu tidak mengubah tenant.
     */
    public function test_17_spoofed_x_outlet_id_header_does_not_override_tenant(): void
    {
        $response = $this->actingAs($this->produksiA)
            ->withHeaders(['X-Outlet-ID' => (string) $this->outletB->id])
            ->get(route('produksi.batches.production-result.create', $this->batchBProduksi));

        $response->assertStatus(404);
    }

    /**
     * 18. User tanpa outlet ditolak saat flag TRUE.
     */
    public function test_18_user_without_outlet_is_rejected_when_flag_true(): void
    {
        $this->actingAs($this->unboundProduksi)
            ->get(route('produksi.batches.production-result.create', $this->batchAProduksi))
            ->assertStatus(403);

        $this->actingAs($this->unboundProduksi)
            ->get(route('produksi.batches.qc.create', $this->batchAQc))
            ->assertStatus(403);
    }

    /**
     * 19. Outlet INACTIVE ditolak.
     */
    public function test_19_inactive_outlet_user_is_rejected(): void
    {
        $this->actingAs($this->produksiInactiveOutlet)
            ->get(route('produksi.batches.production-result.create', $this->batchAProduksi))
            ->assertStatus(403);

        $this->actingAs($this->produksiInactiveOutlet)
            ->get(route('produksi.batches.qc.create', $this->batchAQc))
            ->assertStatus(403);
    }

    /**
     * 20. User INACTIVE ditolak.
     */
    public function test_20_inactive_user_is_rejected(): void
    {
        $response = $this->actingAs($this->inactiveProduksiA)
            ->get(route('produksi.batches.production-result.create', $this->batchAProduksi));

        $response->assertRedirect('/login');
    }

    /**
     * 21. CUSTOMER tidak dapat membuka form internal hasil produksi.
     */
    public function test_21_customer_cannot_open_internal_production_result_form(): void
    {
        $this->actingAs($this->customerUser)
            ->get(route('produksi.batches.production-result.create', $this->batchAProduksi))
            ->assertStatus(403);
    }

    /**
     * 22. CUSTOMER tidak dapat membuka form QC.
     */
    public function test_22_customer_cannot_open_qc_form(): void
    {
        $this->actingAs($this->customerUser)
            ->get(route('produksi.batches.qc.create', $this->batchAQc))
            ->assertStatus(403);
    }

    /**
     * 23. RoleMiddleware tetap membatasi akses sesuai role.
     */
    public function test_23_role_middleware_strictly_restricts_access(): void
    {
        // Admin cannot access Produksi route
        $this->actingAs($this->adminA)
            ->get(route('produksi.batches.production-result.create', $this->batchAProduksi))
            ->assertStatus(403);

        // Gudang cannot access Produksi route
        $this->actingAs($this->gudangA)
            ->get(route('produksi.batches.production-result.create', $this->batchAProduksi))
            ->assertStatus(403);
    }

    /**
     * 24. OWNER tidak memperoleh bypass otomatis.
     */
    public function test_24_owner_does_not_get_bypass_for_production_routes(): void
    {
        $this->actingAs($this->ownerA)
            ->get(route('produksi.batches.production-result.create', $this->batchAProduksi))
            ->assertStatus(403);

        $this->actingAs($this->ownerA)
            ->get(route('produksi.batches.qc.create', $this->batchAQc))
            ->assertStatus(403);
    }

    /**
     * 25. PO approval tetap ADMIN.
     */
    public function test_25_po_approval_remains_admin_exclusive(): void
    {
        $supplier = Supplier::create([
            'supplier_name' => 'Supplier Kain Utama',
            'contact_person'=> 'Bapak Ahmad',
            'phone'         => '081299991111',
            'outlet_id'     => $this->outletA->id,
        ]);
        $purchase = Purchase::create([
            'purchase_no'    => 'PO-TEST-002',
            'supplier_id'    => $supplier->id,
            'purchase_date'  => now()->toDateString(),
            'total_amount'   => 1500000,
            'status'         => PurchaseStatus::DRAFT,
            'outlet_id'      => $this->outletA->id,
        ]);

        $this->actingAs($this->produksiA)
            ->post(route('admin.purchases.approvals.approve', $purchase))
            ->assertStatus(403);

        $this->actingAs($this->ownerA)
            ->post(route('admin.purchases.approvals.approve', $purchase))
            ->assertStatus(403);
    }

    /**
     * 26. Mode flag FALSE mempertahankan kompatibilitas legacy.
     */
    public function test_26_tenant_isolation_false_preserves_legacy_workflow(): void
    {
        config(['nayara.tenant_isolation_enabled' => false]);

        $legacyProduksi = $this->createTestUser(null, UserRole::PRODUKSI, UserStatus::ACTIVE, 'leg-prod');

        $response = $this->actingAs($legacyProduksi)
            ->get(route('produksi.batches.production-result.create', $this->unassignedBatchProduksi));

        $response->assertOk();
        $response->assertSee($this->unassignedBatchProduksi->batch_no);
    }

    /**
     * 27. Mode flag TRUE fail-closed jika tenant tidak valid.
     */
    public function test_27_tenant_isolation_true_fails_closed_when_tenant_invalid(): void
    {
        config(['nayara.tenant_isolation_enabled' => true]);

        $this->actingAs($this->unboundProduksi)
            ->get(route('produksi.batches.production-result.create', $this->batchAProduksi))
            ->assertStatus(403);
    }

    /**
     * 28. Tidak ada perubahan ProductionBatch status akibat request GET.
     */
    public function test_28_no_production_batch_status_mutation_on_get(): void
    {
        $statusBefore = $this->batchAProduksi->status;

        $this->actingAs($this->produksiA)
            ->get(route('produksi.batches.production-result.create', $this->batchAProduksi));

        $this->assertEquals($statusBefore, $this->batchAProduksi->fresh()->status);
    }

    /**
     * 29. Tidak ada perubahan ProductionResult akibat GET.
     */
    public function test_29_no_production_result_mutation_on_get(): void
    {
        $countBefore = ProductionResult::count();
        $dataBefore = $this->prodResultA->fresh()->toArray();

        $this->actingAs($this->produksiA)
            ->get(route('produksi.batches.qc.create', $this->batchAQc));

        $this->assertEquals($countBefore, ProductionResult::count());
        $this->assertEquals($dataBefore, $this->prodResultA->fresh()->toArray());
    }

    /**
     * 30. Tidak ada perubahan QcResult akibat GET.
     */
    public function test_30_no_qc_result_mutation_on_get(): void
    {
        $countBefore = QcResult::count();

        $this->actingAs($this->produksiA)
            ->get(route('produksi.batches.qc.create', $this->batchAQc));

        $this->assertEquals($countBefore, QcResult::count());
    }

    /**
     * 31. Tidak ada StockMovement baru akibat GET.
     */
    public function test_31_no_stock_movement_created_on_get(): void
    {
        $countBefore = StockMovement::count();

        $this->actingAs($this->produksiA)
            ->get(route('produksi.batches.production-result.create', $this->batchAProduksi));

        $this->actingAs($this->produksiA)
            ->get(route('produksi.batches.qc.create', $this->batchAQc));

        $this->assertEquals($countBefore, StockMovement::count());
    }

    /**
     * 32. Tidak ada data outlet lain termodifikasi.
     */
    public function test_32_no_cross_tenant_data_modified_by_unauthorized_get(): void
    {
        $batchBBefore = $this->batchBProduksi->fresh()->toArray();
        $prodResultBBefore = $this->prodResultB->fresh()->toArray();

        // Produksi A attempts unauthorized access to Batch B forms
        $this->actingAs($this->produksiA)
            ->get(route('produksi.batches.production-result.create', $this->batchBProduksi));
        $this->actingAs($this->produksiA)
            ->get(route('produksi.batches.qc.create', $this->batchBQc));

        $this->assertEquals($batchBBefore, $this->batchBProduksi->fresh()->toArray());
        $this->assertEquals($prodResultBBefore, $this->prodResultB->fresh()->toArray());
    }

    /**
     * 33. Data privat tenant lain tidak muncul di HTML response.
     */
    public function test_33_no_cross_tenant_private_data_leakage_in_html(): void
    {
        $response = $this->actingAs($this->produksiA)
            ->get(route('produksi.batches.production-result.create', $this->batchAProduksi));

        $response->assertOk();
        $response->assertDontSee($this->customerB->customer_name);
        $response->assertDontSee($this->orderB->order_no);
        $response->assertDontSee($this->productB->product_name);
        $response->assertDontSee($this->batchBProduksi->batch_no);
    }

    /**
     * 34. Existing ProductionBatch READ/WRITE isolation tests tetap lulus.
     */
    public function test_34_existing_batch_read_write_isolation_invariants_hold(): void
    {
        $this->assertTrue($this->batchAProduksi->belongsToOutlet($this->outletA->id));
        $this->assertFalse($this->batchAProduksi->belongsToOutlet($this->outletB->id));
        $this->assertTrue($this->batchAQc->hasConsistentTenantRelations($this->outletA->id));
        $this->assertFalse($this->batchAQc->hasConsistentTenantRelations($this->outletB->id));
    }

    /**
     * 35. Tidak ada Global Tenant Scope.
     */
    public function test_35_no_stealth_global_tenant_scope_active(): void
    {
        $this->assertEquals(
            ProductionBatch::withoutGlobalScopes()->count(),
            ProductionBatch::count()
        );
        $this->assertEquals(
            ProductionResult::withoutGlobalScopes()->count(),
            ProductionResult::count()
        );
        $this->assertEquals(
            QcResult::withoutGlobalScopes()->count(),
            QcResult::count()
        );
    }

    /**
     * 36. Tidak ada perubahan perhitungan bisnis existing.
     */
    public function test_36_existing_business_calculation_and_gates_preserved(): void
    {
        // Batch not in PRODUKSI status redirects with business error
        $batchBahan = ProductionBatch::create([
            'order_item_id'          => $this->itemA->id,
            'batch_no'               => 'BAT-A-BAHAN-005',
            'target_qty'             => 5,
            'status'                 => BatchStatus::BAHAN,
            'requires_printing'      => false,
            'production_employee_id' => $this->produksiA->id,
        ]);
        BatchMaterial::create([
            'batch_id'      => $batchBahan->id,
            'material_id'   => $this->materialA->id,
            'required_qty'  => '10.0000',
            'allocated_qty' => '10.0000',
            'used_qty'      => '0.0000',
        ]);

        $response = $this->actingAs($this->produksiA)
            ->get(route('produksi.batches.production-result.create', $batchBahan));

        $response->assertRedirect(route('produksi.batches.show', $batchBahan));
        $response->assertSessionHas('error');
    }
}
