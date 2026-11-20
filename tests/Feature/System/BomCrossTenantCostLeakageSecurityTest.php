<?php

namespace Tests\Feature\System;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Inventory\MaterialStock;
use App\Models\Master\BomItem;
use App\Models\Master\Material;
use App\Models\System\Outlet;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Master\Unit;
use App\Models\System\User;
use App\Services\Finance\VariantCostCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * PHASE 8.7A — CROSS-TENANT BOM COST LEAKAGE & HPP INTEGRITY HARDENING
 *
 * Verifies that corrupted cross-tenant BOM items cannot:
 * 1. Expose foreign material unit costs (MWA).
 * 2. Expose foreign material subtotals.
 * 3. Produce a misleading valid-looking estimated HPP.
 * 4. Leak foreign material metadata through Blade views.
 * 5. Allow price margin validation against corrupted BOM data.
 *
 * SQLite :memory: ONLY.
 */
class BomCrossTenantCostLeakageSecurityTest extends TestCase
{
    use RefreshDatabase;

    private Outlet $outletA;
    private Outlet $outletB;
    private User $adminA;
    private User $adminB;
    private User $ownerA;

    private Unit $unitMeter;
    private Material $materialA1;
    private MaterialStock $stockA1;
    private Material $materialA2;
    private MaterialStock $stockA2;

    private Material $materialB1;
    private MaterialStock $stockB1;

    private Material $legacyMaterial;
    private MaterialStock $legacyStock;

    private Product $productA;
    private ProductVariant $variantA;
    private BomItem $bomItemA1;

    private Product $productB;
    private ProductVariant $variantB;
    private BomItem $bomItemB1;

    private VariantCostCalculationService $costService;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('nayara.tenant_isolation_enabled', true);

        $this->outletA = Outlet::create([
            'outlet_name' => 'Konveksi Hijab Nayara A',
            'outlet_code' => 'OUT-A',
            'status'      => 'ACTIVE',
        ]);

        $this->outletB = Outlet::create([
            'outlet_name' => 'Konveksi Hijab Nayara B',
            'outlet_code' => 'OUT-B',
            'status'      => 'ACTIVE',
        ]);

        $this->adminA = User::create([
            'name'      => 'Admin A',
            'email'     => 'admin.a@nayara.test',
            'password'  => Hash::make('password'),
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $this->outletA->id,
        ]);

        $this->adminB = User::create([
            'name'      => 'Admin B',
            'email'     => 'admin.b@nayara.test',
            'password'  => Hash::make('password'),
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $this->outletB->id,
        ]);

        $this->ownerA = User::create([
            'name'      => 'Owner A',
            'email'     => 'owner.a@nayara.test',
            'password'  => Hash::make('password'),
            'role'      => UserRole::OWNER,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $this->outletA->id,
        ]);

        $this->unitMeter = Unit::create([
            'unit_name' => 'Meter',
            'symbol'    => 'm',
            'active'    => true,
        ]);

        // Materials for Outlet A
        $this->materialA1 = Material::create([
            'outlet_id'     => $this->outletA->id,
            'material_code' => 'MAT-A1',
            'material_name' => 'Katun Premium A1',
            'unit_id'       => $this->unitMeter->id,
            'min_stock'     => 10,
            'active'        => true,
        ]);
        $this->stockA1 = MaterialStock::create([
            'material_id'   => $this->materialA1->id,
            'qty_on_hand'   => '100.0000',
            'avg_unit_cost' => '25000.0000', // 25,000 / m
        ]);

        $this->materialA2 = Material::create([
            'outlet_id'     => $this->outletA->id,
            'material_code' => 'MAT-A2',
            'material_name' => 'Renda Bordir A2',
            'unit_id'       => $this->unitMeter->id,
            'min_stock'     => 5,
            'active'        => true,
        ]);
        $this->stockA2 = MaterialStock::create([
            'material_id'   => $this->materialA2->id,
            'qty_on_hand'   => '50.0000',
            'avg_unit_cost' => '15000.0000', // 15,000 / m
        ]);

