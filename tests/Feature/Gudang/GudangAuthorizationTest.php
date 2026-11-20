<?php

namespace Tests\Feature\Gudang;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Master\Material;
use App\Models\Master\Supplier;
use App\Models\Master\Unit;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GudangAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $gudangUser;
    private User $adminUser;
    private User $ownerUser;
    private User $produksiUser;
    private User $customerUser;
    private User $inactiveGudangUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gudangUser = User::factory()->create([
            'role'   => UserRole::GUDANG,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->adminUser = User::factory()->create([
            'role'   => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->ownerUser = User::factory()->create([
            'role'   => UserRole::OWNER,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->produksiUser = User::factory()->create([
            'role'   => UserRole::PRODUKSI,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->customerUser = User::factory()->create([
            'role'   => UserRole::CUSTOMER,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->inactiveGudangUser = User::factory()->create([
            'role'   => UserRole::GUDANG,
            'status' => UserStatus::INACTIVE,
        ]);
    }

    public function test_gudang_can_access_gudang_dashboard(): void
    {
        $response = $this->actingAs($this->gudangUser)->get(route('gudang.dashboard'));
        $response->assertOk();
        $response->assertSee('Dashboard Gudang');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('gudang.dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_admin_cannot_access_gudang_routes(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('gudang.dashboard'));
        $response->assertForbidden();
    }

    public function test_owner_cannot_access_gudang_routes(): void
    {
        $response = $this->actingAs($this->ownerUser)->get(route('gudang.dashboard'));
        $response->assertForbidden();
    }

    public function test_produksi_cannot_access_gudang_routes(): void
    {
        $response = $this->actingAs($this->produksiUser)->get(route('gudang.dashboard'));
        $response->assertForbidden();
    }

    public function test_customer_cannot_access_gudang_routes(): void
    {
        $response = $this->actingAs($this->customerUser)->get(route('gudang.dashboard'));
        $response->assertForbidden();
    }

    public function test_inactive_gudang_user_is_rejected_or_forbidden(): void
    {
        $response = $this->actingAs($this->inactiveGudangUser)->get(route('gudang.dashboard'));
        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(route('login')) || $response->status() === 401 || $response->status() === 403
        );
    }

    public function test_non_gudang_cannot_toggle_or_delete_gudang_masters(): void
    {
        $unit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $material = Material::create(['unit_id' => $unit->id, 'material_name' => 'Kain Kain', 'min_stock' => 10]);
        $supplier = Supplier::create(['supplier_name' => 'Supplier Kain']);

        // Admin cannot toggle or delete Gudang masters
        $res1 = $this->actingAs($this->adminUser)->post(route('gudang.materials.toggle-active', $material));
        $res1->assertForbidden();

        $res2 = $this->actingAs($this->adminUser)->delete(route('gudang.materials.destroy', $material));
        $res2->assertForbidden();

        $res3 = $this->actingAs($this->adminUser)->post(route('gudang.suppliers.toggle-active', $supplier));
        $res3->assertForbidden();

        // Produksi cannot delete unit
        $res4 = $this->actingAs($this->produksiUser)->delete(route('gudang.units.destroy', $unit));
        $res4->assertForbidden();
    }
}
