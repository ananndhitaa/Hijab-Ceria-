<?php

namespace Tests\Feature\System;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\System\Outlet;
use App\Models\System\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OutletFoundationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. Tabel outlets dapat dibuat melalui migration di SQLite testing.
     */
    public function test_01_outlets_table_exists_and_has_expected_schema(): void
    {
        $this->assertTrue(Schema::hasTable('outlets'));
        $this->assertTrue(Schema::hasColumns('outlets', [
            'id',
            'outlet_name',
            'outlet_code',
            'slug',
            'phone_wa',
            'address',
            'description',
            'logo_path',
            'status',
            'is_public_showcase_enabled',
            'created_at',
            'updated_at',
        ]));

        $this->assertTrue(Schema::hasColumn('users', 'outlet_id'));
    }

    /**
     * 2. Model Outlet dapat menyimpan data outlet yang valid.
     */
    public function test_02_outlet_model_can_persist_valid_data(): void
    {
        $outlet = Outlet::create([
            'outlet_name'                => 'NAYARA Hijab Bandung',
            'outlet_code'                => 'OUT-BDG-01',
            'slug'                       => 'nayara-hijab-bandung',
            'phone_wa'                   => '081234567890',
            'address'                    => 'Jl. Riau No. 123, Bandung',
            'description'                => 'Konveksi spesialis pashmina dan bergo',
            'logo_path'                  => 'logos/out-bdg-01.png',
            'status'                     => 'ACTIVE',
            'is_public_showcase_enabled' => true,
        ]);

        $this->assertDatabaseHas('outlets', [
            'id'          => $outlet->id,
            'outlet_code' => 'OUT-BDG-01',
            'slug'        => 'nayara-hijab-bandung',
            'status'      => 'ACTIVE',
        ]);

        $this->assertTrue($outlet->is_public_showcase_enabled);
        $this->assertInstanceOf(Outlet::class, $outlet);
    }

    /**
     * 3. outlet_code harus unik secara global.
     */
    public function test_03_outlet_code_must_be_globally_unique(): void
    {
        Outlet::create([
            'outlet_name' => 'Outlet Pertama',
            'outlet_code' => 'NYR-JKT-01',
        ]);

        $this->expectException(QueryException::class);

        Outlet::create([
            'outlet_name' => 'Outlet Duplikat',
            'outlet_code' => 'NYR-JKT-01',
        ]);
    }

    /**
     * 4. slug harus unik jika diisi, tetapi boleh null ganda.
     */
    public function test_04_slug_must_be_unique_when_provided_and_allows_null(): void
    {
        // Null slug boleh ada lebih dari satu saat profil publik belum dibuat
        $outlet1 = Outlet::create([
            'outlet_name' => 'Outlet Draft 1',
            'outlet_code' => 'OUT-DRAFT-1',
            'slug'        => null,
        ]);

        $outlet2 = Outlet::create([
            'outlet_name' => 'Outlet Draft 2',
            'outlet_code' => 'OUT-DRAFT-2',
            'slug'        => null,
        ]);

        $this->assertNull($outlet1->slug);
        $this->assertNull($outlet2->slug);

        // Namun jika slug terisi, duplikasi harus ditolak
        Outlet::create([
            'outlet_name' => 'Outlet Publik 1',
            'outlet_code' => 'OUT-PUB-1',
            'slug'        => 'konveksi-nayara',
        ]);

        $this->expectException(QueryException::class);

        Outlet::create([
            'outlet_name' => 'Outlet Publik 2',
            'outlet_code' => 'OUT-PUB-2',
            'slug'        => 'konveksi-nayara',
        ]);
    }

    /**
     * 5. Users dapat dihubungkan ke outlet (User belongsTo Outlet).
     */
    public function test_05_user_can_be_associated_with_an_outlet(): void
    {
        $outlet = Outlet::create([
            'outlet_name' => 'NAYARA Jakarta Pusat',
            'outlet_code' => 'OUT-JKT-01',
        ]);

        $user = User::factory()->create([
            'name'      => 'Ahmad Admin',
            'role'      => UserRole::ADMIN,
            'outlet_id' => $outlet->id,
        ]);

        $this->assertNotNull($user->outlet);
        $this->assertEquals($outlet->id, $user->outlet->id);
        $this->assertEquals('NAYARA Jakarta Pusat', $user->outlet->outlet_name);
    }

    /**
     * 6. Satu outlet dapat memiliki beberapa karyawan dengan berbagai role.
     */
    public function test_06_one_outlet_can_have_multiple_employees_with_different_roles(): void
    {
        $outlet = Outlet::create([
            'outlet_name' => 'NAYARA Surabaya',
            'outlet_code' => 'OUT-SBY-01',
        ]);

        $owner = User::factory()->create(['name' => 'Budi Owner', 'role' => UserRole::OWNER, 'outlet_id' => $outlet->id]);
        $admin = User::factory()->create(['name' => 'Citra Admin', 'role' => UserRole::ADMIN, 'outlet_id' => $outlet->id]);
        $gudang = User::factory()->create(['name' => 'Dodi Gudang', 'role' => UserRole::GUDANG, 'outlet_id' => $outlet->id]);
        $produksi = User::factory()->create(['name' => 'Eko Produksi', 'role' => UserRole::PRODUKSI, 'outlet_id' => $outlet->id]);

        $this->assertCount(4, $outlet->users);
        $this->assertTrue($outlet->users->contains($owner));
        $this->assertTrue($outlet->users->contains($admin));
        $this->assertTrue($outlet->users->contains($gudang));
        $this->assertTrue($outlet->users->contains($produksi));
    }

    /**
     * 7. Dua outlet dapat mempunyai user masing-masing yang terisolasi.
     */
    public function test_07_two_outlets_have_independent_user_populations(): void
    {
        $outletA = Outlet::create(['outlet_name' => 'Outlet Bandung', 'outlet_code' => 'OUT-A']);
        $outletB = Outlet::create(['outlet_name' => 'Outlet Semarang', 'outlet_code' => 'OUT-B']);

        $userA1 = User::factory()->create(['name' => 'Staff A1', 'outlet_id' => $outletA->id]);
        $userA2 = User::factory()->create(['name' => 'Staff A2', 'outlet_id' => $outletA->id]);
        $userB1 = User::factory()->create(['name' => 'Staff B1', 'outlet_id' => $outletB->id]);

        $this->assertCount(2, $outletA->users);
        $this->assertCount(1, $outletB->users);

        $this->assertTrue($outletA->users->contains($userA1));
        $this->assertTrue($outletA->users->contains($userA2));
        $this->assertFalse($outletA->users->contains($userB1));

        $this->assertTrue($outletB->users->contains($userB1));
        $this->assertFalse($outletB->users->contains($userA1));
    }

    /**
     * 8. Penghapusan outlet yang masih memiliki user ditolak oleh Foreign Key constraint (RESTRICT).
     */
    public function test_08_deleting_outlet_with_assigned_users_is_restricted_by_foreign_key(): void
    {
        $outlet = Outlet::create([
            'outlet_name' => 'Outlet Terproteksi',
            'outlet_code' => 'OUT-PROT-01',
        ]);

        User::factory()->create([
            'name'      => 'Karyawan Aktif',
            'outlet_id' => $outlet->id,
        ]);

        $this->expectException(QueryException::class);

        // Menghapus outlet saat masih ada user harus dilempar Exception oleh database foreign key
        $outlet->delete();
    }

    /**
     * 9. Factory dan role existing tetap kompatibel tanpa wajib outlet_id (backward compatible).
     */
    public function test_09_existing_factories_and_roles_remain_compatible_without_outlet(): void
    {
        $roles = [
            UserRole::ADMIN,
            UserRole::GUDANG,
            UserRole::PRODUKSI,
            UserRole::OWNER,
            UserRole::CUSTOMER,
        ];

        foreach ($roles as $role) {
            $user = User::factory()->create([
                'role'   => $role,
                'status' => UserStatus::ACTIVE,
            ]);

            $this->assertNull($user->outlet_id);
            $this->assertNull($user->outlet);
            $this->assertEquals($role, $user->role);
            $this->assertTrue($user->isActive());
        }
    }

    /**
     * 10. Tidak terjadi circular dependency saat pembuatan User Owner dan Outlet.
     */
    public function test_10_no_circular_dependency_when_registering_outlet_and_owner(): void
    {
        // 1. Buat User Owner terlebih dahulu tanpa outlet (outlet_id null)
        $owner = User::create([
            'name'     => 'Hj. Fatimah',
            'email'    => 'fatimah@nayarahijab.com',
            'password' => 'secret123',
            'role'     => UserRole::OWNER,
            'status'   => UserStatus::ACTIVE,
        ]);

        $this->assertNull($owner->outlet_id);

        // 2. Buat Outlet konveksi miliknya
        $outlet = Outlet::create([
            'outlet_name' => 'NAYARA Hijab Solo',
            'outlet_code' => 'OUT-SLO-01',
            'slug'        => 'nayara-hijab-solo',
            'phone_wa'    => '0899112233',
        ]);

        // 3. Hubungkan Owner ke Outlet
        $owner->update(['outlet_id' => $outlet->id]);

        $owner->refresh();
        $this->assertEquals($outlet->id, $owner->outlet_id);
        $this->assertEquals('OUT-SLO-01', $owner->outlet->outlet_code);
        $this->assertTrue($outlet->users->contains($owner));
    }
}
