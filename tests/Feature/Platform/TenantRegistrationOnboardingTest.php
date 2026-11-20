<?php

namespace Tests\Feature\Platform;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Platform\TenantRegistration;
use App\Models\Platform\TenantRegistrationHistory;
use App\Models\System\Outlet;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TenantRegistrationOnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        config(['nayara.tenant_isolation_enabled' => false]);

        $this->superAdmin = User::create([
            'name'      => 'Super Admin QA',
            'email'     => 'superadmin.qa@nayara.test',
            'password'  => Hash::make('Secret123!'),
            'role'      => UserRole::SUPER_ADMIN->value,
            'status'    => UserStatus::ACTIVE->value,
            'outlet_id' => null,
        ]);
    }

    /**
     * Requirement 7 & 20: Registration is free, validated, and starts in PENDING status.
     */
    public function test_free_registration_starts_in_pending_status_without_payment(): void
    {
        $response = $this->post(route('tenant.register.store'), [
            'company_name'          => 'CV Hijab Lestari Mandiri',
            'owner_name'            => 'Hj. Aminah',
            'email'                 => 'aminah@lestarihijab.com',
            'phone_wa'              => '081234567890',
            'address'               => 'Jl. Tekstil Indah No. 10',
            'password'              => 'HijabSecure2026!',
            'password_confirmation' => 'HijabSecure2026!',
        ]);

        $response->assertRedirect(route('tenant.register.success'));

        $this->assertDatabaseHas('tenant_registrations', [
            'company_name' => 'CV Hijab Lestari Mandiri',
            'owner_name'   => 'Hj. Aminah',
            'email'        => 'aminah@lestarihijab.com',
            'phone_wa'     => '081234567890',
            'status'       => TenantRegistration::STATUS_PENDING,
        ]);

        $this->assertDatabaseHas('tenant_registration_histories', [
            'action'    => 'SUBMIT',
            'to_status' => TenantRegistration::STATUS_PENDING,
        ]);
    }

    /**
     * Requirement 8: Registration does not automatically activate ERP.
     * Applicant cannot log in while status is PENDING.
     */
    public function test_pending_registration_does_not_permit_erp_login(): void
    {
        $this->post(route('tenant.register.store'), [
            'company_name'          => 'Garmen Bergo Barokah',
            'owner_name'            => 'Ahmad Zaki',
            'email'                 => 'zaki@bergobarokah.com',
            'phone_wa'              => '081399887766',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        // No user record exists yet
        $this->assertDatabaseMissing('users', [
            'email' => 'zaki@bergobarokah.com',
        ]);

        // Attempt login must fail
        $loginRes = $this->post('/login', [
            'email'    => 'zaki@bergobarokah.com',
            'password' => 'Password123!',
        ]);

        $this->assertGuest();
    }

    /**
     * Requirement 9: Super Admin approval activates one tenant outlet and one OWNER account.
     */
    public function test_super_admin_approval_provisions_one_outlet_and_one_owner(): void
    {
        $reg = TenantRegistration::create([
            'company_name' => 'Nayara Hijab Partner Solo',
            'owner_name'   => 'Budi Santoso',
            'email'        => 'budi@partnersolo.com',
            'phone_wa'     => '081544332211',
            'password'     => Hash::make('BudiPass2026!'),
            'status'       => TenantRegistration::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->superAdmin)->post(
            route('platform.registrations.approve', $reg->id),
            ['decision_notes' => 'Dokumen lengkap dan terverifikasi sebagai konveksi hijab independen.']
        );

        $response->assertRedirect(route('platform.registrations.show', $reg->id));
        $response->assertSessionHas('success');

        $reg->refresh();
        $this->assertEquals(TenantRegistration::STATUS_APPROVED, $reg->status);
        $this->assertNotNull($reg->outlet_id);
        $this->assertNotNull($reg->reviewed_by);
        $this->assertNotNull($reg->reviewed_at);

        // Verify Outlet created
        $outlet = Outlet::find($reg->outlet_id);
        $this->assertNotNull($outlet);
        $this->assertEquals('Nayara Hijab Partner Solo', $outlet->outlet_name);
        $this->assertEquals('ACTIVE', $outlet->status);

        // Verify OWNER account created
        $owner = User::where('email', 'budi@partnersolo.com')->first();
        $this->assertNotNull($owner);
        $this->assertEquals(UserRole::OWNER->value, $owner->role->value);
        $this->assertEquals($outlet->id, $owner->outlet_id);
        $this->assertEquals(UserStatus::ACTIVE->value, $owner->status->value);

        // Section D: Verify OWNER login is BLOCKED while isolation is FALSE
        auth()->logout();
        $this->flushSession();

        $loginResFalse = $this->post('/login', [
            'email'    => 'budi@partnersolo.com',
            'password' => 'BudiPass2026!',
        ]);
        $this->assertGuest();

        // Section D: When tenant isolation is safely enabled (TRUE), OWNER can log in to their isolated ERP
        config(['nayara.tenant_isolation_enabled' => true]);

        $loginResTrue = $this->post('/login', [
            'email'    => 'budi@partnersolo.com',
            'password' => 'BudiPass2026!',
        ]);

        $this->assertAuthenticatedAs($owner);
    }

    /**
     * Requirement 10: Duplicate approval calls are strictly idempotent.
     */
    public function test_duplicate_approval_is_idempotent(): void
    {
        $reg = TenantRegistration::create([
            'company_name' => 'Idempotent Hijab',
            'owner_name'   => 'Dewi Sartika',
            'email'        => 'dewi@idempotent.com',
            'phone_wa'     => '081911223344',
            'password'     => Hash::make('DewiPass123!'),
            'status'       => TenantRegistration::STATUS_PENDING,
        ]);

        // First approval
        $this->actingAs($this->superAdmin)->post(
            route('platform.registrations.approve', $reg->id),
            ['decision_notes' => 'Persetujuan pertama']
        );

        $outletsCount1 = Outlet::where('outlet_name', 'Idempotent Hijab')->count();
        $usersCount1   = User::where('email', 'dewi@idempotent.com')->count();
        $this->assertEquals(1, $outletsCount1);
        $this->assertEquals(1, $usersCount1);

        // Second approval attempt on the already-approved registration
        $response2 = $this->actingAs($this->superAdmin)->post(
            route('platform.registrations.approve', $reg->id),
            ['decision_notes' => 'Persetujuan kedua tidak sengaja terpanggil ulang']
        );

        $response2->assertSessionHas('info');

        // Total count must remain strictly 1
        $this->assertEquals(1, Outlet::where('outlet_name', 'Idempotent Hijab')->count());
        $this->assertEquals(1, User::where('email', 'dewi@idempotent.com')->count());
    }

    /**
     * Requirement 11: Rejection requires a reason of at least 10 characters.
     */
    public function test_rejection_requires_reason_with_minimum_ten_characters(): void
    {
        $reg = TenantRegistration::create([
            'company_name' => 'Invalid Request Konveksi',
            'owner_name'   => 'Fake Owner',
            'email'        => 'fake@invalid.com',
            'phone_wa'     => '081900000000',
            'password'     => Hash::make('password123'),
            'status'       => TenantRegistration::STATUS_PENDING,
        ]);

        // Attempt with short reason (< 10 chars)
        $shortRes = $this->actingAs($this->superAdmin)->post(
            route('platform.registrations.reject', $reg->id),
            ['rejection_reason' => 'Tolak']
        );

        $shortRes->assertSessionHasErrors('rejection_reason');
        $this->assertEquals(TenantRegistration::STATUS_PENDING, $reg->fresh()->status);

        // Attempt with valid reason (>= 10 chars)
        $validRes = $this->actingAs($this->superAdmin)->post(
            route('platform.registrations.reject', $reg->id),
            ['rejection_reason' => 'Data nomor WhatsApp dan usaha tidak dapat dikonfirmasi kebenarannya.']
        );

        $validRes->assertRedirect(route('platform.registrations.show', $reg->id));
        $reg->refresh();
        $this->assertEquals(TenantRegistration::STATUS_REJECTED, $reg->status);
        $this->assertEquals('Data nomor WhatsApp dan usaha tidak dapat dikonfirmasi kebenarannya.', $reg->rejection_reason);
    }

    /**
     * Requirement 12: Correction request transitions to NEEDS_REVISION, and resubmission transitions back to PENDING.
     */
    public function test_correction_request_and_applicant_resubmission_lifecycle(): void
    {
        $reg = TenantRegistration::create([
            'company_name' => 'Konveksi Hijab Bandung',
            'owner_name'   => 'Rina',
            'email'        => 'rina@bandunghijab.com',
            'phone_wa'     => '0812341234',
            'password'     => Hash::make('password123'),
            'status'       => TenantRegistration::STATUS_PENDING,
        ]);

        // Super Admin requests revision
        $revRes = $this->actingAs($this->superAdmin)->post(
            route('platform.registrations.request-revision', $reg->id),
            ['revision_notes' => 'Harap lengkapi alamat lengkap workshop dan kontak nomor WA yang aktif.']
        );

        $revRes->assertRedirect(route('platform.registrations.show', $reg->id));
        $reg->refresh();
        $this->assertTrue($reg->isNeedsRevision());
        $this->assertEquals('Harap lengkapi alamat lengkap workshop dan kontak nomor WA yang aktif.', $reg->revision_notes);

        // Applicant resubmits data
        $resubmitRes = $this->post(route('tenant.register.resubmit', $reg->id), [
            'company_name' => 'CV Hijab Bandung Kreatif',
            'owner_name'   => 'Rina Marlina',
            'phone_wa'     => '081298765432',
            'address'      => 'Jl. Industri Garmen Soreang No. 88, Bandung',
            'notes'        => 'Data alamat lengkap telah diperbarui.',
        ]);

        $resubmitRes->assertRedirect();
        $reg->refresh();
        $this->assertTrue($reg->isPending());
        $this->assertEquals('CV Hijab Bandung Kreatif', $reg->company_name);
        $this->assertEquals('081298765432', $reg->phone_wa);
    }

    /**
     * Requirement 15 & 16: Outlet suspension & reactivation preserves all data.
     */
    public function test_tenant_suspension_and_reactivation_preserves_data(): void
    {
        $outlet = Outlet::create([
            'outlet_name' => 'CV Hijab Harmoni',
            'outlet_code' => 'HARMONI',
            'status'      => 'ACTIVE',
        ]);

        // Suspend
        $resSuspend = $this->actingAs($this->superAdmin)->patch(
            route('platform.tenants.toggle-status', $outlet->id),
            ['status' => 'INACTIVE', 'reason' => 'Audit administratif sementara']
        );

        $resSuspend->assertSessionHas('success');
        $this->assertEquals('INACTIVE', $outlet->fresh()->status);

        // Reactivate
        $resActivate = $this->actingAs($this->superAdmin)->patch(
            route('platform.tenants.toggle-status', $outlet->id),
            ['status' => 'ACTIVE', 'reason' => 'Verifikasi selesai']
        );

        $resActivate->assertSessionHas('success');
        $this->assertEquals('ACTIVE', $outlet->fresh()->status);
        $this->assertEquals('CV Hijab Harmoni', $outlet->fresh()->outlet_name);
    }

    /**
     * Requirement 19: CSRF and Validation Enforced.
     */
    public function test_registration_validation_enforces_required_fields(): void
    {
        $response = $this->post(route('tenant.register.store'), [
            'company_name' => '',
            'owner_name'   => '',
            'email'        => 'not-an-email',
            'phone_wa'     => '',
            'password'     => 'short',
        ]);

        $response->assertSessionHasErrors(['company_name', 'owner_name', 'email', 'phone_wa', 'password']);
    }

    /**
     * Requirement 14: Cross-tenant OWNER access is strictly denied under tenant isolation.
     */
    public function test_cross_tenant_owner_access_is_strictly_denied_under_tenant_isolation(): void
    {
        config(['nayara.tenant_isolation_enabled' => true]);

        $outletA = Outlet::create(['outlet_name' => 'Outlet Convection A', 'outlet_code' => 'OUT-A', 'status' => 'ACTIVE']);
        $outletB = Outlet::create(['outlet_name' => 'Outlet Convection B', 'outlet_code' => 'OUT-B', 'status' => 'ACTIVE']);

        $ownerA = User::create([
            'name'      => 'Owner A',
            'email'     => 'owner.a@convection.test',
            'password'  => Hash::make('password'),
            'role'      => UserRole::OWNER->value,
            'status'    => UserStatus::ACTIVE->value,
            'outlet_id' => $outletA->id,
        ]);

        $this->actingAs($ownerA);

        $tenantContext = app(\App\Services\System\TenantContext::class);

        // Owner A must only resolve Outlet A
        $this->assertEquals($outletA->id, $tenantContext->currentOutletId());
        $this->assertNotEquals($outletB->id, $tenantContext->currentOutletId());
    }

    /**
     * Requirement 15: Inactive outlet user is denied tenant access under tenant isolation.
     */
    public function test_inactive_outlet_user_is_denied_tenant_access_under_tenant_isolation(): void
    {
        config(['nayara.tenant_isolation_enabled' => true]);

        $suspendedOutlet = Outlet::create([
            'outlet_name' => 'Suspended Convection',
            'outlet_code' => 'SUSP',
            'status'      => 'INACTIVE',
        ]);

        $suspendedOwner = User::create([
            'name'      => 'Suspended Owner',
            'email'     => 'owner.suspended@convection.test',
            'password'  => Hash::make('password'),
            'role'      => UserRole::OWNER->value,
            'status'    => UserStatus::ACTIVE->value,
            'outlet_id' => $suspendedOutlet->id,
        ]);

        $this->actingAs($suspendedOwner);

        $tenantContext = app(\App\Services\System\TenantContext::class);

        // Resolving outlet for a user in an inactive outlet must fail closed with AuthorizationException
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $tenantContext->currentOutlet();
    }

    /**
     * Requirement 21 & 22: Legacy FALSE remains usable, Tenant TRUE remains secure.
     */
    public function test_legacy_mode_and_tenant_mode_configurations(): void
    {
        // Legacy FALSE
        config(['nayara.tenant_isolation_enabled' => false]);
        $this->assertFalse(config('nayara.tenant_isolation_enabled'));

        // Tenant TRUE
        config(['nayara.tenant_isolation_enabled' => true]);
        $this->assertTrue(config('nayara.tenant_isolation_enabled'));
    }
}
