<?php

namespace Tests\Feature\System;

use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\PurchaseApprovalStatus;
use App\Enums\PurchaseStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Inventory\Purchase;
use App\Models\Inventory\PurchaseApprovalHistory;
use App\Models\Inventory\PurchaseItem;
use App\Models\Inventory\PurchasePayment;
use App\Models\Master\Customer;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Master\Supplier;
use App\Models\Master\Unit;
use App\Models\Production\ProductionBatch;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\Sales\Payment;
use App\Models\System\Outlet;
use App\Models\System\User;
use Database\Seeders\LegacyOutletSeeder;
use Database\Seeders\LegacyTransactionOutletSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegacyTransactionOutletBackfillTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper membuat outlet legacy standar beserta akun Owner.
     */
    protected function createStandardLegacyOutletAndOwner(int $outletId = 1): array
    {
        $outlet = new Outlet([
            'outlet_name'                => LegacyOutletSeeder::LEGACY_OUTLET_NAME,
            'outlet_code'                => LegacyOutletSeeder::LEGACY_OUTLET_CODE,
            'slug'                       => LegacyOutletSeeder::LEGACY_OUTLET_SLUG,
            'phone_wa'                   => '081234567890',
            'address'                    => 'Kantor Pusat Konveksi NAYARA',
            'status'                     => 'ACTIVE',
            'is_public_showcase_enabled' => true,
        ]);
        $outlet->id = $outletId;
        $outlet->save();

        $owner = User::factory()->create([
            'name'      => 'Owner Konveksi Legacy',
            'email'     => 'owner@nayara.test',
            'role'      => UserRole::OWNER,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $outlet->id,
        ]);

        return [$outlet, $owner];
    }

    /**
     * Helper membuat master data terikat ke outlet tertentu.
     */
    protected function createMasterDataForOutlet(?Outlet $outlet = null, string $suffix = '01'): array
    {
        $unit = Unit::firstOrCreate(['symbol' => 'm'], ['unit_name' => 'Meter']);

        $customer = Customer::create([
            'customer_name' => 'Customer Legacy ' . $suffix,
            'phone'         => '0812' . str_pad($suffix, 8, '0', STR_PAD_RIGHT),
            'outlet_id'     => $outlet?->id,
        ]);

        $supplier = Supplier::create([
            'supplier_name' => 'Supplier Legacy ' . $suffix,
            'phone'         => '0898' . str_pad($suffix, 8, '0', STR_PAD_RIGHT),
            'outlet_id'     => $outlet?->id,
        ]);

        $product = Product::create([
            'product_name' => 'Hijab Segiempat Voal ' . $suffix,
            'code'         => 'PROD-VOAL-' . $suffix,
            'outlet_id'    => $outlet?->id,
        ]);

        $variant = ProductVariant::create([
            'product_id'                 => $product->id,
            'variant_name'               => 'Navy ' . $suffix,
            'sku'                        => 'VOAL-NAVY-' . $suffix,
            'base_selling_price'         => 45000,
            'requires_printing'          => false,
            'printing_cost_per_unit'     => 0,
            'direct_labor_cost_per_unit' => 5000,
            'outlet_id'                  => $outlet?->id,
        ]);

        $material = Material::create([
            'unit_id'       => $unit->id,
            'material_name' => 'Kain Ceruty ' . $suffix,
            'min_stock'     => 10,
            'outlet_id'     => $outlet?->id,
        ]);

        return compact('unit', 'customer', 'supplier', 'product', 'variant', 'material');
    }

    /**
     * Helper membuat data transaksi unassigned (outlet_id = null).
     */
    protected function createUnassignedLegacyTransactions(array $master, string $suffix = '01'): array
    {
        $order = Order::create([
            'customer_id' => $master['customer']->id,
            'order_no'    => 'SO-LEGACY-' . $suffix,
            'order_date'  => '2026-09-29',
            'status'      => OrderStatus::SELESAI,
            'outlet_id'   => null,
        ]);

        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $master['variant']->id,
            'qty_ordered'        => 50,
            'unit_selling_price' => 45000,
        ]);

        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-LEGACY-' . $suffix,
            'invoice_date' => '2026-09-29',
            'total_amount' => 2250000,
            'status'       => 'PAID',
        ]);

        $payment = Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_type'   => PaymentType::PELUNASAN,
            'amount'         => 2250000,
            'payment_date'   => '2026-09-29',
            'payment_method' => PaymentMethod::TRANSFER_BANK,
            'status'         => PaymentStatus::CONFIRMED,
        ]);

        $batch = ProductionBatch::create([
            'order_item_id' => $orderItem->id,
            'batch_no'      => 'BAT-LEGACY-' . $suffix,
            'target_qty'    => 50,
            'status'        => BatchStatus::SELESAI,
        ]);

        $purchase = Purchase::create([
            'supplier_id'     => $master['supplier']->id,
            'purchase_no'     => 'PO-LEGACY-' . $suffix,
            'purchase_date'   => '2026-09-28',
            'status'          => PurchaseStatus::RECEIVED,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
            'approved_at'     => '2026-09-28 10:00:00',
            'outlet_id'       => null,
        ]);

        $purchaseItem = PurchaseItem::create([
            'purchase_id'  => $purchase->id,
            'material_id'  => $master['material']->id,
            'qty'          => 100,
            'unit_price'   => 25000,
            'qty_received' => 100,
        ]);

        $purchasePayment = PurchasePayment::create([
            'purchase_id'    => $purchase->id,
            'amount'         => 2500000,
            'payment_date'   => '2026-09-28',
            'payment_method' => 'Transfer',
        ]);

        return compact('order', 'orderItem', 'invoice', 'payment', 'batch', 'purchase', 'purchaseItem', 'purchasePayment');
    }

    /**
     * 1. Orders legacy terpetakan benar ke OUT-NAYARA-PUSAT.
     */
    public function test_01_legacy_orders_are_correctly_mapped_to_legacy_outlet(): void
    {
        [$outlet, $owner] = $this->createStandardLegacyOutletAndOwner();
        $master = $this->createMasterDataForOutlet($outlet);
        $tx = $this->createUnassignedLegacyTransactions($master);

        $this->assertNull($tx['order']->fresh()->outlet_id);

        $seeder = new LegacyTransactionOutletSeeder();
        $seeder->run();

        $freshOrder = $tx['order']->fresh();
        $this->assertEquals($outlet->id, $freshOrder->outlet_id);
        $this->assertEquals(LegacyOutletSeeder::LEGACY_OUTLET_CODE, $freshOrder->outlet->outlet_code);
    }

    /**
     * 2. Purchases legacy terpetakan benar ke OUT-NAYARA-PUSAT.
     */
    public function test_02_legacy_purchases_are_correctly_mapped_to_legacy_outlet(): void
    {
        [$outlet, $owner] = $this->createStandardLegacyOutletAndOwner();
        $master = $this->createMasterDataForOutlet($outlet);
        $tx = $this->createUnassignedLegacyTransactions($master);

        $this->assertNull($tx['purchase']->fresh()->outlet_id);

        $seeder = new LegacyTransactionOutletSeeder();
        $seeder->run();

        $freshPurchase = $tx['purchase']->fresh();
        $this->assertEquals($outlet->id, $freshPurchase->outlet_id);
        $this->assertEquals(LegacyOutletSeeder::LEGACY_OUTLET_CODE, $freshPurchase->outlet->outlet_code);
    }

    /**
     * 3. Seeder tidak mengasumsikan outlet ID = 1 secara hardcoded.
     */
    public function test_03_seeder_does_not_assume_outlet_id_is_one(): void
    {
        // Buat outlet legacy dengan ID non-standar (misal ID 99)
        [$outlet, $owner] = $this->createStandardLegacyOutletAndOwner(99);
        $master = $this->createMasterDataForOutlet($outlet);
        $tx = $this->createUnassignedLegacyTransactions($master);

        $seeder = new LegacyTransactionOutletSeeder();
        $seeder->run();

        $this->assertEquals(99, $tx['order']->fresh()->outlet_id);
        $this->assertEquals(99, $tx['purchase']->fresh()->outlet_id);
    }

    /**
     * 4. Seeder bersifat idempotent pada eksekusi berulang.
     */
    public function test_04_seeder_is_idempotent_on_subsequent_runs(): void
    {
        [$outlet, $owner] = $this->createStandardLegacyOutletAndOwner();
        $master = $this->createMasterDataForOutlet($outlet);
        $tx = $this->createUnassignedLegacyTransactions($master);

        $seeder = new LegacyTransactionOutletSeeder();

        // Eksekusi pertama
        $seeder->run();
        $this->assertEquals($outlet->id, $tx['order']->fresh()->outlet_id);
        $this->assertEquals($outlet->id, $tx['purchase']->fresh()->outlet_id);

        // Eksekusi kedua
        $seeder->run();
        $this->assertEquals($outlet->id, $tx['order']->fresh()->outlet_id);
        $this->assertEquals($outlet->id, $tx['purchase']->fresh()->outlet_id);
        $this->assertEquals(1, Order::count());
        $this->assertEquals(1, Purchase::count());
    }

    /**
     * 5. Record transaksi milik outlet lain tidak diubah.
     */
    public function test_05_transactions_belonging_to_other_outlets_are_not_modified(): void
    {
        [$outletLegacy, $owner] = $this->createStandardLegacyOutletAndOwner();

        $outletBranch = Outlet::create([
            'outlet_name' => 'Konveksi Cabang Bandung',
            'outlet_code' => 'OUT-BDG-01',
            'slug'        => 'konveksi-cabang-bandung',
            'status'      => 'ACTIVE',
        ]);

        $masterBranch = $this->createMasterDataForOutlet($outletBranch, 'BDG');

        $orderBranch = Order::create([
            'customer_id' => $masterBranch['customer']->id,
            'order_no'    => 'SO-BRANCH-01',
            'order_date'  => '2026-10-23',
            'status'      => OrderStatus::KOMUNIKASI,
            'outlet_id'   => $outletBranch->id,
        ]);

        $purchaseBranch = Purchase::create([
            'supplier_id'   => $masterBranch['supplier']->id,
            'purchase_no'   => 'PO-BRANCH-01',
            'purchase_date' => '2026-10-23',
            'status'        => PurchaseStatus::DRAFT,
            'outlet_id'     => $outletBranch->id,
        ]);

        $seeder = new LegacyTransactionOutletSeeder();
        $seeder->run();

        $this->assertEquals($outletBranch->id, $orderBranch->fresh()->outlet_id);
        $this->assertEquals($outletBranch->id, $purchaseBranch->fresh()->outlet_id);
    }

    /**
     * 6. Transaksi unassigned dalam kondisi multi-outlet diblokir demi keamanan tenant.
     */
    public function test_06_unassigned_ambiguous_transactions_are_blocked_in_multi_outlet(): void
    {
        [$outletLegacy, $owner] = $this->createStandardLegacyOutletAndOwner();

        // Ada outlet kedua
        Outlet::create([
            'outlet_name' => 'Konveksi Cabang Surabaya',
            'outlet_code' => 'OUT-SBY-01',
            'slug'        => 'konveksi-cabang-surabaya',
            'status'      => 'ACTIVE',
        ]);

        $master = $this->createMasterDataForOutlet($outletLegacy);
        $this->createUnassignedLegacyTransactions($master);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Terdeteksi multi-outlet');

        $seeder = new LegacyTransactionOutletSeeder();
        $seeder->run();
    }

    /**
     * 7. Order dan Customer dari outlet berbeda terdeteksi dan diblokir.
     */
    public function test_07_order_and_customer_from_different_outlets_are_blocked(): void
    {
        [$outletLegacy, $owner] = $this->createStandardLegacyOutletAndOwner();

        $outletOther = Outlet::create([
            'outlet_name' => 'Outlet Lain',
            'outlet_code' => 'OUT-OTHER',
            'slug'        => 'outlet-lain',
            'status'      => 'ACTIVE',
        ]);

        // Customer milik outlet lain
        $customerOther = Customer::create([
            'customer_name' => 'Customer Outlet Lain',
            'phone'         => '081299998888',
            'outlet_id'     => $outletOther->id,
        ]);

        // Order mencoba terhubung ke customer outlet lain
        Order::create([
            'customer_id' => $customerOther->id,
            'order_no'    => 'SO-CROSS-CHECK',
            'order_date'  => '2026-10-23',
            'status'      => OrderStatus::KOMUNIKASI,
            'outlet_id'   => null,
        ]);

        $this->expectException(DomainException::class);

        $seeder = new LegacyTransactionOutletSeeder();
        $seeder->run();
    }

    /**
     * 8. OrderItem dan ProductVariant dari outlet berbeda terdeteksi dan diblokir.
     */
    public function test_08_order_item_and_variant_from_different_outlets_are_blocked(): void
    {
        [$outletLegacy, $owner] = $this->createStandardLegacyOutletAndOwner();
        $masterLegacy = $this->createMasterDataForOutlet($outletLegacy);

        $outletOther = Outlet::create([
            'outlet_name' => 'Outlet Lain',
            'outlet_code' => 'OUT-OTHER',
            'slug'        => 'outlet-lain',
            'status'      => 'ACTIVE',
        ]);

        $masterOther = $this->createMasterDataForOutlet($outletOther, 'OTH');

        $order = Order::create([
            'customer_id' => $masterLegacy['customer']->id,
            'order_no'    => 'SO-CROSS-VARIANT',
            'order_date'  => '2026-10-23',
            'status'      => OrderStatus::KOMUNIKASI,
            'outlet_id'   => null,
        ]);

        // Item menggunakan varian dari outlet lain
        OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $masterOther['variant']->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 50000,
        ]);

        $this->expectException(DomainException::class);

        $seeder = new LegacyTransactionOutletSeeder();
        $seeder->run();
    }

    /**
     * 9. Purchase dan Supplier dari outlet berbeda terdeteksi dan diblokir.
     */
    public function test_09_purchase_and_supplier_from_different_outlets_are_blocked(): void
    {
        [$outletLegacy, $owner] = $this->createStandardLegacyOutletAndOwner();

        $outletOther = Outlet::create([
            'outlet_name' => 'Outlet Lain',
            'outlet_code' => 'OUT-OTHER',
            'slug'        => 'outlet-lain',
            'status'      => 'ACTIVE',
        ]);

        // Supplier milik outlet lain
        $supplierOther = Supplier::create([
            'supplier_name' => 'Supplier Outlet Lain',
            'phone'         => '089988887777',
            'outlet_id'     => $outletOther->id,
        ]);

        Purchase::create([
            'supplier_id'   => $supplierOther->id,
            'purchase_no'   => 'PO-CROSS-SUPPLIER',
            'purchase_date' => '2026-10-23',
            'status'        => PurchaseStatus::DRAFT,
            'outlet_id'     => null,
        ]);

        $this->expectException(DomainException::class);

        $seeder = new LegacyTransactionOutletSeeder();
        $seeder->run();
    }

    /**
     * 10. PurchaseItem dan Material dari outlet berbeda terdeteksi dan diblokir.
     */
    public function test_10_purchase_item_and_material_from_different_outlets_are_blocked(): void
    {
        [$outletLegacy, $owner] = $this->createStandardLegacyOutletAndOwner();
        $masterLegacy = $this->createMasterDataForOutlet($outletLegacy);

        $outletOther = Outlet::create([
            'outlet_name' => 'Outlet Lain',
            'outlet_code' => 'OUT-OTHER',
            'slug'        => 'outlet-lain',
            'status'      => 'ACTIVE',
        ]);

        $masterOther = $this->createMasterDataForOutlet($outletOther, 'OTH');

        $purchase = Purchase::create([
            'supplier_id'   => $masterLegacy['supplier']->id,
            'purchase_no'   => 'PO-CROSS-MATERIAL',
            'purchase_date' => '2026-10-23',
            'status'        => PurchaseStatus::DRAFT,
            'outlet_id'     => null,
        ]);

        // Item menggunakan material milik outlet lain
        PurchaseItem::create([
            'purchase_id'  => $purchase->id,
            'material_id'  => $masterOther['material']->id,
            'qty'          => 20,
            'unit_price'   => 30000,
            'qty_received' => 0,
        ]);

        $this->expectException(DomainException::class);

        $seeder = new LegacyTransactionOutletSeeder();
        $seeder->run();
    }

    /**
     * 11. Transaksi tidak kehilangan record apa pun selama proses backfill.
     */
    public function test_11_transaction_record_counts_remain_exact(): void
    {
        [$outlet, $owner] = $this->createStandardLegacyOutletAndOwner();
        $master = $this->createMasterDataForOutlet($outlet);
        $this->createUnassignedLegacyTransactions($master, '01');
        $this->createUnassignedLegacyTransactions($master, '02');

        $this->assertEquals(2, Order::count());
        $this->assertEquals(2, Purchase::count());

        $seeder = new LegacyTransactionOutletSeeder();
        $seeder->run();

        $this->assertEquals(2, Order::count());
        $this->assertEquals(2, Purchase::count());
    }

    /**
     * 12. Status dan nilai nominal transaksi tidak berubah sama sekali.
     */
    public function test_12_status_and_nominal_values_remain_unmodified(): void
    {
        [$outlet, $owner] = $this->createStandardLegacyOutletAndOwner();
        $master = $this->createMasterDataForOutlet($outlet);
        $tx = $this->createUnassignedLegacyTransactions($master);

        $initialOrderStatus = $tx['order']->status;
        $initialOrderTotal = $tx['order']->total_amount;
        $initialPurchaseStatus = $tx['purchase']->status;
        $initialPurchaseTotal = $tx['purchase']->total_amount;

        $seeder = new LegacyTransactionOutletSeeder();
        $seeder->run();

        $freshOrder = $tx['order']->fresh();
        $freshPurchase = $tx['purchase']->fresh();

        $this->assertEquals($initialOrderStatus, $freshOrder->status);
        $this->assertEquals($initialOrderTotal, $freshOrder->total_amount);
        $this->assertEquals($initialPurchaseStatus, $freshPurchase->status);
        $this->assertEquals($initialPurchaseTotal, $freshPurchase->total_amount);
    }

    /**
     * 13. Timestamp historis (created_at dan updated_at) tidak berubah.
     */
    public function test_13_historical_timestamps_remain_unmodified(): void
    {
        [$outlet, $owner] = $this->createStandardLegacyOutletAndOwner();
        $master = $this->createMasterDataForOutlet($outlet);
        $tx = $this->createUnassignedLegacyTransactions($master);

        $historicalCreated = Carbon::create(2026, 9, 29, 10, 15, 30);
        $historicalUpdated = Carbon::create(2026, 9, 30, 14, 20, 00);

        DB::table('orders')->where('id', $tx['order']->id)->update([
            'created_at' => $historicalCreated,
            'updated_at' => $historicalUpdated,
        ]);

        DB::table('purchases')->where('id', $tx['purchase']->id)->update([
            'created_at' => $historicalCreated,
            'updated_at' => $historicalUpdated,
        ]);

        $seeder = new LegacyTransactionOutletSeeder();
        $seeder->run();

        $freshOrder = DB::table('orders')->where('id', $tx['order']->id)->first();
        $freshPurchase = DB::table('purchases')->where('id', $tx['purchase']->id)->first();

        $this->assertEquals('2026-09-29 10:15:30', $freshOrder->created_at);
        $this->assertEquals('2026-09-30 14:20:00', $freshOrder->updated_at);
        $this->assertEquals('2026-09-29 10:15:30', $freshPurchase->created_at);
        $this->assertEquals('2026-09-30 14:20:00', $freshPurchase->updated_at);
    }

    /**
     * 14. Histori audit approval PO dan kolom approved_by tetap utuh.
     */
    public function test_14_purchase_approval_history_and_approved_by_remain_intact(): void
    {
        [$outlet, $owner] = $this->createStandardLegacyOutletAndOwner();
        $master = $this->createMasterDataForOutlet($outlet);
        $tx = $this->createUnassignedLegacyTransactions($master);

        // Tambah approval history
        $history = PurchaseApprovalHistory::create([
            'purchase_id' => $tx['purchase']->id,
            'action'      => 'APPROVED',
            'from_status' => 'PENDING_APPROVAL',
            'to_status'   => 'APPROVED',
            'user_id'     => $owner->id,
            'note'        => 'Approval transisi oleh Owner',
        ]);

        DB::table('purchases')->where('id', $tx['purchase']->id)->update([
            'approved_by' => $owner->id,
            'approved_at' => '2026-09-28 10:00:00',
        ]);

        $seeder = new LegacyTransactionOutletSeeder();
        $seeder->run();

        $freshPO = $tx['purchase']->fresh();
        $this->assertEquals($owner->id, $freshPO->approved_by);
        $this->assertEquals('2026-09-28 10:00:00', $freshPO->approved_at->format('Y-m-d H:i:s'));
        $this->assertEquals(1, $freshPO->approvalHistories()->count());
        $this->assertEquals($history->id, $freshPO->approvalHistories()->first()->id);
    }

    /**
     * 15. Transaksi DB rollback secara atomik saat terjadi error validasi.
     */
    public function test_15_database_transaction_rolls_back_atomically_on_error(): void
    {
        [$outlet, $owner] = $this->createStandardLegacyOutletAndOwner();
        $master = $this->createMasterDataForOutlet($outlet);

        // 1 Order valid
        $validOrder = Order::create([
            'customer_id' => $master['customer']->id,
            'order_no'    => 'SO-VALID',
            'order_date'  => '2026-09-29',
            'status'      => OrderStatus::SELESAI,
            'outlet_id'   => null,
        ]);

        $outletOther = Outlet::create([
            'outlet_name' => 'Outlet Lain',
            'outlet_code' => 'OUT-OTHER',
            'slug'        => 'outlet-lain',
            'status'      => 'ACTIVE',
        ]);
        $masterOther = $this->createMasterDataForOutlet($outletOther, 'OTH');

        // Order invalid (menggunakan varian milik outlet lain pada order kedua)
        $invalidOrder = Order::create([
            'customer_id' => $master['customer']->id,
            'order_no'    => 'SO-INVALID-ITEM',
            'order_date'  => '2026-09-29',
            'status'      => OrderStatus::KOMUNIKASI,
            'outlet_id'   => null,
        ]);

        OrderItem::create([
            'order_id'           => $invalidOrder->id,
            'variant_id'         => $masterOther['variant']->id,
            'qty_ordered'        => 5,
            'unit_selling_price' => 50000,
        ]);

        try {
            $seeder = new LegacyTransactionOutletSeeder();
            $seeder->run();
            $this->fail('Seeder should have failed');
        } catch (DomainException $e) {
            // Berhasil tertangkap
            $this->assertNotEmpty($e->getMessage());
        }

        // Pastikan order valid tidak ter-update parsial (ter-rollback)
        $this->assertNull($validOrder->fresh()->outlet_id);
    }

    /**
     * 16. Seeder tidak membuat outlet atau record transaksi baru.
     */
    public function test_16_seeder_does_not_create_new_outlets_or_transactions(): void
    {
        [$outlet, $owner] = $this->createStandardLegacyOutletAndOwner();
        $master = $this->createMasterDataForOutlet($outlet);
        $this->createUnassignedLegacyTransactions($master);

        $initialOutletCount = Outlet::count();
        $initialOrderCount = Order::count();
        $initialPurchaseCount = Purchase::count();

        $seeder = new LegacyTransactionOutletSeeder();
        $seeder->run();

        $this->assertEquals($initialOutletCount, Outlet::count());
        $this->assertEquals($initialOrderCount, Order::count());
        $this->assertEquals($initialPurchaseCount, Purchase::count());
    }

    /**
     * 17. Data child (invoice, payment, batch, purchase_payment) tetap mengikuti kepemilikan parent.
     */
    public function test_17_child_records_follow_parent_ownership_consistently(): void
    {
        [$outlet, $owner] = $this->createStandardLegacyOutletAndOwner();
        $master = $this->createMasterDataForOutlet($outlet);
        $tx = $this->createUnassignedLegacyTransactions($master);

        $seeder = new LegacyTransactionOutletSeeder();
        $seeder->run();

        $this->assertEquals($outlet->id, $tx['invoice']->fresh()->outlet?->id);
        $this->assertEquals($outlet->id, $tx['payment']->fresh()->outlet?->id);
        $this->assertEquals($outlet->id, $tx['batch']->fresh()->outlet?->id);
        $this->assertEquals($outlet->id, $tx['purchasePayment']->fresh()->outlet?->id);
    }

    /**
     * 18. Database tanpa transaksi legacy tidak menghasilkan data palsu.
     */
    public function test_18_empty_transaction_database_does_not_create_dummy_data(): void
    {
        [$outlet, $owner] = $this->createStandardLegacyOutletAndOwner();

        $this->assertEquals(0, Order::count());
        $this->assertEquals(0, Purchase::count());

        $seeder = new LegacyTransactionOutletSeeder();
        $seeder->run();

        $this->assertEquals(0, Order::count());
        $this->assertEquals(0, Purchase::count());
    }
}
