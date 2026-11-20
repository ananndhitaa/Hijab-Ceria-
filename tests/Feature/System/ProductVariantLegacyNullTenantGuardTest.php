<?php

namespace Tests\Feature\System;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Inventory\MaterialStock;
use App\Models\Master\BomItem;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Master\Unit;
use App\Models\System\Outlet;
use App\Models\System\User;
use App\Services\Finance\VariantCostCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * PHASE 8.7B — PRODUCT VARIANT LEGACY NULL TENANT GUARD & CROSS-TENANT INTEGRITY
 *
 * Verifies that:
 * 1. Legacy NULL variants, foreign variants, and malformed parent-child relationships fail closed (HTTP 404).
 * 2. VariantCostCalculationService never leaks foreign materials or MWA for unassigned or invalid variants.
 * 3. Registered write endpoints prevent mutations on foreign or legacy NULL variants/products.
 * 4. Product listings and details do not expose foreign variants under isolation TRUE.
 * 5. Legacy mode (flag FALSE) remains 100% compatible with legacy single-tenant schema.
 *
 * SQLite :memory: ONLY.
 */
class ProductVariantLegacyNullTenantGuardTest extends TestCase
{
    use RefreshDatabase;

    private Outlet $outletA;
    private Outlet $outletB;
    private Outlet $outletInactive;

    private User $adminA;
    private User $adminB;
    private User $adminInactive;
    private User $adminInactiveOutlet;
    private User $adminUnbound;

    private Unit $unitMeter;

    private Material $materialA;
    private MaterialStock $stockA;

    private Material $materialB;
    private MaterialStock $stockB;

    private Product $productA;
    private Product $productB;
    private Product $legacyNullProduct;

    private ProductVariant $variantA;
    private ProductVariant $variantB;
    private ProductVariant $legacyNullVariant;
    private ProductVariant $malformedVariant;

    private BomItem $bomItemA;
    private BomItem $bomItemB;

    private VariantCostCalculationService $costService;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('nayara.tenant_isolation_enabled', true);

        // Outlets
        $this->outletA = Outlet::create([
            'outlet_name' => 'Konveksi Hijab A',
            'outlet_code' => 'OUT-A',
            'status'      => 'ACTIVE',
        ]);

        $this->outletB = Outlet::create([
            'outlet_name' => 'Konveksi Hijab B',
            'outlet_code' => 'OUT-B',
            'status'      => 'ACTIVE',
        ]);

        $this->outletInactive = Outlet::create([
            'outlet_name' => 'Konveksi Nonaktif',
            'outlet_code' => 'OUT-INACTIVE',
            'status'      => 'INACTIVE',
        ]);

        // Users
        $this->adminA = User::create([
            'outlet_id' => $this->outletA->id,
            'name'      => 'Admin A',
            'email'     => 'admin.a@example.com',
            'password'  => Hash::make('password'),
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::ACTIVE,
        ]);

        $this->adminB = User::create([
            'outlet_id' => $this->outletB->id,
            'name'      => 'Admin B',
            'email'     => 'admin.b@example.com',
            'password'  => Hash::make('password'),
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::ACTIVE,
        ]);

        $this->adminInactive = User::create([
            'outlet_id' => $this->outletA->id,
            'name'      => 'Admin Inactive',
            'email'     => 'admin.inactive@example.com',
            'password'  => Hash::make('password'),
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::INACTIVE,
        ]);

        $this->adminInactiveOutlet = User::create([
            'outlet_id' => $this->outletInactive->id,
            'name'      => 'Admin Inactive Outlet',
            'email'     => 'admin.inact.out@example.com',
            'password'  => Hash::make('password'),
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::ACTIVE,
        ]);

        $this->adminUnbound = User::create([
            'outlet_id' => null,
            'name'      => 'Admin Unbound',
            'email'     => 'admin.unbound@example.com',
            'password'  => Hash::make('password'),
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::ACTIVE,
        ]);

        // Units
        $this->unitMeter = Unit::create([
            'unit_name' => 'Meter',
            'symbol'    => 'm',
        ]);

