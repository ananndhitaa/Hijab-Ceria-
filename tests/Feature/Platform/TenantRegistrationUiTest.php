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

class TenantRegistrationUiTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        config(['nayara.tenant_isolation_enabled' => false]);

        $this->superAdmin = User::create([
            'name'      => 'Super Admin QA',
            'email'     => 'superadmin.ui@nayara.test',
            'password'  => Hash::make('Secret123!'),
            'role'      => UserRole::SUPER_ADMIN->value,
            'status'    => UserStatus::ACTIVE->value,
            'outlet_id' => null,
        ]);

        $this->regularUser = User::create([
            'name'      => 'Regular Staff',
            'email'     => 'staff.ui@nayara.test',
            'password'  => Hash::make('Secret123!'),
            'role'      => UserRole::ADMIN->value,
            'status'    => UserStatus::ACTIVE->value,
            'outlet_id' => null,
        ]);
    }

    public function test_super_admin_can_access_registrations_queue(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('platform.registrations.index'));

        $response->assertStatus(200);
        $response->assertSee('Antrean Pendaftaran Tenant');
        $response->assertSee('PENDAFTARAN TENANT');
        $response->assertSee('Tinjau pendaftaran tenant baru dan kelola status verifikasi.');
        $response->assertSee('Menunggu Review');
        $response->assertSee('Perlu Revisi');
        $response->assertSee('Disetujui');
        $response->assertSee('Ditolak');
        $response->assertSee('Semua Pendaftaran');
    }

    public function test_unauthorized_users_are_denied_access(): void
    {
        // Guest
        $guestResponse = $this->get(route('platform.registrations.index'));
        $guestResponse->assertRedirect(route('login'));

        // Non-superadmin
        $nonAdminResponse = $this->actingAs($this->regularUser)
            ->get(route('platform.registrations.index'));
        $nonAdminResponse->assertStatus(403);
    }

    public function test_empty_state_is_rendered_when_no_data(): void
    {
        // 1. Pending empty tab
        $response = $this->actingAs($this->superAdmin)
            ->get(route('platform.registrations.index', ['tab' => 'pending']));

        $response->assertStatus(200);
        $response->assertSee('Belum ada pendaftaran baru');
        $response->assertSee('Belum ada pengajuan pada kategori ini.');
        $response->assertSee('n-reg-empty-wave', false);

        // 2. All empty tab
        $responseAll = $this->actingAs($this->superAdmin)
            ->get(route('platform.registrations.index', ['tab' => 'all']));

        $responseAll->assertStatus(200);
        $responseAll->assertSee('Belum ada pendaftaran baru');
        $responseAll->assertSee('Belum ada pendaftaran tenant.');
    }

    public function test_tabs_show_real_counts_from_database(): void
    {
        // Seed varying statuses
        $this->createRegistration('Tenant Pending 1', TenantRegistration::STATUS_PENDING);
        $this->createRegistration('Tenant Pending 2', TenantRegistration::STATUS_PENDING);
        $this->createRegistration('Tenant Revision 1', TenantRegistration::STATUS_NEEDS_REVISION);
        $this->createRegistration('Tenant Approved 1', TenantRegistration::STATUS_APPROVED);
        $this->createRegistration('Tenant Approved 2', TenantRegistration::STATUS_APPROVED);
        $this->createRegistration('Tenant Approved 3', TenantRegistration::STATUS_APPROVED);
        $this->createRegistration('Tenant Rejected 1', TenantRegistration::STATUS_REJECTED);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('platform.registrations.index'));

        $response->assertStatus(200);
        $response->assertViewHas('counts', [
            'pending'        => 2,
            'needs_revision' => 1,
            'approved'       => 3,
            'rejected'       => 1,
            'all'            => 7,
        ]);
    }

    public function test_populated_table_rendered_when_registrations_exist(): void
    {
        $reg = $this->createRegistration('Hijab Syar\'i Elegan', TenantRegistration::STATUS_PENDING, [
            'owner_name' => 'Fatimah Zahra',
            'email'      => 'fatimah@hijabsyari.test',
            'phone_wa'   => '081299887766',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('platform.registrations.index', ['tab' => 'pending']));

        $response->assertStatus(200);
        $response->assertDontSee('Belum ada pendaftaran baru');
        $response->assertSee('Hijab Syar\'i Elegan');
        $response->assertSee('Fatimah Zahra');
        $response->assertSee('fatimah@hijabsyari.test');
        $response->assertSee('081299887766');
        $response->assertSee('Menunggu Review');
        $response->assertSee(route('platform.registrations.show', $reg->id));
        $response->assertSee('Lihat Detail');
    }

    public function test_filter_by_status_tabs_shows_only_matching_registrations(): void
    {
        $this->createRegistration('Tenant Pending Test', TenantRegistration::STATUS_PENDING);
        $this->createRegistration('Tenant Approved Test', TenantRegistration::STATUS_APPROVED);

        // Check pending tab
        $responsePending = $this->actingAs($this->superAdmin)
            ->get(route('platform.registrations.index', ['tab' => 'pending']));
        $responsePending->assertSee('Tenant Pending Test');
        $responsePending->assertDontSee('Tenant Approved Test');

        // Check approved tab
        $responseApproved = $this->actingAs($this->superAdmin)
            ->get(route('platform.registrations.index', ['tab' => 'approved']));
        $responseApproved->assertSee('Tenant Approved Test');
        $responseApproved->assertDontSee('Tenant Pending Test');
    }

    public function test_search_filter_functions_properly(): void
    {
        $this->createRegistration('Konveksi Mawar Indah', TenantRegistration::STATUS_PENDING, [
            'owner_name' => 'Siti Mawar',
            'email'      => 'siti@mawarindah.test',
        ]);
        $this->createRegistration('Konveksi Melati Putih', TenantRegistration::STATUS_PENDING, [
            'owner_name' => 'Dewi Melati',
            'email'      => 'dewi@melatiputih.test',
        ]);

        // Search match
        $searchResponse = $this->actingAs($this->superAdmin)
            ->get(route('platform.registrations.index', ['tab' => 'pending', 'search' => 'Mawar']));
        $searchResponse->assertSee('Konveksi Mawar Indah');
        $searchResponse->assertDontSee('Konveksi Melati Putih');

        // Search no match
        $noMatchResponse = $this->actingAs($this->superAdmin)
            ->get(route('platform.registrations.index', ['tab' => 'pending', 'search' => 'KenangaNonExistent']));
        $noMatchResponse->assertSee('Belum ada pendaftaran baru');
        $noMatchResponse->assertSee('Tidak ditemukan pendaftaran yang cocok dengan kata kunci');
        $noMatchResponse->assertSee('Reset Pencarian');
    }

    public function test_sidebar_is_single_and_highlights_pendaftaran(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('platform.registrations.index'));

        $content = $response->getContent();

        // Exactly one main sidebar element
        $this->assertEquals(1, substr_count($content, 'class="n-sidebar"'));

        // Pendaftaran menu item has active class
        $this->assertStringContainsString('Pendaftaran', $content);
        $this->assertMatchesRegularExpression('/href="[^"]*platform\/registrations"[^>]*class="[^"]*n-nav-link[^"]*active/i', $content);
    }

    private function createRegistration(string $name, string $status, array $overrides = []): TenantRegistration
    {
        return TenantRegistration::create(array_merge([
            'company_name' => $name,
            'owner_name'   => 'Owner ' . $name,
            'email'        => strtolower(str_replace(' ', '.', $name)) . '@tenant.test',
            'phone_wa'     => '081234567890',
            'address'      => 'Jl. Testing No. 123',
            'password'     => Hash::make('Password123!'),
            'status'       => $status,
        ], $overrides));
    }
}
