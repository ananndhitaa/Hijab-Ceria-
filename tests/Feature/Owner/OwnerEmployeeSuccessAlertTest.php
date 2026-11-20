<?php

namespace Tests\Feature\Owner;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\System\Outlet;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerEmployeeSuccessAlertTest extends TestCase
{
    use RefreshDatabase;

    private Outlet $outlet;
    private User $owner;
    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outlet = Outlet::create([
            'outlet_name' => 'Nayara Hijab Kudus',
            'outlet_code' => 'OUT-KUDUS',
            'status'      => 'ACTIVE',
        ]);

        $this->owner = User::factory()->create([
            'outlet_id' => $this->outlet->id,
            'name'      => 'Owner Nayara',
            'email'     => 'owner@nayara.test',
            'role'      => UserRole::OWNER,
            'status'    => UserStatus::ACTIVE,
            'password'  => bcrypt('ownerpassword'),
        ]);

        $this->employee = User::factory()->create([
            'outlet_id' => $this->outlet->id,
            'name'      => 'Sinta Produksi',
            'email'     => 'sinta@nayara.test',
            'role'      => UserRole::PRODUKSI,
            'status'    => UserStatus::ACTIVE,
            'password'  => bcrypt('secret123'),
        ]);
    }

    public function test_owner_employees_index_renders_single_plus_tambah_karyawan_button(): void
    {
        $response = $this->actingAs($this->owner)->get(route('owner.employees.index'));

        $response->assertOk();
        $response->assertSee('Tambah Karyawan');
        $response->assertDontSee('+ + Tambah Karyawan');
        $response->assertDontSee('+ Tambah Karyawan</span>', false);
    }

    public function test_owner_employees_index_with_flash_success_renders_compact_alert_with_explicit_sizing(): void
    {
        $message = "Data karyawan {$this->employee->name} berhasil diperbarui.";

        $response = $this->actingAs($this->owner)
            ->withSession(['success' => $message])
            ->get(route('owner.employees.index'));

        $response->assertOk();
        $content = $response->getContent();

        // Must display message exactly once
        $this->assertEquals(1, substr_count($content, $message));

        // Must have explicit w-5 h-5 shrink-0 and width/height attributes on icon
        $this->assertStringContainsString('w-5 h-5 text-emerald-600 shrink-0', $content);
        $this->assertStringContainsString('width="20" height="20"', $content);

        // Close icon must have explicit w-4 h-4 shrink-0
        $this->assertStringContainsString('w-4 h-4 shrink-0', $content);
        $this->assertStringContainsString('width="16" height="16"', $content);

        // Alert container must be compact and have correct styling
        $this->assertStringContainsString('min-h-[48px]', $content);
        $this->assertStringContainsString('bg-emerald-50 border border-emerald-200 text-emerald-800', $content);

        // Must NOT contain invalid non-Tailwind w-4.5 or h-4.5 classes
        $this->assertStringNotContainsString('w-4.5', $content);
        $this->assertStringNotContainsString('h-4.5', $content);
    }

    public function test_owner_update_employee_redirects_and_renders_compact_success_alert(): void
    {
        $updateResponse = $this->actingAs($this->owner)
            ->put(route('owner.employees.update', $this->employee), [
                'name'   => 'Sinta Update',
                'email'  => 'sinta.update@nayara.test',
                'role'   => UserRole::PRODUKSI->value,
                'status' => UserStatus::ACTIVE->value,
            ]);

        $updateResponse->assertRedirect(route('owner.employees.index'));
        $updateResponse->assertSessionHas('success', 'Data karyawan Sinta Update berhasil diperbarui.');

        $followResponse = $this->actingAs($this->owner)->get(route('owner.employees.index'));
        $followResponse->assertOk();

        // Explicit icon sizing on index page
        $followResponse->assertDontSee('w-4.5');
        $followResponse->assertDontSee('h-4.5');
        $followResponse->assertDontSee('+ + Tambah Karyawan');
    }

    public function test_flash_message_supports_error_warning_and_info_with_explicit_sizing(): void
    {
        // 1. Error flash
        $errResponse = $this->actingAs($this->owner)
            ->withSession(['error' => 'Terjadi kesalahan sistem.'])
            ->get(route('owner.employees.index'));

        $errContent = $errResponse->getContent();
        $this->assertStringContainsString('Terjadi kesalahan sistem.', $errContent);
        $this->assertStringContainsString('w-5 h-5 text-red-600 shrink-0', $errContent);
        $this->assertStringNotContainsString('w-4.5', $errContent);

        // 2. Warning flash
        $warnResponse = $this->actingAs($this->owner)
            ->withSession(['warning' => 'Peringatan kuota akun hampir habis.'])
            ->get(route('owner.employees.index'));

        $warnContent = $warnResponse->getContent();
        $this->assertStringContainsString('Peringatan kuota akun hampir habis.', $warnContent);
        $this->assertStringContainsString('w-5 h-5 text-amber-600 shrink-0', $warnContent);

        // 3. Info flash
        $infoResponse = $this->actingAs($this->owner)
            ->withSession(['info' => 'Informasi pembaruan sistem jam 22:00 WIB.'])
            ->get(route('owner.employees.index'));

        $infoContent = $infoResponse->getContent();
        $this->assertStringContainsString('Informasi pembaruan sistem jam 22:00 WIB.', $infoContent);
        $this->assertStringContainsString('w-5 h-5 text-sky-600 shrink-0', $infoContent);
    }
}
