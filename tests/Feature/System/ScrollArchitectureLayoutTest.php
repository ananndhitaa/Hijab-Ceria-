<?php

namespace Tests\Feature\System;

use App\Enums\UserRole;
use App\Models\System\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScrollArchitectureLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_authenticated_shell_uses_authoritative_single_scroll_layout(): void
    {
        $gudang = User::where('role', UserRole::GUDANG)->first();
        $response = $this->actingAs($gudang)->get(route('gudang.material-planning.index'));

        $response->assertOk();

        // 1. html and body layout
        $response->assertSee('<html lang="id">', false);
        $response->assertSee('<body class="bg-page-bg text-text-main antialiased min-h-screen overflow-x-hidden">', false);

        // 2. Sidebar is pinned full viewport height with independent navigation scroll
        $response->assertSee('h-screen h-dvh shrink-0', false);
        $response->assertSee('id="nayara-sidebar-nav"', false);

        // 3. Main content container
        $response->assertSee('<main id="main-content" class="flex-1 min-w-0 p-4 sm:p-6">', false);
    }

    public function test_guest_layout_does_not_force_overflow_hidden(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        // guest layout should not have overflow-hidden
        $response->assertDontSee('<html lang="id" class="h-full overflow-hidden">', false);
        $response->assertDontSee('<body class="bg-page-bg text-text-main antialiased h-full overflow-hidden">', false);
    }

    public function test_role_regression_layout_integrity_for_all_roles(): void
    {
        $roles = [
            UserRole::ADMIN->value => [
                'user' => User::where('role', UserRole::ADMIN)->first(),
                'routes' => [
                    route('admin.dashboard'),
                    route('admin.financial-records.index'),
                ],
            ],
            UserRole::GUDANG->value => [
                'user' => User::where('role', UserRole::GUDANG)->first(),
                'routes' => [
                    route('gudang.dashboard'),
                    route('gudang.material-planning.index'),
                    route('gudang.purchases.index'),
                ],
            ],
            UserRole::PRODUKSI->value => [
                'user' => User::where('role', UserRole::PRODUKSI)->first(),
                'routes' => [
                    route('produksi.dashboard'),
                ],
            ],
            UserRole::OWNER->value => [
                'user' => User::where('role', UserRole::OWNER)->first(),
                'routes' => [
                    route('owner.dashboard'),
                    route('owner.performance.index'),
                ],
            ],
            UserRole::CUSTOMER->value => [
                'user' => User::where('role', UserRole::CUSTOMER)->first(),
                'routes' => [
                    route('customer.dashboard'),
                ],
            ],
        ];

        foreach ($roles as $roleKey => $roleConfig) {
            $user = $roleConfig['user'];
            $this->assertNotNull($user, "User for role {$roleKey} must exist in seed.");

            foreach ($roleConfig['routes'] as $url) {
                $response = $this->actingAs($user)->get($url);
                $response->assertOk();
                $response->assertSee('<html lang="id">', false);
                $response->assertSee('<main id="main-content"', false);
            }
        }
    }
}
