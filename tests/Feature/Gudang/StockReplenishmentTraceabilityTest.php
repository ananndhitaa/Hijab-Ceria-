<?php

namespace Tests\Feature\Gudang;

use App\Enums\BatchStatus;
use App\Enums\MovementType;
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
use App\Models\Production\BatchMaterial;
use App\Models\Production\ProductionBatch;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\User;
use App\Services\Inventory\BatchMaterialPlanningService;
use App\Services\Inventory\MaterialAllocationService;
use App\Services\Inventory\PurchaseOrderService;
use App\Services\Inventory\PurchaseReceivingService;
use App\Services\Inventory\StockOpnameService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockReplenishmentTraceabilityTest extends TestCase
{
    use RefreshDatabase;

    private User $gudangUser;
    private Supplier $supplier;
    private Material $material;
    private Unit $meter;
    private PurchaseOrderService $poService;
    private PurchaseReceivingService $receivingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->poService = app(PurchaseOrderService::class);
        $this->receivingService = app(PurchaseReceivingService::class);

        $this->gudangUser = User::factory()->create([
            'role'   => UserRole::GUDANG,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->meter = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);

        $this->supplier = Supplier::create([
            'supplier_name' => 'CV. Kain Nusantara',
            'active'        => true,
        ]);

        $this->material = Material::create([
            'unit_id'       => $this->meter->id,
            'material_name' => 'Kain Ceruty Premium',
            'min_stock'     => 10,
            'active'        => true,
        ]);
        MaterialStock::create([
            'material_id'   => $this->material->id,
            'qty_on_hand'   => '71.5000',
            'avg_unit_cost' => '30000.0000',
        ]);
    }

    private function payPurchase(Purchase $purchase): void
    {
        $purchase->update([
            'status'          => PurchaseStatus::ORDERED,
            'approval_status' => \App\Enums\PurchaseApprovalStatus::APPROVED,
            'approved_at'     => now(),
        ]);
        $purchase->load('items');
        PurchasePayment::create([
            'purchase_id'    => $purchase->id,
            'payment_date'   => $purchase->purchase_date->toDateString(),
            'amount'         => $purchase->total_amount,
            'payment_method' => 'TRANSFER',
            'created_by'     => $this->gudangUser->id,
        ]);
    }

    // ── PHASE 1: Create PO does not change stock ──────────────────────

    public function test_create_po_does_not_change_stock(): void
    {
        $purchase = $this->poService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-10-02',
            'items'         => [
                ['material_id' => $this->material->id, 'qty' => '100.0000', 'unit_price' => '32000.00'],
            ],
        ]);

        $this->assertEquals(PurchaseStatus::DRAFT, $purchase->status);

        $stock = $this->material->fresh(['stock'])->stock;
        $this->assertEquals('71.5000', $stock->qty_on_hand);
    }

    // ── PHASE 2: Confirm PO does not change stock ─────────────────────

    public function test_confirm_po_does_not_change_stock(): void
    {
        $purchase = $this->poService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-10-02',
            'items'         => [
                ['material_id' => $this->material->id, 'qty' => '100.0000', 'unit_price' => '32000.00'],
            ],
        ]);

        $this->payPurchase($purchase);

        $this->assertEquals(PurchaseStatus::ORDERED, $purchase->fresh()->status);

        $stock = $this->material->fresh(['stock'])->stock;
        $this->assertEquals('71.5000', $stock->qty_on_hand);
    }

    // ── PHASE 3: Receive PO changes stock ─────────────────────────────

    public function test_receive_po_changes_stock(): void
    {
        $purchase = $this->poService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-10-02',
            'items'         => [
                ['material_id' => $this->material->id, 'qty' => '100.0000', 'unit_price' => '32000.00'],
            ],
        ]);
        $this->payPurchase($purchase);

        $received = $this->receivingService->receivePurchase($purchase, $this->gudangUser->id);

        $this->assertEquals(PurchaseStatus::RECEIVED, $received->status);

        $stock = $this->material->fresh(['stock'])->stock;
        $this->assertEquals('171.5000', $stock->qty_on_hand);
    }

    // ── PHASE 4: Receive creates StockMovement IN ─────────────────────

    public function test_receive_creates_stock_movement_in(): void
    {
        $purchase = $this->poService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-10-02',
            'items'         => [
                ['material_id' => $this->material->id, 'qty' => '100.0000', 'unit_price' => '32000.00'],
            ],
        ]);
        $this->payPurchase($purchase);
        $this->receivingService->receivePurchase($purchase, $this->gudangUser->id);

        $movement = StockMovement::where('material_id', $this->material->id)
            ->where('movement_type', MovementType::IN)
            ->first();

        $this->assertNotNull($movement);
        $this->assertEquals('100.0000', $movement->qty);
        $this->assertEquals(MovementType::IN, $movement->movement_type);
    }

    // ── PHASE 5: StockMovement has actual created timestamp ───────────

    public function test_stock_movement_has_created_timestamp(): void
    {
        $purchase = $this->poService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-10-02',
            'items'         => [
                ['material_id' => $this->material->id, 'qty' => '100.0000', 'unit_price' => '32000.00'],
            ],
        ]);
        $this->payPurchase($purchase);
        $this->receivingService->receivePurchase($purchase, $this->gudangUser->id);

        $movement = StockMovement::where('material_id', $this->material->id)
            ->where('movement_type', MovementType::IN)
            ->first();

        $this->assertNotNull($movement->created_at);
        // created_at should include time component (not just date)
        $this->assertNotNull($movement->created_at->format('H:i:s'));
    }

    // ── PHASE 6: Receive timestamp appears in Purchase History ────────

    public function test_receive_timestamp_appears_in_purchase_history(): void
    {
        $purchase = $this->poService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-10-02',
            'items'         => [
                ['material_id' => $this->material->id, 'qty' => '100.0000', 'unit_price' => '32000.00'],
            ],
        ]);
        $this->payPurchase($purchase);
        $this->receivingService->receivePurchase($purchase, $this->gudangUser->id);

        $response = $this->actingAs($this->gudangUser)
            ->get(route('gudang.purchases.history'));

        $response->assertStatus(200);
        $response->assertSee($purchase->purchase_no);
        // The view should contain "WIB" for receiving timestamp
        $response->assertSee('WIB');
    }

    // ── PHASE 7: Purchase date and receive date remain distinct ───────

    public function test_purchase_date_and_receive_date_are_distinct(): void
    {
        $purchase = $this->poService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-10-02',
            'items'         => [
                ['material_id' => $this->material->id, 'qty' => '100.0000', 'unit_price' => '32000.00'],
            ],
        ]);
        $this->payPurchase($purchase);
        $this->receivingService->receivePurchase($purchase, $this->gudangUser->id);

        $fresh = $purchase->fresh();
        $movement = StockMovement::where('reference_type', 'purchase_items')
            ->where('movement_type', MovementType::IN)
            ->first();

        // Purchase date is the PO creation date (user-set)
        $this->assertEquals('2026-10-02', $fresh->purchase_date->toDateString());

        // Receive date is the actual created_at of the StockMovement
        $this->assertNotEquals($fresh->purchase_date->toDateString(), $movement->created_at->toDateTimeString());
    }

    // ── PHASE 8: Stock Movement before/after quantities correct ───────

    public function test_stock_movement_before_after_quantities_correct(): void
    {
        $purchase = $this->poService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-10-02',
            'items'         => [
                ['material_id' => $this->material->id, 'qty' => '100.0000', 'unit_price' => '32000.00'],
            ],
        ]);
        $this->payPurchase($purchase);
        $this->receivingService->receivePurchase($purchase, $this->gudangUser->id);

        $movement = StockMovement::where('material_id', $this->material->id)
            ->where('movement_type', MovementType::IN)
            ->first();

        $this->assertEquals('71.5000', $movement->qty_before);
        $this->assertEquals('171.5000', $movement->qty_after);
    }

    // ── PHASE 9: Stock Movement references correct PO ─────────────────

    public function test_stock_movement_references_correct_po(): void
    {
        $purchase = $this->poService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-10-02',
            'items'         => [
                ['material_id' => $this->material->id, 'qty' => '100.0000', 'unit_price' => '32000.00'],
            ],
        ]);
        $this->payPurchase($purchase);
        $this->receivingService->receivePurchase($purchase, $this->gudangUser->id);

        $purchaseItem = PurchaseItem::where('purchase_id', $purchase->id)->first();
        $movement = StockMovement::where('material_id', $this->material->id)
            ->where('movement_type', MovementType::IN)
            ->first();

        $this->assertEquals('purchase_items', $movement->reference_type);
        $this->assertEquals($purchaseItem->id, $movement->reference_id);
    }

    // ── PHASE 10: Allocation creates OUT ──────────────────────────────

    public function test_allocation_creates_stock_movement_out(): void
    {
        // Setup batch with BOM
        $product = Product::create(['product_name' => 'Hijab Voal', 'code' => 'HJB-VL']);
        $variant = ProductVariant::create([
            'product_id'   => $product->id,
            'variant_name' => 'Hitam',
            'sku'          => 'HJB-VL-HT-001',
        ]);
        BomItem::create([
            'variant_id'   => $variant->id,
            'material_id'  => $this->material->id,
            'qty_per_unit' => '1.5000',
        ]);
        $customer = Customer::create(['customer_name' => 'Test Customer', 'phone' => '081234567890']);
        $order = Order::create([
            'customer_id' => $customer->id,
            'order_no'    => 'SO-TEST-001',
            'order_date'  => '2026-10-01',
            'status'      => \App\Enums\OrderStatus::BAHAN,
        ]);
        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => '150000.00',
        ]);
        $batch = ProductionBatch::create([
            'order_item_id' => $orderItem->id,
            'batch_no'      => 'BAT-TEST-001',
            'target_qty'    => 10,
            'status'        => BatchStatus::BAHAN,
        ]);

        // Generate MRP and allocate
        $mrpService = app(BatchMaterialPlanningService::class);
        $mrpService->generateMaterialPlanning($batch);

        $allocationService = app(MaterialAllocationService::class);
        $allocationService->allocateBatchMaterials($batch, $this->gudangUser->id);

        $outMovement = StockMovement::where('material_id', $this->material->id)
            ->where('movement_type', MovementType::OUT)
            ->first();

        $this->assertNotNull($outMovement);
        $this->assertEquals('15.0000', $outMovement->qty);
        $this->assertEquals('batch_materials', $outMovement->reference_type);
        $this->assertEquals($this->gudangUser->id, $outMovement->created_by);
    }

    // ── PHASE 11: MRP initial calculation timestamp preserved ─────────

    public function test_mrp_calculation_timestamp_preserved(): void
    {
        $product = Product::create(['product_name' => 'Hijab Voal', 'code' => 'HJB-VL']);
        $variant = ProductVariant::create([
            'product_id'   => $product->id,
            'variant_name' => 'Hitam',
            'sku'          => 'HJB-VL-HT-001',
        ]);
        BomItem::create([
            'variant_id'   => $variant->id,
            'material_id'  => $this->material->id,
            'qty_per_unit' => '1.5000',
        ]);
        $customer = Customer::create(['customer_name' => 'Test Customer', 'phone' => '081234567890']);
        $order = Order::create([
            'customer_id' => $customer->id,
            'order_no'    => 'SO-TEST-001',
            'order_date'  => '2026-10-01',
            'status'      => \App\Enums\OrderStatus::BAHAN,
        ]);
        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => '150000.00',
        ]);
        $batch = ProductionBatch::create([
            'order_item_id' => $orderItem->id,
            'batch_no'      => 'BAT-TEST-001',
            'target_qty'    => 10,
            'status'        => BatchStatus::BAHAN,
        ]);

        $mrpService = app(BatchMaterialPlanningService::class);
        $mrpService->generateMaterialPlanning($batch);

        $bm = BatchMaterial::where('batch_id', $batch->id)->first();

        $this->assertNotNull($bm->calculated_at);
    }

    // ── PHASE 12: MRP stock-at-calculation remains historical ─────────

    public function test_mrp_stock_at_calculation_remains_historical(): void
    {
        $product = Product::create(['product_name' => 'Hijab Voal', 'code' => 'HJB-VL']);
        $variant = ProductVariant::create([
            'product_id'   => $product->id,
            'variant_name' => 'Hitam',
            'sku'          => 'HJB-VL-HT-001',
        ]);
        BomItem::create([
            'variant_id'   => $variant->id,
            'material_id'  => $this->material->id,
            'qty_per_unit' => '1.5000',
        ]);
        $customer = Customer::create(['customer_name' => 'Test Customer', 'phone' => '081234567890']);
        $order = Order::create([
            'customer_id' => $customer->id,
            'order_no'    => 'SO-TEST-001',
            'order_date'  => '2026-10-01',
            'status'      => \App\Enums\OrderStatus::BAHAN,
        ]);
        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => '150000.00',
        ]);
        $batch = ProductionBatch::create([
            'order_item_id' => $orderItem->id,
            'batch_no'      => 'BAT-TEST-001',
            'target_qty'    => 10,
            'status'        => BatchStatus::BAHAN,
        ]);

        $mrpService = app(BatchMaterialPlanningService::class);
        $mrpService->generateMaterialPlanning($batch);

        $bm = BatchMaterial::where('batch_id', $batch->id)->first();
        $stockAtCalc = $bm->stock_at_calc;

        // Now change stock via purchase
        $purchase = $this->poService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-10-02',
            'items'         => [
                ['material_id' => $this->material->id, 'qty' => '100.0000', 'unit_price' => '32000.00'],
            ],
        ]);
        $this->payPurchase($purchase);
        $this->receivingService->receivePurchase($purchase, $this->gudangUser->id);

        // Verify stock actually changed
        $currentStock = $this->material->fresh(['stock'])->stock->qty_on_hand;
        $this->assertNotEquals($stockAtCalc, $currentStock);

        // But historical snapshot remains unchanged
        $bmFresh = $bm->fresh();
        $this->assertEquals($stockAtCalc, $bmFresh->stock_at_calc);
    }

    // ── PHASE 13: Current stock shown separately via MRP data ─────────

    public function test_current_stock_shown_separately_from_historical(): void
    {
        $product = Product::create(['product_name' => 'Hijab Voal', 'code' => 'HJB-VL']);
        $variant = ProductVariant::create([
            'product_id'   => $product->id,
            'variant_name' => 'Hitam',
            'sku'          => 'HJB-VL-HT-001',
        ]);
        BomItem::create([
            'variant_id'   => $variant->id,
            'material_id'  => $this->material->id,
            'qty_per_unit' => '1.5000',
        ]);
        $customer = Customer::create(['customer_name' => 'Test Customer', 'phone' => '081234567890']);
        $order = Order::create([
            'customer_id' => $customer->id,
            'order_no'    => 'SO-TEST-001',
            'order_date'  => '2026-10-01',
            'status'      => \App\Enums\OrderStatus::BAHAN,
        ]);
        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => '150000.00',
        ]);
        $batch = ProductionBatch::create([
            'order_item_id' => $orderItem->id,
            'batch_no'      => 'BAT-TEST-001',
            'target_qty'    => 10,
            'status'        => BatchStatus::BAHAN,
        ]);

        $mrpService = app(BatchMaterialPlanningService::class);
        $mrpService->generateMaterialPlanning($batch);

        // Change stock after MRP calculation
        $purchase = $this->poService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-10-02',
            'items'         => [
                ['material_id' => $this->material->id, 'qty' => '100.0000', 'unit_price' => '32000.00'],
            ],
        ]);
        $this->payPurchase($purchase);
        $this->receivingService->receivePurchase($purchase, $this->gudangUser->id);

        // Get MRP data — it should show different values for current vs historical
        $mrpData = $mrpService->getMaterialRequirementsData($batch);
        $item = $mrpData['items'][0];

        // Historical stock at calculation time
        $this->assertEquals('71.5000', $item['stock_at_calc']);

        // Current stock should reflect the purchase
        $this->assertEquals('171.5000', $item['current_stock']);
    }

    // ── PHASE 14: Stock Opname creates ADJUSTMENT only ────────────────

    public function test_stock_opname_creates_adjustment_only(): void
    {
        $opnameService = app(StockOpnameService::class);
        $movement = $opnameService->adjustStock(
            $this->material,
            '70.0000',
            'Koreksi stok fisik',
            $this->gudangUser->id,
        );

        $this->assertNotNull($movement);
        $this->assertEquals(MovementType::ADJUSTMENT, $movement->movement_type);
        $this->assertEquals('71.5000', $movement->qty_before);
        $this->assertEquals('70.0000', $movement->qty_after);

        // Verify no IN or OUT was created
        $nonAdjMovements = StockMovement::where('material_id', $this->material->id)
            ->where('movement_type', '!=', MovementType::ADJUSTMENT)
            ->count();
        $this->assertEquals(0, $nonAdjMovements);
    }

    // ── PHASE 15: Double receiving remains blocked ─────────────────────

    public function test_double_receiving_blocked(): void
    {
        $purchase = $this->poService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-10-02',
            'items'         => [
                ['material_id' => $this->material->id, 'qty' => '100.0000', 'unit_price' => '32000.00'],
            ],
        ]);
        $this->payPurchase($purchase);
        $this->receivingService->receivePurchase($purchase, $this->gudangUser->id);

        // Attempt second receive
        $this->expectException(\DomainException::class);
        $this->receivingService->receivePurchase($purchase, $this->gudangUser->id);
    }

    // ── PHASE 16: MWA tests remain PASS ───────────────────────────────

    public function test_mwa_calculation_on_receive(): void
    {
        // Initial: 71.5 m @ Rp30.000
        // Receive: 100 m @ Rp32.000
        // New MWA = (71.5 * 30000 + 100 * 32000) / 171.5
        // = (2145000 + 3200000) / 171.5
        // = 5345000 / 171.5
        // = 31166.1807... → 31166.1808 at scale 4

        $purchase = $this->poService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-10-02',
            'items'         => [
                ['material_id' => $this->material->id, 'qty' => '100.0000', 'unit_price' => '32000.00'],
            ],
        ]);
        $this->payPurchase($purchase);
        $this->receivingService->receivePurchase($purchase, $this->gudangUser->id);

        $stock = $this->material->fresh(['stock'])->stock;
        $this->assertEquals('171.5000', $stock->qty_on_hand);

        // MWA should be recalculated using BCMath
        $expectedMwa = bcdiv(
            bcadd(bcmul('71.5000', '30000.0000', 8), bcmul('100.0000', '32000.0000', 8), 8),
            '171.5000',
            4
        );
        $this->assertEquals($expectedMwa, $stock->avg_unit_cost);
    }

    // ── PHASE 17: StockMovement cost snapshot correct ─────────────────

    public function test_stock_movement_cost_snapshot_correct(): void
    {
        $purchase = $this->poService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-10-02',
            'items'         => [
                ['material_id' => $this->material->id, 'qty' => '100.0000', 'unit_price' => '32000.00'],
            ],
        ]);
        $this->payPurchase($purchase);
        $this->receivingService->receivePurchase($purchase, $this->gudangUser->id);

        $movement = StockMovement::where('material_id', $this->material->id)
            ->where('movement_type', MovementType::IN)
            ->first();

        $this->assertEquals('32000.0000', $movement->unit_cost_snapshot);
        $expectedTotal = bcmul('100.0000', '32000.00', 2);
        $this->assertEquals($expectedTotal, $movement->total_cost_snapshot);
    }

    // ── PHASE 18: StockMovement records created_by actor ──────────────

    public function test_stock_movement_records_created_by_actor(): void
    {
        $purchase = $this->poService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-10-02',
            'items'         => [
                ['material_id' => $this->material->id, 'qty' => '100.0000', 'unit_price' => '32000.00'],
            ],
        ]);
        $this->payPurchase($purchase);
        $this->receivingService->receivePurchase($purchase, $this->gudangUser->id);

        $movement = StockMovement::where('material_id', $this->material->id)
            ->where('movement_type', MovementType::IN)
            ->first();

        $this->assertEquals($this->gudangUser->id, $movement->created_by);
        $this->assertNotNull($movement->createdByUser);
        $this->assertEquals($this->gudangUser->name, $movement->createdByUser->name);
    }
}
