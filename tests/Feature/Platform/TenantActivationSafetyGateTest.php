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
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TenantActivationSafetyGateTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected Outlet $outletA;
    protected Outlet $outletB;
    protected User $ownerA;
    protected User $legacyAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::create([
            'name'      => 'Super Admin Master',
            'email'     => 'superadmin.gate@nayara.test',
            'password'  => Hash::make('Secret123!'),
            'role'      => UserRole::SUPER_ADMIN->value,
            'status'    => UserStatus::ACTIVE->value,
            'outlet_id' => null,
        ]);

        $this->outletA = Outlet::create([
            'outlet_name' => 'Convection A',
            'outlet_code' => 'OUT-A',
            'status'      => 'ACTIVE',
        ]);

        $this->outletB = Outlet::create([
            'outlet_name' => 'Convection B',
            'outlet_code' => 'OUT-B',
            'status'      => 'ACTIVE',
        ]);

        $this->ownerA = User::create([
            'name'      => 'Owner Convection A',
            'email'     => 'owner.a@gate.test',
            'password'  => Hash::make('Secret123!'),
            'role'      => UserRole::OWNER->value,
            'status'    => UserStatus::ACTIVE->value,
            'outlet_id' => $this->outletA->id,
        ]);

        TenantRegistration::create([
            'company_name'  => 'Convection A',
            'owner_name'    => 'Owner Convection A',
            'email'         => 'owner.a@gate.test',
            'phone_wa'      => '081234567890',
            'password'      => Hash::make('Secret123!'),
            'status'        => TenantRegistration::STATUS_APPROVED,
            'outlet_id'     => $this->outletA->id,
            'owner_user_id' => $this->ownerA->id,
        ]);

        $this->legacyAdmin = User::create([
            'name'      => 'Legacy Monolith Admin',
            'email'     => 'legacy.admin@gate.test',
            'password'  => Hash::make('Secret123!'),
            'role'      => UserRole::ADMIN->value,
            'status'    => UserStatus::ACTIVE->value,
            'outlet_id' => null,
        ]);
    }

    /**
     * Requirement 1: Original single-tenant ERP remains usable by legacy user in legacy FALSE mode.
     */
    public function test_legacy_unassigned_user_can_access_erp_when_isolation_is_false(): void
    {
        config(['nayara.tenant_isolation_enabled' => false]);

        $response = $this->post('/login', [
            'email'    => 'legacy.admin@gate.test',
            'password' => 'Secret123!',
        ]);

        $this->assertAuthenticatedAs($this->legacyAdmin);
        $response->assertRedirect(route('admin.dashboard'));
    }

    /**
     * Requirement 2 & 4: Onboarded tenant user (outlet_id !== null) is BLOCKED from ERP login when isolation is FALSE.
     */
    public function test_onboarded_tenant_user_cannot_login_when_isolation_is_false(): void
    {
        config(['nayara.tenant_isolation_enabled' => false]);

        $response = $this->post('/login', [
            'email'    => 'owner.a@gate.test',
            'password' => 'Secret123!',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    /**
     * Requirement 2 & 4 (continued): Direct session of onboarded tenant user is rejected from ERP routes when isolation is FALSE.
     */
    public function test_onboarded_tenant_user_session_is_intercepted_from_erp_routes_when_isolation_is_false(): void
    {
        config(['nayara.tenant_isolation_enabled' => false]);

        $response = $this->actingAs($this->ownerA)->get(route('owner.dashboard'));
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
    }

    /**
     * Requirement 2: Onboarded tenant user CAN access their ERP dashboard when tenant isolation is TRUE.
     */
    public function test_onboarded_tenant_user_can_login_and_access_erp_when_isolation_is_true(): void
    {
        config(['nayara.tenant_isolation_enabled' => true]);

        $response = $this->post('/login', [
            'email'    => 'owner.a@gate.test',
            'password' => 'Secret123!',
        ]);

        $this->assertAuthenticatedAs($this->ownerA);
        $response->assertRedirect(route('owner.dashboard'));
    }

    /**
     * Requirement 3: Public registration can collect applications even while isolation is FALSE.
     */
    public function test_public_registration_functions_when_isolation_is_false(): void
    {
        config(['nayara.tenant_isolation_enabled' => false]);

        $response = $this->post(route('tenant.register.store'), [
            'company_name'          => 'Garmen Mandiri Solo',
            'owner_name'            => 'Bambang',
            'email'                 => 'bambang@garmen.test',
            'phone_wa'              => '081299990000',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect(route('tenant.register.success'));

        $this->assertDatabaseHas('tenant_registrations', [
            'company_name' => 'Garmen Mandiri Solo',
            'status'       => TenantRegistration::STATUS_PENDING,
        ]);
    }

    /**
     * Requirement 5 & 6: Super Admin can review and approve applications while isolation is FALSE,
     * but the activated owner remains gated until tenant isolation is TRUE.
     */
    public function test_approval_while_false_does_not_permit_immediate_unscoped_erp_access(): void
    {
        config(['nayara.tenant_isolation_enabled' => false]);

        $reg = TenantRegistration::create([
            'company_name' => 'Konveksi Hijab Cirebon',
            'owner_name'   => 'Dedi',
            'email'        => 'dedi@cirebon.test',
            'phone_wa'     => '081288889999',
            'password'     => Hash::make('Secret123!'),
            'status'       => TenantRegistration::STATUS_PENDING,
        ]);

        // Super Admin approves
        $this->actingAs($this->superAdmin)->post(
            route('platform.registrations.approve', $reg->id),
            ['decision_notes' => 'Verifikasi awal berhasil']
        );

        $reg->refresh();
        $this->assertEquals(TenantRegistration::STATUS_APPROVED, $reg->status);

        // Attempting to log in as Dedi while isolation is FALSE must fail closed
        auth()->logout();
        $this->flushSession();

        $loginAttemptFalse = $this->post('/login', [
            'email'    => 'dedi@cirebon.test',
            'password' => 'Secret123!',
        ]);
        $this->assertGuest();

        // When isolation is enabled (TRUE), Dedi can log in
        config(['nayara.tenant_isolation_enabled' => true]);

        $loginAttemptTrue = $this->post('/login', [
            'email'    => 'dedi@cirebon.test',
            'password' => 'Secret123!',
        ]);
        $this->assertAuthenticated();
    }

    /**
     * Requirement 7 & 9: Super Admin is never bound to Outlet 1 and cannot execute tenant writes.
     */
    public function test_super_admin_is_never_bound_to_outlet_1_and_fails_closed_on_tenant_context(): void
    {
        config(['nayara.tenant_isolation_enabled' => true]);

        $this->actingAs($this->superAdmin);

        $tenantContext = app(TenantContext::class);

        $this->assertNull($this->superAdmin->outlet_id);
        $this->expectException(AuthorizationException::class);
        $tenantContext->currentOutlet();
    }
}
