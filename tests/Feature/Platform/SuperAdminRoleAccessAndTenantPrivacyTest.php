<?php

namespace Tests\Feature\Platform;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\System\Outlet;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminRoleAccessAndTenantPrivacyTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private Outlet $outletA;
    private Outlet $outletB;
    private User $ownerA;
    private User $ownerB;
    private User $adminA;
    private User $adminB;
    private User $gudangA;
    private User $produksiA;
    private User $customerA;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Super Admin Platform (strictly outlet_id = null)
        $this->superAdmin = User::create([
            'name'      => 'Super Admin Utama',
            'email'     => 'superadmin@nayara.test',
            'password'  => Hash::make('SuperSecret123!'),
            'role'      => UserRole::SUPER_ADMIN,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => null,
        ]);

        // 2. Tenant Outlets
        $this->outletA = Outlet::create([
            'outlet_name' => 'Nayara Hijab Kudus',
            'outlet_code' => 'OUT-KUDUS-01',
            'status'      => 'ACTIVE',
        ]);

        $this->outletB = Outlet::create([
            'outlet_name' => 'Nayara Hijab Semarang',
            'outlet_code' => 'OUT-SMG-02',
            'status'      => 'ACTIVE',
        ]);

        // 3. Outlet A Personnel
        $this->ownerA = User::create([
            'name'      => 'Budi Owner Kudus',
            'email'     => 'budi.owner@kudus.test',
            'password'  => Hash::make('Secret123!'),
            'role'      => UserRole::OWNER,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $this->outletA->id,
        ]);

        $this->adminA = User::create([
            'name'      => 'Siti Admin Kudus',
            'email'     => 'siti.admin@kudus.test',
            'password'  => Hash::make('Secret123!'),
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $this->outletA->id,
        ]);

        $this->gudangA = User::create([
            'name'      => 'Ahmad Gudang Kudus',
            'email'     => 'ahmad.gudang@kudus.test',
            'password'  => Hash::make('Secret123!'),
            'role'      => UserRole::GUDANG,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $this->outletA->id,
        ]);

        $this->produksiA = User::create([
            'name'      => 'Dewi Produksi Kudus',
            'email'     => 'dewi.produksi@kudus.test',
            'password'  => Hash::make('Secret123!'),
            'role'      => UserRole::PRODUKSI,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $this->outletA->id,
        ]);

        $this->customerA = User::create([
            'name'      => 'Rina Customer Kudus',
            'email'     => 'rina.customer@buyer.test',
            'password'  => Hash::make('Secret123!'),
            'role'      => UserRole::CUSTOMER,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $this->outletA->id,
        ]);

        // 4. Outlet B Personnel
        $this->ownerB = User::create([
            'name'      => 'Hendra Owner Semarang',
            'email'     => 'hendra.owner@semarang.test',
            'password'  => Hash::make('Secret123!'),
            'role'      => UserRole::OWNER,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $this->outletB->id,
        ]);

        $this->adminB = User::create([
            'name'      => 'Farah Admin Semarang',
            'email'     => 'farah.admin@semarang.test',
            'password'  => Hash::make('Secret123!'),
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $this->outletB->id,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PENGUJIAN 1: SUPER ADMIN MEMBUKA /platform/roles
    // ─────────────────────────────────────────────────────────────────────────

    public function test_superadmin_can_open_platform_roles_page(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('platform.roles.index'));

        $response->assertOk();
        $response->assertSee('Matriks Role');
        $response->assertSee('Hak Akses');
        $response->assertSee('Definisi resmi 6 tingkatan hak akses');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PENGUJIAN 2: TABEL SELURUH PENGGUNA TENANT TIDAK MUNCUL
    // ─────────────────────────────────────────────────────────────────────────

    public function test_tenant_users_table_does_not_appear_on_platform_roles(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('platform.roles.index'));

        $response->assertOk();

        // Previous table headers & captions MUST NOT appear
        $response->assertDontSee('Pengguna Terdaftar di Sistem');
        $response->assertDontSee('Seluruh akun yang terdaftar pada database hijab_erp beserta perannya');
        $response->assertDontSee('NAMA PENGGUNA');
        $response->assertDontSee('OUTLET ASOSIASI');

        // Instead, Platform Administrators table is present
        $response->assertSee('Administrator Platform (Super Admin)');
        $response->assertSee('NAMA ADMINISTRATOR');
        $response->assertSee('EMAIL RESMI');
        $response->assertSee('CAKUPAN OTORITAS');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PENGUJIAN 3: DATA TENANT TIDAK TERKIRIM DALAM HTML ATAU DATA BACKEND
    // ─────────────────────────────────────────────────────────────────────────

    public function test_no_tenant_data_is_leaked_in_response_or_backend_view_data(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('platform.roles.index'));

        $response->assertOk();

        // 1. Assert HTML response does NOT contain any tenant user names or emails
        $response->assertDontSee($this->ownerA->name);
        $response->assertDontSee($this->ownerA->email);
        $response->assertDontSee($this->adminA->name);
        $response->assertDontSee($this->adminA->email);
        $response->assertDontSee($this->gudangA->name);
        $response->assertDontSee($this->gudangA->email);
        $response->assertDontSee($this->produksiA->name);
        $response->assertDontSee($this->produksiA->email);
        $response->assertDontSee($this->customerA->name);
        $response->assertDontSee($this->customerA->email);
        $response->assertDontSee($this->ownerB->name);
        $response->assertDontSee($this->ownerB->email);
        $response->assertDontSee($this->adminB->name);
        $response->assertDontSee($this->adminB->email);

        // 2. Assert HTML response does NOT contain tenant outlet names
        $response->assertDontSee('Nayara Hijab Kudus');
        $response->assertDontSee('Nayara Hijab Semarang');

        // 3. Assert Backend View Data contract
        $response->assertViewMissing('users');
        $response->assertViewHas('platformAdmins');
        $response->assertViewHas('rolesMatrix');

        $platformAdmins = $response->viewData('platformAdmins');
        foreach ($platformAdmins as $admin) {
            $this->assertEquals(UserRole::SUPER_ADMIN, $admin->role);
            $this->assertNull($admin->outlet_id, 'Platform admin must not be bound to a tenant outlet');
        }

        // 4. Matrix retains all 6 official NAYARA roles
        $matrixRoles = array_column($response->viewData('rolesMatrix'), 'role');
        $this->assertContains(UserRole::SUPER_ADMIN->value, $matrixRoles);
        $this->assertContains(UserRole::OWNER->value, $matrixRoles);
        $this->assertContains(UserRole::ADMIN->value, $matrixRoles);
        $this->assertContains(UserRole::GUDANG->value, $matrixRoles);
        $this->assertContains(UserRole::PRODUKSI->value, $matrixRoles);
        $this->assertContains(UserRole::CUSTOMER->value, $matrixRoles);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PENGUJIAN 4: OWNER DAPAT MENGELOLA KARYAWAN OUTLET SENDIRI
    // ─────────────────────────────────────────────────────────────────────────

    public function test_owner_can_manage_own_outlet_employees(): void
    {
        // 1. Owner A can view employees list for Outlet A
        $response = $this->actingAs($this->ownerA)->get(route('owner.employees.index'));
        $response->assertOk();
        $response->assertSee($this->adminA->name);
        $response->assertSee($this->gudangA->name);
        $response->assertSee($this->produksiA->name);
        $response->assertDontSee($this->adminB->name); // Outlet B employee must NOT appear

        // 2. Owner A can view employee detail of Outlet A
        $showResponse = $this->actingAs($this->ownerA)->get(route('owner.employees.show', $this->adminA));
        $showResponse->assertOk();
        $showResponse->assertSee($this->adminA->name);

        // 3. Owner A can create new employee for Outlet A
        $createResponse = $this->actingAs($this->ownerA)->post(route('owner.employees.store'), [
            'name'                  => 'Karyawan Baru Kudus',
            'email'                 => 'baru.kudus@nayara.test',
            'role'                  => UserRole::PRODUKSI->value,
            'status'                => UserStatus::ACTIVE->value,
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $createResponse->assertRedirect(route('owner.employees.index'));
        $this->assertDatabaseHas('users', [
            'email'     => 'baru.kudus@nayara.test',
            'outlet_id' => $this->outletA->id,
            'role'      => UserRole::PRODUKSI->value,
        ]);

        // 4. Owner A can update own employee
        $updateResponse = $this->actingAs($this->ownerA)->put(route('owner.employees.update', $this->adminA), [
            'name'   => 'Siti Admin Kudus Updated',
            'email'  => 'siti.admin@kudus.test',
            'role'   => UserRole::ADMIN->value,
            'status' => UserStatus::ACTIVE->value,
        ]);

        $updateResponse->assertRedirect(route('owner.employees.index'));
        $this->assertEquals('Siti Admin Kudus Updated', $this->adminA->fresh()->name);

        // 5. Owner A can toggle status of own employee
        $toggleResponse = $this->actingAs($this->ownerA)->patch(route('owner.employees.toggle-status', $this->adminA));
        $toggleResponse->assertRedirect();
        $this->assertEquals(UserStatus::INACTIVE, $this->adminA->fresh()->status);

        // 6. Owner A can reset password of own employee
        $resetResponse = $this->actingAs($this->ownerA)->post(route('owner.employees.reset-password', $this->adminA), [
            'password'              => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $resetResponse->assertRedirect(route('owner.employees.show', $this->adminA));
        $this->assertTrue(Hash::check('NewPassword123!', $this->adminA->fresh()->password));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PENGUJIAN 5: OWNER TIDAK DAPAT MENGAKSES KARYAWAN OUTLET LAIN
    // ─────────────────────────────────────────────────────────────────────────

    public function test_owner_cannot_access_or_manage_other_outlet_employees(): void
    {
        // 1. Owner A cannot view Outlet B employee
        $this->actingAs($this->ownerA)->get(route('owner.employees.show', $this->adminB))
            ->assertForbidden();

        // 2. Owner A cannot access edit form of Outlet B employee
        $this->actingAs($this->ownerA)->get(route('owner.employees.edit', $this->adminB))
            ->assertForbidden();

        // 3. Owner A cannot update Outlet B employee
        $this->actingAs($this->ownerA)->put(route('owner.employees.update', $this->adminB), [
            'name'   => 'Attacked Name',
            'email'  => 'farah.admin@semarang.test',
            'role'   => UserRole::ADMIN->value,
            'status' => UserStatus::ACTIVE->value,
        ])->assertForbidden();

        // Verify Outlet B employee was not changed
        $this->assertEquals('Farah Admin Semarang', $this->adminB->fresh()->name);

        // 4. Owner A cannot toggle status of Outlet B employee
        $this->actingAs($this->ownerA)->patch(route('owner.employees.toggle-status', $this->adminB))
            ->assertForbidden();

        // 5. Owner A cannot reset password of Outlet B employee
        $this->actingAs($this->ownerA)->post(route('owner.employees.reset-password', $this->adminB), [
            'password'              => 'HackedPassword123!',
            'password_confirmation' => 'HackedPassword123!',
        ])->assertForbidden();

        // 6. Owner A cannot access employee performance detail of Outlet B admin
        $this->actingAs($this->ownerA)->get(route('owner.employee-performance.admin-detail', $this->adminB))
            ->assertForbidden();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PENGUJIAN 5B: OWNER TANPA OUTLET_ID MENGGUNAKAN PRINSIP FAIL-CLOSED
    // ─────────────────────────────────────────────────────────────────────────

    public function test_owner_with_null_outlet_id_fails_closed(): void
    {
        $unboundOwner = User::create([
            'name'      => 'Unbound Owner',
            'email'     => 'unbound.owner@nayara.test',
            'password'  => Hash::make('Secret123!'),
            'role'      => UserRole::OWNER,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => null, // Explicitly NULL
        ]);

        // 1. Cannot view employee list
        $this->actingAs($unboundOwner)->get(route('owner.employees.index'))
            ->assertForbidden();

        // 2. Cannot access create form
        $this->actingAs($unboundOwner)->get(route('owner.employees.create'))
            ->assertForbidden();

        // 3. Cannot store employee
        $this->actingAs($unboundOwner)->post(route('owner.employees.store'), [
            'name'                  => 'Illegal Employee',
            'email'                 => 'illegal@nayara.test',
            'role'                  => UserRole::ADMIN->value,
            'status'                => UserStatus::ACTIVE->value,
            'password'              => 'Secret123!',
            'password_confirmation' => 'Secret123!',
        ])->assertForbidden();

        // 4. Cannot view, edit, update, toggle, or reset any employee
        $this->actingAs($unboundOwner)->get(route('owner.employees.show', $this->adminA))
            ->assertForbidden();

        $this->actingAs($unboundOwner)->get(route('owner.employees.edit', $this->adminA))
            ->assertForbidden();

        $this->actingAs($unboundOwner)->put(route('owner.employees.update', $this->adminA), [
            'name'   => 'Illegal Edit',
            'email'  => 'siti.admin@kudus.test',
            'role'   => UserRole::ADMIN->value,
            'status' => UserStatus::ACTIVE->value,
        ])->assertForbidden();

        $this->actingAs($unboundOwner)->patch(route('owner.employees.toggle-status', $this->adminA))
            ->assertForbidden();

        $this->actingAs($unboundOwner)->post(route('owner.employees.reset-password', $this->adminA), [
            'password'              => 'IllegalPassword123!',
            'password_confirmation' => 'IllegalPassword123!',
        ])->assertForbidden();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PENGUJIAN 5C: OWNER TIDAK DAPAT MEMBUAT SUPER_ADMIN
    // ─────────────────────────────────────────────────────────────────────────

    public function test_owner_cannot_create_super_admin_or_unauthorized_role(): void
    {
        // Attempt to create SUPER_ADMIN
        $response = $this->actingAs($this->ownerA)->post(route('owner.employees.store'), [
            'name'                  => 'Malicious Super Admin',
            'email'                 => 'malicious.sa@nayara.test',
            'role'                  => UserRole::SUPER_ADMIN->value,
            'status'                => UserStatus::ACTIVE->value,
            'password'              => 'Secret123!',
            'password_confirmation' => 'Secret123!',
        ]);

        $response->assertSessionHasErrors(['role']);
        $this->assertDatabaseMissing('users', ['email' => 'malicious.sa@nayara.test']);

        // Attempt to create OWNER
        $responseOwner = $this->actingAs($this->ownerA)->post(route('owner.employees.store'), [
            'name'                  => 'Duplicate Owner',
            'email'                 => 'duplicate.owner@nayara.test',
            'role'                  => UserRole::OWNER->value,
            'status'                => UserStatus::ACTIVE->value,
            'password'              => 'Secret123!',
            'password_confirmation' => 'Secret123!',
        ]);

        $responseOwner->assertSessionHasErrors(['role']);
        $this->assertDatabaseMissing('users', ['email' => 'duplicate.owner@nayara.test']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PENGUJIAN 6: SUPER ADMIN TIDAK DAPAT MENGAKSES DATA OPERASIONAL PRIVAT TENANT
    // ─────────────────────────────────────────────────────────────────────────

    public function test_superadmin_cannot_access_tenant_private_operational_endpoints(): void
    {
        // Super Admin cannot access tenant ERP routes
        $this->actingAs($this->superAdmin)->get(route('owner.employees.index'))->assertForbidden();
        $this->actingAs($this->superAdmin)->get(route('owner.dashboard'))->assertForbidden();
        $this->actingAs($this->superAdmin)->get(route('owner.hpp.index'))->assertForbidden();
        $this->actingAs($this->superAdmin)->get(route('owner.profitability.index'))->assertForbidden();
        $this->actingAs($this->superAdmin)->get(route('owner.employee-performance.index'))->assertForbidden();
        $this->actingAs($this->superAdmin)->get(route('owner.analytics.index'))->assertForbidden();
    }
}
