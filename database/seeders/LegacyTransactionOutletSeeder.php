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

class LegacyTransactionOutletSeeder extends Seeder
{
    public const LEGACY_OUTLET_CODE = LegacyOutletSeeder::LEGACY_OUTLET_CODE;

    /**
     * Target transaction root tables that receive outlet_id.
     *
     * @var array<int, string>
     */
    protected array $targetTables = [
        'orders',
        'purchases',
    ];

    /**
     * Master data tables whose outlet assignment is a prerequisite.
     *
     * @var array<int, string>
     */
    protected array $prerequisiteMasterTables = [
        'customers',
        'suppliers',
        'products',
        'product_variants',
        'materials',
    ];

    /**
     * Run the database seeds.
     *
     * IMPORTANT:
     * Dijalankan secara eksplisit untuk menautkan seluruh transaksi ERP legacy
     * (orders & purchases) ke outlet awal NAYARA Group ('OUT-NAYARA-PUSAT').
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
                throw new RuntimeException("Prasyarat gagal: Tabel transaksi target '{$table}' belum tersedia di database.");
            }
            if (! Schema::hasColumn($table, 'outlet_id')) {
                throw new RuntimeException("Prasyarat gagal: Kolom '{$table}.outlet_id' belum tersedia di database. Jalankan migration terlebih dahulu.");
            }
        }

        // 2. Cek apakah database transaksi kosong
        $totalOrders = DB::table('orders')->count();
        $totalPurchases = DB::table('purchases')->count();
        $totalTransactions = $totalOrders + $totalPurchases;

        if ($totalTransactions === 0) {
            if ($this->command) {
                $this->command->warn('ℹ️  Database tidak memiliki data transaksi legacy (orders & purchases). LegacyTransactionOutletSeeder dilewati.');
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

        // 5. Validasi Master Data Prerequisite: Master data harus sudah memiliki outlet_id
        foreach ($this->prerequisiteMasterTables as $mTable) {
            if (Schema::hasTable($mTable) && Schema::hasColumn($mTable, 'outlet_id')) {
                $unassignedMaster = DB::table($mTable)->whereNull('outlet_id')->count();
                if ($unassignedMaster > 0) {
                    throw new DomainException("Tolak: Ditemukan {$unassignedMaster} master data pada tabel '{$mTable}' yang belum memiliki outlet_id. Jalankan LegacyMasterDataOutletSeeder terlebih dahulu.");
                }
            }
        }

        // 6. Eksekusi Validasi Integritas dan Backfill dalam Transaksi Database Atomic
        DB::transaction(function () use ($legacyOutlet) {
            $allOutletIds = Outlet::pluck('id')->all();

            // 6a. Cek Orphan outlet_id pada tabel transaksi
            foreach ($this->targetTables as $table) {
                $orphanCount = DB::table($table)
                    ->whereNotNull('outlet_id')
                    ->whereNotIn('outlet_id', $allOutletIds)
                    ->count();

                if ($orphanCount > 0) {
                    throw new DomainException("Tolak: Ditemukan {$orphanCount} record di tabel '{$table}' dengan outlet_id yang tidak terdaftar di tabel outlets.");
                }
            }

            // 6b. Inventarisasi Record Unassigned
            $unassignedOrderIds = DB::table('orders')->whereNull('outlet_id')->pluck('id')->all();
            $unassignedPurchaseIds = DB::table('purchases')->whereNull('outlet_id')->pluck('id')->all();
            $totalUnassigned = count($unassignedOrderIds) + count($unassignedPurchaseIds);

            // 6c. Perlindungan Multi-Outlet: Jika ada lebih dari satu outlet dan ada transaksi unassigned
            $totalOutlets = Outlet::count();
            if ($totalOutlets > 1 && $totalUnassigned > 0) {
                throw new DomainException("Tolak: Terdeteksi multi-outlet ({$totalOutlets} outlet terdaftar) dengan {$totalUnassigned} transaksi tanpa pemilik (unassigned). Pemetaan otomatis diblokir demi perlindungan data multi-tenant.");
            }

            // 7. Validasi Integritas Relasi Transaksi Order Sebelum Mutasi
            $orders = DB::table('orders')->select('id', 'customer_id', 'order_no', 'outlet_id')->get();
            $customersById = DB::table('customers')->select('id', 'outlet_id', 'customer_name')->get()->keyBy('id');

            foreach ($orders as $order) {
                if (! isset($customersById[$order->customer_id])) {
                    throw new DomainException("Integritas gagal: Order ID {$order->id} ({$order->order_no}) merujuk ke customer_id {$order->customer_id} yang tidak ada.");
                }

                $cust = $customersById[$order->customer_id];

                // Jika customer milik outlet lain, order tidak boleh terikat ke legacy
                if ($cust->outlet_id !== null && $cust->outlet_id !== $legacyOutlet->id) {
                    if ($order->outlet_id === $legacyOutlet->id || in_array($order->id, $unassignedOrderIds, true)) {
                        throw new DomainException("Integritas gagal: Order ID {$order->id} ({$order->order_no}) terhubung ke Customer milik outlet lain (Outlet ID {$cust->outlet_id}).");
                    }
                }

                // Jika order sudah milik outlet lain, customer tidak boleh milik legacy
                if ($order->outlet_id !== null && $order->outlet_id !== $legacyOutlet->id) {
                    if ($cust->outlet_id === $legacyOutlet->id) {
                        throw new DomainException("Integritas gagal: Order ID {$order->id} ({$order->order_no}) milik outlet lain tetapi Customernya adalah milik outlet legacy.");
                    }
                }
            }

            // 7b. Validasi OrderItems -> ProductVariant
            if (Schema::hasTable('order_items')) {
                $orderItems = DB::table('order_items')->select('id', 'order_id', 'variant_id')->get();
                $variantsById = DB::table('product_variants')->select('id', 'outlet_id', 'sku')->get()->keyBy('id');
                $ordersById = $orders->keyBy('id');

                foreach ($orderItems as $item) {
                    if (! isset($ordersById[$item->order_id])) {
                        throw new DomainException("Integritas gagal: OrderItem ID {$item->id} merujuk ke order_id {$item->order_id} yang tidak ada.");
                    }
                    if (! isset($variantsById[$item->variant_id])) {
                        throw new DomainException("Integritas gagal: OrderItem ID {$item->id} merujuk ke variant_id {$item->variant_id} yang tidak ada.");
                    }

                    $parentOrder = $ordersById[$item->order_id];
                    $variant = $variantsById[$item->variant_id];

                    $orderIsLegacy = ($parentOrder->outlet_id === $legacyOutlet->id || in_array($parentOrder->id, $unassignedOrderIds, true));
                    $orderIsOther = ($parentOrder->outlet_id !== null && $parentOrder->outlet_id !== $legacyOutlet->id);

                    if ($orderIsLegacy && $variant->outlet_id !== null && $variant->outlet_id !== $legacyOutlet->id) {
                        throw new DomainException("Integritas gagal: OrderItem ID {$item->id} pada Order ID {$parentOrder->id} menggunakan ProductVariant milik outlet lain (Outlet ID {$variant->outlet_id}).");
                    }

                    if ($orderIsOther && $variant->outlet_id === $legacyOutlet->id) {
                        throw new DomainException("Integritas gagal: Order ID {$parentOrder->id} milik outlet lain menggunakan ProductVariant milik outlet legacy.");
                    }
                }
            }

            // 8. Validasi Integritas Relasi Transaksi Purchase Sebelum Mutasi
            $purchases = DB::table('purchases')->select('id', 'supplier_id', 'purchase_no', 'outlet_id', 'submitted_by', 'approved_by')->get();
            $suppliersById = DB::table('suppliers')->select('id', 'outlet_id', 'supplier_name')->get()->keyBy('id');
            $usersById = DB::table('users')->select('id', 'outlet_id', 'name')->get()->keyBy('id');

            foreach ($purchases as $purchase) {
                if (! isset($suppliersById[$purchase->supplier_id])) {
                    throw new DomainException("Integritas gagal: Purchase ID {$purchase->id} ({$purchase->purchase_no}) merujuk ke supplier_id {$purchase->supplier_id} yang tidak ada.");
                }

                $sup = $suppliersById[$purchase->supplier_id];

                // Jika supplier milik outlet lain, purchase tidak boleh terikat ke legacy
                if ($sup->outlet_id !== null && $sup->outlet_id !== $legacyOutlet->id) {
                    if ($purchase->outlet_id === $legacyOutlet->id || in_array($purchase->id, $unassignedPurchaseIds, true)) {
                        throw new DomainException("Integritas gagal: Purchase ID {$purchase->id} ({$purchase->purchase_no}) terhubung ke Supplier milik outlet lain (Outlet ID {$sup->outlet_id}).");
                    }
                }

                // Jika purchase sudah milik outlet lain, supplier tidak boleh milik legacy
                if ($purchase->outlet_id !== null && $purchase->outlet_id !== $legacyOutlet->id) {
                    if ($sup->outlet_id === $legacyOutlet->id) {
                        throw new DomainException("Integritas gagal: Purchase ID {$purchase->id} ({$purchase->purchase_no}) milik outlet lain tetapi Suppliernya adalah milik outlet legacy.");
                    }
                }

                // Verifikasi user references (submitted_by, approved_by) jika ada
                if ($purchase->submitted_by !== null && isset($usersById[$purchase->submitted_by])) {
                    $submitter = $usersById[$purchase->submitted_by];
                    if ($submitter->outlet_id !== null && $submitter->outlet_id !== $legacyOutlet->id) {
                        if ($purchase->outlet_id === $legacyOutlet->id || in_array($purchase->id, $unassignedPurchaseIds, true)) {
                            throw new DomainException("Integritas gagal: Purchase ID {$purchase->id} diajukan oleh user ID {$submitter->id} milik outlet lain.");
                        }
                    }
                }

                if ($purchase->approved_by !== null && isset($usersById[$purchase->approved_by])) {
                    $approver = $usersById[$purchase->approved_by];
                    if ($approver->outlet_id !== null && $approver->outlet_id !== $legacyOutlet->id) {
                        if ($purchase->outlet_id === $legacyOutlet->id || in_array($purchase->id, $unassignedPurchaseIds, true)) {
                            throw new DomainException("Integritas gagal: Purchase ID {$purchase->id} disetujui oleh user ID {$approver->id} milik outlet lain.");
                        }
                    }
                }
            }

            // 8b. Validasi PurchaseItems -> Material
            if (Schema::hasTable('purchase_items')) {
                $purchaseItems = DB::table('purchase_items')->select('id', 'purchase_id', 'material_id')->get();
                $materialsById = DB::table('materials')->select('id', 'outlet_id', 'material_name')->get()->keyBy('id');
                $purchasesById = $purchases->keyBy('id');

                foreach ($purchaseItems as $pItem) {
                    if (! isset($purchasesById[$pItem->purchase_id])) {
                        throw new DomainException("Integritas gagal: PurchaseItem ID {$pItem->id} merujuk ke purchase_id {$pItem->purchase_id} yang tidak ada.");
                    }
                    if (! isset($materialsById[$pItem->material_id])) {
                        throw new DomainException("Integritas gagal: PurchaseItem ID {$pItem->id} merujuk ke material_id {$pItem->material_id} yang tidak ada.");
                    }

                    $parentPO = $purchasesById[$pItem->purchase_id];
                    $material = $materialsById[$pItem->material_id];

                    $poIsLegacy = ($parentPO->outlet_id === $legacyOutlet->id || in_array($parentPO->id, $unassignedPurchaseIds, true));
                    $poIsOther = ($parentPO->outlet_id !== null && $parentPO->outlet_id !== $legacyOutlet->id);

                    if ($poIsLegacy && $material->outlet_id !== null && $material->outlet_id !== $legacyOutlet->id) {
                        throw new DomainException("Integritas gagal: PurchaseItem ID {$pItem->id} pada Purchase ID {$parentPO->id} menggunakan Material milik outlet lain (Outlet ID {$material->outlet_id}).");
                    }

                    if ($poIsOther && $material->outlet_id === $legacyOutlet->id) {
                        throw new DomainException("Integritas gagal: Purchase ID {$parentPO->id} milik outlet lain menggunakan Material milik outlet legacy.");
                    }
                }
            }

            // 9. Cek Idempotensi: Jika tidak ada transaksi unassigned
            if ($totalUnassigned === 0) {
                if ($this->command) {
                    $this->command->info("✅ LegacyTransactionOutletSeeder: Seluruh transaksi legacy sudah terhubung ke outlet {$legacyOutlet->outlet_name} ({$legacyOutlet->outlet_code}). Tidak ada perubahan (Idempotent).");
                }
                return;
            }

            // 10. Eksekusi Atomic Backfill Tanpa Mengubah Timestamps Historis
            // Menggunakan query builder update() secara langsung tidak mengubah created_at maupun updated_at
            if (! empty($unassignedOrderIds)) {
                DB::table('orders')
                    ->whereIn('id', $unassignedOrderIds)
                    ->update(['outlet_id' => $legacyOutlet->id]);
            }

            if (! empty($unassignedPurchaseIds)) {
                DB::table('purchases')
                    ->whereIn('id', $unassignedPurchaseIds)
                    ->update(['outlet_id' => $legacyOutlet->id]);
            }

            if ($this->command) {
                $this->command->info("✅ LegacyTransactionOutletSeeder: Berhasil menautkan seluruh transaksi legacy ke outlet {$legacyOutlet->outlet_name} ({$legacyOutlet->outlet_code}) [ID: {$legacyOutlet->id}].");
                $this->command->line("   - orders: " . count($unassignedOrderIds) . " record ditautkan");
                $this->command->line("   - purchases: " . count($unassignedPurchaseIds) . " record ditautkan");
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
