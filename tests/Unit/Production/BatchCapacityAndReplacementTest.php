<?php

namespace Tests\Unit\Production;

use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
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
use App\Services\Production\BatchService;
use App\Services\Sales\OrderStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchCapacityAndReplacementTest extends TestCase
{
    use RefreshDatabase;

    private BatchService $batchService;
    private OrderStatusService $statusService;
    private User $adminUser;
    private Customer $customer;
    private Product $product;
    private ProductVariant $variant;
    private Material $kain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->batchService = app(BatchService::class);
        $this->statusService = app(OrderStatusService::class);

        $this->adminUser = User::factory()->create([
            'role'   => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->customer = Customer::create([
            'customer_name' => 'Customer Test',
            'phone'         => '08123456789',
        ]);

        $unit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $this->kain = Material::create([
            'material_name' => 'Kain Katun',
            'unit_id'       => $unit->id,
            'cost_per_unit' => 20000,
            'active'        => true,
        ]);

        $this->product = Product::create([
            'product_name' => 'Hijab Segiempat',
            'active'       => true,
        ]);

        $this->variant = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'Navy',
            'sku'                                  => 'HJB-SE-NVY',
            'requires_printing'                    => false,
            'direct_labor_cost_per_unit'           => '5000.00',
            'packaging_process_cost_per_good_unit' => '1000.00',
            'other_direct_cost_per_unit'           => '500.00',
            'active'                               => true,
        ]);

        // Master BOM recipe: 2 meters per piece
        \App\Models\Master\BomItem::create([
            'variant_id'   => $this->variant->id,
            'material_id'  => $this->kain->id,
            'qty_per_unit' => 2.0000,
        ]);
    }

    private function createOrderWithItem(float $qtyOrdered = 20): array
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'ORD-' . uniqid(),
            'order_date'  => now()->toDateString(),
            'status'      => OrderStatus::DEAL,
        ]);

        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => $qtyOrdered,
            'unit_selling_price' => 50000,
            'subtotal'           => $qtyOrdered * 50000,
        ]);

        $inv = \App\Models\Sales\Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-' . uniqid(),
            'invoice_date' => now()->toDateString(),
            'total_amount' => (string) ($qtyOrdered * 50000),
            'status'       => \App\Enums\InvoiceStatus::PARTIAL,
        ]);
        \App\Models\Sales\Payment::create([
            'invoice_id'   => $inv->id,
            'payment_no'   => 'PAY-' . uniqid(),
            'payment_date' => now()->toDateString(),
            'amount'       => (string) ($qtyOrdered * 25000),
            'payment_type' => \App\Enums\PaymentType::DP,
            'status'       => \App\Enums\PaymentStatus::CONFIRMED,
        ]);

        return [$order, $item];
    }

    public function test_scenario_a_ordered_20_no_batches_remaining_to_plan_is_20(): void
    {
        [, $item] = $this->createOrderWithItem(20);

        $coverage = $this->batchService->calculateProductionCoverage($item);

        $this->assertEquals('20.0000', $coverage['ordered_qty']);
        $this->assertEquals('0.0000', $coverage['confirmed_good_qty']);
        $this->assertEquals('0.0000', $coverage['pending_production_qty']);
        $this->assertEquals('0.0000', $coverage['production_coverage']);
        $this->assertEquals('20.0000', $coverage['remaining_to_plan']);
        $this->assertEquals('20.0000', $this->batchService->calculateRemainingQty($item));
    }

    public function test_scenario_b_active_batch_target_20_pre_qc_remaining_to_plan_is_0(): void
    {
        [, $item] = $this->createOrderWithItem(20);

        ProductionBatch::create([
            'order_item_id'     => $item->id,
            'batch_no'          => 'BAT-001',
            'target_qty'        => 20,
            'status'            => BatchStatus::PRODUKSI,
            'requires_printing' => false,
        ]);

        $coverage = $this->batchService->calculateProductionCoverage($item);

        $this->assertEquals('0.0000', $coverage['confirmed_good_qty']);
        $this->assertEquals('20.0000', $coverage['pending_production_qty']);
        $this->assertEquals('20.0000', $coverage['production_coverage']);
        $this->assertEquals('0.0000', $coverage['remaining_to_plan']);
        $this->assertEquals('0.0000', $this->batchService->calculateRemainingQty($item));
    }

    public function test_scenario_c_batch_target_20_qc_good_18_reject_2_remaining_to_plan_is_2(): void
    {
        [, $item] = $this->createOrderWithItem(20);

        $batch = ProductionBatch::create([
            'order_item_id'     => $item->id,
            'batch_no'          => 'BAT-001',
            'target_qty'        => 20,
            'status'            => BatchStatus::SELESAI,
            'requires_printing' => false,
        ]);

        $result = ProductionResult::create([
            'batch_id'    => $batch->id,
            'actual_qty'  => 20,
            'result_date' => now()->toDateString(),
            'recorded_by' => $this->adminUser->id,
        ]);

        QcResult::create([
            'production_result_id' => $result->id,
            'good_qty'             => 18,
            'reject_qty'           => 2,
            'qc_date'              => now()->toDateString(),
            'qc_by'                => $this->adminUser->id,
        ]);

        $coverage = $this->batchService->calculateProductionCoverage($item);

        $this->assertEquals('18.0000', $coverage['confirmed_good_qty']);
        $this->assertEquals('0.0000', $coverage['pending_production_qty']);
        $this->assertEquals('18.0000', $coverage['production_coverage']);
        $this->assertEquals('2.0000', $coverage['remaining_to_plan']);
        $this->assertEquals('2.0000', $coverage['total_reject_qty']);
        $this->assertEquals('2.0000', $this->batchService->calculateRemainingQty($item));
    }

    public function test_scenario_d_replacement_batch_target_2_exists_pre_qc_remaining_to_plan_is_0(): void
    {
        [, $item] = $this->createOrderWithItem(20);

        // Batch 1: Completed with Good 18, Reject 2
        $batch1 = ProductionBatch::create([
            'order_item_id'     => $item->id,
            'batch_no'          => 'BAT-001',
            'target_qty'        => 20,
            'status'            => BatchStatus::SELESAI,
            'requires_printing' => false,
        ]);
        $res1 = ProductionResult::create([
            'batch_id'    => $batch1->id,
            'actual_qty'  => 20,
            'result_date' => now()->toDateString(),
            'recorded_by' => $this->adminUser->id,
        ]);
        QcResult::create([
            'production_result_id' => $res1->id,
            'good_qty'             => 18,
            'reject_qty'           => 2,
            'qc_date'              => now()->toDateString(),
            'qc_by'                => $this->adminUser->id,
        ]);

        // Batch 2 (Replacement): Target 2, in BAHAN/PRODUKSI (pre-QC)
        ProductionBatch::create([
            'order_item_id'     => $item->id,
            'batch_no'          => 'BAT-002',
            'target_qty'        => 2,
            'status'            => BatchStatus::PRODUKSI,
            'requires_printing' => false,
        ]);

        $coverage = $this->batchService->calculateProductionCoverage($item);

        $this->assertEquals('18.0000', $coverage['confirmed_good_qty']);
        $this->assertEquals('2.0000', $coverage['pending_production_qty']);
        $this->assertEquals('20.0000', $coverage['production_coverage']);
        $this->assertEquals('0.0000', $coverage['remaining_to_plan']);
    }

    public function test_scenario_e_replacement_qc_good_1_reject_1_remaining_to_plan_becomes_1(): void
    {
        [, $item] = $this->createOrderWithItem(20);

        // Batch 1: Good 18, Reject 2
        $batch1 = ProductionBatch::create([
            'order_item_id'     => $item->id,
            'batch_no'          => 'BAT-001',
            'target_qty'        => 20,
            'status'            => BatchStatus::SELESAI,
            'requires_printing' => false,
        ]);
        $res1 = ProductionResult::create([
            'batch_id'    => $batch1->id,
            'actual_qty'  => 20,
            'result_date' => now()->toDateString(),
            'recorded_by' => $this->adminUser->id,
        ]);
        QcResult::create([
            'production_result_id' => $res1->id,
            'good_qty'             => 18,
            'reject_qty'           => 2,
            'qc_date'              => now()->toDateString(),
            'qc_by'                => $this->adminUser->id,
        ]);

        // Batch 2 (Replacement): Good 1, Reject 1
        $batch2 = ProductionBatch::create([
            'order_item_id'     => $item->id,
            'batch_no'          => 'BAT-002',
            'target_qty'        => 2,
            'status'            => BatchStatus::SELESAI,
            'requires_printing' => false,
        ]);
        $res2 = ProductionResult::create([
            'batch_id'    => $batch2->id,
            'actual_qty'  => 2,
            'result_date' => now()->toDateString(),
            'recorded_by' => $this->adminUser->id,
        ]);
        QcResult::create([
            'production_result_id' => $res2->id,
            'good_qty'             => 1,
            'reject_qty'           => 1,
            'qc_date'              => now()->toDateString(),
            'qc_by'                => $this->adminUser->id,
        ]);

        $coverage = $this->batchService->calculateProductionCoverage($item);

        $this->assertEquals('19.0000', $coverage['confirmed_good_qty']);
        $this->assertEquals('0.0000', $coverage['pending_production_qty']);
        $this->assertEquals('19.0000', $coverage['production_coverage']);
        $this->assertEquals('1.0000', $coverage['remaining_to_plan']);
        $this->assertEquals('3.0000', $coverage['total_reject_qty']);
    }

    public function test_scenario_f_good_total_20_remaining_is_0(): void
    {
        [, $item] = $this->createOrderWithItem(20);

        // Batch 1: Good 18, Reject 2
        $batch1 = ProductionBatch::create([
            'order_item_id'     => $item->id,
            'batch_no'          => 'BAT-001',
            'target_qty'        => 20,
            'status'            => BatchStatus::SELESAI,
            'requires_printing' => false,
        ]);
        $res1 = ProductionResult::create([
            'batch_id'    => $batch1->id,
            'actual_qty'  => 20,
            'result_date' => now()->toDateString(),
            'recorded_by' => $this->adminUser->id,
        ]);
        QcResult::create([
            'production_result_id' => $res1->id,
            'good_qty'             => 18,
            'reject_qty'           => 2,
            'qc_date'              => now()->toDateString(),
            'qc_by'                => $this->adminUser->id,
        ]);

        // Batch 2 (Replacement): Good 2, Reject 0
        $batch2 = ProductionBatch::create([
            'order_item_id'     => $item->id,
            'batch_no'          => 'BAT-002',
            'target_qty'        => 2,
            'status'            => BatchStatus::SELESAI,
            'requires_printing' => false,
        ]);
        $res2 = ProductionResult::create([
            'batch_id'    => $batch2->id,
            'actual_qty'  => 2,
            'result_date' => now()->toDateString(),
            'recorded_by' => $this->adminUser->id,
        ]);
        QcResult::create([
            'production_result_id' => $res2->id,
            'good_qty'             => 2,
            'reject_qty'           => 0,
            'qc_date'              => now()->toDateString(),
            'qc_by'                => $this->adminUser->id,
        ]);

        $coverage = $this->batchService->calculateProductionCoverage($item);

        $this->assertEquals('20.0000', $coverage['confirmed_good_qty']);
        $this->assertEquals('0.0000', $coverage['pending_production_qty']);
        $this->assertEquals('20.0000', $coverage['production_coverage']);
        $this->assertEquals('0.0000', $coverage['remaining_to_plan']);
    }

    public function test_scenario_g_good_surplus_22_remaining_is_0_not_negative(): void
    {
        [, $item] = $this->createOrderWithItem(20);

        $batch = ProductionBatch::create([
            'order_item_id'     => $item->id,
            'batch_no'          => 'BAT-001',
            'target_qty'        => 20,
            'status'            => BatchStatus::SELESAI,
            'requires_printing' => false,
        ]);
        $res = ProductionResult::create([
            'batch_id'    => $batch->id,
            'actual_qty'  => 22,
            'result_date' => now()->toDateString(),
            'recorded_by' => $this->adminUser->id,
        ]);
        QcResult::create([
            'production_result_id' => $res->id,
            'good_qty'             => 22,
            'reject_qty'           => 0,
            'qc_date'              => now()->toDateString(),
            'qc_by'                => $this->adminUser->id,
        ]);

        $coverage = $this->batchService->calculateProductionCoverage($item);

        $this->assertEquals('22.0000', $coverage['confirmed_good_qty']);
        $this->assertEquals('0.0000', $coverage['pending_production_qty']);
        $this->assertEquals('22.0000', $coverage['production_coverage']);
        $this->assertEquals('0.0000', $coverage['remaining_to_plan']);
    }

    public function test_order_status_aggregation_returns_to_bahan_when_qc_reject_creates_fulfillment_deficit(): void
    {
        [$order, $item] = $this->createOrderWithItem(20);
        $order->update(['status' => OrderStatus::PACKING]);

        $batch = ProductionBatch::create([
            'order_item_id'     => $item->id,
            'batch_no'          => 'BAT-001',
            'target_qty'        => 20,
            'status'            => BatchStatus::SELESAI,
            'requires_printing' => false,
        ]);
        $res = ProductionResult::create([
            'batch_id'    => $batch->id,
            'actual_qty'  => 20,
            'result_date' => now()->toDateString(),
            'recorded_by' => $this->adminUser->id,
        ]);
        QcResult::create([
            'production_result_id' => $res->id,
            'good_qty'             => 18,
            'reject_qty'           => 2,
            'qc_date'              => now()->toDateString(),
            'qc_by'                => $this->adminUser->id,
        ]);

        $newStatus = $this->statusService->syncOperationalStatus($order);

        $this->assertEquals(OrderStatus::BAHAN, $newStatus);
        $this->assertEquals(OrderStatus::BAHAN, $order->fresh()->status);

        // Verify history entry
        $latestHistory = $order->fresh()->statusHistories()->latest()->first();
        $this->assertEquals(OrderStatus::BAHAN, $latestHistory->status);
        $this->assertEquals('Hasil QC belum memenuhi qty pesanan; produksi tambahan diperlukan.', $latestHistory->notes);
    }

    public function test_admin_can_create_replacement_batch_and_complete_order_to_packing(): void
    {
        [$order, $item] = $this->createOrderWithItem(20);

        // 1. Initial Batch
        $created1 = $this->batchService->createBatch($order, [
            'order_item_id' => $item->id,
            'target_qty'    => 20,
        ], $this->adminUser);
        $batch1 = $created1['batch'];

        // Batch 1 proceeds through production & QC: Good 18, Reject 2
        $batch1->update(['status' => BatchStatus::SELESAI]);
        $res1 = ProductionResult::create([
            'batch_id'    => $batch1->id,
            'actual_qty'  => 20,
            'result_date' => now()->toDateString(),
            'recorded_by' => $this->adminUser->id,
        ]);
        QcResult::create([
            'production_result_id' => $res1->id,
            'good_qty'             => 18,
            'reject_qty'           => 2,
            'qc_date'              => now()->toDateString(),
            'qc_by'                => $this->adminUser->id,
        ]);

        // Sync order status -> reverts to BAHAN
        $this->statusService->syncOperationalStatus($order);
        $this->assertEquals(OrderStatus::BAHAN, $order->fresh()->status);

        // 2. Admin creates replacement batch with target 2
        $this->assertTrue($this->batchService->canCreateForOrder($order->fresh()));
        $this->assertEquals('2.0000', $this->batchService->calculateRemainingQty($item));

        $created2 = $this->batchService->createBatch($order->fresh(), [
            'order_item_id' => $item->id,
            'target_qty'    => 2,
        ], $this->adminUser);
        $batch2 = $created2['batch'];
        $this->assertNotNull($batch2);
        $this->assertEquals('2.0000', (string) $batch2->target_qty);

        // Target remaining is now 0
        $this->assertEquals('0.0000', $this->batchService->calculateRemainingQty($item));

        // 3. Replacement batch finishes with Good 2, Reject 0
        $batch2->update(['status' => BatchStatus::SELESAI]);
        $res2 = ProductionResult::create([
            'batch_id'    => $batch2->id,
            'actual_qty'  => 2,
            'result_date' => now()->toDateString(),
            'recorded_by' => $this->adminUser->id,
        ]);
        QcResult::create([
            'production_result_id' => $res2->id,
            'good_qty'             => 2,
            'reject_qty'           => 0,
            'qc_date'              => now()->toDateString(),
            'qc_by'                => $this->adminUser->id,
        ]);

        // Sync order status -> All batches SELESAI and Good total 20 == Ordered 20 -> PACKING
        $this->statusService->syncOperationalStatus($order);
        $this->assertEquals(OrderStatus::PACKING, $order->fresh()->status);
    }

    public function test_scenario_uat_bat_202609_0003_target_30_actual_7_good_1_reject_6(): void
    {
        [$order, $item] = $this->createOrderWithItem(30);

        // Batch 1: target = 30
        $created = $this->batchService->createBatch($order, [
            'order_item_id' => $item->id,
            'target_qty'    => 30,
        ], $this->adminUser);
        $batch1 = $created['batch'];

        // Batch 1 transitions through Production (actual = 7) and QC (good = 1, reject = 6)
        $batch1->update(['status' => BatchStatus::PACKING]);
        $res = ProductionResult::create([
            'batch_id'    => $batch1->id,
            'actual_qty'  => 7,
            'result_date' => now()->toDateString(),
            'recorded_by' => $this->adminUser->id,
        ]);
        QcResult::create([
            'production_result_id' => $res->id,
            'good_qty'             => 1,
            'reject_qty'           => 6,
            'qc_date'              => now()->toDateString(),
            'qc_by'                => $this->adminUser->id,
        ]);

        // Coverage computation
        $coverage = $this->batchService->calculateProductionCoverage($item);
        $this->assertEquals('1.0000', $coverage['confirmed_good_qty']);
        $this->assertEquals('0.0000', $coverage['pending_production_qty']);
        $this->assertEquals('1.0000', $coverage['production_coverage']);
        $this->assertEquals('29.0000', $coverage['remaining_to_plan']);
        $this->assertEquals('6.0000', $coverage['total_reject_qty']);

        // Order status sync -> BAHAN (despite Batch 1 being PACKING)
        $this->statusService->syncOperationalStatus($order);
        $this->assertEquals(OrderStatus::BAHAN, $order->fresh()->status);

        // Capacity check: Admin may create replacement Batch with max 29
        $this->assertTrue($this->batchService->canCreateForOrder($order->fresh()));
        $this->assertEquals('29.0000', $this->batchService->calculateRemainingQty($item));

        // Attempting to create batch > 29 must fail
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('melebihi sisa kuantitas');
        $this->batchService->createBatch($order->fresh(), [
            'order_item_id' => $item->id,
            'target_qty'    => 30,
        ], $this->adminUser);
    }
}
