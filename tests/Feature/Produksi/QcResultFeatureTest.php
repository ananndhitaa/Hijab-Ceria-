<?php

namespace Tests\Feature\Produksi;

use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
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
use App\Models\System\User;
use App\Services\Tracking\TrackingTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QcResultFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $produksiUser;
    private Customer $customer;
    private Unit $meterUnit;
    private Material $kain;
    private MaterialStock $kainStock;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->produksiUser = User::factory()->create([
            'role'   => UserRole::PRODUKSI,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->customer = Customer::create(['customer_name' => 'Butik Nabila', 'phone' => '08123456789']);

        $this->meterUnit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $this->kain = Material::create([
            'unit_id'       => $this->meterUnit->id,
            'material_name' => 'Kain Katun Premium',
            'min_stock'     => 10,
            'active'        => true,
        ]);
        $this->kainStock = MaterialStock::create([
            'material_id'   => $this->kain->id,
            'qty_on_hand'   => 150.0000,
            'avg_unit_cost' => 35000.0000,
        ]);

        $this->product = Product::create(['product_name' => 'Hijab Pashmina Katun', 'active' => true]);
    }

    protected function tearDown(): void
    {
        \Carbon\Carbon::setTestNow();
        parent::tearDown();
    }

    private function createBatchWithResult(
        BatchStatus $status = BatchStatus::QC,
        float $targetQty = 100,
        float $actualQty = 100,
        string $resultDate = '2026-08-29',
        ?string $plannedStartDate = null,
        ?string $plannedEndDate = null
    ): array {
        $variant = ProductVariant::create([
            'product_id'        => $this->product->id,
            'variant_name'      => 'Cream',
            'sku'               => 'SKU-' . uniqid(),
            'requires_printing' => false,
            'active'            => true,
        ]);

        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'ORD-' . uniqid(),
            'order_date'  => '2026-08-28',
            'status'      => OrderStatus::QC,
        ]);

        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => $targetQty,
            'unit_selling_price' => 75000,
            'subtotal'           => $targetQty * 75000,
        ]);

        $batch = ProductionBatch::create([
            'order_item_id'          => $item->id,
            'batch_no'               => 'BAT-' . uniqid(),
            'target_qty'             => $targetQty,
            'status'                 => $status,
            'requires_printing'      => false,
            'planned_start_date'     => $plannedStartDate,
            'planned_end_date'       => $plannedEndDate,
            'production_employee_id' => $this->produksiUser->id,
        ]);

        $bm = BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->kain->id,
            'required_qty'  => 120.0000,
            'allocated_qty' => 120.0000,
            'used_qty'      => 0.0000,
        ]);

        $prodResult = ProductionResult::create([
            'batch_id'    => $batch->id,
            'actual_qty'  => $actualQty,
            'result_date' => $resultDate,
            'notes'       => 'Hasil produksi awal',
            'recorded_by' => $this->produksiUser->id,
        ]);

        return [$batch, $order, $variant, $bm, $prodResult];
    }

    public function test_basic_qc_recording(): void
    {
        [$batch, $order] = $this->createBatchWithResult(BatchStatus::QC, 100, 100);

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch), [
                'good_qty'   => 98,
                'reject_qty' => 2,
                'qc_date'    => '2026-08-29',
                'notes'      => '2 pcs afkir karena noda benang.',
            ]);

        $response->assertRedirect(route('produksi.batches.show', $batch));
        $response->assertSessionHas('success');

        $this->assertEquals(BatchStatus::PACKING, $batch->fresh()->status);
        // Order returns/remains BAHAN because 2 pcs are short (remaining_to_plan = 2)
        $this->assertEquals(OrderStatus::BAHAN, $order->fresh()->status);

        $qc = QcResult::where('production_result_id', $batch->productionResult->id)->first();
        $this->assertNotNull($qc);
        $this->assertEquals('98.0000', $qc->good_qty);
        $this->assertEquals('2.0000', $qc->reject_qty);
        $this->assertEquals($this->produksiUser->id, $qc->qc_by);
        $this->assertEquals('2 pcs afkir karena noda benang.', $qc->notes);
    }

    public function test_all_good_qc_is_accepted(): void
    {
        [$batch] = $this->createBatchWithResult(BatchStatus::QC, 100, 100);

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch), [
                'good_qty'   => 100,
                'reject_qty' => 0,
                'qc_date'    => '2026-08-29',
            ]);

        $response->assertRedirect(route('produksi.batches.show', $batch));
        $response->assertSessionHas('success');

        $this->assertEquals(BatchStatus::PACKING, $batch->fresh()->status);
        $qc = QcResult::where('production_result_id', $batch->productionResult->id)->first();
        $this->assertEquals('100.0000', $qc->good_qty);
        $this->assertEquals('0.0000', $qc->reject_qty);
    }

    public function test_all_reject_qc_is_accepted(): void
    {
        [$batch] = $this->createBatchWithResult(BatchStatus::QC, 100, 100);

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch), [
                'good_qty'   => 0,
                'reject_qty' => 100,
                'qc_date'    => '2026-08-29',
                'notes'      => 'Seluruh batch reject karena cacat fatal.',
            ]);

        $response->assertRedirect(route('produksi.batches.show', $batch));
        $response->assertSessionHas('success');

        $this->assertEquals(BatchStatus::PACKING, $batch->fresh()->status);
        $qc = QcResult::where('production_result_id', $batch->productionResult->id)->first();
        $this->assertEquals('0.0000', $qc->good_qty);
        $this->assertEquals('100.0000', $qc->reject_qty);
    }

    public function test_qc_good_qty_must_be_integer_and_rejects_decimals(): void
    {
        [$batch] = $this->createBatchWithResult(BatchStatus::QC, 18, 18);

        // Fractional / decimal Good Qty is rejected (integer only for pcs)
        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch), [
                'good_qty'   => 18.5,
                'qc_date'    => '2026-08-29',
            ]);

        $response->assertSessionHasErrors('good_qty');
        $this->assertEquals(BatchStatus::QC, $batch->fresh()->status);
        $this->assertEquals(0, QcResult::count());
    }

    public function test_qc_good_qty_hard_limits_and_reject_calculations(): void
    {
        // 1. Verify rendered form HTML attributes (Actual = 18)
        [$formBatch] = $this->createBatchWithResult(BatchStatus::QC, 18, 18);

        $viewResponse = $this->actingAs($this->produksiUser)
            ->get(route('produksi.batches.qc.create', $formBatch));

        $viewResponse->assertOk();
        $viewResponse->assertSee('min="0"', false);
        $viewResponse->assertSee('max="18"', false);
        $viewResponse->assertSee('step="1"', false);

        // 2. good_qty = 0 -> accepted (Reject 18)
        [$batch0] = $this->createBatchWithResult(BatchStatus::QC, 18, 18);
        $res0 = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch0), ['good_qty' => 0, 'qc_date' => '2026-08-29']);
        $res0->assertRedirect(route('produksi.batches.show', $batch0));
        $qc0 = QcResult::where('production_result_id', $batch0->productionResult->id)->first();
        $this->assertEquals('0.0000', $qc0->good_qty);
        $this->assertEquals('18.0000', $qc0->reject_qty);

        // 3. good_qty = 1 -> accepted (Reject 17)
        [$batch1] = $this->createBatchWithResult(BatchStatus::QC, 18, 18);
        $res1 = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch1), ['good_qty' => 1, 'qc_date' => '2026-08-29']);
        $res1->assertRedirect(route('produksi.batches.show', $batch1));
        $qc1 = QcResult::where('production_result_id', $batch1->productionResult->id)->first();
        $this->assertEquals('1.0000', $qc1->good_qty);
        $this->assertEquals('17.0000', $qc1->reject_qty);

        // 4. good_qty = 15 -> accepted (Reject 3)
        [$batch15] = $this->createBatchWithResult(BatchStatus::QC, 18, 18);
        $res15 = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch15), ['good_qty' => 15, 'qc_date' => '2026-08-29']);
        $res15->assertRedirect(route('produksi.batches.show', $batch15));
        $qc15 = QcResult::where('production_result_id', $batch15->productionResult->id)->first();
        $this->assertEquals('15.0000', $qc15->good_qty);
        $this->assertEquals('3.0000', $qc15->reject_qty);

        // 5. good_qty = 18 -> accepted (Reject 0)
        [$batch18] = $this->createBatchWithResult(BatchStatus::QC, 18, 18);
        $res18 = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch18), ['good_qty' => 18, 'qc_date' => '2026-08-29']);
        $res18->assertRedirect(route('produksi.batches.show', $batch18));
        $qc18 = QcResult::where('production_result_id', $batch18->productionResult->id)->first();
        $this->assertEquals('18.0000', $qc18->good_qty);
        $this->assertEquals('0.0000', $qc18->reject_qty);

        // 6. good_qty = 19 -> backend rejected (> 18)
        [$batch19] = $this->createBatchWithResult(BatchStatus::QC, 18, 18);
        $res19 = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch19), ['good_qty' => 19, 'qc_date' => '2026-08-29']);
        $res19->assertSessionHasErrors('good_qty');

        // 7. good_qty = 76 -> backend rejected (> 18)
        [$batch76] = $this->createBatchWithResult(BatchStatus::QC, 18, 18);
        $res76 = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch76), ['good_qty' => 76, 'qc_date' => '2026-08-29']);
        $res76->assertSessionHasErrors('good_qty');

        // 8. good_qty = -1 -> backend rejected (< 0)
        [$batchNeg] = $this->createBatchWithResult(BatchStatus::QC, 18, 18);
        $resNeg = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batchNeg), ['good_qty' => -1, 'qc_date' => '2026-08-29']);
        $resNeg->assertSessionHasErrors('good_qty');

        // 9. good_qty = 18.5 -> backend rejected (decimal)
        [$batchDec] = $this->createBatchWithResult(BatchStatus::QC, 18, 18);
        $resDec = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batchDec), ['good_qty' => 18.5, 'qc_date' => '2026-08-29']);
        $resDec->assertSessionHasErrors('good_qty');
    }

    public function test_good_qty_exceeding_actual_qty_is_rejected(): void
    {
        [$batch] = $this->createBatchWithResult(BatchStatus::QC, 100, 100);

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch), [
                'good_qty' => 101, // 101 > 100
            ]);

        $response->assertSessionHasErrors('good_qty');
        $this->assertEquals(BatchStatus::QC, $batch->fresh()->status);
        $this->assertEquals(0, QcResult::count());
    }

    public function test_crafted_client_reject_is_ignored_and_automatically_calculated(): void
    {
        [$batch] = $this->createBatchWithResult(BatchStatus::QC, 100, 100);

        // Client attempts to send reject_qty = 0 even though good_qty = 95 (actual = 100)
        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch), [
                'good_qty'   => 95,
                'reject_qty' => 0,
            ]);

        $response->assertRedirect(route('produksi.batches.show', $batch));
        $response->assertSessionHas('success');
        $this->assertEquals(BatchStatus::PACKING, $batch->fresh()->status);

        $qc = QcResult::first();
        $this->assertEquals('95.0000', $qc->good_qty);
        $this->assertEquals('5.0000', $qc->reject_qty); // System calculated: 100 - 95 = 5
    }

    public function test_negative_or_malformed_quantities_are_rejected(): void
    {
        [$batch] = $this->createBatchWithResult(BatchStatus::QC, 100, 100);

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch), [
                'good_qty'   => -10,
                'reject_qty' => 110,
                'qc_date'    => '2026-08-29',
            ]);

        $response->assertSessionHasErrors('good_qty');
        $this->assertEquals(BatchStatus::QC, $batch->fresh()->status);
        $this->assertEquals(0, QcResult::count());
    }

    public function test_invalid_batch_status_is_rejected(): void
    {
        $invalidStatuses = [
            BatchStatus::BAHAN,
            BatchStatus::PRINTING,
            BatchStatus::PRODUKSI,
            BatchStatus::PACKING,
            BatchStatus::SELESAI,
            BatchStatus::CANCELLED,
        ];

        foreach ($invalidStatuses as $status) {
            [$batch] = $this->createBatchWithResult($status, 100, 100);

            $response = $this->actingAs($this->produksiUser)
                ->post(route('produksi.batches.qc.store', $batch), [
                    'good_qty'   => 100,
                    'reject_qty' => 0,
                    'qc_date'    => '2026-08-29',
                ]);

            $response->assertRedirect();
            $response->assertSessionHas('error');
            $this->assertEquals($status, $batch->fresh()->status);
            $this->assertEquals(0, QcResult::where('production_result_id', $batch->productionResult->id)->count());
        }
    }

    public function test_missing_production_result_rejects_qc(): void
    {
        // Create batch directly at QC without ProductionResult
        $variant = ProductVariant::create([
            'product_id'        => $this->product->id,
            'variant_name'      => 'Dusty',
            'sku'               => 'SKU-NO-RES-' . uniqid(),
            'requires_printing' => false,
            'active'            => true,
        ]);
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'ORD-NO-RES-' . uniqid(),
            'order_date'  => '2026-08-28',
            'status'      => OrderStatus::QC,
        ]);
        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 100,
            'unit_selling_price' => 75000,
            'subtotal'           => 7500000,
        ]);
        $batch = ProductionBatch::create([
            'order_item_id'          => $item->id,
            'batch_no'               => 'BAT-NO-RES-' . uniqid(),
            'target_qty'             => 100,
            'status'                 => BatchStatus::QC,
            'requires_printing'      => false,
            'production_employee_id' => $this->produksiUser->id,
        ]);

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch), [
                'good_qty'   => 100,
                'reject_qty' => 0,
                'qc_date'    => '2026-08-29',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals(0, QcResult::count());
    }

    public function test_duplicate_qc_submission_is_prevented(): void
    {
        [$batch] = $this->createBatchWithResult(BatchStatus::QC, 100, 100);

        // First submit: QC -> PACKING
        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch), [
                'good_qty'   => 98,
                'reject_qty' => 2,
                'qc_date'    => '2026-08-29',
            ]);

        $this->assertEquals(BatchStatus::PACKING, $batch->fresh()->status);
        $this->assertEquals(1, QcResult::where('production_result_id', $batch->productionResult->id)->count());

        // Second submit attempt
        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch), [
                'good_qty'   => 98,
                'reject_qty' => 2,
                'qc_date'    => '2026-08-29',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals(BatchStatus::PACKING, $batch->fresh()->status); // Must NOT jump to SELESAI
        $this->assertEquals(1, QcResult::where('production_result_id', $batch->productionResult->id)->count());
    }

    public function test_qc_timestamp_and_date_are_automatically_generated(): void
    {
        [$batch] = $this->createBatchWithResult(BatchStatus::QC, 100, 100, '2026-08-29');

        $this->freezeTime();

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch), [
                'good_qty' => 100,
            ]);

        $response->assertSessionHas('success');
        $this->assertEquals(1, QcResult::count());

        $qc = QcResult::first();
        $this->assertNotNull($qc->qc_at);
        $this->assertEquals(now()->toDateTimeString(), $qc->qc_at->toDateTimeString());
        $this->assertEquals(
            $batch->getProductionStartDate(),
            $qc->qc_date->toDateString()
        );
    }

    public function test_tampered_actor_payload_is_ignored(): void
    {
        [$batch] = $this->createBatchWithResult(BatchStatus::QC, 100, 100);
        $otherUser = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch), [
                'good_qty'   => 100,
                'reject_qty' => 0,
                'qc_date'    => '2026-08-29',
                'qc_by'      => $otherUser->id, // Malicious spoof
            ]);

        $qc = QcResult::where('production_result_id', $batch->productionResult->id)->first();
        $this->assertEquals($this->produksiUser->id, $qc->qc_by);
    }

    public function test_zero_inventory_mutation_on_qc_recording(): void
    {
        [$batch] = $this->createBatchWithResult(BatchStatus::QC, 100, 100);

        $initialStockQty = $this->kainStock->fresh()->qty_on_hand;
        $initialAvgCost = $this->kainStock->fresh()->avg_unit_cost;
        $initialMovementCount = StockMovement::count();

        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch), [
                'good_qty'   => 98,
                'reject_qty' => 2,
                'qc_date'    => '2026-08-29',
            ]);

        $this->assertEquals($initialStockQty, $this->kainStock->fresh()->qty_on_hand);
        $this->assertEquals($initialAvgCost, $this->kainStock->fresh()->avg_unit_cost);
        $this->assertEquals($initialMovementCount, StockMovement::count());

        $bm = $batch->batchMaterials()->first();
        $this->assertEquals('120.0000', $bm->required_qty);
        $this->assertEquals('120.0000', $bm->allocated_qty);
        $this->assertEquals('0.0000', $bm->used_qty);
    }

    public function test_production_result_immutability_on_qc_recording(): void
    {
        [$batch, , , , $prodResult] = $this->createBatchWithResult(BatchStatus::QC, 100, 100);

        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch), [
                'good_qty'   => 98,
                'reject_qty' => 2,
                'qc_date'    => '2026-08-29',
            ]);

        $freshResult = $prodResult->fresh();
        $this->assertEquals('100.0000', $freshResult->actual_qty);
        $this->assertEquals('2026-08-29', $freshResult->result_date->toDateString());
        $this->assertEquals('Hasil produksi awal', $freshResult->notes);
        $this->assertEquals($this->produksiUser->id, $freshResult->recorded_by);
    }

    public function test_order_aggregation_with_sibling_batches_at_qc(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'ORD-QC-AGG-' . uniqid(),
            'order_date'  => '2026-08-28',
            'status'      => OrderStatus::QC,
        ]);

        $variant = ProductVariant::create([
            'product_id'        => $this->product->id,
            'variant_name'      => 'Sage',
            'sku'               => 'SKU-QC-SIB-' . uniqid(),
            'requires_printing' => false,
            'active'            => true,
        ]);

        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 200,
            'unit_selling_price' => 75000,
            'subtotal'           => 15000000,
        ]);

        // Batch 1 (100 pcs) at QC with Result
        $batch1 = ProductionBatch::create([
            'order_item_id'          => $item->id,
            'batch_no'               => 'BAT-QC-1-' . uniqid(),
            'target_qty'             => 100,
            'status'                 => BatchStatus::QC,
            'requires_printing'      => false,
            'production_employee_id' => $this->produksiUser->id,
        ]);
        BatchMaterial::create([
            'batch_id'      => $batch1->id,
            'material_id'   => $this->kain->id,
            'required_qty'  => 100,
            'allocated_qty' => 100,
        ]);
        ProductionResult::create([
            'batch_id'    => $batch1->id,
            'actual_qty'  => 100,
            'result_date' => '2026-08-29',
            'recorded_by' => $this->produksiUser->id,
        ]);

        // Batch 2 (100 pcs) at QC with Result
        $batch2 = ProductionBatch::create([
            'order_item_id'          => $item->id,
            'batch_no'               => 'BAT-QC-2-' . uniqid(),
            'target_qty'             => 100,
            'status'                 => BatchStatus::QC,
            'requires_printing'      => false,
            'production_employee_id' => $this->produksiUser->id,
        ]);
        BatchMaterial::create([
            'batch_id'      => $batch2->id,
            'material_id'   => $this->kain->id,
            'required_qty'  => 100,
            'allocated_qty' => 100,
        ]);
        ProductionResult::create([
            'batch_id'    => $batch2->id,
            'actual_qty'  => 100,
            'result_date' => '2026-08-29',
            'recorded_by' => $this->produksiUser->id,
        ]);

        // Record QC for Batch 1 -> Batch 1 reaches PACKING
        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch1), [
                'good_qty'   => 100,
                'reject_qty' => 0,
                'qc_date'    => '2026-08-29',
            ]);

        $this->assertEquals(BatchStatus::PACKING, $batch1->fresh()->status);
        $this->assertEquals(BatchStatus::QC, $batch2->fresh()->status);

        // Order remains QC because Batch 2 is still at QC
        $this->assertEquals(OrderStatus::QC, $order->fresh()->status);

        // Record QC for Batch 2 -> Batch 2 reaches PACKING
        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch2), [
                'good_qty'   => 100,
                'reject_qty' => 0,
                'qc_date'    => '2026-08-29',
            ]);

        $this->assertEquals(BatchStatus::PACKING, $batch2->fresh()->status);

        // Order now advances to PACKING (total good 200 == ordered 200)
        $this->assertEquals(OrderStatus::PACKING, $order->fresh()->status);
    }

    public function test_customer_tracking_reflects_packing_status_without_leaking_qc_details(): void
    {
        [$batch, $order] = $this->createBatchWithResult(BatchStatus::QC, 100, 100);

        $tokenService = app(TrackingTokenService::class);
        $token = $tokenService->createForOrder($order);

        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch), [
                'good_qty'   => 100,
                'reject_qty' => 0,
                'qc_date'    => '2026-08-29',
                'notes'      => 'Internal QC defect note secret.',
            ]);

        $this->assertEquals(OrderStatus::PACKING, $order->fresh()->status);

        $response = $this->get("/track/{$token}");
        $response->assertOk();
        $response->assertSee('Pengemasan');
        $response->assertDontSee('Internal QC defect note secret');
        $response->assertDontSee('Reject');
    }

    public function test_role_authorization_for_qc_recording(): void
    {
        [$batch] = $this->createBatchWithResult(BatchStatus::QC, 100, 100);

        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'status' => UserStatus::ACTIVE]);
        $this->actingAs($admin)
            ->get(route('produksi.batches.qc.create', $batch))
            ->assertForbidden();
        $this->actingAs($admin)
            ->post(route('produksi.batches.qc.store', $batch), ['good_qty' => 100, 'reject_qty' => 0, 'qc_date' => '2026-08-29'])
            ->assertForbidden();

        $gudang = User::factory()->create(['role' => UserRole::GUDANG, 'status' => UserStatus::ACTIVE]);
        $this->actingAs($gudang)
            ->post(route('produksi.batches.qc.store', $batch), ['good_qty' => 100, 'reject_qty' => 0, 'qc_date' => '2026-08-29'])
            ->assertForbidden();

        $owner = User::factory()->create(['role' => UserRole::OWNER, 'status' => UserStatus::ACTIVE]);
        $this->actingAs($owner)
            ->post(route('produksi.batches.qc.store', $batch), ['good_qty' => 100, 'reject_qty' => 0, 'qc_date' => '2026-08-29'])
            ->assertForbidden();

        $customer = User::factory()->create(['role' => UserRole::CUSTOMER, 'status' => UserStatus::ACTIVE]);
        $this->actingAs($customer)
            ->post(route('produksi.batches.qc.store', $batch), ['good_qty' => 100, 'reject_qty' => 0, 'qc_date' => '2026-08-29'])
            ->assertForbidden();

        auth()->logout();
        $this->get(route('produksi.batches.qc.create', $batch))
            ->assertRedirect(route('login'));
    }

    public function test_qc_form_authoritative_dates_editable_picker_and_no_helper_text(): void
    {
        // CASE 1: planned_start_date = 2026-10-08, actual result_date = 2026-10-12
        \Carbon\Carbon::setTestNow('2026-10-12 14:30:00');

        [$batch] = $this->createBatchWithResult(
            BatchStatus::QC,
            100,
            100,
            '2026-10-12',
            '2026-10-08',
            '2026-10-12'
        );

        $response = $this->actingAs($this->produksiUser)
            ->get(route('produksi.batches.qc.create', $batch));

        $response->assertOk();

        // CASE 1: Tanggal Produksi = 12 Okt 2026, NOT 08 Okt 2026
        $response->assertSee('12 Okt 2026');
        $response->assertSee('Tanggal hasil produksi.');
        $response->assertDontSee('08 Okt 2026');
        $response->assertDontSee('08 Oct 2026');
        $response->assertDontSee('12 Oct 2026');

        // CASE 2: QC date field default: 2026-10-12
        $response->assertSee('value="2026-10-12"', false);

        // CASE 3: QC date min: 2026-10-12
        $response->assertSee('min="2026-10-12"', false);

        // CASE 11: no helper text appears below QC date field and WIB helper removed
        $response->assertDontSee('Mengikuti tanggal produksi.');
        $response->assertDontSee('Minimal tanggal produksi.');
        $response->assertDontSee('Otomatis mengikuti tanggal produksi.');
        $response->assertDontSee('Tidak boleh sebelum tanggal produksi.');
        $response->assertDontSee('Waktu aktual sistem (WIB).');
        $response->assertDontSee('Hanya-baca, dari hasil produksi.');
    }

    public function test_qc_date_validation_rejects_date_before_actual_production_date(): void
    {
        // CASE 4: submit qc_date = 2026-10-11 when production date = 2026-10-12 -> validation rejected
        \Carbon\Carbon::setTestNow('2026-10-12 14:30:00');

        [$batch] = $this->createBatchWithResult(
            BatchStatus::QC,
            100,
            100,
            '2026-10-12',
            '2026-10-08',
            '2026-10-12'
        );

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch), [
                'good_qty' => 100,
                'qc_date'  => '2026-10-11',
            ]);

        $response->assertSessionHasErrors('qc_date');
        $this->assertEquals(0, QcResult::count());
        $this->assertEquals(BatchStatus::QC, $batch->fresh()->status);
    }

    public function test_qc_date_accepts_same_or_later_date_and_stores_exact_date_and_timestamp(): void
    {
        \Carbon\Carbon::setTestNow('2026-10-12 14:30:00');

        [$batch, , , , $prodResult] = $this->createBatchWithResult(
            BatchStatus::QC,
            100,
            100,
            '2026-10-12',
            '2026-10-08',
            '2026-10-12'
        );

        $initialStockQty = $this->kainStock->fresh()->qty_on_hand;
        $initialAvgCost = $this->kainStock->fresh()->avg_unit_cost;
        $initialMovementCount = StockMovement::count();

        // CASE 6: submit qc_date = 2026-10-14 -> accepted
        // CASE 12: existing Good/Reject calculation remains valid (100 - 95 = 5 reject)
        $storeResponse = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch), [
                'good_qty'   => 95,
                'reject_qty' => 5,
                'qc_date'    => '2026-10-14',
                'notes'      => 'Kain jahitan rapi.',
            ]);

        $storeResponse->assertRedirect(route('produksi.batches.show', $batch));
        $storeResponse->assertSessionHas('success');

        // CASE 7: saved qc_date exactly matches selected date (2026-10-14)
        $qc = QcResult::where('production_result_id', $prodResult->id)->first();
        $this->assertNotNull($qc);
        $this->assertEquals('2026-10-14', $qc->qc_date->toDateString());

        // CASE 8: qc_at equals actual save timestamp
        $this->assertEquals('2026-10-12 14:30:00', $qc->qc_at->toDateTimeString());

        // CASE 9: ProductionResult.result_date remains unchanged
        $this->assertEquals('2026-10-12', $prodResult->fresh()->result_date->toDateString());

        // CASE 10: planned_start_date / planned_end_date remain unchanged
        $freshBatch = $batch->fresh();
        $this->assertEquals('2026-10-08', $freshBatch->planned_start_date->toDateString());
        $this->assertEquals('2026-10-12', $freshBatch->planned_end_date->toDateString());

        // CASE 12: Good/Reject verification
        $this->assertEquals('95.0000', $qc->good_qty);
        $this->assertEquals('5.0000', $qc->reject_qty);

        // Functional safety: No stock or HPP mutations
        $this->assertEquals($initialStockQty, $this->kainStock->fresh()->qty_on_hand);
        $this->assertEquals($initialAvgCost, $this->kainStock->fresh()->avg_unit_cost);
        $this->assertEquals($initialMovementCount, StockMovement::count());
    }

    public function test_qc_date_accepts_same_date_as_production_date(): void
    {
        // CASE 5: submit qc_date = 2026-10-12 -> accepted
        \Carbon\Carbon::setTestNow('2026-10-12 10:00:00');

        [$batch, , , , $prodResult] = $this->createBatchWithResult(
            BatchStatus::QC,
            100,
            100,
            '2026-10-12',
            '2026-10-08',
            '2026-10-12'
        );

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch), [
                'good_qty' => 100,
                'qc_date'  => '2026-10-12',
            ]);

        $response->assertRedirect(route('produksi.batches.show', $batch));
        $response->assertSessionHas('success');

        $qc = QcResult::where('production_result_id', $prodResult->id)->first();
        $this->assertNotNull($qc);
        $this->assertEquals('2026-10-12', $qc->qc_date->toDateString());
        $this->assertEquals('2026-10-12 10:00:00', $qc->qc_at->toDateTimeString());
    }

    public function test_qc_before_actual_production_date_is_blocked(): void
    {
        // Actual production result recorded on 2026-10-12, planned start 2026-10-08
        [$batch] = $this->createBatchWithResult(
            BatchStatus::QC,
            100,
            100,
            '2026-10-12',
            '2026-10-08',
            '2026-10-12'
        );

        // Simulated clock on 2026-10-11 (before actual production date)
        \Carbon\Carbon::setTestNow('2026-10-11 16:00:00');

        $this->assertFalse($batch->fresh()->is_qc_date_eligible);

        // Attempting to visit QC create should redirect with error
        $response = $this->actingAs($this->produksiUser)
            ->get(route('produksi.batches.qc.create', $batch));

        $response->assertRedirect(route('produksi.batches.show', $batch));
        $response->assertSessionHas('error');

        // Attempting to submit QC should fail / be blocked
        $storeResponse = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch), [
                'good_qty'   => 100,
                'reject_qty' => 0,
            ]);

        $this->assertEquals(0, QcResult::count());
        $this->assertEquals(BatchStatus::QC, $batch->fresh()->status);

        // On or after actual production date (2026-10-12), QC is permitted
        \Carbon\Carbon::setTestNow('2026-10-12 08:00:00');
        $this->assertTrue($batch->fresh()->is_qc_date_eligible);

        $allowedResponse = $this->actingAs($this->produksiUser)
            ->get(route('produksi.batches.qc.create', $batch));
        $allowedResponse->assertOk();
    }

    public function test_legacy_fallback_when_production_result_is_missing(): void
    {
        // Legacy batch without production result, falls back to planned_start_date
        $variant = ProductVariant::create([
            'product_id'        => $this->product->id,
            'variant_name'      => 'Navy',
            'sku'               => 'SKU-LEGACY-' . uniqid(),
            'requires_printing' => false,
            'active'            => true,
        ]);

        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'ORD-LEGACY-' . uniqid(),
            'order_date'  => '2026-10-01',
            'status'      => OrderStatus::PRODUKSI,
        ]);

        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 50,
            'unit_selling_price' => 75000,
            'subtotal'           => 3750000,
        ]);

        $batch = ProductionBatch::create([
            'order_item_id'      => $item->id,
            'batch_no'           => 'BAT-LEGACY-' . uniqid(),
            'target_qty'         => 50,
            'status'             => BatchStatus::PRODUKSI,
            'requires_printing'  => false,
            'planned_start_date' => '2026-10-08',
            'planned_end_date'   => '2026-10-10',
        ]);

        $this->assertEquals('2026-10-08', $batch->getActualProductionDate());

        // Batch with neither production result nor planned_start_date falls back to created date
        $batchWithoutPlanned = ProductionBatch::create([
            'order_item_id'      => $item->id,
            'batch_no'           => 'BAT-NOPLAN-' . uniqid(),
            'target_qty'         => 50,
            'status'             => BatchStatus::PRODUKSI,
            'requires_printing'  => false,
        ]);
        $batchWithoutPlanned->created_at = \Carbon\Carbon::parse('2026-10-05 10:00:00');
        $batchWithoutPlanned->saveQuietly();

        $this->assertEquals('2026-10-05', $batchWithoutPlanned->fresh()->getActualProductionDate());
    }
}
