<?php

namespace Tests\Feature\Purchase;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PurchaseApprovalStatus;
use App\Enums\PurchaseStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
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
use App\Models\System\User;
use App\Services\Finance\FinancialRecordService;
use App\Services\Inventory\PurchaseApprovalService;
use App\Services\Inventory\PurchaseOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPurchasePaymentQueueTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $ownerUser;
    private User $gudangUser;
    private User $produksiUser;
    private Supplier $supplier;
    private Material $material;
    private Unit $meter;
    private FinancialRecordService $financeService;
    private PurchaseApprovalService $approvalService;
    private PurchaseOrderService $poService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->financeService = app(FinancialRecordService::class);
        $this->approvalService = app(PurchaseApprovalService::class);
        $this->poService = app(PurchaseOrderService::class);

        $this->adminUser = User::factory()->create([
            'name'   => 'Admin Keuangan',
            'role'   => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->ownerUser = User::factory()->create([
            'name'   => 'Owner Nayara',
            'role'   => UserRole::OWNER,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->gudangUser = User::factory()->create([
            'name'   => 'Staf Gudang',
            'role'   => UserRole::GUDANG,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->produksiUser = User::factory()->create([
            'name'   => 'Staf Produksi',
            'role'   => UserRole::PRODUKSI,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->meter = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);

        $this->supplier = Supplier::create([
            'supplier_name' => 'PT Tekstil Utama',
            'phone'         => '08123456789',
            'active'        => true,
        ]);

        $this->material = Material::create([
            'unit_id'       => $this->meter->id,
            'material_name' => 'Kain Katun Rayon',
            'min_stock'     => '10.0000',
            'active'        => true,
        ]);

        MaterialStock::create([
            'material_id'   => $this->material->id,
            'qty_on_hand'   => '100.0000',
            'avg_unit_cost' => '20000.0000',
        ]);

        // Opening cash: 10.000.000
        OpeningCashBalance::create([
            'opening_date' => '2026-10-01',
            'amount'       => '10000000.00',
            'notes'        => 'Saldo Awal Kas',
            'created_by'   => $this->adminUser->id,
        ]);
    }

    private function createDraftPurchase(string $no = 'PO-TEST-001', string $qty = '10.0000', string $price = '50000.00'): Purchase
    {
        $purchase = Purchase::create([
            'supplier_id'     => $this->supplier->id,
            'purchase_no'     => $no,
            'purchase_date'   => '2026-10-09',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::NOT_SUBMITTED,
        ]);

        PurchaseItem::create([
            'purchase_id'  => $purchase->id,
            'material_id'  => $this->material->id,
            'qty'          => $qty,
            'unit_price'   => $price,
            'qty_received' => '0.0000',
        ]);

        return $purchase;
    }

    /**
     * 1. Pending approval PO not shown to Admin payment queue.
     */
    public function test_pending_approval_po_is_not_shown_in_admin_payment_queue(): void
    {
        $purchase = $this->createDraftPurchase();
        $this->approvalService->submitForApproval($purchase, $this->gudangUser);

        $this->assertEquals(PurchaseApprovalStatus::PENDING_APPROVAL, $purchase->fresh()->approval_status);

        $response = $this->actingAs($this->adminUser)->get(route('admin.purchases.payments.index'));
        $response->assertOk();
        $response->assertDontSee($purchase->purchase_no);
        $response->assertSee('Tidak ada PO yang menunggu pembayaran');

        // Catatan Keuangan also does not show PO queue
        $recordsResponse = $this->actingAs($this->adminUser)->get(route('admin.financial-records.index'));
        $recordsResponse->assertOk();
        $recordsResponse->assertDontSee($purchase->purchase_no);
    }

    /**
     * 2. Approved unpaid PO shown in Admin payment queue with required columns.
     */
    public function test_approved_unpaid_po_is_shown_in_admin_payment_queue_with_required_columns(): void
    {
        $purchase = $this->createDraftPurchase();
        $this->approvalService->submitForApproval($purchase, $this->gudangUser);
        $this->approvalService->approvePurchase($purchase, $this->adminUser);

        $this->assertEquals(PurchaseApprovalStatus::APPROVED, $purchase->fresh()->approval_status);

        $response = $this->actingAs($this->adminUser)->get(route('admin.purchases.payments.index'));
        $response->assertOk();
        $response->assertSee('Pembayaran Supplier');
        $response->assertSee($purchase->purchase_no);
        $response->assertSee($this->supplier->supplier_name);
        $response->assertSee('Disetujui');
        $response->assertSee('Bayar');
        $response->assertSee(route('admin.purchases.payments.create', $purchase));
    }

    /**
     * 3. Dashboard count increments after Admin approval.
     */
    public function test_dashboard_count_increments_after_owner_approval(): void
    {
        $purchase = $this->createDraftPurchase();
        $this->approvalService->submitForApproval($purchase, $this->gudangUser);

        // Before approval, pending payment count is 0
        $dashBefore = $this->actingAs($this->adminUser)->get(route('admin.dashboard'));
        $dashBefore->assertOk();
        $dashBefore->assertDontSee('Menunggu Pembayaran');

        // Admin approves
        $this->approvalService->approvePurchase($purchase, $this->adminUser);

        // After approval, appears in Perlu Tindakan queue
        $dashAfter = $this->actingAs($this->adminUser)->get(route('admin.dashboard'));
        $dashAfter->assertOk();
        $dashAfter->assertSee('Menunggu Pembayaran');
        $dashAfter->assertSee($purchase->purchase_no);
        $dashAfter->assertSee($this->supplier->supplier_name);
    }

    /**
     * 4. Approved PO action on dashboard navigates to canonical payment workflow.
     */
    public function test_dashboard_action_points_to_canonical_payment_form(): void
    {
        $purchase = $this->createDraftPurchase();
        $this->approvalService->submitForApproval($purchase, $this->gudangUser);
        $this->approvalService->approvePurchase($purchase, $this->adminUser);

        $response = $this->actingAs($this->adminUser)->get(route('admin.dashboard'));
        $response->assertOk();
        $response->assertSee(route('admin.purchases.payments.create', $purchase));

        // From action queue, opening the payment form works
        $payFormResponse = $this->actingAs($this->adminUser)->get(route('admin.purchases.payments.create', $purchase));
        $payFormResponse->assertOk();
        $payFormResponse->assertSee($purchase->purchase_no);
        $payFormResponse->assertSee('Pembayaran Supplier');
        $payFormResponse->assertSee($this->adminUser->name);
        $payFormResponse->assertDontSee('No. Referensi');
        $payFormResponse->assertDontSee('TRF-20261007-001');
    }

    /**
     * 5. Admin can pay approved PO.
     */
    public function test_admin_can_pay_approved_po(): void
    {
        Storage::fake('local');
        $purchase = $this->createDraftPurchase('PO-PAY-01', '10.0000', '50000.00'); // total 500.000
        $this->approvalService->submitForApproval($purchase, $this->gudangUser);
        $this->approvalService->approvePurchase($purchase, $this->adminUser);

        $response = $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $purchase), [
            'amount'         => '500000.00',
            'payment_date'   => '2026-10-09',
            'payment_method' => 'TRANSFER_BCA',
            'reference_no'   => 'TRF-001',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect(route('admin.financial-records.index'));
        $this->assertDatabaseHas('purchase_payments', [
            'purchase_id' => $purchase->id,
            'amount'      => '500000.00',
        ]);
        $this->assertTrue($purchase->fresh()->isFullyPaid());
        $this->assertEquals(PurchaseStatus::ORDERED, $purchase->fresh()->status);
    }

    /**
     * 6. Admin cannot pay unapproved PO (DRAFT, PENDING, REVISION, REJECTED, CANCELLED).
     */
    public function test_admin_cannot_pay_unapproved_po(): void
    {
        // NOT_SUBMITTED
        $draftPo = $this->createDraftPurchase('PO-UNAPPROVED-01');
        $this->actingAs($this->adminUser)->get(route('admin.purchases.payments.create', $draftPo))
            ->assertRedirect(route('admin.financial-records.index'))
            ->assertSessionHas('error');
        $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $draftPo), [
            'amount'         => '100000.00',
            'payment_date'   => '2026-10-09',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('amount');

        // PENDING_APPROVAL
        $this->approvalService->submitForApproval($draftPo, $this->gudangUser);
        $this->actingAs($this->adminUser)->get(route('admin.purchases.payments.create', $draftPo))
            ->assertRedirect(route('admin.financial-records.index'));
        $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $draftPo), [
            'amount'         => '100000.00',
            'payment_date'   => '2026-10-09',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('amount');

        // REVISION_REQUIRED
        $this->approvalService->requestRevision($draftPo, $this->adminUser, 'Mohon sesuaikan kuantitas.');
        $this->actingAs($this->adminUser)->get(route('admin.purchases.payments.create', $draftPo))
            ->assertRedirect(route('admin.financial-records.index'));

        // REJECTED
        $rejectedPo = $this->createDraftPurchase('PO-REJECT-01');
        $this->approvalService->submitForApproval($rejectedPo, $this->gudangUser);
        $this->approvalService->rejectPurchase($rejectedPo, $this->adminUser, 'Anggaran belum tersedia.');
        $this->actingAs($this->adminUser)->get(route('admin.purchases.payments.create', $rejectedPo))
            ->assertRedirect(route('admin.financial-records.index'));
    }

    /**
     * 7. Partial payment keeps PO in queue with updated outstanding.
     */
    public function test_partial_payment_keeps_po_in_queue(): void
    {
        Storage::fake('local');
        $purchase = $this->createDraftPurchase('PO-PARTIAL-01', '10.0000', '100000.00'); // total 1.000.000
        $this->approvalService->submitForApproval($purchase, $this->gudangUser);
        $this->approvalService->approvePurchase($purchase, $this->adminUser);

        // Pay 600.000
        $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $purchase), [
            'amount'         => '600000.00',
            'payment_date'   => '2026-10-09',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ]);

        $this->assertEquals('400000.00', $purchase->fresh()->outstanding_amount);
        $this->assertEquals('BAYAR SEBAGIAN', $purchase->fresh()->payment_status_label);

        // Dashboard still lists this PO
        $dashResponse = $this->actingAs($this->adminUser)->get(route('admin.dashboard'));
        $dashResponse->assertSee($purchase->purchase_no);
        $dashResponse->assertSee('Dibayar Sebagian');

        // Supplier payment queue still lists this PO with outstanding 400.000
        $financeResponse = $this->actingAs($this->adminUser)->get(route('admin.purchases.payments.index'));
        $financeResponse->assertSee('400.000');
        $financeResponse->assertSee($purchase->purchase_no);
    }

    /**
     * 8. Full payment removes PO from queue and clears dashboard count.
     */
    public function test_full_payment_removes_po_from_queue(): void
    {
        Storage::fake('local');
        $purchase = $this->createDraftPurchase('PO-FULL-01', '10.0000', '100000.00'); // total 1.000.000
        $this->approvalService->submitForApproval($purchase, $this->gudangUser);
        $this->approvalService->approvePurchase($purchase, $this->adminUser);

        // Pay 1.000.000 in full
        $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $purchase), [
            'amount'         => '1000000.00',
            'payment_date'   => '2026-10-09',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ]);

        $this->assertTrue($purchase->fresh()->isFullyPaid());
        $this->assertEquals(PurchaseStatus::ORDERED, $purchase->fresh()->status);

        // Dashboard count becomes 0
        $dashResponse = $this->actingAs($this->adminUser)->get(route('admin.dashboard'));
        $dashResponse->assertDontSee('PO Menunggu Pembayaran');

        // Supplier payment queue removes PO
        $financeResponse = $this->actingAs($this->adminUser)->get(route('admin.purchases.payments.index'));
        $financeResponse->assertDontSee($purchase->purchase_no);
        $financeResponse->assertSee('Tidak ada PO yang menunggu pembayaran');
    }

    /**
     * 9, 10, 11. Payment reduces cash, does not change stock, does not change MWA.
     */
    public function test_payment_reduces_cash_without_mutating_stock_or_mwa(): void
    {
        Storage::fake('local');
        $purchase = $this->createDraftPurchase('PO-CASH-01', '10.0000', '100000.00'); // total 1.000.000
        $this->approvalService->submitForApproval($purchase, $this->gudangUser);
        $this->approvalService->approvePurchase($purchase, $this->adminUser);

        $stockBefore = MaterialStock::where('material_id', $this->material->id)->first();
        $qtyBefore = $stockBefore->qty_on_hand;
        $mwaBefore = $stockBefore->avg_unit_cost;

        $openingRecord = OpeningCashBalance::latest('opening_date')->latest('id')->first();
        $cashBefore = $this->financeService->calculateCurrentCash($openingRecord);
        $this->assertEquals('10000000.00', $cashBefore);

        // Pay 1.000.000
        $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $purchase), [
            'amount'         => '1000000.00',
            'payment_date'   => '2026-10-09',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ]);

        // Cash reduced to 9.000.000
        $cashAfter = $this->financeService->calculateCurrentCash($openingRecord);
        $this->assertEquals('9000000.00', $cashAfter);

        // Stock and MWA are unchanged
        $stockAfter = MaterialStock::where('material_id', $this->material->id)->first();
        $this->assertEquals($qtyBefore, $stockAfter->qty_on_hand);
        $this->assertEquals($mwaBefore, $stockAfter->avg_unit_cost);

        // No stock movement created for payment
        $this->assertDatabaseMissing('stock_movements', [
            'reference_type' => 'purchase_payments',
        ]);
    }

    /**
     * 12, 13. Gudang sees payment status, cannot see company cash.
     */
    public function test_gudang_sees_payment_status_without_exposing_company_cash(): void
    {
        $purchase = $this->createDraftPurchase('PO-GUDANG-01', '10.0000', '50000.00');
        $this->approvalService->submitForApproval($purchase, $this->gudangUser);
        $this->approvalService->approvePurchase($purchase, $this->adminUser);

        // Gudang views PO show page
        $response = $this->actingAs($this->gudangUser)->get(route('gudang.purchases.show', $purchase));
        $response->assertOk();
        $response->assertSee('BELUM DIBAYAR');
        $response->assertSee('Disetujui');

        // Privacy: Gudang must NOT see company cash or liquidity calculations
        $response->assertDontSee('Kas Tersedia');
        $response->assertDontSee('Kas Tersedia Sekarang');
        $response->assertDontSee('available_to_commit');
        $response->assertDontSee('Catat Pembayaran Supplier');
    }

    /**
     * 14, 15. Partial payment does not order; full payment automatically sets PO to ORDERED.
     */
    public function test_gudang_can_only_confirm_supplier_after_fully_paid(): void
    {
        Storage::fake('local');
        $purchase = $this->createDraftPurchase('PO-CONFIRM-01', '10.0000', '50000.00'); // 500.000
        $this->approvalService->submitForApproval($purchase, $this->gudangUser);
        $this->approvalService->approvePurchase($purchase, $this->adminUser);

        // Approved but unpaid: cannot confirm via old method and status remains DRAFT
        $this->assertFalse($this->poService->canConfirm($purchase->fresh()));
        $this->assertEquals(PurchaseStatus::DRAFT, $purchase->fresh()->status);

        // Partial payment (200.000): status remains DRAFT
        $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $purchase), [
            'amount'         => '200000.00',
            'payment_date'   => '2026-10-09',
            'transfer_proof' => UploadedFile::fake()->create('proof1.pdf', 100, 'application/pdf'),
        ]);
        $this->assertFalse($purchase->fresh()->isFullyPaid());
        $this->assertEquals(PurchaseStatus::DRAFT, $purchase->fresh()->status);

        // Pay remaining (300.000): now fully paid -> automatically ORDERED
        $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $purchase), [
            'amount'         => '300000.00',
            'payment_date'   => '2026-10-09',
            'transfer_proof' => UploadedFile::fake()->create('proof2.pdf', 100, 'application/pdf'),
        ]);
        $this->assertTrue($purchase->fresh()->isFullyPaid());
        $this->assertEquals(PurchaseStatus::ORDERED, $purchase->fresh()->status);
    }

    /**
     * 16. Owner cannot perform supplier payment.
     */
    public function test_owner_cannot_perform_supplier_payment(): void
    {
        $purchase = $this->createDraftPurchase('PO-OWNER-PAY-01');
        $this->approvalService->submitForApproval($purchase, $this->gudangUser);
        $this->approvalService->approvePurchase($purchase, $this->adminUser);

        $this->actingAs($this->ownerUser)->get(route('admin.purchases.payments.create', $purchase))
            ->assertForbidden();

        $this->actingAs($this->ownerUser)->post(route('admin.purchases.payments.store', $purchase), [
            'amount'       => '100000.00',
            'payment_date' => '2026-10-09',
        ])->assertForbidden();
    }

    /**
     * 17. Produksi/customer cannot access payment workflow.
     */
    public function test_produksi_cannot_access_payment_workflow(): void
    {
        $purchase = $this->createDraftPurchase('PO-PRODUKSI-01');
        $this->approvalService->submitForApproval($purchase, $this->gudangUser);
        $this->approvalService->approvePurchase($purchase, $this->adminUser);

        $this->actingAs($this->produksiUser)->get(route('admin.purchases.payments.create', $purchase))
            ->assertForbidden();

        $this->actingAs($this->produksiUser)->post(route('admin.purchases.payments.store', $purchase), [
            'amount'       => '100000.00',
            'payment_date' => '2026-10-09',
        ])->assertForbidden();
    }

    /**
     * 18. Customer invoice "Belum Lunas" metrics remain unchanged.
     */
    public function test_customer_invoice_unsettled_metrics_remain_unchanged(): void
    {
        $customer = Customer::create([
            'customer_name' => 'Butik Cantik',
            'phone'         => '08999999',
            'active'        => true,
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'order_no'    => 'SO-TEST-001',
            'order_date'  => '2026-10-09',
            'status'      => OrderStatus::DEAL,
        ]);

        $invoice = Invoice::create([
            'order_id'       => $order->id,
            'invoice_no'     => 'INV-TEST-001',
            'invoice_date'   => '2026-10-09',
            'total_amount'   => '3500000.00',
            'paid_amount'    => '0.00',
            'status'         => InvoiceStatus::UNPAID,
        ]);

        // Create an approved PO
        $purchase = $this->createDraftPurchase('PO-DISTINCT-01');
        $this->approvalService->submitForApproval($purchase, $this->gudangUser);
        $this->approvalService->approvePurchase($purchase, $this->adminUser);

        $response = $this->actingAs($this->adminUser)->get(route('admin.dashboard'));
        $response->assertOk();

        // Customer invoice card says: "Belum Lunas"
        $response->assertSee('Belum Lunas');
        $response->assertSee('Unpaid & Partial', false);

        // Supplier PO shows in Perlu Tindakan
        $response->assertSee('Menunggu Pembayaran');
        $response->assertSee($purchase->purchase_no);
    }
}