        // Materials & Stocks
        $this->materialA = Material::create([
            'outlet_id'     => $this->outletA->id,
            'material_code' => 'MAT-A-VOAL',
            'material_name' => 'Voal Premium A',
            'unit_id'       => $this->unitMeter->id,
            'min_stock'     => 10,
            'active'        => true,
        ]);
        $this->stockA = MaterialStock::create([
            'material_id'   => $this->materialA->id,
            'qty_on_hand'   => '100.0000',
            'avg_unit_cost' => '25000.0000', // 25,000 / m
        ]);

        $this->materialB = Material::create([
            'outlet_id'     => $this->outletB->id,
            'material_code' => 'MAT-B-SILK',
            'material_name' => 'Silk Rahasia B',
            'unit_id'       => $this->unitMeter->id,
            'min_stock'     => 5,
            'active'        => true,
        ]);
        $this->stockB = MaterialStock::create([
            'material_id'   => $this->materialB->id,
            'qty_on_hand'   => '50.0000',
            'avg_unit_cost' => '47850.0000', // 47,850 / m
        ]);

        // Products
        $this->productA = Product::create([
            'outlet_id'    => $this->outletA->id,
            'product_name' => 'Pashmina Curve A',
            'code'         => 'PCA',
            'description'  => 'Pashmina Curve Konveksi A',
            'active'       => true,
        ]);

        $this->productB = Product::create([
            'outlet_id'    => $this->outletB->id,
            'product_name' => 'Segiempat Voal B',
            'code'         => 'SVB',
            'description'  => 'Segiempat Voal Konveksi B',
            'active'       => true,
        ]);

        $this->legacyNullProduct = Product::create([
            'outlet_id'    => null,
            'product_name' => 'Legacy Unassigned Product',
            'code'         => 'LUP',
            'description'  => 'Produk Lama Tanpa Outlet',
            'active'       => true,
        ]);

        // Variants
        $this->variantA = ProductVariant::create([
            'outlet_id'                            => $this->outletA->id,
            'product_id'                           => $this->productA->id,
            'variant_name'                         => 'Pashmina Curve Hitam',
            'sku'                                  => 'PCA-BLK',
            'base_selling_price'                   => '50000.00',
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => '0.00',
            'direct_labor_cost_per_unit'           => '5000.00',
            'packaging_process_cost_per_good_unit' => '2000.00',
            'other_direct_cost_per_unit'           => '1000.00',
            'active'                               => true,
        ]);

        $this->variantB = ProductVariant::create([
            'outlet_id'                            => $this->outletB->id,
            'product_id'                           => $this->productB->id,
            'variant_name'                         => 'Segiempat Voal Navy',
            'sku'                                  => 'SVB-NVY',
            'base_selling_price'                   => '100000.00',
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => '0.00',
            'direct_labor_cost_per_unit'           => '5000.00',
            'packaging_process_cost_per_good_unit' => '2000.00',
            'other_direct_cost_per_unit'           => '1000.00',
            'active'                               => true,
        ]);

        $this->legacyNullVariant = ProductVariant::create([
            'outlet_id'                            => null,
            'product_id'                           => $this->legacyNullProduct->id,
            'variant_name'                         => 'Legacy NULL Variant',
            'sku'                                  => 'LNV-001',
            'base_selling_price'                   => '50000.00',
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => '0.00',
            'direct_labor_cost_per_unit'           => '5000.00',
            'packaging_process_cost_per_good_unit' => '2000.00',
            'other_direct_cost_per_unit'           => '1000.00',
            'active'                               => true,
        ]);

        // Malformed variant: Variant belongs to Outlet A, but product belongs to Outlet B
        $this->malformedVariant = ProductVariant::create([
            'outlet_id'                            => $this->outletA->id,
            'product_id'                           => $this->productB->id,
            'variant_name'                         => 'Malformed Cross Variant',
            'sku'                                  => 'MAL-001',
            'base_selling_price'                   => '60000.00',
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => '0.00',
            'direct_labor_cost_per_unit'           => '5000.00',
            'packaging_process_cost_per_good_unit' => '2000.00',
            'other_direct_cost_per_unit'           => '1000.00',
            'active'                               => true,
        ]);

