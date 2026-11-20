<?php

namespace Tests\Feature\Platform;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Platform\PlatformNotification;
use App\Models\System\Outlet;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlatformGovernanceAndUiTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected Outlet $outlet;
    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        config(['nayara.tenant_isolation_enabled' => false]);

        $this->superAdmin = User::create([
            'name'      => 'Platform Master',
            'email'     => 'superadmin@nayara.test',
            'password'  => Hash::make('Secret123!'),
            'role'      => UserRole::SUPER_ADMIN->value,
            'status'    => UserStatus::ACTIVE->value,
            'outlet_id' => null,
        ]);

        $this->outlet = Outlet::create([
            'outlet_name' => 'Nayara Hijab Test',
            'outlet_code' => 'OUT-TEST',
            'status'      => 'ACTIVE',
        ]);

        $this->owner = User::create([
            'name'      => 'Owner Test',
            'email'     => 'owner@nayara.test',
            'password'  => Hash::make('Secret123!'),
            'role'      => UserRole::OWNER->value,
            'status'    => UserStatus::ACTIVE->value,
            'outlet_id' => $this->outlet->id,
        ]);
    }

    public function test_superadmin_can_access_dashboard_with_nayara_data_contract(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('platform.dashboard'));

        $response->assertOk();
        $response->assertViewHas('nayaraDashboard');
        $response->assertSee('NAYARA');
        $response->assertSee('SUPER ADMIN');
        $response->assertSee('Dashboard Platform');
        $response->assertSee('Pusat kendali ekosistem NAYARA');
    }

    public function test_superadmin_can_view_role_matrix(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('platform.roles.index'));

        $response->assertOk();
        $response->assertSee('Matriks Role');
        $response->assertSee('SUPER_ADMIN');
        $response->assertSee('OWNER');
        $response->assertSee('ADMIN');
    }

    public function test_superadmin_can_view_and_send_broadcast_notification(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('platform.notifications.index'));
        $response->assertOk();
        $response->assertSee('Broadcast');
        $response->assertSee('Notifikasi');

        $postResponse = $this->actingAs($this->superAdmin)->post(route('platform.notifications.broadcast'), [
            'target'     => 'all',
            'type'       => 'ANNOUNCEMENT',
            'title'      => 'Jadwal Maintenance Sistem',
            'message'    => 'Server platform akan melakukan optimasi pada akhir pekan.',
            'action_url' => '/owner/dashboard',
        ]);

        $postResponse->assertRedirect(route('platform.notifications.index'));
        $postResponse->assertSessionHas('success');

        $this->assertDatabaseHas('platform_notifications', [
            'title' => 'Jadwal Maintenance Sistem',
            'type'  => 'ANNOUNCEMENT',
        ]);
    }

    public function test_notification_broadcast_deduplication_prevents_duplicate_notifications(): void
    {
        // First submission
        $this->actingAs($this->superAdmin)->post(route('platform.notifications.broadcast'), [
            'target'     => 'all',
            'type'       => 'MAINTENANCE',
            'title'      => 'Maintenance Server Rutin',
            'message'    => 'Pemberitahuan pemeliharaan sistem berkala.',
            'action_url' => null,
        ])->assertSessionHas('success');

        $initialCount = PlatformNotification::where('title', 'Maintenance Server Rutin')->count();
        $this->assertEquals(1, $initialCount);

        // Immediate retry with identical data
        $retryResponse = $this->actingAs($this->superAdmin)->post(route('platform.notifications.broadcast'), [
            'target'     => 'all',
            'type'       => 'MAINTENANCE',
            'title'      => 'Maintenance Server Rutin',
            'message'    => 'Pemberitahuan pemeliharaan sistem berkala.',
            'action_url' => null,
        ]);

        $retryResponse->assertRedirect(route('platform.notifications.index'));
        $retryCount = PlatformNotification::where('title', 'Maintenance Server Rutin')->count();
        $this->assertEquals(1, $retryCount, 'Duplicate broadcast must not create extra notification records');
    }

    public function test_superadmin_can_view_and_update_platform_settings(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('platform.settings.index'));
        $response->assertOk();
        $response->assertSee('Pengaturan');
        $response->assertSee('Kebijakan Platform');

        $updateResponse = $this->actingAs($this->superAdmin)->post(route('platform.settings.update'), [
            'setting_category' => 'ads_monetization',
        ]);

        $updateResponse->assertRedirect(route('platform.settings.index'));
        $updateResponse->assertSessionHas('success');
    }

    public function test_platform_settings_displays_official_commercial_model(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('platform.settings.index'));

        $response->assertOk();
        // 1. FREE ERP
        $response->assertSee('Paket Dasar (Free ERP)');
        $response->assertSee('5 Slot Etalase Publik Bawaan');

        // 2. PREMIUM
        $response->assertSee('Paket NAYARA Premium');
        $response->assertSee('59.000');
        $response->assertSee('169.000');
        $response->assertSee('319.000');
        $response->assertSee('569.000');

        // 3. Permanent Slots
        $response->assertSee('+5 Slot Permanen');
        $response->assertSee('49.000');
        $response->assertSee('+10 Slot Permanen');
        $response->assertSee('89.000');

        // 4. Financial Analytics Add-On
        $response->assertSee('Financial Analytics Add-On');
        $response->assertSee('45.000');
        $response->assertSee('129.000');
        $response->assertSee('249.000');
        $response->assertSee('489.000');

        // 5. Unauthorized package names MUST NOT be present
        $response->assertDontSee('Paket Bisnis Pro');
        $response->assertDontSee('Paket Skala Konveksi Enterprise');
    }

    public function test_non_superadmin_cannot_access_new_governance_routes(): void
    {
        $this->actingAs($this->owner)->get(route('platform.roles.index'))->assertForbidden();
        $this->actingAs($this->owner)->get(route('platform.notifications.index'))->assertForbidden();
        $this->actingAs($this->owner)->get(route('platform.settings.index'))->assertForbidden();
    }
}
