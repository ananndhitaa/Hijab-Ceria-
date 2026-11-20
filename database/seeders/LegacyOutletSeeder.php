<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\System\Outlet;
use App\Models\System\User;
use DomainException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class LegacyOutletSeeder extends Seeder
{
    public const LEGACY_OUTLET_CODE = 'OUT-NAYARA-PUSAT';
    public const LEGACY_OUTLET_NAME = 'NAYARA Hijab Pusat';
    public const LEGACY_OUTLET_SLUG = 'nayara-hijab-pusat';

    /**
     * Run the database seeds.
     *
     * IMPORTANT:
     * Dijalankan secara eksplisit untuk menautkan akun ERP legacy ke outlet awal NAYARA Group.
     * Tidak dijalankan otomatis di DatabaseSeeder utama.
     */
    public function run(): void
    {
        // 1. Verifikasi Prasyarat Migrasi Skema
        if (! Schema::hasTable('outlets')) {
            throw new RuntimeException("Prasyarat gagal: Tabel 'outlets' belum tersedia di database. Pastikan migration telah dieksekusi.");
        }
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'outlet_id')) {
            throw new RuntimeException("Prasyarat gagal: Kolom 'users.outlet_id' belum tersedia di database. Pastikan migration telah dieksekusi.");
        }

        $allUsers = User::all();

        // 2. Jika database kosong tanpa akun legacy, jangan buat tenant palsu
        if ($allUsers->isEmpty()) {
            if ($this->command) {
                $this->command->warn('ℹ️  Database tidak memiliki data user. LegacyOutletSeeder dilewati.');
            }
            return;
        }

        // 3. Validasi Role Resmi (Berdasarkan enum UserRole, tanpa heuristik nama/email)
        $validRoles = array_map(fn (UserRole $role) => $role->value, UserRole::cases());
        foreach ($allUsers as $user) {
            $roleValue = $user->role instanceof \BackedEnum ? $user->role->value : (string) $user->role;
            if (! in_array($roleValue, $validRoles, true)) {
                throw new DomainException("Tolak: User ID {$user->id} ({$user->name}) memiliki role tidak dikenal '{$roleValue}'.");
            }
        }

        // 4. Validasi Integritas Foreign Key (Cegah orphan outlet_id)
        $assignedUsers = $allUsers->filter(fn (User $u) => $u->outlet_id !== null);
        if ($assignedUsers->isNotEmpty()) {
            $existingOutletIds = Outlet::pluck('id')->all();
            $orphanedUsers = $assignedUsers->reject(fn (User $u) => in_array($u->outlet_id, $existingOutletIds, true));
            if ($orphanedUsers->isNotEmpty()) {
                $orphanedIds = $orphanedUsers->pluck('id')->join(', ');
                throw new DomainException("Tolak: Ditemukan user dengan outlet_id yang tidak terdaftar di tabel outlets (User ID: {$orphanedIds}). Rekonsiliasi manual diperlukan.");
            }
        }

        // 5. Resolusi Outlet Legacy Berdasarkan Kode Unik (Bukan Asumsi ID = 1)
        /** @var Outlet|null $legacyOutlet */
        $legacyOutlet = Outlet::where('outlet_code', self::LEGACY_OUTLET_CODE)->first();

        // 6. Klasifikasi User
        $alreadyMappedUsers = $allUsers->filter(fn (User $u) => $legacyOutlet && $u->outlet_id === $legacyOutlet->id);
        $assignedToOtherOutlet = $allUsers->filter(fn (User $u) => $u->outlet_id !== null && (! $legacyOutlet || $u->outlet_id !== $legacyOutlet->id));
        $unassignedUsers = $allUsers->filter(fn (User $u) => $u->outlet_id === null);

        // 7. Validasi Inkonsistensi: Owner sudah terikat ke outlet lain saat masih ada user unassigned
        $ownerAssignedToOther = $assignedToOtherOutlet->first(fn (User $u) => $this->isOwnerRole($u));
        if ($ownerAssignedToOther && $unassignedUsers->isNotEmpty()) {
            throw new DomainException("Inkonsistensi terdeteksi: Akun Owner ({$ownerAssignedToOther->name}) sudah terikat ke outlet lain (ID {$ownerAssignedToOther->outlet_id}), sedangkan masih ada akun konveksi yang unassigned. Rekonsiliasi manual diperlukan.");
        }

        // 8. Deteksi dan Validasi Owner (Tepat Satu Owner Sah)
        $unassignedOwners = $unassignedUsers->filter(fn (User $u) => $this->isOwnerRole($u));
        $alreadyMappedOwners = $alreadyMappedUsers->filter(fn (User $u) => $this->isOwnerRole($u));
        $totalLegacyOwners = $unassignedOwners->count() + $alreadyMappedOwners->count();

        if ($totalLegacyOwners === 0) {
            throw new DomainException('Tolak: Tidak ditemukan akun dengan role OWNER untuk konveksi awal.');
        }

        if ($totalLegacyOwners > 1) {
            $ownerNames = $unassignedOwners->merge($alreadyMappedOwners)->pluck('name')->join(', ');
            throw new DomainException("Tolak: Ditemukan lebih dari satu akun dengan role OWNER ({$ownerNames}). Kondisi ambigu; pemilik sah konveksi tidak tunggal.");
        }

        // 8b. Outlet legacy sudah ada dan memiliki staf terpetakan, tapi tidak memiliki owner terpetakan
        if ($legacyOutlet && $alreadyMappedUsers->isNotEmpty() && $alreadyMappedOwners->isEmpty()) {
            throw new DomainException("Inkonsistensi terdeteksi: Outlet legacy ({$legacyOutlet->outlet_code}) sudah memiliki akun terpetakan tetapi belum memiliki akun Owner yang terpetakan.");
        }

        // 9. Idempotent: Jika seluruh akun unassigned sudah kosong dan outlet legacy sudah ada
        if ($legacyOutlet && $unassignedUsers->isEmpty()) {
            if ($this->command) {
                $this->command->info("✅ LegacyOutletSeeder: Seluruh akun legacy ({$alreadyMappedUsers->count()} user) sudah terhubung ke outlet {$legacyOutlet->outlet_name}. Tidak ada perubahan baru (Idempotent).");
            }
            return;
        }

        // 10. Eksekusi Penautan dalam Transaksi Database Atomic
        DB::transaction(function () use ($legacyOutlet, $unassignedUsers, $alreadyMappedOwners, $unassignedOwners) {
            $outlet = $legacyOutlet;

            // Buat outlet jika belum ada (tanpa asumsi ID = 1)
            if (! $outlet) {
                /** @var User|null $owner */
                $owner = $alreadyMappedOwners->first() ?? $unassignedOwners->first();

                $outlet = Outlet::create([
                    'outlet_name'                => self::LEGACY_OUTLET_NAME,
                    'outlet_code'                => self::LEGACY_OUTLET_CODE,
                    'slug'                       => self::LEGACY_OUTLET_SLUG,
                    'phone_wa'                   => $owner?->phone ?? null,
                    'address'                    => 'Kantor Pusat Konveksi NAYARA',
                    'description'                => 'Outlet konveksi awal NAYARA Group hasil migrasi ERP legacy.',
                    'status'                     => 'ACTIVE',
                    'is_public_showcase_enabled' => true,
                ]);
            }

            // Tautkan user unassigned ke outlet legacy
            foreach ($unassignedUsers as $user) {
                // Jangan pernah memindahkan user yang sudah terikat ke outlet lain
                if ($user->outlet_id !== null && $user->outlet_id !== $outlet->id) {
                    throw new DomainException("Pelanggaran keamanan: User ID {$user->id} ({$user->name}) sudah terhubung ke outlet lain.");
                }

                // Update HANYA outlet_id; role, email, password, status tetap utuh
                $user->update(['outlet_id' => $outlet->id]);
            }

            if ($this->command) {
                $this->command->info("✅ LegacyOutletSeeder: Berhasil menautkan {$unassignedUsers->count()} akun legacy ke outlet {$outlet->outlet_name} ({$outlet->outlet_code}) [ID: {$outlet->id}].");
            }
        });
    }

    /**
     * Helper pengecekan role OWNER menggunakan enum resmi.
     */
    protected function isOwnerRole(User $user): bool
    {
        if ($user->role instanceof UserRole) {
            return $user->role === UserRole::OWNER;
        }

        return $user->role === UserRole::OWNER->value;
    }
}