        // BOM Items
        $this->bomItemA = BomItem::create([
            'variant_id'   => $this->variantA->id,
            'material_id'  => $this->materialA->id,
            'qty_per_unit' => '1.0000',
        ]);

        $this->bomItemB = BomItem::create([
            'variant_id'   => $this->variantB->id,
            'material_id'  => $this->materialB->id,
            'qty_per_unit' => '1.0000',
        ]);

        $this->costService = app(VariantCostCalculationService::class);
    }

    /**
     * 01. ADMIN A can show Variant A.
     */
    public function test_01_admin_a_can_show_variant_a(): void
    {
        $response = $this->actingAs($this->adminA)->get(route('admin.variants.show', $this->variantA));
        $response->assertOk();
        $response->assertSee('Pashmina Curve Hitam');
        $response->assertSee('PCA-BLK');
    }

    /**
     * 02. ADMIN B can show Variant B.
     */
    public function test_02_admin_b_can_show_variant_b(): void
    {
        $response = $this->actingAs($this->adminB)->get(route('admin.variants.show', $this->variantB));
        $response->assertOk();
        $response->assertSee('Segiempat Voal Navy');
        $response->assertSee('SVB-NVY');
    }

    /**
     * 03. ADMIN A cannot show Variant B.
     */
    public function test_03_admin_a_cannot_show_variant_b(): void
    {
        $response = $this->actingAs($this->adminA)->get(route('admin.variants.show', $this->variantB));
        $response->assertNotFound();
    }

    /**
     * 04. ADMIN B cannot show Variant A.
     */
    public function test_04_admin_b_cannot_show_variant_a(): void
    {
        $response = $this->actingAs($this->adminB)->get(route('admin.variants.show', $this->variantA));
        $response->assertNotFound();
    }

    /**
     * 05. Legacy NULL Variant cannot be shown under isolation TRUE.
     */
    public function test_05_legacy_null_variant_cannot_be_shown_under_isolation_true(): void
    {
        $responseA = $this->actingAs($this->adminA)->get(route('admin.variants.show', $this->legacyNullVariant));
        $responseA->assertNotFound();

        $responseB = $this->actingAs($this->adminB)->get(route('admin.variants.show', $this->legacyNullVariant));
        $responseB->assertNotFound();
    }

    /**
     * 06. Legacy NULL Variant cannot be edited under isolation TRUE.
     */
    public function test_06_legacy_null_variant_cannot_be_edited_under_isolation_true(): void
    {
        $responseA = $this->actingAs($this->adminA)->get(route('admin.variants.edit', $this->legacyNullVariant));
        $responseA->assertNotFound();

        $responseB = $this->actingAs($this->adminB)->get(route('admin.variants.edit', $this->legacyNullVariant));
        $responseB->assertNotFound();
    }

    /**
     * 07. Foreign parent Product relationship fails closed.
     */
    public function test_07_foreign_parent_product_relationship_fails_closed(): void
    {
        // Malformed variant belongs to Outlet A, but product belongs to Outlet B
        $responseShow = $this->actingAs($this->adminA)->get(route('admin.variants.show', $this->malformedVariant));
        $responseShow->assertNotFound();

        $responseEdit = $this->actingAs($this->adminA)->get(route('admin.variants.edit', $this->malformedVariant));
        $responseEdit->assertNotFound();

        $responseUpdate = $this->actingAs($this->adminA)->put(route('admin.variants.update', $this->malformedVariant), [
            'variant_name'      => 'Malicious Update',
            'requires_printing' => 0,
        ]);
        $responseUpdate->assertNotFound();
    }

    /**
     * 08. Spoofed outlet_id rejected or ignored safely.
     */
    public function test_08_spoofed_outlet_id_rejected_or_ignored_safely(): void
    {
        $response = $this->actingAs($this->adminA)->put(route('admin.variants.update', $this->variantA), [
            'outlet_id'         => $this->outletB->id,
            'variant_name'      => 'Pashmina Curve Hitam Reborn',
            'requires_printing' => 0,
        ]);

        $response->assertRedirect(route('admin.variants.show', $this->variantA));

        $this->variantA->refresh();
        $this->assertEquals('Pashmina Curve Hitam Reborn', $this->variantA->variant_name);
        // outlet_id must remain unchanged (Outlet A)
        $this->assertEquals($this->outletA->id, $this->variantA->outlet_id);
    }

    /**
     * 09. X-Outlet-ID spoofing ignored.
     */
    public function test_09_x_outlet_id_spoofing_ignored(): void
    {
        $response = $this->actingAs($this->adminA)
            ->withHeader('X-Outlet-ID', (string) $this->outletB->id)
            ->get(route('admin.variants.show', $this->variantB));

        $response->assertNotFound();

        $responseNull = $this->actingAs($this->adminA)
            ->withHeader('X-Outlet-ID', (string) $this->outletA->id)
            ->get(route('admin.variants.show', $this->legacyNullVariant));

        $responseNull->assertNotFound();
    }

    /**
     * 10. Unbound user fails closed.
     */
    public function test_10_unbound_user_fails_closed(): void
    {
        $response = $this->actingAs($this->adminUnbound)->get(route('admin.variants.show', $this->variantA));
        $response->assertForbidden();
    }

    /**
     * 11. Inactive user fails closed.
     */
    public function test_11_inactive_user_fails_closed(): void
    {
        $response = $this->actingAs($this->adminInactive)->get(route('admin.variants.show', $this->variantA));
        $response->assertRedirect(route('login'));
    }

    /**
     * 12. Inactive outlet fails closed.
     */
    public function test_12_inactive_outlet_fails_closed(): void
    {
        $response = $this->actingAs($this->adminInactiveOutlet)->get(route('admin.variants.show', $this->variantA));
        $response->assertForbidden();
    }

    /**
     * 13. Legacy NULL Variant cannot expose BOM material names.
     */
    public function test_13_legacy_null_variant_cannot_expose_bom_material_names(): void
    {
        BomItem::create([
            'variant_id'   => $this->legacyNullVariant->id,
            'material_id'  => $this->materialB->id,
            'qty_per_unit' => '1.5000',
        ]);

        $cost = $this->costService->calculateEstimatedCost($this->legacyNullVariant);

        $this->assertFalse($cost['is_available']);
        $this->assertNotEmpty($cost['breakdown']['materials']);

        $item = $cost['breakdown']['materials'][0];
        $this->assertEquals('[Integritas Tenant Tidak Valid: Material Tidak Sah]', $item['material_name']);
        $this->assertNull($item['material_id']);
    }

    /**
     * 14. Legacy NULL Variant cannot expose confidential MWA.
     */
    public function test_14_legacy_null_variant_cannot_expose_confidential_mwa(): void
    {
        BomItem::create([
            'variant_id'   => $this->legacyNullVariant->id,
            'material_id'  => $this->materialB->id,
            'qty_per_unit' => '2.0000',
        ]);

        $cost = $this->costService->calculateEstimatedCost($this->legacyNullVariant);

        $this->assertNull($cost['estimated_material_cost']);
        $this->assertNull($cost['estimated_total_cost']);

        $item = $cost['breakdown']['materials'][0];
        $this->assertNull($item['avg_unit_cost']);
        $this->assertNull($item['subtotal']);
        $this->assertFalse($item['has_valid_mwa']);
    }

    /**
     * 15. Cross-tenant Product/Variant relationship does not leak HPP.
     */
    public function test_15_cross_tenant_product_variant_relationship_does_not_leak_hpp(): void
    {
        BomItem::create([
            'variant_id'   => $this->malformedVariant->id,
            'material_id'  => $this->materialA->id,
            'qty_per_unit' => '1.0000',
        ]);

        $cost = $this->costService->calculateEstimatedCost($this->malformedVariant);

        $this->assertFalse($cost['is_available']);
        $this->assertNull($cost['estimated_total_cost']);
        $this->assertContains(
            'Integritas resep BOM tidak valid: varian belum terikat pada outlet yang sah atau produk induk tidak valid.',
            $cost['missing_reasons']
        );
    }

    /**
     * 16. Valid same-tenant estimated HPP remains correct.
     */
    public function test_16_valid_same_tenant_estimated_hpp_remains_correct(): void
    {
        $cost = $this->costService->calculateEstimatedCost($this->variantA);

        $this->assertTrue($cost['is_available']);
        $this->assertEmpty($cost['missing_reasons']);

        // Material A = 1.0 * 25,000 = 25,000
        $this->assertEquals(25000.0, (float) $cost['estimated_material_cost']);
        // Direct costs: labor 5000 + packaging 2000 + other 1000 = 8000
        $this->assertEquals(8000.0, (float) $cost['estimated_direct_cost']);
        // Total = 25000 + 8000 = 33000
        $this->assertEquals(33000.0, (float) $cost['estimated_total_cost']);
    }

    /**
     * 17. Valid same-tenant margin remains correct.
     */
    public function test_17_valid_same_tenant_margin_remains_correct(): void
    {
        // Selling price 50,000 vs reference cost 33,000 -> margin = 17,000 (34.00%)
        $margin = $this->costService->evaluatePriceMargin($this->variantA, $this->variantA->base_selling_price);

        $this->assertTrue($margin['is_valid']);
        $this->assertFalse($margin['is_below_cost']);
        $this->assertEquals('VALID', $margin['price_status']);
        $this->assertEquals('33000.00', $margin['reference_cost']);
        $this->assertEquals('50000.00', $margin['selling_price']);
        $this->assertEquals('17000.00', $margin['margin']);
    }

    /**
     * 18. Corrupted cross-tenant BOM remains classified invalid.
     */
    public function test_18_corrupted_cross_tenant_bom_remains_classified_invalid(): void
    {
        // Add foreign Material B to Variant A
        BomItem::create([
            'variant_id'   => $this->variantA->id,
            'material_id'  => $this->materialB->id,
            'qty_per_unit' => '0.5000',
        ]);

        $cost = $this->costService->calculateEstimatedCost($this->variantA->fresh());

        $this->assertFalse($cost['is_available']);
        $this->assertNull($cost['estimated_total_cost']);
        $this->assertContains(
            'Integritas resep BOM tidak valid: terdapat material yang tidak sah atau berbeda outlet.',
            $cost['missing_reasons']
        );
    }

    /**
     * 19. Product listing/detail does not expose foreign variants under isolation TRUE.
     */
    public function test_19_product_listing_and_detail_do_not_expose_foreign_variants_under_isolation_true(): void
    {
        // Attach Variant B to Product A directly in DB to simulate malformed data
        $foreignVariantOnProductA = ProductVariant::create([
            'outlet_id'                            => $this->outletB->id,
            'product_id'                           => $this->productA->id,
            'variant_name'                         => 'Injected Foreign Variant',
            'sku'                                  => 'IFV-001',
            'base_selling_price'                   => '75000.00',
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => '0.00',
            'direct_labor_cost_per_unit'           => '5000.00',
            'packaging_process_cost_per_good_unit' => '2000.00',
            'other_direct_cost_per_unit'           => '1000.00',
            'active'                               => true,
        ]);

        // Product show for Admin A should only load variants matching Outlet A
        $responseShow = $this->actingAs($this->adminA)->get(route('admin.products.show', $this->productA));
        $responseShow->assertOk();

        $loadedProduct = $responseShow->viewData('product');
        $this->assertCount(1, $loadedProduct->variants);
        $this->assertEquals($this->variantA->id, $loadedProduct->variants->first()->id);

        // Product index for Admin A should only count variants matching Outlet A
        $responseIndex = $this->actingAs($this->adminA)->get(route('admin.products.index'));
        $responseIndex->assertOk();
        $productsInIndex = $responseIndex->viewData('products');
        $prodAFromIndex = $productsInIndex->firstWhere('id', $this->productA->id);
        $this->assertEquals(1, $prodAFromIndex->variants_count);

        // Foreign product access fails closed
        $this->actingAs($this->adminA)->get(route('admin.products.show', $this->productB))->assertNotFound();
        $this->actingAs($this->adminA)->get(route('admin.products.show', $this->legacyNullProduct))->assertNotFound();
    }

    /**
     * 20. Registered write endpoints cannot mutate foreign or NULL-owned variants.
     */
    public function test_20_registered_write_endpoints_cannot_mutate_foreign_or_null_owned_variants(): void
    {
        // Try to update foreign Variant B
        $responseB = $this->actingAs($this->adminA)->put(route('admin.variants.update', $this->variantB), [
            'variant_name'      => 'Hacked Variant B',
            'requires_printing' => 0,
        ]);
        $responseB->assertNotFound();
        $this->assertEquals('Segiempat Voal Navy', $this->variantB->fresh()->variant_name);

        // Try to update legacy NULL Variant
        $responseNull = $this->actingAs($this->adminA)->put(route('admin.variants.update', $this->legacyNullVariant), [
            'variant_name'      => 'Hacked NULL Variant',
            'requires_printing' => 0,
        ]);
        $responseNull->assertNotFound();
        $this->assertEquals('Legacy NULL Variant', $this->legacyNullVariant->fresh()->variant_name);

        // Try to create variant under foreign Product B
        $responseStoreForeign = $this->actingAs($this->adminA)->post(route('admin.products.variants.store', $this->productB), [
            'variant_name'      => 'New Variant Foreign',
            'requires_printing' => 0,
        ]);
        $responseStoreForeign->assertNotFound();

        // Try to create variant under legacy NULL Product
        $responseStoreNull = $this->actingAs($this->adminA)->post(route('admin.products.variants.store', $this->legacyNullProduct), [
            'variant_name'      => 'New Variant NULL',
            'requires_printing' => 0,
        ]);
        $responseStoreNull->assertNotFound();
    }

    /**
     * 21. Flag FALSE remains compatible with legacy unmigrated schema.
     */
    public function test_21_flag_false_remains_compatible_with_legacy_unmigrated_schema(): void
    {
        Config::set('nayara.tenant_isolation_enabled', false);

        // Under flag false, all variants can be shown and edited regardless of outlet_id
        $this->actingAs($this->adminA)->get(route('admin.variants.show', $this->variantA))->assertOk();
        $this->actingAs($this->adminA)->get(route('admin.variants.show', $this->variantB))->assertOk();
        $this->actingAs($this->adminA)->get(route('admin.variants.show', $this->legacyNullVariant))->assertOk();

        $costNull = $this->costService->calculateEstimatedCost($this->legacyNullVariant);
        // Under flag false, no tenant corruption error is generated
        $this->assertNotContains(
            'Integritas resep BOM tidak valid: varian belum terikat pada outlet yang sah atau produk induk tidak valid.',
            $costNull['missing_reasons']
        );
    }

    /**
     * 22. No Global Tenant Scope.
     */
    public function test_22_no_global_tenant_scope(): void
    {
        $this->assertEmpty((new ProductVariant())->getGlobalScopes());
        $this->assertEmpty((new Product())->getGlobalScopes());
    }

    /**
     * 23. No automatic legacy backfill.
     */
    public function test_23_no_automatic_legacy_backfill(): void
    {
        // Re-read directly from DB
        $nullVariantDb = ProductVariant::find($this->legacyNullVariant->id);
        $nullProductDb = Product::find($this->legacyNullProduct->id);

        $this->assertNull($nullVariantDb->outlet_id, 'Legacy NULL variant must not be automatically backfilled');
        $this->assertNull($nullProductDb->outlet_id, 'Legacy NULL product must not be automatically backfilled');
    }

    /**
     * 24. No unrelated mutation.
     */
    public function test_24_no_unrelated_mutation(): void
    {
        $variantCountBefore = ProductVariant::count();
        $productCountBefore = Product::count();
        $materialCountBefore = Material::count();
        $outletCountBefore = Outlet::count();

        // Perform read operations
        $this->actingAs($this->adminA)->get(route('admin.variants.show', $this->variantA));
        $this->actingAs($this->adminA)->get(route('admin.products.show', $this->productA));
        $this->costService->calculateEstimatedCost($this->variantA);

        $this->assertEquals($variantCountBefore, ProductVariant::count());
        $this->assertEquals($productCountBefore, Product::count());
        $this->assertEquals($materialCountBefore, Material::count());
        $this->assertEquals($outletCountBefore, Outlet::count());
    }

    /**
     * 25. Legacy FALSE variant creation succeeds with unmigrated schema shape (omitting outlet_id column).
     */
    public function test_25_legacy_false_variant_creation_succeeds_without_tenant_column(): void
    {
        Config::set('nayara.tenant_isolation_enabled', false);

        // Simulate an unmigrated legacy database where product_variants table does NOT have outlet_id
        if (\Illuminate\Support\Facades\Schema::hasColumn('product_variants', 'outlet_id')) {
            \Illuminate\Support\Facades\Schema::table('product_variants', function ($table) {
                if (\Illuminate\Support\Facades\Schema::hasColumn('product_variants', 'tenant_sku_scope')) {
                    $table->dropUnique('product_variants_tenant_sku_unique');
                    $table->dropColumn('tenant_sku_scope');
                }
                $table->dropForeign(['outlet_id']);
                $table->dropColumn('outlet_id');
            });
        }

        $this->assertFalse(
            \Illuminate\Support\Facades\Schema::hasColumn('product_variants', 'outlet_id'),
            'outlet_id column must be dropped to simulate unmigrated legacy schema'
        );

        $response = $this->actingAs($this->adminA)->post(route('admin.products.variants.store', $this->productA), [
            'variant_name'       => 'Pashmina Legacy No Tenant Column',
            'requires_printing'  => 0,
            'base_selling_price' => '45000.00',
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('product_variants', [
            'product_id'   => $this->productA->id,
            'variant_name' => 'Pashmina Legacy No Tenant Column',
        ]);
    }

    /**
     * 26. Missing, unassigned, or foreign parent Product relation cannot produce valid HPP and leaks zero foreign MWA.
     */
    public function test_26_missing_parent_product_relation_cannot_produce_valid_hpp(): void
    {
        // 1. Parent Product relation explicitly missing (null)
        $orphanedVariant = clone $this->variantA;
        $orphanedVariant->setRelation('product', null);

        $costOrphaned = $this->costService->calculateEstimatedCost($orphanedVariant);

        $this->assertFalse($costOrphaned['is_available']);
        $this->assertNull($costOrphaned['estimated_material_cost']);
        $this->assertNull($costOrphaned['estimated_total_cost']);
        $this->assertContains(
            'Integritas resep BOM tidak valid: varian belum terikat pada outlet yang sah atau produk induk tidak valid.',
            $costOrphaned['missing_reasons']
        );

        // Materials must be masked; MWA and subtotal must be null
        $itemsOrphaned = $costOrphaned['breakdown']['materials'];
        $this->assertCount(1, $itemsOrphaned);
        $this->assertEquals('[Integritas Tenant Tidak Valid: Material Tidak Sah]', $itemsOrphaned[0]['material_name']);
        $this->assertNull($itemsOrphaned[0]['avg_unit_cost']);
        $this->assertNull($itemsOrphaned[0]['subtotal']);
        $this->assertFalse($itemsOrphaned[0]['has_valid_mwa']);

        $marginOrphaned = $this->costService->evaluatePriceMargin($orphanedVariant, '75000.00');
        $this->assertEquals('COST_UNAVAILABLE', $marginOrphaned['status']);
        $this->assertFalse($marginOrphaned['is_valid']);
        $this->assertFalse($marginOrphaned['has_reference']);

        // 2. Parent Product has NULL outlet_id (legacy unassigned product)
        $nullParentVariant = clone $this->variantA;
        $nullParentVariant->setRelation('product', $this->legacyNullProduct);

        $costNullParent = $this->costService->calculateEstimatedCost($nullParentVariant);
        $this->assertFalse($costNullParent['is_available']);
        $this->assertNull($costNullParent['estimated_total_cost']);
        $this->assertEquals(
            '[Integritas Tenant Tidak Valid: Material Tidak Sah]',
            $costNullParent['breakdown']['materials'][0]['material_name']
        );

        // 3. Parent Product belongs to foreign outlet B
        $foreignParentVariant = clone $this->variantA;
        $foreignParentVariant->setRelation('product', $this->productB);

        $costForeignParent = $this->costService->calculateEstimatedCost($foreignParentVariant);
        $this->assertFalse($costForeignParent['is_available']);
        $this->assertNull($costForeignParent['estimated_total_cost']);
        $this->assertEquals(
            '[Integritas Tenant Tidak Valid: Material Tidak Sah]',
            $costForeignParent['breakdown']['materials'][0]['material_name']
        );
    }
}
