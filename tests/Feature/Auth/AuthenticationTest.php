<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::create([
            'name'     => 'Test Admin',
            'email'    => 'admin@test.com',
            'password' => bcrypt('password'),
            'role'     => UserRole::ADMIN->value,
            'status'   => UserStatus::ACTIVE->value,
        ]);

        $response = $this->post('/login', [
            'email'    => 'admin@test.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        User::create([
            'name'     => 'Test Admin',
            'email'    => 'admin@test.com',
            'password' => bcrypt('password'),
            'role'     => UserRole::ADMIN->value,
            'status'   => UserStatus::ACTIVE->value,
        ]);

        $this->post('/login', [
            'email'    => 'admin@test.com',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_inactive_users_cannot_login(): void
    {
        User::create([
            'name'     => 'Inactive User',
            'email'    => 'inactive@test.com',
            'password' => bcrypt('password'),
            'role'     => UserRole::ADMIN->value,
            'status'   => UserStatus::INACTIVE->value,
        ]);

        $this->post('/login', [
            'email'    => 'inactive@test.com',
            'password' => 'password',
        ]);

        $this->assertGuest();
    }
}
