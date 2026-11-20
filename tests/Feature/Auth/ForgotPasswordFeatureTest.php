<?php

namespace Tests\Feature\Auth;

use App\Enums\PasswordResetStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\System\PasswordResetRequest;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ForgotPasswordFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'name'     => 'Test User',
            'email'    => 'testuser@example.com',
            'password' => bcrypt('oldpassword'),
            'role'     => UserRole::GUDANG->value,
            'status'   => UserStatus::ACTIVE->value,
        ], $overrides));
    }

    private function createAdmin(): User
    {
        return User::create([
            'name'     => 'Admin User',
            'email'    => 'admin@test.com',
            'password' => bcrypt('password'),
            'role'     => UserRole::ADMIN->value,
            'status'   => UserStatus::ACTIVE->value,
        ]);
    }

    // ── 1. Forgot Password Page Accessible by Guest ──────────────────

    public function test_forgot_password_page_is_accessible_by_guest(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertOk();
        $response->assertSee('Lupa Password');
        $response->assertSee('Kirim Permintaan Reset');
    }

    // ── 2. Registered Email Creates PENDING Request ──────────────────

    public function test_registered_email_creates_pending_request(): void
    {
        $user = $this->createUser();

        $response = $this->post(route('password.request.submit'), [
            'email' => $user->email,
        ]);

        $response->assertRedirect(route('password.request'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('password_reset_requests', [
            'user_id' => $user->id,
            'status'  => PasswordResetStatus::PENDING->value,
        ]);
    }

    // ── 3. Unregistered Email Shows Generic Response ─────────────────

    public function test_unregistered_email_shows_generic_response_without_creating_request(): void
    {
        $response = $this->post(route('password.request.submit'), [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertRedirect(route('password.request'));
        $response->assertSessionHas('status');

        $this->assertDatabaseCount('password_reset_requests', 0);
    }

    // ── 4. Duplicate Pending Request Is Not Created ──────────────────

    public function test_duplicate_pending_request_is_not_created(): void
    {
        $user = $this->createUser();

        // First request
        $this->post(route('password.request.submit'), ['email' => $user->email]);
        $this->assertDatabaseCount('password_reset_requests', 1);

        // Second request with same email
        RateLimiter::clear('forgot-password|' . strtolower($user->email) . '|127.0.0.1');
        $this->post(route('password.request.submit'), ['email' => $user->email]);

        // Still only one pending
        $this->assertDatabaseCount('password_reset_requests', 1);
    }

    // ── 5. Rate Limiting Works ───────────────────────────────────────

    public function test_rate_limiting_prevents_excessive_requests(): void
    {
        $email = 'ratelimit-test@example.com';

        for ($i = 0; $i < 3; $i++) {
            $response = $this->post(route('password.request.submit'), ['email' => $email]);
            $response->assertRedirect();
        }

        $response = $this->post(route('password.request.submit'), ['email' => $email]);
        $response->assertStatus(429);
    }

    // ── 6. CSRF Protection Active ───────────────────────────────────

    public function test_csrf_protection_on_forgot_password_form(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        $response = $this->call('POST', route('password.request.submit'), ['email' => 'test@test.com']);
        $response->assertRedirect();
    }

    // ── 7. Existing Login and Logout Still Work ─────────────────────

    public function test_existing_login_and_logout_still_work(): void
    {
        $user = $this->createUser();

        $response = $this->post('/login', [
            'email'    => $user->email,
            'password' => 'oldpassword',
        ]);
        $this->assertAuthenticated();
        $response->assertRedirect(route('gudang.dashboard'));

        $this->post(route('logout'));
        $this->assertGuest();
    }

    // ── 8. Login Page Shows Forgot Password Link ─────────────────────

    public function test_login_page_shows_forgot_password_link(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('Lupa Password?');
        $response->assertSee(route('password.request'));
    }

    // ── 9. Email validation on forgot password form ──────────────

    public function test_forgot_password_validates_email_format(): void
    {
        $response = $this->post(route('password.request.submit'), [
            'email' => 'not-an-email',
        ]);

        $response->assertSessionHasErrors('email');
    }

    // ── 10. New pending request allowed after previous completed ──

    public function test_new_request_allowed_after_previous_completed(): void
    {
        $user = $this->createUser();

        PasswordResetRequest::create([
            'user_id'      => $user->id,
            'status'       => PasswordResetStatus::COMPLETED,
            'requested_at' => now()->subDay(),
            'processed_at' => now(),
        ]);

        $this->post(route('password.request.submit'), ['email' => $user->email]);

        $this->assertSame(1, PasswordResetRequest::where('user_id', $user->id)
            ->where('status', PasswordResetStatus::PENDING)->count());
    }

    // ── 11. Admin Old Password Reset Route is 404 (Removed) ──────────

    public function test_admin_cannot_access_removed_password_resets_route(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get('/admin/password-resets');

        $response->assertNotFound();
    }

    // ── 12. Admin Sidebar Does Not Show Reset Password or Manajemen User ─

    public function test_admin_sidebar_does_not_show_reset_password_or_manajemen_user(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertDontSee('Manajemen User');
        $response->assertDontSee('Reset Password');
        $response->assertDontSee('/admin/password-resets');
    }
}
