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

class LegacyMasterDataOutletSeeder extends Seeder
{
    public const LEGACY_OUTLET_CODE = LegacyOutletSeeder::LEGACY_OUTLET_CODE;

    /**
     * Target master data tables that receive outlet_id.
     *
     * @var array<int, string>
     */
    protected array $targetTables = [
        'products',
        'product_variants',
        'materials',
        'suppliers',
        'customers',
        'bom_templates',
        'document_sequences',
    ];

    /**
     * Run the database seeds.
     *
     * IMPORTANT:
     * Dijalankan secara eksplisit untuk menautkan seluruh master data legacy
     * ke outlet awal NAYARA Group ('OUT-NAYARA-PUSAT').
     * Tidak didaftarkan ke DatabaseSeeder utama dan tidak dipanggil migration.
     */
    public function run(): void
    {
        // 1. Verifikasi Prasyarat Skema Database
        if (! Schema::hasTable('outlets')) {
            throw new RuntimeException("Prasyarat gagal: Tabel 'outlets' belum tersedia di database.");
        }

        foreach ($this->targetTables as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Prasyarat gagal: Tabel target '{$table}' belum tersedia di database.");
            }
            if (! Schema::hasColumn($table, 'outlet_id')) {
                throw new RuntimeException("Prasyarat gagal: Kolom '{$table}.outlet_id' belum tersedia di database.");
            }
        }

        // 2. Cek apakah database master kosong
        $totalMasterRecords = 0;
        foreach ($this->targetTables as $table) {
            $totalMasterRecords += DB::table($table)->count();
        }

        if ($totalMasterRecords === 0) {
            if ($this->command) {
                $this->command->warn('ℹ️  Database tidak memiliki data master legacy. LegacyMasterDataOutletSeeder dilewati.');
            }
            return;
        }

        // 3. Resolusi Outlet Legacy Berdasarkan Kode Unik (Bukan Asumsi ID = 1)
        /** @var Outlet|null $legacyOutlet */
        $legacyOutlet = Outlet::where('outlet_code', self::LEGACY_OUTLET_CODE)->first();

        if (! $legacyOutlet) {
            throw new DomainException("Tolak: Outlet legacy dengan kode '" . self::LEGACY_OUTLET_CODE . "' tidak ditemukan. Jalankan LegacyOutletSeeder terlebih dahulu.");
        }

        // 4. Validasi Keberadaan dan Konsistensi Owner Legacy
        $legacyOwners = User::where('outlet_id', $legacyOutlet->id)
            ->get()
            ->filter(fn (User $u) => $this->isOwnerRole($u));

        if ($legacyOwners->isEmpty()) {
            throw new DomainException("Tolak: Outlet legacy ({$legacyOutlet->outlet_code}) belum memiliki akun Owner yang terhubung.");
        }

        if ($legacyOwners->count() > 1) {
            $ownerNames = $legacyOwners->pluck('name')->join(', ');
            throw new DomainException("Tolak: Ditemukan lebih dari 1 Owner ({$ownerNames}) pada outlet legacy. Kondisi ambigu.");
        }

