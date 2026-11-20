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
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TransactionOutletOwnershipTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper membuat outlet uji.
     */
    protected function createTestOutlet(string $code = 'OUT-TEST-01', string $name = 'Outlet Test 01'): Outlet
    {
        return Outlet::create([
            'outlet_name'                => $name,
            'outlet_code'                => $code,
            'slug'                       => strtolower($code),
            'status'                     => 'ACTIVE',
            'is_public_showcase_enabled' => true,
        ]);
    }

    /**
     * Helper membuat data master baseline untuk transaksi.
     */
    protected function createMasterDataBaseline(?Outlet $outlet = null, string $suffix = '01'): array
    {
        $unit = Unit::firstOrCreate(
            ['symbol' => 'm'],
            ['unit_name' => 'Meter']
        );

        $customer = Customer::create([
            'customer_name' => 'Customer Test ' . $suffix,
            'phone'         => '0812' . str_pad($suffix, 8, '0', STR_PAD_RIGHT),
            'outlet_id'     => $outlet?->id,
        ]);

        $supplier = Supplier::create([
            'supplier_name' => 'Supplier Test ' . $suffix,
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
            'base_selling_price'         => 35000,
            'requires_printing'          => false,
            'printing_cost_per_unit'     => 0,
            'direct_labor_cost_per_unit' => 3000,
            'outlet_id'                  => $outlet?->id,
        ]);

        $material = Material::create([
            'unit_id'       => $unit->id,
            'material_name' => 'Kain Voal Premium ' . $suffix,
            'min_stock'     => 10,
            'outlet_id'     => $outlet?->id,
        ]);

        return compact('unit', 'customer', 'supplier', 'product', 'variant', 'material');
    }

    /**
     * 1. Tabel transaction root yang dipilih memiliki kolom outlet_id, dan child tables tidak memiliki kolom redundan.
     */
    public function test_01_selected_transaction_root_tables_have_outlet_id_column(): void
    {
        // Root tables yang dipilih
        $this->assertTrue(Schema::hasColumn('orders', 'outlet_id'), "Tabel 'orders' harus memiliki kolom outlet_id.");
        $this->assertTrue(Schema::hasColumn('purchases', 'outlet_id'), "Tabel 'purchases' harus memiliki kolom outlet_id.");

        // Child tables yang didesain mengikuti parent tanpa kolom redundan
        $this->assertFalse(Schema::hasColumn('invoices', 'outlet_id'), "Tabel 'invoices' sengaja tidak diberi kolom outlet_id redundan.");
        $this->assertFalse(Schema::hasColumn('payments', 'outlet_id'), "Tabel 'payments' sengaja tidak diberi kolom outlet_id redundan.");
        $this->assertFalse(Schema::hasColumn('production_batches', 'outlet_id'), "Tabel 'production_batches' sengaja tidak diberi kolom outlet_id redundan.");
    }

    /**
     * 2. Kolom outlet_id nullable pada fase transisi.
     */
    public function test_02_outlet_id_is_nullable_during_transition_phase(): void
    {
        $master = $this->createMasterDataBaseline();

        $order = Order::create([
            'customer_id' => $master['customer']->id,
            'order_no'    => 'SO-TRANS-001',
            'order_date'  => '2026-10-23',
            'status'      => OrderStatus::KOMUNIKASI,
            'outlet_id'   => null,
        ]);
        $this->assertNull($order->fresh()->outlet_id);

        $purchase = Purchase::create([
            'supplier_id'   => $master['supplier']->id,
            'purchase_no'   => 'PO-TRANS-001',
            'purchase_date' => '2026-10-23',
            'status'        => PurchaseStatus::DRAFT,
            'outlet_id'     => null,
        ]);
        $this->assertNull($purchase->fresh()->outlet_id);
    }

    /**
     * 3. Foreign key mengarah ke tabel outlets dan menolak ID outlet yang tidak ada.
     */
    public function test_03_foreign_key_references_outlets_table_and_rejects_invalid_id(): void
    {
        $master = $this->createMasterDataBaseline();

        $this->expectException(QueryException::class);

        Order::create([
            'customer_id' => $master['customer']->id,
            'order_no'    => 'SO-INVALID-OUTLET',
            'order_date'  => '2026-10-23',
            'status'      => OrderStatus::KOMUNIKASI,
            'outlet_id'   => 999999, // tidak terdaftar di outlets
        ]);
    }

    /**
     * 4. Penghapusan outlet yang memiliki transaksi ditolak melalui FK RESTRICT.
     */
    public function test_04_deletion_of_outlet_with_transactions_is_rejected_by_restrict_on_delete(): void
    {
        $outlet = $this->createTestOutlet();
        $master = $this->createMasterDataBaseline($outlet);

        $order = Order::create([
            'customer_id' => $master['customer']->id,
            'order_no'    => 'SO-RESTRICT-001',
            'order_date'  => '2026-10-23',
            'status'      => OrderStatus::KOMUNIKASI,
            'outlet_id'   => $outlet->id,
        ]);

        $this->expectException(QueryException::class);
        $outlet->delete();
    }

    /**
     * 5. Model transaksi memiliki relasi Outlet yang benar dan relasi balik pada Outlet tersedia.
     */
    public function test_05_transaction_models_have_correct_outlet_eloquent_relationships(): void
    {
        $outlet = $this->createTestOutlet();
        $master = $this->createMasterDataBaseline($outlet);

        $order = Order::create([
            'customer_id' => $master['customer']->id,
            'order_no'    => 'SO-REL-001',
            'order_date'  => '2026-10-23',
            'status'      => OrderStatus::KOMUNIKASI,
            'outlet_id'   => $outlet->id,
        ]);

        $purchase = Purchase::create([
            'supplier_id'   => $master['supplier']->id,
            'purchase_no'   => 'PO-REL-001',
            'purchase_date' => '2026-10-23',
            'status'        => PurchaseStatus::DRAFT,
            'outlet_id'     => $outlet->id,
        ]);

        // Forward belongsTo relationship
        $this->assertInstanceOf(Outlet::class, $order->outlet);
        $this->assertEquals($outlet->outlet_code, $order->outlet->outlet_code);

        $this->assertInstanceOf(Outlet::class, $purchase->outlet);
        $this->assertEquals($outlet->outlet_code, $purchase->outlet->outlet_code);

        // Reverse hasMany relationship on Outlet
        $freshOutlet = $outlet->fresh();
        $this->assertTrue($freshOutlet->orders->contains('id', $order->id));
        $this->assertTrue($freshOutlet->purchases->contains('id', $purchase->id));
    }

    /**
     * 6. Dua outlet dapat memiliki transaksi yang berbeda dalam testing secara terisolasi.
     */
    public function test_06_two_distinct_outlets_can_own_independent_transactions(): void
    {
        $outletA = $this->createTestOutlet('OUT-A', 'Konveksi Nayara Jakarta');
        $outletB = $this->createTestOutlet('OUT-B', 'Konveksi Nayara Bandung');

        $masterA = $this->createMasterDataBaseline($outletA, 'A');
        $masterB = $this->createMasterDataBaseline($outletB, 'B');

        $orderA = Order::create([
            'customer_id' => $masterA['customer']->id,
            'order_no'    => 'SO-OUTLET-A',
            'order_date'  => '2026-10-23',
            'status'      => OrderStatus::KOMUNIKASI,
            'outlet_id'   => $outletA->id,
        ]);

        $orderB = Order::create([
            'customer_id' => $masterB['customer']->id,
            'order_no'    => 'SO-OUTLET-B',
            'order_date'  => '2026-10-23',
            'status'      => OrderStatus::KOMUNIKASI,
            'outlet_id'   => $outletB->id,
        ]);

        $purchaseA = Purchase::create([
            'supplier_id'   => $masterA['supplier']->id,
            'purchase_no'   => 'PO-OUTLET-A',
            'purchase_date' => '2026-10-23',
            'status'        => PurchaseStatus::DRAFT,
            'outlet_id'     => $outletA->id,
        ]);

        $purchaseB = Purchase::create([
            'supplier_id'   => $masterB['supplier']->id,
            'purchase_no'   => 'PO-OUTLET-B',
            'purchase_date' => '2026-10-23',
            'status'        => PurchaseStatus::DRAFT,
            'outlet_id'     => $outletB->id,
        ]);

        // Filter eksplisit per outlet
        $ordersOutletA = Order::where('outlet_id', $outletA->id)->pluck('order_no')->all();
        $ordersOutletB = Order::where('outlet_id', $outletB->id)->pluck('order_no')->all();

        $this->assertEquals(['SO-OUTLET-A'], $ordersOutletA);
        $this->assertEquals(['SO-OUTLET-B'], $ordersOutletB);

        $purchasesOutletA = Purchase::where('outlet_id', $outletA->id)->pluck('purchase_no')->all();
        $purchasesOutletB = Purchase::where('outlet_id', $outletB->id)->pluck('purchase_no')->all();

        $this->assertEquals(['PO-OUTLET-A'], $purchasesOutletA);
        $this->assertEquals(['PO-OUTLET-B'], $purchasesOutletB);
    }

    /**
     * 7. Transaksi legacy dengan outlet_id NULL tetap kompatibel dengan query normal.
     */
    public function test_07_legacy_transactions_without_outlet_id_remain_fully_compatible(): void
    {
        $outlet = $this->createTestOutlet();
        $master = $this->createMasterDataBaseline();

        // 1 Transaksi legacy (outlet_id null)
        $legacyOrder = Order::create([
            'customer_id' => $master['customer']->id,
            'order_no'    => 'SO-LEGACY-001',
            'order_date'  => '2026-10-01',
            'status'      => OrderStatus::KOMUNIKASI,
            'outlet_id'   => null,
        ]);

        // 1 Transaksi outlet baru (outlet_id terisi)
        $newOrder = Order::create([
            'customer_id' => $master['customer']->id,
            'order_no'    => 'SO-NEW-001',
            'order_date'  => '2026-10-23',
            'status'      => OrderStatus::KOMUNIKASI,
            'outlet_id'   => $outlet->id,
        ]);

        // Query total tanpa filter tenant tetap mengembalikan semua (kompatibilitas historis)
        $this->assertEquals(2, Order::count());
        $this->assertNull($legacyOrder->fresh()->outlet);
        $this->assertNotNull($newOrder->fresh()->outlet);
    }

    /**
     * 8. Factory existing User dan Outlet tetap kompatibel tanpa error.
     */
    public function test_08_existing_factories_remain_compatible(): void
    {
        $outlet = Outlet::factory()->create();
        $user = User::factory()->create(['outlet_id' => $outlet->id]);

        $this->assertInstanceOf(Outlet::class, $outlet);
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($outlet->id, $user->outlet_id);
    }

    /**
     * 9. Foreign key transaksi existing (customer_id, supplier_id, approved_by) tetap utuh.
     */
    public function test_09_existing_foreign_keys_and_relations_remain_intact(): void
    {
        $outlet = $this->createTestOutlet();
        $master = $this->createMasterDataBaseline($outlet);

        $admin = User::factory()->create([
            'role'      => UserRole::ADMIN,
            'outlet_id' => $outlet->id,
        ]);

        $order = Order::create([
            'customer_id' => $master['customer']->id,
            'order_no'    => 'SO-FK-001',
            'order_date'  => '2026-10-23',
            'status'      => OrderStatus::KOMUNIKASI,
            'outlet_id'   => $outlet->id,
        ]);

        $purchase = Purchase::create([
            'supplier_id'     => $master['supplier']->id,
            'purchase_no'     => 'PO-FK-001',
            'purchase_date'   => '2026-10-23',
            'status'          => PurchaseStatus::RECEIVED,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
            'approved_by'     => $admin->id,
            'approved_at'     => now(),
            'outlet_id'       => $outlet->id,
        ]);

        $this->assertEquals($master['customer']->id, $order->customer->id);
        $this->assertEquals($master['supplier']->id, $purchase->supplier->id);
        $this->assertEquals($admin->id, $purchase->approvedByUser->id);
    }

    /**
     * 10. Unique index existing (order_no, purchase_no) tetap utuh dan menolak duplikasi.
     */
    public function test_10_existing_unique_indexes_remain_intact(): void
    {
        $outlet = $this->createTestOutlet();
        $master = $this->createMasterDataBaseline($outlet);

        Order::create([
            'customer_id' => $master['customer']->id,
            'order_no'    => 'SO-DUPLICATE-CHECK',
            'order_date'  => '2026-10-23',
            'status'      => OrderStatus::KOMUNIKASI,
            'outlet_id'   => $outlet->id,
        ]);

        $this->expectException(QueryException::class);

        // Duplikat nomor order ditolak oleh unique index
        Order::create([
            'customer_id' => $master['customer']->id,
            'order_no'    => 'SO-DUPLICATE-CHECK',
            'order_date'  => '2026-10-24',
            'status'      => OrderStatus::KOMUNIKASI,
            'outlet_id'   => $outlet->id,
        ]);
    }

    /**
     * 11. Hubungan parent-child mengikuti pola kepemilikan yang dirancang secara konsisten.
     */
    public function test_11_parent_child_ownership_hierarchy_is_consistently_preserved(): void
    {
        $outlet = $this->createTestOutlet();
        $master = $this->createMasterDataBaseline($outlet);

        // 1. Order (Root) dengan outlet_id
        $order = Order::create([
            'customer_id' => $master['customer']->id,
            'order_no'    => 'SO-PARENT-001',
            'order_date'  => '2026-10-23',
            'status'      => OrderStatus::KOMUNIKASI,
            'outlet_id'   => $outlet->id,
        ]);

        // 2. OrderItem (Child of Order)
        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $master['variant']->id,
            'qty_ordered'        => 50,
            'unit_selling_price' => 35000,
        ]);

        // 3. Invoice (Child of Order, 1-to-1)
        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-PARENT-001',
            'invoice_date' => '2026-10-23',
            'total_amount' => 1750000,
            'status'       => 'UNPAID',
        ]);

        // 4. Payment (Child of Invoice)
        $payment = Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_type'   => PaymentType::DP,
            'amount'         => 500000,
            'payment_date'   => '2026-10-23',
            'payment_method' => PaymentMethod::TRANSFER_BANK,
            'status'         => PaymentStatus::CONFIRMED,
        ]);

        // 5. ProductionBatch (Child of OrderItem)
        $batch = ProductionBatch::create([
            'order_item_id' => $orderItem->id,
            'batch_no'      => 'BAT-PARENT-001',
            'target_qty'    => 50,
            'status'        => BatchStatus::BAHAN,
        ]);

        // 6. Purchase (Root) & PurchasePayment (Child)
        $purchase = Purchase::create([
            'supplier_id'   => $master['supplier']->id,
            'purchase_no'   => 'PO-PARENT-001',
            'purchase_date' => '2026-10-23',
            'status'        => PurchaseStatus::DRAFT,
            'outlet_id'     => $outlet->id,
        ]);

        $purchasePayment = PurchasePayment::create([
            'purchase_id'    => $purchase->id,
            'amount'         => 1000000,
            'payment_date'   => '2026-10-23',
            'payment_method' => 'Transfer',
        ]);

        // Verifikasi seluruh child model mewarisi outlet dari parent secara konsisten
        $this->assertEquals($outlet->id, $invoice->outlet?->id);
        $this->assertEquals($outlet->id, $payment->outlet?->id);
        $this->assertEquals($outlet->id, $batch->outlet?->id);
        $this->assertEquals($outlet->id, $purchasePayment->outlet?->id);
    }

    /**
     * 12. Tidak ada Global Tenant Scope yang aktif secara prematur.
     */
    public function test_12_no_global_tenant_scope_is_prematurely_activated(): void
    {
        $outletA = $this->createTestOutlet('OUT-A');
        $outletB = $this->createTestOutlet('OUT-B');

        $masterA = $this->createMasterDataBaseline($outletA, 'A');
        $masterB = $this->createMasterDataBaseline($outletB, 'B');

        Order::create([
            'customer_id' => $masterA['customer']->id,
            'order_no'    => 'SO-SCOPE-A',
            'order_date'  => '2026-10-23',
            'status'      => OrderStatus::KOMUNIKASI,
            'outlet_id'   => $outletA->id,
        ]);

        Order::create([
            'customer_id' => $masterB['customer']->id,
            'order_no'    => 'SO-SCOPE-B',
            'order_date'  => '2026-10-23',
            'status'      => OrderStatus::KOMUNIKASI,
            'outlet_id'   => $outletB->id,
        ]);

        // Order::all() mengembalikan 2 record (belum ada global scope tersembunyi yang menyaring)
        $this->assertCount(2, Order::all());
    }

    /**
     * 13. Workflow approval Purchase Order tetap mutlak dilakukan oleh ADMIN.
     */
    public function test_13_purchase_order_approval_remains_strictly_admin(): void
    {
        $outlet = $this->createTestOutlet();
        $master = $this->createMasterDataBaseline($outlet);

        $admin = User::factory()->create([
            'role'      => UserRole::ADMIN,
            'outlet_id' => $outlet->id,
        ]);

        $purchase = Purchase::create([
            'supplier_id'     => $master['supplier']->id,
            'purchase_no'     => 'PO-APPROVAL-001',
            'purchase_date'   => '2026-10-23',
            'status'          => PurchaseStatus::ORDERED,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
            'approved_by'     => $admin->id,
            'approved_at'     => now(),
            'outlet_id'       => $outlet->id,
        ]);

        $this->assertEquals(UserRole::ADMIN, $purchase->approvedByUser->role);
        $this->assertEquals(PurchaseApprovalStatus::APPROVED, $purchase->approval_status);
    }

    /**
     * 14. Nilai bisnis dan kalkulasi order tetap tidak berubah dengan adanya kolom baru.
     */
    public function test_14_business_values_and_calculations_remain_unmodified(): void
    {
        $outlet = $this->createTestOutlet();
        $master = $this->createMasterDataBaseline($outlet);

        $order = Order::create([
            'customer_id' => $master['customer']->id,
            'order_no'    => 'SO-CALC-001',
            'order_date'  => '2026-10-23',
            'status'      => OrderStatus::KOMUNIKASI,
            'outlet_id'   => $outlet->id,
        ]);

        OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $master['variant']->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 35000,
        ]);

        OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $master['variant']->id,
            'qty_ordered'        => 5,
            'unit_selling_price' => 35000,
        ]);

        $freshOrder = $order->fresh(['items']);
        $this->assertEquals(15.0, $freshOrder->total_qty);
        $this->assertEquals(525000.0, $freshOrder->total_amount);
    }

    /**
     * 15. Migration baru kompatibel dan dapat di-rollback (down) dengan bersih.
     */
    public function test_15_migration_is_reversible(): void
    {
        $migration = require database_path('migrations/2026_10_23_000001_add_outlet_id_to_transaction_tables.php');

        // Rollback
        $migration->down();
        $this->assertFalse(Schema::hasColumn('orders', 'outlet_id'));
        $this->assertFalse(Schema::hasColumn('purchases', 'outlet_id'));

        // Re-apply
        $migration->up();
        $this->assertTrue(Schema::hasColumn('orders', 'outlet_id'));
        $this->assertTrue(Schema::hasColumn('purchases', 'outlet_id'));
    }
}