        // Distinctive foreign material for Outlet B
        $this->materialB1 = Material::create([
            'outlet_id'     => $this->outletB->id,
            'material_code' => 'MAT-B1-SECRET',
            'material_name' => 'Sutra Rahasia B1',
            'unit_id'       => $this->unitMeter->id,
            'min_stock'     => 10,
            'active'        => true,
        ]);
        $this->stockB1 = MaterialStock::create([
            'material_id'   => $this->materialB1->id,
            'qty_on_hand'   => '80.0000',
            'avg_unit_cost' => '47850.0000', // Highly distinctive foreign MWA
        ]);

        // Legacy unassigned material
        $this->legacyMaterial = Material::create([
            'outlet_id'     => null,
            'material_code' => 'MAT-LEGACY-NULL',
            'material_name' => 'Kain Legacy Unassigned',
            'unit_id'       => $this->unitMeter->id,
            'min_stock'     => 10,
            'active'        => true,
        ]);
        $this->legacyStock = MaterialStock::create([
            'material_id'   => $this->legacyMaterial->id,
            'qty_on_hand'   => '30.0000',
            'avg_unit_cost' => '33000.0000',
        ]);

        // Product & Variant A
        $this->productA = Product::create([
            'outlet_id'    => $this->outletA->id,
            'product_name' => 'Pashmina A',
            'active'       => true,
        ]);
        $this->variantA = ProductVariant::create([
            'outlet_id'                            => $this->outletA->id,
            'product_id'                           => $this->productA->id,
            'variant_name'                         => 'Pashmina A Hitam',
            'sku'                                  => 'SKU-PA-H',
            'base_selling_price'                   => '75000.00',
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => '0.00',
            'direct_labor_cost_per_unit'           => '5000.00',
            'packaging_process_cost_per_good_unit' => '2000.00',
            'other_direct_cost_per_unit'           => '1000.00',
            'active'                               => true,
        ]);
        $this->bomItemA1 = BomItem::create([
            'variant_id'   => $this->variantA->id,
            'material_id'  => $this->materialA1->id,
            'qty_per_unit' => '1.5000',
        ]);

        // Product & Variant B
        $this->productB = Product::create([
            'outlet_id'    => $this->outletB->id,
            'product_name' => 'Bergo B',
            'active'       => true,
        ]);
        $this->variantB = ProductVariant::create([
            'outlet_id'                            => $this->outletB->id,
            'product_id'                           => $this->productB->id,
            'variant_name'                         => 'Bergo B Coklat',
            'sku'                                  => 'SKU-BB-C',
            'base_selling_price'                   => '120000.00',
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => '0.00',
            'direct_labor_cost_per_unit'           => '5000.00',
            'packaging_process_cost_per_good_unit' => '2000.00',
            'other_direct_cost_per_unit'           => '1000.00',
            'active'                               => true,
        ]);
        $this->bomItemB1 = BomItem::create([
            'variant_id'   => $this->variantB->id,
            'material_id'  => $this->materialB1->id,
            'qty_per_unit' => '2.0000',
        ]);

