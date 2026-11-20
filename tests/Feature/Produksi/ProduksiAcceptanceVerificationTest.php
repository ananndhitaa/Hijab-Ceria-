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
use App\Services\Production\BatchWorkflowService;
use App\Services\Production\ProductionResultService;
use App\Services\Production\QcResultService;
use App\Services\Tracking\TrackingTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProduksiAcceptanceVerificationTest extends TestCase
{
    use RefreshDatabase;

    private User $produksiUser;
    private Customer $customer;
    private Unit $meterUnit;
    private Unit $pcsUnit;
    private Material $kain;
    private Material $benang;
    private MaterialStock $kainStock;
    private MaterialStock $benangStock;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->produksiUser = User::factory()->create([
            'role'   => UserRole::PRODUKSI,
            'status' => UserStatus::ACTIVE,
            'name'   => 'Budi Penjahit',
        ]);

        $this->customer = Customer::create([
            'customer_name' => 'Nayara Hijab Store',
            'phone'         => '081298765432',
        ]);

        $this->meterUnit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $this->pcsUnit = Unit::create(['unit_name' => 'Pieces', 'symbol' => 'pcs']);

        $this->kain = Material::create([
            'unit_id'       => $this->meterUnit->id,
            'material_name' => 'Kain Ceruty Babydoll',
            'min_stock'     => 10,
            'active'        => true,
        ]);
        $this->kainStock = MaterialStock::create([
            'material_id'   => $this->kain->id,
            'qty_on_hand'   => 400.0000,
            'avg_unit_cost' => 32000.0000,
        ]);

        $this->benang = Material::create([
            'unit_id'       => $this->pcsUnit->id,
            'material_name' => 'Benang Obras',
            'min_stock'     => 5,
            'active'        => true,
        ]);
        $this->benangStock = MaterialStock::create([
            'material_id'   => $this->benang->id,
            'qty_on_hand'   => 50.0000,
            'avg_unit_cost' => 12000.0000,
        ]);

        $this->product = Product::create(['product_name' => 'Khimar Ceruty Layer', 'active' => true]);
    }

    public function test_full_end_to_end_non_printing_lifecycle(): void
    {
        $variant = ProductVariant::create([
            'product_id'        => $this->product->id,
            'variant_name'      => 'Black Edition',
            'sku'               => 'SKU-BLK-' . uniqid(),
            'requires_printing' => false,
            'active'            => true,
        ]);

        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'ORD-E2E-PLAIN-' . uniqid(),
            'order_date'  => '2026-08-28',
            'status'      => OrderStatus::BAHAN,
        ]);

        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 100,
            'unit_selling_price' => 125000,
            'subtotal'           => 12500000,
        ]);

        $batch = ProductionBatch::create([
            'order_item_id'          => $item->id,
            'batch_no'               => 'BAT-E2E-001',
            'target_qty'             => 100,
            'status'                 => BatchStatus::BAHAN,
            'requires_printing'      => false,
            'production_employee_id' => $this->produksiUser->id,
        ]);

        $bmKain = BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->kain->id,
            'required_qty'  => 129.0000,
            'allocated_qty' => 129.0000,
            'used_qty'      => 0.0000,
        ]);

        $bmBenang = BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->benang->id,
            'required_qty'  => 2.0000,
            'allocated_qty' => 2.0000,
            'used_qty'      => 0.0000,
        ]);

        $initialMovementCount = StockMovement::count();
        $initialKainStock = $this->kainStock->fresh()->qty_on_hand;

        // Step 1: Start Batch (BAHAN -> PRODUKSI directly for non-printing)
        $workflowService = app(BatchWorkflowService::class);
        $workflowService->startBatch($batch, $this->produksiUser);

        $this->assertEquals(BatchStatus::PRODUKSI, $batch->fresh()->status);
        $this->assertEquals(OrderStatus::PRODUKSI, $order->fresh()->status);

        // Step 2: Record Actual Usage and Production Result (PRODUKSI -> QC)
        $resultService = app(ProductionResultService::class);
        $prodResult = $resultService->recordFinalResult(
            $batch->fresh(),
            100,
            '2026-08-29',
            'Selesai jahit dengan sedikit sisa kain',
            $this->produksiUser,
            [
                $bmKain->id   => 127.5000,
                $bmBenang->id => 1.8000,
            ]
        );

        $this->assertEquals(BatchStatus::QC, $batch->fresh()->status);
        $this->assertEquals(OrderStatus::QC, $order->fresh()->status);
        $this->assertEquals('127.5000', $bmKain->fresh()->used_qty);
        $this->assertEquals('1.8000', $bmBenang->fresh()->used_qty);
        $this->assertNotNull($batch->fresh()->material_usage_recorded_at);
        $this->assertEquals($this->produksiUser->id, $batch->fresh()->material_usage_recorded_by);

        // Revisi Pembimbing Tahap 2: Sisa bahan (1.5 m kain, 0.2 pcs benang) otomatis kembali ke gudang
        $this->assertEquals($initialMovementCount + 2, StockMovement::count());
        $this->assertEquals(bcadd($initialKainStock, '1.5000', 4), (string) $this->kainStock->fresh()->qty_on_hand);

        // Step 3: Record QC Inspection (QC -> PACKING)
        $qcService = app(QcResultService::class);
        $qcResult = $qcService->recordFinalQc(
            $batch->fresh(),
            100,
            'QC lolos 100 pcs',
            $this->produksiUser
        );

        $this->assertEquals(BatchStatus::PACKING, $batch->fresh()->status);
        $this->assertEquals(OrderStatus::PACKING, $order->fresh()->status);
        $this->assertEquals('100.0000', $qcResult->good_qty);
        $this->assertEquals('0.0000', $qcResult->reject_qty);

        // Step 4: Complete Packing (PACKING -> SELESAI)
        $workflowService->completePacking($batch->fresh(), $this->produksiUser);

        $this->assertEquals(BatchStatus::SELESAI, $batch->fresh()->status);
        // Order status capped at PACKING (Shipment boundary)
        $this->assertEquals(OrderStatus::PACKING, $order->fresh()->status);

        // Step 5: Public Customer Tracking
        $tokenService = app(TrackingTokenService::class);
        $token = $tokenService->createForOrder($order);

        $response = $this->get("/track/{$token}");
        $response->assertOk();
        $response->assertSee('Pengemasan');
    }

    public function test_full_end_to_end_printing_lifecycle(): void
    {
        $variant = ProductVariant::create([
            'product_id'        => $this->product->id,
            'variant_name'      => 'Floral Motif',
            'sku'               => 'SKU-FLR-' . uniqid(),
            'requires_printing' => true,
            'active'            => true,
        ]);

        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'ORD-E2E-PRINT-' . uniqid(),
            'order_date'  => '2026-08-28',
            'status'      => OrderStatus::BAHAN,
        ]);

        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 100,
            'unit_selling_price' => 150000,
            'subtotal'           => 15000000,
        ]);

        $batch = ProductionBatch::create([
            'order_item_id'          => $item->id,
            'batch_no'               => 'BAT-E2E-002',
            'target_qty'             => 100,
            'status'                 => BatchStatus::BAHAN,
            'requires_printing'      => true,
            'production_employee_id' => $this->produksiUser->id,
        ]);

        $bm = BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->kain->id,
            'required_qty'  => 120.0000,
            'allocated_qty' => 120.0000,
            'used_qty'      => 0.0000,
        ]);

        $workflowService = app(BatchWorkflowService::class);

        // Stage 1: BAHAN -> PRINTING
        $workflowService->startBatch($batch, $this->produksiUser);
        $this->assertEquals(BatchStatus::PRINTING, $batch->fresh()->status);
        $this->assertEquals(OrderStatus::PRINTING, $order->fresh()->status);

        // Stage 2: PRINTING -> PRODUKSI
        $workflowService->completePrinting($batch->fresh(), $this->produksiUser);
        $this->assertEquals(BatchStatus::PRODUKSI, $batch->fresh()->status);
        $this->assertEquals(OrderStatus::PRODUKSI, $order->fresh()->status);

        // Stage 3: PRODUKSI -> QC
        $resultService = app(ProductionResultService::class);
        $resultService->recordFinalResult(
            $batch->fresh(),
            100,
            '2026-08-29',
            'Hasil cetak & jahit rapi',
            $this->produksiUser,
            [$bm->id => 120.0000]
        );
        $this->assertEquals(BatchStatus::QC, $batch->fresh()->status);
        $this->assertEquals(OrderStatus::QC, $order->fresh()->status);

        // Stage 4: QC -> PACKING
        $qcService = app(QcResultService::class);
        $qcService->recordFinalQc(
            $batch->fresh(),
            100,
            '100% Good',
            $this->produksiUser
        );
        $this->assertEquals(BatchStatus::PACKING, $batch->fresh()->status);
        $this->assertEquals(OrderStatus::PACKING, $order->fresh()->status);

        // Stage 5: PACKING -> SELESAI
        $workflowService->completePacking($batch->fresh(), $this->produksiUser);
        $this->assertEquals(BatchStatus::SELESAI, $batch->fresh()->status);
        $this->assertEquals(OrderStatus::PACKING, $order->fresh()->status);
    }

    public function test_split_mixed_batch_order_aggregation(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'ORD-SPLIT-' . uniqid(),
            'order_date'  => '2026-08-28',
            'status'      => OrderStatus::BAHAN,
        ]);

        $varPlain = ProductVariant::create(['product_id' => $this->product->id, 'variant_name' => 'Plain', 'sku' => 'V-PL-' . uniqid(), 'requires_printing' => false]);
        $varPrint = ProductVariant::create(['product_id' => $this->product->id, 'variant_name' => 'Print', 'sku' => 'V-PR-' . uniqid(), 'requires_printing' => true]);

        $item1 = OrderItem::create(['order_id' => $order->id, 'variant_id' => $varPlain->id, 'qty_ordered' => 100, 'unit_selling_price' => 100000, 'subtotal' => 10000000]);
        $item2 = OrderItem::create(['order_id' => $order->id, 'variant_id' => $varPrint->id, 'qty_ordered' => 100, 'unit_selling_price' => 120000, 'subtotal' => 12000000]);

        $batchA = ProductionBatch::create(['order_item_id' => $item1->id, 'batch_no' => 'B-A-' . uniqid(), 'target_qty' => 100, 'status' => BatchStatus::BAHAN, 'requires_printing' => false, 'production_employee_id' => $this->produksiUser->id]);
        $bmA = BatchMaterial::create(['batch_id' => $batchA->id, 'material_id' => $this->kain->id, 'required_qty' => 100, 'allocated_qty' => 100]);

        $batchB = ProductionBatch::create(['order_item_id' => $item2->id, 'batch_no' => 'B-B-' . uniqid(), 'target_qty' => 100, 'status' => BatchStatus::BAHAN, 'requires_printing' => true, 'production_employee_id' => $this->produksiUser->id]);
        $bmB = BatchMaterial::create(['batch_id' => $batchB->id, 'material_id' => $this->kain->id, 'required_qty' => 100, 'allocated_qty' => 100]);

        $workflowService = app(BatchWorkflowService::class);
        $resultService = app(ProductionResultService::class);
        $qcService = app(QcResultService::class);

        // Start Batch A (Plain -> PRODUKSI) & Batch B (Print -> PRINTING)
        $workflowService->startBatch($batchA, $this->produksiUser);
        $workflowService->startBatch($batchB, $this->produksiUser);

        $this->assertEquals(BatchStatus::PRODUKSI, $batchA->fresh()->status);
        $this->assertEquals(BatchStatus::PRINTING, $batchB->fresh()->status);
        // Lowest candidate rank is PRINTING (rank 2)
        $this->assertEquals(OrderStatus::PRINTING, $order->fresh()->status);

        // Advance Batch B: PRINTING -> PRODUKSI
        $workflowService->completePrinting($batchB->fresh(), $this->produksiUser);
        $this->assertEquals(BatchStatus::PRODUKSI, $batchB->fresh()->status);
        // Both now at PRODUKSI -> Order becomes PRODUKSI
        $this->assertEquals(OrderStatus::PRODUKSI, $order->fresh()->status);

        // Advance Batch A through QC, Packing, and SELESAI
        $resultService->recordFinalResult($batchA->fresh(), 100, '2026-08-29', null, $this->produksiUser, [$bmA->id => 100]);
        $qcService->recordFinalQc($batchA->fresh(), 100, null, $this->produksiUser);
        $workflowService->completePacking($batchA->fresh(), $this->produksiUser);

        $this->assertEquals(BatchStatus::SELESAI, $batchA->fresh()->status);
        $this->assertEquals(BatchStatus::PRODUKSI, $batchB->fresh()->status);
        // Batch A is SELESAI (candidate PACKING rank 5), but Batch B is PRODUKSI (rank 3) -> Order is PRODUKSI
        $this->assertEquals(OrderStatus::PRODUKSI, $order->fresh()->status);

        // Advance Batch B to QC
        $resultService->recordFinalResult($batchB->fresh(), 100, '2026-08-29', null, $this->produksiUser, [$bmB->id => 100]);
        $this->assertEquals(BatchStatus::QC, $batchB->fresh()->status);
        // Order becomes QC
        $this->assertEquals(OrderStatus::QC, $order->fresh()->status);

        // Advance Batch B to PACKING and SELESAI
        $qcService->recordFinalQc($batchB->fresh(), 100, null, $this->produksiUser);
        $workflowService->completePacking($batchB->fresh(), $this->produksiUser);

        $this->assertEquals(BatchStatus::SELESAI, $batchA->fresh()->status);
        $this->assertEquals(BatchStatus::SELESAI, $batchB->fresh()->status);
        // All batches SELESAI -> Order is PACKING (capped at shipment boundary)
        $this->assertEquals(OrderStatus::PACKING, $order->fresh()->status);
    }

    public function test_unbatched_remainder_keeps_order_at_bahan(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'ORD-REM-' . uniqid(),
            'order_date'  => '2026-08-28',
            'status'      => OrderStatus::BAHAN,
        ]);

        $variant = ProductVariant::create([
            'product_id'        => $this->product->id,
            'variant_name'      => 'Mocca',
            'sku'               => 'SKU-MOC-' . uniqid(),
            'requires_printing' => false,
            'active'            => true,
        ]);

        // Order item: 200 pcs ordered
        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 200,
            'unit_selling_price' => 100000,
            'subtotal'           => 20000000,
        ]);

        // Only 100 pcs batched
        $batch = ProductionBatch::create([
            'order_item_id'          => $item->id,
            'batch_no'               => 'BAT-PARTIAL-' . uniqid(),
            'target_qty'             => 100,
            'status'                 => BatchStatus::BAHAN,
            'requires_printing'      => false,
            'production_employee_id' => $this->produksiUser->id,
        ]);

        $bm = BatchMaterial::create(['batch_id' => $batch->id, 'material_id' => $this->kain->id, 'required_qty' => 100, 'allocated_qty' => 100]);

        $workflowService = app(BatchWorkflowService::class);
        $resultService = app(ProductionResultService::class);
        $qcService = app(QcResultService::class);

        // Advance this batch all the way to SELESAI
        $workflowService->startBatch($batch, $this->produksiUser);
        $resultService->recordFinalResult($batch->fresh(), 100, '2026-08-29', null, $this->produksiUser, [$bm->id => 100]);
        $qcService->recordFinalQc($batch->fresh(), 100, null, $this->produksiUser);
        $workflowService->completePacking($batch->fresh(), $this->produksiUser);

        $this->assertEquals(BatchStatus::SELESAI, $batch->fresh()->status);
        // Order remains BAHAN because 100 pcs remain unbatched!
        $this->assertEquals(OrderStatus::BAHAN, $order->fresh()->status);
    }

    public function test_customer_tracking_preserves_internal_privacy(): void
    {
        $variant = ProductVariant::create([
            'product_id'        => $this->product->id,
            'variant_name'      => 'Lavender',
            'sku'               => 'SKU-LAV-' . uniqid(),
            'requires_printing' => false,
            'active'            => true,
        ]);

        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'ORD-PRIVACY-' . uniqid(),
            'order_date'  => '2026-08-28',
            'status'      => OrderStatus::PACKING,
        ]);

        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 100,
            'unit_selling_price' => 100000,
            'subtotal'           => 10000000,
        ]);

        $batch = ProductionBatch::create([
            'order_item_id'     => $item->id,
            'batch_no'          => 'BAT-PRIVACY-01',
            'target_qty'        => 100,
            'status'            => BatchStatus::PACKING,
            'requires_printing' => false,
        ]);

        $bm = BatchMaterial::create(['batch_id' => $batch->id, 'material_id' => $this->kain->id, 'required_qty' => 129, 'allocated_qty' => 129, 'used_qty' => 127.5]);

        $pr = ProductionResult::create([
            'batch_id'    => $batch->id,
            'actual_qty'  => 98,
            'result_date' => '2026-08-29',
            'notes'       => 'Jahitan cacat serat 2 pcs pada mesin 4',
            'recorded_by' => $this->produksiUser->id,
        ]);

        QcResult::create([
            'production_result_id' => $pr->id,
            'good_qty'             => 96,
            'reject_qty'           => 2,
            'qc_date'              => '2026-08-29',
            'notes'                => 'Reject kain rusak benang putus',
            'qc_by'                => $this->produksiUser->id,
        ]);

        $tokenService = app(TrackingTokenService::class);
        $token = $tokenService->createForOrder($order);

        $response = $this->get("/track/{$token}");
        $response->assertOk();

        // Must see tracking status
        $response->assertSee('Pengemasan');

        // Must NOT leak internal operational / defect data
        $response->assertDontSee('Jahitan cacat serat 2 pcs pada mesin 4');
        $response->assertDontSee('Reject kain rusak benang putus');
        $response->assertDontSee('127.5');
        $response->assertDontSee('Budi Penjahit');
    }
}
