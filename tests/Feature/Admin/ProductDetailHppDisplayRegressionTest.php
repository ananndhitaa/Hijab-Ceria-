<?php

namespace Tests\Feature\Admin;

use App\Enums\BatchStatus;
use App\Enums\MovementType;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Inventory\MaterialStock;
use App\Models\Inventory\StockMovement;
use App\Models\Master\BomItem;
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
use App\Services\Finance\VariantCostCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductDetailHppDisplayRegressionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Product $product;
    private ProductVariant $variant;
    private Material $material;
    private OrderItem $orderItem;
    private VariantCostCalculationService $variantCostService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->variantCostService = app(VariantCostCalculationService::class);

        $this->admin = User::factory()->create([
            'role'   => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
        ]);

        $unit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $this->material = Material::create([
            'material_name' => 'Kain Ceruty Babydoll',
            'unit_id'       => $unit->id,
            'cost_per_unit' => 15000,
            'min_stock'     => 10,
            'active'        => true,
        ]);

        MaterialStock::create([
            'material_id'   => $this->material->id,
            'qty_on_hand'   => 100,
            'avg_unit_cost' => 15000,
        ]);

        $this->product = Product::create([
            'product_name' => 'Pashmina Ceruty',
            'active'       => true,
        ]);

        $this->variant = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'Soft Pink',
            'sku'                                  => 'HJB-PC-SP-001',
            'base_selling_price'                   => 45000,
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => 0,
            'direct_labor_cost_per_unit'           => 4000,
            'packaging_process_cost_per_good_unit' => 1000,
            'other_direct_cost_per_unit'           => 500,
            'active'                               => true,
        ]);

        BomItem::create([
            'variant_id'   => $this->variant->id,
            'material_id'  => $this->material->id,
            'qty_per_unit' => 1.5,
        ]);

        $customer = Customer::create([
            'customer_name' => 'Customer Test',
            'phone'         => '08123456789',
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'order_no'    => 'ORD-TEST-001',
            'order_date'  => now()->toDateString(),
            'status'      => OrderStatus::PRODUKSI,
        ]);

        $this->orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => 20,
            'unit_selling_price' => 45000,
        ]);
    }

    private function createCompletedBatchWithHpp(string $batchNo, string $qcAt, int $goodQty = 10): ProductionBatch
    {
        $batch = ProductionBatch::create([
            'order_item_id'                                 => $this->orderItem->id,
            'batch_no'                                      => $batchNo,
            'target_qty'                                    => $goodQty,
            'status'                                        => BatchStatus::SELESAI,
            'requires_printing'                             => false,
            'printing_cost_per_unit_snapshot'               => '0.00',
            'direct_labor_cost_per_unit_snapshot'           => '4000.00',
            'packaging_process_cost_per_good_unit_snapshot' => '1000.00',
            'other_direct_cost_per_unit_snapshot'           => '500.00',
            'material_usage_recorded_at'                    => now(),
            'material_usage_recorded_by'                    => $this->admin->id,
        ]);

        $bm = BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->material->id,
            'required_qty'  => 15.0,
            'allocated_qty' => 15.0,
            'used_qty'      => 15.0,
        ]);

        StockMovement::create([
            'material_id'        => $this->material->id,
            'movement_type'      => MovementType::OUT,
            'qty'                => 15.0,
            'qty_before'         => 100.0,
            'qty_after'          => 85.0,
            'unit_cost_snapshot' => '15000.0000',
            'movement_date'      => now()->toDateString(),
            'reference_type'     => 'batch_materials',
            'reference_id'       => $bm->id,
            'notes'              => "Alokasi batch {$batchNo}",
        ]);

        $prodResult = ProductionResult::create([
            'batch_id'    => $batch->id,
            'actual_qty'  => $goodQty,
            'result_date' => substr($qcAt, 0, 10),
            'recorded_by' => $this->admin->id,
        ]);

        QcResult::create([
            'production_result_id' => $prodResult->id,
            'good_qty'             => $goodQty,
            'reject_qty'           => 0,
            'qc_date'              => substr($qcAt, 0, 10),
            'qc_at'                => $qcAt,
            'qc_by'                => $this->admin->id,
        ]);

        return $batch;
    }

    /**
     * 1. Product detail opens for product without completed batch.
     */
    public function test_01_product_detail_opens_without_completed_batch(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.products.show', $this->product));

        $response->assertStatus(200);
        $response->assertSee($this->product->product_name);
        $response->assertSee($this->variant->variant_name);
    }

    /**
     * 2. Product detail opens for variant with completed batch.
     */
    public function test_02_product_detail_opens_with_completed_batch(): void
    {
        $this->createCompletedBatchWithHpp('BAT-001', '2026-10-01 10:00:00', 10);

        $response = $this->actingAs($this->admin)->get(route('admin.products.show', $this->product));

        $response->assertStatus(200);
        $response->assertSee($this->product->product_name);
        $response->assertSee($this->variant->variant_name);
    }

    /**
     * 3. Latest completed batch selected correctly.
     */
    public function test_03_latest_completed_batch_selected_correctly(): void
    {
        $batch = $this->createCompletedBatchWithHpp('BAT-001', '2026-10-01 10:00:00', 10);

        $hppData = $this->variantCostService->getLatestActualHpp($this->variant);

        $this->assertTrue($hppData['is_available']);
        $this->assertEquals($batch->id, $hppData['latest_batch_id']);
        $this->assertEquals('BAT-001', $hppData['latest_batch_no']);
        $this->assertNotNull($hppData['latest_actual_hpp']);
    }

    /**
     * 4. Active/incomplete batch is not used as actual final HPP.
     */
    public function test_04_active_incomplete_batch_is_not_used_as_actual_final_hpp(): void
    {
        ProductionBatch::create([
            'order_item_id'                                 => $this->orderItem->id,
            'batch_no'                                      => 'BAT-INCOMPLETE-001',
            'target_qty'                                    => 10,
            'status'                                        => BatchStatus::PRODUKSI,
            'requires_printing'                             => false,
            'printing_cost_per_unit_snapshot'               => '0.00',
            'direct_labor_cost_per_unit_snapshot'           => '4000.00',
            'packaging_process_cost_per_good_unit_snapshot' => '1000.00',
            'other_direct_cost_per_unit_snapshot'           => '500.00',
        ]);

        $hppData = $this->variantCostService->getLatestActualHpp($this->variant);

        $this->assertFalse($hppData['is_available']);
        $this->assertNull($hppData['latest_actual_hpp']);
        $this->assertNull($hppData['latest_batch_id']);
    }

    /**
     * 5. Multiple completed batches select newest authoritative completion.
     */
    public function test_05_multiple_completed_batches_select_newest_authoritative_completion(): void
    {
        // Batch 1: Earlier QC
        $this->createCompletedBatchWithHpp('BAT-EARLIER', '2026-10-01 08:00:00', 10);

        // Batch 2: Later QC
        $batchLater = $this->createCompletedBatchWithHpp('BAT-LATER', '2026-10-05 14:30:00', 10);

        $hppData = $this->variantCostService->getLatestActualHpp($this->variant);

        $this->assertTrue($hppData['is_available']);
        $this->assertEquals($batchLater->id, $hppData['latest_batch_id']);
        $this->assertEquals('BAT-LATER', $hppData['latest_batch_no']);
    }

    /**
     * 6. HPP unavailable displays "Belum tersedia", not Rp0.
     */
    public function test_06_hpp_unavailable_displays_belum_tersedia_not_rp0(): void
    {
        // Product without any completed batch or BOM reference cost
        $bareProduct = Product::create([
            'product_name' => 'Bare Product Without Cost',
            'active'       => true,
        ]);
        $bareVariant = ProductVariant::create([
            'product_id'   => $bareProduct->id,
            'variant_name' => 'Bare Variant',
            'sku'          => 'BARE-VAR-001',
            'active'       => true,
        ]);

        $responseProduct = $this->actingAs($this->admin)->get(route('admin.products.show', $bareProduct));
        $responseProduct->assertStatus(200);
        $responseProduct->assertSee('Belum tersedia');
        $responseProduct->assertDontSee('Rp 0');

        $responseVariant = $this->actingAs($this->admin)->get(route('admin.variants.show', $bareVariant));
        $responseVariant->assertStatus(200);
        $responseVariant->assertSee('Belum tersedia');
        $responseVariant->assertSee('Belum ada batch produksi SELESAI');
    }

    /**
     * 7. Variant detail still opens.
     */
    public function test_07_variant_detail_still_opens(): void
    {
        $this->createCompletedBatchWithHpp('BAT-VAR-001', '2026-10-03 11:00:00', 10);

        $response = $this->actingAs($this->admin)->get(route('admin.variants.show', $this->variant));

        $response->assertStatus(200);
        $response->assertSee($this->variant->variant_name);
        $response->assertSee('HPP Aktual Tersedia');
    }

    /**
     * 8. Variant edit still opens.
     */
    public function test_08_variant_edit_still_opens(): void
    {
        $this->createCompletedBatchWithHpp('BAT-VAR-EDIT-001', '2026-10-03 11:00:00', 10);

        $response = $this->actingAs($this->admin)->get(route('admin.variants.edit', $this->variant));

        $response->assertStatus(200);
        $response->assertSee($this->variant->variant_name);
        $response->assertSee('HPP / Modal Referensi:');
    }
}