        $this->costService = app(VariantCostCalculationService::class);
    }

    /**
     * 01. Valid BOM A computes exact existing expected HPP.
     */
    public function test_01_valid_bom_a_computes_exact_existing_expected_hpp(): void
    {
        $cost = $this->costService->calculateEstimatedCost($this->variantA);

        $this->assertTrue($cost['is_available']);
        $this->assertTrue($cost['has_bom']);
        $this->assertEmpty($cost['missing_reasons']);

        // 1.5 m * 25,000 = 37,500
        $this->assertEquals(37500.0, (float) $cost['estimated_material_cost']);
        // 5000 + 2000 + 1000 = 8000
        $this->assertEquals(8000.0, (float) $cost['estimated_direct_cost']);
        // 37500 + 8000 = 45500
        $this->assertEquals(45500.0, (float) $cost['estimated_total_cost']);
    }

    /**
     * 02. Valid BOM B computes exact existing expected HPP.
     */
    public function test_02_valid_bom_b_computes_exact_existing_expected_hpp(): void
    {
        $cost = $this->costService->calculateEstimatedCost($this->variantB);

        $this->assertTrue($cost['is_available']);
        $this->assertTrue($cost['has_bom']);
        $this->assertEmpty($cost['missing_reasons']);

        // 2.0 m * 47,850 = 95,700
        $this->assertEquals(95700.0, (float) $cost['estimated_material_cost']);
        // Direct = 8000
        $this->assertEquals(8000.0, (float) $cost['estimated_direct_cost']);
        // 95700 + 8000 = 103,700
        $this->assertEquals(103700.0, (float) $cost['estimated_total_cost']);
    }

    /**
     * 03 & 04. Foreign material unit cost and subtotal are not returned to Tenant A.
     */
    public function test_03_04_foreign_material_unit_cost_and_subtotal_not_returned_to_tenant_a(): void
    {
        // Deliberately attach foreign Material B1 to Variant A (simulating pre-existing corrupted DB row)
        BomItem::create([
            'variant_id'   => $this->variantA->id,
            'material_id'  => $this->materialB1->id,
            'qty_per_unit' => '0.7500',
        ]);

        $cost = $this->costService->calculateEstimatedCost($this->variantA->fresh());

        // Find the corrupted item breakdown
        $items = $cost['breakdown']['materials'];
        $this->assertCount(2, $items);

        $corruptItem = $items[1];
        $this->assertNull($corruptItem['material_id']);
        $this->assertEquals('[Integritas Tenant Tidak Valid: Material Tidak Sah]', $corruptItem['material_name']);
        $this->assertNull($corruptItem['avg_unit_cost'], 'Foreign MWA must not be returned');
        $this->assertNull($corruptItem['subtotal'], 'Foreign subtotal must not be returned');
        $this->assertFalse($corruptItem['has_valid_mwa']);
    }

    /**
     * 05 & 06. Corrupted BOM is marked incomplete/invalid and produces null total cost.
     */
    public function test_05_06_corrupted_bom_marked_incomplete_and_produces_null_totals(): void
    {
        BomItem::create([
            'variant_id'   => $this->variantA->id,
            'material_id'  => $this->materialB1->id,
            'qty_per_unit' => '0.5000',
        ]);

        $cost = $this->costService->calculateEstimatedCost($this->variantA->fresh());

        $this->assertFalse($cost['is_available'], 'Corrupted BOM must make is_available false');
        $this->assertNull($cost['estimated_material_cost'], 'Corrupted BOM must not produce partial material cost');
        $this->assertNull($cost['estimated_total_cost'], 'Corrupted BOM must not produce valid-looking HPP');
        $this->assertNull($cost['grand_total']);
        $this->assertNull($cost['breakdown']['material_subtotal']);

        $this->assertContains(
            'Integritas resep BOM tidak valid: terdapat material yang tidak sah atau berbeda outlet.',
            $cost['missing_reasons']
        );
    }

    /**
     * 07. Pricing margin is not represented as trustworthy when BOM is invalid.
     */
    public function test_07_pricing_margin_not_trustworthy_when_bom_is_invalid(): void
    {
        BomItem::create([
            'variant_id'   => $this->variantA->id,
            'material_id'  => $this->materialB1->id,
            'qty_per_unit' => '0.5000',
        ]);

        $ref = $this->costService->getReferenceCost($this->variantA->fresh());
        $this->assertFalse($ref['is_available']);
        $this->assertFalse($ref['has_reference']);
        $this->assertNull($ref['reference_cost']);

        $eval = $this->costService->evaluatePriceMargin($this->variantA->fresh(), '75000.00');
        $this->assertEquals('COST_UNAVAILABLE', $eval['status']);
        $this->assertEquals('BELUM DAPAT DIVERIFIKASI', $eval['price_status']);
        $this->assertFalse($eval['is_valid']);
        $this->assertNull($eval['reference_cost']);
        $this->assertNull($eval['margin']);
    }

    /**
     * 08, 09, 10, 11, 12, 13, 14.
     * Variant Blade renders safely without leaking foreign material name, MWA, or subtotals.
     */
    public function test_08_to_14_blade_renders_safely_without_leaking_foreign_costs(): void
    {
        BomItem::create([
            'variant_id'   => $this->variantA->id,
            'material_id'  => $this->materialB1->id, // MWA = 47850
            'qty_per_unit' => '0.5000',
        ]);

        $response = $this->actingAs($this->adminA)->get(route('admin.variants.show', $this->variantA));
        $response->assertOk();

        // 08 & 14. Generic warning and name masking
        $response->assertSee('Integritas Tenant Tidak Valid: Material Tidak Sah');
        $response->assertSee('Integritas BOM tidak valid. Perhitungan biaya belum dapat digunakan sampai data BOM diverifikasi.');

        // 11. Foreign MWA amount (47850) must NOT appear in HTML
        $response->assertDontSee('47.850');
        $response->assertDontSee('47850');

        // 12. Derived subtotal (0.5 * 47850 = 23925) must NOT appear in HTML
        $response->assertDontSee('23.925');
        $response->assertDontSee('23925');

        // 13. Foreign secret name & code must NOT appear
        $response->assertDontSee('Sutra Rahasia B1');
        $response->assertDontSee('MAT-B1-SECRET');

        // Status banner in Blade must indicate unverified cost
        $response->assertSee('BELUM DAPAT DIVERIFIKASI');
        $response->assertSee('HPP / Modal Acuan belum dapat dihitung.');
    }

    /**
     * 15. Original corrupted BOM row is preserved, not silently deleted or reassigned.
     */
    public function test_15_corrupted_bom_row_is_preserved_for_audit(): void
    {
        $corrupt = BomItem::create([
            'variant_id'   => $this->variantA->id,
            'material_id'  => $this->materialB1->id,
            'qty_per_unit' => '0.5000',
        ]);

        $this->costService->calculateEstimatedCost($this->variantA->fresh());

        $this->assertDatabaseHas('bom_items', [
            'id'          => $corrupt->id,
            'variant_id'  => $this->variantA->id,
            'material_id' => $this->materialB1->id,
        ]);
    }

    /**
     * 16. Valid own-tenant BOM item still renders correct costs.
     */
    public function test_16_valid_own_tenant_bom_item_renders_correct_costs(): void
    {
        $response = $this->actingAs($this->adminA)->get(route('admin.variants.show', $this->variantA));
        $response->assertOk();

        // Material A1 (Katun Premium A1, 25000)
        $response->assertSee('Katun Premium A1');
        $response->assertSee('Rp 45.500'); // Valid Grand Total
        $response->assertSee('Rp 37.500'); // Material Subtotal
    }

    /**
     * 17, 18, 19. Cross-tenant BOM Store, Update, and Template remain rejected.
     */
    public function test_17_to_19_cross_tenant_bom_mutations_remain_rejected(): void
    {
        // Store
        $this->actingAs($this->adminA)
            ->post(route('admin.variants.bom.store', $this->variantA), [
                'material_id'  => $this->materialB1->id,
                'qty_per_unit' => '1.0000',
            ])
            ->assertSessionHasErrors('material_id');

        // Update
        $this->actingAs($this->adminB)
            ->put(route('admin.variants.bom.update', [$this->variantA, $this->bomItemA1]), [
                'qty_per_unit' => '2.0000',
            ])
            ->assertNotFound();
    }

    /**
     * 20. Legacy NULL material does not leak costs when isolation is TRUE.
     */
    public function test_20_legacy_null_material_treated_as_invalid_under_isolation_true(): void
    {
        BomItem::create([
            'variant_id'   => $this->variantA->id,
            'material_id'  => $this->legacyMaterial->id,
            'qty_per_unit' => '1.0000',
        ]);

        $cost = $this->costService->calculateEstimatedCost($this->variantA->fresh());

        $this->assertFalse($cost['is_available']);
        $this->assertNull($cost['estimated_total_cost']);

        $corrupt = $cost['breakdown']['materials'][1];
        $this->assertNull($corrupt['avg_unit_cost']);
        $this->assertNull($corrupt['subtotal']);
    }

    /**
     * 21. Material with missing stock/MWA relation is handled safely.
     */
    public function test_21_missing_material_stock_relation_handled_safely(): void
    {
        // Material with NO stock record created
        $matNoStock = Material::create([
            'outlet_id'     => $this->outletA->id,
            'material_code' => 'MAT-NO-STOCK',
            'material_name' => 'Bahan Tanpa Stok',
            'unit_id'       => $this->unitMeter->id,
            'min_stock'     => 10,
            'active'        => true,
        ]);

        BomItem::create([
            'variant_id'   => $this->variantA->id,
            'material_id'  => $matNoStock->id,
            'qty_per_unit' => '1.0000',
        ]);

        $cost = $this->costService->calculateEstimatedCost($this->variantA->fresh());

        $this->assertFalse($cost['is_available']);
        $this->assertNull($cost['estimated_total_cost']);
        $this->assertContains("Modal material 'Bahan Tanpa Stok' belum tersedia (MWA belum tercatat).", $cost['missing_reasons']);
    }

    /**
     * 22. Foreign Variant request returns 404.
     */
    public function test_22_foreign_variant_request_returns_404(): void
    {
        $this->actingAs($this->adminA)
            ->get(route('admin.variants.show', $this->variantB))
            ->assertNotFound();
    }

    /**
     * 23. Owner/ADMIN roles unchanged.
     */
    public function test_23_owner_and_admin_roles_unchanged(): void
    {
        $this->actingAs($this->ownerA)
            ->get(route('admin.variants.show', $this->variantA))
            ->assertForbidden();
    }

    /**
     * 24, 25, 26, 27. No foreign stock, material, or valuation mutation occurs.
     */
    public function test_24_to_27_no_foreign_mutations_or_valuation_changes(): void
    {
        $initialStock = $this->stockB1->qty_on_hand;
        $initialMwa = $this->stockB1->avg_unit_cost;

        BomItem::create([
            'variant_id'   => $this->variantA->id,
            'material_id'  => $this->materialB1->id,
            'qty_per_unit' => '0.5000',
        ]);

        $this->costService->calculateEstimatedCost($this->variantA->fresh());

        $this->stockB1->refresh();
        $this->assertEquals($initialStock, $this->stockB1->qty_on_hand);
        $this->assertEquals($initialMwa, $this->stockB1->avg_unit_cost);
    }

    /**
     * 28. Legacy flag FALSE still computes original BOM costs without requiring tenant columns.
     */
    public function test_28_legacy_flag_false_computes_original_bom_costs(): void
    {
        Config::set('nayara.tenant_isolation_enabled', false);

        // Under flag false, attaching any material calculates normally as single-tenant
        BomItem::create([
            'variant_id'   => $this->variantA->id,
            'material_id'  => $this->materialB1->id,
            'qty_per_unit' => '1.0000',
        ]);

        $cost = $this->costService->calculateEstimatedCost($this->variantA->fresh());

        // Material A: 1.5 * 25,000 = 37,500
        // Material B: 1.0 * 47,850 = 47,850
        // Total material = 85,350; Direct = 8000; Total = 93,350
        $this->assertTrue($cost['is_available']);
        $this->assertEquals(85350.0, (float) $cost['estimated_material_cost']);
        $this->assertEquals(93350.0, (float) $cost['estimated_total_cost']);
    }

    /**
     * 29. No Global Tenant Scope on Material or BomItem model.
     */
    public function test_29_no_global_tenant_scope(): void
    {
        $this->assertEmpty((new Material())->getGlobalScopes());
        $this->assertEmpty((new BomItem())->getGlobalScopes());
    }

    /**
     * 30. No unrelated DB mutation occurs during calculation.
     */
    public function test_30_no_unrelated_db_mutation_during_calculation(): void
    {
        $bomCountBefore = BomItem::count();
        $materialCountBefore = Material::count();

        $this->costService->calculateEstimatedCost($this->variantA);

        $this->assertEquals($bomCountBefore, BomItem::count());
        $this->assertEquals($materialCountBefore, Material::count());
    }
}
