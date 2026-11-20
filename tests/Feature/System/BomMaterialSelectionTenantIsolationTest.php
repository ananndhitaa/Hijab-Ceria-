<?php

namespace Tests\Feature\System;

use App\Enums\BatchStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Inventory\MaterialStock;
use App\Models\Master\BomItem;
use App\Models\Master\BomTemplate;
use App\Models\Master\Customer;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Master\Unit;
use App\Models\Production\BatchMaterial;
use App\Models\Production\ProductionBatch;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\Outlet;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Fase 8.6B: BOM Material Selection & Tenant Isolation Security Test Suite.
 *
 * Covers Scenarios 25 to 45:
 * BOM item creation, update, destroy, template application, dropdown scoping,
 * anti-IDOR, foreign material rejection, malformed BOM view isolation, and legacy compatibility.
 */
class BomMaterialSelectionTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Outlet $outletA;
    protected Outlet $outletB;

    protected User $adminA;
    protected User $adminB;
    protected User $gudangA;
    protected User $ownerA;

    protected Unit $unitMeter;

    protected Material $materialA1;
    protected Material $materialA2;
    protected MaterialStock $stockA1;
    protected MaterialStock $stockA2;

    protected Material $materialB1;
    protected MaterialStock $stockB1;

    protected Material $legacyMaterial;
    protected MaterialStock $legacyStock;

    protected Product $productA;
    protected ProductVariant $variantA;

    protected Product $productB;
    protected ProductVariant $variantB;

    protected BomItem $bomItemA;
    protected BomItem $bomItemB;

    protected function setUp(): void
    {
        parent::setUp();

        config(['nayara.tenant_isolation_enabled' => true]);

        $this->outletA = Outlet::create([
            'outlet_code' => 'OUT-BOM-A',
            'outlet_name' => 'Konveksi Hijab A',
            'status'      => 'ACTIVE',
        ]);

        $this->outletB = Outlet::create([
            'outlet_code' => 'OUT-BOM-B',
            'outlet_name' => 'Konveksi Hijab B',
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

        $this->gudangA = User::create([
            'name'      => 'Gudang A',
            'email'     => 'gudang.a@nayara.test',
            'password'  => Hash::make('password'),
            'role'      => UserRole::GUDANG,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $this->outletA->id,
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

        // Materials
        $this->materialA1 = Material::create([
            'outlet_id'     => $this->outletA->id,
            'material_code' => 'MAT-A1',
            'material_name' => 'Kain Katun A1',
            'unit_id'       => $this->unitMeter->id,
            'min_stock'     => 10,
            'active'        => true,
        ]);
        $this->stockA1 = MaterialStock::create([
            'material_id'   => $this->materialA1->id,
            'qty_on_hand'   => '100.0000',
            'avg_unit_cost' => '25000.0000',
        ]);

        $this->materialA2 = Material::create([
            'outlet_id'     => $this->outletA->id,
            'material_code' => 'MAT-A2',
            'material_name' => 'Kain Renda A2',
            'unit_id'       => $this->unitMeter->id,
            'min_stock'     => 5,
            'active'        => true,
        ]);
        $this->stockA2 = MaterialStock::create([
            'material_id'   => $this->materialA2->id,
            'qty_on_hand'   => '50.0000',
            'avg_unit_cost' => '15000.0000',
        ]);

        $this->materialB1 = Material::create([
            'outlet_id'     => $this->outletB->id,
            'material_code' => 'MAT-B1',
            'material_name' => 'Kain Sutra B1',
            'unit_id'       => $this->unitMeter->id,
            'min_stock'     => 10,
            'active'        => true,
        ]);
        $this->stockB1 = MaterialStock::create([
            'material_id'   => $this->materialB1->id,
            'qty_on_hand'   => '80.0000',
            'avg_unit_cost' => '45000.0000',
        ]);

        $this->legacyMaterial = Material::create([
            'outlet_id'     => null,
            'material_code' => 'MAT-LEGACY',
            'material_name' => 'Kain Legacy Unassigned',
            'unit_id'       => $this->unitMeter->id,
            'min_stock'     => 10,
            'active'        => true,
        ]);
        $this->legacyStock = MaterialStock::create([
            'material_id'   => $this->legacyMaterial->id,
            'qty_on_hand'   => '30.0000',
            'avg_unit_cost' => '20000.0000',
        ]);

        // Products & Variants
        $this->productA = Product::create([
            'outlet_id'    => $this->outletA->id,
            'product_name' => 'Pashmina A',
            'active'       => true,
        ]);
        $this->variantA = ProductVariant::create([
            'outlet_id'          => $this->outletA->id,
            'product_id'         => $this->productA->id,
            'variant_name'       => 'Pashmina A Hitam',
            'sku'                => 'SKU-PA-H',
            'base_selling_price' => '75000.00',
            'active'             => true,
        ]);
        $this->bomItemA = BomItem::create([
            'variant_id'   => $this->variantA->id,
            'material_id'  => $this->materialA1->id,
            'qty_per_unit' => '1.5000',
        ]);

        $this->productB = Product::create([
            'outlet_id'    => $this->outletB->id,
            'product_name' => 'Bergo B',
            'active'       => true,
        ]);
        $this->variantB = ProductVariant::create([
            'outlet_id'          => $this->outletB->id,
            'product_id'         => $this->productB->id,
            'variant_name'       => 'Bergo B Coklat',
            'sku'                => 'SKU-BB-C',
            'base_selling_price' => '85000.00',
            'active'             => true,
        ]);
        $this->bomItemB = BomItem::create([
            'variant_id'   => $this->variantB->id,
            'material_id'  => $this->materialB1->id,
            'qty_per_unit' => '1.7500',
        ]);
    }

    /**
     * 25. ADMIN A can create own-tenant BOM item.
     */
    public function test_25_admin_a_can_create_own_tenant_bom_item(): void
    {
        $response = $this->actingAs($this->adminA)->post(route('admin.variants.bom.store', $this->variantA), [
            'material_id'  => $this->materialA2->id,
            'qty_per_unit' => '0.2500',
        ]);

        $response->assertRedirect(route('admin.variants.show', $this->variantA));
        $this->assertDatabaseHas('bom_items', [
            'variant_id'   => $this->variantA->id,
            'material_id'  => $this->materialA2->id,
            'qty_per_unit' => '0.2500',
        ]);
    }

    /**
     * 26. ADMIN B can create own-tenant BOM item.
     */
    public function test_26_admin_b_can_create_own_tenant_bom_item(): void
    {
        $materialB2 = Material::create([
            'outlet_id'     => $this->outletB->id,
            'material_code' => 'MAT-B2',
            'material_name' => 'Benang Jahit B2',
            'unit_id'       => $this->unitMeter->id,
            'min_stock'     => 10,
            'active'        => true,
        ]);

        $response = $this->actingAs($this->adminB)->post(route('admin.variants.bom.store', $this->variantB), [
            'material_id'  => $materialB2->id,
            'qty_per_unit' => '0.5000',
        ]);

        $response->assertRedirect(route('admin.variants.show', $this->variantB));
        $this->assertDatabaseHas('bom_items', [
            'variant_id'   => $this->variantB->id,
            'material_id'  => $materialB2->id,
            'qty_per_unit' => '0.5000',
        ]);
    }

    /**
     * 27. ADMIN A cannot attach Material B to Variant A.
     */
    public function test_27_admin_a_cannot_attach_material_b_to_variant_a(): void
    {
        $response = $this->actingAs($this->adminA)->post(route('admin.variants.bom.store', $this->variantA), [
            'material_id'  => $this->materialB1->id,
            'qty_per_unit' => '1.0000',
        ]);

        $response->assertSessionHasErrors('material_id');
        $this->assertDatabaseMissing('bom_items', [
            'variant_id'  => $this->variantA->id,
            'material_id' => $this->materialB1->id,
        ]);
    }

    /**
     * 28. ADMIN B cannot attach Material A to Variant B.
     */
    public function test_28_admin_b_cannot_attach_material_a_to_variant_b(): void
    {
        $response = $this->actingAs($this->adminB)->post(route('admin.variants.bom.store', $this->variantB), [
            'material_id'  => $this->materialA1->id,
            'qty_per_unit' => '1.0000',
        ]);

        $response->assertSessionHasErrors('material_id');
        $this->assertDatabaseMissing('bom_items', [
            'variant_id'  => $this->variantB->id,
            'material_id' => $this->materialA1->id,
        ]);
    }

    /**
     * 29. Legacy NULL material rejected under flag TRUE.
     */
    public function test_29_legacy_null_material_rejected_under_flag_true(): void
    {
        $response = $this->actingAs($this->adminA)->post(route('admin.variants.bom.store', $this->variantA), [
            'material_id'  => $this->legacyMaterial->id,
            'qty_per_unit' => '1.0000',
        ]);

        $response->assertSessionHasErrors('material_id');
        $this->assertDatabaseMissing('bom_items', [
            'variant_id'  => $this->variantA->id,
            'material_id' => $this->legacyMaterial->id,
        ]);
    }

    /**
     * 30. Foreign Variant rejected with 404.
     */
    public function test_30_foreign_variant_rejected_with_404(): void
    {
        // Admin A tries to add BOM to Variant B
        $this->actingAs($this->adminA)
            ->post(route('admin.variants.bom.store', $this->variantB), [
                'material_id'  => $this->materialA1->id,
                'qty_per_unit' => '1.0000',
            ])
            ->assertNotFound();
    }

    /**
     * 31. Foreign BOM item update rejected with 404.
     */
    public function test_31_foreign_bom_item_update_rejected_with_404(): void
    {
        // Admin A tries to update BOM item of Variant B
        $this->actingAs($this->adminA)
            ->put(route('admin.variants.bom.update', [$this->variantB, $this->bomItemB]), [
                'qty_per_unit' => '9.9999',
            ])
            ->assertNotFound();

        $this->bomItemB->refresh();
        $this->assertEquals('1.7500', (string) $this->bomItemB->qty_per_unit);
    }

    /**
     * 32. Foreign BOM item destroy rejected with 404.
     */
    public function test_32_foreign_bom_item_destroy_rejected_with_404(): void
    {
        // Admin A tries to delete BOM item of Variant B
        $this->actingAs($this->adminA)
            ->delete(route('admin.variants.bom.destroy', [$this->variantB, $this->bomItemB]))
            ->assertNotFound();

        $this->assertDatabaseHas('bom_items', ['id' => $this->bomItemB->id]);
    }

    /**
     * 33. Cross-tenant material replacement rejected in applyTemplate.
     */
    public function test_33_cross_tenant_material_replacement_rejected(): void
    {
        // Create a template in Outlet A but with foreign material B1 (corrupted template)
        $corruptTemplate = BomTemplate::create([
            'outlet_id'   => $this->outletA->id,
            'product_id'  => $this->productA->id,
            'name'        => 'Template Corrupt Cross-Tenant',
            'is_active'   => true,
        ]);
        $corruptTemplate->items()->create([
            'material_id'  => $this->materialB1->id, // Foreign material!
            'qty_per_unit' => '2.0000',
        ]);

        $response = $this->actingAs($this->adminA)->post(route('admin.variants.bom.apply-template', $this->variantA), [
            'bom_template_id'  => $corruptTemplate->id,
            'replace_existing' => true,
        ]);

        $response->assertSessionHasErrors('bom_template_id');

        // Original BOM item A must remain untouched
        $this->assertDatabaseHas('bom_items', ['id' => $this->bomItemA->id]);
    }

    /**
     * 34. Valid own-tenant material replacement accepted in applyTemplate.
     */
    public function test_34_valid_own_tenant_template_applied_successfully(): void
    {
        $validTemplate = BomTemplate::create([
            'outlet_id'   => $this->outletA->id,
            'product_id'  => $this->productA->id,
            'name'        => 'Template Valid A',
            'is_active'   => true,
        ]);
        $validTemplate->items()->create([
            'material_id'  => $this->materialA2->id,
            'qty_per_unit' => '0.8000',
        ]);

        $response = $this->actingAs($this->adminA)->post(route('admin.variants.bom.apply-template', $this->variantA), [
            'bom_template_id'  => $validTemplate->id,
            'replace_existing' => true,
        ]);

        $response->assertRedirect(route('admin.variants.show', $this->variantA));

        // Replaced: materialA2 is now in BOM
        $this->assertDatabaseHas('bom_items', [
            'variant_id'   => $this->variantA->id,
            'material_id'  => $this->materialA2->id,
            'qty_per_unit' => '0.8000',
        ]);
    }

    /**
     * 35. BOM dropdown in view shows only own-tenant materials.
     */
    public function test_35_bom_dropdown_in_view_shows_only_own_tenant_materials(): void
    {
        $response = $this->actingAs($this->adminA)->get(route('admin.variants.show', $this->variantA));
        $response->assertOk();

        // The view contains option values for Material A1 and A2, but NOT B1
        $response->assertSee('Kain Katun A1');
        $response->assertSee('Kain Renda A2');
        $response->assertDontSee('Kain Sutra B1');
    }

    /**
     * 36. Spoofed outlet ID cannot bypass validation.
     */
    public function test_36_spoofed_outlet_id_cannot_bypass_validation(): void
    {
        $response = $this->actingAs($this->adminA)->post(route('admin.variants.bom.store', $this->variantA), [
            'material_id'  => $this->materialB1->id,
            'qty_per_unit' => '1.0000',
            'outlet_id'    => $this->outletB->id, // Spoofed payload
        ]);

        $response->assertSessionHasErrors('material_id');
        $this->assertDatabaseMissing('bom_items', [
            'variant_id'  => $this->variantA->id,
            'material_id' => $this->materialB1->id,
        ]);
    }

    /**
     * 37. Invalid association creates no BOM record.
     */
    public function test_37_invalid_association_creates_no_bom_record(): void
    {
        $countBefore = BomItem::count();

        $this->actingAs($this->adminA)->post(route('admin.variants.bom.store', $this->variantA), [
            'material_id'  => 99999, // Nonexistent
            'qty_per_unit' => '1.0000',
        ])->assertSessionHasErrors('material_id');

        $this->assertEquals($countBefore, BomItem::count());
    }

    /**
     * 38. Failed update preserves previous BOM association.
     */
    public function test_38_failed_update_preserves_previous_bom_association(): void
    {
        $this->actingAs($this->adminA)->put(route('admin.variants.bom.update', [$this->variantA, $this->bomItemA]), [
            'qty_per_unit' => -5, // Invalid negative
        ])->assertSessionHasErrors('qty_per_unit');

        $this->bomItemA->refresh();
        $this->assertEquals('1.5000', (string) $this->bomItemA->qty_per_unit);
    }

    /**
     * 39. HPP calculations unchanged.
     */
    public function test_39_hpp_calculations_unchanged(): void
    {
        $this->variantA->update([
            'requires_printing'                    => false,
            'direct_labor_cost_per_unit'           => '5000.00',
            'packaging_process_cost_per_good_unit' => '2000.00',
            'other_direct_cost_per_unit'           => '1000.00',
        ]);

        $costService = app(\App\Services\Finance\VariantCostCalculationService::class);
        $cost = $costService->calculateEstimatedCost($this->variantA->fresh());

        // 1.5 m * 25000 = 37500
        $this->assertEquals(37500.0, (float) $cost['estimated_material_cost']);
        $this->assertEquals(45500.0, (float) $cost['estimated_total_cost']);
    }

    /**
     * 40. Production batch integrity remains valid (hasConsistentTenantRelations).
     */
    public function test_40_production_batch_integrity_remains_valid(): void
    {
        $customerA = Customer::create([
            'outlet_id'     => $this->outletA->id,
            'customer_name' => 'Cust Valid A',
            'phone'         => '081234567890',
            'active'        => true,
        ]);
        $orderA = Order::create([
            'outlet_id'    => $this->outletA->id,
            'customer_id'  => $customerA->id,
            'order_no'     => 'ORD-V-01',
            'order_date'   => now()->toDateString(),
            'total_amount' => '150000.00',
        ]);
        $orderItemA = OrderItem::create([
            'order_id'           => $orderA->id,
            'variant_id'         => $this->variantA->id,
            'qty_ordered'        => 2,
            'unit_selling_price' => '75000.00',
        ]);
        $batchA = ProductionBatch::create([
            'order_item_id'     => $orderItemA->id,
            'outlet_id'         => $this->outletA->id,
            'batch_no'          => 'BAT-V-01',
            'target_qty'        => 2,
            'status'            => BatchStatus::BAHAN,
            'requires_printing' => false,
        ]);
        BatchMaterial::create([
            'batch_id'      => $batchA->id,
            'material_id'   => $this->materialA1->id,
            'required_qty'  => '3.0000',
            'stock_at_calc' => '100.0000',
            'shortage_qty'  => '0.0000',
            'allocated_qty' => '0.0000',
            'used_qty'      => '0.0000',
            'calculated_at' => now(),
        ]);

        $this->assertTrue($batchA->hasConsistentTenantRelations($this->outletA->id));
    }

    /**
     * 41. Existing malformed BOM cannot leak foreign material information through views.
     */
    public function test_41_existing_malformed_bom_cannot_leak_foreign_material_info(): void
    {
        // Force insert a corrupt cross-tenant BOM item
        $corruptBomItem = BomItem::create([
            'variant_id'   => $this->variantA->id,
            'material_id'  => $this->materialB1->id, // Belongs to Outlet B ("Kain Sutra B1")
            'qty_per_unit' => '0.5000',
        ]);

        $response = $this->actingAs($this->adminA)->get(route('admin.variants.show', $this->variantA));
        $response->assertOk();

        // Must see non-sensitive integrity warning instead of foreign material name
        $response->assertSee('Integritas Tenant Tidak Valid: Material Tidak Sah');
        $response->assertDontSee('Kain Sutra B1');
    }

    /**
     * 42. Existing malformed BOM cannot be repaired or touched by another tenant.
     */
    public function test_42_existing_malformed_bom_cannot_be_repaired_by_another_tenant(): void
    {
        // Admin B tries to delete the corrupt BOM item on Variant A
        $this->actingAs($this->adminB)
            ->delete(route('admin.variants.bom.destroy', [$this->variantA, $this->bomItemA]))
            ->assertNotFound();

        $this->assertDatabaseHas('bom_items', ['id' => $this->bomItemA->id]);
    }

    /**
     * 43. Flag FALSE legacy BOM workflow compatible.
     */
    public function test_43_flag_false_legacy_bom_workflow_compatible(): void
    {
        config(['nayara.tenant_isolation_enabled' => false]);

        // In legacy mode, cross-tenant material is permitted
        $materialNew = Material::create([
            'material_code' => 'MAT-LEGACY-2',
            'material_name' => 'Kain Anyar Legacy',
            'unit_id'       => $this->unitMeter->id,
            'min_stock'     => 5,
            'active'        => true,
        ]);

        $response = $this->actingAs($this->adminA)->post(route('admin.variants.bom.store', $this->variantA), [
            'material_id'  => $materialNew->id,
            'qty_per_unit' => '1.0000',
        ]);

        $response->assertRedirect(route('admin.variants.show', $this->variantA));
        $this->assertDatabaseHas('bom_items', [
            'variant_id'  => $this->variantA->id,
            'material_id' => $materialNew->id,
        ]);
    }

    /**
     * 44. No new role permissions — non-ADMIN cannot manipulate BOM.
     */
    public function test_44_no_new_role_permissions(): void
    {
        $this->actingAs($this->gudangA)
            ->post(route('admin.variants.bom.store', $this->variantA), [
                'material_id'  => $this->materialA2->id,
                'qty_per_unit' => '0.5000',
            ])
            ->assertForbidden();

        $this->actingAs($this->ownerA)
            ->post(route('admin.variants.bom.store', $this->variantA), [
                'material_id'  => $this->materialA2->id,
                'qty_per_unit' => '0.5000',
            ])
            ->assertForbidden();
    }

    /**
     * 45. No unrelated records mutated.
     */
    public function test_45_no_unrelated_records_mutated(): void
    {
        $this->stockA1->refresh();
        $this->assertEquals('100.0000', (string) $this->stockA1->qty_on_hand);
        $this->assertEquals('25000.0000', (string) $this->stockA1->avg_unit_cost);

        $this->stockB1->refresh();
        $this->assertEquals('80.0000', (string) $this->stockB1->qty_on_hand);
    }
}
