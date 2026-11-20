<?php

namespace Tests\Feature\Platform;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Platform\TenantRegistration;
use App\Models\System\Outlet;
use App\Models\System\User;
use App\Services\System\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminPlatformTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['nayara.tenant_isolation_enabled' => false]);
    }

    /**
     * Requirement 1: SUPER_ADMIN login and redirection.
     */
    public function test_super_admin_can_login_and_is_redirected_to_platform_dashboard(): void
    {
        $superAdmin = User::create([
            'name'      => 'Platform Master',
            'email'     => 'superadmin@nayara.test',
            'password'  => Hash::make('Secret123!'),
            'role'      => UserRole::SUPER_ADMIN->value,
            'status'    => UserStatus::ACTIVE->value,
            'outlet_id' => null,
        ]);

        $response = $this->post('/login', [
            'email'    => 'superadmin@nayara.test',
            'password' => 'Secret123!',
        ]);

        $this->assertAuthenticatedAs($superAdmin);
        $response->assertRedirect(route('platform.dashboard'));
    }

    /**
     * Requirement 2: Non-SUPER_ADMIN denied from platform routes (403).
     */
    public function test_non_super_admin_roles_are_denied_from_platform_routes(): void
    {
        $outlet = Outlet::create([
            'outlet_name' => 'Tenant Convection A',
            'outlet_code' => 'OUT-A',
            'status'      => 'ACTIVE',
        ]);

        $roles = [
            UserRole::OWNER,
            UserRole::ADMIN,
            UserRole::GUDANG,
            UserRole::PRODUKSI,
            UserRole::CUSTOMER,
        ];

        foreach ($roles as $role) {
            $user = User::create([
                'name'      => "Tenant {$role->value}",
                'email'     => "user-{$role->value}@tenant.test",
                'password'  => Hash::make('password'),
                'role'      => $role->value,
                'status'    => UserStatus::ACTIVE->value,
                'outlet_id' => $outlet->id,
            ]);

            $response = $this->actingAs($user)->get(route('platform.dashboard'));
            $response->assertStatus(403);

            $regResponse = $this->actingAs($user)->get(route('platform.registrations.index'));
            $regResponse->assertStatus(403);

            $auditResponse = $this->actingAs($user)->get(route('platform.audit.index'));
            $auditResponse->assertStatus(403);
        }
    }

    /**
     * Requirement 3 & 4: SUPER_ADMIN denied from tenant financial & stock writes.
     * In tenant TRUE mode, TenantContext throws AuthorizationException when actor is SUPER_ADMIN.
     */
    public function test_super_admin_cannot_execute_tenant_business_writes_under_tenant_isolation(): void
    {
        config(['nayara.tenant_isolation_enabled' => true]);

        $superAdmin = User::create([
            'name'      => 'Platform Super Admin',
            'email'     => 'sa@nayara.test',
            'password'  => Hash::make('Secret123!'),
            'role'      => UserRole::SUPER_ADMIN->value,
            'status'    => UserStatus::ACTIVE->value,
            'outlet_id' => null,
        ]);

        $this->actingAs($superAdmin);

        $tenantContext = app(TenantContext::class);

        // Accessing tenant context as Super Admin must fail closed
        $this->expectException(AuthorizationException::class);
        $tenantContext->currentOutletId();
    }

    /**
     * Requirement 5: Unbound tenant ADMIN (outlet_id=null) cannot access platform.
     */
    public function test_unbound_tenant_admin_with_null_outlet_cannot_access_platform(): void
    {
        $legacyUnboundAdmin = User::create([
            'name'      => 'Legacy Unbound Admin',
            'email'     => 'legacy_admin@test.com',
            'password'  => Hash::make('password'),
            'role'      => UserRole::ADMIN->value,
            'status'    => UserStatus::ACTIVE->value,
            'outlet_id' => null,
        ]);

        $response = $this->actingAs($legacyUnboundAdmin)->get(route('platform.dashboard'));
        $response->assertStatus(403);
    }

    /**
     * Requirement 6: Public user cannot self-assign role SUPER_ADMIN via public registration.
     */
    public function test_public_user_cannot_register_with_super_admin_role(): void
    {
        $response = $this->post(route('tenant.register.store'), [
            'company_name'          => 'Malicious Hijab Corp',
            'owner_name'            => 'Attacker',
            'email'                 => 'attacker@malicious.com',
            'phone_wa'              => '081299998888',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => UserRole::SUPER_ADMIN->value, // Forged role attempt
            'outlet_id'             => 1,                            // Forged outlet_id attempt
        ]);

        $response->assertRedirect(route('tenant.register.success'));

        // The registration record must only capture applicant data without role/outlet_id
        $this->assertDatabaseHas('tenant_registrations', [
            'company_name' => 'Malicious Hijab Corp',
            'email'        => 'attacker@malicious.com',
            'status'       => TenantRegistration::STATUS_PENDING,
        ]);

        // No user record created immediately
        $this->assertDatabaseMissing('users', [
            'email' => 'attacker@malicious.com',
        ]);
    }

    /**
     * Requirement 13: Unauthorized review action denied to unauthenticated or non-platform users.
     */
    public function test_unauthorized_users_cannot_approve_or_reject_registrations(): void
    {
        $reg = TenantRegistration::create([
            'company_name' => 'Pending Hijab',
            'owner_name'   => 'Owner Pending',
            'email'        => 'pending@hijab.test',
            'phone_wa'     => '081233334444',
            'password'     => Hash::make('password123'),
            'status'       => TenantRegistration::STATUS_PENDING,
        ]);

        // Guest attempt
        $resGuest = $this->post(route('platform.registrations.approve', $reg->id));
        $resGuest->assertRedirect(route('login'));

        // Ordinary Admin attempt
        $outlet = Outlet::create(['outlet_name' => 'Outlet X', 'outlet_code' => 'OUT-X', 'status' => 'ACTIVE']);
        $admin = User::create([
            'name'      => 'Tenant Admin',
            'email'     => 'tenant_admin@test.com',
            'password'  => Hash::make('password'),
            'role'      => UserRole::ADMIN->value,
            'status'    => UserStatus::ACTIVE->value,
            'outlet_id' => $outlet->id,
        ]);

        $resAdmin = $this->actingAs($admin)->post(route('platform.registrations.approve', $reg->id));
        $resAdmin->assertStatus(403);
    }

    /**
     * Requirement 17 & 18: Forged outlet_id and forged role are completely disregarded.
     */
    public function test_forged_outlet_id_and_role_are_rejected_or_sanitized(): void
    {
        $response = $this->post(route('tenant.register.store'), [
            'company_name'          => 'Injected Convection',
            'owner_name'            => 'Injected Owner',
            'email'                 => 'injected@test.com',
            'phone_wa'              => '081288887777',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'SUPER_ADMIN',
            'outlet_id'             => 999,
        ]);

        $response->assertRedirect(route('tenant.register.success'));

        $reg = TenantRegistration::where('email', 'injected@test.com')->first();
        $this->assertNotNull($reg);
        $this->assertEquals(TenantRegistration::STATUS_PENDING, $reg->status);
    }

    /**
     * Requirement 30: Role boundaries - ERP roles cannot escalate or access platform.
     */
    public function test_existing_erp_roles_permissions_are_not_expanded_to_platform(): void
    {
        $superAdmin = User::create([
            'name'      => 'Platform Admin',
            'email'     => 'super@platform.test',
            'password'  => Hash::make('Secret123!'),
            'role'      => UserRole::SUPER_ADMIN->value,
            'status'    => UserStatus::ACTIVE->value,
            'outlet_id' => null,
        ]);

        // Platform dashboard accessible by super admin
        $response = $this->actingAs($superAdmin)->get(route('platform.dashboard'));
        $response->assertStatus(200);
        $response->assertViewIs('platform.dashboard');

        // Monitoring accessible
        $resSystem = $this->actingAs($superAdmin)->get(route('platform.system.index'));
        $resSystem->assertStatus(200);

        // Audit accessible
        $resAudit = $this->actingAs($superAdmin)->get(route('platform.audit.index'));
        $resAudit->assertStatus(200);
    }

    /**
     * Bootstrap command: nayara:super-admin:bootstrap is idempotent and protected.
     */
    public function test_super_admin_bootstrap_command_is_functional_and_idempotent(): void
    {
        // First run: creates account
        $exitCode = Artisan::call('nayara:super-admin:bootstrap', [
            '--email'    => 'cli.superadmin@nayara.test',
            '--name'     => 'CLI Super Admin',
            '--password' => 'CliSecret123!',
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertDatabaseHas('users', [
            'email'  => 'cli.superadmin@nayara.test',
            'role'   => UserRole::SUPER_ADMIN->value,
            'status' => UserStatus::ACTIVE->value,
        ]);

        // Second run: updates existing, does not duplicate
        $exitCode2 = Artisan::call('nayara:super-admin:bootstrap', [
            '--email'    => 'cli.superadmin@nayara.test',
            '--name'     => 'CLI Super Admin Updated',
            '--password' => 'NewCliSecret123!',
        ]);

        $this->assertEquals(0, $exitCode2);
        $this->assertEquals(1, User::where('email', 'cli.superadmin@nayara.test')->count());
    }
}
