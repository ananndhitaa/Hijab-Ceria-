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

class TenantDirectoryUiTest extends TestCase
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
            'email'     => 'superadmin.tenant@nayara.test',
            'password'  => Hash::make('Secret123!'),
            'role'      => UserRole::SUPER_ADMIN->value,
            'status'    => UserStatus::ACTIVE->value,
            'outlet_id' => null,
        ]);

        $this->regularUser = User::create([
            'name'      => 'Regular Staff',
            'email'     => 'staff.tenant@nayara.test',
            'password'  => Hash::make('Secret123!'),
            'role'      => UserRole::ADMIN->value,
            'status'    => UserStatus::ACTIVE->value,
            'outlet_id' => null,
        ]);
    }

    public function test_super_admin_can_access_tenant_directory(): void
    {
        $outlet = Outlet::create([
            'outlet_name' => 'NAYARA Hijab Pusat',
            'outlet_code' => 'OUT-NAYARA-PUSAT',
            'slug'        => 'nayara-hijab-pusat',
            'status'      => 'ACTIVE',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('platform.tenants.index'));

        $response->assertStatus(200);
        $response->assertSee('Direktori Tenant');
        $response->assertSee('Kelola entitas konveksi hijab independen');
        $response->assertSee('Total Tenant');
        $response->assertSee('Tenant Aktif');
        $response->assertSee('Perlu Tindakan');
        $response->assertSee('Suspend / Nonaktif');
        $response->assertSee('OUT-NAYARA-PUSAT');
        $response->assertSee('NAYARA Hijab Pusat');
        $response->assertSee('Kelola');
        $response->assertSee(route('platform.tenants.show', $outlet));

        // Ensure removed verbose elements do NOT appear
        $response->assertDontSee('Total Akun Tenant');
        $response->assertDontSee('n-btn-more');
    }

    public function test_unauthorized_users_are_denied_access(): void
    {
        // Guest
        $guestResponse = $this->get(route('platform.tenants.index'));
        $guestResponse->assertRedirect(route('login'));

        // Non-superadmin
        $nonAdminResponse = $this->actingAs($this->regularUser)
            ->get(route('platform.tenants.index'));
        $nonAdminResponse->assertStatus(403);
    }

    public function test_four_kpi_cards_display_accurate_database_metrics(): void
    {
        $outletActive1 = Outlet::create(['outlet_name' => 'Outlet 1', 'outlet_code' => 'OUT-1', 'slug' => 'out-1', 'status' => 'ACTIVE']);
        $outletActive2 = Outlet::create(['outlet_name' => 'Outlet 2', 'outlet_code' => 'OUT-2', 'slug' => 'out-2', 'status' => 'ACTIVE']);
        $outletInactive = Outlet::create(['outlet_name' => 'Outlet 3', 'outlet_code' => 'OUT-3', 'slug' => 'out-3', 'status' => 'INACTIVE']);

        // Registration needing action
        TenantRegistration::create([
            'company_name' => 'Konveksi Baru',
            'owner_name'   => 'Calon Owner',
            'email'        => 'calon@konveksi.test',
            'phone_wa'     => '081234567890',
            'status'       => TenantRegistration::STATUS_PENDING,
            'password'     => Hash::make('Secret123!'),
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('platform.tenants.index'));

        $response->assertStatus(200);
        $response->assertViewHas('stats', [
            'total'         => 3,
            'active'        => 2,
            'action_needed' => 1,
            'inactive'      => 1,
        ]);
    }

    public function test_tenant_table_contains_only_six_columns_and_no_staff_counts(): void
    {
        $outlet = Outlet::create([
            'outlet_name' => 'Konveksi Zahra',
            'outlet_code' => 'OUT-ZAHRA',
            'slug'        => 'konveksi-zahra',
            'status'      => 'ACTIVE',
        ]);

        // Create internal tenant staff
        User::create([
            'name'      => 'Staff Internal Zahra',
            'email'     => 'staff.internal@zahra.test',
            'password'  => Hash::make('Pass123!'),
            'role'      => UserRole::ADMIN->value,
            'status'    => UserStatus::ACTIVE->value,
            'outlet_id' => $outlet->id,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('platform.tenants.index'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // 6 columns in table header
        $this->assertStringContainsString('>KODE</th>', $content);
        $this->assertStringContainsString('>NAMA TENANT</th>', $content);
        $this->assertStringContainsString('>SLUG PUBLIK</th>', $content);
        $this->assertStringContainsString('>STATUS</th>', $content);
        $this->assertStringContainsString('>TERDAFTAR SEJAK</th>', $content);
        $this->assertStringContainsString('>AKSI</th>', $content);

        // Disallowed columns are completely absent
        $this->assertStringNotContainsString('KONTAK WA', $content);
        $this->assertStringNotContainsString('PENGGUNA', $content);
        $this->assertStringNotContainsString('n-user-badge', $content);
        $this->assertStringNotContainsString('1 Akun', $content);
    }

    public function test_search_filter_queries_code_name_and_slug(): void
    {
        Outlet::create(['outlet_name' => 'Konveksi Hijab Mawar', 'outlet_code' => 'OUT-MAWAR', 'slug' => 'konveksi-mawar', 'status' => 'ACTIVE']);
        Outlet::create(['outlet_name' => 'Konveksi Hijab Melati', 'outlet_code' => 'OUT-MELATI', 'slug' => 'konveksi-melati', 'status' => 'ACTIVE']);

        // Search by name
        $responseName = $this->actingAs($this->superAdmin)
            ->get(route('platform.tenants.index', ['search' => 'Mawar']));
        $responseName->assertSee('Konveksi Hijab Mawar');
        $responseName->assertDontSee('Konveksi Hijab Melati');

        // Search by code
        $responseCode = $this->actingAs($this->superAdmin)
            ->get(route('platform.tenants.index', ['search' => 'OUT-MELATI']));
        $responseCode->assertSee('Konveksi Hijab Melati');
        $responseCode->assertDontSee('Konveksi Hijab Mawar');
    }

    public function test_status_filter_works(): void
    {
        Outlet::create(['outlet_name' => 'Outlet Aktif', 'outlet_code' => 'OUT-AKTIF', 'slug' => 'outlet-aktif', 'status' => 'ACTIVE']);
        Outlet::create(['outlet_name' => 'Outlet Nonaktif', 'outlet_code' => 'OUT-NONAKTIF', 'slug' => 'outlet-nonaktif', 'status' => 'INACTIVE']);

        // Filter ACTIVE
        $responseActive = $this->actingAs($this->superAdmin)
            ->get(route('platform.tenants.index', ['status' => 'ACTIVE']));
        $responseActive->assertSee('Outlet Aktif');
        $responseActive->assertDontSee('Outlet Nonaktif');

        // Filter INACTIVE
        $responseInactive = $this->actingAs($this->superAdmin)
            ->get(route('platform.tenants.index', ['status' => 'INACTIVE']));
        $responseInactive->assertSee('Outlet Nonaktif');
        $responseInactive->assertDontSee('Outlet Aktif');
    }

    public function test_sorting_functionality_works(): void
    {
        $oA = Outlet::create(['outlet_name' => 'Azzahra Konveksi', 'outlet_code' => 'OUT-A', 'slug' => 'out-a', 'status' => 'ACTIVE', 'created_at' => now()->subDays(5)]);
        $oZ = Outlet::create(['outlet_name' => 'Zahira Konveksi', 'outlet_code' => 'OUT-Z', 'slug' => 'out-z', 'status' => 'ACTIVE', 'created_at' => now()->subDays(1)]);

        // Sort Name A-Z
        $respAsc = $this->actingAs($this->superAdmin)
            ->get(route('platform.tenants.index', ['sort' => 'name_asc']));
        $contentAsc = $respAsc->getContent();
        $this->assertTrue(strpos($contentAsc, 'Azzahra Konveksi') < strpos($contentAsc, 'Zahira Konveksi'));

        // Sort Name Z-A
        $respDesc = $this->actingAs($this->superAdmin)
            ->get(route('platform.tenants.index', ['sort' => 'name_desc']));
        $contentDesc = $respDesc->getContent();
        $this->assertTrue(strpos($contentDesc, 'Zahira Konveksi') < strpos($contentDesc, 'Azzahra Konveksi'));
    }

    public function test_sidebar_highlights_direktori_tenant(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('platform.tenants.index'));

        $content = $response->getContent();

        // Exactly one sidebar
        $this->assertEquals(1, substr_count($content, 'class="n-sidebar"'));

        // Direktori Tenant is marked active
        $this->assertMatchesRegularExpression('/href="[^"]*platform\/tenants"[^>]*class="[^"]*n-nav-link[^"]*is-active/i', $content);
    }

    public function test_super_admin_can_view_tenant_detail_view(): void
    {
        $outlet = Outlet::create([
            'outlet_name' => 'Zhafira Hijab Kudus',
            'outlet_code' => 'OUT-ZHAFIRA-01',
            'slug'        => 'zhafira-hijab-kudus',
            'phone_wa'    => '081299887766',
            'address'     => 'Jl. Menara No. 45, Kudus',
            'status'      => 'ACTIVE',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('platform.tenants.show', $outlet));

        $response->assertStatus(200);
        $response->assertSee('Kembali ke Direktori Tenant');
        $response->assertSee('Zhafira Hijab Kudus');
        $response->assertSee('OUT-ZHAFIRA-01');
        $response->assertSee('zhafira-hijab-kudus');
        $response->assertSee('Kelola Tenant');
        $response->assertSee('Ringkasan');
        $response->assertSee('Informasi Perusahaan');
        $response->assertSee('Layanan & Langganan', false);
        $response->assertSee('Status & Riwayat', false);
        $response->assertSee('Kontak Resmi');
        $response->assertSee('Informasi Utama');
        $response->assertSee('Kontak & Lokasi', false);
        $response->assertSee('Kode Tenant');
        $response->assertSee('Nama Konveksi / Outlet');
        $response->assertSee('WhatsApp / Kontak');
        $response->assertSee('Alamat Workshop');
        $response->assertSee('Kelola Layanan');
        $response->assertSee('LAYANAN');
        $response->assertSee('PAKET / DETAIL');
        $response->assertSee('BERLAKU HINGGA');
    }

    public function test_tenant_detail_hardened_privacy_never_exposes_internal_staff_data(): void
    {
        $outlet = Outlet::create([
            'outlet_name' => 'Konveksi Hijab Rahasia',
            'outlet_code' => 'OUT-RAHASIA-09',
            'slug'        => 'konveksi-hijab-rahasia',
            'status'      => 'ACTIVE',
        ]);

        // Create internal tenant staff with private personal data
        User::create([
            'name'      => 'Budi Supervisor Jahit Rahasia',
            'email'     => 'budi.supervisor@rahasia.test',
            'password'  => Hash::make('Pass123!'),
            'role'      => UserRole::PRODUKSI->value,
            'status'    => UserStatus::ACTIVE->value,
            'outlet_id' => $outlet->id,
        ]);

        User::create([
            'name'      => 'Siti Staff Internal Rahasia',
            'email'     => 'siti.staff@rahasia.test',
            'password'  => Hash::make('Pass123!'),
            'role'      => UserRole::OWNER->value,
            'status'    => UserStatus::ACTIVE->value,
            'outlet_id' => $outlet->id,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('platform.tenants.show', $outlet));

        $response->assertStatus(200);

        // 1. Controller payload does NOT include users
        $response->assertViewMissing('users');

        // 2. Outlet relationship users is NOT loaded
        /** @var Outlet $viewOutlet */
        $viewOutlet = $response->viewData('outlet');
        $this->assertFalse($viewOutlet->relationLoaded('users'), 'Super Admin detail tenant MUST NOT eager load users relation.');

        // 3. HTML does NOT disclose private employee names or emails
        $response->assertDontSee('Budi Supervisor Jahit Rahasia');
        $response->assertDontSee('budi.supervisor@rahasia.test');
        $response->assertDontSee('Siti Staff Internal Rahasia');
        $response->assertDontSee('siti.staff@rahasia.test');

        // 4. Disallowed internal tables or badges are absent
        $response->assertDontSee('Akun Pengguna Terdaftar');
        $response->assertDontSee('Daftar akun staf yang terikat');
        $response->assertDontSee('Role ERP');
    }

    public function test_super_admin_can_toggle_tenant_status(): void
    {
        $outlet = Outlet::create([
            'outlet_name' => 'Konveksi Mutiara',
            'outlet_code' => 'OUT-MUTIARA',
            'slug'        => 'konveksi-mutiara',
            'status'      => 'ACTIVE',
        ]);

        // Suspend
        $responseSuspend = $this->actingAs($this->superAdmin)
            ->patch(route('platform.tenants.toggle-status', $outlet));

        $responseSuspend->assertRedirect(route('platform.tenants.show', $outlet));
        $this->assertEquals('INACTIVE', $outlet->fresh()->status);

        // Reactivate
        $responseReactivate = $this->actingAs($this->superAdmin)
            ->patch(route('platform.tenants.toggle-status', $outlet));

        $responseReactivate->assertRedirect(route('platform.tenants.show', $outlet));
        $this->assertEquals('ACTIVE', $outlet->fresh()->status);
    }

    public function test_super_admin_can_toggle_tenant_status_with_audit_reason(): void
    {
        $outlet = Outlet::create([
            'outlet_name' => 'Konveksi Berlian',
            'outlet_code' => 'OUT-BERLIAN',
            'slug'        => 'konveksi-berlian',
            'status'      => 'ACTIVE',
        ]);

        $auditReason = 'Permintaan resmi pemilik usaha karena maintenance workshop';

        $response = $this->actingAs($this->superAdmin)
            ->patch(route('platform.tenants.toggle-status', $outlet), [
                'reason' => $auditReason,
            ]);

        $response->assertRedirect(route('platform.tenants.show', $outlet));
        $response->assertSessionHas('success');
        $this->assertStringContainsString($auditReason, session('success'));
        $this->assertEquals('INACTIVE', $outlet->fresh()->status);
    }
}
