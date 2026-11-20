<?php

namespace Tests\Feature\Finance;

use App\Enums\ExpenseType;
use App\Enums\InvoiceStatus;
use App\Enums\MovementType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\PurchaseApprovalStatus;
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
use App\Models\Master\Supplier;
use App\Models\Master\Unit;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\Sales\Payment;
use App\Models\System\User;
use App\Services\Finance\FinancialRecordService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class OperationalFinancialSummaryAndPurchasePaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $gudang;
    private User $produksi;
    private User $customerUser;
    private Customer $customer;
    private Supplier $supplier;
    private Material $material;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role'   => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
            'name'   => 'Admin Keuangan',
        ]);

        $this->gudang = User::factory()->create([
            'role'   => UserRole::GUDANG,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->produksi = User::factory()->create([
            'role'   => UserRole::PRODUKSI,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->customerUser = User::factory()->create([
            'role'   => UserRole::CUSTOMER,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->customer = Customer::create([
            'customer_name' => 'Nayara Reseller B',
            'phone'         => '081299887766',
            'address'       => 'Bandung',
        ]);

        $this->supplier = Supplier::create([
            'supplier_name'  => 'PT Kain Halus Tekstil',
            'contact_person' => 'Ibu Siti',
            'phone'          => '0812332211',
            'address'        => 'Solo',
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

        $product = \App\Models\Master\Product::create(['product_name' => 'Hijab Segiempat', 'active' => true]);
        $this->variant = \App\Models\Master\ProductVariant::create([
            'product_id'                 => $product->id,
            'variant_name'               => 'Dusty Pink',
            'sku'                        => 'HJB-SE-DP-001',
            'base_selling_price'         => '100000.00',
            'direct_labor_cost_per_unit' => '5000.00',
        ]);
    }

    /**
     * 1. Valid/Deal order appears in Total Penjualan.
     * 2. Cancelled/non-sale order excluded (KOMUNIKASI excluded).
     * 3. Confirmed DP counts as paid.
     * 4. Confirmed final payment counts as paid.
     * 5. Pending payment not counted as paid.
     * 6. Belum Dibayar calculated correctly.
     */
    public function test_sales_summary_and_receivables_calculation(): void
    {
        // Valid Order 1: DEAL, 10,000,000
        $order1 = Order::create([
            'customer_id'  => $this->customer->id,
            'order_no'     => 'SO-202610-0101',
            'order_date'   => '2026-10-01',
            'status'       => OrderStatus::DEAL,
        ]);
        OrderItem::create([
            'order_id'           => $order1->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => '100.0000',
            'unit_selling_price' => '100000.00', // Total = 10,000,000
        ]);

        $inv1 = Invoice::create([
            'order_id'     => $order1->id,
            'invoice_no'   => 'INV-202610-0101',
            'invoice_date' => '2026-10-01',
            'total_amount' => '10000000.00',
            'status'       => InvoiceStatus::UNPAID,
        ]);

        // Confirmed DP 4,000,000
        Payment::create([
            'invoice_id'   => $inv1->id,
            'payment_date' => '2026-10-02',
            'amount'       => '4000000.00',
            'payment_type' => PaymentType::DP,
            'status'       => PaymentStatus::CONFIRMED,
            'confirmed_by' => $this->admin->id,
        ]);

        // Pending Payment 2,000,000 (MUST NOT COUNT AS PAID)
        Payment::create([
            'invoice_id'   => $inv1->id,
            'payment_date' => '2026-10-03',
            'amount'       => '2000000.00',
            'payment_type' => PaymentType::PELUNASAN,
            'status'       => PaymentStatus::PENDING,
        ]);

        // Order 2: KOMUNIKASI (Quotation/Inquiry stage -> MUST NOT COUNT IN TOTAL PENJUALAN)
        $order2 = Order::create([
            'customer_id'  => $this->customer->id,
            'order_no'     => 'SO-202610-0102',
            'order_date'   => '2026-10-02',
            'status'       => OrderStatus::KOMUNIKASI,
        ]);
        OrderItem::create([
            'order_id'           => $order2->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => '50.0000',
            'unit_selling_price' => '100000.00', // 5,000,000
        ]);

        $service = app(FinancialRecordService::class);
        $result = $service->getConsolidatedRecords();

        // Total Penjualan = 10,000,000 (Order 1 only)
        $this->assertEquals('10000000.00', $result['sales_summary']['total_penjualan']);
        // Sudah Dibayar = 4,000,000 (Confirmed DP only)
        $this->assertEquals('4000000.00', $result['sales_summary']['sudah_dibayar']);
        // Belum Dibayar = 6,000,000
        $this->assertEquals('6000000.00', $result['sales_summary']['belum_dibayar']);

        // Check Admin view (Catatan Keuangan focuses purely on cashflow; sales cards removed)
        $response = $this->actingAs($this->admin)->get(route('admin.financial-records.index'));
        $response->assertStatus(200);
        $response->assertSee('Rp 4.000.000'); // Confirmed payment appears in cashflow
        $response->assertDontSee('Total Penjualan');
        $response->assertDontSee('Belum Dibayar');
        $response->assertDontSee('Rp 6.000.000');

        // Customer invoice page retains invoice and billing responsibilities
        $invResponse = $this->actingAs($this->admin)->get(route('admin.invoices.index'));
        $invResponse->assertStatus(200);
    }

    /**
     * 7. Admin can set opening cash.
     * 8. Opening cash not counted as revenue.
     * 9. Opening cash not counted as profit.
     * 10. Non-Admin cannot mutate opening cash.
     */
    public function test_opening_cash_balance_management_and_safety(): void
    {
        // 10. Non-admin forbidden
        $this->actingAs($this->gudang)->get(route('admin.opening-cash.edit'))->assertStatus(403);
        $this->actingAs($this->produksi)->get(route('admin.opening-cash.edit'))->assertStatus(403);
        $this->actingAs($this->customerUser)->get(route('admin.opening-cash.edit'))->assertStatus(403);

        // 7. Admin sets opening cash
        $postResponse = $this->actingAs($this->admin)->post(route('admin.opening-cash.store'), [
            'amount'       => '25000000.00',
            'opening_date' => '2026-10-01',
            'notes'        => 'Kas modal awal bank operasional',
        ]);

        $postResponse->assertRedirect(route('admin.financial-records.index'));
        $this->assertDatabaseHas('opening_cash_balances', [
            'amount'     => '25000000.00',
            'created_by' => $this->admin->id,
        ]);

        $service = app(FinancialRecordService::class);
        $result = $service->getConsolidatedRecords();

        // 8 & 9. Opening cash does NOT affect sales revenue or period cashflow summary
        $this->assertEquals('25000000.00', $result['cash_position']['opening_balance']);
        $this->assertEquals('0.00', $result['sales_summary']['total_penjualan']);
        $this->assertEquals('0.00', $result['summary']['total_masuk']);
        $this->assertEquals('25000000.00', $result['cash_position']['kas_tersedia']);
    }

    /**
     * 11. Admin can record supplier payment.
     * 12. payment > 0 required.
     * 13. payment > outstanding rejected.
     * 14. cancelled PO payment rejected.
     * 15. partial supplier payment supported.
     * 16. multiple payment rows sum correctly.
     * 17. supplier payment does not change stock.
     * 18. supplier payment does not change MWA.
     * 19. supplier payment appears as one KELUAR ledger row.
     * 20. no duplicate expense created.
     */
    public function test_purchase_payment_lifecycle_and_invariants(): void
    {
        // Set Opening Cash so supplier payments can be recorded
        OpeningCashBalance::create([
            'amount'       => '10000000.00',
            'opening_date' => '2026-10-01',
            'created_by'   => $this->admin->id,
        ]);

        // Create PO: Total = 100 m * Rp 30.000 = Rp 3.000.000
        $po = Purchase::create([
            'supplier_id'     => $this->supplier->id,
            'purchase_no'     => 'PO-202610-0501',
            'purchase_date'   => '2026-10-01',
            'status'          => PurchaseStatus::ORDERED,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
        ]);
        PurchaseItem::create([
            'purchase_id'  => $po->id,
            'material_id'  => $this->material->id,
            'qty'          => '100.0000',
            'unit_price'   => '30000.00',
            'qty_received' => '0.0000',
        ]);

        $stockBefore = MaterialStock::where('material_id', $this->material->id)->first();
        $this->assertEquals('100.0000', $stockBefore->qty_on_hand);
        $this->assertEquals(0, bccomp($stockBefore->avg_unit_cost, '25000.00', 2));

        // 12. Validation: amount <= 0 rejected
        $this->actingAs($this->admin)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '0',
            'payment_date'   => '2026-10-02',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('amount');

        // 13. Validation: amount > outstanding (Rp 3.500.000 > Rp 3.000.000) rejected
        $this->actingAs($this->admin)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '3500000.00',
            'payment_date'   => '2026-10-02',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('amount');

        // 15. Partial Payment 1: Rp 1.000.000
        $pay1 = $this->actingAs($this->admin)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '1000000.00',
            'payment_date'   => '2026-10-02',
            'payment_method' => 'TRANSFER_BCA',
            'reference_no'   => 'TRF-PO-01',
            'notes'          => 'DP PO 33%',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ]);
        $pay1->assertRedirect(route('admin.financial-records.index'));

        // 16. Partial Payment 2: Rp 2.000.000 (Pelunasan)
        $pay2 = $this->actingAs($this->admin)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '2000000.00',
            'payment_date'   => '2026-10-05',
            'payment_method' => 'TRANSFER_BCA',
            'reference_no'   => 'TRF-PO-02',
            'notes'          => 'Pelunasan PO',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ]);
        $pay2->assertRedirect(route('admin.financial-records.index'));

        $po->refresh();
        $this->assertEquals('3000000.00', $po->paid_amount);
        $this->assertEquals('0.00', $po->outstanding_amount);
        $this->assertTrue($po->isFullyPaid());

        // 17 & 18. INVARIANT: Supplier payment does NOT touch stock and does NOT alter MWA
        $stockAfter = MaterialStock::where('material_id', $this->material->id)->first();
        $this->assertEquals('100.0000', $stockAfter->qty_on_hand);
        $this->assertEquals(0, bccomp($stockAfter->avg_unit_cost, '25000.00', 2));

        // 20. INVARIANT: No duplicate BusinessExpense created
        $this->assertEquals(0, BusinessExpense::count());

        // 19. Supplier payment appears in Catatan Keuangan as KELUAR
        $service = app(FinancialRecordService::class);
        $result = $service->getConsolidatedRecords();
        $this->assertEquals('3000000.00', $result['summary']['total_keluar']);

        $viewResponse = $this->actingAs($this->admin)->get(route('admin.financial-records.index'));
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('PO #PO-202610-0501');
        $viewResponse->assertSee('Pembayaran Supplier');
        $viewResponse->assertSee('&minus; Rp 1.000.000', false);
        $viewResponse->assertSee('&minus; Rp 2.000.000', false);

        // 14. Cancelled PO payment rejected
        $cancelledPo = Purchase::create([
            'supplier_id'   => $this->supplier->id,
            'purchase_no'   => 'PO-202610-CANCELLED',
            'purchase_date' => '2026-10-03',
            'status'        => PurchaseStatus::CANCELLED,
        ]);
        PurchaseItem::create([
            'purchase_id'  => $cancelledPo->id,
            'material_id'  => $this->material->id,
            'qty'          => '10.0000',
            'unit_price'   => '10000.00',
            'qty_received' => '0.0000',
        ]);

        $cancelPayResponse = $this->actingAs($this->admin)->post(route('admin.purchases.payments.store', $cancelledPo), [
            'amount'         => '500000.00',
            'payment_date'   => '2026-10-03',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ]);
        $cancelPayResponse->assertSessionHasErrors('amount');
    }

    /**
     * 21. Formula: opening + confirmed customer payment - supplier payment - expense = current cash.
     * 22. Pending customer payment excluded.
     * 23. PO creation alone does not reduce cash.
     * 24. Purchase receiving alone does not reduce cash.
     * 25. Cash 10m, PO outstanding 3m -> status CUKUP, projected cash 7m.
     * 26. Cash 2m, PO outstanding 3m -> status TIDAK CUKUP, shortage 1m.
     */
    public function test_cash_sufficiency_and_liquidity_formula(): void
    {
        // Set Opening Cash: 10,000,000
        OpeningCashBalance::create([
            'amount'       => '10000000.00',
            'opening_date' => '2026-10-01',
            'created_by'   => $this->admin->id,
        ]);

        // Create PO 1: Rp 3.000.000 (Ordered, unpaid)
        $po1 = Purchase::create([
            'supplier_id'   => $this->supplier->id,
            'purchase_no'   => 'PO-202610-001',
            'purchase_date' => '2026-10-01',
            'status'        => PurchaseStatus::ORDERED,
        ]);
        PurchaseItem::create([
            'purchase_id'  => $po1->id,
            'material_id'  => $this->material->id,
            'qty'          => '100.0000',
            'unit_price'   => '30000.00', // Rp 3.000.000
            'qty_received' => '0.0000',
        ]);

        // 23. INVARIANT: PO creation alone does NOT reduce cash
        $service = app(FinancialRecordService::class);
        $res1 = $service->getConsolidatedRecords();
        $this->assertEquals('10000000.00', $res1['cash_position']['kas_tersedia']);

        // 25. Sufficiency check: Cash 10m >= PO outstanding 3m -> CUKUP, projected 7m
        $this->assertTrue($res1['procurement_summary']['is_sufficient']);
        $this->assertEquals('CUKUP', $res1['procurement_summary']['sufficiency_status']);
        $this->assertEquals('7000000.00', $res1['procurement_summary']['projected_cash']);
        $this->assertEquals('0.00', $res1['procurement_summary']['shortage']);

        // 26. Scenario with insufficient cash:
        // Add expense Rp 9.000.000 -> Current cash becomes 10m - 9m = 1.000.000
        BusinessExpense::create([
            'expense_date' => '2026-10-02',
            'expense_type' => ExpenseType::OPERATING,
            'amount'       => '9000000.00',
            'description'  => 'Sewa Ruko Tahunan',
            'created_by'   => $this->admin->id,
        ]);

        $res2 = $service->getConsolidatedRecords();
        $this->assertEquals('1000000.00', $res2['cash_position']['kas_tersedia']);

        // PO outstanding is 3m. Cash is 1m.
        // Status: TIDAK CUKUP, shortage = 2m
        $this->assertFalse($res2['procurement_summary']['is_sufficient']);
        $this->assertEquals('TIDAK_CUKUP', $res2['procurement_summary']['sufficiency_status']);
        $this->assertEquals('2000000.00', $res2['procurement_summary']['shortage']);

        // PO procurement sufficiency is displayed on dedicated Pembayaran Supplier page
        $po1->update(['approval_status' => \App\Enums\PurchaseApprovalStatus::APPROVED]);
        $supplierPayResponse = $this->actingAs($this->admin)->get(route('admin.purchases.payments.index'));
        $supplierPayResponse->assertStatus(200);
        $supplierPayResponse->assertSee('Tidak Cukup');
        $supplierPayResponse->assertSee('2.000.000');

        // Catatan Keuangan does not show PO sufficiency warning
        $viewResponse = $this->actingAs($this->admin)->get(route('admin.financial-records.index'));
        $viewResponse->assertStatus(200);
        $viewResponse->assertDontSee('KAS TIDAK CUKUP');
    }

    /**
     * 31. Admin allowed.
     * 32. Gudang cannot see operational cash/customer sales.
     * 33. Produksi forbidden.
     * 34. Customer forbidden.
     */
    public function test_security_authorization_on_tahap_4b_endpoints(): void
    {
        OpeningCashBalance::create([
            'amount'       => '10000000.00',
            'opening_date' => '2026-10-01',
            'created_by'   => $this->admin->id,
        ]);

        $po = Purchase::create([
            'supplier_id'     => $this->supplier->id,
            'purchase_no'     => 'PO-SEC-01',
            'purchase_date'   => '2026-10-01',
            'status'          => PurchaseStatus::ORDERED,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
        ]);
        PurchaseItem::create([
            'purchase_id'  => $po->id,
            'material_id'  => $this->material->id,
            'qty'          => '10.0000',
            'unit_price'   => '10000.00',
            'qty_received' => '0.0000',
        ]);

        // Purchase Payment create page
        $this->actingAs($this->admin)->get(route('admin.purchases.payments.create', $po))->assertStatus(200);
        $this->actingAs($this->gudang)->get(route('admin.purchases.payments.create', $po))->assertStatus(403);
        $this->actingAs($this->produksi)->get(route('admin.purchases.payments.create', $po))->assertStatus(403);
        $this->actingAs($this->customerUser)->get(route('admin.purchases.payments.create', $po))->assertStatus(403);

        // Opening cash edit page
        $this->actingAs($this->admin)->get(route('admin.opening-cash.edit'))->assertStatus(200);
        $this->actingAs($this->gudang)->get(route('admin.opening-cash.edit'))->assertStatus(403);
        $this->actingAs($this->produksi)->get(route('admin.opening-cash.edit'))->assertStatus(403);
        $this->actingAs($this->customerUser)->get(route('admin.opening-cash.edit'))->assertStatus(403);
    }

    /**
     * 24. Purchase receiving alone does not reduce cash.
     * 27. Partial paid PO uses only outstanding in sufficiency check.
     */
    public function test_purchase_receiving_alone_does_not_reduce_cash_and_sufficiency_uses_outstanding(): void
    {
        // Opening cash 10m
        OpeningCashBalance::create([
            'amount'       => '10000000.00',
            'opening_date' => '2026-10-01',
            'created_by'   => $this->admin->id,
        ]);

        // PO Total: 200m * 25.000 = 5.000.000
        $po = Purchase::create([
            'supplier_id'     => $this->supplier->id,
            'purchase_no'     => 'PO-RCV-01',
            'purchase_date'   => '2026-10-01',
            'status'          => PurchaseStatus::ORDERED,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
        ]);
        PurchaseItem::create([
            'purchase_id'  => $po->id,
            'material_id'  => $this->material->id,
            'qty'          => '200.0000',
            'unit_price'   => '25000.00',
            'qty_received' => '0.0000',
        ]);

        // Attempt receive unpaid PO -> must be blocked
        $receivingService = app(\App\Services\Inventory\PurchaseReceivingService::class);
        try {
            $receivingService->receivePurchase($po, $this->gudang->id);
            $this->fail('Unpaid PO receiving should be blocked');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('belum lunas', $e->getMessage());
        }

        // Now pay partial 3,000,000
        $this->actingAs($this->admin)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '3000000.00',
            'payment_date'   => '2026-10-02',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ]);

        $po->refresh();
        $this->assertEquals('3000000.00', $po->paid_amount);
        $this->assertEquals('2000000.00', $po->outstanding_amount);

        // Attempt receive partially paid PO -> must still be blocked
        try {
            $receivingService->receivePurchase($po, $this->gudang->id);
            $this->fail('Partially paid PO receiving should be blocked');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('belum lunas', $e->getMessage());
        }

        // INVARIANT 27: Cash sufficiency uses OUTSTANDING (2m), not total PO (5m)
        // Cash is now 10m - 3m = 7m.
        // Outstanding is 2m.
        // Cash 7m >= Outstanding 2m -> CUKUP, projected = 5m
        $service = app(FinancialRecordService::class);
        $resAfter = $service->getConsolidatedRecords();
        $this->assertEquals('7000000.00', $resAfter['cash_position']['kas_tersedia']);
        $this->assertEquals('2000000.00', $resAfter['procurement_summary']['po_outstanding']);
        $this->assertTrue($resAfter['procurement_summary']['is_sufficient']);
        $this->assertEquals('5000000.00', $resAfter['procurement_summary']['projected_cash']);

        // Now pay remaining 2,000,000
        $this->actingAs($this->admin)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '2000000.00',
            'payment_date'   => '2026-10-02',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ]);

        $po->refresh();
        $this->assertEquals('5000000.00', $po->paid_amount);
        $this->assertEquals('0.00', $po->outstanding_amount);
        $this->assertTrue($po->isFullyPaid());

        // Now receive Purchase via receiving service -> allowed
        $receivingService->receivePurchase($po, $this->gudang->id);

        $po->refresh();
        $this->assertEquals(PurchaseStatus::RECEIVED, $po->status);

        // INVARIANT 24: Receiving PO does NOT reduce cash again
        $resFinal = $service->getConsolidatedRecords();
        $this->assertEquals('5000000.00', $resFinal['cash_position']['kas_tersedia']);
        $this->assertEquals('5000000.00', $resFinal['cash_position']['all_time_keluar']);
    }

    /**
     * 28. HPP summary uses canonical Phase 3 service.
     * 29. HPP unavailable is not Rp0 (shows count of unfinal orders/batches).
     * 30. HPP does not alter cash balance.
     */
    public function test_hpp_modal_summary_and_distinction_from_cash(): void
    {
        // Opening Cash: 5,000,000
        OpeningCashBalance::create([
            'amount'       => '5000000.00',
            'opening_date' => '2026-10-01',
            'created_by'   => $this->admin->id,
        ]);

        // Create deal order without final batch
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-HPP-TEST-01',
            'order_date'  => '2026-10-01',
            'status'      => OrderStatus::DEAL,
        ]);
        OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => '10.0000',
            'unit_selling_price' => '100000.00',
        ]);

        $service = app(FinancialRecordService::class);
        $res = $service->getConsolidatedRecords();

        // 30. HPP does not alter cash balance
        $this->assertEquals('5000000.00', $res['cash_position']['kas_tersedia']);

        // 29. HPP non-final count detected
        $this->assertGreaterThanOrEqual(1, $res['modal_summary']['unready_count']);

        // Catatan Keuangan pure cashflow: HPP summary removed from this page
        $viewResponse = $this->actingAs($this->admin)->get(route('admin.financial-records.index'));
        $viewResponse->assertStatus(200);
        $viewResponse->assertDontSee('HPP Belum Final');
        $viewResponse->assertDontSee('MODAL PRODUKSI');
    }

    /**
     * 35. Public tracking has no finance leakage.
     */
    public function test_public_tracking_has_no_financial_leakage(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-PUBLIC-01',
            'order_date'  => '2026-10-01',
            'status'      => OrderStatus::PRODUKSI,
        ]);

        $tokenService = app(\App\Services\Tracking\TrackingTokenService::class);
        $rawToken = $tokenService->createForOrder($order);

        $response = $this->get("/track/{$rawToken}");
        $response->assertStatus(200);

        // Ensure operational financial fields are NOT exposed to public tracking
        $response->assertDontSee('Kas Tersedia');
        $response->assertDontSee('Saldo Awal');
        $response->assertDontSee('PO Belum Dibayar');
        $response->assertDontSee('Beban Usaha');
        $response->assertDontSee('HPP / Modal Produksi');
    }
}
