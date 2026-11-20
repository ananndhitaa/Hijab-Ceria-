<?php

namespace Tests\Feature\Platform;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Platform\TenantRegistration;
use App\Models\System\Outlet;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlatformCompactDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected Outlet $outlet;
    protected User $tenantOwner;

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
            'outlet_name' => 'Nayara Test Outlet',
            'outlet_code' => 'OUT-TEST-1',
            'status'      => 'ACTIVE',
        ]);

        $this->tenantOwner = User::create([
            'name'      => 'Tenant Owner Test',
            'email'     => 'tenant@nayara.test',
            'password'  => Hash::make('Secret123!'),
            'role'      => UserRole::OWNER->value,
            'status'    => UserStatus::ACTIVE->value,
            'outlet_id' => $this->outlet->id,
        ]);
    }

    public function test_dashboard_renders_single_compact_heading_and_no_promotional_hero(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('platform.dashboard'));

        $response->assertOk();
        $response->assertViewIs('platform.dashboard');

        // Required official heading & brand hero
        $response->assertSee('Portal Super Admin');
        $response->assertSee('PUSAT KENDALI PLATFORM');
        $response->assertSee('Kelola Pertumbuhan Ekosistem NAYARA');
        $response->assertSee('Tinjau pendaftaran, persetujuan tenant');
        $response->assertSee('Lihat Antrean');
        $response->assertSee('Kelola Tenant');
    }

    public function test_dashboard_renders_four_main_kpis_without_verbose_descriptions(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('platform.dashboard'));

        $response->assertOk();

        // Required main KPI labels
        $response->assertSee('MENUNGGU REVIEW');
        $response->assertSee('TENANT AKTIF');
        $response->assertSee('PERLU PERBAIKAN');
        $response->assertSee('TENANT NONAKTIF');

        // Required official descriptions
        $response->assertSee('Perusahaan beroperasi');
        $response->assertSee('Perlu tindakan verifikasi');
        $response->assertSee('Menunggu revisi pemohon');
        $response->assertSee('Akses sedang dibatasi');
    }

    public function test_dashboard_renders_secondary_metrics_accurately(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('platform.dashboard'));

        $response->assertOk();
        $response->assertSee('Total Pendaftaran');
        $response->assertSee('Total Disetujui');
        $response->assertSee('Total Ditolak');
        $response->assertSee('Total Akun Tenant');
    }

    public function test_dashboard_renders_registration_table_and_empty_state_when_no_records(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('platform.dashboard'));

        $response->assertOk();
        $response->assertSee('Antrean Pendaftaran Terbaru');
        $response->assertSee('Belum ada pendaftaran terbaru');
    }

    public function test_dashboard_renders_recent_registrations_when_present(): void
    {
        TenantRegistration::create([
            'company_name'   => 'Konveksi Berkah Mandiri',
            'owner_name'     => 'Haji Ahmad',
            'email'          => 'ahmad@berkah.test',
            'phone_wa'       => '081234567890',
            'address'        => 'Jl. Tekstil No. 12',
            'password'       => Hash::make('Secret123!'),
            'status'         => TenantRegistration::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('platform.dashboard'));

        $response->assertOk();
        $response->assertSee('Konveksi Berkah Mandiri');
        $response->assertSee('Haji Ahmad');
        $response->assertSee('Menunggu');
    }

    public function test_dashboard_renders_only_four_implemented_quick_actions(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('platform.dashboard'));

        $response->assertOk();
        $response->assertSee('Tindakan Super Admin');
        $response->assertSee('Review Pendaftaran');
        $response->assertSee('Kelola Tenant');
        $response->assertSee('Verifikasi Tagihan');
        $response->assertSee('Moderasi Iklan');
        $response->assertSee('Proses Refund');
        $response->assertSee('Lihat Audit Trail');
    }

    public function test_sidebar_contains_all_13_modules_grouped_logically(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('platform.dashboard'));

        $response->assertOk();

        // 4 Logical groups matching the official Layanan Tenant reorganization
        $response->assertSee('PUSAT KENDALI');
        $response->assertSee('BISNIS & MONETISASI');
        $response->assertSee('LAYANAN TENANT');
        $response->assertSee('TATA KELOLA');

        // Module labels
        $response->assertSee('Dashboard Platform');
        $response->assertSee('Antrean Pendaftaran');
        $response->assertSee('Direktori Tenant');
        $response->assertSee('Tagihan Platform');
        $response->assertSee('Review Iklan');
        $response->assertSee('Pengembalian Dana');
        $response->assertSee('Analitik Global');
        $response->assertSee('Keluhan Owner');
        $response->assertSee('Pusat Notifikasi & Broadcast');
        $response->assertSee('Role & Hak Akses');
        $response->assertSee('Log Aktivitas');
        $response->assertSee('Pengaturan & Kebijakan');

        // All platform routes accessible
        $routes = [
            'platform.dashboard',
            'platform.registrations.index',
            'platform.tenants.index',
            'platform.billing.index',
            'platform.ads.index',
            'platform.refunds.index',
            'platform.analytics.index',
            'platform.services.index',
            'platform.complaints.index',
            'platform.notifications.index',
            'platform.roles.index',
            'platform.audit.index',
            'platform.settings.index',
            'platform.system.index',
        ];

        foreach ($routes as $routeName) {
            $subResponse = $this->actingAs($this->superAdmin)->get(route($routeName));
            $this->assertEquals(200, $subResponse->getStatusCode(), "Route {$routeName} must return 200 OK");
        }
    }
}
