<?php

namespace Tests\Feature\Platform;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Platform\PlatformInvoice;
use App\Models\Platform\TenantRegistration;
use App\Models\System\Outlet;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlatformNotificationSynchronizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected Outlet $outlet;

    protected function setUp(): void
    {
        parent::setUp();
        config(['nayara.tenant_isolation_enabled' => false]);

        $this->superAdmin = User::create([
            'name'      => 'Platform Master',
            'email'     => 'superadmin@nayara.test',
            'password'  => Hash::make('password'),
            'role'      => UserRole::SUPER_ADMIN->value,
            'status'    => UserStatus::ACTIVE->value,
            'outlet_id' => null,
        ]);

        $this->outlet = Outlet::create([
            'outlet_name' => 'NAYARA Test Convection',
            'outlet_code' => 'OUT-SYNC-01',
            'status'      => 'ACTIVE',
        ]);
    }

    /**
     * Test notification badge and tasks dropdown are synchronized across Dashboard and all platform pages.
     */
    public function test_notification_badge_and_tasks_are_synchronized_across_all_platform_pages(): void
    {
        // Setup: 1 pending invoice waiting for verification (exact scenario from user screenshot)
        PlatformInvoice::create([
            'invoice_no'   => 'INV-SYNC-001',
            'outlet_id'    => $this->outlet->id,
            'service_type' => PlatformInvoice::SERVICE_PREMIUM,
            'amount'       => 500000,
            'status'       => PlatformInvoice::STATUS_PAYMENT_SUBMITTED,
            'expires_at'   => now()->addDays(3),
        ]);

        $platformRoutes = [
            route('platform.dashboard'),
            route('platform.registrations.index'),
            route('platform.tenants.index'),
            route('platform.billing.index'),
            route('platform.ads.index'),
            route('platform.refunds.index'),
            route('platform.complaints.index'),
            route('platform.roles.index'),
            route('platform.audit.index'),
            route('platform.settings.index'),
        ];

        foreach ($platformRoutes as $url) {
            $response = $this->actingAs($this->superAdmin)->get($url);
            $response->assertOk();

            // Must see the synchronized notification count "1 Tugas Masuk" in rendered text
            $response->assertSeeText('1 Tugas Masuk');
            $response->assertSee('Tagihan Platform');
            $response->assertSee('1 Invoice Menunggu Verifikasi');
            $response->assertSee('Bukti pembayaran langganan SaaS siap diverifikasi.');
            $response->assertSee(route('platform.billing.index'));
        }
    }

    /**
     * Test empty notification state is uniform across Dashboard and all platform pages when no tasks are pending.
     */
    public function test_empty_notification_state_is_uniform_when_no_tasks_pending(): void
    {
        $platformRoutes = [
            route('platform.dashboard'),
            route('platform.registrations.index'),
            route('platform.tenants.index'),
            route('platform.billing.index'),
        ];

        foreach ($platformRoutes as $url) {
            $response = $this->actingAs($this->superAdmin)->get($url);
            $response->assertOk();

            $response->assertSee('Up to Date');
            $response->assertSee('Tidak Ada Tugas Menunggu');
            $response->assertSee('Semua pengajuan pendaftaran dan tagihan telah bersih diproses.');
            $response->assertDontSee('data-i18n="notif_tasks_incoming"', false);
            $response->assertDontSee('min-w-[18px]', false);
        }
    }

    /**
     * Test multi-task aggregation reflects correctly across all pages.
     */
    public function test_multi_task_aggregation_reflects_correctly(): void
    {
        // 1 pending registration
        TenantRegistration::create([
            'company_name' => 'Hijab Baru Syari',
            'owner_name'   => 'Aisyah',
            'email'        => 'aisyah@hijabbaru.test',
            'phone_wa'     => '081234567890',
            'password'     => Hash::make('password123'),
            'status'       => TenantRegistration::STATUS_PENDING,
        ]);

        // 1 needs revision
        TenantRegistration::create([
            'company_name' => 'Revisi Hijab Mandiri',
            'owner_name'   => 'Fatimah',
            'email'        => 'fatimah@revisi.test',
            'phone_wa'     => '081234567891',
            'password'     => Hash::make('password123'),
            'status'       => TenantRegistration::STATUS_NEEDS_REVISION,
        ]);

        // 1 pending invoice
        PlatformInvoice::create([
            'invoice_no'   => 'INV-SYNC-002',
            'outlet_id'    => $this->outlet->id,
            'service_type' => PlatformInvoice::SERVICE_LISTING_SLOTS,
            'amount'       => 200000,
            'status'       => PlatformInvoice::STATUS_PAYMENT_SUBMITTED,
            'expires_at'   => now()->addDays(3),
        ]);

        // Total should be 3
        $pages = [
            route('platform.dashboard'),
            route('platform.registrations.index'),
            route('platform.billing.index'),
        ];

        foreach ($pages as $url) {
            $response = $this->actingAs($this->superAdmin)->get($url);
            $response->assertOk();

            $response->assertSeeText('3 Tugas Masuk');
            $response->assertSee('Review Pendaftaran');
            $response->assertSee('1 Pengajuan Baru Menunggu Review');
            $response->assertSee('Perlu Revisi');
            $response->assertSee('1 Berkas Memerlukan Perbaikan');
            $response->assertSee('Tagihan Platform');
            $response->assertSee('1 Invoice Menunggu Verifikasi');
        }
    }
}
