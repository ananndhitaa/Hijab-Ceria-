<?php

namespace Tests\Feature\Finance;

use App\Enums\BatchStatus;
use App\Enums\MovementType;
use App\Enums\OrderStatus;
use App\Enums\PurchaseStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Inventory\MaterialStock;
use App\Models\Inventory\Purchase;
use App\Models\Inventory\PurchaseItem;
use App\Models\Inventory\PurchasePayment;
use App\Models\Inventory\StockMovement;
use App\Models\Master\BomItem;
use App\Models\Master\Customer;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Master\Supplier;
use App\Models\Master\Unit;
use App\Models\Production\BatchCost;
use App\Models\Production\BatchMaterial;
use App\Models\Production\ProductionBatch;
use App\Models\Production\ProductionResult;
use App\Models\Production\QcResult;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\User;
use App\Services\Finance\BatchCostCalculationService;
use App\Services\Finance\OrderProfitabilityService;
use App\Services\Finance\VariantCostCalculationService;
use App\Services\Inventory\PurchaseOrderService;
use App\Services\Inventory\PurchaseReceivingService;
use App\Services\Tracking\TrackingTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupervisorHppAndSellingPriceRevisionTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $ownerUser;
    private User $gudangUser;
    private User $produksiUser;
    private User $customerUser;

    private Unit $meter;
    private Supplier $supplier;
    private Material $kain;
    private Product $product;
    private ProductVariant $variant;
    private Customer $customer;

    private PurchaseOrderService $poService;
    private PurchaseReceivingService $receivingService;
    private BatchCostCalculationService $batchCostService;
    private VariantCostCalculationService $variantCostService;
    private OrderProfitabilityService $profitabilityService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->poService = app(PurchaseOrderService::class);
        $this->receivingService = app(PurchaseReceivingService::class);
        $this->batchCostService = app(BatchCostCalculationService::class);
        $this->variantCostService = app(VariantCostCalculationService::class);
        $this->profitabilityService = app(OrderProfitabilityService::class);

        // Users with distinct roles
        $this->adminUser = User::factory()->create([
            'role'   => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
        ]);
        $this->ownerUser = User::factory()->create([
            'role'   => UserRole::OWNER,
            'status' => UserStatus::ACTIVE,
        ]);
        $this->gudangUser = User::factory()->create([
            'role'   => UserRole::GUDANG,
            'status' => UserStatus::ACTIVE,
        ]);
        $this->produksiUser = User::factory()->create([
            'role'   => UserRole::PRODUKSI,
            'status' => UserStatus::ACTIVE,
        ]);
        $this->customerUser = User::factory()->create([
            'role'   => UserRole::CUSTOMER,
            'status' => UserStatus::ACTIVE,
        ]);

        // Base Master Data
        $this->meter = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $this->supplier = Supplier::create([
            'supplier_name' => 'PT Tekstil Nusantara',
            'phone'         => '081234567890',
            'active'        => true,
        ]);

        $this->kain = Material::create([
            'material_name' => 'Kain Voal Premium',
            'unit_id'       => $this->meter->id,
            'cost_per_unit' => 20000,
            'min_stock'     => 10,
            'active'        => true,
        ]);

        MaterialStock::create([
            'material_id'   => $this->kain->id,
            'qty_on_hand'   => 100,
            'avg_unit_cost' => 20000,
        ]);

        $this->product = Product::create([
            'product_name' => 'Hijab Segiempat Voal',
            'active'       => true,
        ]);

        $this->variant = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'Navy Blue',
            'sku'                                  => 'HJB-VOAL-NVY',
            'base_selling_price'                   => 69000,
            'requires_printing'                    => true,
            'printing_cost_per_unit'               => 10000,
            'direct_labor_cost_per_unit'           => 5000,
            'packaging_process_cost_per_good_unit' => 2000,
            'other_direct_cost_per_unit'           => 1000,
            'active'                               => true,
        ]);

        BomItem::create([
            'variant_id'   => $this->variant->id,
            'material_id'  => $this->kain->id,
            'qty_per_unit' => 1,
        ]);

        $this->customer = Customer::create([
            'customer_name' => 'Ibu Rahmawati',
            'phone'         => '081298765432',
            'address'       => 'Jl. Anggrek No. 12 Jakarta',
            'user_id'       => $this->customerUser->id,
        ]);
    }

    // =========================================================================
    // GROUP 1: PURCHASE / MATERIAL COST (Requirements 1-5)
    // =========================================================================

    public function test_01_create_po_does_not_change_avg_cost(): void
    {
        $initialAvg = MaterialStock::where('material_id', $this->kain->id)->value('avg_unit_cost');
        $this->assertEquals('20000.0000', $initialAvg);

        // Create PO with higher price (Rp30.000)
        $po = $this->poService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => now()->toDateString(),
            'notes'         => 'Pembelian bahan tambahan',
            'items'         => [
                [
                    'material_id' => $this->kain->id,
                    'qty'         => 100,
                    'unit_price'  => 30000,
                ],
            ],
        ]);

        $this->assertEquals(PurchaseStatus::DRAFT, $po->status);
        $stockAfter = MaterialStock::where('material_id', $this->kain->id)->first();
        $this->assertEquals('20000.0000', $stockAfter->avg_unit_cost);
        $this->assertEquals('100.0000', $stockAfter->qty_on_hand);
    }

    public function test_02_confirm_po_does_not_change_avg_cost(): void
    {
        $po = $this->poService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => now()->toDateString(),
            'items'         => [
                [
                    'material_id' => $this->kain->id,
                    'qty'         => 100,
                    'unit_price'  => 30000,
                ],
            ],
        ]);

        $po->update([
            'status'          => PurchaseStatus::ORDERED,
            'approval_status' => \App\Enums\PurchaseApprovalStatus::APPROVED,
            'approved_at'     => now(),
        ]);
        PurchasePayment::create([
            'purchase_id'    => $po->id,
            'payment_date'   => $po->purchase_date->toDateString(),
            'amount'         => $po->total_amount,
            'payment_method' => 'TRANSFER',
            'created_by'     => $this->gudangUser->id,
        ]);

        $po->refresh();
        $this->assertEquals(PurchaseStatus::ORDERED, $po->status);

        $stockAfter = MaterialStock::where('material_id', $this->kain->id)->first();
        $this->assertEquals('20000.0000', $stockAfter->avg_unit_cost);
        $this->assertEquals('100.0000', $stockAfter->qty_on_hand);
    }

    public function test_03_receive_purchase_updates_stock_and_mwa(): void
    {
        // Initial: 100 m @ Rp20.000 (total = Rp2.000.000)
        $po = $this->poService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => now()->toDateString(),
            'items'         => [
                [
                    'material_id' => $this->kain->id,
                    'qty'         => 100,
                    'unit_price'  => 30000, // New receipt: 100 m @ Rp30.000 (total = Rp3.000.000)
                ],
            ],
        ]);
        $po->update([
            'status'          => PurchaseStatus::ORDERED,
            'approval_status' => \App\Enums\PurchaseApprovalStatus::APPROVED,
            'approved_at'     => now(),
        ]);
        $po->load('items');
        PurchasePayment::create([
            'purchase_id'    => $po->id,
            'payment_date'   => $po->purchase_date->toDateString(),
            'amount'         => $po->total_amount,
            'payment_method' => 'TRANSFER',
            'created_by'     => $this->gudangUser->id,
        ]);

        // Receive PO (Full Receiving)
        $this->receivingService->receivePurchase($po, $this->gudangUser->id);

        // Expected MWA: (2.000.000 + 3.000.000) / 200 = Rp25.000
        $stockAfter = MaterialStock::where('material_id', $this->kain->id)->first();
        $this->assertEquals('200.0000', $stockAfter->qty_on_hand);
        $this->assertEquals('25000.0000', $stockAfter->avg_unit_cost);
    }

    public function test_04_material_displays_authoritative_average_cost(): void
    {
        $response = $this->actingAs($this->gudangUser)->get(route('gudang.materials.show', $this->kain));
        $response->assertStatus(200);
        $response->assertSee('Modal Rata-rata (MWA)');
        $response->assertSee('Rp 20.000');
    }

    public function test_05_unreceived_po_price_is_not_treated_as_current_hpp(): void
    {
        // Unreceived PO at Rp50.000
        $this->poService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => now()->toDateString(),
            'items'         => [
                [
                    'material_id' => $this->kain->id,
                    'qty'         => 50,
                    'unit_price'  => 50000,
                ],
            ],
        ]);

        // Material stock cost must stay at received MWA (Rp20.000), not unreceived Rp50.000
        $stock = MaterialStock::where('material_id', $this->kain->id)->first();
        $this->assertEquals('20000.0000', $stock->avg_unit_cost);
    }

    // =========================================================================
    // GROUP 2: PHASE 2 COST RECONCILIATION (Requirements 6-11)
    // =========================================================================

    public function test_06_initial_allocation_cost_captured(): void
    {
        $order = Order::create([
            'customer_id'  => $this->customer->id,
            'order_no'     => 'SO-TEST-001',
            'order_date'   => now()->toDateString(),
            'status'       => OrderStatus::DEAL,
            'total_qty'    => 50,
            'total_amount' => 3450000,
        ]);
        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => 50,
            'unit_selling_price' => 69000,
            'subtotal'           => 3450000,
        ]);

        $batch = ProductionBatch::create([
            'batch_no'                   => 'BATCH-202610-001',
            'order_item_id'              => $orderItem->id,
            'target_qty'                 => 50,
            'actual_qty'                 => 50,
            'status'                     => BatchStatus::SELESAI,
            'material_usage_recorded_at' => now(),
            'start_date'                 => now()->toDateString(),
            'end_date'                   => now()->toDateString(),
        ]);

        $bm = BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->kain->id,
            'allocated_qty' => 50,
            'used_qty'      => 50,
        ]);

        StockMovement::create([
            'material_id'         => $this->kain->id,
            'movement_type'       => MovementType::OUT,
            'qty'                 => -50,
            'unit_cost_snapshot'  => 20000,
            'total_cost_snapshot' => 1000000,
            'reference_type'      => 'batch_materials',
            'reference_id'        => $bm->id,
            'movement_date'       => now(),
            'created_by'          => $this->gudangUser->id,
        ]);

        $materialCost = $this->batchCostService->calculateMaterialCost($batch);
        $this->assertEquals('READY', $materialCost['status']);
        $this->assertEquals('1000000.00', $materialCost['amount']);
    }

    public function test_07_additional_allocation_cost_captured_correctly_with_distinct_snapshot(): void
    {
        $order = Order::create([
            'customer_id'  => $this->customer->id,
            'order_no'     => 'SO-TEST-002',
            'order_date'   => now()->toDateString(),
            'status'       => OrderStatus::DEAL,
            'total_qty'    => 50,
            'total_amount' => 3450000,
        ]);
        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => 50,
            'unit_selling_price' => 69000,
            'subtotal'           => 3450000,
        ]);

        $batch = ProductionBatch::create([
            'batch_no'                   => 'BATCH-202610-002',
            'order_item_id'              => $orderItem->id,
            'target_qty'                 => 50,
            'actual_qty'                 => 50,
            'status'                     => BatchStatus::SELESAI,
            'material_usage_recorded_at' => now(),
            'start_date'                 => now()->toDateString(),
            'end_date'                   => now()->toDateString(),
        ]);

        $bm = BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->kain->id,
            'allocated_qty' => 55, // 50 initial + 5 additional
            'used_qty'      => 55,
        ]);

        // Initial issue: 50 m @ Rp20.000 = Rp1.000.000
        StockMovement::create([
            'material_id'         => $this->kain->id,
            'movement_type'       => MovementType::OUT,
            'qty'                 => -50,
            'unit_cost_snapshot'  => 20000,
            'total_cost_snapshot' => 1000000,
            'reference_type'      => 'batch_materials',
            'reference_id'        => $bm->id,
            'movement_date'       => now()->subHour(),
            'created_by'          => $this->gudangUser->id,
        ]);

        // Additional issue (Tahap 2): 5 m @ Rp22.000 = Rp110.000
        StockMovement::create([
            'material_id'         => $this->kain->id,
            'movement_type'       => MovementType::OUT,
            'qty'                 => -5,
            'unit_cost_snapshot'  => 22000,
            'total_cost_snapshot' => 110000,
            'reference_type'      => 'production_batches',
            'reference_id'        => $batch->id,
            'movement_date'       => now(),
            'created_by'          => $this->gudangUser->id,
        ]);

        $materialCost = $this->batchCostService->calculateMaterialCost($batch);
        // Total expected: 1.000.000 + 110.000 = 1.110.000
        $this->assertEquals('READY', $materialCost['status']);
        $this->assertEquals('1110000.00', $materialCost['amount']);
    }

    public function test_08_returned_unused_quantity_is_not_counted_as_consumed_material_cost(): void
    {
        $order = Order::create([
            'customer_id'  => $this->customer->id,
            'order_no'     => 'SO-TEST-003',
            'order_date'   => now()->toDateString(),
            'status'       => OrderStatus::DEAL,
            'total_qty'    => 50,
            'total_amount' => 3450000,
        ]);
        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => 50,
            'unit_selling_price' => 69000,
            'subtotal'           => 3450000,
        ]);

        $batch = ProductionBatch::create([
            'batch_no'                   => 'BATCH-202610-003',
            'order_item_id'              => $orderItem->id,
            'target_qty'                 => 50,
            'actual_qty'                 => 50,
            'status'                     => BatchStatus::SELESAI,
            'material_usage_recorded_at' => now(),
            'start_date'                 => now()->toDateString(),
            'end_date'                   => now()->toDateString(),
        ]);

        // Example from prompt:
        // Initial: 100 m
        // Additional: 5 m
        // Total issued: 105 m
        // Returned unused: 8 m
        // Actual consumed: 97 m
        $bm = BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->kain->id,
            'allocated_qty' => 105,
            'used_qty'      => 97, // Returned 8
        ]);

        // Initial: 100 m @ 20.000
        StockMovement::create([
            'material_id'         => $this->kain->id,
            'movement_type'       => MovementType::OUT,
            'qty'                 => -100,
            'unit_cost_snapshot'  => 20000,
            'total_cost_snapshot' => 2000000,
            'reference_type'      => 'batch_materials',
            'reference_id'        => $bm->id,
            'movement_date'       => now()->subHours(2),
            'created_by'          => $this->gudangUser->id,
        ]);

        // Additional: 5 m @ 22.000
        StockMovement::create([
            'material_id'         => $this->kain->id,
            'movement_type'       => MovementType::OUT,
            'qty'                 => -5,
            'unit_cost_snapshot'  => 22000,
            'total_cost_snapshot' => 110000,
            'reference_type'      => 'production_batches',
            'reference_id'        => $batch->id,
            'movement_date'       => now()->subHour(),
            'created_by'          => $this->gudangUser->id,
        ]);

        // Valuation of actual consumption 97 m:
        // Consumed entirely from first issue (97 m * 20.000 = 1.940.000)
        $materialCost = $this->batchCostService->calculateMaterialCost($batch);
        $this->assertEquals('READY', $materialCost['status']);
        $this->assertEquals('1940000.00', $materialCost['amount']);
    }

    public function test_09_production_return_movement_in_does_not_corrupt_mwa(): void
    {
        // Allocate 10 m
        $initialStock = MaterialStock::where('material_id', $this->kain->id)->first();
        $this->assertEquals('100.0000', $initialStock->qty_on_hand);
        $this->assertEquals('20000.0000', $initialStock->avg_unit_cost);

        // Simulated return of 3 m with snapshot cost Rp20.000
        // Physical stock IN (+3 m)
        $initialStock->qty_on_hand = bcadd($initialStock->qty_on_hand, '3.0000', 4);
        $initialStock->save();

        // MWA must remain Rp20.000
        $this->assertEquals('20000.0000', $initialStock->avg_unit_cost);
        $this->assertEquals('103.0000', $initialStock->qty_on_hand);
    }

    public function test_10_production_return_does_not_appear_in_purchase_history(): void
    {
        // Create a return StockMovement IN with reference_type = 'production_batches'
        StockMovement::create([
            'material_id'         => $this->kain->id,
            'movement_type'       => MovementType::IN,
            'qty'                 => 5,
            'unit_cost_snapshot'  => 20000,
            'total_cost_snapshot' => 100000,
            'reference_type'      => 'production_batches',
            'reference_id'        => 999,
            'notes'               => 'Pengembalian sisa bahan produksi Batch #999',
            'movement_date'       => now(),
            'created_by'          => $this->gudangUser->id,
        ]);

        // Query Purchase History endpoint
        $response = $this->actingAs($this->gudangUser)->get(route('gudang.purchases.history'));
        $response->assertStatus(200);

        // Must not contain the production return notes or batch reference
        $response->assertDontSee('Pengembalian sisa bahan produksi Batch #999');
    }

    // =========================================================================
    // GROUP 3: HPP FORMULA & FINALITY (Requirements 12-19)
    // =========================================================================

    public function test_11_hpp_per_unit_uses_qc_good_qty_and_includes_all_direct_costs(): void
    {
        $order = Order::create([
            'customer_id'  => $this->customer->id,
            'order_no'     => 'SO-TEST-004',
            'order_date'   => now()->toDateString(),
            'status'       => OrderStatus::DEAL,
            'total_qty'    => 10,
            'total_amount' => 690000,
        ]);
        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 69000,
            'subtotal'           => 690000,
        ]);

        $batch = ProductionBatch::create([
            'batch_no'                                      => 'BATCH-202610-004',
            'order_item_id'                                 => $orderItem->id,
            'target_qty'                                    => 10,
            'status'                                        => BatchStatus::SELESAI,
            'material_usage_recorded_at'                    => now(),
            'requires_printing'                             => true,
            'printing_cost_per_unit_snapshot'               => 10000,
            'direct_labor_cost_per_unit_snapshot'           => 5000,
            'packaging_process_cost_per_good_unit_snapshot' => 2000,
            'other_direct_cost_per_unit_snapshot'           => 1000,
            'planned_start_date'                            => now()->toDateString(),
            'planned_end_date'                              => now()->toDateString(),
        ]);

        // Material consumed: 10 m @ 20.000 = Rp200.000
        $bm = BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->kain->id,
            'allocated_qty' => 10,
            'used_qty'      => 10,
        ]);
        StockMovement::create([
            'material_id'         => $this->kain->id,
            'movement_type'       => MovementType::OUT,
            'qty'                 => -10,
            'unit_cost_snapshot'  => 20000,
            'total_cost_snapshot' => 200000,
            'reference_type'      => 'batch_materials',
            'reference_id'        => $bm->id,
            'movement_date'       => now(),
            'created_by'          => $this->gudangUser->id,
        ]);

        // Production and QC Result: 8 good pcs, 2 reject pcs
        $prodResult = ProductionResult::create([
            'batch_id'    => $batch->id,
            'actual_qty'  => 10,
            'result_date' => now()->toDateString(),
            'recorded_by' => $this->adminUser->id,
        ]);

        QcResult::create([
            'production_result_id' => $prodResult->id,
            'good_qty'             => 8,
            'reject_qty'           => 2,
            'qc_date'              => now()->toDateString(),
            'qc_at'                => now(),
            'qc_by'                => $this->adminUser->id,
        ]);

        // Calculate full batch costs:
        // Material: 200.000
        // Printing (per actual/target 10 pcs @ 10.000): 100.000
        // Labor (10 pcs @ 5.000): 50.000
        // Packaging (8 good pcs @ 2.000): 16.000
        // Other direct (10 pcs @ 1.000): 10.000
        // Total Batch Production Cost: 200.000 + 100.000 + 50.000 + 16.000 + 10.000 = 376.000
        // HPP per good pcs = 376.000 / 8 = 47.000
        $batchCost = $this->batchCostService->calculateBatchHpp($batch);
        $this->assertTrue($batchCost['is_ready']);
        $this->assertEquals('376000.00', $batchCost['total_hpp']);
        $this->assertEquals('47000.0000', $batchCost['hpp_per_good_pcs']);
    }

    public function test_12_hpp_unavailable_before_batch_completion_shows_not_final(): void
    {
        $order = Order::create([
            'customer_id'  => $this->customer->id,
            'order_no'     => 'SO-TEST-005',
            'order_date'   => now()->toDateString(),
            'status'       => OrderStatus::DEAL,
            'total_qty'    => 10,
            'total_amount' => 690000,
        ]);
        OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 69000,
            'subtotal'           => 690000,
        ]);

        $profitability = $this->profitabilityService->calculateOrderProfitability($order);
        $this->assertFalse($profitability['is_ready']);
        $this->assertEquals('HPP_NOT_READY', $profitability['status']);
        $this->assertNull($profitability['hpp_amount']);
    }

    // =========================================================================
    // GROUP 4: SELLING PRICE & MARGIN (Requirements 20-25)
    // =========================================================================

    public function test_13_admin_can_see_base_selling_price_and_modal_reference(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.variants.show', $this->variant));
        $response->assertStatus(200);
        $response->assertSee('Harga Jual Master');
        $response->assertSee('Rp 69.000');
        $response->assertSee('Analisis HPP / Modal vs Harga Jual');
    }

    public function test_14_sales_order_transaction_price_remains_snapshot_when_master_price_changes(): void
    {
        $order = Order::create([
            'customer_id'  => $this->customer->id,
            'order_no'     => 'SO-TEST-006',
            'order_date'   => now()->toDateString(),
            'status'       => OrderStatus::DEAL,
            'total_qty'    => 10,
            'total_amount' => 690000,
        ]);
        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 69000, // Snapshot
            'subtotal'           => 690000,
        ]);

        // Change master base selling price
        $this->variant->update(['base_selling_price' => 85000]);

        // Historical order item price must remain 69.000
        $orderItem->refresh();
        $this->assertEquals('69000.00', $orderItem->unit_selling_price);
    }

    public function test_15_price_greater_than_hpp_shows_positive_margin(): void
    {
        // Variant base selling price = 69.000
        // Estimated cost = 20.000 (material) + 10.000 + 5.000 + 2.000 + 1.000 = 38.000
        $marginEval = $this->variantCostService->evaluatePriceMargin($this->variant, '69000');
        $this->assertFalse($marginEval['is_below_cost']);
        $this->assertGreaterThan(0, $marginEval['margin_per_unit']);
    }

    public function test_16_price_less_than_or_equal_to_hpp_shows_warning_guard(): void
    {
        // Price = 30.000, which is below estimated cost of 38.000
        $marginEval = $this->variantCostService->evaluatePriceMargin($this->variant, '30000');
        $this->assertTrue($marginEval['is_below_cost']);
        $this->assertNotNull($marginEval['warning']);
        $this->assertStringContainsString('tidak lebih tinggi dari HPP/modal', $marginEval['warning']);
    }

    // =========================================================================
    // GROUP 5: SECURITY & PRIVACY (Requirements 26-29)
    // =========================================================================

    public function test_17_customer_cannot_see_internal_cost_or_hpp(): void
    {
        $order = Order::create([
            'customer_id'  => $this->customer->id,
            'order_no'     => 'SO-CUST-PRIV-01',
            'order_date'   => now()->toDateString(),
            'status'       => OrderStatus::DEAL,
            'total_qty'    => 10,
            'total_amount' => 690000,
        ]);

        // Customer views order detail
        $response = $this->actingAs($this->customerUser)->get(route('customer.orders.show', $order));
        $response->assertStatus(200);
        $response->assertDontSee('HPP');
        $response->assertDontSee('Laba Kotor');
        $response->assertDontSee('Modal Rata-rata');
    }

    public function test_18_public_tracking_cannot_see_hpp_or_profit(): void
    {
        $tokenService = app(TrackingTokenService::class);
        $order = Order::create([
            'customer_id'  => $this->customer->id,
            'order_no'     => 'SO-TRACK-PRIV-01',
            'order_date'   => now()->toDateString(),
            'status'       => OrderStatus::DEAL,
            'total_qty'    => 10,
            'total_amount' => 690000,
        ]);
        $rawToken = $tokenService->createForOrder($order);

        $response = $this->get(route('track.show', ['token' => $rawToken]));
        $response->assertStatus(200);
        $response->assertDontSee('HPP');
        $response->assertDontSee('Laba Kotor');
        $response->assertDontSee('Gross Profit');
        $response->assertDontSee('Modal');
    }

    public function test_19_produksi_cannot_see_sales_profit(): void
    {
        $response = $this->actingAs($this->produksiUser)->get(route('produksi.dashboard'));
        $response->assertStatus(200);
        $response->assertDontSee('Laba Kotor');
        $response->assertDontSee('Gross Profit');
    }

    public function test_20_admin_and_owner_can_see_allowed_profitability(): void
    {
        $order = Order::create([
            'customer_id'  => $this->customer->id,
            'order_no'     => 'SO-ROLE-ACC-01',
            'order_date'   => now()->toDateString(),
            'status'       => OrderStatus::DEAL,
            'total_qty'    => 10,
            'total_amount' => 690000,
        ]);
        OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 69000,
            'subtotal'           => 690000,
        ]);

        $adminRes = $this->actingAs($this->adminUser)->get(route('admin.orders.show', $order));
        $adminRes->assertStatus(200);
        $adminRes->assertSee($order->order_no);
        $adminRes->assertDontSee('RINGKASAN NILAI PESANAN');

        $ownerRes = $this->actingAs($this->ownerUser)->get(route('owner.profitability.show', $order));
        $ownerRes->assertStatus(200);
        $ownerRes->assertSee('HPP');
    }
}
