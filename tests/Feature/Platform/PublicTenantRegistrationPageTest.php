<?php

namespace Tests\Feature\Platform;

use App\Models\Platform\TenantRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicTenantRegistrationPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['nayara.tenant_isolation_enabled' => false]);
    }

    public function test_public_registration_page_can_be_viewed(): void
    {
        $response = $this->get(route('tenant.register.create'));

        $response->assertOk();
        $response->assertSee('NAYARA SaaS ERP');
        $response->assertSee('Platform ERP Multi-Tenant Khusus Konveksi Hijab Mandiri.');
        $response->assertSee('PENDAFTARAN 100% GRATIS');
        $response->assertSee('Formulir Pendaftaran Perusahaan Baru');
        $response->assertSee('Satu Outlet = Satu Perusahaan');
        $response->assertSee('Identitas Usaha / Konveksi');
        $response->assertSee('Akun Pemilik (Owner)');
        $response->assertSee('Privasi &amp; Keamanan Data Multi-Tenant', false);
        $response->assertSee('Daftarkan Konveksi Sekarang (Gratis)');
        $response->assertSee('Cek Status Pengajuan');
        $response->assertSee('Masuk (Login ERP)');
        $response->assertDontSee('EXECUTIVE MONITORING');
    }

    public function test_public_registration_submission_succeeds(): void
    {
        $payload = [
            'company_name'          => 'Konveksi Hijab Barokah Jaya',
            'owner_name'            => 'Hj. Siti Aisyah',
            'email'                 => 'aisyah@barokahjaya.test',
            'phone_wa'              => '081298765432',
            'address'               => 'Jl. Industri Tekstil No. 88, Soreang',
            'notes'                 => 'Kapasitas produksi 5.000 pcs per bulan',
            'password'              => 'BarokahSecure2026!',
            'password_confirmation' => 'BarokahSecure2026!',
        ];

        $response = $this->post(route('tenant.register.store'), $payload);

        $response->assertRedirect(route('tenant.register.success'));
        $response->assertSessionHas('company_name', 'Konveksi Hijab Barokah Jaya');
        $response->assertSessionHas('email', 'aisyah@barokahjaya.test');

        $this->assertDatabaseHas('tenant_registrations', [
            'company_name' => 'Konveksi Hijab Barokah Jaya',
            'owner_name'   => 'Hj. Siti Aisyah',
            'email'        => 'aisyah@barokahjaya.test',
            'phone_wa'     => '081298765432',
            'status'       => TenantRegistration::STATUS_PENDING,
        ]);
    }

    public function test_public_registration_validation_fails_on_missing_fields(): void
    {
        $response = $this->post(route('tenant.register.store'), []);

        $response->assertSessionHasErrors([
            'company_name',
            'owner_name',
            'email',
            'phone_wa',
            'password',
        ]);
    }

    public function test_password_confirmation_must_match(): void
    {
        $response = $this->post(route('tenant.register.store'), [
            'company_name'          => 'CV Test',
            'owner_name'            => 'Owner Test',
            'email'                 => 'test@example.com',
            'phone_wa'              => '081234567890',
            'password'              => 'Password123!',
            'password_confirmation' => 'DifferentPassword123!',
        ]);

        $response->assertSessionHasErrors('password');
    }
}

