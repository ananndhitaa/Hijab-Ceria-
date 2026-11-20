<?php

namespace Tests\Feature\Production;

use App\Enums\BatchStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Sales\Invoice;
use App\Models\Sales\Payment;
use App\Models\Inventory\MaterialStock;
use App\Models\Master\Customer;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Master\Unit;
use App\Models\Production\BatchMaterial;
use App\Models\Production\BatchPicHistory;
use App\Models\Production\ProductionBatch;
use App\Models\Production\ProductionResult;
use App\Models\Production\QcResult;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\Outlet;
use App\Models\System\OutletActivityLog;
use App\Models\System\User;
use App\Services\Production\BatchService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionEmployeeIsolationAndWorkloadTest extends TestCase
{
    use RefreshDatabase;

    private Outlet $outletA;
    private Outlet $outletB;
    private User $ownerA;
    private User $adminA;
    private User $employeeA;
    private User $employeeB;
    private Product $product;
    private ProductVariant $variant;
    private Customer $customerA;
    private Material $fabric;
    private MaterialStock $fabricStock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outletA = Outlet::create([
            'outlet_name' => 'Konveksi Hijab A',
            'outlet_code' => 'KHA',
            'status'      => 'active',
        ]);

        $this->outletB = Outlet::create([
            'outlet_name' => 'Konveksi Hijab B',
            'outlet_code' => 'KHB',
            'status'      => 'active',
        ]);

        $this->ownerA = User::factory()->create([
            'name'      => 'Owner A',
            'role'      => UserRole::OWNER,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $this->outletA->id,
        ]);

        $this->adminA = User::factory()->create([
            'name'      => 'Admin A',
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $this->outletA->id,
        ]);

        $this->employeeA = User::factory()->create([
            'name'      => 'Karyawan Produksi A',
            'role'      => UserRole::PRODUKSI,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $this->outletA->id,
        ]);

        $this->employeeB = User::factory()->create([
            'name'      => 'Karyawan Produksi B',
            'role'      => UserRole::PRODUKSI,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $this->outletA->id,
        ]);

        $this->customerA = Customer::create([
            'outlet_id'     => $this->outletA->id,
            'customer_name' => 'Pelanggan Hijab A',
            'phone'         => '081298765432',
        ]);

        $this->product = Product::create([
            'outlet_id'    => $this->outletA->id,
            'product_name' => 'Pashmina Silk Premium',
            'active'       => true,
        ]);

        $this->variant = ProductVariant::create([
            'product_id'        => $this->product->id,
            'variant_name'      => 'Dusty Mauve',
            'sku'               => 'PSH-SLK-DM',
            'requires_printing' => false,
            'active'            => true,
        ]);

        $unit = Unit::firstOrCreate(['unit_name' => 'Meter'], ['symbol' => 'm']);
        $this->fabric = Material::create([
            'outlet_id'     => $this->outletA->id,
            'material_name' => 'Kain Ceruty Silk',
            'material_code' => 'MAT-CERUTY',
            'unit_id'       => $unit->id,
            'active'        => true,
        ]);

        $this->fabricStock = MaterialStock::create([
            'material_id'   => $this->fabric->id,
            'qty_on_hand'   => 500.0,
            'avg_unit_cost' => 25000.0,
        ]);
    }

    private function createOrderAndBatch(
        int $orderedQty,
        int $targetQty,
        ?User $assignedEmployee = null,
        ?string $deadline = null,
        ?Carbon $orderDate = null
    ): array {
        $order = Order::create([
            'outlet_id'   => $this->outletA->id,
            'customer_id' => $this->customerA->id,
            'order_no'    => 'ORD-' . uniqid(),
            'order_date'  => ($orderDate ?? now())->toDateString(),
            'status'      => OrderStatus::PRODUKSI,
        ]);

        if ($orderDate) {
            $order->created_at = $orderDate;
            $order->saveQuietly();
        }

        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => $orderedQty,
            'unit_selling_price' => 75000,
            'subtotal'           => $orderedQty * 75000,
        ]);

        $batch = ProductionBatch::create([
            'order_item_id'          => $item->id,
            'batch_no'               => 'BAT-' . uniqid(),
            'target_qty'             => $targetQty,
            'status'                 => BatchStatus::PRODUKSI,
            'planned_start_date'     => now()->toDateString(),
            'planned_end_date'       => $deadline ?? now()->addDays(2)->toDateString(),
            'requires_printing'      => false,
            'production_employee_id' => $assignedEmployee?->id,
        ]);

        $bm = BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->fabric->id,
            'required_qty'  => $targetQty * 1.5,
            'allocated_qty' => $targetQty * 1.5,
            'used_qty'      => 0,
        ]);

        return [$order, $item, $batch, $bm];
    }

    /**
     * Bagian 2: Partial Production & Partial QC logic.
     * Target: 2 pcs, Actual: 1 pc, QC Inspected: 1 pc (Good: 1, Reject: 0).
     * Order should not prematurely complete if remainder remains unbatched/uncompleted.
     */
    public function test_partial_production_allows_partial_qc_and_prevents_premature_order_completion(): void
    {
        [$order, $item, $batch, $bm] = $this->createOrderAndBatch(2, 2, $this->employeeA);

        // Record partial actual production: 1 pc out of target 2 pcs
        $resResponse = $this->actingAs($this->employeeA)->post(route('produksi.batches.production-result.store', $batch), [
            'actual_qty'     => 1,
            'result_date'    => now()->toDateString(),
            'notes'          => 'Produksi selesai 1 pcs terlebih dahulu',
            'material_usage' => [
                $bm->id => 1.5,
            ],
        ]);
        $resResponse->assertRedirect();
        $this->assertEquals(BatchStatus::QC, $batch->fresh()->status);
        $this->assertEquals(1.0, (float) $batch->fresh()->productionResult->actual_qty);

        // Attempting to inspect 2 pcs in QC when only 1 pc was produced must fail validation
        $invalidQcResponse = $this->actingAs($this->employeeA)->post(route('produksi.batches.qc.store', $batch), [
            'good_qty'   => 2,
            'reject_qty' => 0,
            'qc_date'    => now()->toDateString(),
        ]);
        $invalidQcResponse->assertSessionHasErrors('good_qty');

        // Recording QC inspection for the 1 pc produced (Good: 1, Reject: 0) must succeed
        $validQcResponse = $this->actingAs($this->employeeA)->post(route('produksi.batches.qc.store', $batch), [
            'good_qty'   => 1,
            'reject_qty' => 0,
            'qc_date'    => now()->toDateString(),
            'notes'      => '1 pc lulus QC sempurna',
        ]);
        $validQcResponse->assertRedirect();
        $this->assertEquals(BatchStatus::PACKING, $batch->fresh()->status);

        // Complete packing for this partial batch
        $packResponse = $this->actingAs($this->employeeA)->post(route('produksi.batches.complete-packing', $batch));
        $packResponse->assertRedirect();
        $this->assertEquals(BatchStatus::SELESAI, $batch->fresh()->status);

        // Verify Order is NOT marked completed because only 1 pc of 2 pcs was fulfilled!
        $orderFresh = $order->fresh();
        $this->assertNotEquals(OrderStatus::SELESAI, $orderFresh->status);
    }

    /**
     * Bagian 3: PIC Selection Workload metrics and smart recommendations in Batch creation form.
     */
    public function test_employee_workload_metrics_and_recommendations_in_batch_create(): void
    {
        // Give Employee A 2 active batches to increase their workload
        $this->createOrderAndBatch(10, 10, $this->employeeA);
        $this->createOrderAndBatch(15, 15, $this->employeeA);

        // Employee B has 0 batches (Tersedia)
        $order = Order::create([
            'outlet_id'   => $this->outletA->id,
            'customer_id' => $this->customerA->id,
            'order_no'    => 'ORD-NEW-' . uniqid(),
            'order_date'  => now()->toDateString(),
            'status'      => OrderStatus::DEAL,
        ]);
        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => 5,
            'unit_selling_price' => 75000,
            'subtotal'           => 375000,
        ]);

        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-' . uniqid(),
            'invoice_date' => now()->toDateString(),
            'total_amount' => 375000,
            'paid_amount'  => 187500,
            'status'       => InvoiceStatus::PARTIAL,
        ]);

        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_no'     => 'PAY-' . uniqid(),
            'amount'         => 187500,
            'payment_type'   => PaymentType::DP,
            'payment_method' => 'TRANSFER',
            'status'         => PaymentStatus::CONFIRMED,
            'payment_date'   => now()->toDateString(),
            'recorded_by'    => $this->adminA->id,
            'confirmed_by'   => $this->adminA->id,
            'confirmed_at'   => now(),
        ]);

        $response = $this->actingAs($this->adminA)->get(route('admin.orders.batches.create', $order));
        $response->assertOk();
        $response->assertViewHas('employeeWorkloads');

        $workloads = $response->viewData('employeeWorkloads');
        $this->assertNotEmpty($workloads);

        $empBWorkload = $workloads->firstWhere('id', $this->employeeB->id);
        $this->assertNotNull($empBWorkload);
        $this->assertEquals('Tersedia', $empBWorkload['workload_status']);
        $this->assertTrue($empBWorkload['is_recommended']);
    }

    /**
     * Bagian 4: Strict employee isolation.
     * Employee A sees only their batches; Employee B sees only their batches.
     */
    public function test_strict_employee_isolation_dashboard_and_batches_list(): void
    {
        [$orderA, $itemA, $batchA] = $this->createOrderAndBatch(10, 10, $this->employeeA);
        [$orderB, $itemB, $batchB] = $this->createOrderAndBatch(20, 20, $this->employeeB);

        // Employee A logged in
        $responseA = $this->actingAs($this->employeeA)->get(route('produksi.dashboard'));
        $responseA->assertOk();
        $responseA->assertSee($batchA->batch_no);
        $responseA->assertDontSee($batchB->batch_no);

        // Employee B logged in
        $responseB = $this->actingAs($this->employeeB)->get(route('produksi.dashboard'));
        $responseB->assertOk();
        $responseB->assertSee($batchB->batch_no);
        $responseB->assertDontSee($batchA->batch_no);
    }

    /**
     * Bagian 4: Tampering URL/routes of another employee's batch returns HTTP 403.
     */
    public function test_tampering_by_unassigned_employee_returns_403(): void
    {
        [$orderA, $itemA, $batchA] = $this->createOrderAndBatch(10, 10, $this->employeeA);

        // Employee B tries to view Batch A's detail
        $this->actingAs($this->employeeB)->get(route('produksi.batches.show', $batchA))->assertForbidden();

        // Employee B tries to start Batch A
        $this->actingAs($this->employeeB)->post(route('produksi.batches.start', $batchA))->assertForbidden();

        // Employee B tries to record production on Batch A
        $this->actingAs($this->employeeB)->get(route('produksi.batches.production-result.create', $batchA))->assertForbidden();
        $this->actingAs($this->employeeB)->post(route('produksi.batches.production-result.store', $batchA), [
            'actual_qty'  => 10,
            'result_date' => now()->toDateString(),
        ])->assertForbidden();

        // Employee B tries to record QC on Batch A
        $this->actingAs($this->employeeB)->get(route('produksi.batches.qc.create', $batchA))->assertForbidden();
        $this->actingAs($this->employeeB)->post(route('produksi.batches.qc.store', $batchA), [
            'good_qty'   => 10,
            'reject_qty' => 0,
            'qc_date'    => now()->toDateString(),
        ])->assertForbidden();
    }

    /**
     * Bagian 4: PIC Reassignment revokes old PIC, grants new PIC, writes BatchPicHistory, and logs activity.
     */
    public function test_pic_reassignment_revokes_old_pic_and_grants_new_pic_with_history_and_logs(): void
    {
        [$orderA, $itemA, $batchA] = $this->createOrderAndBatch(10, 10, $this->employeeA);

        // Admin reassigns PIC from Employee A to Employee B
        $response = $this->actingAs($this->adminA)->post(route('admin.batches.reassign-pic', $batchA), [
            'production_employee_id' => $this->employeeB->id,
            'notes'                  => 'Pergantian shift sore',
        ]);
        $response->assertRedirect();

        // Verify batch PIC is now Employee B
        $freshBatch = $batchA->fresh();
        $this->assertEquals($this->employeeB->id, $freshBatch->production_employee_id);

        // Verify history record
        $this->assertDatabaseHas('batch_pic_histories', [
            'batch_id'         => $batchA->id,
            'from_employee_id' => $this->employeeA->id,
            'to_employee_id'   => $this->employeeB->id,
            'assigned_by'      => $this->adminA->id,
            'notes'            => 'Pergantian shift sore',
        ]);

        // Verify audit activity log
        $this->assertDatabaseHas('outlet_activity_logs', [
            'module'       => 'BATCH_PRODUKSI',
            'action_type'  => 'REASSIGN_PIC',
            'reference_no' => $batchA->batch_no,
        ]);

        // Employee A is now forbidden from accessing Batch A
        $this->actingAs($this->employeeA)->get(route('produksi.batches.show', $batchA))->assertForbidden();

        // Employee B can now view Batch A
        $this->actingAs($this->employeeB)->get(route('produksi.batches.show', $batchA))->assertOk();
    }

    /**
     * Bagian 5: Produksi Dashboard sorting (deadline, newest_order, oldest_order).
     */
    public function test_produksi_dashboard_sorting(): void
    {
        // Job 1: Older order, earlier deadline
        [$order1, $item1, $batch1] = $this->createOrderAndBatch(
            5, 5, $this->employeeA,
            now()->addDay()->toDateString(),
            now()->subDays(5)
        );

        // Job 2: Newer order, later deadline
        [$order2, $item2, $batch2] = $this->createOrderAndBatch(
            10, 10, $this->employeeA,
            now()->addDays(4)->toDateString(),
            now()->subHour()
        );

        // Job 3: Overdue deadline
        [$order3, $item3, $batch3] = $this->createOrderAndBatch(
            8, 8, $this->employeeA,
            now()->subDay()->toDateString(),
            now()->subDays(2)
        );

        // Test deadline sort: Overdue first (batch3), then closest (batch1), then later (batch2)
        $respDeadline = $this->actingAs($this->employeeA)->get(route('produksi.dashboard', ['sort' => 'deadline']));
        $respDeadline->assertOk();
        $jobsDeadline = $respDeadline->viewData('assignedJobs');
        $this->assertEquals($batch3->id, $jobsDeadline->first()->id);

        // Test newest_order sort: Newest order created_at first (order2 -> batch2)
        $respNewest = $this->actingAs($this->employeeA)->get(route('produksi.dashboard', ['sort' => 'newest_order']));
        $respNewest->assertOk();
        $jobsNewest = $respNewest->viewData('assignedJobs');
        $this->assertEquals($batch2->id, $jobsNewest->first()->id);

        // Test oldest_order sort: Oldest order created_at first (order1 -> batch1)
        $respOldest = $this->actingAs($this->employeeA)->get(route('produksi.dashboard', ['sort' => 'oldest_order']));
        $respOldest->assertOk();
        $jobsOldest = $respOldest->viewData('assignedJobs');
        $this->assertEquals($batch1->id, $jobsOldest->first()->id);
    }

    /**
     * Bagian 6: Employee Production History page (/produksi/history).
     */
    public function test_employee_history_page_strictly_isolated_to_employee(): void
    {
        // Batch 1 completed by Employee A
        [$order1, $item1, $batch1] = $this->createOrderAndBatch(5, 5, $this->employeeA);
        $batch1->update(['status' => BatchStatus::SELESAI]);
        ProductionResult::create([
            'batch_id'    => $batch1->id,
            'actual_qty'  => 5,
            'result_date' => now()->toDateString(),
            'recorded_by' => $this->employeeA->id,
        ]);
        QcResult::create([
            'batch_id'             => $batch1->id,
            'production_result_id' => $batch1->productionResult->id,
            'good_qty'             => 5,
            'reject_qty'           => 0,
            'qc_date'              => now()->toDateString(),
            'qc_by'                => $this->employeeA->id,
        ]);

        // Batch 2 completed by Employee B
        [$order2, $item2, $batch2] = $this->createOrderAndBatch(10, 10, $this->employeeB);
        $batch2->update(['status' => BatchStatus::SELESAI]);
        ProductionResult::create([
            'batch_id'    => $batch2->id,
            'actual_qty'  => 10,
            'result_date' => now()->toDateString(),
            'recorded_by' => $this->employeeB->id,
        ]);

        // Employee A accesses /produksi/history
        $responseA = $this->actingAs($this->employeeA)->get(route('produksi.history'));
        $responseA->assertOk();
        $responseA->assertSee($batch1->batch_no);
        $responseA->assertDontSee($batch2->batch_no);
    }

    /**
     * Bagian 7: Owner Employee Productivity & Zero-division protection.
     */
    public function test_owner_employee_productivity_aggregate_stats_and_zero_division_protection(): void
    {
        $response = $this->actingAs($this->ownerA)->get(route('owner.employee-performance.index', ['tab' => 'production']));
        $response->assertOk();
        $response->assertViewHas('productionPerformance');

        $data = $response->viewData('productionPerformance');
        $this->assertArrayHasKey('total_production_staff', $data);
        $this->assertArrayHasKey('active_production_staff', $data);
        $this->assertArrayHasKey('available_production_staff', $data);
        $this->assertArrayHasKey('target_met_staff', $data);
        $this->assertArrayHasKey('total_active_batches', $data);

        // Ensure zero division protection didn't throw
        $this->assertIsString($data['overall_qty_text']);
        $this->assertIsString($data['overall_on_time_text']);
    }

    /**
     * Bagian 8: Owner Activity Logs tenant scoping & read-only enforcement.
     */
    public function test_owner_activity_logs_tenant_scoping_and_read_only(): void
    {
        $ownerB = User::factory()->create([
            'name'      => 'Owner B',
            'role'      => UserRole::OWNER,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $this->outletB->id,
        ]);

        // Create log in Outlet A
        OutletActivityLog::create([
            'outlet_id'     => $this->outletA->id,
            'user_id'       => $this->ownerA->id,
            'user_name'     => $this->ownerA->name,
            'user_role'     => $this->ownerA->role->value,
            'module'        => 'SALES_ORDER',
            'action_type'   => 'CREATE_ORDER',
            'reference_no'  => 'ORD-OUTLET-A-001',
            'description'   => 'Sales Order Konveksi A dibuat.',
        ]);

        // Create log in Outlet B
        OutletActivityLog::create([
            'outlet_id'     => $this->outletB->id,
            'user_id'       => $ownerB->id,
            'user_name'     => $ownerB->name,
            'user_role'     => $ownerB->role->value,
            'module'        => 'SALES_ORDER',
            'action_type'   => 'CREATE_ORDER',
            'reference_no'  => 'ORD-OUTLET-B-002',
            'description'   => 'Sales Order Konveksi B dibuat.',
        ]);

        // Owner A accesses /owner/activity-logs
        $response = $this->actingAs($this->ownerA)->get(route('owner.activity-logs.index'));
        $response->assertOk();
        $response->assertSee('ORD-OUTLET-A-001');
        $response->assertDontSee('ORD-OUTLET-B-002');
    }
}
