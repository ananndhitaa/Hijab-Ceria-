<?php

namespace Tests\Unit\Finance;

use App\Enums\BatchStatus;
use App\Enums\CostType;
use App\Enums\MovementType;
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
use App\Services\Finance\BatchCostCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaterialCostCalculationAndDirectCostTest extends TestCase
{
    use RefreshDatabase;

    private BatchCostCalculationService $costService;
    private Material $kain;
    private Material $benang;
    private ProductionBatch $batch;
    private BatchMaterial $bmKain;
    private BatchMaterial $bmBenang;
    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->costService = app(BatchCostCalculationService::class);

        $unit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $this->kain = Material::create([
            'material_name' => 'Kain Voal',
            'unit_id'       => $unit->id,
            'cost_per_unit' => 25000,
            'active'        => true,
        ]);
        $this->benang = Material::create([
            'material_name' => 'Benang Jahit',
            'unit_id'       => $unit->id,
            'cost_per_unit' => 5000,
            'active'        => true,
        ]);

        MaterialStock::create([
            'material_id'   => $this->kain->id,
            'qty_on_hand'   => 500,
            'avg_unit_cost' => 25000,
        ]);
        MaterialStock::create([
            'material_id'   => $this->benang->id,
            'qty_on_hand'   => 100,
            'avg_unit_cost' => 5000,
        ]);

        $cust = Customer::create(['customer_name' => 'Cust Finance', 'phone' => '08123456789']);
        $order = Order::create([
            'customer_id'  => $cust->id,
            'order_no'     => 'ORD-202609-FIN1',
            'order_date'   => now()->toDateString(),
            'status'       => 'DEAL',
            'total_qty'    => 10,
            'total_amount' => 500000,
        ]);
        $prod = Product::create(['product_name' => 'Hijab Fin']);
        $this->variant = ProductVariant::create([
            'product_id'                           => $prod->id,
            'variant_name'                         => 'Lilac',
            'sku'                                  => 'HJB-LLC',
            'requires_printing'                    => true,
            'printing_cost_per_unit'               => '2000.00',
            'direct_labor_cost_per_unit'           => '5000.00',
            'packaging_process_cost_per_good_unit' => '1000.00',
            'other_direct_cost_per_unit'           => '500.00',
        ]);
        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 50000,
            'subtotal'           => 500000,
        ]);

        $this->batch = ProductionBatch::create([
            'order_item_id'                                 => $item->id,
            'batch_no'                                      => 'BAT-202609-FIN1',
            'target_qty'                                    => 10,
            'status'                                        => BatchStatus::PRODUKSI,
            'requires_printing'                             => true,
            'printing_cost_per_unit_snapshot'               => '2000.00',
            'direct_labor_cost_per_unit_snapshot'           => '5000.00',
            'packaging_process_cost_per_good_unit_snapshot' => '1000.00',
            'other_direct_cost_per_unit_snapshot'           => '500.00',
            'material_usage_recorded_at'                    => now(),
        ]);

        // Kain: allocated 15, used 12
        $this->bmKain = BatchMaterial::create([
            'batch_id'      => $this->batch->id,
            'material_id'   => $this->kain->id,
            'required_qty'  => 15,
            'allocated_qty' => 15,
            'used_qty'      => 12,
        ]);

        // Benang: allocated 2, used 1.5
        $this->bmBenang = BatchMaterial::create([
            'batch_id'      => $this->batch->id,
            'material_id'   => $this->benang->id,
            'required_qty'  => 2,
            'allocated_qty' => 2,
            'used_qty'      => 1.5,
        ]);

        // Authoritative StockMovement OUT snapshots
        StockMovement::create([
            'material_id'         => $this->kain->id,
            'movement_type'       => MovementType::OUT,
            'qty'                 => 15,
            'unit_cost_snapshot'  => '25000.0000',
            'total_cost_snapshot' => '375000.00',
            'reference_type'      => 'batch_materials',
            'reference_id'        => $this->bmKain->id,
            'movement_date'       => now()->toDateString(),
        ]);

        StockMovement::create([
            'material_id'         => $this->benang->id,
            'movement_type'       => MovementType::OUT,
            'qty'                 => 2,
            'unit_cost_snapshot'  => '5000.0000',
            'total_cost_snapshot' => '10000.00',
            'reference_type'      => 'batch_materials',
            'reference_id'        => $this->bmBenang->id,
            'movement_date'       => now()->toDateString(),
        ]);
    }

    public function test_material_cost_uses_used_qty_multiplied_by_out_snapshot(): void
    {
        $result = $this->costService->calculateMaterialCost($this->batch);

        $this->assertEquals('READY', $result['status']);
        $this->assertNull($result['error']);

        // Expected: (12 * 25000) + (1.5 * 5000) = 300000 + 7500 = 307500.00
        $this->assertEquals('307500.00', $result['amount']);
        $this->assertCount(2, $result['items']);
    }

    public function test_changes_in_current_material_stock_avg_unit_cost_do_not_alter_historical_cost(): void
    {
        MaterialStock::where('material_id', $this->kain->id)->update([
            'avg_unit_cost' => '45000.0000',
        ]);

        $result = $this->costService->calculateMaterialCost($this->batch);

        // Historical cost MUST remain 307500.00 (snapshot 25000.00, not current 45000.00)
        $this->assertEquals('307500.00', $result['amount']);
    }

    public function test_zero_unit_cost_snapshot_is_valid(): void
    {
        StockMovement::where('reference_id', $this->bmKain->id)->update([
            'unit_cost_snapshot' => '0.0000',
        ]);

        $result = $this->costService->calculateMaterialCost($this->batch);

        $this->assertEquals('READY', $result['status']);
        // Kain = 12 * 0 = 0, Benang = 1.5 * 5000 = 7500
        $this->assertEquals('7500.00', $result['amount']);
    }

    public function test_negative_snapshot_cost_is_detected_as_integrity_error(): void
    {
        StockMovement::where('reference_id', $this->bmKain->id)->update([
            'unit_cost_snapshot' => '-500.0000',
        ]);

        $result = $this->costService->calculateMaterialCost($this->batch);

        $this->assertEquals('INTEGRITY_ERROR', $result['status']);
        $this->assertNull($result['amount']);
        $this->assertStringContainsString('negatif', $result['error']);
    }

    public function test_missing_stock_movement_out_snapshot_returns_integrity_error(): void
    {
        StockMovement::where('reference_id', $this->bmBenang->id)->delete();

        $result = $this->costService->calculateMaterialCost($this->batch);

        $this->assertEquals('INTEGRITY_ERROR', $result['status']);
        $this->assertNull($result['amount']);
        $this->assertStringContainsString('harus tepat 1', $result['error']);
    }

    public function test_duplicate_stock_movement_out_snapshot_returns_integrity_error(): void
    {
        StockMovement::create([
            'material_id'         => $this->benang->id,
            'movement_type'       => MovementType::OUT,
            'qty'                 => 2,
            'unit_cost_snapshot'  => '5000.0000',
            'total_cost_snapshot' => '10000.00',
            'reference_type'      => 'batch_materials',
            'reference_id'        => $this->bmBenang->id,
            'movement_date'       => now()->toDateString(),
        ]);

        $result = $this->costService->calculateMaterialCost($this->batch);

        $this->assertEquals('INTEGRITY_ERROR', $result['status']);
        $this->assertNull($result['amount']);
        $this->assertStringContainsString('2 mutasi OUT', $result['error']);
    }

    public function test_unrecorded_usage_returns_usage_unrecorded_and_null_amount(): void
    {
        $this->batch->update(['material_usage_recorded_at' => null]);

        $result = $this->costService->calculateMaterialCost($this->batch);

        $this->assertEquals('USAGE_UNRECORDED', $result['status']);
        $this->assertNull($result['amount']);
        $this->assertStringContainsString('belum dicatat', $result['error']);
    }

    public function test_active_batch_returns_batch_not_completed(): void
    {
        $this->batch->update([
            'status' => BatchStatus::PRODUKSI,
        ]);

        $hpp = $this->costService->calculateBatchHpp($this->batch);

        $this->assertEquals('BATCH_NOT_COMPLETED', $hpp['status']);
        $this->assertFalse($hpp['is_ready']);
        $this->assertNull($hpp['total_hpp']);
        $this->assertStringContainsString('belum selesai', $hpp['error']);
    }

    public function test_completed_batch_missing_snapshots_returns_costing_snapshot_missing(): void
    {
        $this->batch->update([
            'status'                              => BatchStatus::SELESAI,
            'direct_labor_cost_per_unit_snapshot' => null,
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

        $hpp = $this->costService->calculateBatchHpp($this->batch);

        $this->assertEquals('COSTING_SNAPSHOT_MISSING', $hpp['status']);
        $this->assertFalse($hpp['is_ready']);
        $this->assertNull($hpp['total_hpp']);
    }

    public function test_completed_batch_missing_qc_result_returns_qc_result_missing(): void
    {
        $this->batch->update([
            'status' => BatchStatus::SELESAI,
        ]);

        ProductionResult::create([
            'batch_id'    => $this->batch->id,
            'actual_qty'  => 10,
            'result_date' => now()->toDateString(),
        ]);

        $hpp = $this->costService->calculateBatchHpp($this->batch);

        $this->assertEquals('QC_RESULT_MISSING', $hpp['status']);
        $this->assertFalse($hpp['is_ready']);
        $this->assertNull($hpp['total_hpp']);
        $this->assertStringContainsString('hasil QC tidak ditemukan', $hpp['error']);
    }

    public function test_batch_hpp_automatic_calculation_with_verbatim_section_32_example(): void
    {
        $cust = Customer::create(['customer_name' => 'Cust 32', 'phone' => '08123456789']);
        $order = Order::create([
            'customer_id'  => $cust->id,
            'order_no'     => 'ORD-202609-SEC32',
            'order_date'   => now()->toDateString(),
            'status'       => 'DEAL',
            'total_qty'    => 100,
            'total_amount' => 5000000,
        ]);
        $prod = Product::create(['product_name' => 'Hijab Sec 32']);
        $var = ProductVariant::create([
            'product_id'                           => $prod->id,
            'variant_name'                         => 'Motif 32',
            'sku'                                  => 'HJB-32',
            'requires_printing'                    => true,
            'printing_cost_per_unit'               => '2000.00',
            'direct_labor_cost_per_unit'           => '5000.00',
            'packaging_process_cost_per_good_unit' => '1000.00',
            'other_direct_cost_per_unit'           => '500.00',
        ]);
        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $var->id,
            'qty_ordered'        => 100,
            'unit_selling_price' => 50000,
            'subtotal'           => 5000000,
        ]);

        $batch = ProductionBatch::create([
            'order_item_id'                                 => $item->id,
            'batch_no'                                      => 'BAT-202609-SEC32',
            'target_qty'                                    => 100,
            'status'                                        => BatchStatus::SELESAI,
            'requires_printing'                             => true,
            'printing_cost_per_unit_snapshot'               => '2000.00',
            'direct_labor_cost_per_unit_snapshot'           => '5000.00',
            'packaging_process_cost_per_good_unit_snapshot' => '1000.00',
            'other_direct_cost_per_unit_snapshot'           => '500.00',
            'material_usage_recorded_at'                    => now(),
        ]);

        $bm = BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->kain->id,
            'required_qty'  => 100,
            'allocated_qty' => 100,
            'used_qty'      => 100,
        ]);

        // Material actual cost: 600.000 (100 * 6000)
        StockMovement::create([
            'material_id'         => $this->kain->id,
            'movement_type'       => MovementType::OUT,
            'qty'                 => 100,
            'unit_cost_snapshot'  => '6000.0000',
            'total_cost_snapshot' => '600000.00',
            'reference_type'      => 'batch_materials',
            'reference_id'        => $bm->id,
            'movement_date'       => now()->toDateString(),
        ]);

        // Actual output: 100 pcs
        $res = ProductionResult::create([
            'batch_id'    => $batch->id,
            'actual_qty'  => 100,
            'result_date' => now()->toDateString(),
        ]);

        // Good QC: 90 pcs, Reject QC: 10 pcs
        QcResult::create([
            'production_result_id' => $res->id,
            'good_qty'             => 90,
            'reject_qty'           => 10,
            'qc_date'              => now()->toDateString(),
        ]);

        $hpp = $this->costService->calculateBatchHpp($batch);

        $this->assertEquals('READY', $hpp['status']);
        $this->assertTrue($hpp['is_ready']);
        $this->assertEquals('600000.00', $hpp['material_cost']);

        // Breakdown checks
        $this->assertEquals('200000.00', $hpp['breakdown']['printing']);
        $this->assertEquals('500000.00', $hpp['breakdown']['direct_labor']);
        $this->assertEquals('90000.00', $hpp['breakdown']['packaging_process']); // 90 * 1000, not 100 * 1000!
        $this->assertEquals('50000.00', $hpp['breakdown']['other_direct']);

        // Direct total: 200k + 500k + 90k + 50k = 840.000
        $this->assertEquals('840000.00', $hpp['direct_cost']);

        // Total HPP: 600k + 840k = 1.440.000
        $this->assertEquals('1440000.00', $hpp['total_hpp']);

        // HPP per Good: 1.440.000 / 90 = 16.000
        $this->assertEquals('16000.0000', $hpp['hpp_per_good_pcs']);
    }

    public function test_changes_in_product_variant_master_rates_do_not_alter_historical_batch_hpp(): void
    {
        $this->batch->update([
            'status' => BatchStatus::SELESAI,
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

        $initialHpp = $this->costService->calculateBatchHpp($this->batch);
        $this->assertEquals('READY', $initialHpp['status']);

        // Change master rates on ProductVariant
        $this->variant->update([
            'printing_cost_per_unit'               => '99999.00',
            'direct_labor_cost_per_unit'           => '99999.00',
            'packaging_process_cost_per_good_unit' => '99999.00',
            'other_direct_cost_per_unit'           => '99999.00',
        ]);

        $afterUpdateHpp = $this->costService->calculateBatchHpp($this->batch);

        // Historical batch HPP MUST be identical
        $this->assertEquals($initialHpp['total_hpp'], $afterUpdateHpp['total_hpp']);
        $this->assertEquals($initialHpp['direct_cost'], $afterUpdateHpp['direct_cost']);
    }

    public function test_all_reject_batch_preserves_total_hpp_and_sets_hpp_per_good_pcs_to_null(): void
    {
        $this->batch->update([
            'status' => BatchStatus::SELESAI,
        ]);

        $res = ProductionResult::create([
            'batch_id'    => $this->batch->id,
            'actual_qty'  => 10,
            'result_date' => now()->toDateString(),
        ]);
        QcResult::create([
            'production_result_id' => $res->id,
            'good_qty'             => 0,
            'reject_qty'           => 10,
            'qc_date'              => now()->toDateString(),
        ]);

        $hpp = $this->costService->calculateBatchHpp($this->batch);

        $this->assertEquals('READY', $hpp['status']);
        $this->assertNotNull($hpp['total_hpp']);
        $this->assertNull($hpp['hpp_per_good_pcs']);
    }

    public function test_cancelled_batch_returns_batch_cancelled_state(): void
    {
        $this->batch->update([
            'status' => BatchStatus::CANCELLED,
        ]);

        $hpp = $this->costService->calculateBatchHpp($this->batch);

        $this->assertEquals('BATCH_CANCELLED', $hpp['status']);
        $this->assertFalse($hpp['is_ready']);
        $this->assertNull($hpp['total_hpp']);
        $this->assertStringContainsString('dibatalkan', $hpp['error']);
    }
}
