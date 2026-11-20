<?php

namespace Tests\Feature\System;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Inventory\MaterialStock;
use App\Models\Inventory\StockMovement;
use App\Models\Master\Material;
use App\Models\Master\Unit;
use App\Models\System\Outlet;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Fase 8.6B: Material Master CRUD Tenant Isolation Security Test Suite.
 *
 * Covers Scenarios 01 to 24:
 * Read, search, write, status toggle, destroy, anti-IDOR, tenant stamping, and legacy compatibility.
 */
class MaterialMasterCrudTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Outlet $outletA;
    protected Outlet $outletB;
    protected Outlet $inactiveOutlet;

    protected User $gudangA;
    protected User $gudangB;
    protected User $adminA;
    protected User $ownerA;
    protected User $inactiveGudangA;
    protected User $unboundGudang;
    protected User $inactiveOutletGudang;

    protected Unit $unitMeter;
    protected Unit $unitRoll;

    protected Material $materialA1;
    protected Material $materialA2;
    protected MaterialStock $stockA1;
    protected MaterialStock $stockA2;

    protected Material $materialB1;
    protected MaterialStock $stockB1;

    protected Material $legacyMaterial;
    protected MaterialStock $legacyStock;

    protected function setUp(): void
    {
        parent::setUp();

        config(['nayara.tenant_isolation_enabled' => true]);

        $this->outletA = Outlet::create([
            'outlet_code' => 'OUT-MAT-A',
            'outlet_name' => 'Konveksi Hijab A',
            'status'      => 'ACTIVE',
        ]);

        $this->outletB = Outlet::create([
            'outlet_code' => 'OUT-MAT-B',
            'outlet_name' => 'Konveksi Hijab B',
            'status'      => 'ACTIVE',
        ]);

        $this->inactiveOutlet = Outlet::create([
            'outlet_code' => 'OUT-MAT-INACT',
            'outlet_name' => 'Konveksi Inactive',
            'status'      => 'INACTIVE',
        ]);

        $this->gudangA = User::create([
            'name'      => 'Gudang A',
            'email'     => 'gudang.a@nayara.test',
            'password'  => Hash::make('password'),
            'role'      => UserRole::GUDANG,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $this->outletA->id,
        ]);

        $this->gudangB = User::create([
            'name'      => 'Gudang B',
            'email'     => 'gudang.b@nayara.test',
            'password'  => Hash::make('password'),
            'role'      => UserRole::GUDANG,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $this->outletB->id,
        ]);

        $this->adminA = User::create([
            'name'      => 'Admin A',
            'email'     => 'admin.a@nayara.test',
            'password'  => Hash::make('password'),
            'role'      => UserRole::ADMIN,
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

        $this->inactiveGudangA = User::create([
            'name'      => 'Inactive Gudang A',
            'email'     => 'inactive.gudang@nayara.test',
            'password'  => Hash::make('password'),
            'role'      => UserRole::GUDANG,
            'status'    => UserStatus::INACTIVE,
            'outlet_id' => $this->outletA->id,
        ]);

        $this->unboundGudang = User::create([
            'name'      => 'Unbound Gudang',
            'email'     => 'unbound.gudang@nayara.test',
            'password'  => Hash::make('password'),
            'role'      => UserRole::GUDANG,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => null,
        ]);

        $this->inactiveOutletGudang = User::create([
            'name'      => 'Inactive Outlet Gudang',
            'email'     => 'inactive.outlet.gudang@nayara.test',
            'password'  => Hash::make('password'),
            'role'      => UserRole::GUDANG,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $this->inactiveOutlet->id,
        ]);

        $this->unitMeter = Unit::create([
            'unit_name' => 'Meter',
            'symbol'    => 'm',
            'active'    => true,
        ]);
        $this->unitRoll = Unit::create([
            'unit_name' => 'Roll',
            'symbol'    => 'roll',
            'active'    => true,
        ]);

        // Materials for Outlet A
        $this->materialA1 = Material::create([
            'outlet_id'     => $this->outletA->id,
            'material_code' => 'MAT-A-01',
            'material_name' => 'Kain Ceruty A',
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
            'material_code' => 'MAT-A-02',
            'material_name' => 'Kain Voal Premium A',
            'unit_id'       => $this->unitMeter->id,
            'min_stock'     => 5,
            'active'        => true,
        ]);
        $this->stockA2 = MaterialStock::create([
            'material_id'   => $this->materialA2->id,
            'qty_on_hand'   => '50.0000',
            'avg_unit_cost' => '35000.0000',
        ]);

        // Material for Outlet B
        $this->materialB1 = Material::create([
            'outlet_id'     => $this->outletB->id,
            'material_code' => 'MAT-B-01',
            'material_name' => 'Kain Sutra B',
            'unit_id'       => $this->unitMeter->id,
            'min_stock'     => 15,
            'active'        => true,
        ]);
        $this->stockB1 = MaterialStock::create([
            'material_id'   => $this->materialB1->id,
            'qty_on_hand'   => '75.0000',
            'avg_unit_cost' => '50000.0000',
        ]);

        // Legacy unassigned Material
        $this->legacyMaterial = Material::create([
            'outlet_id'     => null,
            'material_code' => 'MAT-LEGACY',
            'material_name' => 'Kain Katun Legacy',
            'unit_id'       => $this->unitMeter->id,
            'min_stock'     => 10,
            'active'        => true,
        ]);
        $this->legacyStock = MaterialStock::create([
            'material_id'   => $this->legacyMaterial->id,
            'qty_on_hand'   => '20.0000',
            'avg_unit_cost' => '20000.0000',
        ]);
    }

    /**
     * 01. GUDANG A lists only A materials.
     */
    public function test_01_gudang_a_lists_only_a_materials(): void
    {
        $response = $this->actingAs($this->gudangA)->get(route('gudang.materials.index'));
        $response->assertOk();

        $materials = $response->viewData('materials');
        $this->assertEquals(2, $materials->total());

        $names = collect($materials->items())->pluck('material_name')->all();
        $this->assertContains('Kain Ceruty A', $names);
        $this->assertContains('Kain Voal Premium A', $names);
        $this->assertNotContains('Kain Sutra B', $names);
    }

    /**
     * 02. GUDANG B lists only B materials.
     */
    public function test_02_gudang_b_lists_only_b_materials(): void
    {
        $response = $this->actingAs($this->gudangB)->get(route('gudang.materials.index'));
        $response->assertOk();

        $materials = $response->viewData('materials');
        $this->assertEquals(1, $materials->total());

        $names = collect($materials->items())->pluck('material_name')->all();
        $this->assertContains('Kain Sutra B', $names);
        $this->assertNotContains('Kain Ceruty A', $names);
    }

    /**
     * 03. Foreign material hidden from index and search.
     */
    public function test_03_foreign_material_hidden_from_index_and_search(): void
    {
        // Search by foreign material name returns 0 results
        $response = $this->actingAs($this->gudangA)->get(route('gudang.materials.index', [
            'search' => 'Sutra B',
        ]));
        $response->assertOk();

        $materials = $response->viewData('materials');
        $this->assertEquals(0, $materials->total());
    }

    /**
     * 04. Pagination totals scoped.
     */
    public function test_04_pagination_totals_scoped(): void
    {
        $response = $this->actingAs($this->gudangA)->get(route('gudang.materials.index'));
        $materials = $response->viewData('materials');

        $this->assertEquals(2, $materials->total());
        $this->assertCount(2, $materials->items());
    }

    /**
     * 05. Foreign show -> 404.
     */
    public function test_05_foreign_show_returns_404(): void
    {
        $this->actingAs($this->gudangA)
            ->get(route('gudang.materials.show', $this->materialB1))
            ->assertNotFound();
    }

    /**
     * 06. Foreign edit -> 404.
     */
    public function test_06_foreign_edit_returns_404(): void
    {
        $this->actingAs($this->gudangA)
            ->get(route('gudang.materials.edit', $this->materialB1))
            ->assertNotFound();
    }

    /**
     * 07. Foreign update -> 404.
     */
    public function test_07_foreign_update_returns_404(): void
    {
        $this->actingAs($this->gudangA)
            ->put(route('gudang.materials.update', $this->materialB1), [
                'unit_id'       => $this->unitMeter->id,
                'material_name' => 'Hacked Name',
                'min_stock'     => 20,
            ])
            ->assertNotFound();

        $this->materialB1->refresh();
        $this->assertEquals('Kain Sutra B', $this->materialB1->material_name);
    }

    /**
     * 08. Foreign destroy -> 404.
     */
    public function test_08_foreign_destroy_returns_404(): void
    {
        // Temporary zero-stock material in B
        $materialB2 = Material::create([
            'outlet_id'     => $this->outletB->id,
            'material_name' => 'B Zero Stock',
            'unit_id'       => $this->unitMeter->id,
            'min_stock'     => 0,
            'active'        => true,
        ]);

        $this->actingAs($this->gudangA)
            ->delete(route('gudang.materials.destroy', $materialB2))
            ->assertNotFound();

        $this->assertDatabaseHas('materials', ['id' => $materialB2->id]);
    }

    /**
     * 09. Foreign toggleActive -> 404.
     */
    public function test_09_foreign_toggle_active_returns_404(): void
    {
        $activeBefore = $this->materialB1->active;

        $this->actingAs($this->gudangA)
            ->post(route('gudang.materials.toggle-active', $this->materialB1))
            ->assertNotFound();

        $this->materialB1->refresh();
        $this->assertEquals($activeBefore, $this->materialB1->active);
    }

    /**
     * 10. New material uses trusted server-side outlet_id.
     */
    public function test_10_new_material_uses_trusted_serverside_outlet_id(): void
    {
        $response = $this->actingAs($this->gudangA)->post(route('gudang.materials.store'), [
            'unit_id'       => $this->unitRoll->id,
            'material_name' => 'Kain Rayon Baru A',
            'min_stock'     => 12,
        ]);

        $response->assertRedirect();

        $created = Material::where('material_name', 'Kain Rayon Baru A')->first();
        $this->assertNotNull($created);
        $this->assertEquals($this->outletA->id, $created->outlet_id);

        // Ensure MaterialStock was created automatically
        $this->assertDatabaseHas('material_stocks', [
            'material_id' => $created->id,
            'qty_on_hand' => 0,
        ]);
    }

    /**
     * 11. Spoofed outlet_id in payload is safely overridden.
     */
    public function test_11_spoofed_outlet_id_is_safely_overridden(): void
    {
        $response = $this->actingAs($this->gudangA)->post(route('gudang.materials.store'), [
            'unit_id'       => $this->unitRoll->id,
            'material_name' => 'Kain Spoof Attempt',
            'min_stock'     => 5,
            'outlet_id'     => $this->outletB->id, // Malicious spoof
        ]);

        $response->assertRedirect();

        $created = Material::where('material_name', 'Kain Spoof Attempt')->first();
        $this->assertNotNull($created);
        $this->assertEquals($this->outletA->id, $created->outlet_id, 'Server must enforce authenticated outlet_id.');
    }

    /**
     * 12. X-Outlet-ID header spoofing is ignored.
     */
    public function test_12_x_outlet_id_header_spoofing_ignored(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->withHeaders(['X-Outlet-ID' => (string) $this->outletB->id])
            ->post(route('gudang.materials.store'), [
                'unit_id'       => $this->unitRoll->id,
                'material_name' => 'Kain Header Spoof',
                'min_stock'     => 5,
            ]);

        $response->assertRedirect();

        $created = Material::where('material_name', 'Kain Header Spoof')->first();
        $this->assertEquals($this->outletA->id, $created->outlet_id);
    }

    /**
     * 13. Legacy unassigned NULL material cannot be accessed or claimed under TRUE.
     */
    public function test_13_foreign_legacy_null_material_not_claimed(): void
    {
        // Show -> 404
        $this->actingAs($this->gudangA)
            ->get(route('gudang.materials.show', $this->legacyMaterial))
            ->assertNotFound();

        // Edit -> 404
        $this->actingAs($this->gudangA)
            ->get(route('gudang.materials.edit', $this->legacyMaterial))
            ->assertNotFound();

        // Update -> 404
        $this->actingAs($this->gudangA)
            ->put(route('gudang.materials.update', $this->legacyMaterial), [
                'unit_id'       => $this->unitMeter->id,
                'material_name' => 'Claim Attempt',
                'min_stock'     => 10,
            ])
            ->assertNotFound();

        $this->legacyMaterial->refresh();
        $this->assertNull($this->legacyMaterial->outlet_id);
    }

    /**
     * 14. Attempt to reparent material ownership during update is rejected.
     */
    public function test_14_attempt_to_reparent_material_rejected(): void
    {
        $response = $this->actingAs($this->gudangA)->put(route('gudang.materials.update', $this->materialA1), [
            'unit_id'       => $this->unitMeter->id,
            'material_name' => 'Kain Ceruty Updated',
            'min_stock'     => 10,
            'outlet_id'     => $this->outletB->id, // Malicious reparent attempt
        ]);

        $response->assertRedirect(route('gudang.materials.show', $this->materialA1));

        $this->materialA1->refresh();
        $this->assertEquals($this->outletA->id, $this->materialA1->outlet_id);
        $this->assertEquals('Kain Ceruty Updated', $this->materialA1->material_name);
    }

    /**
     * 15. Unauthorized roles rejected from accessing material master.
     */
    public function test_15_unauthorized_roles_rejected(): void
    {
        $this->actingAs($this->adminA)->get(route('gudang.materials.index'))->assertForbidden();
        $this->actingAs($this->ownerA)->get(route('gudang.materials.index'))->assertForbidden();
    }

    /**
     * 16. Inactive user rejected.
     */
    public function test_16_inactive_user_rejected(): void
    {
        $response = $this->actingAs($this->inactiveGudangA)->get(route('gudang.materials.index'));
        $response->assertRedirect(route('login'));
    }

    /**
     * 17. Inactive outlet rejected.
     */
    public function test_17_inactive_outlet_rejected(): void
    {
        $this->actingAs($this->inactiveOutletGudang)->get(route('gudang.materials.index'))->assertForbidden();
    }

    /**
     * 18. Unbound user rejected.
     */
    public function test_18_unbound_user_rejected(): void
    {
        $this->actingAs($this->unboundGudang)->get(route('gudang.materials.index'))->assertForbidden();
    }

    /**
     * 19. Valid own-tenant CRUD still functions.
     */
    public function test_19_valid_own_tenant_crud_still_functions(): void
    {
        // 1. Create
        $resStore = $this->actingAs($this->gudangA)->post(route('gudang.materials.store'), [
            'unit_id'       => $this->unitMeter->id,
            'material_name' => 'Kain Linen A',
            'min_stock'     => 8,
        ]);
        $resStore->assertRedirect();
        $linen = Material::where('material_name', 'Kain Linen A')->firstOrFail();

        // 2. Show
        $this->actingAs($this->gudangA)->get(route('gudang.materials.show', $linen))->assertOk();

        // 3. Edit
        $this->actingAs($this->gudangA)->get(route('gudang.materials.edit', $linen))->assertOk();

        // 4. Update
        $this->actingAs($this->gudangA)->put(route('gudang.materials.update', $linen), [
            'unit_id'       => $this->unitMeter->id,
            'material_name' => 'Kain Linen A Super',
            'min_stock'     => 10,
        ])->assertRedirect();
        $linen->refresh();
        $this->assertEquals('Kain Linen A Super', $linen->material_name);

        // 5. Toggle active
        $this->actingAs($this->gudangA)->post(route('gudang.materials.toggle-active', $linen))->assertRedirect();
        $linen->refresh();
        $this->assertFalse($linen->active);

        // 6. Destroy (no stock, no movements, no BOM)
        $this->actingAs($this->gudangA)->delete(route('gudang.materials.destroy', $linen))->assertRedirect(route('gudang.materials.index'));
        $this->assertDatabaseMissing('materials', ['id' => $linen->id]);
    }

    /**
     * 20. Failed operation preserves material and stock records.
     */
    public function test_20_failed_operation_preserves_material_and_stock_records(): void
    {
        // Try invalid update with negative min_stock
        $this->actingAs($this->gudangA)->put(route('gudang.materials.update', $this->materialA1), [
            'unit_id'       => $this->unitMeter->id,
            'material_name' => 'Invalid',
            'min_stock'     => -10,
        ])->assertSessionHasErrors('min_stock');

        $this->materialA1->refresh();
        $this->assertEquals(10, (float) $this->materialA1->min_stock);
    }

    /**
     * 21. StockMovement records remain intact.
     */
    public function test_21_stock_movement_records_remain_intact(): void
    {
        $movement = StockMovement::create([
            'material_id'         => $this->materialA1->id,
            'movement_type'       => \App\Enums\MovementType::IN,
            'qty'                 => '10.0000',
            'unit_cost_snapshot'  => '25000.0000',
            'total_cost_snapshot' => '250000.00',
            'qty_before'          => '90.0000',
            'qty_after'           => '100.0000',
            'movement_date'       => now()->toDateString(),
            'notes'               => 'Test movement',
            'created_by'          => $this->gudangA->id,
        ]);

        // Attempting to delete material with movements is blocked
        $this->actingAs($this->gudangA)->delete(route('gudang.materials.destroy', $this->materialA1))
            ->assertRedirect(route('gudang.materials.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('materials', ['id' => $this->materialA1->id]);
        $this->assertDatabaseHas('stock_movements', ['id' => $movement->id]);
    }

    /**
     * 22. Existing stock valuation remains unchanged.
     */
    public function test_22_existing_stock_valuation_remains_unchanged(): void
    {
        $this->stockA1->refresh();
        $this->assertEquals('25000.0000', (string) $this->stockA1->avg_unit_cost);
        $this->assertEquals('100.0000', (string) $this->stockA1->qty_on_hand);
    }

    /**
     * 23. Flag FALSE legacy CRUD remains compatible.
     */
    public function test_23_flag_false_legacy_crud_remains_compatible(): void
    {
        config(['nayara.tenant_isolation_enabled' => false]);

        // In legacy mode, all materials are visible
        $response = $this->actingAs($this->gudangA)->get(route('gudang.materials.index'));
        $response->assertOk();

        $materials = $response->viewData('materials');
        $this->assertEquals(4, $materials->total()); // A1, A2, B1, and Legacy
    }

    /**
     * 24. No Global Tenant Scope on Material model.
     */
    public function test_24_no_global_tenant_scope_on_material(): void
    {
        $material = new Material();
        $this->assertEmpty($material->getGlobalScopes(), 'Material must not have global tenant scopes.');
    }
}
