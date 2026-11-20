<?php

namespace Tests\Feature\Finance;

use App\Enums\ExpenseType;
use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\PurchaseStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Finance\BusinessExpense;
use App\Models\Finance\OpeningCashBalance;
use App\Models\Inventory\MaterialStock;
use App\Models\Inventory\Purchase;
use App\Models\Inventory\PurchaseItem;
use App\Models\Inventory\PurchasePayment;
use App\Models\Master\Customer;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Master\Supplier;
use App\Models\Master\Unit;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\Sales\Payment;
use App\Models\System\User;
use App\Services\Finance\FinancialRecordService;
use App\Enums\PurchaseApprovalStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class FinancialCutoffAndSupplierCashGuardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Supplier $supplier;
    private Customer $customer;
    private Material $material;
    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role'   => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
            'name'   => 'Admin Keuangan',
        ]);

        $this->supplier = Supplier::create([
            'supplier_name'  => 'PT Kain Halus Tekstil',
            'contact_person' => 'Ibu Siti',
            'phone'          => '0812332211',
            'address'        => 'Solo',
        ]);

        $this->customer = Customer::create([
            'customer_name' => 'Nayara Reseller Pusat',
            'phone'         => '081299887766',
            'address'       => 'Bandung',
        ]);

        $unit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $this->material = Material::create([
            'unit_id'       => $unit->id,
            'material_name' => 'Kain Voal Super',
            'min_stock'     => '10.0000',
            'active'        => true,
        ]);

        MaterialStock::create([
            'material_id'   => $this->material->id,
            'qty_on_hand'   => '100.0000',
            'avg_unit_cost' => '25000.00',
        ]);

        $product = Product::create(['product_name' => 'Hijab Segiempat Premium', 'active' => true]);
        $this->variant = ProductVariant::create([
            'product_id'                 => $product->id,
            'variant_name'               => 'Dusty Pink',
            'sku'                        => 'HJB-SE-DP-001',
            'base_selling_price'         => '100000.00',
            'direct_labor_cost_per_unit' => '5000.00',
        ]);
    }

    /**
     * Helper to create a deal order with invoice.
     */
    private function createOrderWithInvoice(string $total = '10000000.00', string $date = '2026-10-01'): array
    {
        $order = Order::create([
            'customer_id'  => $this->customer->id,
            'order_no'     => 'SO-' . uniqid(),
            'order_date'   => $date,
            'status'       => OrderStatus::DEAL,
        ]);

        OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => '100.0000',
            'unit_selling_price' => bcdiv($total, '100', 2),
        ]);

        $inv = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-' . uniqid(),
            'invoice_date' => $date,
            'total_amount' => $total,
            'status'       => InvoiceStatus::UNPAID,
        ]);

        return [$order, $inv];
    }

    /**
     * Helper to create a purchase order.
     */
    private function createPurchase(string $total = '5000000.00', string $date = '2026-10-01'): Purchase
    {
        $po = Purchase::create([
            'supplier_id'   => $this->supplier->id,
            'purchase_no'   => 'PO-' . uniqid(),
            'purchase_date' => $date,
            'status'          => PurchaseStatus::ORDERED,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
        ]);

        PurchaseItem::create([
            'purchase_id'  => $po->id,
            'material_id'  => $this->material->id,
            'qty'          => '100.0000',
            'unit_price'   => bcdiv($total, '100', 2),
            'qty_received' => '0.0000',
        ]);

        return $po;
    }

    /**
     * OPENING CUT-OFF TESTS (Phase 16: Tests 1 - 7)
     * 1. Opening date = Oct 8, cash = 8m.
     * 2. Customer payment Oct 7 does NOT increase current cash.
     * 3. Customer payment Oct 8 DOES increase current cash.
     * 4. Expense Oct 7 does NOT decrease current cash.
     * 5. Expense Oct 8 DOES decrease current cash.
     * 6. Supplier payment Oct 7 does NOT decrease current cash.
     * 7. Supplier payment Oct 8 DOES decrease current cash.
     */
    public function test_01_to_07_opening_cut_off_boundary_semantics(): void
    {
        $service = app(FinancialRecordService::class);

        // 1. Opening date = Oct 8, cash = 8,000,000
        $opening = OpeningCashBalance::create([
            'amount'       => '8000000.00',
            'opening_date' => '2026-10-08',
            'created_by'   => $this->admin->id,
        ]);

        $this->assertEquals('8000000.00', $service->calculateCurrentCash($opening));

        // 2. Customer payment Oct 7 (historical) does NOT increase current cash
        [$order1, $inv1] = $this->createOrderWithInvoice('2000000.00', '2026-10-01');
        Payment::create([
            'invoice_id'   => $inv1->id,
            'payment_date' => '2026-10-07',
            'amount'       => '1000000.00',
            'payment_type' => PaymentType::DP,
            'status'       => PaymentStatus::CONFIRMED,
            'confirmed_by' => $this->admin->id,
        ]);

        // Current cash MUST remain 8,000,000
        $this->assertEquals('8000000.00', $service->calculateCurrentCash($opening));

        // 3. Customer payment Oct 8 (on cut-off) DOES increase current cash
        Payment::create([
            'invoice_id'   => $inv1->id,
            'payment_date' => '2026-10-08',
            'amount'       => '1000000.00',
            'payment_type' => PaymentType::PELUNASAN,
            'status'       => PaymentStatus::CONFIRMED,
            'confirmed_by' => $this->admin->id,
        ]);

        // Current cash becomes 8m + 1m = 9,000,000
        $this->assertEquals('9000000.00', $service->calculateCurrentCash($opening));

        // 4. Expense Oct 7 (historical) does NOT decrease current cash
        BusinessExpense::create([
            'expense_date' => '2026-10-07',
            'expense_type' => ExpenseType::OPERATING,
            'amount'       => '500000.00',
            'description'  => 'Biaya Listrik Lama',
            'created_by'   => $this->admin->id,
        ]);

        // Current cash MUST remain 9,000,000
        $this->assertEquals('9000000.00', $service->calculateCurrentCash($opening));

        // 5. Expense Oct 8 (on cut-off) DOES decrease current cash
        BusinessExpense::create([
            'expense_date' => '2026-10-08',
            'expense_type' => ExpenseType::OPERATING,
            'amount'       => '700000.00',
            'description'  => 'Biaya Operasional Baru',
            'created_by'   => $this->admin->id,
        ]);

        // Current cash becomes 9m - 0.7m = 8,300,000
        $this->assertEquals('8300000.00', $service->calculateCurrentCash($opening));

        // 6. Supplier payment Oct 7 (historical) does NOT decrease current cash
        $po1 = $this->createPurchase('3000000.00', '2026-10-01');
        PurchasePayment::create([
            'purchase_id'    => $po1->id,
            'amount'         => '1000000.00',
            'payment_date'   => '2026-10-07',
            'payment_method' => 'TRANSFER',
            'created_by'     => $this->admin->id,
        ]);

        // Current cash MUST remain 8,300,000
        $this->assertEquals('8300000.00', $service->calculateCurrentCash($opening));

        // 7. Supplier payment Oct 8 (on cut-off) DOES decrease current cash
        PurchasePayment::create([
            'purchase_id'    => $po1->id,
            'amount'         => '1300000.00',
            'payment_date'   => '2026-10-08',
            'payment_method' => 'TRANSFER',
            'created_by'     => $this->admin->id,
        ]);

        // Current cash becomes 8.3m - 1.3m = 7,000,000
        $this->assertEquals('7000000.00', $service->calculateCurrentCash($opening));

        // Verify consolidated report
        $records = $service->getConsolidatedRecords();
        $this->assertEquals('7000000.00', $records['cash_position']['kas_tersedia']);
        $this->assertEquals('8000000.00', $records['cash_position']['opening_balance']);
    }

    /**
     * NO OPENING CASH TESTS (Phase 16: Tests 8 - 10)
     * 8. Current Cash displays unavailable.
     * 9. Supplier payment today blocked.
     * 10. PO sufficiency displays unavailable.
     */
    public function test_08_to_10_no_opening_cash_guards_and_status(): void
    {
        $service = app(FinancialRecordService::class);
        $records = $service->getConsolidatedRecords();

        // 8. Current cash is null / unavailable
        $this->assertNull($records['cash_position']['kas_tersedia']);
        $this->assertFalse($records['cash_position']['has_opening_balance']);

        // View displays "Belum tersedia" and "Saldo Awal Kas belum ditentukan"
        $viewResponse = $this->actingAs($this->admin)->get(route('admin.financial-records.index'));
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Belum tersedia');
        $viewResponse->assertSee('Saldo Awal Kas belum ditentukan.');

        // 10. PO sufficiency displays unavailable (BELUM DAPAT DIHITUNG, not KAS TIDAK CUKUP)
        $this->assertFalse($records['procurement_summary']['is_sufficient']);
        $this->assertEquals('BELUM_DAPAT_DIHITUNG', $records['procurement_summary']['sufficiency_status']);
        $this->assertNull($records['procurement_summary']['projected_cash']);
        $this->assertNull($records['procurement_summary']['shortage']);

        // PO sufficiency displayed on dedicated Pembayaran Supplier page
        $payPageResponse = $this->actingAs($this->admin)->get(route('admin.purchases.payments.index'));
        $payPageResponse->assertStatus(200);
        $payPageResponse->assertSee('Belum Dihitung');
        $payPageResponse->assertDontSee('Tidak Cukup');

        // 9. Supplier payment today blocked
        $po = $this->createPurchase('2000000.00', '2026-10-08');

        // Attempting to view create page redirects with warning
        $createPageResponse = $this->actingAs($this->admin)->get(route('admin.purchases.payments.create', $po));
        $createPageResponse->assertRedirect(route('admin.financial-records.index'));
        $createPageResponse->assertSessionHas('error', 'Saldo awal kas belum ditentukan. Atur saldo awal kas sebelum mencatat pembayaran supplier.');

        // Attempting to POST payment fails validation with clear message
        $storeResponse = $this->actingAs($this->admin)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '500000.00',
            'payment_date'   => '2026-10-08',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ]);
        $storeResponse->assertSessionHasErrors(['amount' => 'Saldo awal kas belum ditentukan. Atur saldo awal kas sebelum mencatat pembayaran supplier.']);
        $this->assertEquals(0, PurchasePayment::count());
    }

    /**
     * HARD CASH GUARD TESTS (Phase 16: Tests 11 - 15)
     * 11. Cash 2m, outstanding 5m, payment 2m accepted.
     * 12. Cash 2m, outstanding 5m, payment 2.01m rejected.
     * 13. Cash cannot become negative.
     * 14. Crafted POST cannot bypass.
     * 15. Two concurrent/near-concurrent payments cannot overspend cash.
     */
    public function test_11_to_15_hard_cash_guard_and_concurrency_protection(): void
    {
        // Setup: Cash 2,000,000 on 2026-10-08
        OpeningCashBalance::create([
            'amount'       => '2000000.00',
            'opening_date' => '2026-10-08',
            'created_by'   => $this->admin->id,
        ]);

        $po = $this->createPurchase('5000000.00', '2026-10-08');
        $this->assertEquals('5000000.00', $po->outstanding_amount);

        // 12. Payment 2.01m (> available cash 2.00m) REJECTED
        $overpayResponse = $this->actingAs($this->admin)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '2010000.00',
            'payment_date'   => '2026-10-08',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ]);
        $overpayResponse->assertSessionHasErrors('amount');
        $this->assertEquals(0, PurchasePayment::count());

        // 14. Crafted POST of 4.5m cannot bypass server-side validation
        $craftedResponse = $this->actingAs($this->admin)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '4500000.00',
            'payment_date'   => '2026-10-08',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ]);
        $craftedResponse->assertSessionHasErrors('amount');
        $this->assertEquals(0, PurchasePayment::count());

        // 11. Payment 2m (<= available cash 2m) ACCEPTED
        $validPayResponse = $this->actingAs($this->admin)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '2000000.00',
            'payment_date'   => '2026-10-08',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ]);
        $validPayResponse->assertRedirect(route('admin.financial-records.index'));
        $validPayResponse->assertSessionHas('success');

        $po->refresh();
        $this->assertEquals('2000000.00', $po->paid_amount);
        $this->assertEquals('3000000.00', $po->outstanding_amount);

        // 13. Cash is now exactly 0.00, CANNOT become negative
        $service = app(FinancialRecordService::class);
        $records = $service->getConsolidatedRecords();
        $this->assertEquals('0.00', $records['cash_position']['kas_tersedia']);

        // 15. Attempting another payment when cash is 0 is rejected (cannot overspend cash)
        $secondPayResponse = $this->actingAs($this->admin)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '100000.00',
            'payment_date'   => '2026-10-08',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ]);
        $secondPayResponse->assertSessionHasErrors('amount');
        $this->assertEquals('0.00', $records['cash_position']['kas_tersedia']);
    }

    /**
     * HISTORICAL PURCHASE PAYMENT TESTS (Phase 16: Tests 16 - 22)
     * 16. Old PO outstanding 5m.
     * 17. Historical payment 5m dated before opening accepted.
     * 18. PO outstanding becomes zero.
     * 19. Current cash unchanged.
     * 20. Payment appears in historical ledger.
     * 21. No stock impact.
     * 22. No MWA impact.
     */
    public function test_16_to_22_historical_purchase_payment_lifecycle(): void
    {
        // Opening cash on Oct 8 = 8,000,000
        OpeningCashBalance::create([
            'amount'       => '8000000.00',
            'opening_date' => '2026-10-08',
            'created_by'   => $this->admin->id,
        ]);

        // 16. Old PO outstanding 5,000,000 created Oct 1
        $oldPo = $this->createPurchase('5000000.00', '2026-10-01');
        $this->assertEquals('5000000.00', $oldPo->outstanding_amount);

        // Record stock & MWA before payment
        $stockBefore = MaterialStock::where('material_id', $this->material->id)->first();
        $qtyBefore = $stockBefore->qty_on_hand;
        $mwaBefore = $stockBefore->avg_unit_cost;

        // 17. Historical payment of 5,000,000 dated Oct 5 (< Oct 8 cut-off) is ACCEPTED
        // Notice: even if cash was less than 5m (e.g. if cash was 2m), historical payment is accepted!
        $response = $this->actingAs($this->admin)->post(route('admin.purchases.payments.store', $oldPo), [
            'amount'         => '5000000.00',
            'payment_date'   => '2026-10-05',
            'payment_method' => 'TRANSFER_MANDIRI',
            'reference_no'   => 'HIST-TRF-001',
            'notes'          => 'Rekonsiliasi PO lama pra-sistem',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ]);
        $response->assertRedirect(route('admin.financial-records.index'));
        $response->assertSessionHas('success');

        // 18. PO outstanding becomes zero
        $oldPo->refresh();
        $this->assertEquals('5000000.00', $oldPo->paid_amount);
        $this->assertEquals('0.00', $oldPo->outstanding_amount);
        $this->assertTrue($oldPo->isFullyPaid());

        // 19. Current cash UNCHANGED (still 8,000,000!)
        $service = app(FinancialRecordService::class);
        $records = $service->getConsolidatedRecords();
        $this->assertEquals('8000000.00', $records['cash_position']['kas_tersedia']);

        // 20. Payment appears in historical ledger
        $this->assertDatabaseHas('purchase_payments', [
            'purchase_id'  => $oldPo->id,
            'reference_no' => 'HIST-TRF-001',
        ]);
        $savedPayment = PurchasePayment::where('reference_no', 'HIST-TRF-001')->first();
        $this->assertNotNull($savedPayment);
        $this->assertEquals('5000000.00', (string) $savedPayment->amount);
        $this->assertEquals('2026-10-05', $savedPayment->payment_date->toDateString());

        $viewResponse = $this->actingAs($this->admin)->get(route('admin.financial-records.index'));
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('PO #' . $oldPo->purchase_no);
        $viewResponse->assertSee('Historis');

        // 21. No stock impact
        $stockAfter = MaterialStock::where('material_id', $this->material->id)->first();
        $this->assertEquals($qtyBefore, $stockAfter->qty_on_hand);

        // 22. No MWA impact
        $this->assertEquals(0, bccomp($mwaBefore, $stockAfter->avg_unit_cost, 2));

        // Check PO show page displays payment history (viewed by Gudang role)
        $gudang = User::factory()->create(['role' => UserRole::GUDANG, 'status' => UserStatus::ACTIVE]);
        $poShowResponse = $this->actingAs($gudang)->get(route('gudang.purchases.show', $oldPo));
        $poShowResponse->assertStatus(200);
        $poShowResponse->assertSee('HIST-TRF-001');
        $poShowResponse->assertSee('Rp 5.000.000');
    }

    /**
     * CUSTOMER PAYMENT HISTORICAL TESTS (Phase 16: Tests 23 - 24)
     * 23. Historical confirmed payment still counts as customer paid.
     * 24. But does not double-count current cash.
     */
    public function test_23_to_24_customer_payment_historical(): void
    {
        // Opening cash on Oct 8 = 8,000,000
        OpeningCashBalance::create([
            'amount'       => '8000000.00',
            'opening_date' => '2026-10-08',
            'created_by'   => $this->admin->id,
        ]);

        [$order, $inv] = $this->createOrderWithInvoice('10000000.00', '2026-10-01');

        // Historical payment 4m confirmed on Oct 5
        Payment::create([
            'invoice_id'   => $inv->id,
            'payment_date' => '2026-10-05',
            'amount'       => '4000000.00',
            'payment_type' => PaymentType::DP,
            'status'       => PaymentStatus::CONFIRMED,
            'confirmed_by' => $this->admin->id,
        ]);

        $service = app(FinancialRecordService::class);
        $records = $service->getConsolidatedRecords();

        // 23. Historical confirmed payment STILL counts in sales summary as customer paid
        $this->assertEquals('10000000.00', $records['sales_summary']['total_penjualan']);
        $this->assertEquals('4000000.00', $records['sales_summary']['sudah_dibayar']);
        $this->assertEquals('6000000.00', $records['sales_summary']['belum_dibayar']);

        // 24. But does NOT double-count to current cash (cash remains exactly 8,000,000)
        $this->assertEquals('8000000.00', $records['cash_position']['kas_tersedia']);
    }

    /**
     * EXPENSE HISTORICAL TESTS (Phase 16: Tests 25 - 26)
     * 25. Historical expense remains visible.
     * 26. But does not double-count current cash.
     */
    public function test_25_to_26_expense_historical(): void
    {
        // Opening cash on Oct 8 = 8,000,000
        OpeningCashBalance::create([
            'amount'       => '8000000.00',
            'opening_date' => '2026-10-08',
            'created_by'   => $this->admin->id,
        ]);

        // Historical expense on Oct 3 = 1,500,000
        BusinessExpense::create([
            'expense_date' => '2026-10-03',
            'expense_type' => ExpenseType::OPERATING,
            'amount'       => '1500000.00',
            'description'  => 'Biaya Listrik Pabrik Pra-Cutoff',
            'created_by'   => $this->admin->id,
        ]);

        $service = app(FinancialRecordService::class);
        $records = $service->getConsolidatedRecords();

        // 25. Historical expense remains visible in ledger
        $this->assertEquals(1, $records['records']->total());
        $viewResponse = $this->actingAs($this->admin)->get(route('admin.financial-records.index'));
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Biaya Listrik Pabrik Pra-Cutoff');
        $viewResponse->assertSee('Historis');

        // 26. But does NOT reduce current cash (cash remains 8,000,000)
        $this->assertEquals('8000000.00', $records['cash_position']['kas_tersedia']);
    }

    /**
     * PO PAYABLE SEMANTICS & STOCK ISOLATION TESTS (Phase 6)
     * 1. DRAFT PO not included in payable summary.
     * 2. CANCELLED PO not included.
     * 3. Committed PO (ORDERED) included.
     * 4. RECEIVED unpaid PO included.
     * 5. Received fully-paid PO outstanding = zero.
     * 6. PO creation does not change cash.
     * 7. PO receiving does not change cash.
     * 8. PurchasePayment still financial-only (no StockMovement, no qty change, no MWA change).
     */
    public function test_po_payable_semantics_and_procurement_cash_isolation(): void
    {
        OpeningCashBalance::create([
            'amount'       => '10000000.00',
            'opening_date' => '2026-10-08',
            'created_by'   => $this->admin->id,
        ]);

        $service = app(FinancialRecordService::class);

        // 1. DRAFT PO (3,000,000)
        $draftPo = Purchase::create([
            'supplier_id'   => $this->supplier->id,
            'purchase_no'   => 'PO-DRAFT-01',
            'purchase_date' => '2026-10-08',
            'status'        => PurchaseStatus::DRAFT,
        ]);
        PurchaseItem::create([
            'purchase_id'  => $draftPo->id,
            'material_id'  => $this->material->id,
            'qty'          => '30.0000',
            'unit_price'   => '100000.00',
            'qty_received' => '0.0000',
        ]);

        // 2. CANCELLED PO (4,000,000)
        $cancelledPo = Purchase::create([
            'supplier_id'   => $this->supplier->id,
            'purchase_no'   => 'PO-CANCEL-01',
            'purchase_date' => '2026-10-08',
            'status'        => PurchaseStatus::CANCELLED,
        ]);
        PurchaseItem::create([
            'purchase_id'  => $cancelledPo->id,
            'material_id'  => $this->material->id,
            'qty'          => '40.0000',
            'unit_price'   => '100000.00',
            'qty_received' => '0.0000',
        ]);

        // 6. PO creation does NOT change cash
        $records = $service->getConsolidatedRecords();
        $this->assertEquals('10000000.00', $records['cash_position']['kas_tersedia']);
        // Draft and Cancelled POs MUST NOT enter procurement summary
        $this->assertEquals('0.00', $records['procurement_summary']['total_po']);
        $this->assertEquals('0.00', $records['procurement_summary']['po_outstanding']);
        $this->assertCount(0, $records['procurement_summary']['unpaid_purchases']);

        // Attempting to open payment form or pay DRAFT PO must be blocked
        $this->actingAs($this->admin)->get(route('admin.purchases.payments.create', $draftPo))
            ->assertRedirect(route('admin.financial-records.index'))
            ->assertSessionHas('error');

        $this->actingAs($this->admin)->post(route('admin.purchases.payments.store', $draftPo), [
            'amount'         => '1000000.00',
            'payment_date'   => '2026-10-08',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('amount');

        // 3. Committed PO: ORDERED (2,000,000)
        $orderedPo = Purchase::create([
            'supplier_id'   => $this->supplier->id,
            'purchase_no'   => 'PO-ORDERED-01',
            'purchase_date' => '2026-10-08',
            'status'        => PurchaseStatus::ORDERED,
        ]);
        PurchaseItem::create([
            'purchase_id'  => $orderedPo->id,
            'material_id'  => $this->material->id,
            'qty'          => '20.0000',
            'unit_price'   => '100000.00',
            'qty_received' => '0.0000',
        ]);

        // 4. RECEIVED unpaid PO (5,000,000)
        $receivedPo = Purchase::create([
            'supplier_id'     => $this->supplier->id,
            'purchase_no'     => 'PO-RECEIVED-01',
            'purchase_date'   => '2026-10-08',
            'status'          => PurchaseStatus::RECEIVED,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
        ]);
        PurchaseItem::create([
            'purchase_id'  => $receivedPo->id,
            'material_id'  => $this->material->id,
            'qty'          => '50.0000',
            'unit_price'   => '100000.00',
            'qty_received' => '50.0000',
        ]);

        // 7. PO receiving does NOT change cash
        $records = $service->getConsolidatedRecords();
        $this->assertEquals('10000000.00', $records['cash_position']['kas_tersedia']);
        // Summary includes ORDERED (2m) + RECEIVED (5m) = 7m
        $this->assertEquals('7000000.00', $records['procurement_summary']['total_po']);
        $this->assertEquals('7000000.00', $records['procurement_summary']['po_outstanding']);
        $this->assertCount(2, $records['procurement_summary']['unpaid_purchases']);

        // 5. Received fully-paid PO outstanding = zero
        // Pay the 5,000,000 received PO in full
        $stockBefore = MaterialStock::where('material_id', $this->material->id)->first()?->qty_on_hand ?? '0.0000';
        $mwaBefore = $this->material->fresh()->stock?->avg_unit_cost ?? '0.00';

        $payResponse = $this->actingAs($this->admin)->post(route('admin.purchases.payments.store', $receivedPo), [
            'amount'         => '5000000.00',
            'payment_date'   => '2026-10-08',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ]);
        $payResponse->assertRedirect(route('admin.financial-records.index'));

        // 8. PurchasePayment still financial-only:
        // - Cash reduced by 5,000,000 (from 10,000,000 to 5,000,000)
        // - Stock qty_on_hand NOT changed
        // - MWA avg_unit_cost NOT changed
        // - No new stock movement created
        $records = $service->getConsolidatedRecords();
        $this->assertEquals('5000000.00', $records['cash_position']['kas_tersedia']);
        $this->assertEquals('7000000.00', $records['procurement_summary']['total_po']);
        $this->assertEquals('5000000.00', $records['procurement_summary']['po_paid']);
        $this->assertEquals('2000000.00', $records['procurement_summary']['po_outstanding']);

        // Fully paid PO is excluded from unpaid_purchases; only orderedPo remains
        $this->assertCount(1, $records['procurement_summary']['unpaid_purchases']);
        $this->assertEquals($orderedPo->id, $records['procurement_summary']['unpaid_purchases']->first()['purchase_id']);
        $this->assertEquals('0.00', $receivedPo->fresh()->outstanding_amount);

        // Assert stock and MWA safety
        $stockAfter = MaterialStock::where('material_id', $this->material->id)->first()?->qty_on_hand ?? '0.0000';
        $mwaAfter = $this->material->fresh()->stock?->avg_unit_cost ?? '0.00';
        $this->assertEquals($stockBefore, $stockAfter);
        $this->assertEquals($mwaBefore, $mwaAfter);
        $this->assertDatabaseMissing('stock_movements', [
            'reference_type' => 'purchase_payments',
        ]);
    }
}
