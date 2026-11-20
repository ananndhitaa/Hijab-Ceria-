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

class ProductionResultQcTenantWriteIsolationTest extends TestCase
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

    // Batches in PRODUKSI status (ready for ProductionResult store / material request)
    protected ProductionBatch $batchAProduksi;
    protected ProductionBatch $batchBProduksi;
    protected ProductionBatch $unassignedBatchProduksi;
    protected BatchMaterial $bmA;
    protected BatchMaterial $bmB;
    protected BatchMaterial $bmUnassigned;

    // Batches in QC status (ready for QC store)
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

        // 7. Batches in PRODUKSI (eligible for production-result store & material request)
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
        $this->bmA = BatchMaterial::create([
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
        $this->bmB = BatchMaterial::create([
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
        $this->bmUnassigned = BatchMaterial::create([
            'batch_id'      => $this->unassignedBatchProduksi->id,
            'material_id'   => $this->materialA->id,
            'required_qty'  => '12.5000',
            'allocated_qty' => '12.5000',
            'used_qty'      => '0.0000',
        ]);

        // 8. Batches in QC (eligible for qc store)
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

    private function createTestOutlet(string $code, string $name, string $status): Outlet
    {
        return Outlet::create([
            'outlet_code' => $code,
            'outlet_name' => $name,
            'status'      => $status,
            'address'     => 'Jl. Konveksi No. 1',
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
    // SECTION 1: PRODUCTION RESULT STORE (TEST 01 - 16)
    // =========================================================================

    public function test_01_user_outlet_a_can_store_production_result_for_eligible_batch_a(): void
    {
        $response = $this->actingAs($this->produksiA)->post(route('produksi.batches.production-result.store', $this->batchAProduksi), [
            'actual_qty'     => 10,
            'result_date'    => now()->toDateString(),
            'notes'          => 'Catatan hasil produksi A',
            'material_usage' => [
                $this->bmA->id => '25.0000',
            ],
        ]);

        $response->assertRedirect(route('produksi.batches.show', $this->batchAProduksi));
        $response->assertSessionHas('success');

        $this->batchAProduksi->refresh();
        $this->assertEquals(BatchStatus::QC, $this->batchAProduksi->status);
        $this->assertDatabaseHas('production_results', [
            'batch_id'   => $this->batchAProduksi->id,
            'actual_qty' => '10.0000',
        ]);
    }

    public function test_02_user_outlet_b_can_store_production_result_for_eligible_batch_b(): void
    {
        $response = $this->actingAs($this->produksiB)->post(route('produksi.batches.production-result.store', $this->batchBProduksi), [
            'actual_qty'     => 15,
            'result_date'    => now()->toDateString(),
            'notes'          => 'Catatan hasil produksi B',
            'material_usage' => [
                $this->bmB->id => '30.0000',
            ],
        ]);

        $response->assertRedirect(route('produksi.batches.show', $this->batchBProduksi));
        $response->assertSessionHas('success');

        $this->batchBProduksi->refresh();
        $this->assertEquals(BatchStatus::QC, $this->batchBProduksi->status);
        $this->assertDatabaseHas('production_results', [
            'batch_id'   => $this->batchBProduksi->id,
            'actual_qty' => '15.0000',
        ]);
    }

    public function test_03_user_a_cannot_store_production_result_for_batch_b(): void
    {
        $response = $this->actingAs($this->produksiA)->post(route('produksi.batches.production-result.store', $this->batchBProduksi), [
            'actual_qty'     => 15,
            'result_date'    => now()->toDateString(),
            'material_usage' => [
                $this->bmB->id => '30.0000',
            ],
        ]);

        $response->assertNotFound();
        $this->assertDatabaseMissing('production_results', [
            'batch_id' => $this->batchBProduksi->id,
        ]);
    }

    public function test_04_foreign_batch_on_store_returns_404(): void
    {
        $response = $this->actingAs($this->produksiB)->post(route('produksi.batches.production-result.store', $this->batchAProduksi), [
            'actual_qty'     => 10,
            'result_date'    => now()->toDateString(),
            'material_usage' => [
                $this->bmA->id => '25.0000',
            ],
        ]);

        $response->assertNotFound();
    }

    public function test_05_batch_unassigned_rejected_when_flag_true(): void
    {
        $response = $this->actingAs($this->produksiA)->post(route('produksi.batches.production-result.store', $this->unassignedBatchProduksi), [
            'actual_qty'     => 5,
            'result_date'    => now()->toDateString(),
            'material_usage' => [
                $this->bmUnassigned->id => '12.5000',
            ],
        ]);

        $response->assertNotFound();
    }

    public function test_06_batch_with_invalid_parent_order_item_is_rejected(): void
    {
        // Batch belonging to order item of another outlet
        $mismatchedBatch = ProductionBatch::create([
            'order_item_id'          => $this->itemB->id, // Belongs to Order B
            'batch_no'               => 'BAT-MISMATCH-001',
            'target_qty'             => 5,
            'status'                 => BatchStatus::PRODUKSI,
            'requires_printing'      => false,
            'production_employee_id' => $this->produksiA->id,
            'planned_start_date'     => now()->toDateString(),
        ]);

        $this->assertFalse($mismatchedBatch->hasConsistentTenantRelations($this->outletA->id));

        $response = $this->actingAs($this->produksiA)->post(route('produksi.batches.production-result.store', $mismatchedBatch), [
            'actual_qty'  => 5,
            'result_date' => now()->toDateString(),
        ]);

        $response->assertNotFound();
    }

    public function test_07_batch_with_cross_tenant_variant_is_rejected(): void
    {
        // OrderItem belonging to Order A, but referencing Variant B (Outlet B)
        $crossItem = OrderItem::create([
            'order_id'           => $this->orderA->id,
            'variant_id'         => $this->variantB->id,
            'qty_ordered'        => 5,
            'unit_selling_price' => 85000,
        ]);

        $crossBatch = ProductionBatch::create([
            'order_item_id'          => $crossItem->id,
            'batch_no'               => 'BAT-CROSS-VAR-001',
            'target_qty'             => 5,
            'status'                 => BatchStatus::PRODUKSI,
            'requires_printing'      => false,
            'production_employee_id' => $this->produksiA->id,
            'planned_start_date'     => now()->toDateString(),
        ]);

        $bm = BatchMaterial::create([
            'batch_id'      => $crossBatch->id,
            'material_id'   => $this->materialA->id,
            'required_qty'  => '10.0000',
            'allocated_qty' => '10.0000',
            'used_qty'      => '0.0000',
        ]);

        $response = $this->actingAs($this->produksiA)->post(route('produksi.batches.production-result.store', $crossBatch), [
            'actual_qty'     => 5,
            'result_date'    => now()->toDateString(),
            'material_usage' => [$bm->id => '10.0000'],
        ]);

        $response->assertNotFound();
    }

    public function test_08_batch_with_cross_tenant_material_is_rejected(): void
    {
        // Batch belonging to Order A, but has material belonging to Outlet B
        $crossBatch = ProductionBatch::create([
            'order_item_id'          => $this->itemA->id,
            'batch_no'               => 'BAT-CROSS-MAT-001',
            'target_qty'             => 5,
            'status'                 => BatchStatus::PRODUKSI,
            'requires_printing'      => false,
            'production_employee_id' => $this->produksiA->id,
            'planned_start_date'     => now()->toDateString(),
        ]);

        $bm = BatchMaterial::create([
            'batch_id'      => $crossBatch->id,
            'material_id'   => $this->materialB->id, // Material belonging to Outlet B
            'required_qty'  => '10.0000',
            'allocated_qty' => '10.0000',
            'used_qty'      => '0.0000',
        ]);

        $response = $this->actingAs($this->produksiA)->post(route('produksi.batches.production-result.store', $crossBatch), [
            'actual_qty'     => 5,
            'result_date'    => now()->toDateString(),
            'material_usage' => [$bm->id => '10.0000'],
        ]);

        $response->assertNotFound();
    }

    public function test_09_fake_request_outlet_id_does_not_change_tenant(): void
    {
        $response = $this->actingAs($this->produksiA)->post(route('produksi.batches.production-result.store', $this->batchBProduksi), [
            'outlet_id'      => $this->outletB->id,
            'actual_qty'     => 15,
            'result_date'    => now()->toDateString(),
            'material_usage' => [$this->bmB->id => '30.0000'],
        ]);

        $response->assertNotFound();
    }

    public function test_10_fake_header_x_outlet_id_does_not_change_tenant(): void
    {
        $response = $this->actingAs($this->produksiA)
            ->withHeaders(['X-Outlet-ID' => (string) $this->outletB->id])
            ->post(route('produksi.batches.production-result.store', $this->batchBProduksi), [
                'actual_qty'     => 15,
                'result_date'    => now()->toDateString(),
                'material_usage' => [$this->bmB->id => '30.0000'],
            ]);

        $response->assertNotFound();
    }

    public function test_11_quantity_validation_existing_still_applies(): void
    {
        $response = $this->actingAs($this->produksiA)->post(route('produksi.batches.production-result.store', $this->batchAProduksi), [
            'actual_qty'     => 0, // Must be > 0
            'result_date'    => now()->toDateString(),
            'material_usage' => [$this->bmA->id => '25.0000'],
        ]);

        $response->assertSessionHasErrors(['actual_qty']);
        $this->assertEquals(BatchStatus::PRODUKSI, $this->batchAProduksi->fresh()->status);
    }

    public function test_12_status_gate_production_result_still_applies(): void
    {
        // Batch in QC status cannot record production result
        $response = $this->actingAs($this->produksiA)
            ->from(route('produksi.batches.show', $this->batchAQc))
            ->post(route('produksi.batches.production-result.store', $this->batchAQc), [
                'actual_qty'     => 10,
                'result_date'    => now()->toDateString(),
                'material_usage' => [],
            ]);

        $response->assertRedirect(route('produksi.batches.show', $this->batchAQc));
        $response->assertSessionHas('error');
    }

    public function test_13_unauthorized_store_does_not_create_production_result(): void
    {
        $initialCount = ProductionResult::count();

        $this->actingAs($this->produksiA)->post(route('produksi.batches.production-result.store', $this->batchBProduksi), [
            'actual_qty'     => 15,
            'result_date'    => now()->toDateString(),
            'material_usage' => [$this->bmB->id => '30.0000'],
        ])->assertNotFound();

        $this->assertEquals($initialCount, ProductionResult::count());
    }

    public function test_14_unauthorized_store_does_not_change_batch_status(): void
    {
        $this->actingAs($this->produksiA)->post(route('produksi.batches.production-result.store', $this->batchBProduksi), [
            'actual_qty'     => 15,
            'result_date'    => now()->toDateString(),
            'material_usage' => [$this->bmB->id => '30.0000'],
        ])->assertNotFound();

        $this->assertEquals(BatchStatus::PRODUKSI, $this->batchBProduksi->fresh()->status);
    }

    public function test_15_unauthorized_store_does_not_change_order_status(): void
    {
        $initialStatus = $this->orderB->status;

        $this->actingAs($this->produksiA)->post(route('produksi.batches.production-result.store', $this->batchBProduksi), [
            'actual_qty'     => 15,
            'result_date'    => now()->toDateString(),
            'material_usage' => [$this->bmB->id => '30.0000'],
        ])->assertNotFound();

        $this->assertEquals($initialStatus, $this->orderB->fresh()->status);
    }

    public function test_16_unauthorized_store_does_not_create_stock_movement(): void
    {
        $initialMovements = StockMovement::count();

        $this->actingAs($this->produksiA)->post(route('produksi.batches.production-result.store', $this->batchBProduksi), [
            'actual_qty'     => 15,
            'result_date'    => now()->toDateString(),
            'material_usage' => [$this->bmB->id => '20.0000'], // surplus
        ])->assertNotFound();

        $this->assertEquals($initialMovements, StockMovement::count());
    }

    // =========================================================================
    // SECTION 2: ADDITIONAL MATERIAL REQUEST (TEST 17 - 23)
    // =========================================================================

    public function test_17_user_a_can_request_additional_material_for_eligible_batch_a(): void
    {
        $initialStock = MaterialStock::where('material_id', $this->materialA->id)->first()->qty_on_hand;

        $response = $this->actingAs($this->produksiA)->post(route('produksi.batches.materials.additional', [
            'batch'         => $this->batchAProduksi,
            'batchMaterial' => $this->bmA,
        ]), [
            'additional_qty' => 5,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->bmA->refresh();
        $this->assertEquals('30.0000', $this->bmA->allocated_qty);

        $newStock = MaterialStock::where('material_id', $this->materialA->id)->first()->qty_on_hand;
        $this->assertEquals(bcsub($initialStock, '5.0000', 4), $newStock);

        $this->assertDatabaseHas('stock_movements', [
            'material_id'    => $this->materialA->id,
            'reference_type' => 'production_batches',
            'reference_id'   => $this->batchAProduksi->id,
            'qty'            => '5.0000',
        ]);
    }

    public function test_18_user_a_cannot_request_additional_material_for_batch_b(): void
    {
        $response = $this->actingAs($this->produksiA)->post(route('produksi.batches.materials.additional', [
            'batch'         => $this->batchBProduksi,
            'batchMaterial' => $this->bmB,
        ]), [
            'additional_qty' => 5,
        ]);

        $response->assertNotFound();
    }

    public function test_19_foreign_batch_on_material_request_returns_404(): void
    {
        $response = $this->actingAs($this->produksiB)->post(route('produksi.batches.materials.additional', [
            'batch'         => $this->batchAProduksi,
            'batchMaterial' => $this->bmA,
        ]), [
            'additional_qty' => 5,
        ]);

        $response->assertNotFound();
    }

    public function test_20_batch_material_from_another_batch_is_rejected(): void
    {
        // Batch A, but batchMaterial belongs to Batch B
        $response = $this->actingAs($this->produksiA)->post(route('produksi.batches.materials.additional', [
            'batch'         => $this->batchAProduksi,
            'batchMaterial' => $this->bmB,
        ]), [
            'additional_qty' => 5,
        ]);

        $response->assertNotFound();
    }

    public function test_21_material_belonging_to_outlet_b_cannot_be_used_for_batch_a(): void
    {
        $crossBm = BatchMaterial::create([
            'batch_id'      => $this->batchAProduksi->id,
            'material_id'   => $this->materialB->id, // Outlet B
            'required_qty'  => '5.0000',
            'allocated_qty' => '5.0000',
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

    public function test_22_additional_material_request_leaves_no_partial_record_when_invalid(): void
    {
        $initialStock = MaterialStock::where('material_id', $this->materialA->id)->first()->qty_on_hand;
        $initialMovements = StockMovement::count();

        // Requesting 999999 (far exceeds warehouse stock)
        $response = $this->actingAs($this->produksiA)->post(route('produksi.batches.materials.additional', [
            'batch'         => $this->batchAProduksi,
            'batchMaterial' => $this->bmA,
        ]), [
            'additional_qty' => 999999,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertEquals($initialStock, MaterialStock::where('material_id', $this->materialA->id)->first()->qty_on_hand);
        $this->assertEquals($initialMovements, StockMovement::count());
        $this->assertEquals('25.0000', $this->bmA->fresh()->allocated_qty);
    }

    public function test_23_no_inventory_mutation_on_outlet_b_due_to_outlet_a_material_request(): void
    {
        $stockBBefore = MaterialStock::where('material_id', $this->materialB->id)->first()->qty_on_hand;

        $this->actingAs($this->produksiA)->post(route('produksi.batches.materials.additional', [
            'batch'         => $this->batchAProduksi,
            'batchMaterial' => $this->bmA,
        ]), [
            'additional_qty' => 5,
        ])->assertRedirect();

        $stockBAfter = MaterialStock::where('material_id', $this->materialB->id)->first()->qty_on_hand;
        $this->assertEquals($stockBBefore, $stockBAfter);
    }

    // =========================================================================
    // SECTION 3: QC RESULT STORE (TEST 24 - 36)
    // =========================================================================

    public function test_24_user_a_can_store_qc_for_eligible_batch_a(): void
    {
        $response = $this->actingAs($this->produksiA)->post(route('produksi.batches.qc.store', $this->batchAQc), [
            'good_qty' => 8,
            'qc_date'  => now()->toDateString(),
            'notes'    => 'QC lolos 8 pcs',
        ]);

        $response->assertRedirect(route('produksi.batches.show', $this->batchAQc));
        $response->assertSessionHas('success');

        $this->batchAQc->refresh();
        $this->assertEquals(BatchStatus::PACKING, $this->batchAQc->status);
        $this->assertDatabaseHas('qc_results', [
            'production_result_id' => $this->prodResultA->id,
            'good_qty'             => '8.0000',
            'reject_qty'           => '2.0000', // derived 10 - 8
        ]);
    }

    public function test_25_user_b_can_store_qc_for_eligible_batch_b(): void
    {
        $response = $this->actingAs($this->produksiB)->post(route('produksi.batches.qc.store', $this->batchBQc), [
            'good_qty' => 12,
            'qc_date'  => now()->toDateString(),
            'notes'    => 'QC lolos 12 pcs B',
        ]);

        $response->assertRedirect(route('produksi.batches.show', $this->batchBQc));
        $response->assertSessionHas('success');

        $this->batchBQc->refresh();
        $this->assertEquals(BatchStatus::PACKING, $this->batchBQc->status);
        $this->assertDatabaseHas('qc_results', [
            'production_result_id' => $this->prodResultB->id,
            'good_qty'             => '12.0000',
            'reject_qty'           => '3.0000', // derived 15 - 12
        ]);
    }

    public function test_26_user_a_cannot_store_qc_for_batch_b(): void
    {
        $response = $this->actingAs($this->produksiA)->post(route('produksi.batches.qc.store', $this->batchBQc), [
            'good_qty' => 12,
            'qc_date'  => now()->toDateString(),
        ]);

        $response->assertNotFound();
        $this->assertDatabaseMissing('qc_results', [
            'production_result_id' => $this->prodResultB->id,
        ]);
    }

    public function test_27_foreign_batch_on_qc_returns_404(): void
    {
        $response = $this->actingAs($this->produksiB)->post(route('produksi.batches.qc.store', $this->batchAQc), [
            'good_qty' => 8,
            'qc_date'  => now()->toDateString(),
        ]);

        $response->assertNotFound();
    }

    public function test_28_production_result_from_another_batch_cannot_be_used_as_qc_reference(): void
    {
        // Even if an attacker passes foreign production_result_id in request, it is ignored
        $response = $this->actingAs($this->produksiA)->post(route('produksi.batches.qc.store', $this->batchAQc), [
            'production_result_id' => $this->prodResultB->id,
            'good_qty'             => 7,
            'qc_date'              => now()->toDateString(),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('qc_results', [
            'production_result_id' => $this->prodResultA->id,
            'good_qty'             => '7.0000',
        ]);
    }

    public function test_29_qc_result_from_another_batch_cannot_be_modified(): void
    {
        // First record QC on batch A
        $this->actingAs($this->produksiA)->post(route('produksi.batches.qc.store', $this->batchAQc), [
            'good_qty' => 8,
            'qc_date'  => now()->toDateString(),
        ])->assertRedirect();

        // Second attempt on the same batch is rejected
        $response = $this->actingAs($this->produksiA)->post(route('produksi.batches.qc.store', $this->batchAQc), [
            'good_qty' => 10,
            'qc_date'  => now()->toDateString(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals('8.0000', QcResult::where('production_result_id', $this->prodResultA->id)->first()->good_qty);
    }

    public function test_30_good_qty_and_reject_qty_follow_existing_rules(): void
    {
        // good_qty > actual_qty (12 > 10)
        $response = $this->actingAs($this->produksiA)->post(route('produksi.batches.qc.store', $this->batchAQc), [
            'good_qty' => 12,
            'qc_date'  => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors(['good_qty']);
        $this->assertDatabaseMissing('qc_results', [
            'production_result_id' => $this->prodResultA->id,
        ]);
    }

    public function test_31_qc_status_gate_still_applies(): void
    {
        // Batch in PRODUKSI status (not yet in QC) cannot record QC
        $response = $this->actingAs($this->produksiA)
            ->from(route('produksi.batches.show', $this->batchAProduksi))
            ->post(route('produksi.batches.qc.store', $this->batchAProduksi), [
                'good_qty' => 5,
                'qc_date'  => now()->toDateString(),
            ]);

        $response->assertRedirect(route('produksi.batches.show', $this->batchAProduksi));
        $response->assertSessionHas('error');
    }

    public function test_32_unauthorized_qc_does_not_create_qc_result(): void
    {
        $initialCount = QcResult::count();

        $this->actingAs($this->produksiA)->post(route('produksi.batches.qc.store', $this->batchBQc), [
            'good_qty' => 10,
            'qc_date'  => now()->toDateString(),
        ])->assertNotFound();

        $this->assertEquals($initialCount, QcResult::count());
    }

    public function test_33_unauthorized_qc_does_not_change_batch_status(): void
    {
        $this->actingAs($this->produksiA)->post(route('produksi.batches.qc.store', $this->batchBQc), [
            'good_qty' => 10,
            'qc_date'  => now()->toDateString(),
        ])->assertNotFound();

        $this->assertEquals(BatchStatus::QC, $this->batchBQc->fresh()->status);
    }

    public function test_34_unauthorized_qc_does_not_change_order_status(): void
    {
        $initialStatus = $this->orderB->status;

        $this->actingAs($this->produksiA)->post(route('produksi.batches.qc.store', $this->batchBQc), [
            'good_qty' => 10,
            'qc_date'  => now()->toDateString(),
        ])->assertNotFound();

        $this->assertEquals($initialStatus, $this->orderB->fresh()->status);
    }

    public function test_35_unauthorized_qc_does_not_create_inventory_movement(): void
    {
        $initialMovements = StockMovement::count();

        $this->actingAs($this->produksiA)->post(route('produksi.batches.qc.store', $this->batchBQc), [
            'good_qty' => 10,
            'qc_date'  => now()->toDateString(),
        ])->assertNotFound();

        $this->assertEquals($initialMovements, StockMovement::count());
    }

    public function test_36_unauthorized_qc_does_not_modify_stock_ledger(): void
    {
        $initialStock = MaterialStock::where('material_id', $this->materialB->id)->first()->qty_on_hand;

        $this->actingAs($this->produksiA)->post(route('produksi.batches.qc.store', $this->batchBQc), [
            'good_qty' => 10,
            'qc_date'  => now()->toDateString(),
        ])->assertNotFound();

        $this->assertEquals($initialStock, MaterialStock::where('material_id', $this->materialB->id)->first()->qty_on_hand);
    }

    // =========================================================================
    // SECTION 4: TENANT SECURITY & ROLE INTEGRITY (TEST 37 - 48)
    // =========================================================================

    public function test_37_user_without_outlet_is_rejected_when_flag_true(): void
    {
        $response = $this->actingAs($this->unboundProduksi)->post(route('produksi.batches.production-result.store', $this->batchAProduksi), [
            'actual_qty'  => 10,
            'result_date' => now()->toDateString(),
        ]);

        $response->assertForbidden();
    }

    public function test_38_inactive_outlet_is_rejected(): void
    {
        $response = $this->actingAs($this->produksiInactiveOutlet)->post(route('produksi.batches.production-result.store', $this->batchAProduksi), [
            'actual_qty'  => 10,
            'result_date' => now()->toDateString(),
        ]);

        $response->assertForbidden();
    }

    public function test_39_inactive_user_is_rejected(): void
    {
        $response = $this->actingAs($this->inactiveProduksiA)->post(route('produksi.batches.production-result.store', $this->batchAProduksi), [
            'actual_qty'  => 10,
            'result_date' => now()->toDateString(),
        ]);

        $response->assertRedirect('/login');
    }

    public function test_40_customer_cannot_access_internal_production_write_routes(): void
    {
        $this->actingAs($this->customerUser)->post(route('produksi.batches.production-result.store', $this->batchAProduksi), [
            'actual_qty'  => 10,
            'result_date' => now()->toDateString(),
        ])->assertForbidden();

        $this->actingAs($this->customerUser)->post(route('produksi.batches.materials.additional', [
            'batch'         => $this->batchAProduksi,
            'batchMaterial' => $this->bmA,
        ]), [
            'additional_qty' => 5,
        ])->assertForbidden();

        $this->actingAs($this->customerUser)->post(route('produksi.batches.qc.store', $this->batchAQc), [
            'good_qty' => 8,
            'qc_date'  => now()->toDateString(),
        ])->assertForbidden();
    }

    public function test_41_role_middleware_restricts_access_to_produksi_role(): void
    {
        // Admin cannot post to produksi write routes
        $this->actingAs($this->adminA)->post(route('produksi.batches.production-result.store', $this->batchAProduksi), [
            'actual_qty'  => 10,
            'result_date' => now()->toDateString(),
        ])->assertForbidden();

        // Gudang cannot post to produksi write routes
        $this->actingAs($this->gudangA)->post(route('produksi.batches.production-result.store', $this->batchAProduksi), [
            'actual_qty'  => 10,
            'result_date' => now()->toDateString(),
        ])->assertForbidden();
    }

    public function test_42_owner_does_not_receive_automatic_operational_bypass(): void
    {
        $this->actingAs($this->ownerA)->post(route('produksi.batches.production-result.store', $this->batchAProduksi), [
            'actual_qty'  => 10,
            'result_date' => now()->toDateString(),
        ])->assertForbidden();

        $this->actingAs($this->ownerA)->post(route('produksi.batches.qc.store', $this->batchAQc), [
            'good_qty' => 8,
            'qc_date'  => now()->toDateString(),
        ])->assertForbidden();
    }

    public function test_43_purchase_order_approval_remains_admin_only(): void
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

        // Produksi user cannot approve PO
        $this->actingAs($this->produksiA)->post(route('admin.purchases.approvals.approve', $purchase))
            ->assertForbidden();

        // Owner cannot approve PO
        $this->actingAs($this->ownerA)->post(route('admin.purchases.approvals.approve', $purchase))
            ->assertForbidden();
    }

    public function test_44_flag_false_maintains_legacy_behavior(): void
    {
        config(['nayara.tenant_isolation_enabled' => false]);

        $response = $this->actingAs($this->unboundProduksi)->post(route('produksi.batches.production-result.store', $this->unassignedBatchProduksi), [
            'actual_qty'     => 5,
            'result_date'    => now()->toDateString(),
            'material_usage' => [$this->bmUnassigned->id => '12.5000'],
        ]);

        // When flag is false, request proceeds without tenant 404/403
        $this->assertNotEquals(403, $response->getStatusCode());
        $this->assertNotEquals(404, $response->getStatusCode());
        $response->assertRedirect(route('produksi.batches.show', $this->unassignedBatchProduksi));
    }

    public function test_45_flag_true_fails_closed_when_tenant_invalid(): void
    {
        config(['nayara.tenant_isolation_enabled' => true]);

        $response = $this->actingAs($this->unboundProduksi)->post(route('produksi.batches.production-result.store', $this->batchAProduksi), [
            'actual_qty'  => 10,
            'result_date' => now()->toDateString(),
        ]);

        $response->assertForbidden();
    }

    public function test_46_phase_7_1_get_endpoints_remain_secure_and_pass(): void
    {
        // Phase 7.1 GET endpoints
        $this->actingAs($this->produksiA)->get(route('produksi.batches.production-result.create', $this->batchAProduksi))
            ->assertOk();

        $this->actingAs($this->produksiA)->get(route('produksi.batches.production-result.create', $this->batchBProduksi))
            ->assertNotFound();

        $this->actingAs($this->produksiA)->get(route('produksi.batches.qc.create', $this->batchAQc))
            ->assertOk();

        $this->actingAs($this->produksiA)->get(route('produksi.batches.qc.create', $this->batchBQc))
            ->assertNotFound();
    }

    public function test_47_batch_core_phase_6_read_write_still_pass(): void
    {
        // Phase 6 batch show & actions
        $this->actingAs($this->produksiA)->get(route('produksi.batches.show', $this->batchAProduksi))
            ->assertOk();

        $this->actingAs($this->produksiA)->get(route('produksi.batches.show', $this->batchBProduksi))
            ->assertNotFound();
    }

    public function test_48_no_global_tenant_scope_registered(): void
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
        $this->assertEquals(
            BatchMaterial::withoutGlobalScopes()->count(),
            BatchMaterial::count()
        );
        $this->assertEmpty((new ProductionBatch)->getGlobalScopes());
        $this->assertEmpty((new ProductionResult)->getGlobalScopes());
        $this->assertEmpty((new QcResult)->getGlobalScopes());
        $this->assertEmpty((new BatchMaterial)->getGlobalScopes());
    }

    // =========================================================================
    // SECTION 5: ADVANCED ATOMICITY, DATA INTEGRITY & CALCULATION (TEST 49 - 54)
    // =========================================================================

    public function test_49_service_error_in_transaction_rolls_back_cleanly(): void
    {
        // Material usage exceeds allocated qty (26 > 25)
        $response = $this->actingAs($this->produksiA)->post(route('produksi.batches.production-result.store', $this->batchAProduksi), [
            'actual_qty'     => 10,
            'result_date'    => now()->toDateString(),
            'material_usage' => [$this->bmA->id => '26.0000'],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Rollback verification: batch status remains PRODUKSI, no ProductionResult created
        $this->assertEquals(BatchStatus::PRODUKSI, $this->batchAProduksi->fresh()->status);
        $this->assertDatabaseMissing('production_results', [
            'batch_id' => $this->batchAProduksi->id,
        ]);
        $this->assertEquals('0.0000', $this->bmA->fresh()->used_qty);
    }

    public function test_50_no_data_of_outlet_b_modified_due_to_unauthorized_action_of_user_a(): void
    {
        $batchBBefore = $this->batchBProduksi->fresh()->toArray();
        $bmBBefore = $this->bmB->fresh()->toArray();

        // User A attempts unauthorized writes on Batch B
        $this->actingAs($this->produksiA)->post(route('produksi.batches.production-result.store', $this->batchBProduksi), [
            'actual_qty'  => 15,
            'result_date' => now()->toDateString(),
        ])->assertNotFound();

        $this->actingAs($this->produksiA)->post(route('produksi.batches.materials.additional', [
            'batch'         => $this->batchBProduksi,
            'batchMaterial' => $this->bmB,
        ]), ['additional_qty' => 5])->assertNotFound();

        $this->actingAs($this->produksiA)->post(route('produksi.batches.qc.store', $this->batchBQc), [
            'good_qty' => 10,
            'qc_date'  => now()->toDateString(),
        ])->assertNotFound();

        $this->assertEquals($batchBBefore, $this->batchBProduksi->fresh()->toArray());
        $this->assertEquals($bmBBefore, $this->bmB->fresh()->toArray());
    }

    public function test_51_happy_path_inventory_movements_reference_valid_tenant_resources(): void
    {
        // When surplus material is returned, stock movement must point to outlet A's material & batch
        $response = $this->actingAs($this->produksiA)->post(route('produksi.batches.production-result.store', $this->batchAProduksi), [
            'actual_qty'     => 10,
            'result_date'    => now()->toDateString(),
            'material_usage' => [$this->bmA->id => '20.0000'], // surplus 5.0000 returned to warehouse
        ]);

        $response->assertRedirect();

        $movement = StockMovement::where('reference_type', 'production_batches')
            ->where('reference_id', $this->batchAProduksi->id)
            ->first();

        $this->assertNotNull($movement);
        $this->assertEquals($this->materialA->id, $movement->material_id);
        $this->assertEquals('5.0000', $movement->qty);
    }

    public function test_52_nested_resource_id_manipulation_across_batches_is_rejected(): void
    {
        // User A attempts to submit material_usage using batchMaterial of Batch B
        $response = $this->actingAs($this->produksiA)->post(route('produksi.batches.production-result.store', $this->batchAProduksi), [
            'actual_qty'     => 10,
            'result_date'    => now()->toDateString(),
            'material_usage' => [
                $this->bmB->id => '25.0000', // Foreign batch material key
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals(BatchStatus::PRODUKSI, $this->batchAProduksi->fresh()->status);
    }

    public function test_53_no_duplicate_posting_allowed(): void
    {
        // First valid store
        $this->actingAs($this->produksiA)->post(route('produksi.batches.production-result.store', $this->batchAProduksi), [
            'actual_qty'     => 10,
            'result_date'    => now()->toDateString(),
            'material_usage' => [$this->bmA->id => '25.0000'],
        ])->assertRedirect();

        // Second store attempt on the same batch is rejected
        $response = $this->actingAs($this->produksiA)->post(route('produksi.batches.production-result.store', $this->batchAProduksi), [
            'actual_qty'     => 10,
            'result_date'    => now()->toDateString(),
            'material_usage' => [$this->bmA->id => '25.0000'],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_54_production_and_qc_calculations_follow_existing_business_rules(): void
    {
        // 1. Verify QC reject derivation: actual 10, good 7 => reject 3
        $this->actingAs($this->produksiA)->post(route('produksi.batches.qc.store', $this->batchAQc), [
            'good_qty' => 7,
            'qc_date'  => now()->toDateString(),
        ])->assertRedirect();

        $qc = QcResult::where('production_result_id', $this->prodResultA->id)->first();
        $this->assertNotNull($qc);
        $this->assertEquals('7.0000', $qc->good_qty);
        $this->assertEquals('3.0000', $qc->reject_qty);
    }
}
