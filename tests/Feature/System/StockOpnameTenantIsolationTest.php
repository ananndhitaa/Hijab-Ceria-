<?php

namespace Tests\Feature\System;

use App\Enums\MovementType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Inventory\MaterialStock;
use App\Models\Inventory\StockMovement;
use App\Models\Master\Material;
use App\Models\Master\Unit;
use App\Models\System\Outlet;
use App\Models\System\User;
use App\Services\Inventory\StockOpnameService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Fase 8.5: Stock Opname Tenant Isolation Security Test Suite.
 *
 * Implements rigorous HTTP, database, transaction boundary, and authorization security tests
 * for Stock Opname write operations covering Scenarios 01 to 23 of Fase 8.5.
 */
class StockOpnameTenantIsolationTest extends TestCase
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
    protected Material $materialA;
    protected MaterialStock $stockA;
    protected Material $materialB;
    protected MaterialStock $stockB;
    protected Material $legacyMaterial;
    protected MaterialStock $legacyStock;

    protected StockOpnameService $opnameService;

    protected function setUp(): void
    {
        parent::setUp();

        config(['nayara.tenant_isolation_enabled' => true]);

        $this->opnameService = app(StockOpnameService::class);

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

        $this->materialA = Material::create([
            'outlet_id'     => $this->outletA->id,
            'material_name' => 'Kain Ceruty Tenant A',
            'unit_id'       => $this->unitMeter->id,
            'min_stock'     => '10.0000',
            'active'        => true,
        ]);
        $this->stockA = MaterialStock::create([
            'material_id'   => $this->materialA->id,
            'qty_on_hand'   => '100.0000',
            'avg_unit_cost' => '25000.0000',
        ]);

        $this->materialB = Material::create([
            'outlet_id'     => $this->outletB->id,
            'material_name' => 'Kain Silk Tenant B',
            'unit_id'       => $this->unitMeter->id,
            'min_stock'     => '20.0000',
            'active'        => true,
        ]);
        $this->stockB = MaterialStock::create([
            'material_id'   => $this->materialB->id,
            'qty_on_hand'   => '200.0000',
            'avg_unit_cost' => '30000.0000',
        ]);

        $this->legacyMaterial = Material::create([
            'outlet_id'     => null, // Legacy unassigned
            'material_name' => 'Kain Katun Legacy Null',
            'unit_id'       => $this->unitMeter->id,
            'min_stock'     => '5.0000',
            'active'        => true,
        ]);
        $this->legacyStock = MaterialStock::create([
            'material_id'   => $this->legacyMaterial->id,
            'qty_on_hand'   => '50.0000',
            'avg_unit_cost' => '15000.0000',
        ]);
    }

    /**
     * 01. Authorized user A can stock-opname Material A.
     */
    public function test_01_authorized_user_a_can_stock_opname_material_a(): void
    {
        $response = $this->actingAs($this->gudangA)->post(route('gudang.materials.stock-opname.store', $this->materialA), [
            'physical_qty' => '120.0000',
            'notes'        => 'Audit fisik bulanan Tenant A - surplus 20m',
        ]);

        $response->assertRedirect(route('gudang.materials.show', $this->materialA));
        $response->assertSessionHas('success');

        $this->stockA->refresh();
        $this->assertEquals('120.0000', (string) $this->stockA->qty_on_hand);

        $this->assertDatabaseHas('stock_movements', [
            'material_id'         => $this->materialA->id,
            'movement_type'       => MovementType::ADJUSTMENT->value,
            'qty'                 => '20.0000',
            'qty_before'          => '100.0000',
            'qty_after'           => '120.0000',
            'unit_cost_snapshot'  => '25000.0000',
            'total_cost_snapshot' => '500000.00', // 20 * 25,000
            'created_by'          => $this->gudangA->id,
        ]);
    }

    /**
     * 02. Authorized user B can stock-opname Material B.
     */
    public function test_02_authorized_user_b_can_stock_opname_material_b(): void
    {
        $response = $this->actingAs($this->gudangB)->post(route('gudang.materials.stock-opname.store', $this->materialB), [
            'physical_qty' => '180.0000',
            'notes'        => 'Audit fisik bulanan Tenant B - susut 20m',
        ]);

        $response->assertRedirect(route('gudang.materials.show', $this->materialB));
        $response->assertSessionHas('success');

        $this->stockB->refresh();
        $this->assertEquals('180.0000', (string) $this->stockB->qty_on_hand);

        $this->assertDatabaseHas('stock_movements', [
            'material_id'         => $this->materialB->id,
            'movement_type'       => MovementType::ADJUSTMENT->value,
            'qty'                 => '20.0000',
            'qty_before'          => '200.0000',
            'qty_after'           => '180.0000',
            'unit_cost_snapshot'  => '30000.0000',
            'total_cost_snapshot' => '600000.00', // 20 * 30,000
            'created_by'          => $this->gudangB->id,
        ]);
    }

    /**
     * 03. User A cannot stock-opname Material B (HTTP 404).
     */
    public function test_03_user_a_cannot_stock_opname_material_b(): void
    {
        // GET create form -> 404
        $this->actingAs($this->gudangA)
            ->get(route('gudang.materials.stock-opname', $this->materialB))
            ->assertNotFound();

        // POST store opname -> 404
        $this->actingAs($this->gudangA)
            ->post(route('gudang.materials.stock-opname.store', $this->materialB), [
                'physical_qty' => '250.0000',
                'notes'        => 'Cross-tenant opname attempt by User A',
            ])
            ->assertNotFound();

        $this->stockB->refresh();
        $this->assertEquals('200.0000', (string) $this->stockB->qty_on_hand);
        $this->assertEquals(0, StockMovement::where('material_id', $this->materialB->id)->count());
    }

    /**
     * 04. User B cannot stock-opname Material A (HTTP 404).
     */
    public function test_04_user_b_cannot_stock_opname_material_a(): void
    {
        // GET create form -> 404
        $this->actingAs($this->gudangB)
            ->get(route('gudang.materials.stock-opname', $this->materialA))
            ->assertNotFound();

        // POST store opname -> 404
        $this->actingAs($this->gudangB)
            ->post(route('gudang.materials.stock-opname.store', $this->materialA), [
                'physical_qty' => '50.0000',
                'notes'        => 'Cross-tenant opname attempt by User B',
            ])
            ->assertNotFound();

        $this->stockA->refresh();
        $this->assertEquals('100.0000', (string) $this->stockA->qty_on_hand);
        $this->assertEquals(0, StockMovement::where('material_id', $this->materialA->id)->count());
    }

    /**
     * 05. Forged outlet_id in payload is ignored.
     */
    public function test_05_forged_outlet_id_in_payload_is_ignored(): void
    {
        $response = $this->actingAs($this->gudangA)->post(route('gudang.materials.stock-opname.store', $this->materialA), [
            'outlet_id'    => $this->outletB->id, // Spoofed outlet_id
            'physical_qty' => '110.0000',
            'notes'        => 'Spoofed outlet_id payload',
        ]);

        $response->assertRedirect(route('gudang.materials.show', $this->materialA));

        $this->stockA->refresh();
        $this->assertEquals('110.0000', (string) $this->stockA->qty_on_hand);
        // Material B stock remains completely untouched
        $this->stockB->refresh();
        $this->assertEquals('200.0000', (string) $this->stockB->qty_on_hand);
    }

    /**
     * 06. Forged X-Outlet-ID header is ignored.
     */
    public function test_06_forged_x_outlet_id_header_is_ignored(): void
    {
        // Gudang A supplies X-Outlet-ID: outletB but targets Material B -> must still 404!
        $this->actingAs($this->gudangA)
            ->withHeaders(['X-Outlet-ID' => (string) $this->outletB->id])
            ->post(route('gudang.materials.stock-opname.store', $this->materialB), [
                'physical_qty' => '300.0000',
                'notes'        => 'Header spoof attempt',
            ])
            ->assertNotFound();

        $this->stockB->refresh();
        $this->assertEquals('200.0000', (string) $this->stockB->qty_on_hand);
    }

    /**
     * 07. Legacy unassigned material cannot be claimed or opnamed under isolation TRUE.
     */
    public function test_07_legacy_unassigned_material_cannot_be_claimed_or_opnamed(): void
    {
        $this->actingAs($this->gudangA)
            ->get(route('gudang.materials.stock-opname', $this->legacyMaterial))
            ->assertNotFound();

        $this->actingAs($this->gudangA)
            ->post(route('gudang.materials.stock-opname.store', $this->legacyMaterial), [
                'physical_qty' => '70.0000',
                'notes'        => 'Claim legacy material attempt',
            ])
            ->assertNotFound();

        $this->legacyStock->refresh();
        $this->assertEquals('50.0000', (string) $this->legacyStock->qty_on_hand);
    }

    /**
     * 08. Inactive user rejected.
     */
    public function test_08_inactive_user_rejected(): void
    {
        $response = $this->actingAs($this->inactiveGudangA)
            ->post(route('gudang.materials.stock-opname.store', $this->materialA), [
                'physical_qty' => '110.0000',
                'notes'        => 'Inactive user attempt',
            ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['email' => 'Akun Anda telah dinonaktifkan. Hubungi administrator.']);

        $this->stockA->refresh();
        $this->assertEquals('100.0000', (string) $this->stockA->qty_on_hand);
    }

    /**
     * 09. Inactive outlet rejected.
     */
    public function test_09_inactive_outlet_rejected(): void
    {
        $this->actingAs($this->inactiveOutletGudang)
            ->post(route('gudang.materials.stock-opname.store', $this->materialA), [
                'physical_qty' => '110.0000',
                'notes'        => 'Inactive outlet attempt',
            ])
            ->assertForbidden();
    }

    /**
     * 10. Unbound user fails closed.
     */
    public function test_10_unbound_user_fails_closed(): void
    {
        $this->actingAs($this->unboundGudang)
            ->post(route('gudang.materials.stock-opname.store', $this->materialA), [
                'physical_qty' => '110.0000',
                'notes'        => 'Unbound user attempt',
            ])
            ->assertForbidden();
    }

    /**
     * 11. Unauthorized roles rejected from accessing stock opname.
     */
    public function test_11_unauthorized_roles_rejected(): void
    {
        $payload = [
            'physical_qty' => '110.0000',
            'notes'        => 'Role test',
        ];

        // Admin (gudang routes are role:GUDANG only)
        $this->actingAs($this->adminA)
            ->post(route('gudang.materials.stock-opname.store', $this->materialA), $payload)
            ->assertForbidden();

        // Produksi
        $this->actingAs($this->produksiA)
            ->post(route('gudang.materials.stock-opname.store', $this->materialA), $payload)
            ->assertForbidden();

        // Customer
        $this->actingAs($this->customerUserA)
            ->post(route('gudang.materials.stock-opname.store', $this->materialA), $payload)
            ->assertForbidden();
    }

    /**
     * 12. Valid physical count produces correct quantity on MaterialStock.
     */
    public function test_12_valid_physical_count_produces_correct_quantity(): void
    {
        $this->actingAs($this->gudangA)->post(route('gudang.materials.stock-opname.store', $this->materialA), [
            'physical_qty' => '155.7500',
            'notes'        => 'Decimal precision test',
        ])->assertRedirect();

        $this->stockA->refresh();
        $this->assertEquals('155.7500', (string) $this->stockA->qty_on_hand);
    }

    /**
     * 13. Stock adjustment delta is calculated correctly.
     */
    public function test_13_stock_adjustment_delta_is_calculated_correctly(): void
    {
        // 100 -> 85 = delta 15.0000
        $this->actingAs($this->gudangA)->post(route('gudang.materials.stock-opname.store', $this->materialA), [
            'physical_qty' => '85.0000',
            'notes'        => 'Delta calculation test',
        ])->assertRedirect();

        $movement = StockMovement::where('material_id', $this->materialA->id)->firstOrFail();
        $this->assertEquals('15.0000', (string) $movement->qty);
        $this->assertEquals('100.0000', (string) $movement->qty_before);
        $this->assertEquals('85.0000', (string) $movement->qty_after);
    }

    /**
     * 14. Correct movement recorded with all metadata snapshots.
     */
    public function test_14_correct_movement_recorded(): void
    {
        $this->actingAs($this->gudangA)->post(route('gudang.materials.stock-opname.store', $this->materialA), [
            'physical_qty' => '130.0000',
            'notes'        => 'Movement metadata test',
        ])->assertRedirect();

        $movement = StockMovement::where('material_id', $this->materialA->id)->firstOrFail();
        $this->assertEquals(MovementType::ADJUSTMENT, $movement->movement_type);
        $this->assertEquals('30.0000', (string) $movement->qty);
        $this->assertEquals('25000.0000', (string) $movement->unit_cost_snapshot);
        $this->assertEquals('750000.00', (string) $movement->total_cost_snapshot);
        $this->assertEquals('Movement metadata test', $movement->notes);
        $this->assertEquals($this->gudangA->id, $movement->created_by);
        $this->assertEquals(now('Asia/Jakarta')->toDateString(), $movement->movement_date->format('Y-m-d'));
    }

    /**
     * 15. Movement references the authorized material.
     */
    public function test_15_movement_references_authorized_material(): void
    {
        $this->actingAs($this->gudangA)->post(route('gudang.materials.stock-opname.store', $this->materialA), [
            'physical_qty' => '105.0000',
            'notes'        => 'Material reference test',
        ])->assertRedirect();

        $movement = StockMovement::where('material_id', $this->materialA->id)->firstOrFail();
        $this->assertEquals($this->materialA->id, $movement->material->id);
        $this->assertEquals($this->outletA->id, $movement->material->outlet_id);
    }

    /**
     * 16. No movement in Tenant B from Tenant A request.
     */
    public function test_16_no_movement_in_tenant_b_from_tenant_a_request(): void
    {
        $this->actingAs($this->gudangA)->post(route('gudang.materials.stock-opname.store', $this->materialA), [
            'physical_qty' => '115.0000',
            'notes'        => 'Tenant isolation verification',
        ])->assertRedirect();

        $movementsB = StockMovement::whereHas('material', fn ($q) => $q->where('outlet_id', $this->outletB->id))->count();
        $this->assertEquals(0, $movementsB);
    }

    /**
     * 17. Invalid quantity rejected (negative or non-numeric).
     */
    public function test_17_invalid_quantity_rejected(): void
    {
        $response = $this->actingAs($this->gudangA)->post(route('gudang.materials.stock-opname.store', $this->materialA), [
            'physical_qty' => '-5.0000',
            'notes'        => 'Negative qty test',
        ]);
        $response->assertSessionHasErrors(['physical_qty']);

        $this->stockA->refresh();
        $this->assertEquals('100.0000', (string) $this->stockA->qty_on_hand);
    }

    /**
     * 18. Zero physical stock follows existing valid rules.
     */
    public function test_18_zero_physical_stock_follows_existing_rules(): void
    {
        $this->actingAs($this->gudangA)->post(route('gudang.materials.stock-opname.store', $this->materialA), [
            'physical_qty' => '0',
            'notes'        => 'Stok fisik habis / nihil',
        ])->assertRedirect();

        $this->stockA->refresh();
        $this->assertEquals('0.0000', (string) $this->stockA->qty_on_hand);

        $movement = StockMovement::where('material_id', $this->materialA->id)->firstOrFail();
        $this->assertEquals('100.0000', (string) $movement->qty);
        $this->assertEquals('0.0000', (string) $movement->qty_after);
    }

    /**
     * 19. Failure rolls back both stock and movement (atomicity).
     */
    public function test_19_failure_rolls_back_both_stock_and_movement(): void
    {
        // Missing required notes in service call directly throws DomainException
        try {
            $this->opnameService->adjustStock($this->materialA, '150.0000', '   ', $this->gudangA->id);
            $this->fail('Expected DomainException for empty notes.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('Catatan penyesuaian stok wajib diisi', $e->getMessage());
        }

        $this->stockA->refresh();
        $this->assertEquals('100.0000', (string) $this->stockA->qty_on_hand);
        $this->assertEquals(0, StockMovement::count());
    }

    /**
     * 20. Repeated requests follow actual defined behavior (no-op on same physical qty).
     */
    public function test_20_repeated_requests_follow_defined_behavior(): void
    {
        // First opname to 110.0000
        $this->actingAs($this->gudangA)->post(route('gudang.materials.stock-opname.store', $this->materialA), [
            'physical_qty' => '110.0000',
            'notes'        => 'First adjustment',
        ])->assertRedirect();

        $this->assertEquals(1, StockMovement::where('material_id', $this->materialA->id)->count());

        // Second opname with exact same 110.0000 -> no-op
        $response = $this->actingAs($this->gudangA)->post(route('gudang.materials.stock-opname.store', $this->materialA), [
            'physical_qty' => '110.0000',
            'notes'        => 'Second identical adjustment',
        ]);
        $response->assertRedirect(route('gudang.materials.show', $this->materialA));
        $response->assertSessionHas('info');

        // Still only 1 movement record!
        $this->assertEquals(1, StockMovement::where('material_id', $this->materialA->id)->count());
    }

    /**
     * 21. Existing MWA behavior remains unchanged.
     */
    public function test_21_existing_mwa_behavior_remains_unchanged(): void
    {
        $avgCostBefore = (string) $this->stockA->avg_unit_cost;

        $this->actingAs($this->gudangA)->post(route('gudang.materials.stock-opname.store', $this->materialA), [
            'physical_qty' => '140.0000',
            'notes'        => 'MWA unchanged test',
        ])->assertRedirect();

        $this->stockA->refresh();
        $this->assertEquals($avgCostBefore, (string) $this->stockA->avg_unit_cost);
    }

    /**
     * 22. Existing receiving records remain unchanged.
     */
    public function test_22_existing_receiving_records_remain_unchanged(): void
    {
        // Create an existing receiving movement
        $recvMovement = StockMovement::create([
            'material_id'         => $this->materialA->id,
            'movement_type'       => MovementType::IN,
            'qty'                 => '50.0000',
            'unit_cost_snapshot'  => '25000.0000',
            'total_cost_snapshot' => '1250000.00',
            'qty_before'          => '50.0000',
            'qty_after'           => '100.0000',
            'movement_date'       => '2026-10-15',
            'notes'               => 'Penerimaan PO',
            'created_by'          => $this->gudangA->id,
        ]);

        $this->actingAs($this->gudangA)->post(route('gudang.materials.stock-opname.store', $this->materialA), [
            'physical_qty' => '120.0000',
            'notes'        => 'Opname with existing receiving',
        ])->assertRedirect();

        // Prior receiving movement is untouched
        $this->assertDatabaseHas('stock_movements', [
            'id'            => $recvMovement->id,
            'movement_type' => MovementType::IN->value,
            'qty'           => '50.0000',
        ]);
    }

    /**
     * 23. Legacy flag FALSE stock opname preserved.
     */
    public function test_23_legacy_flag_false_stock_opname_preserved(): void
    {
        config(['nayara.tenant_isolation_enabled' => false]);

        $response = $this->actingAs($this->gudangA)->post(route('gudang.materials.stock-opname.store', $this->legacyMaterial), [
            'physical_qty' => '65.0000',
            'notes'        => 'Legacy stock opname',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->legacyStock->refresh();
        $this->assertEquals('65.0000', (string) $this->legacyStock->qty_on_hand);
    }
}
