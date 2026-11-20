<?php

namespace Tests\Feature\System;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\System\Outlet;
use App\Models\System\User;
use App\Services\System\TenantContext;
use Database\Seeders\LegacyOutletSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class TenantContextResolutionTest extends TestCase
{
    use RefreshDatabase;

    protected TenantContext $tenantContext;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantContext = new TenantContext();
    }

    /**
     * Helper membuat outlet uji.
     */
    protected function createTestOutlet(string $code = 'OUT-A', string $name = 'Outlet A', string $status = 'ACTIVE'): Outlet
    {
        return Outlet::create([
            'outlet_name'                => $name,
            'outlet_code'                => $code,
            'slug'                       => strtolower($code),
            'status'                     => $status,
            'is_public_showcase_enabled' => true,
        ]);
    }

    /**
     * Helper membuat user uji dengan role dan outlet tertentu.
     */
    protected function createTestUser(?Outlet $outlet, UserRole $role = UserRole::ADMIN, UserStatus $status = UserStatus::ACTIVE, string $emailSuffix = '01'): User
    {
        return User::factory()->create([
            'name'      => "User {$role->value} {$emailSuffix}",
            'email'     => "user.{$emailSuffix}@nayara.test",
            'role'      => $role,
            'status'    => $status,
            'outlet_id' => $outlet?->id,
        ]);
    }

    /**
     * 1. User Outlet A berhasil memperoleh Outlet A.
     */
    public function test_01_user_from_outlet_a_resolves_to_outlet_a(): void
    {
        $outletA = $this->createTestOutlet('OUT-A', 'Konveksi Nayara Jakarta');
        $userA = $this->createTestUser($outletA, UserRole::ADMIN);

        $resolved = $this->tenantContext->outletFor($userA);

        $this->assertInstanceOf(Outlet::class, $resolved);
        $this->assertEquals($outletA->id, $resolved->id);
        $this->assertEquals('OUT-A', $resolved->outlet_code);
        $this->assertEquals($outletA->id, $this->tenantContext->outletIdFor($userA));
    }

    /**
     * 2. User Outlet B berhasil memperoleh Outlet B.
     */
    public function test_02_user_from_outlet_b_resolves_to_outlet_b(): void
    {
        $outletB = $this->createTestOutlet('OUT-B', 'Konveksi Nayara Bandung');
        $userB = $this->createTestUser($outletB, UserRole::ADMIN, UserStatus::ACTIVE, '02');

        $resolved = $this->tenantContext->outletFor($userB);

        $this->assertInstanceOf(Outlet::class, $resolved);
        $this->assertEquals($outletB->id, $resolved->id);
        $this->assertEquals('OUT-B', $resolved->outlet_code);
        $this->assertEquals($outletB->id, $this->tenantContext->outletIdFor($userB));
    }

    /**
     * 3. Kedua outlet tidak tertukar satu sama lain.
     */
    public function test_03_outlets_do_not_cross_or_swap_between_users(): void
    {
        $outletA = $this->createTestOutlet('OUT-A', 'Konveksi Nayara Jakarta');
        $outletB = $this->createTestOutlet('OUT-B', 'Konveksi Nayara Bandung');

        $userA = $this->createTestUser($outletA, UserRole::ADMIN, UserStatus::ACTIVE, 'a');
        $userB = $this->createTestUser($outletB, UserRole::GUDANG, UserStatus::ACTIVE, 'b');

        $this->assertEquals($outletA->id, $this->tenantContext->outletFor($userA)->id);
        $this->assertEquals($outletB->id, $this->tenantContext->outletFor($userB)->id);
        $this->assertNotEquals($this->tenantContext->outletFor($userA)->id, $this->tenantContext->outletFor($userB)->id);
    }

    /**
     * 4. User dengan outlet_id NULL ditolak pada resolusi wajib.
     */
    public function test_04_user_with_null_outlet_id_is_rejected_fail_closed(): void
    {
        $userWithoutOutlet = $this->createTestUser(null, UserRole::ADMIN);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('tidak terikat ke outlet');

        $this->tenantContext->outletFor($userWithoutOutlet);
    }

    /**
     * 5. Outlet dengan status INACTIVE ditolak pada resolusi wajib.
     */
    public function test_05_inactive_outlet_is_rejected_fail_closed(): void
    {
        $inactiveOutlet = $this->createTestOutlet('OUT-INACTIVE', 'Outlet Nonaktif', 'INACTIVE');
        $user = $this->createTestUser($inactiveOutlet, UserRole::ADMIN);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('dinonaktifkan');

        $this->tenantContext->outletFor($user);
    }

    /**
     * 6. Outlet yang tidak valid / tidak ada di database ditolak.
     */
    public function test_06_non_existent_outlet_is_rejected_fail_closed(): void
    {
        $user = $this->createTestUser(null, UserRole::ADMIN);
        $user->outlet_id = 999999; // Non-existent foreign key in memory

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('tidak ditemukan');

        $this->tenantContext->outletFor($user);
    }

    /**
     * 7. Parameter request outlet_id tidak bisa mengubah tenant yang di-resolve.
     */
    public function test_07_request_body_outlet_id_cannot_manipulate_tenant(): void
    {
        $legitimateOutlet = $this->createTestOutlet('OUT-REAL', 'Outlet Sah');
        $attackerOutlet = $this->createTestOutlet('OUT-FAKE', 'Outlet Palsu');

        $user = $this->createTestUser($legitimateOutlet, UserRole::ADMIN);

        // Simulasi input request manipulatif dari client
        request()->merge(['outlet_id' => $attackerOutlet->id]);

        $resolved = $this->tenantContext->outletFor($user);

        // Harus tetap mengembalikan outlet sah dari database user
        $this->assertEquals($legitimateOutlet->id, $resolved->id);
        $this->assertNotEquals($attackerOutlet->id, $resolved->id);
    }

    /**
     * 8. HTTP header X-Outlet-ID tidak bisa mengubah tenant yang di-resolve.
     */
    public function test_08_http_header_x_outlet_id_cannot_manipulate_tenant(): void
    {
        $legitimateOutlet = $this->createTestOutlet('OUT-REAL', 'Outlet Sah');
        $attackerOutlet = $this->createTestOutlet('OUT-FAKE', 'Outlet Palsu');

        $user = $this->createTestUser($legitimateOutlet, UserRole::ADMIN);

        // Simulasi header injeksi
        request()->headers->set('X-Outlet-ID', (string) $attackerOutlet->id);

        $resolved = $this->tenantContext->outletFor($user);

        $this->assertEquals($legitimateOutlet->id, $resolved->id);
        $this->assertNotEquals($attackerOutlet->id, $resolved->id);
    }

    /**
     * 9. Manipulasi query parameter (?outlet_id=999) tidak bisa mengubah tenant.
     */
    public function test_09_query_parameter_cannot_manipulate_tenant(): void
    {
        $legitimateOutlet = $this->createTestOutlet('OUT-REAL', 'Outlet Sah');
        $attackerOutlet = $this->createTestOutlet('OUT-FAKE', 'Outlet Palsu');

        $user = $this->createTestUser($legitimateOutlet, UserRole::ADMIN);

        // Simulasi query string
        request()->query->set('outlet_id', (string) $attackerOutlet->id);

        $resolved = $this->tenantContext->outletFor($user);

        $this->assertEquals($legitimateOutlet->id, $resolved->id);
        $this->assertNotEquals($attackerOutlet->id, $resolved->id);
    }

    /**
     * 10. Akun Owner hanya di-resolve ke outlet miliknya dan tidak bisa melintasi outlet lain.
     */
    public function test_10_owner_account_only_resolves_to_their_owned_outlet(): void
    {
        $ownerOutlet = $this->createTestOutlet('OUT-OWNER', 'Konveksi Nayara Owner');
        $otherOutlet = $this->createTestOutlet('OUT-OTHER', 'Konveksi Lain');

        $owner = $this->createTestUser($ownerOutlet, UserRole::OWNER);

        $resolved = $this->tenantContext->outletFor($owner);

        $this->assertEquals($ownerOutlet->id, $resolved->id);
        $this->assertNotEquals($otherOutlet->id, $resolved->id);
        $this->assertTrue($owner->isOwner());
    }

    /**
     * 11. ADMIN, GUDANG, PRODUKSI tetap mengikuti outlet masing-masing.
     */
    public function test_11_operational_roles_consistently_resolve_to_their_assigned_outlets(): void
    {
        $outletA = $this->createTestOutlet('OUT-A', 'Outlet A');
        $outletB = $this->createTestOutlet('OUT-B', 'Outlet B');

        $adminA = $this->createTestUser($outletA, UserRole::ADMIN, UserStatus::ACTIVE, 'adm-a');
        $gudangA = $this->createTestUser($outletA, UserRole::GUDANG, UserStatus::ACTIVE, 'gdg-a');
        $prodB = $this->createTestUser($outletB, UserRole::PRODUKSI, UserStatus::ACTIVE, 'prd-b');

        $this->assertEquals($outletA->id, $this->tenantContext->outletFor($adminA)->id);
        $this->assertEquals($outletA->id, $this->tenantContext->outletFor($gudangA)->id);
        $this->assertEquals($outletB->id, $this->tenantContext->outletFor($prodB)->id);
    }

    /**
     * 12. Resolusi tenant tidak mengubah role atau permission pengguna.
     */
    public function test_12_tenant_resolution_does_not_modify_user_role_or_privileges(): void
    {
        $outlet = $this->createTestOutlet('OUT-TEST', 'Outlet Test');
        $user = $this->createTestUser($outlet, UserRole::GUDANG);

        $this->assertEquals(UserRole::GUDANG, $user->role);
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->isOwner());

        $this->tenantContext->outletFor($user);

        $freshUser = $user->fresh();
        $this->assertEquals(UserRole::GUDANG, $freshUser->role);
        $this->assertFalse($freshUser->isAdmin());
        $this->assertFalse($freshUser->isOwner());
    }

    /**
     * 13. Tenant Context tidak menggunakan hardcoded outlet ID (misal ID 1).
     */
    public function test_13_tenant_context_does_not_use_hardcoded_id_one(): void
    {
        $outlet = new Outlet([
            'outlet_name'                => 'Outlet Khusus ID 55',
            'outlet_code'                => 'OUT-55',
            'slug'                       => 'out-55',
            'status'                     => 'ACTIVE',
            'is_public_showcase_enabled' => true,
        ]);
        $outlet->id = 55;
        $outlet->save();

        $user = $this->createTestUser($outlet, UserRole::ADMIN);

        $this->assertEquals(55, $this->tenantContext->outletIdFor($user));
        $this->assertEquals('OUT-55', $this->tenantContext->outletFor($user)->outlet_code);
    }

    /**
     * 14. Tidak ada state tenant yang bocor antar-user atau antar-panggilan.
     */
    public function test_14_no_tenant_state_leakage_between_sequential_resolutions(): void
    {
        $outletA = $this->createTestOutlet('OUT-A', 'Outlet A');
        $outletB = $this->createTestOutlet('OUT-B', 'Outlet B');

        $userA = $this->createTestUser($outletA, UserRole::ADMIN, UserStatus::ACTIVE, 'a');
        $userB = $this->createTestUser($outletB, UserRole::ADMIN, UserStatus::ACTIVE, 'b');

        // Panggilan bergantian pada instance yang sama
        $this->assertEquals($outletA->id, $this->tenantContext->outletFor($userA)->id);
        $this->assertEquals($outletB->id, $this->tenantContext->outletFor($userB)->id);
        $this->assertEquals($outletA->id, $this->tenantContext->outletFor($userA)->id);
        $this->assertEquals($outletB->id, $this->tenantContext->outletFor($userB)->id);

        // Panggilan statis bergantian tanpa menyimpan mutable state
        $this->assertEquals($outletA->id, TenantContext::resolveOutletId($userA));
        $this->assertEquals($outletB->id, TenantContext::resolveOutletId($userB));
    }

    /**
     * 15. Customer existing tidak otomatis memperoleh akses ERP internal hanya karena memiliki outlet.
     */
    public function test_15_customer_with_outlet_does_not_acquire_internal_erp_privileges(): void
    {
        $outlet = $this->createTestOutlet('OUT-TEST', 'Outlet Test');
        $customerUser = $this->createTestUser($outlet, UserRole::CUSTOMER);

        $resolved = $this->tenantContext->outletFor($customerUser);

        $this->assertEquals($outlet->id, $resolved->id);
        $this->assertTrue($customerUser->isCustomer());
        $this->assertFalse($customerUser->isInternal());
        $this->assertFalse($customerUser->isAdmin());
        $this->assertFalse($customerUser->isOwner());
    }

    /**
     * 16. Database tanpa outlet valid tidak memperoleh fallback tenant apa pun.
     */
    public function test_16_empty_database_does_not_provide_any_fallback_tenant(): void
    {
        $this->assertDatabaseCount('outlets', 0);

        $this->expectException(AuthorizationException::class);

        // Tidak ada authenticated user
        $this->tenantContext->currentOutlet();
    }

    /**
     * 17. Outlet legacy (OUT-NAYARA-PUSAT) tidak otomatis menjadi tenant default bagi semua pengguna.
     */
    public function test_17_legacy_outlet_is_never_used_as_silent_fallback_for_unassigned_users(): void
    {
        // Ada outlet legacy di database
        $legacyOutlet = $this->createTestOutlet(
            LegacyOutletSeeder::LEGACY_OUTLET_CODE,
            LegacyOutletSeeder::LEGACY_OUTLET_NAME
        );

        // User baru tanpa outlet_id
        $unassignedUser = $this->createTestUser(null, UserRole::ADMIN);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('tidak terikat ke outlet');

        // Resolusi wajib menolak, BUKAN fallback diam-diam ke legacyOutlet
        $this->tenantContext->outletFor($unassignedUser);
    }

    /**
     * 18. Resolusi tenant bersifat read-only dan tidak melakukan mutasi terhadap database.
     */
    public function test_18_tenant_resolution_does_not_mutate_database(): void
    {
        $outlet = $this->createTestOutlet('OUT-TEST', 'Outlet Test');
        $user = $this->createTestUser($outlet, UserRole::ADMIN);

        $initialOutletsCount = Outlet::count();
        $initialUsersCount = User::count();

        $this->tenantContext->outletFor($user);
        $this->tenantContext->outletIdFor($user);
        $this->tenantContext->hasValidOutlet($user);

        $this->assertEquals($initialOutletsCount, Outlet::count());
        $this->assertEquals($initialUsersCount, User::count());
        $this->assertEquals($outlet->updated_at->timestamp, $outlet->fresh()->updated_at->timestamp);
        $this->assertEquals($user->updated_at->timestamp, $user->fresh()->updated_at->timestamp);
    }
}