        // 5. Cek Orphan outlet_id pada master data (jika ada record dengan outlet_id tak dikenal)
        // 5. Eksekusi Validasi Integritas dan Backfill dalam Transaksi Database Atomic
        DB::transaction(function () use ($legacyOutlet) {
            $allOutletIds = Outlet::pluck('id')->all();

            // 5a. Cek Orphan outlet_id pada master data
            foreach ($this->targetTables as $table) {
                $orphanCount = DB::table($table)
                    ->whereNotNull('outlet_id')
                    ->whereNotIn('outlet_id', $allOutletIds)
                    ->count();

                if ($orphanCount > 0) {
                    throw new DomainException("Tolak: Ditemukan {$orphanCount} record di tabel '{$table}' dengan outlet_id yang tidak terdaftar di tabel outlets.");
                }
            }

            // 6. Inventarisasi Record Unassigned
            $unassignedMap = [];
            $totalUnassigned = 0;

            foreach ($this->targetTables as $table) {
                $unassignedMap[$table] = DB::table($table)->whereNull('outlet_id')->pluck('id')->all();
                $totalUnassigned += count($unassignedMap[$table]);
            }

            // 7. Perlindungan Multi-Outlet: Jika ada outlet lain dan ada record unassigned yang ambigu
            $totalOutlets = Outlet::count();
            if ($totalOutlets > 1 && $totalUnassigned > 0) {
                throw new DomainException("Tolak: Terdeteksi multi-outlet ({$totalOutlets} outlet terdaftar) dengan {$totalUnassigned} master data tanpa pemilik (unassigned). Pemetaan otomatis diblokir demi perlindungan data multi-tenant.");
            }

            // 8. Validasi Integritas Relasi Parent-Child & Cross-Outlet Sebelum Mutasi

            // 8a. Product & ProductVariant
            $variants = DB::table('product_variants')->select('id', 'product_id', 'outlet_id', 'sku')->get();
            $productsById = DB::table('products')->select('id', 'outlet_id', 'product_name')->get()->keyBy('id');

            foreach ($variants as $variant) {
                if (! isset($productsById[$variant->product_id])) {
                    throw new DomainException("Integritas gagal: Variant ID {$variant->id} (SKU: {$variant->sku}) merujuk ke product_id {$variant->product_id} yang tidak ada.");
                }

                $parentProduct = $productsById[$variant->product_id];

                // Jika parent product milik outlet lain, variant tidak boleh terikat ke legacy
                if ($parentProduct->outlet_id !== null && $parentProduct->outlet_id !== $legacyOutlet->id) {
                    if ($variant->outlet_id === $legacyOutlet->id || in_array($variant->id, $unassignedMap['product_variants'], true)) {
                        throw new DomainException("Integritas gagal: Variant ID {$variant->id} (SKU: {$variant->sku}) terhubung ke produk milik outlet lain (Outlet ID {$parentProduct->outlet_id}).");
                    }
                }

                // Jika variant sudah milik outlet lain, parent tidak boleh milik legacy
                if ($variant->outlet_id !== null && $variant->outlet_id !== $legacyOutlet->id) {
                    if ($parentProduct->outlet_id === $legacyOutlet->id || in_array($parentProduct->id, $unassignedMap['products'], true)) {
                        throw new DomainException("Integritas gagal: Variant ID {$variant->id} milik outlet lain tetapi produk induknya adalah milik outlet legacy.");
                    }
                }
            }

            // 8b. Customer & Users
            $customers = DB::table('customers')->select('id', 'customer_name', 'user_id', 'handled_by_user_id', 'outlet_id')->get();
            foreach ($customers as $customer) {
                if ($customer->user_id !== null) {
                    $user = User::find($customer->user_id);
                    if (! $user) {
                        throw new DomainException("Integritas gagal: Customer ID {$customer->id} merujuk ke user_id {$customer->user_id} yang tidak ada.");
                    }
                    if ($user->outlet_id !== null && $user->outlet_id !== $legacyOutlet->id) {
                        throw new DomainException("Integritas gagal: Customer ID {$customer->id} ({$customer->customer_name}) terhubung ke user login yang terikat ke outlet lain (Outlet ID {$user->outlet_id}).");
                    }
                }

                if ($customer->handled_by_user_id !== null) {
                    $handler = User::find($customer->handled_by_user_id);
                    if (! $handler) {
                        throw new DomainException("Integritas gagal: Customer ID {$customer->id} ditangani oleh user_id {$customer->handled_by_user_id} yang tidak ada.");
                    }
                    if ($handler->outlet_id !== null && $handler->outlet_id !== $legacyOutlet->id) {
                        throw new DomainException("Integritas gagal: Customer ID {$customer->id} ({$customer->customer_name}) ditangani oleh staf outlet lain (Outlet ID {$handler->outlet_id}).");
                    }
                }
            }

            // 8c. BomTemplates & Materials
            $materialsById = DB::table('materials')->select('id', 'outlet_id', 'material_name')->get()->keyBy('id');
            $bomTemplates = DB::table('bom_templates')->select('id', 'name', 'product_id', 'outlet_id')->get();

            foreach ($bomTemplates as $bt) {
                if (! isset($productsById[$bt->product_id])) {
                    throw new DomainException("Integritas gagal: BOM Template ID {$bt->id} ({$bt->name}) merujuk ke product_id {$bt->product_id} yang tidak ada.");
                }
                $parentProduct = $productsById[$bt->product_id];
                if ($parentProduct->outlet_id !== null && $parentProduct->outlet_id !== $legacyOutlet->id) {
                    if ($bt->outlet_id === $legacyOutlet->id || in_array($bt->id, $unassignedMap['bom_templates'], true)) {
                        throw new DomainException("Integritas gagal: BOM Template ID {$bt->id} terhubung ke produk milik outlet lain (Outlet ID {$parentProduct->outlet_id}).");
                    }
                }
            }

            if (Schema::hasTable('bom_template_items')) {
                $bomItems = DB::table('bom_template_items')->select('id', 'bom_template_id', 'material_id')->get();
                $bomTemplatesById = $bomTemplates->keyBy('id');

                foreach ($bomItems as $item) {
                    if (! isset($materialsById[$item->material_id])) {
                        throw new DomainException("Integritas gagal: BOM Template Item ID {$item->id} merujuk ke material_id {$item->material_id} yang tidak ada.");
                    }
                    if (! isset($bomTemplatesById[$item->bom_template_id])) {
                        throw new DomainException("Integritas gagal: BOM Template Item ID {$item->id} merujuk ke bom_template_id {$item->bom_template_id} yang tidak ada.");
                    }

                    $mat = $materialsById[$item->material_id];
                    $tmpl = $bomTemplatesById[$item->bom_template_id];

                    if ($mat->outlet_id !== null && $mat->outlet_id !== $legacyOutlet->id) {
                        if ($tmpl->outlet_id === $legacyOutlet->id || in_array($tmpl->id, $unassignedMap['bom_templates'], true)) {
                            throw new DomainException("Integritas gagal: BOM Template ID {$tmpl->id} menggunakan material ID {$mat->id} milik outlet lain.");
                        }
                    }
                }
            }

            // 8d. Supplier & Materials (supplier_materials pivot)
            if (Schema::hasTable('supplier_materials')) {
                $suppliersById = DB::table('suppliers')->select('id', 'outlet_id', 'supplier_name')->get()->keyBy('id');
                $pivotRows = DB::table('supplier_materials')->select('supplier_id', 'material_id')->get();

                foreach ($pivotRows as $pivot) {
                    if (! isset($suppliersById[$pivot->supplier_id])) {
                        throw new DomainException("Integritas gagal: supplier_materials merujuk ke supplier_id {$pivot->supplier_id} yang tidak ada.");
                    }
                    if (! isset($materialsById[$pivot->material_id])) {
                        throw new DomainException("Integritas gagal: supplier_materials merujuk ke material_id {$pivot->material_id} yang tidak ada.");
                    }

                    $sup = $suppliersById[$pivot->supplier_id];
                    $mat = $materialsById[$pivot->material_id];

                    $supIsLegacy = ($sup->outlet_id === $legacyOutlet->id || in_array($sup->id, $unassignedMap['suppliers'], true));
                    $matIsLegacy = ($mat->outlet_id === $legacyOutlet->id || in_array($mat->id, $unassignedMap['materials'], true));

                    if ($supIsLegacy && $mat->outlet_id !== null && $mat->outlet_id !== $legacyOutlet->id) {
                        throw new DomainException("Integritas gagal: Supplier legacy ID {$sup->id} terhubung ke material milik outlet lain (Outlet ID {$mat->outlet_id}).");
                    }
                    if ($matIsLegacy && $sup->outlet_id !== null && $sup->outlet_id !== $legacyOutlet->id) {
                        throw new DomainException("Integritas gagal: Material legacy ID {$mat->id} terhubung ke supplier milik outlet lain (Outlet ID {$sup->outlet_id}).");
                    }
                }
            }

            // 9. Cek Idempotensi: Jika tidak ada data unassigned
            if ($totalUnassigned === 0) {
                if ($this->command) {
                    $this->command->info("✅ LegacyMasterDataOutletSeeder: Seluruh master data legacy sudah terhubung ke outlet {$legacyOutlet->outlet_name} ({$legacyOutlet->outlet_code}). Tidak ada perubahan (Idempotent).");
                }
                return;
            }

            // 10. Eksekusi Atomic Backfill
            foreach ($this->targetTables as $table) {
                $ids = $unassignedMap[$table];
                if (! empty($ids)) {
                    DB::table($table)
                        ->whereIn('id', $ids)
                        ->update(['outlet_id' => $legacyOutlet->id]);
                }
            }

            if ($this->command) {
                $this->command->info("✅ LegacyMasterDataOutletSeeder: Berhasil menautkan seluruh master data legacy ke outlet {$legacyOutlet->outlet_name} ({$legacyOutlet->outlet_code}) [ID: {$legacyOutlet->id}].");
                foreach ($this->targetTables as $table) {
                    $count = count($unassignedMap[$table]);
                    if ($count > 0) {
                        $this->command->line("   - {$table}: {$count} record ditautkan");
                    }
                }
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
