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
use App\Models\Production\ProductionResult;
use App\Models\Production\QcResult;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\User;
use App\Services\Production\BatchWorkflowService;
use App\Services\Production\ProductionResultService;
use App\Services\Production\QcResultService;
use App\Support\DateTimeFormatter;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionSupervisorRevisionPhase1Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $produksiUser;
    private User $gudangUser;
    private Customer $customer;
    private Unit $meterUnit;
    private Material $kain;
    private MaterialStock $kainStock;
    private Product $product;
    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role'   => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
            'name'   => 'Admin Nayara',
        ]);

        $this->produksiUser = User::factory()->create([
            'role'   => UserRole::PRODUKSI,
            'status' => UserStatus::ACTIVE,
            'name'   => 'Budi Produksi',
        ]);

        $this->gudangUser = User::factory()->create([
            'role'   => UserRole::GUDANG,
            'status' => UserStatus::ACTIVE,
            'name'   => 'Gudang Agus',
        ]);

        $this->customer = Customer::create([
            'customer_name' => 'Nayara Boutique',
            'phone'         => '081234567890',
        ]);

        $this->meterUnit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);

        $this->kain = Material::create([
            'unit_id'       => $this->meterUnit->id,
            'material_name' => 'Kain Ceruty Silk',
            'min_stock'     => 10,
            'active'        => true,
        ]);

        $this->kainStock = MaterialStock::create([
            'material_id'   => $this->kain->id,
            'qty_on_hand'   => 500.0000,
            'avg_unit_cost' => 30000.0000,
        ]);

        $this->product = Product::create([
            'product_name' => 'Pashmina Silk Luxury',
            'active'       => true,
        ]);

        $this->variant = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'Dusty Rose',
            'sku'                                  => 'VAR-DUSTY-001',
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => '0.00',
            'direct_labor_cost_per_unit'           => '5000.00',
            'packaging_process_cost_per_good_unit' => '1000.00',
            'other_direct_cost_per_unit'           => '500.00',
            'active'                               => true,
        ]);
    }

    protected function tearDown(): void
    {
        \Carbon\Carbon::setTestNow();
        parent::tearDown();
    }

    private function createOrderAndBatch(
        float $targetQty = 50,
        ?string $plannedEndDate = '2026-10-15',
        BatchStatus $status = BatchStatus::PRODUKSI
    ): array {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-' . uniqid(),
            'order_date'  => '2026-10-01',
            'status'      => OrderStatus::PRODUKSI,
        ]);

        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => $targetQty,
            'unit_selling_price' => 75000,
            'subtotal'           => $targetQty * 75000,
        ]);

        $batch = ProductionBatch::create([
            'order_item_id'          => $orderItem->id,
            'batch_no'               => 'BAT-' . uniqid(),
            'target_qty'             => $targetQty,
            'status'                 => $status,
            'planned_end_date'       => $plannedEndDate,
            'production_employee_id' => $this->produksiUser->id,
        ]);

        $bm = BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->kain->id,
            'required_qty'  => $targetQty * 1.5,
            'allocated_qty' => $targetQty * 1.5,
            'used_qty'      => '0.0000',
        ]);

        return [$order, $orderItem, $batch, $bm];
    }

    // =========================================================================
    // REQUIREMENT A — TARGET TANGGAL PRODUKSI
    // =========================================================================

    public function test_admin_can_set_production_target_date(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-TGT-001',
            'order_date'  => '2026-10-01',
            'status'      => OrderStatus::DEAL,
        ]);

        // Deal order must have confirmed DP to create batch
        $invoice = \App\Models\Sales\Invoice::create([
            'order_id'       => $order->id,
            'invoice_no'     => 'INV-TGT-001',
            'invoice_date'   => '2026-10-01',
            'total_amount'   => 3750000,
            'status'         => \App\Enums\InvoiceStatus::PARTIAL,
            'payment_status' => \App\Enums\InvoiceStatus::PARTIAL,
        ]);
        \App\Models\Sales\Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_no'     => 'PAY-TGT-001',
            'payment_date'   => '2026-10-01',
            'payment_type'   => \App\Enums\PaymentType::DP,
            'amount'         => 1875000,
            'status'         => \App\Enums\PaymentStatus::CONFIRMED,
            'payment_method' => \App\Enums\PaymentMethod::TRANSFER_BANK,
        ]);

        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => 50,
            'unit_selling_price' => 75000,
            'subtotal'           => 3750000,
        ]);

        // Variant BOM
        \App\Models\Master\BomItem::create([
            'variant_id'   => $this->variant->id,
            'material_id'  => $this->kain->id,
            'qty_per_unit' => 1.5,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.orders.batches.store', $order), [
                'order_item_id'    => $item->id,
                'target_qty'       => 50,
                'planned_end_date' => '2026-10-15',
            ]);

        $response->assertSessionHasNoErrors();
        $batch = ProductionBatch::where('order_item_id', $item->id)->first();
        $this->assertNotNull($batch);
        $this->assertEquals('2026-10-15', $batch->planned_end_date->toDateString());
    }

    public function test_admin_cannot_create_batch_without_target_date(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-TGT-002',
            'order_date'  => '2026-10-01',
            'status'      => OrderStatus::DEAL,
        ]);

        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => 50,
            'unit_selling_price' => 75000,
            'subtotal'           => 3750000,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.orders.batches.store', $order), [
                'order_item_id' => $item->id,
                'target_qty'    => 50,
                // planned_end_date omitted
            ]);

        $response->assertSessionHasErrors('planned_end_date');
        $this->assertEquals(0, ProductionBatch::where('order_item_id', $item->id)->count());
    }

    public function test_produksi_can_see_target_date_on_queue_and_detail(): void
    {
        [, , $batch] = $this->createOrderAndBatch(50, '2026-10-25');

        $expectedFormattedDate = DateTimeFormatter::formatDate($batch->planned_end_date, 'd M Y');

        // Queue view
        $indexResponse = $this->actingAs($this->produksiUser)
            ->get(route('produksi.batches.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee($expectedFormattedDate);

        // Detail view
        $showResponse = $this->actingAs($this->produksiUser)
            ->get(route('produksi.batches.show', $batch));
        $showResponse->assertOk();
        $showResponse->assertSee('Target Selesai');
        $showResponse->assertSee($expectedFormattedDate);
    }

    public function test_gudang_does_not_see_target_date_in_material_planning(): void
    {
        [, , $batch] = $this->createOrderAndBatch(50, '2026-10-25');

        $expectedFormattedDate = DateTimeFormatter::formatDate($batch->planned_end_date, 'd M Y');

        // Material planning index: Gudang must NOT see production deadline date
        $indexResponse = $this->actingAs($this->gudangUser)
            ->get(route('gudang.material-planning.index'));
        $indexResponse->assertOk();
        $indexResponse->assertDontSee($expectedFormattedDate);
        $indexResponse->assertDontSee('Target Selesai');

        // Material planning show: Gudang must NOT see production deadline date
        $showResponse = $this->actingAs($this->gudangUser)
            ->get(route('gudang.material-planning.show', $batch));
        $showResponse->assertOk();
        $showResponse->assertDontSee($expectedFormattedDate);
        $showResponse->assertDontSee('Target Selesai');
        $showResponse->assertSee('Target Batch');
    }

    public function test_produksi_cannot_modify_target_date(): void
    {
        [, , $batch] = $this->createOrderAndBatch(50, '2026-10-15');

        // Produksi has no route/ability to update batch header or target date
        $response = $this->actingAs($this->produksiUser)
            ->put("/produksi/batches/{$batch->id}", [
                'planned_end_date' => '2026-12-31',
            ]);

        // Method not allowed or not found
        $this->assertTrue(in_array($response->status(), [404, 405, 403]));
        $this->assertEquals('2026-10-15', $batch->fresh()->planned_end_date->toDateString());
    }

    // =========================================================================
    // REQUIREMENT B — ACTUAL QTY TIDAK BOLEH LEBIH DARI TARGET
    // =========================================================================

    public function test_actual_equals_target_accepted(): void
    {
        [, , $batch, $bm] = $this->createOrderAndBatch(50, '2026-10-15');

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 50,
                'result_date'    => '2026-10-07',
                'material_usage' => [$bm->id => 75],
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');
        $this->assertEquals(BatchStatus::QC, $batch->fresh()->status);
        $this->assertEquals('50.0000', $batch->productionResult->actual_qty);
    }

    public function test_actual_less_than_target_accepted(): void
    {
        [, , $batch, $bm] = $this->createOrderAndBatch(50, '2026-10-15');

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 48,
                'result_date'    => '2026-10-07',
                'material_usage' => [$bm->id => 75],
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');
        $this->assertEquals(BatchStatus::QC, $batch->fresh()->status);
        $this->assertEquals('48.0000', $batch->productionResult->actual_qty);
    }

    public function test_actual_greater_than_target_accepted_in_request(): void
    {
        [, , $batch, $bm] = $this->createOrderAndBatch(50, '2026-10-15');

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 51,
                'result_date'    => '2026-10-07',
                'material_usage' => [$bm->id => 75],
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');
        $this->assertEquals(BatchStatus::QC, $batch->fresh()->status);
        $this->assertNotNull($batch->fresh()->productionResult);
        $this->assertEquals('50.0000', (string) $batch->fresh()->target_qty);
        $this->assertEquals('51.0000', (string) $batch->fresh()->productionResult->actual_qty);
    }

    public function test_direct_call_actual_greater_than_target_accepted_in_service(): void
    {
        [, , $batch, $bm] = $this->createOrderAndBatch(50, '2026-10-15');

        $service = app(ProductionResultService::class);

        $result = $service->recordFinalResult(
            $batch,
            55, // 55 > 50 target
            '2026-10-07',
            null,
            $this->produksiUser,
            [$bm->id => 75]
        );

        $this->assertNotNull($result);
        $this->assertEquals('55.0000', (string) $result->actual_qty);
        $this->assertEquals('50.0000', (string) $batch->fresh()->target_qty);
        $this->assertEquals(BatchStatus::QC, $batch->fresh()->status);
    }

    // =========================================================================
    // REQUIREMENT C, D, E — QC (GOOD <= ACTUAL, AUTO REJECT, AUTO TIMESTAMP)
    // =========================================================================

    private function createBatchReadyForQc(
        float $targetQty = 50,
        float $actualQty = 48,
        ?string $productionDate = '2026-10-07'
    ): ProductionBatch {
        [, , $batch, $bm] = $this->createOrderAndBatch($targetQty, '2026-10-15');

        if ($productionDate) {
            $batch->update(['planned_start_date' => $productionDate]);
        }

        ProductionResult::create([
            'batch_id'    => $batch->id,
            'actual_qty'  => $actualQty,
            'result_date' => $productionDate ?? '2026-10-07',
            'recorded_by' => $this->produksiUser->id,
        ]);

        $batch->update(['status' => BatchStatus::QC]);

        return $batch;
    }

    public function test_good_equals_actual_results_in_zero_reject(): void
    {
        $batch = $this->createBatchReadyForQc(50, 48);

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch), [
                'good_qty' => 48,
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');
        $this->assertEquals(BatchStatus::PACKING, $batch->fresh()->status);

        $qc = QcResult::first();
        $this->assertEquals('48.0000', $qc->good_qty);
        $this->assertEquals('0.0000', $qc->reject_qty);
    }

    public function test_good_less_than_actual_automatically_calculates_reject(): void
    {
        $batch = $this->createBatchReadyForQc(50, 48);

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch), [
                'good_qty' => 45,
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');
        $this->assertEquals(BatchStatus::PACKING, $batch->fresh()->status);

        $qc = QcResult::first();
        $this->assertEquals('45.0000', $qc->good_qty);
        $this->assertEquals('3.0000', $qc->reject_qty); // 48 - 45 = 3
    }

    public function test_good_greater_than_actual_rejected_in_request_and_service(): void
    {
        $batch = $this->createBatchReadyForQc(50, 48);

        // Request validation layer
        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch), [
                'good_qty' => 49, // 49 > 48
            ]);

        $response->assertSessionHasErrors('good_qty');
        $this->assertEquals(
            'Jumlah lolos QC tidak boleh melebihi hasil produksi aktual.',
            session('errors')->first('good_qty')
        );
        $this->assertEquals(0, QcResult::count());

        // Direct Service layer
        $qcService = app(QcResultService::class);
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Jumlah lolos QC (49) tidak boleh melebihi hasil produksi aktual (48 pcs).');

        $qcService->recordFinalQc(
            $batch,
            49,
            null,
            $this->produksiUser
        );
    }

    public function test_client_supplied_reject_cannot_manipulate_result(): void
    {
        $batch = $this->createBatchReadyForQc(50, 48);

        // Malicious client posts reject_qty = 0 to hide defects
        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch), [
                'good_qty'   => 45,
                'reject_qty' => 0,
            ]);

        $response->assertSessionHasNoErrors();
        $qc = QcResult::first();
        $this->assertEquals('45.0000', $qc->good_qty);
        // Server MUST recompute: reject = 48 - 45 = 3
        $this->assertEquals('3.0000', $qc->reject_qty);
    }

    public function test_qc_blocked_when_current_business_date_before_production_date(): void
    {
        // 1. Production date = 10 Oct 2026, current business date = 9 Oct 2026 -> Blocked
        $batch = $this->createBatchReadyForQc(50, 48, '2026-10-10');
        \Carbon\Carbon::setTestNow('2026-10-09 10:00:00');

        // FormRequest / HTTP layer: fails with validation error
        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch), [
                'good_qty' => 45,
            ]);

        $response->assertSessionHasErrors('good_qty');
        $this->assertEquals(
            'QC belum dapat dilakukan sebelum tanggal produksi.',
            session('errors')->first('good_qty')
        );
        $this->assertEquals(0, QcResult::count());

        // Direct Service layer: hard DomainException
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('QC belum dapat dilakukan sebelum tanggal produksi.');
        app(QcResultService::class)->recordFinalQc($batch, 45, null, $this->produksiUser);
    }

    public function test_qc_navigation_and_ui_blocked_before_production_date(): void
    {
        $batch = $this->createBatchReadyForQc(50, 48, '2026-10-10');
        \Carbon\Carbon::setTestNow('2026-10-09 10:00:00');

        // Accessing create form directly redirects back with error
        $createResponse = $this->actingAs($this->produksiUser)
            ->get(route('produksi.batches.qc.create', $batch));
        $createResponse->assertRedirect(route('produksi.batches.show', $batch));
        $createResponse->assertSessionHas('error', 'QC belum dapat dilakukan sebelum tanggal produksi.');

        // Batch detail shows disabled button and helpful message
        $showResponse = $this->actingAs($this->produksiUser)
            ->get(route('produksi.batches.show', $batch));
        $showResponse->assertOk();
        $showResponse->assertSee('QC dapat dilakukan mulai');
        $showResponse->assertSee(DateTimeFormatter::formatDate(\Carbon\Carbon::parse('2026-10-10')));
        $showResponse->assertSee('QC (Terkunci)');
    }

    public function test_qc_allowed_when_current_business_date_equals_production_date(): void
    {
        // 2. Production date = 10 Oct 2026, current business date = 10 Oct 2026 -> Allowed
        $batch = $this->createBatchReadyForQc(50, 48, '2026-10-10');
        \Carbon\Carbon::setTestNow('2026-10-10 09:00:00');

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch), [
                'good_qty' => 45,
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');
        $this->assertEquals(BatchStatus::PACKING, $batch->fresh()->status);

        $qc = QcResult::first();
        $this->assertEquals('2026-10-10', $qc->qc_date->toDateString());
    }

    public function test_qc_submitted_after_production_date_records_production_date_as_qc_date_and_actual_submission_timestamp(): void
    {
        // 3 & 4. Production date = 10 Oct, QC submitted 12 Oct -> qc_date = 10 Oct, qc_at = 12 Oct timestamp
        $batch = $this->createBatchReadyForQc(50, 48, '2026-10-10');
        \Carbon\Carbon::setTestNow('2026-10-12 14:30:00');

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch), [
                'good_qty' => 45,
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $qc = QcResult::first();
        $this->assertNotNull($qc);
        // qc_date automatically follows production start date (10 Oct 2026)
        $this->assertEquals('2026-10-10', $qc->qc_date->toDateString());
        // qc_at stores actual event submission timestamp (12 Oct 2026 14:30:00)
        $this->assertEquals('2026-10-12 14:30:00', $qc->qc_at->toDateTimeString());
        $this->assertNotEquals($qc->qc_at->toDateString(), $qc->qc_date->toDateString());
    }

    public function test_staff_cannot_manipulate_qc_date(): void
    {
        // 5. Staff cannot manipulate qc_date to a date before actual production date
        $batch = $this->createBatchReadyForQc(50, 48, '2026-10-10');
        \Carbon\Carbon::setTestNow('2026-10-12 10:00:00');

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch), [
                'good_qty' => 45,
                'qc_date'  => '2020-01-01',
            ]);

        $response->assertSessionHasErrors('qc_date');
        $this->assertEquals(0, QcResult::count());
    }

    public function test_staff_cannot_manipulate_qc_at(): void
    {
        // 6. Staff cannot manipulate qc_at
        $batch = $this->createBatchReadyForQc(50, 48, '2026-10-10');
        \Carbon\Carbon::setTestNow('2026-10-12 14:30:00');

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch), [
                'good_qty' => 45,
                'qc_at'    => '2020-01-01 00:00:00',
            ]);

        $response->assertSessionHasNoErrors();
        $qc = QcResult::first();
        $this->assertEquals('2026-10-12 14:30:00', $qc->qc_at->toDateTimeString());
    }

    public function test_planned_end_date_is_not_used_as_qc_date(): void
    {
        // 7. Target selesai (planned_end_date) = 15 Oct, Production date = 10 Oct -> qc_date = 10 Oct (NOT 15 Oct)
        $batch = $this->createBatchReadyForQc(50, 48, '2026-10-10');
        $batch->update(['planned_end_date' => '2026-10-15']);
        \Carbon\Carbon::setTestNow('2026-10-12 10:00:00');

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch), [
                'good_qty' => 45,
            ]);

        $qc = QcResult::first();
        $this->assertEquals('2026-10-10', $qc->qc_date->toDateString());
        $this->assertNotEquals('2026-10-15', $qc->qc_date->toDateString());
    }

    public function test_timezone_boundary_uses_asia_jakarta_business_date(): void
    {
        // 11. Timezone boundary test: comparison uses Asia/Jakarta business date
        $batch = $this->createBatchReadyForQc(50, 48, '2026-10-10');

        // UTC: 2026-10-09 20:00:00 -> In Asia/Jakarta (UTC+7): 2026-10-10 03:00:00 WIB (Allowed)
        \Carbon\Carbon::setTestNow('2026-10-09 20:00:00');
        $response1 = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch), [
                'good_qty' => 45,
            ]);
        $response1->assertSessionHasNoErrors();
        $this->assertEquals('2026-10-10', QcResult::first()->qc_date->toDateString());

        // Now test blocking case: UTC 2026-10-09 16:59:00 -> In Asia/Jakarta: 2026-10-09 23:59:00 WIB (Blocked)
        $batch2 = $this->createBatchReadyForQc(50, 48, '2026-10-10');
        \Carbon\Carbon::setTestNow('2026-10-09 16:59:00');
        $response2 = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch2), [
                'good_qty' => 45,
            ]);
        $response2->assertSessionHasErrors('good_qty');
    }

    public function test_qc_detail_displays_in_wib(): void
    {
        $batch = $this->createBatchReadyForQc(50, 48, '2026-10-07');

        $nowUtc = now();
        $qc = QcResult::create([
            'production_result_id' => $batch->productionResult->id,
            'good_qty'             => 45,
            'reject_qty'           => 3,
            'qc_date'              => '2026-10-07',
            'qc_at'                => $nowUtc,
            'qc_by'                => $this->produksiUser->id,
        ]);

        $response = $this->actingAs($this->produksiUser)
            ->get(route('produksi.batches.show', $batch));

        $response->assertOk();
        // Should display WIB formatted string
        $expectedWib = DateTimeFormatter::formatDateTime($qc->qc_at);
        $response->assertSee($expectedWib);
        $response->assertSee(DateTimeFormatter::formatDate($qc->qc_date));
    }

    // =========================================================================
    // REQUIREMENT F — BATCH PROGRESS INDEPENDEN DI KANBAN
    // =========================================================================

    public function test_completed_batch_independently_changes_state_while_sibling_remains_unchanged(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-MULTI-001',
            'order_date'  => '2026-10-01',
            'status'      => OrderStatus::PRODUKSI,
        ]);

        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => 100,
            'unit_selling_price' => 75000,
            'subtotal'           => 7500000,
        ]);

        // Batch A (50 pcs)
        $batchA = ProductionBatch::create([
            'order_item_id'          => $orderItem->id,
            'batch_no'               => 'BAT-A',
            'target_qty'             => 50,
            'status'                 => BatchStatus::PRODUKSI,
            'planned_end_date'       => '2026-10-15',
            'production_employee_id' => $this->produksiUser->id,
        ]);
        BatchMaterial::create([
            'batch_id'      => $batchA->id,
            'material_id'   => $this->kain->id,
            'required_qty'  => 75,
            'allocated_qty' => 75,
        ]);

        // Batch B (50 pcs)
        $batchB = ProductionBatch::create([
            'order_item_id'          => $orderItem->id,
            'batch_no'               => 'BAT-B',
            'target_qty'             => 50,
            'status'                 => BatchStatus::PRODUKSI,
            'planned_end_date'       => '2026-10-20',
            'production_employee_id' => $this->produksiUser->id,
        ]);
        BatchMaterial::create([
            'batch_id'      => $batchB->id,
            'material_id'   => $this->kain->id,
            'required_qty'  => 75,
            'allocated_qty' => 75,
        ]);

        // Advance Batch A through Result -> QC -> Packing -> Selesai
        $resService = app(ProductionResultService::class);
        $qcService = app(QcResultService::class);
        $workflowService = app(BatchWorkflowService::class);

        $resService->recordFinalResult($batchA, 50, '2026-10-07', null, $this->produksiUser, [$batchA->batchMaterials->first()->id => 75]);
        $this->assertEquals(BatchStatus::QC, $batchA->fresh()->status);
        $this->assertEquals(BatchStatus::PRODUKSI, $batchB->fresh()->status);

        $qcService->recordFinalQc($batchA->fresh(), 50, null, $this->produksiUser);
        $this->assertEquals(BatchStatus::PACKING, $batchA->fresh()->status);
        $this->assertEquals(BatchStatus::PRODUKSI, $batchB->fresh()->status);

        $workflowService->completePacking($batchA->fresh(), $this->produksiUser);
        $this->assertEquals(BatchStatus::SELESAI, $batchA->fresh()->status);

        // Batch B MUST remain PRODUKSI!
        $this->assertEquals(BatchStatus::PRODUKSI, $batchB->fresh()->status);

        // Sales Order MUST NOT be SELESAI!
        $this->assertNotEquals(OrderStatus::SELESAI, $order->fresh()->status);
        $this->assertEquals(OrderStatus::PRODUKSI, $order->fresh()->status);
    }

    public function test_admin_kanban_displays_independent_progress_per_batch(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-KANBAN-001',
            'order_date'  => '2026-10-01',
            'status'      => OrderStatus::PRODUKSI,
        ]);

        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => 100,
            'unit_selling_price' => 75000,
            'subtotal'           => 7500000,
        ]);

        $batchA = ProductionBatch::create([
            'order_item_id'    => $orderItem->id,
            'batch_no'         => 'BAT-A-KANBAN',
            'target_qty'       => 50,
            'status'           => BatchStatus::SELESAI,
            'planned_end_date' => '2026-10-15',
        ]);

        $batchB = ProductionBatch::create([
            'order_item_id'    => $orderItem->id,
            'batch_no'         => 'BAT-B-KANBAN',
            'target_qty'       => 50,
            'status'           => BatchStatus::PRODUKSI,
            'planned_end_date' => '2026-10-20',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.kanban.index'));

        $response->assertOk();
        $response->assertSee('BAT-A-KANBAN');
        $response->assertSee('Batch Selesai');
        $response->assertSee('BAT-B-KANBAN');
        $response->assertSee('Produksi');
    }
}
