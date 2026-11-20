<?php

namespace Tests\Unit\Finance;

use App\Enums\BatchStatus;
use App\Enums\CostType;
use App\Enums\MovementType;
use App\Models\Finance\BatchCost;
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
use App\Models\System\User;
use App\Services\Finance\OrderHppCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderHppCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderHppCalculationService $orderHppService;
    private User $admin;
    private Material $kain;
    private Order $order;
    private OrderItem $orderItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderHppService = app(OrderHppCalculationService::class);

        $this->admin = User::factory()->create();

        $unit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $this->kain = Material::create([
            'material_name' => 'Kain Premium',
            'unit_id'       => $unit->id,
            'cost_per_unit' => 20000,
            'active'        => true,
        ]);
        MaterialStock::create([
            'material_id'   => $this->kain->id,
            'qty_on_hand'   => 1000,
            'avg_unit_cost' => 20000,
        ]);

        $cust = Customer::create(['customer_name' => 'Cust Order HPP', 'phone' => '081122334455']);
        $this->order = Order::create([
            'customer_id'  => $cust->id,
            'order_no'     => 'ORD-202609-OH1',
            'order_date'   => now()->toDateString(),
            'status'       => 'DEAL',
            'total_qty'    => 20,
            'total_amount' => 2000000,
        ]);

        $product = Product::create(['product_name' => 'Hijab Silk']);
        $variant = ProductVariant::create([
            'product_id'   => $product->id,
            'variant_name' => 'Emerald',
            'sku'          => 'HJB-EMR',
        ]);

        $this->orderItem = OrderItem::create([
            'order_id'           => $this->order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 20,
            'unit_selling_price' => 100000,
            'subtotal'           => 2000000,
        ]);
    }

    private function createCompletedBatchWithCost(
        OrderItem $item,
        string $batchNo,
        int $targetQty,
        int $actualQty,
        int $goodQty,
        int $rejectQty,
        float $materialQtyUsed,
        float $materialUnitSnapshot,
        array $directCosts = []
    ): ProductionBatch {
        $laborAmt = (float) ($directCosts[CostType::DIRECT_LABOR->value] ?? 0);
        $packAmt  = (float) ($directCosts[CostType::PACKAGING->value] ?? 0);
        $printAmt = (float) ($directCosts[CostType::PRINTING->value] ?? 0);
        $otherAmt = (float) ($directCosts[CostType::OTHER_DIRECT->value] ?? 0);

        $laborSnapshot = $actualQty > 0 ? ($laborAmt / $actualQty) : 0;
        $packSnapshot  = $goodQty > 0 ? ($packAmt / $goodQty) : 0;
        $printSnapshot = $actualQty > 0 ? ($printAmt / $actualQty) : 0;
        $otherSnapshot = $actualQty > 0 ? ($otherAmt / $actualQty) : 0;

        $batch = ProductionBatch::create([
            'order_item_id'                                 => $item->id,
            'batch_no'                                      => $batchNo,
            'target_qty'                                    => $targetQty,
            'status'                                        => BatchStatus::SELESAI,
            'requires_printing'                             => $printAmt > 0,
            'printing_cost_per_unit_snapshot'               => number_format($printSnapshot, 2, '.', ''),
            'direct_labor_cost_per_unit_snapshot'           => number_format($laborSnapshot, 2, '.', ''),
            'packaging_process_cost_per_good_unit_snapshot' => number_format($packSnapshot, 2, '.', ''),
            'other_direct_cost_per_unit_snapshot'           => number_format($otherSnapshot, 2, '.', ''),
            'material_usage_recorded_at'                    => now(),
        ]);

        $bm = BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->kain->id,
            'required_qty'  => $materialQtyUsed,
            'allocated_qty' => $materialQtyUsed,
            'used_qty'      => $materialQtyUsed,
        ]);

        StockMovement::create([
            'material_id'         => $this->kain->id,
            'movement_type'       => MovementType::OUT,
            'qty'                 => $materialQtyUsed,
            'unit_cost_snapshot'  => number_format($materialUnitSnapshot, 4, '.', ''),
            'total_cost_snapshot' => number_format($materialQtyUsed * $materialUnitSnapshot, 2, '.', ''),
            'reference_type'      => 'batch_materials',
            'reference_id'        => $bm->id,
            'movement_date'       => now()->toDateString(),
        ]);

        $res = ProductionResult::create([
            'batch_id'    => $batch->id,
            'actual_qty'  => $actualQty,
            'result_date' => now()->toDateString(),
        ]);

        QcResult::create([
            'production_result_id' => $res->id,
            'good_qty'             => $goodQty,
            'reject_qty'           => $rejectQty,
            'qc_date'              => now()->toDateString(),
        ]);

        return $batch;
    }

    public function test_single_completed_ready_batch_equals_order_hpp(): void
    {
        // 20 pcs target, 20 actual, 20 good, 0 reject
        // Material: 20 * 20000 = 400000
        // Direct Labor: 100000, Packaging: 20000 => Direct: 120000
        // Total HPP: 520000
        $this->createCompletedBatchWithCost(
            $this->orderItem,
            'BAT-202609-001',
            20,
            20,
            20,
            0,
            20,
            20000,
            [
                CostType::DIRECT_LABOR->value => '100000.00',
                CostType::PACKAGING->value    => '20000.00',
            ]
        );

        $result = $this->orderHppService->calculateOrderHpp($this->order);

        $this->assertTrue($result['is_ready']);
        $this->assertEquals('READY', $result['status']);
        $this->assertEquals('520000.00', $result['total_hpp']);
        $this->assertEquals('400000.00', $result['total_material_cost']);
        $this->assertEquals('120000.00', $result['total_direct_cost']);
        $this->assertEquals('20.0000', $result['total_good_qty']);
        // 520000 / 20 = 26000
        $this->assertEquals('26000.0000', $result['hpp_per_good_pcs']);
        $this->assertEquals(1, $result['batches_count']);
    }

    public function test_split_batches_sum_exactly_for_order_hpp(): void
    {
        // Split Batch 1: target 10, good 10. Material: 200000, Labor: 50000 => HPP: 250000
        $this->createCompletedBatchWithCost(
            $this->orderItem,
            'BAT-202609-001-A',
            10,
            10,
            10,
            0,
            10,
            20000,
            [CostType::DIRECT_LABOR->value => '50000.00']
        );

        // Split Batch 2: target 10, good 10. Material: 200000, Labor: 60000 => HPP: 260000
        $this->createCompletedBatchWithCost(
            $this->orderItem,
            'BAT-202609-001-B',
            10,
            10,
            10,
            0,
            10,
            20000,
            [CostType::DIRECT_LABOR->value => '60000.00']
        );

        $result = $this->orderHppService->calculateOrderHpp($this->order);

        $this->assertTrue($result['is_ready']);
        $this->assertEquals('510000.00', $result['total_hpp']);
        $this->assertEquals('400000.00', $result['total_material_cost']);
        $this->assertEquals('110000.00', $result['total_direct_cost']);
        $this->assertEquals('20.0000', $result['total_good_qty']);
        // 510000 / 20 = 25500
        $this->assertEquals('25500.0000', $result['hpp_per_good_pcs']);
        $this->assertEquals(2, $result['batches_count']);
    }

    public function test_replacement_production_batch_and_reject_costs_are_included(): void
    {
        // Batch 1 (Underproduced / Rejected): target 20, actual 20, good 18, reject 2
        // Material: 20 * 20000 = 400000, Labor: 100000 => HPP: 500000
        $this->createCompletedBatchWithCost(
            $this->orderItem,
            'BAT-202609-001-ORIG',
            20,
            20,
            18,
            2,
            20,
            20000,
            [CostType::DIRECT_LABOR->value => '100000.00']
        );

        // Replacement Batch: target 2, good 2, reject 0
        // Material: 2 * 20000 = 40000, Labor: 15000 => HPP: 55000
        $this->createCompletedBatchWithCost(
            $this->orderItem,
            'BAT-202609-001-REPL',
            2,
            2,
            2,
            0,
            2,
            20000,
            [CostType::DIRECT_LABOR->value => '15000.00']
        );

        $result = $this->orderHppService->calculateOrderHpp($this->order);

        $this->assertTrue($result['is_ready']);
        // Total HPP = 500000 + 55000 = 555000
        $this->assertEquals('555000.00', $result['total_hpp']);
        $this->assertEquals('440000.00', $result['total_material_cost']);
        $this->assertEquals('115000.00', $result['total_direct_cost']);
        $this->assertEquals('20.0000', $result['total_good_qty']);
        // 555000 / 20 = 27750
        $this->assertEquals('27750.0000', $result['hpp_per_good_pcs']);
    }

    public function test_all_reject_original_and_replacement_batches_both_contribute_to_order_hpp(): void
    {
        // Batch 1 (All Reject): target 20, actual 20, good 0, reject 20. HPP = 400000
        $this->createCompletedBatchWithCost(
            $this->orderItem,
            'BAT-202609-001-FAIL',
            20,
            20,
            0,
            20,
            20,
            20000,
            []
        );

        // Batch 2 (Replacement): target 20, actual 20, good 20, reject 0. HPP = 450000 (400k mat + 50k labor)
        $this->createCompletedBatchWithCost(
            $this->orderItem,
            'BAT-202609-001-PASS',
            20,
            20,
            20,
            0,
            20,
            20000,
            [CostType::DIRECT_LABOR->value => '50000.00']
        );

        $result = $this->orderHppService->calculateOrderHpp($this->order);

        $this->assertTrue($result['is_ready']);
        // Total HPP = 400000 + 450000 = 850000
        $this->assertEquals('850000.00', $result['total_hpp']);
        $this->assertEquals('20.0000', $result['total_good_qty']);
        // 850000 / 20 = 42500
        $this->assertEquals('42500.0000', $result['hpp_per_good_pcs']);
    }

    public function test_cancelled_batch_is_strictly_excluded_from_order_hpp(): void
    {
        // 1. Valid Completed Batch: target 20, good 20. HPP = 500000
        $this->createCompletedBatchWithCost(
            $this->orderItem,
            'BAT-202609-VALID',
            20,
            20,
            20,
            0,
            20,
            20000,
            [CostType::DIRECT_LABOR->value => '100000.00']
        );

        // 2. Cancelled Batch: target 20 (status = CANCELLED)
        $cancelledBatch = ProductionBatch::create([
            'order_item_id'                                 => $this->orderItem->id,
            'batch_no'                                      => 'BAT-202609-CANCELLED',
            'target_qty'                                    => 20,
            'status'                                        => BatchStatus::CANCELLED,
            'printing_cost_per_unit_snapshot'               => '9999.00',
            'direct_labor_cost_per_unit_snapshot'           => '9999.00',
            'packaging_process_cost_per_good_unit_snapshot' => '9999.00',
            'other_direct_cost_per_unit_snapshot'           => '9999.00',
        ]);

        $result = $this->orderHppService->calculateOrderHpp($this->order);

        $this->assertTrue($result['is_ready']);
        $this->assertEquals('500000.00', $result['total_hpp']);
        $this->assertCount(1, $result['batches']);
        $this->assertCount(1, $result['cancelled_batches']);
    }

    public function test_order_hpp_is_not_ready_if_any_active_batch_is_incomplete(): void
    {
        // Batch 1: Completed & Ready
        $this->createCompletedBatchWithCost(
            $this->orderItem,
            'BAT-202609-001',
            10,
            10,
            10,
            0,
            10,
            20000
        );

        // Batch 2: Still in PRODUKSI
        ProductionBatch::create([
            'order_item_id' => $this->orderItem->id,
            'batch_no'      => 'BAT-202609-002',
            'target_qty'    => 10,
            'status'        => BatchStatus::PRODUKSI,
        ]);

        $result = $this->orderHppService->calculateOrderHpp($this->order);

        $this->assertFalse($result['is_ready']);
        $this->assertNull($result['total_hpp']);
        $this->assertEquals('ORDER_BATCHES_NOT_READY', $result['status']);
    }

    public function test_order_hpp_is_not_ready_when_production_fulfillment_remains_unplanned(): void
    {
        // Ordered: 20 pcs. Only batch 1 is created for 10 pcs (completed).
        // Remaining to plan = 10 pcs
        $this->createCompletedBatchWithCost(
            $this->orderItem,
            'BAT-202609-001',
            10,
            10,
            10,
            0,
            10,
            20000
        );

        $result = $this->orderHppService->calculateOrderHpp($this->order);

        $this->assertFalse($result['is_ready']);
        $this->assertNull($result['total_hpp']);
        $this->assertEquals('ORDER_FULFILLMENT_REMAINING', $result['status']);
        $this->assertStringContainsString('belum terpenuhi', $result['error']);
    }

    public function test_all_reject_order_produces_valid_total_hpp_and_null_hpp_per_good_pcs(): void
    {
        // Sample/Trial order where qty_ordered = 0, target batch = 20, actual = 20, good = 0, reject = 20
        $this->orderItem->update(['qty_ordered' => 0]);
        $this->order->update(['total_qty' => 0]);

        $this->createCompletedBatchWithCost(
            $this->orderItem,
            'BAT-202609-FAIL-ALL',
            20,
            20,
            0,
            20,
            20,
            20000
        );

        $result = $this->orderHppService->calculateOrderHpp($this->order);

        $this->assertTrue($result['is_ready']);
        $this->assertEquals('400000.00', $result['total_hpp']);
        $this->assertEquals('0.0000', $result['total_good_qty']);
        $this->assertNull($result['hpp_per_good_pcs']);
    }

    public function test_good_surplus_uses_actual_total_good_qty_as_denominator(): void
    {
        // Ordered: 20 pcs. Produced 22 good pcs.
        // Material: 22 * 20000 = 440000, Labor: 660000 => HPP = 1100000
        $this->createCompletedBatchWithCost(
            $this->orderItem,
            'BAT-202609-SURPLUS',
            22,
            22,
            22,
            0,
            22,
            20000,
            [CostType::DIRECT_LABOR->value => '660000.00']
        );

        $result = $this->orderHppService->calculateOrderHpp($this->order);

        $this->assertTrue($result['is_ready']);
        $this->assertEquals('1100000.00', $result['total_hpp']);
        $this->assertEquals('22.0000', $result['total_good_qty']);
        // 1100000 / 22 = 50000
        $this->assertEquals('50000.0000', $result['hpp_per_good_pcs']);

        // Invariant: Order total_qty remains untouched
        $this->order->refresh();
        $this->assertEquals(20, $this->order->total_qty);
    }
}
