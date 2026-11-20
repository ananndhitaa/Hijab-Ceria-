<?php

namespace Tests\Feature\Owner;

use App\Enums\UserRole;
use App\Models\Platform\PlatformNotification;
use App\Models\System\Outlet;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerNotificationFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Outlet $outlet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outlet = Outlet::factory()->create();

        $this->owner = User::factory()->create([
            'role' => UserRole::OWNER,
            'outlet_id' => $this->outlet->id,
        ]);
    }

    public function test_owner_can_view_notification_center_empty_state(): void
    {
        $response = $this->actingAs($this->owner)->get(route('owner.notifications.index'));

        $response->assertOk();
        $response->assertSee('Pusat Notifikasi Platform NAYARA');
        $response->assertSee('EXECUTIVE MONITORING');
        $response->assertSee('Semua Notifikasi Terkendali');
        $response->assertSee('Belum ada pemberitahuan baru');
        $response->assertSee('Tandai Semua Telah Dibaca');
    }

    public function test_owner_can_view_notification_center_with_notifications_and_grouping(): void
    {
        // Notification created today
        PlatformNotification::create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->owner->id,
            'type' => 'AD_REVIEW_DECISION',
            'title' => 'Iklan Banner Utama Disetujui',
            'message' => 'Materi promosi toko Anda telah lolos moderasi dan siap ditayangkan.',
            'action_url' => '/owner/ads',
            'is_read' => false,
            'created_at' => now(),
        ]);

        // Notification created yesterday
        $yesterdayNotif = PlatformNotification::create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->owner->id,
            'type' => 'BILLING_ALERT',
            'title' => 'Tagihan Langganan Pro Berhasil Diterbitkan',
            'message' => 'Invoice INV-2026-11-001 telah tersedia untuk pelunasan.',
            'action_url' => '/owner/subscriptions',
            'is_read' => true,
        ]);
        $yesterdayNotif->created_at = now()->subDay();
        $yesterdayNotif->save();

        $response = $this->actingAs($this->owner)->get(route('owner.notifications.index'));

        $response->assertOk();
        $response->assertSee('Iklan Banner Utama Disetujui');
        $response->assertSee('Iklan & Moderasi');
        $response->assertSee('Buka &amp; Baca', false);
        $response->assertSee('Tagihan Langganan Pro Berhasil Diterbitkan');
        $response->assertSee('Langganan &amp; Tagihan', false);
        $response->assertSee('Lihat Detail');
        $response->assertSee('Hari Ini');
        $response->assertSee('Kemarin');
    }

    public function test_owner_can_filter_notifications_by_status(): void
    {
        PlatformNotification::create([
            'outlet_id' => $this->outlet->id,
            'type' => 'AD_REVIEW',
            'title' => 'Notifikasi Belum Dibaca A',
            'message' => 'Pesan 1',
            'is_read' => false,
        ]);

        PlatformNotification::create([
            'outlet_id' => $this->outlet->id,
            'type' => 'GENERAL',
            'title' => 'Notifikasi Sudah Dibaca B',
            'message' => 'Pesan 2',
            'is_read' => true,
        ]);

        // Filter unread
        $unreadResp = $this->actingAs($this->owner)->get(route('owner.notifications.index', ['status' => 'unread']));
        $unreadResp->assertOk();
        $unreadResp->assertSee('Notifikasi Belum Dibaca A');
        $unreadResp->assertDontSee('Notifikasi Sudah Dibaca B');

        // Filter read
        $readResp = $this->actingAs($this->owner)->get(route('owner.notifications.index', ['status' => 'read']));
        $readResp->assertOk();
        $readResp->assertSee('Notifikasi Sudah Dibaca B');
        $readResp->assertDontSee('Notifikasi Belum Dibaca A');
    }

    public function test_owner_can_filter_notifications_by_category(): void
    {
        PlatformNotification::create([
            'outlet_id' => $this->outlet->id,
            'type' => 'AD_PROMOTION',
            'title' => 'Promo Iklan Baru',
            'message' => 'Diskon iklan 20%',
            'is_read' => false,
        ]);

        PlatformNotification::create([
            'outlet_id' => $this->outlet->id,
            'type' => 'SERVICE_SCOPED',
            'title' => 'Estimasi Layanan Terbit',
            'message' => 'Estimasi setup toko telah disusun.',
            'is_read' => false,
        ]);

        $adsResp = $this->actingAs($this->owner)->get(route('owner.notifications.index', ['category' => 'ads']));
        $adsResp->assertOk();
        $adsResp->assertSee('Promo Iklan Baru');
        $adsResp->assertDontSee('Estimasi Layanan Terbit');

        $svcResp = $this->actingAs($this->owner)->get(route('owner.notifications.index', ['category' => 'service']));
        $svcResp->assertOk();
        $svcResp->assertSee('Estimasi Layanan Terbit');
        $svcResp->assertDontSee('Promo Iklan Baru');
    }

    public function test_owner_can_mark_single_notification_as_read(): void
    {
        $notif = PlatformNotification::create([
            'outlet_id' => $this->outlet->id,
            'type' => 'ANNOUNCEMENT',
            'title' => 'Pengumuman Sistem',
            'message' => 'Pemeliharaan server berkala akhir pekan.',
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->owner)->post(route('owner.notifications.read', $notif->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertTrue($notif->fresh()->is_read);
    }

    public function test_owner_can_mark_all_notifications_as_read(): void
    {
        PlatformNotification::create([
            'outlet_id' => $this->outlet->id,
            'type' => 'ANNOUNCEMENT',
            'title' => 'Notif 1',
            'message' => 'Msg 1',
            'is_read' => false,
        ]);

        PlatformNotification::create([
            'outlet_id' => $this->outlet->id,
            'type' => 'BILLING_ALERT',
            'title' => 'Notif 2',
            'message' => 'Msg 2',
            'is_read' => false,
        ]);

        $this->assertEquals(2, PlatformNotification::where('outlet_id', $this->outlet->id)->where('is_read', false)->count());

        $response = $this->actingAs($this->owner)->post(route('owner.notifications.read-all'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(0, PlatformNotification::where('outlet_id', $this->outlet->id)->where('is_read', false)->count());
    }

    public function test_owner_cannot_mark_other_outlet_notification_as_read(): void
    {
        $otherOutlet = Outlet::factory()->create();
        $foreignNotif = PlatformNotification::create([
            'outlet_id' => $otherOutlet->id,
            'type' => 'ANNOUNCEMENT',
            'title' => 'Pemberitahuan Toko Lain',
            'message' => 'Pesan rahasia toko lain',
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->owner)->post(route('owner.notifications.read', $foreignNotif->id));

        $response->assertForbidden();
        $this->assertFalse($foreignNotif->fresh()->is_read);
    }
}
