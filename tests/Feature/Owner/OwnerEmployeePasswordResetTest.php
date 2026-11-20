<?php

namespace Tests\Feature\Owner;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\System\Outlet;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OwnerEmployeePasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private Outlet $outlet;
    private User $owner;
    private User $admin;
    private User $gudang;
    private User $produksi;
    private User $customerUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outlet = Outlet::create([
            'outlet_name' => 'Nayara Hijab Kudus',
            'outlet_code' => 'OUT-KUDUS',
            'status'      => 'ACTIVE',
        ]);

        $this->owner = User::factory()->create([
            'outlet_id' => $this->outlet->id,
            'name'      => 'Owner Nayara',
            'email'     => 'owner@nayara.test',
            'role'      => UserRole::OWNER,
            'status'    => UserStatus::ACTIVE,
            'password'  => bcrypt('ownerpassword'),
        ]);

        $this->admin = User::factory()->create([
            'outlet_id' => $this->outlet->id,
            'name'      => 'Admin Nayara',
            'email'     => 'admin@nayara.test',
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::ACTIVE,
            'password'  => bcrypt('adminoldpassword'),
        ]);

        $this->gudang = User::factory()->create([
            'outlet_id' => $this->outlet->id,
            'name'      => 'Gudang Nayara',
            'email'     => 'gudang@nayara.test',
            'role'      => UserRole::GUDANG,
            'status'    => UserStatus::ACTIVE,
            'password'  => bcrypt('gudangoldpassword'),
        ]);

        $this->produksi = User::factory()->create([
            'outlet_id' => $this->outlet->id,
            'name'      => 'Produksi Nayara',
            'email'     => 'produksi@nayara.test',
            'role'      => UserRole::PRODUKSI,
            'status'    => UserStatus::ACTIVE,
            'password'  => bcrypt('produksioldpassword'),
        ]);

        $this->customerUser = User::factory()->create([
            'outlet_id' => $this->outlet->id,
            'name'      => 'Customer Buyer',
            'email'     => 'customer@buyer.test',
            'role'     => UserRole::CUSTOMER,
            'status'   => UserStatus::ACTIVE,
            'password' => bcrypt('customeroldpassword'),
        ]);
    }

    // =========================================================================
    // 17. TESTS — ADMIN REMOVAL
    // =========================================================================

    /**
     * 1. Admin sidebar does NOT show: Reset Password.
     */
    public function test_01_admin_sidebar_does_not_show_reset_password(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertDontSee('Reset Password');
        $response->assertDontSee('/admin/password-resets');
    }

    /**
     * 2. Admin sidebar does NOT show empty: Manajemen User if no other menu remains.
     */
    public function test_02_admin_sidebar_does_not_show_empty_manajemen_user_section(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertDontSee('Manajemen User');
    }

    /**
     * 3. Admin cannot access old reset password route (404 Not Found).
     */
    public function test_03_admin_cannot_access_old_reset_password_route(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/password-resets');
        $response->assertNotFound();

        $postResponse = $this->actingAs($this->admin)->post('/admin/password-resets/1/process', [
            'password'              => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);
        $postResponse->assertNotFound();
    }

    /**
     * 4. Admin cannot reset another employee password (403 Forbidden).
     */
    public function test_04_admin_cannot_reset_another_employee_password(): void
    {
        $response = $this->actingAs($this->admin)->post(
            route('owner.employees.reset-password', $this->gudang),
            [
                'password'              => 'adminHackedPassword123',
                'password_confirmation' => 'adminHackedPassword123',
            ]
        );

        $response->assertForbidden();

        // Ensure Gudang password was NOT modified
        $this->assertTrue(Hash::check('gudangoldpassword', $this->gudang->fresh()->password));
    }

    // =========================================================================
    // 18. TESTS — OWNER RESET PASSWORD
    // =========================================================================

    /**
     * 5. Owner can open employee detail and see reset password action.
     */
    public function test_05_owner_can_open_employee_detail(): void
    {
        $response = $this->actingAs($this->owner)->get(route('owner.employees.show', $this->produksi));

        $response->assertOk();
        $response->assertSee($this->produksi->name);
        $response->assertSee('Edit Karyawan');
        $response->assertSee('Reset Password');
        $response->assertSee(route('owner.employees.reset-password', $this->produksi));
    }

    /**
     * 6. Owner can reset Admin employee password.
     */
    public function test_06_owner_can_reset_admin_employee_password(): void
    {
        $response = $this->actingAs($this->owner)->post(
            route('owner.employees.reset-password', $this->admin),
            [
                'password'              => 'newAdminPass123',
                'password_confirmation' => 'newAdminPass123',
            ]
        );

        $response->assertRedirect(route('owner.employees.show', $this->admin));
        $response->assertSessionHas('success');

        $this->assertTrue(Hash::check('newAdminPass123', $this->admin->fresh()->password));
    }

    /**
     * 7. Owner can reset Gudang employee password.
     */
    public function test_07_owner_can_reset_gudang_employee_password(): void
    {
        $response = $this->actingAs($this->owner)->post(
            route('owner.employees.reset-password', $this->gudang),
            [
                'password'              => 'newGudangPass123',
                'password_confirmation' => 'newGudangPass123',
            ]
        );

        $response->assertRedirect(route('owner.employees.show', $this->gudang));
        $response->assertSessionHas('success');

        $this->assertTrue(Hash::check('newGudangPass123', $this->gudang->fresh()->password));
    }

    /**
     * 8. Owner can reset Produksi employee password.
     */
    public function test_08_owner_can_reset_produksi_employee_password(): void
    {
        $response = $this->actingAs($this->owner)->post(
            route('owner.employees.reset-password', $this->produksi),
            [
                'password'              => 'newProduksiPass123',
                'password_confirmation' => 'newProduksiPass123',
            ]
        );

        $response->assertRedirect(route('owner.employees.show', $this->produksi));
        $response->assertSessionHas('success');

        $this->assertTrue(Hash::check('newProduksiPass123', $this->produksi->fresh()->password));
    }

    /**
     * 9. Password stored hashed, never plaintext.
     */
    public function test_09_password_is_stored_hashed_not_plaintext(): void
    {
        $this->actingAs($this->owner)->post(
            route('owner.employees.reset-password', $this->gudang),
            [
                'password'              => 'secureHashPass123',
                'password_confirmation' => 'secureHashPass123',
            ]
        );

        $rawPassword = \DB::table('users')->where('id', $this->gudang->id)->value('password');

        $this->assertNotSame('secureHashPass123', $rawPassword);
        $this->assertTrue(Hash::check('secureHashPass123', $rawPassword));
    }

    /**
     * 10. Password confirmation validation works.
     */
    public function test_10_password_confirmation_validation_works(): void
    {
        // Mismatch confirmation
        $responseMismatch = $this->actingAs($this->owner)->post(
            route('owner.employees.reset-password', $this->produksi),
            [
                'password'              => 'validPassword123',
                'password_confirmation' => 'mismatchingConfirmation',
            ]
        );

        $responseMismatch->assertSessionHasErrors(['password']);
        $this->assertTrue(Hash::check('produksioldpassword', $this->produksi->fresh()->password));

        // Too short (< 8 chars)
        $responseShort = $this->actingAs($this->owner)->post(
            route('owner.employees.reset-password', $this->produksi),
            [
                'password'              => 'short',
                'password_confirmation' => 'short',
            ]
        );

        $responseShort->assertSessionHasErrors(['password']);
        $this->assertTrue(Hash::check('produksioldpassword', $this->produksi->fresh()->password));
    }

    /**
     * 11. Old password no longer authenticates after successful reset.
     */
    public function test_11_old_password_no_longer_authenticates_after_reset(): void
    {
        $this->actingAs($this->owner)->post(
            route('owner.employees.reset-password', $this->admin),
            [
                'password'              => 'newFreshAdminPass123',
                'password_confirmation' => 'newFreshAdminPass123',
            ]
        );

        // Logout owner
        $this->post(route('logout'));

        // Attempt login with old password
        $this->post('/login', [
            'email'    => $this->admin->email,
            'password' => 'adminoldpassword',
        ]);

        $this->assertGuest();
    }

    /**
     * 12. New password authenticates successfully.
     */
    public function test_12_new_password_authenticates_successfully(): void
    {
        $this->actingAs($this->owner)->post(
            route('owner.employees.reset-password', $this->admin),
            [
                'password'              => 'newFreshAdminPass123',
                'password_confirmation' => 'newFreshAdminPass123',
            ]
        );

        // Logout owner
        $this->post(route('logout'));

        // Attempt login with new password
        $response = $this->post('/login', [
            'email'    => $this->admin->email,
            'password' => 'newFreshAdminPass123',
        ]);

        $this->assertAuthenticated();
        $this->assertSame($this->admin->id, auth()->id());
    }

    /**
     * 13. Owner cannot reset Customer through employee reset flow.
     */
    public function test_13_owner_cannot_reset_customer_through_employee_flow(): void
    {
        $response = $this->actingAs($this->owner)->post(
            route('owner.employees.reset-password', $this->customerUser),
            [
                'password'              => 'newCustomerPass123',
                'password_confirmation' => 'newCustomerPass123',
            ]
        );

        // Blocked with 403 Forbidden
        $response->assertForbidden();

        // Customer password must remain unchanged
        $this->assertTrue(Hash::check('customeroldpassword', $this->customerUser->fresh()->password));
    }

    /**
     * 14. Owner cannot reset Owner through employee management flow.
     */
    public function test_14_owner_cannot_reset_owner_through_employee_flow(): void
    {
        $otherOwner = User::factory()->create([
            'name'     => 'Second Owner',
            'email'    => 'second.owner@nayara.test',
            'role'     => UserRole::OWNER,
            'status'   => UserStatus::ACTIVE,
            'password' => bcrypt('otherownerpass'),
        ]);

        $response = $this->actingAs($this->owner)->post(
            route('owner.employees.reset-password', $otherOwner),
            [
                'password'              => 'attemptedOwnerReset123',
                'password_confirmation' => 'attemptedOwnerReset123',
            ]
        );

        // Blocked with 403 Forbidden
        $response->assertForbidden();

        // Target owner password must remain unchanged
        $this->assertTrue(Hash::check('otherownerpass', $otherOwner->fresh()->password));
    }

    /**
     * 15. Non-Owner receives 403 for employee password reset endpoint.
     */
    public function test_15_non_owner_receives_403_for_employee_password_reset_endpoint(): void
    {
        // Admin -> 403
        $this->actingAs($this->admin)->post(
            route('owner.employees.reset-password', $this->produksi),
            [
                'password'              => 'unauthorizedPass123',
                'password_confirmation' => 'unauthorizedPass123',
            ]
        )->assertForbidden();

        // Gudang -> 403
        $this->actingAs($this->gudang)->post(
            route('owner.employees.reset-password', $this->produksi),
            [
                'password'              => 'unauthorizedPass123',
                'password_confirmation' => 'unauthorizedPass123',
            ]
        )->assertForbidden();

        // Produksi -> 403
        $this->actingAs($this->produksi)->post(
            route('owner.employees.reset-password', $this->gudang),
            [
                'password'              => 'unauthorizedPass123',
                'password_confirmation' => 'unauthorizedPass123',
            ]
        )->assertForbidden();

        // Customer -> 403
        $this->actingAs($this->customerUser)->post(
            route('owner.employees.reset-password', $this->produksi),
            [
                'password'              => 'unauthorizedPass123',
                'password_confirmation' => 'unauthorizedPass123',
            ]
        )->assertForbidden();

        // Target passwords remained unchanged
        $this->assertTrue(Hash::check('produksioldpassword', $this->produksi->fresh()->password));
        $this->assertTrue(Hash::check('gudangoldpassword', $this->gudang->fresh()->password));
    }
}
