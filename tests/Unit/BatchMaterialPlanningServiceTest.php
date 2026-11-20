<?php

namespace Tests\Unit;

use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Models\Production\BatchMaterial;
use App\Models\Master\BomItem;
use App\Models\Master\Customer;
use App\Models\Master\Material;
use App\Models\Inventory\MaterialStock;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Production\ProductionBatch;
use App\Models\Master\Unit;
use App\Services\Inventory\BatchMaterialPlanningService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchMaterialPlanningServiceTest extends TestCase
{
    use RefreshDatabase;

    private BatchMaterialPlanningService $service;
    private Unit $meterUnit;
    private Unit $pcsUnit;
    private ProductVariant $variant;
    private Material $kainVoal;
    private Material $labelBrand;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(BatchMaterialPlanningService::class);

        $this->meterUnit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $this->pcsUnit   = Unit::create(['unit_name' => 'Pieces', 'symbol' => 'pcs']);

        $product = Product::create(['product_name' => 'Hijab Bella Square', 'active' => true]);
        $this->variant = ProductVariant::create([
            'product_id'         => $product->id,
            'variant_name'       => 'Hitam',
            'sku'                => 'HJB-BS-001',
            'base_selling_price' => 55000,
            'active'             => true,
        ]);

        $this->kainVoal = Material::create([
            'unit_id'       => $this->meterUnit->id,
            'material_name' => 'Kain Voal Premium',
            'min_stock'     => 50,
            'active'        => true,
        ]);
        MaterialStock::create(['material_id' => $this->kainVoal->id, 'qty_on_hand' => 100]);

        $this->labelBrand = Material::create([
            'unit_id'       => $this->pcsUnit->id,
            'material_name' => 'Label Brand Hijab',
            'min_stock'     => 200,
            'active'        => true,
        ]);
        MaterialStock::create(['material_id' => $this->labelBrand->id, 'qty_on_hand' => 500]);
    }

    private function createBatch(float|string $targetQty = 100): ProductionBatch
    {
        $customer = Customer::create(['customer_name' => 'Customer Test', 'phone' => '08123456789']);
        $order = Order::create([
            'customer_id' => $customer->id,
            'order_no'    => 'SO-TEST-001',
            'order_date'  => now()->toDateString(),
            'status'      => OrderStatus::BAHAN,
        ]);

        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => $targetQty,
            'unit_selling_price' => 55000,
        ]);

        return ProductionBatch::create([
            'order_item_id' => $orderItem->id,
            'batch_no'      => 'BAT-TEST-001',
            'target_qty'    => $targetQty,
            'status'        => BatchStatus::BAHAN,
        ]);
    }

    public function test_single_bom_item_calculation_target_100_bom_1_29(): void
    {
        BomItem::create([
            'variant_id'   => $this->variant->id,
            'material_id'  => $this->kainVoal->id,
            'qty_per_unit' => 1.2900,
        ]);

        $batch = $this->createBatch(100);

        $result = $this->service->generateMaterialPlanning($batch);
        $this->assertTrue($result['created']);
        $this->assertEquals(1, $result['count']);

        $batchMaterial = BatchMaterial::where('batch_id', $batch->id)
            ->where('material_id', $this->kainVoal->id)
            ->first();

        $this->assertNotNull($batchMaterial);
        $this->assertEquals('129.0000', $batchMaterial->required_qty);
        $this->assertEquals('100.0000', $batchMaterial->stock_at_calc);
        $this->assertEquals('29.0000', $batchMaterial->shortage_qty);
    }

    public function test_multiple_bom_items_are_all_snapshotted(): void
    {
        BomItem::create([
            'variant_id'   => $this->variant->id,
            'material_id'  => $this->kainVoal->id,
            'qty_per_unit' => 1.2500,
        ]);

        BomItem::create([
            'variant_id'   => $this->variant->id,
            'material_id'  => $this->labelBrand->id,
            'qty_per_unit' => 1.0000,
        ]);

        $batch = $this->createBatch(200);

        $result = $this->service->generateMaterialPlanning($batch);
        $this->assertEquals(2, $result['count']);

        $this->assertDatabaseHas('batch_materials', [
            'batch_id'     => $batch->id,
            'material_id'  => $this->kainVoal->id,
            'required_qty' => '250.0000',
        ]);

        $this->assertDatabaseHas('batch_materials', [
            'batch_id'     => $batch->id,
            'material_id'  => $this->labelBrand->id,
            'required_qty' => '200.0000',
        ]);
    }

    public function test_bom_immutability_existing_batch_preserves_snapshot_when_bom_master_changes(): void
    {
        $bomItem = BomItem::create([
            'variant_id'   => $this->variant->id,
            'material_id'  => $this->kainVoal->id,
            'qty_per_unit' => 1.2900,
        ]);

        $batch1 = $this->createBatch(100);
        $this->service->generateMaterialPlanning($batch1);

        // 1st batch snapshot is 129.0000
        $this->assertEquals('129.0000', BatchMaterial::where('batch_id', $batch1->id)->value('required_qty'));

        // Update BOM master to 1.3500
        $bomItem->update(['qty_per_unit' => 1.3500]);

        // Existing batch snapshot MUST NOT change
        $this->assertEquals('129.0000', BatchMaterial::where('batch_id', $batch1->id)->value('required_qty'));

        // New Batch uses updated BOM master
        $batch2 = ProductionBatch::create([
            'order_item_id' => $batch1->order_item_id,
            'batch_no'      => 'BAT-TEST-002',
            'target_qty'    => 100,
            'status'        => BatchStatus::BAHAN,
        ]);
        $this->service->generateMaterialPlanning($batch2);

        $this->assertEquals('135.0000', BatchMaterial::where('batch_id', $batch2->id)->value('required_qty'));
    }

    public function test_shortage_and_stock_logic(): void
    {
        BomItem::create([
            'variant_id'   => $this->variant->id,
            'material_id'  => $this->kainVoal->id,
            'qty_per_unit' => 1.2900,
        ]);

        $batch = $this->createBatch(100); // Required: 129

        // Case 1: Stock 100 < 129 -> Shortage 29, Status KURANG, readiness PERLU PEMBELIAN
        $this->kainVoal->stock->update(['qty_on_hand' => 100]);
        $this->service->generateMaterialPlanning($batch);

        $data1 = $this->service->getMaterialRequirementsData($batch);
        $this->assertEquals('29.0000', $data1['items'][0]['shortage_qty']);
        $this->assertEquals('KURANG', $data1['items'][0]['status_label']);
        $this->assertTrue($data1['has_shortage']);
        $this->assertEquals('PERLU PEMBELIAN', $data1['readiness_status']);

        // Case 2: Stock increases to 150 > 129 -> Live shortage 0, Status SIAP, readiness SIAP DIALOKASIKAN
        $this->kainVoal->stock->update(['qty_on_hand' => 150]);
        $data2 = $this->service->getMaterialRequirementsData($batch);
        $this->assertEquals('0.0000', $data2['items'][0]['shortage_qty']);
        $this->assertEquals('SIAP', $data2['items'][0]['status_label']);
        $this->assertFalse($data2['has_shortage']);
        $this->assertEquals('SIAP DIALOKASIKAN', $data2['readiness_status']);

        // Case 3: Stock exact 129 == 129 -> Live shortage 0, Status SIAP, readiness SIAP DIALOKASIKAN
        $this->kainVoal->stock->update(['qty_on_hand' => 129]);
        $data3 = $this->service->getMaterialRequirementsData($batch);
        $this->assertEquals('0.0000', $data3['items'][0]['shortage_qty']);
        $this->assertEquals('SIAP', $data3['items'][0]['status_label']);
        $this->assertFalse($data3['has_shortage']);
        $this->assertEquals('SIAP DIALOKASIKAN', $data3['readiness_status']);

        // Case 4: Stock is 0 -> Live shortage 129, Status KURANG, readiness PERLU PEMBELIAN
        $this->kainVoal->stock->update(['qty_on_hand' => 0]);
        $data4 = $this->service->getMaterialRequirementsData($batch);
        $this->assertEquals('129.0000', $data4['items'][0]['shortage_qty']);
        $this->assertEquals('KURANG', $data4['items'][0]['status_label']);
        $this->assertTrue($data4['has_shortage']);
        $this->assertEquals('PERLU PEMBELIAN', $data4['readiness_status']);
    }

    public function test_no_physical_stock_changes_during_mrp_generation(): void
    {
        BomItem::create([
            'variant_id'   => $this->variant->id,
            'material_id'  => $this->kainVoal->id,
            'qty_per_unit' => 1.2900,
        ]);

        $this->kainVoal->stock->update(['qty_on_hand' => 500]);
        $batch = $this->createBatch(100);

        $this->service->generateMaterialPlanning($batch);

        // Stock remains 500
        $this->assertEquals('500.0000', $this->kainVoal->fresh(['stock'])->stock->qty_on_hand);
        $this->assertEquals(0, \App\Models\Inventory\StockMovement::count());
    }

    public function test_throws_exception_if_variant_has_no_bom_items(): void
    {
        $batch = $this->createBatch(100);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('BOM varian belum tersedia. Lengkapi BOM pada Master Produk sebelum membuat Batch Produksi.');

        $this->service->generateMaterialPlanning($batch);
    }

    public function test_throws_exception_if_bom_contains_inactive_material(): void
    {
        $this->kainVoal->update(['active' => false]);

        BomItem::create([
            'variant_id'   => $this->variant->id,
            'material_id'  => $this->kainVoal->id,
            'qty_per_unit' => 1.2900,
        ]);

        $batch = $this->createBatch(100);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('BOM menggunakan material nonaktif (Kain Voal Premium). Perbarui BOM terlebih dahulu.');

        $this->service->generateMaterialPlanning($batch);
    }

    public function test_validate_bom_returns_collection_when_valid(): void
    {
        BomItem::create([
            'variant_id'   => $this->variant->id,
            'material_id'  => $this->kainVoal->id,
            'qty_per_unit' => 1.2900,
        ]);

        $validated = $this->service->validateBom($this->variant);
        $this->assertCount(1, $validated);
        $this->assertEquals($this->kainVoal->id, $validated->first()->material_id);
    }

    public function test_duplicate_mrp_generation_is_idempotent_no_duplicate_rows(): void
    {
        BomItem::create([
            'variant_id'   => $this->variant->id,
            'material_id'  => $this->kainVoal->id,
            'qty_per_unit' => 1.2900,
        ]);

        $batch = $this->createBatch(100);

        $result1 = $this->service->generateMaterialPlanning($batch);
        $this->assertTrue($result1['created']);

        $result2 = $this->service->generateMaterialPlanning($batch);
        $this->assertFalse($result2['created']);
        $this->assertEquals('Kebutuhan bahan untuk batch ini sudah dihitung.', $result2['message']);

        $this->assertEquals(1, BatchMaterial::where('batch_id', $batch->id)->count());
    }
}
