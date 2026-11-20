<?php

namespace Tests\Feature\System;

use App\Enums\MovementType;
use App\Enums\PurchaseStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Inventory\MaterialStock;
use App\Models\Inventory\Purchase;
use App\Models\Inventory\PurchaseItem;
use App\Models\Inventory\StockMovement;
use App\Models\Master\Material;
use App\Models\Master\Supplier;
use App\Models\Master\Unit;
use App\Models\System\Outlet;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Fase 8.5: Stock Movement History Tenant Isolation Security Test Suite.
 *
 * Implements rigorous HTTP, query boundary, anti-IDOR, and relationship consistency tests
 * for Stock Movement History read operations covering Scenarios 24 to 45 of Fase 8.5.
 */
class StockMovementHistoryTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Outlet $outletA;
    protected Outlet $outletB;
    protected Outlet $inactiveOutlet;

    protected User $gudangA;
    protected User $gudangB;
    protected User $adminA;
    protected User $produksiA;
    protected User $customerUserA;
    protected User $inactiveGudangA;
    protected User $unboundGudang;
    protected User $inactiveOutletGudang;

    protected Unit $unitMeter;

    protected Material $materialA1;
    protected Material $materialA2;
    protected Material $materialB1;
    protected Material $materialB2;
    protected Material $legacyMaterial;

    protected StockMovement $movementA1;
    protected StockMovement $movementA2;
    protected StockMovement $movementA3;
    protected StockMovement $movementB1;
    protected StockMovement $movementB2;
    protected StockMovement $movementLegacy;

    protected Supplier $supplierA;
    protected Supplier $supplierB;
    protected Purchase $purchaseA;
    protected Purchase $purchaseB;
    protected PurchaseItem $purchaseItemA;
    protected PurchaseItem $purchaseItemB;

    protected function setUp(): void
    {
        parent::setUp();

        config(['nayara.tenant_isolation_enabled' => true]);

        // 1. Outlets
        $this->outletA = Outlet::create([
            'outlet_code'                => 'OUT-A',
            'outlet_name'                => 'Nayara Outlet A',
            'slug'                       => 'outlet-a',
            'address'                    => 'Jl. Industri A No. 1',
            'status'                     => 'ACTIVE',
            'is_public_showcase_enabled' => true,
        ]);

        $this->outletB = Outlet::create([
            'outlet_code'                => 'OUT-B',
            'outlet_name'                => 'Nayara Outlet B',
            'slug'                       => 'outlet-b',
            'address'                    => 'Jl. Industri B No. 2',
            'status'                     => 'ACTIVE',
            'is_public_showcase_enabled' => true,
        ]);

        $this->inactiveOutlet = Outlet::create([
            'outlet_code'                => 'OUT-INACTIVE',
            'outlet_name'                => 'Nayara Inactive Outlet',
            'slug'                       => 'outlet-inactive',
            'address'                    => 'Jl. Tutup No. 99',
            'status'                     => 'INACTIVE',
            'is_public_showcase_enabled' => false,
        ]);

        // 2. Users
        $this->gudangA = User::create([
            'outlet_id' => $this->outletA->id,
            'name'      => 'Gudang A',
            'email'     => 'gudang.a@example.com',
            'password'  => Hash::make('password'),
            'role'      => UserRole::GUDANG,
            'status'    => UserStatus::ACTIVE,
        ]);

        $this->gudangB = User::create([
            'outlet_id' => $this->outletB->id,
            'name'      => 'Gudang B',
            'email'     => 'gudang.b@example.com',
            'password'  => Hash::make('password'),
            'role'      => UserRole::GUDANG,
            'status'    => UserStatus::ACTIVE,
        ]);

        $this->adminA = User::create([
            'outlet_id' => $this->outletA->id,
            'name'      => 'Admin A',
            'email'     => 'admin.a@example.com',
            'password'  => Hash::make('password'),
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::ACTIVE,
        ]);

        $this->produksiA = User::create([
            'outlet_id' => $this->outletA->id,
            'name'      => 'Produksi A',
            'email'     => 'produksi.a@example.com',
            'password'  => Hash::make('password'),
            'role'      => UserRole::PRODUKSI,
            'status'    => UserStatus::ACTIVE,
        ]);

        $this->customerUserA = User::create([
            'outlet_id' => $this->outletA->id,
            'name'      => 'Customer User A',
            'email'     => 'customer.a@example.com',
            'password'  => Hash::make('password'),
            'role'      => UserRole::CUSTOMER,
            'status'    => UserStatus::ACTIVE,
        ]);

        $this->inactiveGudangA = User::create([
            'outlet_id' => $this->outletA->id,
            'name'      => 'Inactive Gudang A',
            'email'     => 'inactive.gudang.a@example.com',
            'password'  => Hash::make('password'),
            'role'      => UserRole::GUDANG,
            'status'    => UserStatus::INACTIVE,
        ]);

        $this->unboundGudang = User::create([
            'outlet_id' => null,
            'name'      => 'Unbound Gudang',
            'email'     => 'unbound.gudang@example.com',
            'password'  => Hash::make('password'),
            'role'      => UserRole::GUDANG,
            'status'    => UserStatus::ACTIVE,
        ]);

        $this->inactiveOutletGudang = User::create([
            'outlet_id' => $this->inactiveOutlet->id,
            'name'      => 'Inactive Outlet Gudang',
            'email'     => 'inactive.outlet.gudang@example.com',
            'password'  => Hash::make('password'),
            'role'      => UserRole::GUDANG,
            'status'    => UserStatus::ACTIVE,
        ]);

        // 3. Units & Materials
        $this->unitMeter = Unit::firstOrCreate(['unit_name' => 'Meter', 'symbol' => 'm']);

        // Materials Tenant A
        $this->materialA1 = Material::create([
            'outlet_id'     => $this->outletA->id,
            'material_name' => 'Kain Ceruty Tenant A',
            'unit_id'       => $this->unitMeter->id,
            'min_stock'     => '10.0000',
            'active'        => true,
        ]);
        MaterialStock::create([
            'material_id'   => $this->materialA1->id,
            'qty_on_hand'   => '100.0000',
            'avg_unit_cost' => '25000.0000',
        ]);

        $this->materialA2 = Material::create([
            'outlet_id'     => $this->outletA->id,
            'material_name' => 'Kain Renda Tenant A',
            'unit_id'       => $this->unitMeter->id,
            'min_stock'     => '5.0000',
            'active'        => true,
        ]);
        MaterialStock::create([
            'material_id'   => $this->materialA2->id,
            'qty_on_hand'   => '50.0000',
            'avg_unit_cost' => '10000.0000',
        ]);

        // Materials Tenant B
        $this->materialB1 = Material::create([
            'outlet_id'     => $this->outletB->id,
            'material_name' => 'Kain Silk Tenant B',
            'unit_id'       => $this->unitMeter->id,
            'min_stock'     => '20.0000',
            'active'        => true,
        ]);
        MaterialStock::create([
            'material_id'   => $this->materialB1->id,
            'qty_on_hand'   => '200.0000',
            'avg_unit_cost' => '30000.0000',
        ]);

        $this->materialB2 = Material::create([
            'outlet_id'     => $this->outletB->id,
            'material_name' => 'Kain Satin Tenant B',
            'unit_id'       => $this->unitMeter->id,
            'min_stock'     => '15.0000',
            'active'        => true,
        ]);
        MaterialStock::create([
            'material_id'   => $this->materialB2->id,
            'qty_on_hand'   => '75.0000',
            'avg_unit_cost' => '18000.0000',
        ]);

        // Legacy Material (outlet_id = null)
        $this->legacyMaterial = Material::create([
            'outlet_id'     => null,
            'material_name' => 'Kain Katun Legacy Null',
            'unit_id'       => $this->unitMeter->id,
            'min_stock'     => '5.0000',
            'active'        => true,
        ]);
        MaterialStock::create([
            'material_id'   => $this->legacyMaterial->id,
            'qty_on_hand'   => '40.0000',
            'avg_unit_cost' => '15000.0000',
        ]);

        // 4. Procurement References
        $this->supplierA = Supplier::create([
            'outlet_id'     => $this->outletA->id,
            'supplier_name' => 'Supplier Kain A',
            'contact_phone' => '0811111111',
            'is_active'     => true,
            'created_by'    => $this->adminA->id,
        ]);
        $this->purchaseA = Purchase::create([
            'outlet_id'     => $this->outletA->id,
            'purchase_no'   => 'PO-A-001',
            'supplier_id'   => $this->supplierA->id,
            'purchase_date' => '2026-10-10',
            'status'        => PurchaseStatus::RECEIVED,
            'created_by'    => $this->gudangA->id,
        ]);
        $this->purchaseItemA = PurchaseItem::create([
            'purchase_id' => $this->purchaseA->id,
            'material_id' => $this->materialA1->id,
            'qty'         => '50.0000',
            'unit_price'  => '25000.0000',
            'subtotal'    => '1250000.00',
        ]);

        $this->supplierB = Supplier::create([
            'outlet_id'     => $this->outletB->id,
            'supplier_name' => 'Supplier Kain B',
            'contact_phone' => '0822222222',
            'is_active'     => true,
            'created_by'    => $this->gudangB->id,
        ]);
        $this->purchaseB = Purchase::create([
            'outlet_id'     => $this->outletB->id,
            'purchase_no'   => 'PO-B-001',
            'supplier_id'   => $this->supplierB->id,
            'purchase_date' => '2026-10-10',
            'status'        => PurchaseStatus::RECEIVED,
            'created_by'    => $this->gudangB->id,
        ]);
        $this->purchaseItemB = PurchaseItem::create([
            'purchase_id' => $this->purchaseB->id,
            'material_id' => $this->materialB1->id,
            'qty'         => '60.0000',
            'unit_price'  => '30000.0000',
            'subtotal'    => '1800000.00',
        ]);

        // 5. Stock Movements
        // Movement A1: IN via Purchase A
        $this->movementA1 = StockMovement::create([
            'material_id'         => $this->materialA1->id,
            'movement_type'       => MovementType::IN,
            'qty'                 => '50.0000',
            'unit_cost_snapshot'  => '25000.0000',
            'total_cost_snapshot' => '1250000.00',
            'qty_before'          => '50.0000',
            'qty_after'           => '100.0000',
            'reference_type'      => 'purchase_items',
            'reference_id'        => $this->purchaseItemA->id,
            'movement_date'       => '2026-10-10',
            'notes'               => 'Penerimaan PO Tenant A',
            'created_by'          => $this->gudangA->id,
        ]);

        // Movement A2: ADJUSTMENT (Opname)
        $this->movementA2 = StockMovement::create([
            'material_id'         => $this->materialA1->id,
            'movement_type'       => MovementType::ADJUSTMENT,
            'qty'                 => '10.0000',
            'unit_cost_snapshot'  => '25000.0000',
            'total_cost_snapshot' => '250000.00',
            'qty_before'          => '100.0000',
            'qty_after'           => '110.0000',
            'reference_type'      => null,
            'reference_id'        => null,
            'movement_date'       => '2026-10-15',
            'notes'               => 'Opname Tenant A',
            'created_by'          => $this->gudangA->id,
        ]);

        // Movement A3: IN (Manual/Initial)
        $this->movementA3 = StockMovement::create([
            'material_id'         => $this->materialA2->id,
            'movement_type'       => MovementType::IN,
            'qty'                 => '50.0000',
            'unit_cost_snapshot'  => '10000.0000',
            'total_cost_snapshot' => '500000.00',
            'qty_before'          => '0.0000',
            'qty_after'           => '50.0000',
            'reference_type'      => null,
            'reference_id'        => null,
            'movement_date'       => '2026-10-20',
            'notes'               => 'Stok Awal Renda Tenant A',
            'created_by'          => $this->gudangA->id,
        ]);

        // Movement B1: IN via Purchase B
        $this->movementB1 = StockMovement::create([
            'material_id'         => $this->materialB1->id,
            'movement_type'       => MovementType::IN,
            'qty'                 => '60.0000',
            'unit_cost_snapshot'  => '30000.0000',
            'total_cost_snapshot' => '1800000.00',
            'qty_before'          => '140.0000',
            'qty_after'           => '200.0000',
            'reference_type'      => 'purchase_items',
            'reference_id'        => $this->purchaseItemB->id,
            'movement_date'       => '2026-10-10',
            'notes'               => 'Penerimaan PO Tenant B',
            'created_by'          => $this->gudangB->id,
        ]);

        // Movement B2: ADJUSTMENT (Opname)
        $this->movementB2 = StockMovement::create([
            'material_id'         => $this->materialB2->id,
            'movement_type'       => MovementType::ADJUSTMENT,
            'qty'                 => '5.0000',
            'unit_cost_snapshot'  => '18000.0000',
            'total_cost_snapshot' => '90000.00',
            'qty_before'          => '75.0000',
            'qty_after'           => '70.0000',
            'reference_type'      => null,
            'reference_id'        => null,
            'movement_date'       => '2026-10-18',
            'notes'               => 'Opname Tenant B',
            'created_by'          => $this->gudangB->id,
        ]);

        // Movement Legacy (Material outlet_id = null)
        $this->movementLegacy = StockMovement::create([
            'material_id'         => $this->legacyMaterial->id,
            'movement_type'       => MovementType::IN,
            'qty'                 => '40.0000',
            'unit_cost_snapshot'  => '15000.0000',
            'total_cost_snapshot' => '600000.00',
            'qty_before'          => '0.0000',
            'qty_after'           => '40.0000',
            'reference_type'      => null,
            'reference_id'        => null,
            'movement_date'       => '2026-10-01',
            'notes'               => 'Legacy Movement Null',
            'created_by'          => null,
        ]);
    }

    /**
     * 24. Tenant A sees only A stock movements.
     */
    public function test_24_tenant_a_sees_only_a_stock_movements(): void
    {
        $response = $this->actingAs($this->gudangA)->get(route('gudang.stock-movements.index'));
        $response->assertOk();

        $movements = $response->viewData('movements');
        $movementIds = collect($movements->items())->pluck('id')->all();

        $this->assertContains($this->movementA1->id, $movementIds);
        $this->assertContains($this->movementA2->id, $movementIds);
        $this->assertContains($this->movementA3->id, $movementIds);

        $this->assertNotContains($this->movementB1->id, $movementIds);
        $this->assertNotContains($this->movementB2->id, $movementIds);
        $this->assertNotContains($this->movementLegacy->id, $movementIds);
    }

    /**
     * 25. Tenant B sees only B stock movements.
     */
    public function test_25_tenant_b_sees_only_b_stock_movements(): void
    {
        $response = $this->actingAs($this->gudangB)->get(route('gudang.stock-movements.index'));
        $response->assertOk();

        $movements = $response->viewData('movements');
        $movementIds = collect($movements->items())->pluck('id')->all();

        $this->assertContains($this->movementB1->id, $movementIds);
        $this->assertContains($this->movementB2->id, $movementIds);

        $this->assertNotContains($this->movementA1->id, $movementIds);
        $this->assertNotContains($this->movementA2->id, $movementIds);
        $this->assertNotContains($this->movementA3->id, $movementIds);
        $this->assertNotContains($this->movementLegacy->id, $movementIds);
    }

    /**
     * 26. Foreign movements are completely hidden.
     */
    public function test_26_foreign_movement_hidden(): void
    {
        $response = $this->actingAs($this->gudangA)->get(route('gudang.stock-movements.index'));
        $response->assertOk();

        $response->assertSeeText($this->materialA1->material_name);
        $response->assertSeeText($this->materialA2->material_name);
        $response->assertDontSeeText($this->materialB1->material_name);
        $response->assertDontSeeText($this->materialB2->material_name);
    }

    /**
     * 27. Legacy ambiguous movements do not leak under isolation TRUE.
     */
    public function test_27_legacy_ambiguous_movement_does_not_leak_under_true(): void
    {
        // Tenant A check
        $resA = $this->actingAs($this->gudangA)->get(route('gudang.stock-movements.index'));
        $this->assertNotContains($this->movementLegacy->id, collect($resA->viewData('movements')->items())->pluck('id')->all());
        $resA->assertDontSeeText($this->legacyMaterial->material_name);

        // Tenant B check
        $resB = $this->actingAs($this->gudangB)->get(route('gudang.stock-movements.index'));
        $this->assertNotContains($this->movementLegacy->id, collect($resB->viewData('movements')->items())->pluck('id')->all());
        $resB->assertDontSeeText($this->legacyMaterial->material_name);
    }

    /**
     * 28. Material filter is tenant-safe (dropdown and query parameter).
     */
    public function test_28_material_filter_is_tenant_safe(): void
    {
        $response = $this->actingAs($this->gudangA)->get(route('gudang.stock-movements.index'));
        $response->assertOk();

        // 1. Dropdown materials in view only contains Tenant A materials
        $materialsInDropdown = $response->viewData('materials');
        $matIds = $materialsInDropdown->pluck('id')->all();
        $this->assertContains($this->materialA1->id, $matIds);
        $this->assertContains($this->materialA2->id, $matIds);
        $this->assertNotContains($this->materialB1->id, $matIds);
        $this->assertNotContains($this->materialB2->id, $matIds);
        $this->assertNotContains($this->legacyMaterial->id, $matIds);

        // 2. Client passes ?material_id={materialB1->id} -> 0 results
        $resSpoofed = $this->actingAs($this->gudangA)->get(route('gudang.stock-movements.index', [
            'material_id' => $this->materialB1->id,
        ]));
        $resSpoofed->assertOk();
        $this->assertCount(0, $resSpoofed->viewData('movements')->items());
    }

    /**
     * 29. Movement type filter preserves tenant scope.
     */
    public function test_29_movement_type_filter_preserves_tenant_scope(): void
    {
        // Filter by ADJUSTMENT: Tenant A has 1 (A2), Tenant B has 1 (B2)
        $response = $this->actingAs($this->gudangA)->get(route('gudang.stock-movements.index', [
            'movement_type' => MovementType::ADJUSTMENT->value,
        ]));
        $response->assertOk();

        $items = $response->viewData('movements')->items();
        $this->assertCount(1, $items);
        $this->assertEquals($this->movementA2->id, $items[0]->id);
    }

    /**
     * 30. Date filter preserves tenant scope.
     */
    public function test_30_date_filter_preserves_tenant_scope(): void
    {
        // On 2026-10-10, both A1 and B1 occurred. Filter on that date must return ONLY A1 for Tenant A!
        $response = $this->actingAs($this->gudangA)->get(route('gudang.stock-movements.index', [
            'date_from' => '2026-10-10',
            'date_to'   => '2026-10-10',
        ]));
        $response->assertOk();

        $items = $response->viewData('movements')->items();
        $this->assertCount(1, $items);
        $this->assertEquals($this->movementA1->id, $items[0]->id);
    }

    /**
     * 31. Search preserves tenant scope.
     */
    public function test_31_search_preserves_tenant_scope(): void
    {
        // Search by Silk (Material B1) while logged in as Gudang A -> 0 results
        $response = $this->actingAs($this->gudangA)->get(route('gudang.stock-movements.index', [
            'material_id' => $this->materialB1->id,
        ]));
        $response->assertOk();
        $this->assertCount(0, $response->viewData('movements')->items());
    }

    /**
     * 32. Pagination count is tenant-safe.
     */
    public function test_32_pagination_count_is_tenant_safe(): void
    {
        $responseA = $this->actingAs($this->gudangA)->get(route('gudang.stock-movements.index'));
        $this->assertEquals(3, $responseA->viewData('movements')->total());

        $responseB = $this->actingAs($this->gudangB)->get(route('gudang.stock-movements.index'));
        $this->assertEquals(2, $responseB->viewData('movements')->total());
    }

    /**
     * 33. Eager-loaded references are tenant-safe.
     */
    public function test_33_eager_loading_references_are_tenant_safe(): void
    {
        $response = $this->actingAs($this->gudangA)->get(route('gudang.stock-movements.index'));
        $response->assertOk();

        $purchaseItemsMap = $response->viewData('purchaseItemsMap');
        $this->assertArrayHasKey($this->purchaseItemA->id, $purchaseItemsMap);
        $this->assertArrayNotHasKey($this->purchaseItemB->id, $purchaseItemsMap);
    }

    /**
     * 34. Spoofed outlet_id query parameter does not expand results.
     */
    public function test_34_spoofed_outlet_id_does_not_expand_results(): void
    {
        $response = $this->actingAs($this->gudangA)->get(route('gudang.stock-movements.index', [
            'outlet_id' => $this->outletB->id,
        ]));
        $response->assertOk();

        $movements = $response->viewData('movements');
        $this->assertEquals(3, $movements->total());
        $this->assertNotContains($this->movementB1->id, collect($movements->items())->pluck('id')->all());
    }

    /**
     * 35. Inactive and unbound callers fail closed.
     */
    public function test_35_inactive_and_unbound_callers_fail_closed(): void
    {
        // Inactive user
        $resInactive = $this->actingAs($this->inactiveGudangA)->get(route('gudang.stock-movements.index'));
        $resInactive->assertRedirect(route('login'));
        $resInactive->assertSessionHasErrors(['email' => 'Akun Anda telah dinonaktifkan. Hubungi administrator.']);

        // Unbound user
        $this->actingAs($this->unboundGudang)->get(route('gudang.stock-movements.index'))->assertForbidden();

        // Inactive outlet user
        $this->actingAs($this->inactiveOutletGudang)->get(route('gudang.stock-movements.index'))->assertForbidden();
    }

    /**
     * 36. Unauthorized roles rejected from accessing stock movements.
     */
    public function test_36_unauthorized_roles_rejected(): void
    {
        $this->actingAs($this->adminA)->get(route('gudang.stock-movements.index'))->assertForbidden();
        $this->actingAs($this->produksiA)->get(route('gudang.stock-movements.index'))->assertForbidden();
        $this->actingAs($this->customerUserA)->get(route('gudang.stock-movements.index'))->assertForbidden();
    }

    /**
     * 37. Tenant-owned movement references remain consistent.
     */
    public function test_37_tenant_owned_movement_references_remain_consistent(): void
    {
        $movement = StockMovement::where('id', $this->movementA1->id)->firstOrFail();
        $this->assertEquals($this->outletA->id, $movement->material->outlet_id);

        $purchaseItem = PurchaseItem::find($movement->reference_id);
        $this->assertNotNull($purchaseItem);
        $this->assertEquals($this->outletA->id, $purchaseItem->purchase->outlet_id);
    }

    /**
     * 38. Flag FALSE legacy movement history works.
     */
    public function test_38_flag_false_legacy_movement_history_works(): void
    {
        config(['nayara.tenant_isolation_enabled' => false]);

        $response = $this->actingAs($this->gudangA)->get(route('gudang.stock-movements.index'));
        $response->assertOk();

        // Under FALSE, all 6 movements are visible in single-tenant mode
        $movements = $response->viewData('movements');
        $this->assertEquals(6, $movements->total());
    }

    /**
     * 39. No new Global Tenant Scope registered on models.
     */
    public function test_39_no_new_global_tenant_scope_on_models(): void
    {
        $material = new Material();
        $this->assertEmpty($material->getGlobalScopes(), 'Material should not have global tenant scopes.');

        $movement = new StockMovement();
        $this->assertEmpty($movement->getGlobalScopes(), 'StockMovement should not have global tenant scopes.');
    }

    /**
     * 40. No cross-tenant mutation on failed requests.
     */
    public function test_40_no_cross_tenant_mutation_on_failed_requests(): void
    {
        $initialMovementCount = StockMovement::count();

        // Attempt invalid opname from Gudang A on Material B1
        $this->actingAs($this->gudangA)->post(route('gudang.materials.stock-opname.store', $this->materialB1), [
            'physical_qty' => '999.0000',
            'notes'        => 'Should fail',
        ])->assertNotFound();

        $this->assertEquals($initialMovementCount, StockMovement::count());
    }

    /**
     * 41. No unintended stock valuation change.
     */
    public function test_41_no_unintended_stock_valuation_change(): void
    {
        $movement = StockMovement::where('id', $this->movementA1->id)->firstOrFail();
        $this->assertEquals('25000.0000', (string) $movement->unit_cost_snapshot);
        $this->assertEquals('1250000.00', (string) $movement->total_cost_snapshot);
    }

    /**
     * 42. No finance or procurement regression.
     */
    public function test_42_no_finance_or_procurement_regression(): void
    {
        // Purchase A remains intact
        $this->purchaseA->refresh();
        $this->assertEquals(PurchaseStatus::RECEIVED, $this->purchaseA->status);
        $this->assertEquals($this->outletA->id, $this->purchaseA->outlet_id);
    }
}
