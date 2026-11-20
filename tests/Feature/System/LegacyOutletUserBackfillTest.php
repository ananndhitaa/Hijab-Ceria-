<?php

namespace Tests\Feature\System;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\System\Outlet;
use App\Models\System\User;
use Database\Seeders\LegacyOutletSeeder;
use DomainException;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyOutletUserBackfillTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper untuk membuat akun legacy standar single-tenant ERP.
     *
     * @return array<string, User>
     */
    protected function createStandardLegacyUsers(): array
    {
        return [
            'owner' => User::factory()->create([
                'name'     => 'Owner Konveksi Legacy',
                'email'    => 'owner@nayara.test',
                'role'     => UserRole::OWNER,
                'status'   => UserStatus::ACTIVE,
                'outlet_id'=> null,
            ]),
            'admin' => User::factory()->create([
                'name'     => 'Admin Konveksi Legacy',
                'email'    => 'admin@nayara.test',
                'role'     => UserRole::ADMIN,
                'status'   => UserStatus::ACTIVE,
                'outlet_id'=> null,
            ]),
            'gudang' => User::factory()->create([
                'name'     => 'Staff Gudang Legacy',
                'email'    => 'gudang@nayara.test',
                'role'     => UserRole::GUDANG,
                'status'   => UserStatus::ACTIVE,
                'outlet_id'=> null,
            ]),
            'produksi' => User::factory()->create([
                'name'     => 'Staff Produksi Legacy',
                'email'    => 'produksi@nayara.test',
                'role'     => UserRole::PRODUKSI,
                'status'   => UserStatus::ACTIVE,
                'outlet_id'=> null,
            ]),
            'customer' => User::factory()->create([
                'name'     => 'Customer Demo Legacy',
                'email'    => 'customer@nayara.test',
                'role'     => UserRole::CUSTOMER,
                'status'   => UserStatus::ACTIVE,
                'outlet_id'=> null,
            ]),
        ];
    }

    /**
     * 1. Seeder berhasil membuat outlet legacy dengan atribut yang benar.
     */
    public function test_01_seeder_successfully_creates_legacy_outlet_with_valid_attributes(): void
    {
        $this->createStandardLegacyUsers();

        $this->seed(LegacyOutletSeeder::class);

        $this->assertDatabaseCount('outlets', 1);

        $outlet = Outlet::where('outlet_code', LegacyOutletSeeder::LEGACY_OUTLET_CODE)->first();
        $this->assertNotNull($outlet);
        $this->assertSame(LegacyOutletSeeder::LEGACY_OUTLET_NAME, $outlet->outlet_name);
        $this->assertSame(LegacyOutletSeeder::LEGACY_OUTLET_SLUG, $outlet->slug);
        $this->assertSame('ACTIVE', $outlet->status);
        $this->assertTrue((bool) $outlet->is_public_showcase_enabled);
    }

    /**
     * 2. Seluruh akun legacy yang memenuhi syarat tertaut dengan benar ke outlet legacy.
     */
    public function test_02_all_eligible_existing_users_are_linked_to_the_same_legacy_outlet(): void
    {
        $users = $this->createStandardLegacyUsers();

        $this->seed(LegacyOutletSeeder::class);

        $outlet = Outlet::where('outlet_code', LegacyOutletSeeder::LEGACY_OUTLET_CODE)->first();
        $this->assertNotNull($outlet);

        foreach ($users as $user) {
            $this->assertSame($outlet->id, $user->fresh()->outlet_id);
        }

        $this->assertSame(count($users), $outlet->users()->count());
    }

    /**
     * 3. Role, email, status, dan password akun existing tetap tidak berubah.
     */
    public function test_03_user_roles_passwords_and_emails_remain_completely_unchanged(): void
    {
        $users = $this->createStandardLegacyUsers();

        $expectedSnapshots = [];
        foreach ($users as $key => $user) {
            $expectedSnapshots[$key] = [
                'role'     => $user->role,
                'email'    => $user->email,
                'password' => $user->password,
                'status'   => $user->status,
            ];
        }

        $this->seed(LegacyOutletSeeder::class);

        foreach ($users as $key => $user) {
            $fresh = $user->fresh();
            $this->assertSame($expectedSnapshots[$key]['role'], $fresh->role, "Role user {$key} berubah!");
            $this->assertSame($expectedSnapshots[$key]['email'], $fresh->email, "Email user {$key} berubah!");
            $this->assertSame($expectedSnapshots[$key]['password'], $fresh->password, "Password hash user {$key} berubah!");
            $this->assertSame($expectedSnapshots[$key]['status'], $fresh->status, "Status user {$key} berubah!");
        }

        // Akun customer tetap ber-role CUSTOMER (bukan internal staff)
        $this->assertSame(UserRole::CUSTOMER, $users['customer']->fresh()->role);
    }

    /**
     * 4. Data user tidak terhapus selama atau setelah proses seeding.
     */
    public function test_04_user_records_are_never_deleted(): void
    {
        $users = $this->createStandardLegacyUsers();
        $initialUserCount = User::count();

        $this->seed(LegacyOutletSeeder::class);

        $this->assertSame($initialUserCount, User::count());
        foreach ($users as $user) {
            $this->assertDatabaseHas('users', ['id' => $user->id]);
        }
    }

    /**
     * 5. Pengulangan seeder bersifat idempotent (tidak membuat outlet duplikat).
     */
    public function test_05_subsequent_seeder_runs_are_idempotent_and_do_not_duplicate_outlets(): void
    {
        $this->createStandardLegacyUsers();

        // Run pertama
        $this->seed(LegacyOutletSeeder::class);
        $this->assertDatabaseCount('outlets', 1);
        $outletFirstRun = Outlet::where('outlet_code', LegacyOutletSeeder::LEGACY_OUTLET_CODE)->first();

        // Run kedua
        $this->seed(LegacyOutletSeeder::class);
        $this->assertDatabaseCount('outlets', 1);
        $outletSecondRun = Outlet::where('outlet_code', LegacyOutletSeeder::LEGACY_OUTLET_CODE)->first();

        $this->assertSame($outletFirstRun->id, $outletSecondRun->id);
    }

    /**
     * 6. Tidak mengasumsikan outlet ID 1 (mendukung ID outlet arbitrer).
     */
    public function test_06_does_not_assume_legacy_outlet_has_id_1(): void
    {
        // Buat outlet dummy sebelumnya sehingga ID 1 terpakai
        $dummyOutlet = Outlet::factory()->create([
            'outlet_name' => 'Outlet Cabang Pertama',
            'outlet_code' => 'OUT-CABANG-01',
            'slug'        => 'outlet-cabang-01',
        ]);
        $this->assertSame(1, (int) $dummyOutlet->id);

        $users = $this->createStandardLegacyUsers();

        $this->seed(LegacyOutletSeeder::class);

        $legacyOutlet = Outlet::where('outlet_code', LegacyOutletSeeder::LEGACY_OUTLET_CODE)->first();
        $this->assertNotNull($legacyOutlet);
        $this->assertGreaterThan(1, (int) $legacyOutlet->id);

        // Pastikan seluruh akun legacy tertaut ke ID legacy outlet (bukan ID 1)
        foreach ($users as $user) {
            $this->assertSame($legacyOutlet->id, $user->fresh()->outlet_id);
            $this->assertNotSame($dummyOutlet->id, $user->fresh()->outlet_id);
        }

        // Outlet dummy tetap memiliki 0 users
        $this->assertSame(0, $dummyOutlet->users()->count());
    }

    /**
     * 7. Seeder menolak kondisi multiple Owner yang ambigu.
     */
    public function test_07_aborts_safely_when_multiple_unassigned_owners_exist(): void
    {
        // 2 Akun Owner
        User::factory()->create([
            'name'     => 'Owner Pertama',
            'email'    => 'owner1@nayara.test',
            'role'     => UserRole::OWNER,
            'outlet_id'=> null,
        ]);
        User::factory()->create([
            'name'     => 'Owner Kedua',
            'email'    => 'owner2@nayara.test',
            'role'     => UserRole::OWNER,
            'outlet_id'=> null,
        ]);
        User::factory()->create([
            'name'     => 'Admin Toko',
            'email'    => 'admin@nayara.test',
            'role'     => UserRole::ADMIN,
            'outlet_id'=> null,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('pemilik sah konveksi tidak tunggal');

        (new LegacyOutletSeeder())->run();

        // Tidak ada outlet legacy yang dibuat
        $this->assertDatabaseCount('outlets', 0);
        $this->assertSame(0, User::whereNotNull('outlet_id')->count());
    }

    /**
     * 8. Seeder menolak pemindahan pengguna yang sudah terikat dengan outlet berbeda.
     */
    public function test_08_preserves_users_already_assigned_to_another_outlet(): void
    {
        $otherOutlet = Outlet::factory()->create([
            'outlet_name' => 'Outlet Mitra Surabaya',
            'outlet_code' => 'OUT-SBY-01',
            'slug'        => 'outlet-mitra-surabaya',
        ]);

        $users = $this->createStandardLegacyUsers();

        // Pindahkan salah satu staf (gudang) ke outlet lain sebelum seeding
        $users['gudang']->update(['outlet_id' => $otherOutlet->id]);

        $this->seed(LegacyOutletSeeder::class);

        $legacyOutlet = Outlet::where('outlet_code', LegacyOutletSeeder::LEGACY_OUTLET_CODE)->first();

        // Staf gudang TETAP di outlet mitra (tidak dipindah)
        $this->assertSame($otherOutlet->id, $users['gudang']->fresh()->outlet_id);

        // Akun legacy unassigned lainnya masuk ke legacy outlet
        $this->assertSame($legacyOutlet->id, $users['owner']->fresh()->outlet_id);
        $this->assertSame($legacyOutlet->id, $users['admin']->fresh()->outlet_id);
        $this->assertSame($legacyOutlet->id, $users['produksi']->fresh()->outlet_id);
        $this->assertSame($legacyOutlet->id, $users['customer']->fresh()->outlet_id);
    }

    /**
     * 9. Database kosong tanpa akun legacy tidak menghasilkan tenant palsu.
     */
    public function test_09_empty_database_without_legacy_users_does_not_create_fake_tenant(): void
    {
        $this->assertSame(0, User::count());

        $this->seed(LegacyOutletSeeder::class);

        // Tidak boleh ada outlet yang dibuat
        $this->assertDatabaseCount('outlets', 0);
    }

    /**
     * 10. Kondisi pemetaan parsial yang tidak konsisten ditolak (Owner terikat ke outlet lain).
     */
    public function test_10_rejects_inconsistent_partial_mapping_when_owner_is_bound_to_different_outlet(): void
    {
        $otherOutlet = Outlet::factory()->create([
            'outlet_name' => 'Outlet Lain',
            'outlet_code' => 'OUT-OTHER-99',
            'slug'        => 'outlet-lain-99',
        ]);

        // Owner terikat ke outlet lain
        User::factory()->create([
            'name'     => 'Owner Konveksi',
            'email'    => 'owner@nayara.test',
            'role'     => UserRole::OWNER,
            'outlet_id'=> $otherOutlet->id,
        ]);

        // Staf konveksi masih unassigned
        $admin = User::factory()->create([
            'name'     => 'Admin Konveksi',
            'email'    => 'admin@nayara.test',
            'role'     => UserRole::ADMIN,
            'outlet_id'=> null,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Inkonsistensi terdeteksi: Akun Owner');

        (new LegacyOutletSeeder())->run();

        // Staf admin tetap unassigned
        $this->assertNull($admin->fresh()->outlet_id);
    }

    /**
     * 11. Kondisi pemetaan parsial ditolak jika outlet legacy ada staf terpetakan tanpa owner terpetakan.
     */
    public function test_11_rejects_inconsistent_partial_mapping_when_outlet_has_mapped_staff_but_no_owner(): void
    {
        $legacyOutlet = Outlet::factory()->create([
            'outlet_name' => LegacyOutletSeeder::LEGACY_OUTLET_NAME,
            'outlet_code' => LegacyOutletSeeder::LEGACY_OUTLET_CODE,
            'slug'        => LegacyOutletSeeder::LEGACY_OUTLET_SLUG,
        ]);

        // Staf terpetakan ke outlet legacy tanpa ada owner yang terpetakan
        User::factory()->create([
            'name'     => 'Staff Admin Mapped',
            'email'    => 'admin-mapped@nayara.test',
            'role'     => UserRole::ADMIN,
            'outlet_id'=> $legacyOutlet->id,
        ]);

        // Owner masih unassigned
        User::factory()->create([
            'name'     => 'Owner Unassigned',
            'email'    => 'owner-unassigned@nayara.test',
            'role'     => UserRole::OWNER,
            'outlet_id'=> null,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Inkonsistensi terdeteksi: Outlet legacy');

        (new LegacyOutletSeeder())->run();
    }

    /**
     * 12. Kegagalan di tengah transaksi me-rollback seluruh perubahan secara bersih.
     */
    public function test_12_transaction_failure_midway_rolls_back_all_changes_cleanly(): void
    {
        $users = $this->createStandardLegacyUsers();

        // Simulasikan kegagalan saat updating user kedua
        User::updating(function (User $user) use ($users) {
            if ($user->id === $users['admin']->id) {
                throw new Exception('Simulasi kegagalan IO database di tengah transaksi');
            }
        });

        try {
            (new LegacyOutletSeeder())->run();
            $this->fail('Harusnya melemparkan Exception simulasi.');
        } catch (Exception $e) {
            $this->assertSame('Simulasi kegagalan IO database di tengah transaksi', $e->getMessage());
        }

        // Verifikasi rollback:
        // 1. Outlet legacy TIDAK tersimpan (di-rollback)
        $this->assertDatabaseCount('outlets', 0);

        // 2. Tidak ada user yang berubah outlet_id-nya
        foreach ($users as $user) {
            $this->assertNull($user->fresh()->outlet_id);
        }
    }

    /**
     * 13. Tidak ada heuristik nama atau email; penentuan role murni berbasis enum UserRole resmi.
     */
    public function test_13_no_email_or_name_heuristics_used_strictly_validates_official_roles(): void
    {
        // User dengan nama atau email yang mengandung string khusus (misal 'super admin')
        // tetap dievaluasi sesuai enum UserRole resminya (ADMIN)
        $users = [
            'owner' => User::factory()->create([
                'name'     => 'Owner Konveksi Utama',
                'email'    => 'pemilik@nayara.test',
                'role'     => UserRole::OWNER,
                'outlet_id'=> null,
            ]),
            'admin' => User::factory()->create([
                'name'     => 'Super Admin Platform System',
                'email'    => 'superadmin@nayara.test',
                'role'     => UserRole::ADMIN,
                'outlet_id'=> null,
            ]),
        ];

        $this->seed(LegacyOutletSeeder::class);

        $legacyOutlet = Outlet::where('outlet_code', LegacyOutletSeeder::LEGACY_OUTLET_CODE)->first();
        $this->assertNotNull($legacyOutlet);

        // Akun admin tetap terpetakan ke outlet legacy karena memiliki role resmi ADMIN
        $this->assertSame($legacyOutlet->id, $users['admin']->fresh()->outlet_id);
        $this->assertSame(UserRole::ADMIN, $users['admin']->fresh()->role);
    }

    /**
     * 14. Tidak ada circular dependency antara outlet dan user.
     */
    public function test_14_no_circular_dependency_between_outlet_and_user(): void
    {
        // Outlet dapat dibuat tanpa mensyaratkan user_id atau owner_user_id di skemanya
        $outlet = Outlet::create([
            'outlet_name' => 'Independent Konveksi',
            'outlet_code' => 'OUT-INDEP-01',
            'slug'        => 'independent-konveksi',
        ]);

        $this->assertNotNull($outlet->id);
        $this->assertDatabaseHas('outlets', ['id' => $outlet->id]);

        // User menunjuk ke outlet melalui outlet_id (one-to-many bersih)
        $user = User::factory()->create([
            'outlet_id' => $outlet->id,
            'role'      => UserRole::OWNER,
        ]);

        $this->assertSame($outlet->id, $user->outlet->id);
        $this->assertTrue($outlet->users->contains($user));
    }
}
