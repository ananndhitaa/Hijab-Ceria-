<?php

namespace Tests\Feature\Admin;

use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Enums\PurchaseStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Inventory\MaterialStock;
use App\Models\Inventory\Purchase;
use App\Models\Inventory\PurchaseItem;
use App\Models\Inventory\PurchasePayment;
use App\Models\Master\BomItem;
use App\Models\Master\Customer;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Master\Supplier;
use App\Models\Master\Unit;
use App\Enums\MovementType;
use App\Models\Inventory\StockMovement;
use App\Models\Production\BatchMaterial;
use App\Models\Production\ProductionBatch;
use App\Models\Production\ProductionResult;
use App\Models\Production\QcResult;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\User;
use App\Services\Finance\BatchCostCalculationService;
use App\Services\Finance\VariantCostCalculationService;
use App\Services\Inventory\PurchaseOrderService;
use App\Services\Inventory\PurchaseReceivingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VariantHppAndSellingPriceFloorTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private Unit $meter;
    private Unit $roll;
    private Unit $pcs;
    private Supplier $supplier;
    private Material $kain;
    private Material $benang;
    private Material $polybag;
    private Product $product;
    private Customer $customer;

    private VariantCostCalculationService $variantCostService;
    private BatchCostCalculationService $batchCostService;
    private PurchaseOrderService $poService;
    private PurchaseReceivingService $receivingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->variantCostService = app(VariantCostCalculationService::class);
        $this->batchCostService = app(BatchCostCalculationService::class);
        $this->poService = app(PurchaseOrderService::class);
        $this->receivingService = app(PurchaseReceivingService::class);

        $this->adminUser = User::factory()->create([
            'role'   => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->meter = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $this->roll = Unit::create(['unit_name' => 'Roll', 'symbol' => 'roll']);
        $this->pcs = Unit::create(['unit_name' => 'Pieces', 'symbol' => 'pcs']);

        $this->supplier = Supplier::create([
            'supplier_name' => 'PT Tekstil Nusantara',
            'phone'         => '081234567890',
            'active'        => true,
        ]);

        // Materials with stocks
        $this->kain = Material::create([
            'material_name' => 'Kain Voal Premium',
            'unit_id'       => $this->meter->id,
            'cost_per_unit' => 24000,
            'active'        => true,
        ]);
        MaterialStock::create([
            'material_id'   => $this->kain->id,
            'qty_on_hand'   => 500,
            'avg_unit_cost' => 24000,
        ]);

        $this->benang = Material::create([
            'material_name' => 'Benang Jahit Spun',
            'unit_id'       => $this->roll->id,
            'cost_per_unit' => 18000,
            'active'        => true,
        ]);
        MaterialStock::create([
            'material_id'   => $this->benang->id,
            'qty_on_hand'   => 50,
            'avg_unit_cost' => 18000,
        ]);

        $this->polybag = Material::create([
            'material_name' => 'Plastik Polybag Klip',
            'unit_id'       => $this->pcs->id,
            'cost_per_unit' => 500,
            'active'        => true,
        ]);
        MaterialStock::create([
            'material_id'   => $this->polybag->id,
            'qty_on_hand'   => 1000,
            'avg_unit_cost' => 500,
        ]);

        $this->product = Product::create([
            'product_name' => 'Hijab Segiempat Voal Exclusive',
            'active'       => true,
        ]);

        $this->customer = Customer::create([
            'customer_name' => 'Ibu Rahmawati',
            'phone'         => '081298765432',
            'address'       => 'Jakarta Selatan',
        ]);
    }

    // =========================================================================
    // 1-8: REFERENCE HPP & BREAKDOWN FORMULAS
    // =========================================================================

    public function test_01_complete_bom_and_cost_produces_reference_hpp(): void
    {
        $variant = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'Emerald Green',
            'sku'                                  => 'HJB-VOAL-EMR',
            'base_selling_price'                   => 69000,
            'requires_printing'                    => true,
            'printing_cost_per_unit'               => 10000,
            'direct_labor_cost_per_unit'           => 5000,
            'packaging_process_cost_per_good_unit' => 2000,
            'other_direct_cost_per_unit'           => 1000,
            'active'                               => true,
        ]);

        // BOM: 0.95m kain @ 24.000 = 22.800; 0.02 roll benang @ 18.000 = 360
        // Material subtotal = 23.160
        // Direct costs = 10.000 + 5.000 + 2.000 + 1.000 = 18.000
        // Grand total = 23.160 + 18.000 = 41.160
        BomItem::create(['variant_id' => $variant->id, 'material_id' => $this->kain->id, 'qty_per_unit' => 0.95]);
        BomItem::create(['variant_id' => $variant->id, 'material_id' => $this->benang->id, 'qty_per_unit' => 0.02]);

        $calc = $this->variantCostService->calculateEstimatedCost($variant);

        $this->assertTrue($calc['is_available']);
        $this->assertEquals('23160.00', $calc['estimated_material_cost']);
        $this->assertEquals('18000.00', $calc['estimated_direct_cost']);
        $this->assertEquals('41160.00', $calc['grand_total']);
    }

    public function test_02_material_detail_breakdown_correct(): void
    {
        $variant = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'Ruby Red',
            'sku'                                  => 'HJB-VOAL-RBY',
            'base_selling_price'                   => 70000,
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => 0,
            'direct_labor_cost_per_unit'           => 5000,
            'packaging_process_cost_per_good_unit' => 2000,
            'other_direct_cost_per_unit'           => 1000,
            'active'                               => true,
        ]);

        BomItem::create(['variant_id' => $variant->id, 'material_id' => $this->kain->id, 'qty_per_unit' => 1.00]);

        $calc = $this->variantCostService->calculateEstimatedCost($variant);
        $materials = $calc['breakdown']['materials'];

        $this->assertCount(1, $materials);
        $this->assertEquals('Kain Voal Premium', $materials[0]['material_name']);
        $this->assertEquals('1.0000', $materials[0]['qty_per_unit']);
        $this->assertEquals('24000.00', $materials[0]['avg_unit_cost']);
        $this->assertEquals('24000.00', $materials[0]['subtotal']);
    }

    public function test_03_material_subtotal_exact_match_with_bcmath(): void
    {
        $variant = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'Pastel Lilac',
            'sku'                                  => 'HJB-VOAL-LLC',
            'base_selling_price'                   => 75000,
            'requires_printing'                    => true,
            'printing_cost_per_unit'               => 8000,
            'direct_labor_cost_per_unit'           => 4000,
            'packaging_process_cost_per_good_unit' => 1500,
            'other_direct_cost_per_unit'           => 500,
            'active'                               => true,
        ]);

        BomItem::create(['variant_id' => $variant->id, 'material_id' => $this->kain->id, 'qty_per_unit' => 0.95]);
        BomItem::create(['variant_id' => $variant->id, 'material_id' => $this->benang->id, 'qty_per_unit' => 0.02]);

        $calc = $this->variantCostService->calculateEstimatedCost($variant);
        $expected = bcadd(bcmul('0.95', '24000', 4), bcmul('0.02', '18000', 4), 2);

        $this->assertEquals($expected, $calc['breakdown']['material_subtotal']);
    }

    public function test_04_printing_included_correctly_based_on_requires_printing(): void
    {
        // Variant WITH printing
        $variantPrint = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'Printed Motif',
            'sku'                                  => 'HJB-PRT-001',
            'base_selling_price'                   => 80000,
            'requires_printing'                    => true,
            'printing_cost_per_unit'               => 12000,
            'direct_labor_cost_per_unit'           => 5000,
            'packaging_process_cost_per_good_unit' => 2000,
            'other_direct_cost_per_unit'           => 1000,
            'active'                               => true,
        ]);
        BomItem::create(['variant_id' => $variantPrint->id, 'material_id' => $this->kain->id, 'qty_per_unit' => 1]);

        $calcPrint = $this->variantCostService->calculateEstimatedCost($variantPrint);
        $this->assertEquals('12000.00', $calcPrint['breakdown']['printing']);

        // Variant WITHOUT printing
        $variantPlain = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'Plain Motif',
            'sku'                                  => 'HJB-PLN-001',
            'base_selling_price'                   => 50000,
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => 0,
            'direct_labor_cost_per_unit'           => 5000,
            'packaging_process_cost_per_good_unit' => 2000,
            'other_direct_cost_per_unit'           => 1000,
            'active'                               => true,
        ]);
        BomItem::create(['variant_id' => $variantPlain->id, 'material_id' => $this->kain->id, 'qty_per_unit' => 1]);

        $calcPlain = $this->variantCostService->calculateEstimatedCost($variantPlain);
        $this->assertEquals('0.00', $calcPlain['breakdown']['printing']);
    }

    public function test_05_direct_labor_included_correctly(): void
    {
        $variant = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'Labor Test',
            'sku'                                  => 'HJB-LBR-001',
            'base_selling_price'                   => 60000,
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => 0,
            'direct_labor_cost_per_unit'           => 7500,
            'packaging_process_cost_per_good_unit' => 1000,
            'other_direct_cost_per_unit'           => 500,
            'active'                               => true,
        ]);
        BomItem::create(['variant_id' => $variant->id, 'material_id' => $this->kain->id, 'qty_per_unit' => 1]);

        $calc = $this->variantCostService->calculateEstimatedCost($variant);
        $this->assertEquals('7500.00', $calc['breakdown']['direct_labor']);
    }

    public function test_06_packing_process_included_correctly(): void
    {
        $variant = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'Packing Test',
            'sku'                                  => 'HJB-PCK-001',
            'base_selling_price'                   => 60000,
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => 0,
            'direct_labor_cost_per_unit'           => 4000,
            'packaging_process_cost_per_good_unit' => 3500,
            'other_direct_cost_per_unit'           => 500,
            'active'                               => true,
        ]);
        BomItem::create(['variant_id' => $variant->id, 'material_id' => $this->kain->id, 'qty_per_unit' => 1]);

        $calc = $this->variantCostService->calculateEstimatedCost($variant);
        $this->assertEquals('3500.00', $calc['breakdown']['packaging_process']);
    }

    public function test_07_other_direct_cost_included_correctly(): void
    {
        $variant = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'Other Direct Test',
            'sku'                                  => 'HJB-OTH-001',
            'base_selling_price'                   => 60000,
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => 0,
            'direct_labor_cost_per_unit'           => 4000,
            'packaging_process_cost_per_good_unit' => 1000,
            'other_direct_cost_per_unit'           => 2500,
            'active'                               => true,
        ]);
        BomItem::create(['variant_id' => $variant->id, 'material_id' => $this->kain->id, 'qty_per_unit' => 1]);

        $calc = $this->variantCostService->calculateEstimatedCost($variant);
        $this->assertEquals('2500.00', $calc['breakdown']['other_direct']);
    }

    public function test_08_grand_total_breakdown_equals_reference_hpp(): void
    {
        $variant = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'Grand Total Test',
            'sku'                                  => 'HJB-GT-001',
            'base_selling_price'                   => 69000,
            'requires_printing'                    => true,
            'printing_cost_per_unit'               => 10000,
            'direct_labor_cost_per_unit'           => 5000,
            'packaging_process_cost_per_good_unit' => 2000,
            'other_direct_cost_per_unit'           => 1000,
            'active'                               => true,
        ]);
        BomItem::create(['variant_id' => $variant->id, 'material_id' => $this->kain->id, 'qty_per_unit' => 1]);

        $calc = $this->variantCostService->calculateEstimatedCost($variant);
        $ref = $this->variantCostService->getReferenceCost($variant);

        $this->assertEquals($ref['reference_cost'], $calc['breakdown']['grand_total']);
        $this->assertEquals($ref['reference_cost'], $calc['grand_total']);
    }

    // =========================================================================
    // 9-11: MISSING DATA HANDLING (NO FAKE RP 0)
    // =========================================================================

    public function test_09_missing_bom_does_not_produce_fake_zero(): void
    {
        $variant = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'No Bom Variant',
            'sku'                                  => 'HJB-NO-BOM',
            'base_selling_price'                   => 50000,
            'requires_printing'                    => false,
            'direct_labor_cost_per_unit'           => 5000,
            'packaging_process_cost_per_good_unit' => 2000,
            'other_direct_cost_per_unit'           => 1000,
            'active'                               => true,
        ]);

        $calc = $this->variantCostService->calculateEstimatedCost($variant);
        $ref = $this->variantCostService->getReferenceCost($variant);

        $this->assertFalse($calc['is_available']);
        $this->assertNull($calc['estimated_total_cost']);
        $this->assertNull($ref['reference_cost']);
        $this->assertFalse($ref['has_reference']);
        $this->assertNotEmpty($calc['missing_reasons']);
    }

    public function test_10_missing_mwa_does_not_produce_fake_zero(): void
    {
        $newMaterial = Material::create([
            'material_name' => 'Kain Sutra Baru',
            'unit_id'       => $this->meter->id,
            'cost_per_unit' => 0,
            'active'        => true,
        ]);
        // Stock exists but avg_unit_cost is 0.00 (no purchase yet)
        MaterialStock::create([
            'material_id'   => $newMaterial->id,
            'qty_on_hand'   => 0,
            'avg_unit_cost' => 0,
        ]);

        $variant = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'Sutra Variant',
            'sku'                                  => 'HJB-STR-001',
            'base_selling_price'                   => 90000,
            'requires_printing'                    => false,
            'direct_labor_cost_per_unit'           => 5000,
            'packaging_process_cost_per_good_unit' => 2000,
            'other_direct_cost_per_unit'           => 1000,
            'active'                               => true,
        ]);
        BomItem::create(['variant_id' => $variant->id, 'material_id' => $newMaterial->id, 'qty_per_unit' => 1]);

        $calc = $this->variantCostService->calculateEstimatedCost($variant);

        $this->assertFalse($calc['is_available']);
        $this->assertNull($calc['estimated_total_cost']);
        $this->assertStringContainsString('Modal material', $calc['missing_reasons'][0]);
    }

    public function test_11_missing_required_direct_cost_shows_clear_unavailable_state(): void
    {
        $variant = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'Incomplete Cost Variant',
            'sku'                                  => 'HJB-INC-001',
            'base_selling_price'                   => 50000,
            'requires_printing'                    => true,
            'printing_cost_per_unit'               => null, // Missing!
            'direct_labor_cost_per_unit'           => null, // Missing!
            'packaging_process_cost_per_good_unit' => 2000,
            'other_direct_cost_per_unit'           => 1000,
            'active'                               => true,
        ]);
        BomItem::create(['variant_id' => $variant->id, 'material_id' => $this->kain->id, 'qty_per_unit' => 1]);

        $calc = $this->variantCostService->calculateEstimatedCost($variant);

        $this->assertFalse($calc['is_available']);
        $this->assertNull($calc['estimated_total_cost']);
        $this->assertContains('Biaya printing standar belum diisi.', $calc['missing_reasons']);
        $this->assertContains('Biaya tenaga kerja standar belum diisi.', $calc['missing_reasons']);
    }

    // =========================================================================
    // 12-16: VARIANT PRICE FLOOR SERVER GUARD (STRICTLY > REFERENCE HPP)
    // =========================================================================

    public function test_12_variant_price_42k_with_selling_price_69k_accepted(): void
    {
        // Kain 24k + Direct 18k = HPP 42k
        $variant = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'Floor Test Variant',
            'sku'                                  => 'HJB-FLR-001',
            'base_selling_price'                   => null,
            'requires_printing'                    => true,
            'printing_cost_per_unit'               => 10000,
            'direct_labor_cost_per_unit'           => 5000,
            'packaging_process_cost_per_good_unit' => 2000,
            'other_direct_cost_per_unit'           => 1000,
            'active'                               => true,
        ]);
        BomItem::create(['variant_id' => $variant->id, 'material_id' => $this->kain->id, 'qty_per_unit' => 1]);

        $response = $this->actingAs($this->adminUser)->put(route('admin.variants.update', $variant), [
            'variant_name'                         => 'Floor Test Variant',
            'base_selling_price'                   => 69000,
            'requires_printing'                    => 1,
            'printing_cost_per_unit'               => 10000,
            'direct_labor_cost_per_unit'           => 5000,
            'packaging_process_cost_per_good_unit' => 2000,
            'other_direct_cost_per_unit'           => 1000,
            'active'                               => 1,
        ]);

        $response->assertSessionHasNoErrors();
        $variant->refresh();
        $this->assertEquals('69000.00', $variant->base_selling_price);
    }

    public function test_13_variant_price_42k_with_selling_price_42k_rejected(): void
    {
        $variant = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'Floor Test Variant 2',
            'sku'                                  => 'HJB-FLR-002',
            'base_selling_price'                   => null,
            'requires_printing'                    => true,
            'printing_cost_per_unit'               => 10000,
            'direct_labor_cost_per_unit'           => 5000,
            'packaging_process_cost_per_good_unit' => 2000,
            'other_direct_cost_per_unit'           => 1000,
            'active'                               => true,
        ]);
        BomItem::create(['variant_id' => $variant->id, 'material_id' => $this->kain->id, 'qty_per_unit' => 1]);

        $response = $this->actingAs($this->adminUser)->put(route('admin.variants.update', $variant), [
            'variant_name'                         => 'Floor Test Variant 2',
            'base_selling_price'                   => 42000, // Equal to HPP!
            'requires_printing'                    => 1,
            'printing_cost_per_unit'               => 10000,
            'direct_labor_cost_per_unit'           => 5000,
            'packaging_process_cost_per_good_unit' => 2000,
            'other_direct_cost_per_unit'           => 1000,
            'active'                               => 1,
        ]);

        $response->assertSessionHasErrors(['base_selling_price']);
    }

    public function test_14_variant_price_42k_with_selling_price_40k_rejected(): void
    {
        $variant = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'Floor Test Variant 3',
            'sku'                                  => 'HJB-FLR-003',
            'base_selling_price'                   => null,
            'requires_printing'                    => true,
            'printing_cost_per_unit'               => 10000,
            'direct_labor_cost_per_unit'           => 5000,
            'packaging_process_cost_per_good_unit' => 2000,
            'other_direct_cost_per_unit'           => 1000,
            'active'                               => true,
        ]);
        BomItem::create(['variant_id' => $variant->id, 'material_id' => $this->kain->id, 'qty_per_unit' => 1]);

        $response = $this->actingAs($this->adminUser)->put(route('admin.variants.update', $variant), [
            'variant_name'                         => 'Floor Test Variant 3',
            'base_selling_price'                   => 40000, // Strictly lower than HPP!
            'requires_printing'                    => 1,
            'printing_cost_per_unit'               => 10000,
            'direct_labor_cost_per_unit'           => 5000,
            'packaging_process_cost_per_good_unit' => 2000,
            'other_direct_cost_per_unit'           => 1000,
            'active'                               => 1,
        ]);

        $response->assertSessionHasErrors(['base_selling_price']);
    }

    public function test_15_crafted_post_rejected_server_side(): void
    {
        $variant = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'Crafted Post Test',
            'sku'                                  => 'HJB-CRF-001',
            'base_selling_price'                   => 69000,
            'requires_printing'                    => true,
            'printing_cost_per_unit'               => 10000,
            'direct_labor_cost_per_unit'           => 5000,
            'packaging_process_cost_per_good_unit' => 2000,
            'other_direct_cost_per_unit'           => 1000,
            'active'                               => true,
        ]);
        BomItem::create(['variant_id' => $variant->id, 'material_id' => $this->kain->id, 'qty_per_unit' => 1]);

        // Direct HTTP call bypassing HTML
        $response = $this->actingAs($this->adminUser)
            ->from(route('admin.variants.edit', $variant))
            ->put(route('admin.variants.update', $variant), [
                'variant_name'                         => 'Crafted Post Test',
                'base_selling_price'                   => 35000, // < 42k
                'requires_printing'                    => 1,
                'printing_cost_per_unit'               => 10000,
                'direct_labor_cost_per_unit'           => 5000,
                'packaging_process_cost_per_good_unit' => 2000,
                'other_direct_cost_per_unit'           => 1000,
                'active'                               => 1,
            ]);

        $response->assertRedirect(route('admin.variants.edit', $variant));
        $response->assertSessionHasErrors(['base_selling_price']);
        $variant->refresh();
        $this->assertEquals('69000.00', $variant->base_selling_price); // Unchanged
    }

    public function test_16_no_automatic_markup_on_rejected_price(): void
    {
        $variant = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'No Markup Test',
            'sku'                                  => 'HJB-MKP-001',
            'base_selling_price'                   => 69000,
            'requires_printing'                    => true,
            'printing_cost_per_unit'               => 10000,
            'direct_labor_cost_per_unit'           => 5000,
            'packaging_process_cost_per_good_unit' => 2000,
            'other_direct_cost_per_unit'           => 1000,
            'active'                               => true,
        ]);
        BomItem::create(['variant_id' => $variant->id, 'material_id' => $this->kain->id, 'qty_per_unit' => 1]);

        $this->actingAs($this->adminUser)->put(route('admin.variants.update', $variant), [
            'variant_name'                         => 'No Markup Test',
            'base_selling_price'                   => 41000, // < 42k
            'requires_printing'                    => 1,
            'printing_cost_per_unit'               => 10000,
            'direct_labor_cost_per_unit'           => 5000,
            'packaging_process_cost_per_good_unit' => 2000,
            'other_direct_cost_per_unit'           => 1000,
            'active'                               => 1,
        ]);

        $variant->refresh();
        // Price was NOT auto-marked up to 42001 or anything else
        $this->assertEquals('69000.00', $variant->base_selling_price);
    }

    // =========================================================================
    // 17-20: SALES ORDER PRICE FLOOR HARD GUARD
    // =========================================================================

    public function test_17_order_transaction_price_greater_than_hpp_accepted(): void
    {
        $variant = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'Order OK Variant',
            'sku'                                  => 'HJB-SO-001',
            'base_selling_price'                   => 69000, // > 42k
            'requires_printing'                    => true,
            'printing_cost_per_unit'               => 10000,
            'direct_labor_cost_per_unit'           => 5000,
            'packaging_process_cost_per_good_unit' => 2000,
            'other_direct_cost_per_unit'           => 1000,
            'active'                               => true,
        ]);
        BomItem::create(['variant_id' => $variant->id, 'material_id' => $this->kain->id, 'qty_per_unit' => 1]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.orders.store'), [
            'customer_id' => $this->customer->id,
            'order_date'  => now()->toDateString(),
            'notes'       => 'Valid order',
            'items'       => [
                [
                    'variant_id'  => $variant->id,
                    'qty_ordered' => 10,
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('orders', ['customer_id' => $this->customer->id]);
    }

    public function test_18_order_transaction_price_equal_to_hpp_rejected(): void
    {
        // Variant created before BOM, then master price set to 42k
        $variant = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'Order Equal Variant',
            'sku'                                  => 'HJB-SO-002',
            'base_selling_price'                   => 42000, // Equal to HPP!
            'requires_printing'                    => true,
            'printing_cost_per_unit'               => 10000,
            'direct_labor_cost_per_unit'           => 5000,
            'packaging_process_cost_per_good_unit' => 2000,
            'other_direct_cost_per_unit'           => 1000,
            'active'                               => true,
        ]);
        BomItem::create(['variant_id' => $variant->id, 'material_id' => $this->kain->id, 'qty_per_unit' => 1]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.orders.store'), [
            'customer_id' => $this->customer->id,
            'order_date'  => now()->toDateString(),
            'items'       => [
                [
                    'variant_id'  => $variant->id,
                    'qty_ordered' => 10,
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['items.0.variant_id']);
    }

    public function test_19_order_transaction_price_less_than_hpp_rejected(): void
    {
        $variant = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'Order Below Variant',
            'sku'                                  => 'HJB-SO-003',
            'base_selling_price'                   => 39000, // < 42k
            'requires_printing'                    => true,
            'printing_cost_per_unit'               => 10000,
            'direct_labor_cost_per_unit'           => 5000,
            'packaging_process_cost_per_good_unit' => 2000,
            'other_direct_cost_per_unit'           => 1000,
            'active'                               => true,
        ]);
        BomItem::create(['variant_id' => $variant->id, 'material_id' => $this->kain->id, 'qty_per_unit' => 1]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.orders.store'), [
            'customer_id' => $this->customer->id,
            'order_date'  => now()->toDateString(),
            'items'       => [
                [
                    'variant_id'  => $variant->id,
                    'qty_ordered' => 10,
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['items.0.variant_id']);
    }

    public function test_20_historical_order_price_immutable_when_costs_or_prices_change(): void
    {
        $variant = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'Historical Variant',
            'sku'                                  => 'HJB-HIST-001',
            'base_selling_price'                   => 69000,
            'requires_printing'                    => true,
            'printing_cost_per_unit'               => 10000,
            'direct_labor_cost_per_unit'           => 5000,
            'packaging_process_cost_per_good_unit' => 2000,
            'other_direct_cost_per_unit'           => 1000,
            'active'                               => true,
        ]);
        BomItem::create(['variant_id' => $variant->id, 'material_id' => $this->kain->id, 'qty_per_unit' => 1]);

        $order = Order::create([
            'customer_id'  => $this->customer->id,
            'order_no'     => 'SO-HIST-001',
            'order_date'   => now()->toDateString(),
            'status'       => OrderStatus::DEAL,
            'total_qty'    => 10,
            'total_amount' => 690000,
        ]);
        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 69000,
            'subtotal'           => 690000,
        ]);

        // Master variant price is updated later
        $variant->update(['base_selling_price' => 85000]);

        $item->refresh();
        $this->assertEquals('69000.00', $item->unit_selling_price);
    }

    // =========================================================================
    // 21-22: MWA CHANGE BEHAVIOR (REFERENCE HPP VS HISTORICAL HPP)
    // =========================================================================

    public function test_21_new_receiving_changes_current_reference_hpp(): void
    {
        $variant = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'MWA Test Variant',
            'sku'                                  => 'HJB-MWA-001',
            'base_selling_price'                   => 80000,
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => 0,
            'direct_labor_cost_per_unit'           => 5000,
            'packaging_process_cost_per_good_unit' => 2000,
            'other_direct_cost_per_unit'           => 1000,
            'active'                               => true,
        ]);
        BomItem::create(['variant_id' => $variant->id, 'material_id' => $this->kain->id, 'qty_per_unit' => 1]);

        // Current Kain MWA = 24.000 -> Reference HPP = 24.000 + 8.000 = 32.000
        $refBefore = $this->variantCostService->getReferenceCost($variant);
        $this->assertEquals('32000.00', $refBefore['reference_cost']);

        // New purchase receiving with higher unit price
        $purchase = $this->poService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => now()->toDateString(),
            'notes'         => 'Pembelian kain harga baru',
            'items'         => [
                [
                    'material_id' => $this->kain->id,
                    'qty'         => 500, // existing 500 @ 24.000
                    'unit_price'  => 30000, // new incoming @ 30.000
                ],
            ],
        ]);

        $purchase->update([
            'status'          => PurchaseStatus::ORDERED,
            'approval_status' => \App\Enums\PurchaseApprovalStatus::APPROVED,
            'approved_at'     => now(),
        ]);
        PurchasePayment::create([
            'purchase_id'    => $purchase->id,
            'payment_date'   => now()->toDateString(),
            'amount'         => $purchase->total_amount,
            'payment_method' => 'TRANSFER',
            'notes'          => 'Lunas',
            'created_by'     => $this->adminUser->id,
        ]);

        $this->receivingService->receivePurchase($purchase, $this->adminUser->id);

        // MWA recalculated: (500*24.000 + 500*30.000) / 1000 = 27.000
        $this->kain->stock->refresh();
        $this->assertEquals('27000.0000', $this->kain->stock->avg_unit_cost);

        // Current Reference HPP updates automatically: 27.000 + 8.000 = 35.000
        $refAfter = $this->variantCostService->getReferenceCost($variant);
        $this->assertEquals('35000.00', $refAfter['reference_cost']);
    }

    public function test_22_historical_actual_batch_hpp_unchanged_by_subsequent_purchases(): void
    {
        $variant = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'Batch History Variant',
            'sku'                                  => 'HJB-BTH-001',
            'base_selling_price'                   => 80000,
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => 0,
            'direct_labor_cost_per_unit'           => 5000,
            'packaging_process_cost_per_good_unit' => 2000,
            'other_direct_cost_per_unit'           => 1000,
            'active'                               => true,
        ]);

        $order = Order::create([
            'customer_id'  => $this->customer->id,
            'order_no'     => 'SO-BTH-001',
            'order_date'   => now()->toDateString(),
            'status'       => OrderStatus::DEAL,
            'total_qty'    => 10,
            'total_amount' => 800000,
        ]);
        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 80000,
            'subtotal'           => 800000,
        ]);

        $batch = ProductionBatch::create([
            'order_item_id'                                 => $orderItem->id,
            'batch_no'                                      => 'BAT-20261008-001',
            'target_qty'                                    => 10,
            'status'                                        => BatchStatus::SELESAI,
            'material_usage_recorded_at'                    => now(),
            'requires_printing'                             => false,
            'printing_cost_per_unit_snapshot'               => 0,
            'direct_labor_cost_per_unit_snapshot'           => 5000,
            'packaging_process_cost_per_good_unit_snapshot' => 2000,
            'other_direct_cost_per_unit_snapshot'           => 1000,
            'created_by_user_id'                            => $this->adminUser->id,
        ]);

        $bm = BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->kain->id,
            'allocated_qty' => 10,
            'used_qty'      => 10,
        ]);

        StockMovement::create([
            'material_id'         => $this->kain->id,
            'movement_type'       => MovementType::OUT,
            'qty'                 => -10,
            'unit_cost_snapshot'  => 24000,
            'total_cost_snapshot' => 240000,
            'reference_type'      => 'batch_materials',
            'reference_id'        => $bm->id,
            'movement_date'       => now(),
            'created_by'          => $this->adminUser->id,
        ]);

        $prodResult = ProductionResult::create([
            'batch_id'    => $batch->id,
            'actual_qty'  => 10,
            'result_date' => now()->toDateString(),
            'recorded_by' => $this->adminUser->id,
        ]);

        QcResult::create([
            'production_result_id' => $prodResult->id,
            'good_qty'             => 10,
            'reject_qty'           => 0,
            'qc_date'              => now()->toDateString(),
            'qc_at'                => now(),
            'qc_by'                => $this->adminUser->id,
        ]);

        $batchHppBefore = $this->batchCostService->calculateBatchHpp($batch);
        $this->assertEquals('32000.0000', $batchHppBefore['hpp_per_good_pcs']);

        // Now change stock MWA
        $this->kain->stock->update(['avg_unit_cost' => 35000]);

        // Historical batch HPP remains 32.000
        $batchHppAfter = $this->batchCostService->calculateBatchHpp($batch);
        $this->assertEquals('32000.0000', $batchHppAfter['hpp_per_good_pcs']);
    }

    // =========================================================================
    // 23-26: ACTUAL HPP DECOUPLING & VIEWS HTTP 200
    // =========================================================================

    public function test_23_completed_batch_shows_latest_actual_hpp(): void
    {
        $variant = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'Actual HPP Variant',
            'sku'                                  => 'HJB-ACT-001',
            'base_selling_price'                   => 80000,
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => 0,
            'direct_labor_cost_per_unit'           => 5000,
            'packaging_process_cost_per_good_unit' => 2000,
            'other_direct_cost_per_unit'           => 1000,
            'active'                               => true,
        ]);

        $order = Order::create([
            'customer_id'  => $this->customer->id,
            'order_no'     => 'SO-ACT-001',
            'order_date'   => now()->toDateString(),
            'status'       => OrderStatus::DEAL,
            'total_qty'    => 10,
            'total_amount' => 800000,
        ]);
        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 80000,
            'subtotal'           => 800000,
        ]);

        $batch = ProductionBatch::create([
            'order_item_id'                                 => $orderItem->id,
            'batch_no'                                      => 'BAT-20261008-099',
            'target_qty'                                    => 10,
            'status'                                        => BatchStatus::SELESAI,
            'material_usage_recorded_at'                    => now(),
            'requires_printing'                             => false,
            'printing_cost_per_unit_snapshot'               => 0,
            'direct_labor_cost_per_unit_snapshot'           => 5000,
            'packaging_process_cost_per_good_unit_snapshot' => 0,
            'other_direct_cost_per_unit_snapshot'           => 0,
            'created_by_user_id'                            => $this->adminUser->id,
        ]);

        $bm = BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->kain->id,
            'allocated_qty' => 10,
            'used_qty'      => 10,
        ]);

        StockMovement::create([
            'material_id'         => $this->kain->id,
            'movement_type'       => MovementType::OUT,
            'qty'                 => -10,
            'unit_cost_snapshot'  => 24000,
            'total_cost_snapshot' => 240000,
            'reference_type'      => 'batch_materials',
            'reference_id'        => $bm->id,
            'movement_date'       => now(),
            'created_by'          => $this->adminUser->id,
        ]);

        $prodResult = ProductionResult::create([
            'batch_id'    => $batch->id,
            'actual_qty'  => 10,
            'result_date' => now()->toDateString(),
            'recorded_by' => $this->adminUser->id,
        ]);

        QcResult::create([
            'production_result_id' => $prodResult->id,
            'good_qty'             => 10,
            'reject_qty'           => 0,
            'qc_date'              => now()->toDateString(),
            'qc_at'                => now(),
            'qc_by'                => $this->adminUser->id,
        ]);

        $latest = $this->variantCostService->getLatestActualHpp($variant);

        $this->assertTrue($latest['is_available']);
        $this->assertEquals('BAT-20261008-099', $latest['latest_batch_no']);
        $this->assertEquals('29000.0000', $latest['latest_actual_hpp']);
        $this->assertNotEmpty($latest['breakdown']);
    }

    public function test_24_incomplete_batch_excluded_from_actual_hpp(): void
    {
        $variant = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'Incomplete Batch Variant',
            'sku'                                  => 'HJB-INC-BTH',
            'base_selling_price'                   => 80000,
            'requires_printing'                    => false,
            'active'                               => true,
        ]);

        $order = Order::create([
            'customer_id'  => $this->customer->id,
            'order_no'     => 'SO-INC-001',
            'order_date'   => now()->toDateString(),
            'status'       => OrderStatus::DEAL,
            'total_qty'    => 10,
            'total_amount' => 800000,
        ]);
        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 80000,
            'subtotal'           => 800000,
        ]);

        // Batch in PRODUKSI status (not SELESAI)
        ProductionBatch::create([
            'order_item_id'      => $orderItem->id,
            'batch_no'           => 'BAT-20261008-500',
            'target_qty'         => 10,
            'status'             => BatchStatus::PRODUKSI,
            'created_by_user_id' => $this->adminUser->id,
        ]);

        $latest = $this->variantCostService->getLatestActualHpp($variant);

        $this->assertFalse($latest['is_available']);
        $this->assertNull($latest['latest_actual_hpp']);
    }

    public function test_25_no_completed_batch_returns_unavailable(): void
    {
        $variant = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'No Batch Variant',
            'sku'                                  => 'HJB-NO-BTH',
            'base_selling_price'                   => 80000,
            'requires_printing'                    => false,
            'active'                               => true,
        ]);

        $latest = $this->variantCostService->getLatestActualHpp($variant);

        $this->assertFalse($latest['is_available']);
        $this->assertNull($latest['latest_actual_hpp']);
        $this->assertNull($latest['latest_batch_no']);
    }

    public function test_26_product_and_variant_detail_pages_http_200(): void
    {
        $variant = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'View Test Variant',
            'sku'                                  => 'HJB-VIEW-001',
            'base_selling_price'                   => 69000,
            'requires_printing'                    => true,
            'printing_cost_per_unit'               => 10000,
            'direct_labor_cost_per_unit'           => 5000,
            'packaging_process_cost_per_good_unit' => 2000,
            'other_direct_cost_per_unit'           => 1000,
            'active'                               => true,
        ]);
        BomItem::create(['variant_id' => $variant->id, 'material_id' => $this->kain->id, 'qty_per_unit' => 1]);

        $productRes = $this->actingAs($this->adminUser)->get(route('admin.products.show', $this->product));
        $productRes->assertStatus(200);
        $productRes->assertSee('HPP / Modal Acuan');

        $variantRes = $this->actingAs($this->adminUser)->get(route('admin.variants.show', $variant));
        $variantRes->assertStatus(200);
        $variantRes->assertSee('Harga &amp; Modal', false);
        $variantRes->assertSee('[ Lihat Rincian HPP ]');
    }

    // =========================================================================
    // 27: PACKAGING MATERIAL VS PACKAGING PROCESS NO DOUBLE COUNT
    // =========================================================================

    public function test_27_packaging_material_in_bom_not_duplicated_by_process_packing_cost(): void
    {
        $variant = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'Packaging Double Count Test',
            'sku'                                  => 'HJB-PKG-001',
            'base_selling_price'                   => 75000,
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => 0,
            'direct_labor_cost_per_unit'           => 5000,
            'packaging_process_cost_per_good_unit' => 2000, // Process labor fee: 2.000
            'other_direct_cost_per_unit'           => 1000,
            'active'                               => true,
        ]);

        // BOM contains Kain (24.000) AND Polybag material (500)
        BomItem::create(['variant_id' => $variant->id, 'material_id' => $this->kain->id, 'qty_per_unit' => 1]);
        BomItem::create(['variant_id' => $variant->id, 'material_id' => $this->polybag->id, 'qty_per_unit' => 1]);

        $calc = $this->variantCostService->calculateEstimatedCost($variant);

        // Material cost: 24.000 + 500 = 24.500
        $this->assertEquals('24500.00', $calc['estimated_material_cost']);

        // Direct cost: Labor (5.000) + Packaging Process (2.000) + Other (1.000) = 8.000
        $this->assertEquals('8000.00', $calc['estimated_direct_cost']);

        // Grand total: 24.500 + 8.000 = 32.500
        $this->assertEquals('32500.00', $calc['grand_total']);

        // Verify Polybag material exists strictly in materials, not in direct costs
        $matNames = collect($calc['breakdown']['materials'])->pluck('material_name')->all();
        $this->assertContains('Plastik Polybag Klip', $matNames);
        $this->assertEquals('2000.00', $calc['breakdown']['packaging_process']);
    }
}
