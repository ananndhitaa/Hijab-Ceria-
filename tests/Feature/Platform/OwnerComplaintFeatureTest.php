<?php

namespace Tests\Feature\Platform;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Platform\OwnerComplaint;
use App\Models\Platform\OwnerComplaintReply;
use App\Models\System\Outlet;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OwnerComplaintFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected Outlet $outletA;
    protected User $ownerA;
    protected Outlet $outletB;
    protected User $ownerB;

    protected function setUp(): void
    {
        parent::setUp();
        config(['nayara.tenant_isolation_enabled' => false]);
        Storage::fake('public');

        $this->superAdmin = User::create([
            'name'      => 'Platform Master',
            'email'     => 'superadmin@nayara.test',
            'password'  => Hash::make('Secret123!'),
            'role'      => UserRole::SUPER_ADMIN->value,
            'status'    => UserStatus::ACTIVE->value,
            'outlet_id' => null,
        ]);

        $this->outletA = Outlet::create([
            'outlet_name' => 'Nayara Hijab Pusat',
            'outlet_code' => 'OUT-A',
            'status'      => 'ACTIVE',
        ]);

        $this->ownerA = User::create([
            'name'      => 'Owner Aulia',
            'email'     => 'owner.a@nayara.test',
            'password'  => Hash::make('Secret123!'),
            'role'      => UserRole::OWNER->value,
            'status'    => UserStatus::ACTIVE->value,
            'outlet_id' => $this->outletA->id,
        ]);

        $this->outletB = Outlet::create([
            'outlet_name' => 'Luxora Hijab Mitra',
            'outlet_code' => 'OUT-B',
            'status'      => 'ACTIVE',
        ]);

        $this->ownerB = User::create([
            'name'      => 'Owner Budi',
            'email'     => 'owner.b@nayara.test',
            'password'  => Hash::make('Secret123!'),
            'role'      => UserRole::OWNER->value,
            'status'    => UserStatus::ACTIVE->value,
            'outlet_id' => $this->outletB->id,
        ]);
    }

    public function test_super_admin_can_view_complaints_index_with_accurate_kpi_counts(): void
    {
        OwnerComplaint::create([
            'complaint_code'  => 'KLH-202611-0001',
            'outlet_id'       => $this->outletA->id,
            'user_id'         => $this->ownerA->id,
            'category'        => OwnerComplaint::CATEGORY_ERP_SYSTEM,
            'title'           => 'Kendala sinkronisasi data',
            'description'     => 'Stok belum terpotong otomatis.',
            'status'          => OwnerComplaint::STATUS_NEW,
        ]);

        OwnerComplaint::create([
            'complaint_code'  => 'KLH-202611-0002',
            'outlet_id'       => $this->outletB->id,
            'user_id'         => $this->ownerB->id,
            'category'        => OwnerComplaint::CATEGORY_BILLING_PAYMENT,
            'title'           => 'Konfirmasi transfer langganan',
            'description'     => 'Mohon dicek bukti bayar.',
            'status'          => OwnerComplaint::STATUS_IN_PROGRESS,
        ]);

        OwnerComplaint::create([
            'complaint_code'  => 'KLH-202611-0003',
            'outlet_id'       => $this->outletA->id,
            'user_id'         => $this->ownerA->id,
            'category'        => OwnerComplaint::CATEGORY_SUBSCRIPTION,
            'title'           => 'Upgrade ke Premium',
            'description'     => 'Pertanyaan fitur analytics.',
            'status'          => OwnerComplaint::STATUS_RESOLVED,
            'resolved_at'     => now(),
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('platform.complaints.index'));

        $response->assertOk();
        $response->assertSee('Pusat Bantuan Owner');
        $response->assertSee('KLH-202611-0001');
        $response->assertSee('KLH-202611-0002');
        $response->assertSee('KLH-202611-0003');
        $response->assertSee('Nayara Hijab Pusat');
        $response->assertSee('Luxora Hijab Mitra');
    }

    public function test_super_admin_can_filter_complaints_by_status_and_category(): void
    {
        OwnerComplaint::create([
            'complaint_code'  => 'KLH-202611-0010',
            'outlet_id'       => $this->outletA->id,
            'user_id'         => $this->ownerA->id,
            'category'        => OwnerComplaint::CATEGORY_ERP_SYSTEM,
            'title'           => 'Error modul inventory',
            'description'     => 'Kain katun tidak tampil.',
            'status'          => OwnerComplaint::STATUS_NEW,
        ]);

        OwnerComplaint::create([
            'complaint_code'  => 'KLH-202611-0011',
            'outlet_id'       => $this->outletA->id,
            'user_id'         => $this->ownerA->id,
            'category'        => OwnerComplaint::CATEGORY_ADS_PROMOTION,
            'title'           => 'Jadwal tayang iklan tertunda',
            'description'     => 'Kapan tayang live?',
            'status'          => OwnerComplaint::STATUS_IN_PROGRESS,
        ]);

        // Filter category ERP_SYSTEM
        $response = $this->actingAs($this->superAdmin)
            ->get(route('platform.complaints.index', ['category' => OwnerComplaint::CATEGORY_ERP_SYSTEM]));

        $response->assertOk();
        $response->assertSee('KLH-202611-0010');
        $response->assertDontSee('KLH-202611-0011');
    }

    public function test_super_admin_can_view_complaint_detail_and_reply(): void
    {
        $complaint = OwnerComplaint::create([
            'complaint_code'  => 'KLH-202611-0020',
            'outlet_id'       => $this->outletA->id,
            'user_id'         => $this->ownerA->id,
            'category'        => OwnerComplaint::CATEGORY_ERP_SYSTEM,
            'title'           => 'Masalah sinkronisasi stok',
            'description'     => 'Tolong dicek tim teknis.',
            'status'          => OwnerComplaint::STATUS_NEW,
        ]);

        $detailResponse = $this->actingAs($this->superAdmin)
            ->get(route('platform.complaints.show', $complaint));

        $detailResponse->assertOk();
        $detailResponse->assertSee('KLH-202611-0020');
        $detailResponse->assertSee('Masalah sinkronisasi stok');

        // Super Admin replies
        $replyResponse = $this->actingAs($this->superAdmin)
            ->post(route('platform.complaints.reply', $complaint), [
                'message' => 'Halo Owner, tim teknis telah memperbaiki penyesuaian stok per sore ini.',
                'status'  => OwnerComplaint::STATUS_WAITING_OWNER,
            ]);

        $replyResponse->assertRedirect(route('platform.complaints.show', $complaint));

        $complaint->refresh();
        $this->assertEquals(OwnerComplaint::STATUS_WAITING_OWNER, $complaint->status);
        $this->assertCount(1, $complaint->replies);
        $this->assertTrue($complaint->replies->first()->is_from_super_admin);
        $this->assertDatabaseHas('platform_notifications', [
            'outlet_id' => $this->outletA->id,
            'type'      => 'COMPLAINT_REPLY',
        ]);
    }

    public function test_super_admin_can_update_complaint_status_directly(): void
    {
        $complaint = OwnerComplaint::create([
            'complaint_code'  => 'KLH-202611-0030',
            'outlet_id'       => $this->outletA->id,
            'user_id'         => $this->ownerA->id,
            'category'        => OwnerComplaint::CATEGORY_BILLING_PAYMENT,
            'title'           => 'Klarifikasi invoice',
            'description'     => 'Pertanyaan rincian PPN.',
            'status'          => OwnerComplaint::STATUS_IN_PROGRESS,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('platform.complaints.update-status', $complaint), [
                'status' => OwnerComplaint::STATUS_RESOLVED,
                'notes'  => 'Invoice telah diverifikasi dan diselesaikan.',
            ]);

        $response->assertRedirect(route('platform.complaints.show', $complaint));

        $complaint->refresh();
        $this->assertEquals(OwnerComplaint::STATUS_RESOLVED, $complaint->status);
        $this->assertNotNull($complaint->resolved_at);
    }

    public function test_owner_can_view_own_complaints_index(): void
    {
        OwnerComplaint::create([
            'complaint_code'  => 'KLH-202611-0040',
            'outlet_id'       => $this->outletA->id,
            'user_id'         => $this->ownerA->id,
            'category'        => OwnerComplaint::CATEGORY_ERP_SYSTEM,
            'title'           => 'Keluhan Outlet A',
            'description'     => 'Deskripsi kendala toko A.',
            'status'          => OwnerComplaint::STATUS_NEW,
        ]);

        // Outlet B complaint should NOT appear in Owner A's view
        OwnerComplaint::create([
            'complaint_code'  => 'KLH-202611-0041',
            'outlet_id'       => $this->outletB->id,
            'user_id'         => $this->ownerB->id,
            'category'        => OwnerComplaint::CATEGORY_ERP_SYSTEM,
            'title'           => 'Keluhan Outlet B Rahasia',
            'description'     => 'Deskripsi kendala toko B.',
            'status'          => OwnerComplaint::STATUS_NEW,
        ]);

        $response = $this->actingAs($this->ownerA)->get(route('owner.complaints.index'));

        $response->assertOk();
        $response->assertSee('Keluhan &amp; Pusat Bantuan', false);
        $response->assertSee('KLH-202611-0040');
        $response->assertSee('Keluhan Outlet A');
        $response->assertDontSee('KLH-202611-0041');
        $response->assertDontSee('Keluhan Outlet B Rahasia');
    }

    public function test_owner_can_submit_new_complaint_with_attachment(): void
    {
        $file = UploadedFile::fake()->create('bukti_kendala.png', 500, 'image/png');

        $response = $this->actingAs($this->ownerA)->post(route('owner.complaints.store'), [
            'category'    => OwnerComplaint::CATEGORY_ERP_SYSTEM,
            'title'       => 'Tombol cetak SPK error',
            'description' => 'Saat diklik muncul error 500 pada pesanan nomor 24.',
            'attachment'  => $file,
        ]);

        $complaint = OwnerComplaint::where('outlet_id', $this->outletA->id)->first();
        $this->assertNotNull($complaint);
        $this->assertEquals('Tombol cetak SPK error', $complaint->title);
        $this->assertEquals(OwnerComplaint::STATUS_NEW, $complaint->status);
        $this->assertNotNull($complaint->attachment_path);

        $response->assertRedirect(route('owner.complaints.show', $complaint));
    }

    public function test_owner_can_view_and_reply_to_own_complaint(): void
    {
        $complaint = OwnerComplaint::create([
            'complaint_code'  => 'KLH-202611-0050',
            'outlet_id'       => $this->outletA->id,
            'user_id'         => $this->ownerA->id,
            'category'        => OwnerComplaint::CATEGORY_ERP_SYSTEM,
            'title'           => 'Konfirmasi perbaikan',
            'description'     => 'Apakah sudah selesai?',
            'status'          => OwnerComplaint::STATUS_WAITING_OWNER,
        ]);

        $showResponse = $this->actingAs($this->ownerA)->get(route('owner.complaints.show', $complaint));
        $showResponse->assertOk();

        $replyResponse = $this->actingAs($this->ownerA)->post(route('owner.complaints.reply', $complaint), [
            'message' => 'Terima kasih, sudah saya coba dan sekarang sudah berjalan normal.',
        ]);

        $replyResponse->assertRedirect(route('owner.complaints.show', $complaint));

        $complaint->refresh();
        $this->assertEquals(OwnerComplaint::STATUS_IN_PROGRESS, $complaint->status);
        $this->assertCount(1, $complaint->replies);
        $this->assertFalse($complaint->replies->first()->is_from_super_admin);
    }

    public function test_tenant_isolation_owner_cannot_access_or_reply_to_other_tenant_complaint(): void
    {
        $complaintB = OwnerComplaint::create([
            'complaint_code'  => 'KLH-202611-0060',
            'outlet_id'       => $this->outletB->id,
            'user_id'         => $this->ownerB->id,
            'category'        => OwnerComplaint::CATEGORY_ERP_SYSTEM,
            'title'           => 'Keluhan Khusus Outlet B',
            'description'     => 'Informasi rahasia internal toko B.',
            'status'          => OwnerComplaint::STATUS_NEW,
        ]);

        // Owner A tries to view Outlet B's complaint -> 403 Forbidden
        $viewResponse = $this->actingAs($this->ownerA)->get(route('owner.complaints.show', $complaintB));
        $viewResponse->assertForbidden();

        // Owner A tries to reply to Outlet B's complaint -> 403 Forbidden
        $replyResponse = $this->actingAs($this->ownerA)->post(route('owner.complaints.reply', $complaintB), [
            'message' => 'Percobaan intervensi ilegal.',
        ]);
        $replyResponse->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_complaint_endpoints(): void
    {
        $this->get(route('platform.complaints.index'))->assertRedirect(route('login'));
        $this->get(route('owner.complaints.index'))->assertRedirect(route('login'));
    }
}
