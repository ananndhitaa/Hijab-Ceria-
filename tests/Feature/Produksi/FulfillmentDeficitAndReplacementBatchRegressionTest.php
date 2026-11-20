<?php

namespace Tests\Feature\Produksi;

use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Inventory\MaterialStock;
use App\Models\Master\Customer;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Master\Unit;
use App\Models\Production\BatchMaterial;
use App\Models\Production\ProductionBatch;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\User;
use App\Services\Production\BatchService;
use App\Services\Production\BatchWorkflowService;
use App\Services\Production\QcResultService;
use App\Services\Production\ShipmentService;
use App\Services\Sales\OrderStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FulfillmentDeficitAndReplacementBatchRegressionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $produksiUser;
    private User $gudangUser;
    private Customer $customer;
    private ProductVariant $variant;
    private Material $kain;
    private BatchService $batchService;
    private BatchWorkflowService $workflowService;
    private QcResultService $qcService;
    private OrderStatusService $orderStatusService;
    private ShipmentService $shipmentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->batchService = app(BatchService::class);
        $this->workflowService = app(BatchWorkflowService::class);
        $this->qcService = app(QcResultService::class);
        $this->orderStatusService = app(OrderStatusService::class);
        $this->shipmentService = app(ShipmentService::class);

        $this->admin = User::factory()->create([
            'name'   => 'Admin User',
            'role'   => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->produksiUser = User::factory()->create([
            'name'   => 'Staff Produksi',
            'role'   => UserRole::PRODUKSI,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->gudangUser = User::factory()->create([
            'name'   => 'Staff Gudang',
            'role'   => UserRole::GUDANG,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->customer = Customer::create([
            'customer_name' => 'Customer UAT',
            'phone'         => '08123456789',
        ]);

        $unit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $this->kain = Material::create([
            'material_name' => 'Kain Voal',
            'unit_id'       => $unit->id,
            'cost_per_unit' => 25000,
            'active'        => true,
        ]);

        MaterialStock::create([
            'material_id'   => $this->kain->id,
            'qty_on_hand'   => 500,
            'avg_unit_cost' => 25000,
        ]);

        $product = Product::create([
            'product_name' => 'Hijab Voal Premium',
            'active'       => true,
        ]);

        $this->variant = ProductVariant::create([
            'product_id'                           => $product->id,
            'variant_name'                         => 'Dusty Pink',
            'sku'                                  => 'HJB-VOAL-DPK',
            'requires_printing'                    => false,
            'direct_labor_cost_per_unit'           => '5000.00',
            'packaging_process_cost_per_good_unit' => '1000.00',
            'other_direct_cost_per_unit'           => '500.00',
            'active'                               => true,
        ]);

        \App\Models\Master\BomItem::create([
            'variant_id'   => $this->variant->id,
            'material_id'  => $this->kain->id,
            'qty_per_unit' => 1.5,
        ]);
    }

    /**
     * Exact Scenario Test:
     * OrderItem qty_ordered = 30
     * Batch 1: target = 30, actual = 7, good = 1, reject = 6
     * After QC:
     *   Batch = PACKING
     *   confirmed_good = 1
     *   pending = 0
     *   remaining_to_plan = 29
     *   Order = BAHAN
     * Admin may create replacement Batch max 29.
     * Shipment = NOT ELIGIBLE.
     * After Batch 1 Packing completion:
     *   Batch 1 = SELESAI
     *   Order remains BAHAN.
     */
    public function test_exact_uat_scenario_full_lifecycle(): void
    {
        // 1. Create Sales Order with DEAL status, qty_ordered = 30
        $order = Order::create([
            'customer_id'  => $this->customer->id,
            'order_no'     => 'ORD-202609-0001',
            'order_date'   => now()->toDateString(),
            'status'       => OrderStatus::DEAL,
            'total_qty'    => 30,
            'total_amount' => 30 * 75000,
        ]);

        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => 30,
            'unit_selling_price' => 75000,
            'subtotal'           => 30 * 75000,
        ]);

        $inv = \App\Models\Sales\Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-FDF-001',
            'invoice_date' => now()->toDateString(),
            'total_amount' => (string) (30 * 75000),
            'status'       => \App\Enums\InvoiceStatus::PARTIAL,
        ]);
        \App\Models\Sales\Payment::create([
            'invoice_id'   => $inv->id,
            'payment_no'   => 'PAY-FDF-001',
            'payment_date' => now()->toDateString(),
            'amount'       => (string) (15 * 75000),
            'payment_type' => \App\Enums\PaymentType::DP,
            'status'       => \App\Enums\PaymentStatus::CONFIRMED,
        ]);

        // 2. Admin creates Batch 1: target = 30
        $createRes1 = $this->batchService->createBatch($order, [
            'order_item_id' => $item->id,
            'target_qty'    => 30,
        ], $this->admin);

        $batch1 = $createRes1['batch'];
        $this->assertEquals(30, (float) $batch1->target_qty);
        $this->assertEquals(BatchStatus::BAHAN, $batch1->status);
        $this->assertEquals(OrderStatus::BAHAN, $order->fresh()->status);

        // 3. Allocate materials for Batch 1
        foreach ($batch1->batchMaterials as $bm) {
            $bm->update(['allocated_qty' => $bm->required_qty]);
        }

        // 4. Produksi starts Batch 1 (Tanpa printing -> directly to PRODUKSI)
        $this->workflowService->startBatch($batch1, $this->produksiUser);
        $batch1->refresh();
        $this->assertEquals(BatchStatus::PRODUKSI, $batch1->status);

        // Order status synced to PRODUKSI
        $this->assertEquals(OrderStatus::PRODUKSI, $order->fresh()->status);

        // 5. Produksi records Production Result: actual = 7
        $prodRes = \App\Models\Production\ProductionResult::create([
            'batch_id'    => $batch1->id,
            'actual_qty'  => 7,
            'result_date' => now()->toDateString(),
            'recorded_by' => $this->produksiUser->id,
        ]);

        // Batch transitions to QC
        $batch1->update(['status' => BatchStatus::QC]);
        $this->orderStatusService->syncOperationalStatus($order);
        $this->assertEquals(OrderStatus::QC, $order->fresh()->status);

        // 6. Test QC create page rendering
        $response = $this->actingAs($this->produksiUser)->get(route('produksi.batches.qc.create', $batch1));
        $response->assertOk();
        $response->assertSee('Pemeriksaan Quality Control');
        $response->assertSee('Dampak ke Pesanan');

        // 7. Produksi records QC: Good = 1, Reject = 6
        $this->qcService->recordFinalQc(
            $batch1,
            1, // good_qty
            'Kain cacat printing dan jahitan 6 pcs afkir',
            $this->produksiUser
        );

        $batch1->refresh();
        // Batch transitions: QC -> PACKING
        $this->assertEquals(BatchStatus::PACKING, $batch1->status);

        // Verify Coverage Metrics
        $coverage = $this->batchService->calculateProductionCoverage($item);
        $this->assertEquals('1.0000', $coverage['confirmed_good_qty']);
        $this->assertEquals('0.0000', $coverage['pending_production_qty']);
        $this->assertEquals('1.0000', $coverage['production_coverage']);
        $this->assertEquals('29.0000', $coverage['remaining_to_plan']);
        $this->assertEquals('6.0000', $coverage['total_reject_qty']);

        // Verify Order Status after QC: Order MUST revert to BAHAN (lowest unfinished operational stage)
        $this->assertEquals(OrderStatus::BAHAN, $order->fresh()->status);

        // 8. Test Produksi Batch Detail UX while in PACKING with deficit
        $batchShowResponse = $this->actingAs($this->produksiUser)->get(route('produksi.batches.show', $batch1));
        $batchShowResponse->assertOk();
        $batchShowResponse->assertSee('Pesanan Belum Terpenuhi');
        $batchShowResponse->assertSee('Batch ini menghasilkan');
        $batchShowResponse->assertSee('1 pcs');
        $batchShowResponse->assertSee('29 pcs');
        $batchShowResponse->assertSee('Selesaikan Packing');

        // 9. Test Admin Order Detail UX
        $orderShowResponse = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));
        $orderShowResponse->assertOk();
        $orderShowResponse->assertSee('Produksi Tambahan Diperlukan 29 pcs');
        $orderShowResponse->assertSee('Buat Batch');

        // 10. Verify Shipment is BLOCKED
        $this->assertFalse($this->shipmentService->canPrepareShipment($order->fresh()));
        $eligibility = $this->shipmentService->getEligibility($order->fresh());
        $this->assertFalse($eligibility['is_eligible']);
        $this->assertFalse($eligibility['status_is_packing']);
        $this->assertFalse($eligibility['all_batches_finished']);
        $this->assertFalse($eligibility['all_items_fulfilled']);

        // 11. Produksi completes Batch 1 Packing -> Batch 1 becomes SELESAI
        $this->workflowService->completePacking($batch1, $this->produksiUser);
        $batch1->refresh();
        $this->assertEquals(BatchStatus::SELESAI, $batch1->status);

        // Order status REMAINS BAHAN
        $this->assertEquals(OrderStatus::BAHAN, $order->fresh()->status);

        // Batch show UX after SELESAI with deficit
        $batchShowSelesai = $this->actingAs($this->produksiUser)->get(route('produksi.batches.show', $batch1));
        $batchShowSelesai->assertOk();
        $batchShowSelesai->assertSee('Batch Selesai');
        $batchShowSelesai->assertSee('Produksi Tambahan Diperlukan');
        $batchShowSelesai->assertSee('29 pcs');
        $batchShowSelesai->assertDontSee('Status Pesanan: Menunggu Pengiriman');

        // Shipment is STILL BLOCKED
        $this->assertFalse($this->shipmentService->canPrepareShipment($order->fresh()));

        // 12. Admin creates replacement Batch 2: max allowed is 29
        $this->assertTrue($this->batchService->canCreateForOrder($order->fresh()));
        $this->assertEquals('29.0000', $this->batchService->calculateRemainingQty($item));

        // Attempting to create batch target > 29 throws DomainException
        $this->expectException(\DomainException::class);
        $this->batchService->createBatch($order->fresh(), [
            'order_item_id' => $item->id,
            'target_qty'    => 30,
        ], $this->admin);
    }

    /**
     * Test full fulfillment resolution with replacement batch
     */
    public function test_full_replacement_batch_fulfills_order_for_shipping(): void
    {
        // 1. Create Sales Order
        $order = Order::create([
            'customer_id'  => $this->customer->id,
            'order_no'     => 'ORD-202609-0002',
            'order_date'   => now()->toDateString(),
            'status'       => OrderStatus::BAHAN,
            'total_qty'    => 30,
            'total_amount' => 30 * 75000,
        ]);

        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => 30,
            'unit_selling_price' => 75000,
            'subtotal'           => 30 * 75000,
        ]);

        $invoice = \App\Models\Sales\Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-' . uniqid(),
            'invoice_date' => now()->toDateString(),
            'total_amount' => '2250000.00',
            'status'       => \App\Enums\InvoiceStatus::PAID,
        ]);

        \App\Models\Sales\Payment::create([
            'invoice_id'   => $invoice->id,
            'payment_no'   => 'PAY-' . uniqid(),
            'payment_date' => now()->toDateString(),
            'amount'       => '2250000.00',
            'payment_type' => \App\Enums\PaymentType::PELUNASAN,
            'status'       => \App\Enums\PaymentStatus::CONFIRMED,
        ]);

        // Batch 1: target 30, actual 7, good 1, reject 6 -> SELESAI
        $b1 = ProductionBatch::create([
            'order_item_id'     => $item->id,
            'batch_no'          => 'BAT-202609-0003',
            'target_qty'        => 30,
            'status'            => BatchStatus::SELESAI,
            'requires_printing' => false,
        ]);
        $pr1 = \App\Models\Production\ProductionResult::create([
            'batch_id'    => $b1->id,
            'actual_qty'  => 7,
            'result_date' => now()->toDateString(),
            'recorded_by' => $this->admin->id,
        ]);
        \App\Models\Production\QcResult::create([
            'production_result_id' => $pr1->id,
            'good_qty'             => 1,
            'reject_qty'           => 6,
            'qc_date'              => now()->toDateString(),
            'qc_by'                => $this->admin->id,
        ]);

        $this->orderStatusService->syncOperationalStatus($order);
        $this->assertEquals(OrderStatus::BAHAN, $order->fresh()->status);

        // 2. Admin creates replacement Batch 2: target 29
        $createRes2 = $this->batchService->createBatch($order->fresh(), [
            'order_item_id' => $item->id,
            'target_qty'    => 29,
        ], $this->admin);

        $b2 = $createRes2['batch'];
        $this->assertEquals('29.0000', (string) $b2->target_qty);

        // Allocate materials for Batch 2
        foreach ($b2->batchMaterials as $bm) {
            $bm->update(['allocated_qty' => $bm->required_qty]);
        }

        // Start Batch 2 -> PRODUKSI
        $this->workflowService->startBatch($b2, $this->produksiUser);
        $b2->refresh();
        $this->assertEquals(BatchStatus::PRODUKSI, $b2->status);

        // Order status becomes PRODUKSI (lowest rank between SELESAI[candidate PACKING] and PRODUKSI)
        $this->assertEquals(OrderStatus::PRODUKSI, $order->fresh()->status);

        // Record production result for Batch 2: actual = 29
        $pr2 = \App\Models\Production\ProductionResult::create([
            'batch_id'    => $b2->id,
            'actual_qty'  => 29,
            'result_date' => now()->toDateString(),
            'recorded_by' => $this->produksiUser->id,
        ]);

        $b2->update(['status' => BatchStatus::QC]);
        $this->orderStatusService->syncOperationalStatus($order);
        $this->assertEquals(OrderStatus::QC, $order->fresh()->status);

        // Record QC for Batch 2: good = 29, reject = 0
        $this->qcService->recordFinalQc(
            $b2,
            29,
            'Semua produk baik lolos QC',
            $this->produksiUser
        );

        $b2->refresh();
        $this->assertEquals(BatchStatus::PACKING, $b2->status);

        // Total confirmed good = 1 + 29 = 30 == ordered_qty 30.
        // Order status transitions to PACKING!
        $this->assertEquals(OrderStatus::PACKING, $order->fresh()->status);

        // Complete Batch 2 packing
        $this->workflowService->completePacking($b2, $this->produksiUser);
        $b2->refresh();
        $this->assertEquals(BatchStatus::SELESAI, $b2->status);

        // Order status remains PACKING
        $this->assertEquals(OrderStatus::PACKING, $order->fresh()->status);

        // 3. Verify Shipment is now FULLY ELIGIBLE
        $eligibility = $this->shipmentService->getEligibility($order->fresh());
        $this->assertTrue($eligibility['is_eligible']);
        $this->assertTrue($eligibility['status_is_packing']);
        $this->assertTrue($eligibility['all_batches_finished']);
        $this->assertTrue($eligibility['all_items_fulfilled']);
        $this->assertTrue($this->shipmentService->canPrepareShipment($order->fresh()));

        // Batch show UX when fully fulfilled and order is PACKING
        $b2Show = $this->actingAs($this->produksiUser)->get(route('produksi.batches.show', $b2));
        $b2Show->assertOk();
        $b2Show->assertSee('Status Pesanan: Menunggu Pengiriman');
    }
}
