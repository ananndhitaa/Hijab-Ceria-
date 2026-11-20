<?php

namespace Tests\Feature\Owner;

use App\Enums\BatchStatus;
use App\Enums\CostType;
use App\Enums\MovementType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerHppMonitoringFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $admin;
    private User $produksi;
    private User $gudang;
    private User $customerUser;
    private Order $order;
    private ProductionBatch $batch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create([
            'role'   => UserRole::OWNER,
            'status' => UserStatus::ACTIVE,
        ]);
        $this->admin = User::factory()->create([
            'role'   => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
        ]);
        $this->produksi = User::factory()->create([
            'role'   => UserRole::PRODUKSI,
            'status' => UserStatus::ACTIVE,
        ]);
        $this->gudang = User::factory()->create([
            'role'   => UserRole::GUDANG,
            'status' => UserStatus::ACTIVE,
        ]);
        $this->customerUser = User::factory()->create([
            'role'   => UserRole::CUSTOMER,
            'status' => UserStatus::ACTIVE,
        ]);

        $unit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $material = Material::create([
            'material_name' => 'Kain Ceruty',
            'unit_id'       => $unit->id,
            'cost_per_unit' => 30000,
            'active'        => true,
        ]);
        MaterialStock::create([
            'material_id'   => $material->id,
            'qty_on_hand'   => 500,
            'avg_unit_cost' => 30000,
        ]);

        $cust = Customer::create(['customer_name' => 'PT Hijab Cantik', 'phone' => '08123456789']);
        $this->order = Order::create([
            'customer_id'  => $cust->id,
            'order_no'     => 'ORD-202609-OWN01',
            'order_date'   => now()->toDateString(),
            'status'       => 'DEAL',
            'total_qty'    => 10,
            'total_amount' => 1500000,
        ]);

        $product = Product::create(['product_name' => 'Pashmina']);
        $variant = ProductVariant::create([
            'product_id'   => $product->id,
            'variant_name' => 'Sage Green',
            'sku'          => 'PSH-SGE',
        ]);

        $item = OrderItem::create([
            'order_id'           => $this->order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 150000,
            'subtotal'           => 1500000,
        ]);

        $this->batch = ProductionBatch::create([
            'order_item_id'                                 => $item->id,
            'batch_no'                                      => 'BAT-202609-OWN01',
            'target_qty'                                    => 10,
            'status'                                        => BatchStatus::SELESAI,
            'requires_printing'                             => false,
            'printing_cost_per_unit_snapshot'               => '0.00',
            'direct_labor_cost_per_unit_snapshot'           => '5000.00',
            'packaging_process_cost_per_good_unit_snapshot' => '0.00',
            'other_direct_cost_per_unit_snapshot'           => '0.00',
            'material_usage_recorded_at'                    => now(),
        ]);

        $bm = BatchMaterial::create([
            'batch_id'      => $this->batch->id,
            'material_id'   => $material->id,
            'required_qty'  => 10,
            'allocated_qty' => 10,
            'used_qty'      => 10,
        ]);

        StockMovement::create([
            'material_id'         => $material->id,
            'movement_type'       => MovementType::OUT,
            'qty'                 => 10,
            'unit_cost_snapshot'  => '30000.0000',
            'total_cost_snapshot' => '300000.00',
            'reference_type'      => 'batch_materials',
            'reference_id'        => $bm->id,
            'movement_date'       => now()->toDateString(),
        ]);

        $res = ProductionResult::create([
            'batch_id'    => $this->batch->id,
            'actual_qty'  => 10,
            'result_date' => now()->toDateString(),
        ]);

        QcResult::create([
            'production_result_id' => $res->id,
            'good_qty'             => 10,
            'reject_qty'           => 0,
            'qc_date'              => now()->toDateString(),
        ]);
    }

    public function test_owner_can_view_hpp_index(): void
    {
        $response = $this->actingAs($this->owner)->get(route('owner.hpp.index'));

        $response->assertOk();
        $response->assertSee('HPP Produksi');
        $response->assertSee('BAT-202609-OWN01');
        $response->assertSee('ORD-202609-OWN01');
        $response->assertSee('350.000'); // Total HPP: 300k mat + 50k labor
        $response->assertSee('35.000');  // HPP / Good pcs: 350k / 10

        // Invariant: No premature profit/KPI metrics
        $response->assertDontSee('Gross Profit');
        $response->assertDontSee('Laba Bersih');
        $response->assertDontSee('EBITDA');
        $response->assertDontSee('ROI');
        $response->assertDontSee('MER');
    }

    public function test_owner_can_view_batch_hpp_detail(): void
    {
        $response = $this->actingAs($this->owner)->get(route('owner.hpp.batches.show', $this->batch));

        $response->assertOk();
        $response->assertSee('BAT-202609-OWN01');
        $response->assertSee('Harga Pokok Produksi (HPP) Final');
        $response->assertSee('350.000');
        $response->assertSee('300.000'); // Material cost
        $response->assertSee('50.000');  // Direct labor
        $response->assertSee('35.000');  // Per good pcs

        // Read-only invariant: No mutation controls
        $response->assertDontSee('Tambah Biaya');
        $response->assertDontSee('Konfirmasi Biaya Lengkap');
        $response->assertDontSee('Buka Kembali Biaya');
    }

    public function test_owner_can_view_order_hpp_detail(): void
    {
        $response = $this->actingAs($this->owner)->get(route('owner.hpp.orders.show', $this->order));

        $response->assertOk();
        $response->assertSee('ORD-202609-OWN01');
        $response->assertSee('Total HPP Pesanan Final');
        $response->assertSee('350.000');
        $response->assertSee('BAT-202609-OWN01');

        // Invariant: No profit/ROI/EBITDA calculations yet
        $response->assertDontSee('Gross Profit');
        $response->assertDontSee('Net Profit');
        $response->assertDontSee('EBITDA');
        $response->assertDontSee('ROI');
    }

    public function test_other_roles_cannot_access_owner_hpp_monitoring_pages(): void
    {
        // Admin
        $this->actingAs($this->admin)->get(route('owner.hpp.index'))->assertForbidden();
        $this->actingAs($this->admin)->get(route('owner.hpp.batches.show', $this->batch))->assertForbidden();
        $this->actingAs($this->admin)->get(route('owner.hpp.orders.show', $this->order))->assertForbidden();

        // Produksi
        $this->actingAs($this->produksi)->get(route('owner.hpp.index'))->assertForbidden();
        $this->actingAs($this->produksi)->get(route('owner.hpp.batches.show', $this->batch))->assertForbidden();
        $this->actingAs($this->produksi)->get(route('owner.hpp.orders.show', $this->order))->assertForbidden();

        // Gudang
        $this->actingAs($this->gudang)->get(route('owner.hpp.index'))->assertForbidden();
        $this->actingAs($this->gudang)->get(route('owner.hpp.batches.show', $this->batch))->assertForbidden();
        $this->actingAs($this->gudang)->get(route('owner.hpp.orders.show', $this->order))->assertForbidden();

        // Customer
        $this->actingAs($this->customerUser)->get(route('owner.hpp.index'))->assertForbidden();
        $this->actingAs($this->customerUser)->get(route('owner.hpp.batches.show', $this->batch))->assertForbidden();
        $this->actingAs($this->customerUser)->get(route('owner.hpp.orders.show', $this->order))->assertForbidden();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('owner.hpp.index'))->assertRedirect(route('login'));
        $this->get(route('owner.hpp.batches.show', $this->batch))->assertRedirect(route('login'));
        $this->get(route('owner.hpp.orders.show', $this->order))->assertRedirect(route('login'));
    }

    public function test_owner_hpp_ready_filter_excludes_batch_with_missing_material_out_snapshot_and_not_ready_filter_includes_it(): void
    {
        /** @var \App\Services\Finance\BatchCostCalculationService $costService */
        $costService = app(\App\Services\Finance\BatchCostCalculationService::class);

        // 1. Valid completed batch from setUp is authoritative READY
        $validResult = $costService->calculateBatchHpp($this->batch);
        $this->assertSame('READY', $validResult['status']);
        $this->assertTrue($validResult['is_ready']);

        // 2. Create completed batch with missing StockMovement OUT snapshot
        $corruptItem = OrderItem::first();
        $corruptBatch = ProductionBatch::create([
            'order_item_id'                                 => $corruptItem->id,
            'batch_no'                                      => 'BAT-CORRUPT-MISSING-OUT',
            'target_qty'                                    => 10,
            'status'                                        => BatchStatus::SELESAI,
            'requires_printing'                             => false,
            'printing_cost_per_unit_snapshot'               => '0.00',
            'direct_labor_cost_per_unit_snapshot'           => '5000.00',
            'packaging_process_cost_per_good_unit_snapshot' => '1000.00',
            'other_direct_cost_per_unit_snapshot'           => '500.00',
            'material_usage_recorded_at'                    => now(),
        ]);

        $bm = BatchMaterial::create([
            'batch_id'      => $corruptBatch->id,
            'material_id'   => Material::first()->id,
            'required_qty'  => 10,
            'allocated_qty' => 10,
            'used_qty'      => 10,
        ]);

        // Production Result & QC Result exist (would match old naive SQL check)
        $res = ProductionResult::create([
            'batch_id'    => $corruptBatch->id,
            'actual_qty'  => 10,
            'result_date' => now()->toDateString(),
        ]);
        QcResult::create([
            'production_result_id' => $res->id,
            'good_qty'             => 10,
            'reject_qty'           => 0,
            'qc_date'              => now()->toDateString(),
        ]);

        // Verify BatchCostCalculationService flags MATERIAL_INTEGRITY_ERROR
        $corruptResult = $costService->calculateBatchHpp($corruptBatch);
        $this->assertSame('MATERIAL_INTEGRITY_ERROR', $corruptResult['status']);
        $this->assertFalse($corruptResult['is_ready']);

        // 3. GET /owner/hpp?status=READY must contain valid batch but NOT the corrupted batch
        $readyResponse = $this->actingAs($this->owner)->get(route('owner.hpp.index', ['status' => 'READY']));
        $readyResponse->assertOk();
        $readyResponse->assertSee($this->batch->batch_no);
        $readyResponse->assertDontSee($corruptBatch->batch_no);

        // 4. GET /owner/hpp?status=NOT_READY must contain corrupted batch with appropriate user-facing status
        $notReadyResponse = $this->actingAs($this->owner)->get(route('owner.hpp.index', ['status' => 'NOT_READY']));
        $notReadyResponse->assertOk();
        $notReadyResponse->assertDontSee($this->batch->batch_no);
        $notReadyResponse->assertSee($corruptBatch->batch_no);
        $notReadyResponse->assertSee('Data Material Bermasalah');
    }

    public function test_owner_hpp_ready_filter_excludes_batch_with_duplicated_material_out_snapshot(): void
    {
        /** @var \App\Services\Finance\BatchCostCalculationService $costService */
        $costService = app(\App\Services\Finance\BatchCostCalculationService::class);

        $material = Material::first();
        $orderItem = OrderItem::first();

        // Create completed batch with duplicate StockMovement OUT snapshot for 1 BatchMaterial
        $duplicateBatch = ProductionBatch::create([
            'order_item_id'                                 => $orderItem->id,
            'batch_no'                                      => 'BAT-CORRUPT-DUPLICATE-OUT',
            'target_qty'                                    => 10,
            'status'                                        => BatchStatus::SELESAI,
            'requires_printing'                             => false,
            'printing_cost_per_unit_snapshot'               => '0.00',
            'direct_labor_cost_per_unit_snapshot'           => '5000.00',
            'packaging_process_cost_per_good_unit_snapshot' => '1000.00',
            'other_direct_cost_per_unit_snapshot'           => '500.00',
            'material_usage_recorded_at'                    => now(),
        ]);

        $bm = BatchMaterial::create([
            'batch_id'      => $duplicateBatch->id,
            'material_id'   => $material->id,
            'required_qty'  => 10,
            'allocated_qty' => 10,
            'used_qty'      => 10,
        ]);

        // Intentionally create 2 OUT movements for the same BatchMaterial
        StockMovement::create([
            'material_id'         => $material->id,
            'movement_type'       => MovementType::OUT,
            'qty'                 => 5,
            'unit_cost_snapshot'  => '30000.0000',
            'total_cost_snapshot' => '150000.00',
            'reference_type'      => 'batch_materials',
            'reference_id'        => $bm->id,
            'movement_date'       => now()->toDateString(),
        ]);
        StockMovement::create([
            'material_id'         => $material->id,
            'movement_type'       => MovementType::OUT,
            'qty'                 => 5,
            'unit_cost_snapshot'  => '30000.0000',
            'total_cost_snapshot' => '150000.00',
            'reference_type'      => 'batch_materials',
            'reference_id'        => $bm->id,
            'movement_date'       => now()->toDateString(),
        ]);

        $res = ProductionResult::create([
            'batch_id'    => $duplicateBatch->id,
            'actual_qty'  => 10,
            'result_date' => now()->toDateString(),
        ]);
        QcResult::create([
            'production_result_id' => $res->id,
            'good_qty'             => 10,
            'reject_qty'           => 0,
            'qc_date'              => now()->toDateString(),
        ]);

        // Verify BatchCostCalculationService flags MATERIAL_INTEGRITY_ERROR
        $dupResult = $costService->calculateBatchHpp($duplicateBatch);
        $this->assertSame('MATERIAL_INTEGRITY_ERROR', $dupResult['status']);
        $this->assertFalse($dupResult['is_ready']);
        $this->assertFalse($costService->isBatchHppReady($duplicateBatch));

        // GET /owner/hpp?status=READY must contain valid batch but NOT duplicateBatch
        $readyResponse = $this->actingAs($this->owner)->get(route('owner.hpp.index', ['status' => 'READY']));
        $readyResponse->assertOk();
        $readyResponse->assertSee($this->batch->batch_no);
        $readyResponse->assertDontSee($duplicateBatch->batch_no);

        // GET /owner/hpp?status=NOT_READY must contain duplicateBatch with 'Data Material Bermasalah' and exclude valid batch
        $notReadyResponse = $this->actingAs($this->owner)->get(route('owner.hpp.index', ['status' => 'NOT_READY']));
        $notReadyResponse->assertOk();
        $notReadyResponse->assertDontSee($this->batch->batch_no);
        $notReadyResponse->assertSee($duplicateBatch->batch_no);
        $notReadyResponse->assertSee('Data Material Bermasalah');
    }

    public function test_owner_hpp_cancelled_filter_and_not_ready_filter_behavior(): void
    {
        /** @var \App\Services\Finance\BatchCostCalculationService $costService */
        $costService = app(\App\Services\Finance\BatchCostCalculationService::class);

        $item = OrderItem::first();
        $cancelledBatch = ProductionBatch::create([
            'order_item_id'                                 => $item->id,
            'batch_no'                                      => 'BAT-TEST-CANCELLED-01',
            'target_qty'                                    => 10,
            'status'                                        => BatchStatus::CANCELLED,
            'requires_printing'                             => false,
            'printing_cost_per_unit_snapshot'               => '0.00',
            'direct_labor_cost_per_unit_snapshot'           => '5000.00',
            'packaging_process_cost_per_good_unit_snapshot' => '1000.00',
            'other_direct_cost_per_unit_snapshot'           => '500.00',
        ]);

        $cancelledResult = $costService->calculateBatchHpp($cancelledBatch);
        $this->assertSame('BATCH_CANCELLED', $cancelledResult['status']);
        $this->assertFalse($cancelledResult['is_ready']);
        $this->assertFalse($costService->isBatchHppReady($cancelledBatch));

        // 1. GET status=READY: valid batch visible, CANCELLED batch NOT visible
        $readyResponse = $this->actingAs($this->owner)->get(route('owner.hpp.index', ['status' => 'READY']));
        $readyResponse->assertOk();
        $readyResponse->assertSee($this->batch->batch_no);
        $readyResponse->assertDontSee($cancelledBatch->batch_no);

        // 2. GET status=NOT_READY: CANCELLED batch must NOT appear, valid batch must NOT appear
        $notReadyResponse = $this->actingAs($this->owner)->get(route('owner.hpp.index', ['status' => 'NOT_READY']));
        $notReadyResponse->assertOk();
        $notReadyResponse->assertDontSee($this->batch->batch_no);
        $notReadyResponse->assertDontSee($cancelledBatch->batch_no);

        // 3. GET status=CANCELLED: CANCELLED batch IS visible, valid batch must NOT appear
        $cancelledResponse = $this->actingAs($this->owner)->get(route('owner.hpp.index', ['status' => 'CANCELLED']));
        $cancelledResponse->assertOk();
        $cancelledResponse->assertSee($cancelledBatch->batch_no);
        $cancelledResponse->assertDontSee($this->batch->batch_no);
        $cancelledResponse->assertSee('Dibatalkan');
    }

    public function test_owner_hpp_ready_filter_excludes_batch_with_missing_production_result_and_not_ready_filter_includes_it(): void
    {
        /** @var \App\Services\Finance\BatchCostCalculationService $costService */
        $costService = app(\App\Services\Finance\BatchCostCalculationService::class);

        $material = Material::first();
        $item = OrderItem::first();

        // Create completed batch with material usage recorded, all cost snapshots, but NO production result
        $prodMissingBatch = ProductionBatch::create([
            'order_item_id'                                 => $item->id,
            'batch_no'                                      => 'BAT-TEST-PROD-MISSING-01',
            'target_qty'                                    => 10,
            'status'                                        => BatchStatus::SELESAI,
            'requires_printing'                             => false,
            'printing_cost_per_unit_snapshot'               => '0.00',
            'direct_labor_cost_per_unit_snapshot'           => '5000.00',
            'packaging_process_cost_per_good_unit_snapshot' => '1000.00',
            'other_direct_cost_per_unit_snapshot'           => '500.00',
            'material_usage_recorded_at'                    => now(),
        ]);

        $bm = BatchMaterial::create([
            'batch_id'      => $prodMissingBatch->id,
            'material_id'   => $material->id,
            'required_qty'  => 10,
            'allocated_qty' => 10,
            'used_qty'      => 10,
        ]);

        StockMovement::create([
            'material_id'         => $material->id,
            'movement_type'       => MovementType::OUT,
            'qty'                 => 10,
            'unit_cost_snapshot'  => '30000.0000',
            'total_cost_snapshot' => '300000.00',
            'reference_type'      => 'batch_materials',
            'reference_id'        => $bm->id,
            'movement_date'       => now()->toDateString(),
        ]);

        // Verify BatchCostCalculationService flags PRODUCTION_RESULT_MISSING
        $prodMissingResult = $costService->calculateBatchHpp($prodMissingBatch);
        $this->assertSame('PRODUCTION_RESULT_MISSING', $prodMissingResult['status']);
        $this->assertFalse($prodMissingResult['is_ready']);
        $this->assertFalse($costService->isBatchHppReady($prodMissingBatch));

        // GET /owner/hpp?status=READY must contain valid batch but NOT prodMissingBatch
        $readyResponse = $this->actingAs($this->owner)->get(route('owner.hpp.index', ['status' => 'READY']));
        $readyResponse->assertOk();
        $readyResponse->assertSee($this->batch->batch_no);
        $readyResponse->assertDontSee($prodMissingBatch->batch_no);

        // GET /owner/hpp?status=NOT_READY must contain prodMissingBatch with 'Data Produksi Tidak Lengkap'
        $notReadyResponse = $this->actingAs($this->owner)->get(route('owner.hpp.index', ['status' => 'NOT_READY']));
        $notReadyResponse->assertOk();
        $notReadyResponse->assertDontSee($this->batch->batch_no);
        $notReadyResponse->assertSee($prodMissingBatch->batch_no);
        $notReadyResponse->assertSee('Data Produksi Tidak Lengkap');
    }
}
