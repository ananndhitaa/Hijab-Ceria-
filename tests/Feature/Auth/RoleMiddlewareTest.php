<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(UserRole $role): User
    {
        return User::create([
            'name'     => $role->label() . ' User',
            'email'    => strtolower($role->value) . '@test.com',
            'password' => bcrypt('password'),
            'role'     => $role->value,
            'status'   => UserStatus::ACTIVE->value,
        ]);
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = $this->createUser(UserRole::ADMIN);

        $response = $this->actingAs($admin)->get('/admin/dashboard');
        $response->assertStatus(200);
    }

    public function test_admin_cannot_access_owner_dashboard(): void
    {
        $admin = $this->createUser(UserRole::ADMIN);

        $response = $this->actingAs($admin)->get('/owner/dashboard');
        $response->assertStatus(403);
    }

    public function test_gudang_cannot_access_admin_dashboard(): void
    {
        $gudang = $this->createUser(UserRole::GUDANG);

        $response = $this->actingAs($gudang)->get('/admin/dashboard');
        $response->assertStatus(403);
    }

    public function test_produksi_can_access_produksi_dashboard(): void
    {
        $produksi = $this->createUser(UserRole::PRODUKSI);

        $response = $this->actingAs($produksi)->get('/produksi/dashboard');
        $response->assertStatus(200);
    }

    public function test_owner_can_access_owner_dashboard(): void
    {
        $owner = $this->createUser(UserRole::OWNER);

        $response = $this->actingAs($owner)->get('/owner/dashboard');
        $response->assertStatus(200);
    }
}
