<?php

namespace Tests\Feature\Purchase;

use App\Enums\PurchaseApprovalStatus;
use App\Enums\PurchaseStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Finance\OpeningCashBalance;
use App\Models\Inventory\MaterialStock;
use App\Models\Inventory\Purchase;
use App\Models\Inventory\PurchaseItem;
use App\Models\Inventory\PurchasePayment;
use App\Models\Inventory\StockMovement;
use App\Models\Master\Material;
use App\Models\Master\Supplier;
use App\Models\Master\Unit;
use App\Models\System\User;
use App\Services\Finance\FinancialRecordService;
use App\Services\Inventory\PurchaseApprovalService;
use App\Services\Inventory\PurchaseOrderService;
use App\Services\Inventory\PurchaseReceivingService;
use App\Services\System\DashboardActionService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PurchaseFinalWorkflowRevisionTest extends TestCase
{
    use RefreshDatabase;

    private User $gudangUser;
    private User $adminUser;
    private User $ownerUser;
    private User $produksiUser;
    private User $customerUser;
    private Supplier $supplier;
    private Material $material;
    private Unit $meter;

    private PurchaseOrderService $poService;
    private PurchaseApprovalService $approvalService;
    private PurchaseReceivingService $receivingService;
    private FinancialRecordService $financeService;
    private DashboardActionService $dashboardActionService;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->poService = app(PurchaseOrderService::class);
        $this->approvalService = app(PurchaseApprovalService::class);
        $this->receivingService = app(PurchaseReceivingService::class);
        $this->financeService = app(FinancialRecordService::class);
        $this->dashboardActionService = app(DashboardActionService::class);

        $this->gudangUser = User::factory()->create([
            'name'   => 'Staf Gudang',
            'role'   => UserRole::GUDANG,
            'status' => UserStatus::ACTIVE,
        ]);

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

        $this->produksiUser = User::factory()->create([
            'name'   => 'Staf Produksi',
            'role'   => UserRole::PRODUKSI,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->customerUser = User::factory()->create([
            'name'   => 'Customer Retail',
            'role'   => UserRole::CUSTOMER,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->meter = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);

        $this->supplier = Supplier::create([
            'supplier_name' => 'PT Tekstil Mandiri',
            'phone'         => '081234567890',
            'active'        => true,
        ]);

        $this->material = Material::create([
            'unit_id'       => $this->meter->id,
            'material_name' => 'Kain Toyobo Premium',
            'min_stock'     => '10.0000',
            'active'        => true,
        ]);

        MaterialStock::create([
            'material_id'   => $this->material->id,
            'qty_on_hand'   => '100.0000',
            'avg_unit_cost' => '30000.0000',
        ]);

        $this->supplier->materials()->attach($this->material->id);

        OpeningCashBalance::create([
            'opening_date' => '2026-10-01',
            'amount'       => '20000000.00',
            'notes'        => 'Kas Awal Operasional',
            'created_by'   => $this->adminUser->id,
        ]);
    }

    private function createSamplePurchase(string $qty = '50.0000', string $unitPrice = '40000.00'): Purchase
    {
        return $this->poService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-10-15',
            'notes'         => 'Pengadaan material kain untuk batch baru',
            'items'         => [
                [
                    'material_id' => $this->material->id,
                    'qty'         => $qty,
                    'unit_price'  => $unitPrice,
                ],
            ],
        ], $this->gudangUser);
    }

    // =========================================================================
    // SECTION 29: TESTS — SUBMISSION / APPROVAL (1 - 9)
    // =========================================================================

    /**
     * 1. Gudang can create DRAFT PO.
     */
    public function test_01_gudang_can_create_draft_po(): void
    {
        $response = $this->actingAs($this->gudangUser)->post(route('gudang.purchases.store'), [
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-10-15',
            'notes'         => 'Draft baru',
            'items'         => [
                ['material_id' => $this->material->id, 'qty' => '50', 'unit_price' => '40000'],
            ],
        ]);

        $po = Purchase::latest('id')->first();
        $this->assertNotNull($po);
        $response->assertRedirect(route('gudang.purchases.show', $po));
        $this->assertEquals(PurchaseStatus::DRAFT, $po->status);
        $this->assertEquals(PurchaseApprovalStatus::NOT_SUBMITTED, $po->approval_status);
        $this->assertEquals('2000000.00', $po->total_amount_string);
    }

    /**
     * 2. Gudang can submit PO to Admin.
     */
    public function test_02_gudang_can_submit_po_to_admin(): void
    {
        $po = $this->createSamplePurchase();

        $response = $this->actingAs($this->gudangUser)->post(route('gudang.purchases.submit-approval', $po), [
            'submission_notes' => 'Mohon disetujui Admin untuk pengadaan',
        ]);

        $response->assertRedirect(route('gudang.purchases.show', $po));
        $response->assertSessionHas('success');

        $fresh = $po->fresh();
        $this->assertEquals(PurchaseApprovalStatus::PENDING_APPROVAL, $fresh->approval_status);
        $this->assertNotNull($fresh->submitted_at);
        $this->assertEquals($this->gudangUser->id, $fresh->submitted_by);
    }

    /**
     * 3. Submission no longer targets Owner.
     */
    public function test_03_submission_no_longer_targets_owner(): void
    {
        $po = $this->createSamplePurchase();
        $this->approvalService->submitForApproval($po, $this->gudangUser);

        // Owner action queue is empty
        $ownerActions = $this->dashboardActionService->getOwnerActions();
        $this->assertEmpty($ownerActions);

        // Gudang UI indicates submission to Admin
        $response = $this->actingAs($this->gudangUser)->get(route('gudang.purchases.show', $po));
        $response->assertOk();
        $response->assertSee('Menunggu Persetujuan Admin');
        $response->assertDontSee('Menunggu Persetujuan Owner');
    }

    /**
     * 4. Admin can approve PO.
     */
    public function test_04_admin_can_approve_po(): void
    {
        $po = $this->createSamplePurchase();
        $this->approvalService->submitForApproval($po, $this->gudangUser);

        $response = $this->actingAs($this->adminUser)->post(route('admin.purchases.approvals.approve', $po), [
            'review_note' => 'Disetujui oleh Admin Keuangan',
        ]);

        $response->assertRedirect(route('admin.purchases.approvals.show', $po));
        $response->assertSessionHas('success');

        $fresh = $po->fresh();
        $this->assertEquals(PurchaseApprovalStatus::APPROVED, $fresh->approval_status);
        $this->assertNotNull($fresh->approved_at);
        $this->assertEquals($this->adminUser->id, $fresh->approved_by);
    }

    /**
     * 5. Admin can request revision.
     */
    public function test_05_admin_can_request_revision(): void
    {
        $po = $this->createSamplePurchase();
        $this->approvalService->submitForApproval($po, $this->gudangUser);

        $response = $this->actingAs($this->adminUser)->post(route('admin.purchases.approvals.request-revision', $po), [
            'review_note' => 'Mohon negosiasi harga satuan dengan supplier.',
        ]);

        $response->assertRedirect(route('admin.purchases.approvals.index'));
        $response->assertSessionHas('success');

        $fresh = $po->fresh();
        $this->assertEquals(PurchaseApprovalStatus::REVISION_REQUIRED, $fresh->approval_status);
        $this->assertEquals('Mohon negosiasi harga satuan dengan supplier.', $fresh->review_note);
    }

    /**
     * 6. Admin can reject PO.
     */
    public function test_06_admin_can_reject_po(): void
    {
        $po = $this->createSamplePurchase();
        $this->approvalService->submitForApproval($po, $this->gudangUser);

        $response = $this->actingAs($this->adminUser)->post(route('admin.purchases.approvals.reject', $po), [
            'reason' => 'Pengadaan ditolak karena stok masih cukup.',
        ]);

        $response->assertRedirect(route('admin.purchases.approvals.index'));
        $response->assertSessionHas('success');

        $fresh = $po->fresh();
        $this->assertEquals(PurchaseApprovalStatus::REJECTED, $fresh->approval_status);
    }

    /**
     * 7. Gudang cannot approve PO.
     */
    public function test_07_gudang_cannot_approve_po(): void
    {
        $po = $this->createSamplePurchase();
        $this->approvalService->submitForApproval($po, $this->gudangUser);

        // HTTP level: 403
        $this->actingAs($this->gudangUser)
            ->post(route('admin.purchases.approvals.approve', $po))
            ->assertStatus(403);

        // Service level: DomainException
        $this->expectException(DomainException::class);
        $this->approvalService->approvePurchase($po, $this->gudangUser);
    }

    /**
     * 8. Owner cannot approve PO.
     */
    public function test_08_owner_cannot_approve_po(): void
    {
        $po = $this->createSamplePurchase();
        $this->approvalService->submitForApproval($po, $this->gudangUser);

        // HTTP level: 403
        $this->actingAs($this->ownerUser)
            ->post(route('admin.purchases.approvals.approve', $po))
            ->assertStatus(403);

        // Service level: DomainException
        $this->expectException(DomainException::class);
        $this->approvalService->approvePurchase($po, $this->ownerUser);
    }

    /**
     * 9. Produksi/Customer cannot approve PO.
     */
    public function test_09_produksi_and_customer_cannot_approve_po(): void
    {
        $po = $this->createSamplePurchase();
        $this->approvalService->submitForApproval($po, $this->gudangUser);

        $this->actingAs($this->produksiUser)
            ->post(route('admin.purchases.approvals.approve', $po))
            ->assertStatus(403);

        $this->actingAs($this->customerUser)
            ->post(route('admin.purchases.approvals.approve', $po))
            ->assertStatus(403);
    }

    // =========================================================================
    // SECTION 30: TESTS — PAYMENT (10 - 21)
    // =========================================================================

    /**
     * 10. Admin can pay approved PO.
     */
    public function test_10_admin_can_pay_approved_po(): void
    {
        $po = $this->createSamplePurchase(); // 2.000.000
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->approvePurchase($po, $this->adminUser);

        $file = UploadedFile::fake()->create('bukti_trf.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '1000000.00',
            'payment_date'   => '2026-10-15',
            'payment_method' => 'TRANSFER_BCA',
            'reference_no'   => 'TRF-PAY-01',
            'transfer_proof' => $file,
        ]);

        $response->assertRedirect(route('admin.financial-records.index'));
        $this->assertDatabaseHas('purchase_payments', [
            'purchase_id' => $po->id,
            'amount'      => '1000000.00',
        ]);
    }

    /**
     * 11. Non-approved PO cannot be paid.
     */
    public function test_11_non_approved_po_cannot_be_paid(): void
    {
        $po = $this->createSamplePurchase();
        $file = UploadedFile::fake()->create('bukti_trf.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '500000.00',
            'payment_date'   => '2026-10-15',
            'transfer_proof' => $file,
        ]);

        $response->assertSessionHasErrors('amount');
    }

    /**
     * 12. Payment above outstanding blocked.
     */
    public function test_12_payment_above_outstanding_blocked(): void
    {
        $po = $this->createSamplePurchase(); // total 2.000.000
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->approvePurchase($po, $this->adminUser);

        $file = UploadedFile::fake()->create('bukti_trf.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '2500000.00', // exceeds 2.000.000
            'payment_date'   => '2026-10-15',
            'transfer_proof' => $file,
        ]);

        $response->assertSessionHasErrors('amount');
    }

    /**
     * 13. Payment above current cash blocked.
     */
    public function test_13_payment_above_current_cash_blocked(): void
    {
        // Set cash to only 500.000
        OpeningCashBalance::truncate();
        OpeningCashBalance::create([
            'opening_date' => '2026-10-01',
            'amount'       => '500000.00',
            'notes'        => 'Kas Minim',
            'created_by'   => $this->adminUser->id,
        ]);

        $po = $this->createSamplePurchase(); // total 2.000.000
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $po->update(['approval_status' => PurchaseApprovalStatus::APPROVED]);

        $file = UploadedFile::fake()->create('bukti_trf.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '1000000.00', // 1jt > 500rb cash
            'payment_date'   => '2026-10-15',
            'transfer_proof' => $file,
        ]);

        $response->assertSessionHasErrors('amount');
    }

    /**
     * 14. Partial payment supported.
     */
    public function test_14_partial_payment_supported(): void
    {
        $po = $this->createSamplePurchase(); // 2.000.000
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->approvePurchase($po, $this->adminUser);

        $file = UploadedFile::fake()->create('bukti_part.jpg', 300, 'image/jpeg');

        $response = $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '800000.00',
            'payment_date'   => '2026-10-15',
            'transfer_proof' => $file,
        ]);

        $response->assertRedirect(route('admin.financial-records.index'));
        $fresh = $po->fresh();
        $this->assertEquals('800000.00', $fresh->paid_amount);
        $this->assertEquals('1200000.00', $fresh->outstanding_amount);
        $this->assertFalse($fresh->isFullyPaid());
        $this->assertEquals(PurchaseStatus::DRAFT, $fresh->status);
    }

    /**
     * 15. Transfer proof required for new payment.
     */
    public function test_15_transfer_proof_required_for_new_payment(): void
    {
        $po = $this->createSamplePurchase();
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->approvePurchase($po, $this->adminUser);

        $response = $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $po), [
            'amount'       => '500000.00',
            'payment_date' => '2026-10-15',
            // missing transfer_proof
        ]);

        $response->assertSessionHasErrors('transfer_proof');
    }

    /**
     * 16. Valid JPG accepted.
     */
    public function test_16_valid_jpg_accepted(): void
    {
        $po = $this->createSamplePurchase();
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->approvePurchase($po, $this->adminUser);

        $file = UploadedFile::fake()->create('receipt.jpg', 400, 'image/jpeg');

        $response = $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '500000.00',
            'payment_date'   => '2026-10-15',
            'transfer_proof' => $file,
        ]);

        $response->assertSessionHasNoErrors();
        $payment = PurchasePayment::where('purchase_id', $po->id)->first();
        $this->assertNotNull($payment->transfer_proof_path);
        Storage::disk('local')->assertExists($payment->transfer_proof_path);
    }

    /**
     * 17. Valid PNG accepted.
     */
    public function test_17_valid_png_accepted(): void
    {
        $po = $this->createSamplePurchase();
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->approvePurchase($po, $this->adminUser);

        $file = UploadedFile::fake()->create('receipt.png', 400, 'image/png');

        $response = $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '500000.00',
            'payment_date'   => '2026-10-15',
            'transfer_proof' => $file,
        ]);

        $response->assertSessionHasNoErrors();
        $payment = PurchasePayment::where('purchase_id', $po->id)->first();
        $this->assertNotNull($payment->transfer_proof_path);
        Storage::disk('local')->assertExists($payment->transfer_proof_path);
    }

    /**
     * 18. Valid PDF accepted.
     */
    public function test_18_valid_pdf_accepted(): void
    {
        $po = $this->createSamplePurchase();
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->approvePurchase($po, $this->adminUser);

        $file = UploadedFile::fake()->create('receipt.pdf', 600, 'application/pdf');

        $response = $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '500000.00',
            'payment_date'   => '2026-10-15',
            'transfer_proof' => $file,
        ]);

        $response->assertSessionHasNoErrors();
        $payment = PurchasePayment::where('purchase_id', $po->id)->first();
        $this->assertNotNull($payment->transfer_proof_path);
        Storage::disk('local')->assertExists($payment->transfer_proof_path);
    }

    /**
     * 19. Invalid file rejected.
     */
    public function test_19_invalid_file_rejected(): void
    {
        $po = $this->createSamplePurchase();
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->approvePurchase($po, $this->adminUser);

        $exeFile = UploadedFile::fake()->create('malicious.exe', 300, 'application/x-msdownload');

        $response = $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '500000.00',
            'payment_date'   => '2026-10-15',
            'transfer_proof' => $exeFile,
        ]);

        $response->assertSessionHasErrors('transfer_proof');
    }

    /**
     * 20. File path stored safely.
     */
    public function test_20_file_path_stored_safely(): void
    {
        $po = $this->createSamplePurchase();
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->approvePurchase($po, $this->adminUser);

        $file = UploadedFile::fake()->create('invoice_proof.pdf', 400, 'application/pdf');

        $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '500000.00',
            'payment_date'   => '2026-10-15',
            'transfer_proof' => $file,
        ]);

        $payment = PurchasePayment::where('purchase_id', $po->id)->first();
        $this->assertNotNull($payment->transfer_proof_path);
        $this->assertStringStartsWith('transfer-proofs/', $payment->transfer_proof_path);
        $this->assertStringNotContainsString('invoice_proof.pdf', $payment->transfer_proof_path);
    }

    /**
     * 21. Historical payment without proof remains readable.
     */
    public function test_21_historical_payment_without_proof_remains_readable(): void
    {
        $po = $this->createSamplePurchase();
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->approvePurchase($po, $this->adminUser);

        $historicalPayment = PurchasePayment::create([
            'purchase_id'         => $po->id,
            'payment_date'        => '2026-09-01',
            'amount'              => '500000.00',
            'payment_method'      => 'CASH',
            'transfer_proof_path' => null,
            'created_by'          => $this->adminUser->id,
        ]);

        $this->assertFalse($historicalPayment->hasTransferProof());
        $this->assertNull($historicalPayment->transfer_proof_url);

        $response = $this->actingAs($this->adminUser)->get(route('admin.financial-records.index'));
        $response->assertOk();
        $response->assertSee('Rp 500.000');
    }

    // =========================================================================
    // SECTION 31: TESTS — AUTO ORDERED (22 - 29)
    // =========================================================================

    /**
     * 22. Partial payment does NOT set ORDERED.
     */
    public function test_22_partial_payment_does_not_set_ordered(): void
    {
        $po = $this->createSamplePurchase('10.0000', '100000.00'); // total 1.000.000
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->approvePurchase($po, $this->adminUser);

        $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '600000.00',
            'payment_date'   => '2026-10-15',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 300, 'application/pdf'),
        ]);

        $this->assertEquals(PurchaseStatus::DRAFT, $po->fresh()->status);
        $this->assertFalse($po->fresh()->isFullyPaid());
    }

    /**
     * 23. Full cumulative payment sets ORDERED automatically.
     */
    public function test_23_full_cumulative_payment_sets_ordered_automatically(): void
    {
        $po = $this->createSamplePurchase('10.0000', '100000.00'); // total 1.000.000
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->approvePurchase($po, $this->adminUser);

        // Payment 1: 600.000
        $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '600000.00',
            'payment_date'   => '2026-10-15',
            'transfer_proof' => UploadedFile::fake()->create('proof1.pdf', 300, 'application/pdf'),
        ]);
        $this->assertEquals(PurchaseStatus::DRAFT, $po->fresh()->status);

        // Payment 2: 400.000 (completes payment)
        $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '400000.00',
            'payment_date'   => '2026-10-15',
            'transfer_proof' => UploadedFile::fake()->create('proof2.pdf', 300, 'application/pdf'),
        ]);

        $this->assertTrue($po->fresh()->isFullyPaid());
        $this->assertEquals(PurchaseStatus::ORDERED, $po->fresh()->status);
    }

    /**
     * 24. Payment status becomes LUNAS.
     */
    public function test_24_payment_status_becomes_lunas(): void
    {
        $po = $this->createSamplePurchase('10.0000', '100000.00');
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->approvePurchase($po, $this->adminUser);

        $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '1000000.00',
            'payment_date'   => '2026-10-15',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 300, 'application/pdf'),
        ]);

        $fresh = $po->fresh();
        $this->assertEquals('LUNAS', $fresh->payment_status_label);
        $this->assertEquals('0.00', $fresh->outstanding_amount);
    }

    /**
     * 25. No Gudang confirmation required.
     */
    public function test_25_no_gudang_confirmation_required(): void
    {
        $po = $this->createSamplePurchase('10.0000', '100000.00');
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->approvePurchase($po, $this->adminUser);

        $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '1000000.00',
            'payment_date'   => '2026-10-15',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 300, 'application/pdf'),
        ]);

        // PO is already ORDERED without Gudang confirmation
        $this->assertEquals(PurchaseStatus::ORDERED, $po->fresh()->status);
    }

    /**
     * 26. Old confirm-to-supplier action unavailable.
     */
    public function test_26_old_confirm_to_supplier_action_unavailable(): void
    {
        $this->assertFalse(Route::has('gudang.purchases.confirm'));

        $po = $this->createSamplePurchase();
        $this->assertFalse($this->poService->canConfirm($po));

        $this->expectException(DomainException::class);
        $this->poService->confirmPurchase($po, $this->gudangUser);
    }

    /**
     * 27. Admin payment queue clears after full payment.
     */
    public function test_27_admin_payment_queue_clears_after_full_payment(): void
    {
        $po = $this->createSamplePurchase('10.0000', '100000.00');
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->approvePurchase($po, $this->adminUser);

        $responseBefore = $this->actingAs($this->adminUser)->get(route('admin.purchases.payments.index'));
        $responseBefore->assertSee($po->purchase_no);

        $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '1000000.00',
            'payment_date'   => '2026-10-15',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 300, 'application/pdf'),
        ]);

        $this->flushSession();
        $responseAfter = $this->actingAs($this->adminUser)->get(route('admin.purchases.payments.index'));
        $responseAfter->assertDontSee($po->purchase_no);
        $responseAfter->assertSee('Tidak ada PO yang menunggu pembayaran');
    }

    /**
     * 28. Gudang sees ORDERED immediately.
     */
    public function test_28_gudang_sees_ordered_immediately(): void
    {
        $po = $this->createSamplePurchase('10.0000', '100000.00');
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->approvePurchase($po, $this->adminUser);

        $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '1000000.00',
            'payment_date'   => '2026-10-15',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 300, 'application/pdf'),
        ]);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.purchases.show', $po));
        $response->assertOk();
        $response->assertSee('Dipesan');
        $response->assertSee('Terima Barang');
        $response->assertDontSee('Konfirmasi ke Supplier');
    }

    /**
     * 29. Gudang can proceed to receive according to existing workflow.
     */
    public function test_29_gudang_can_proceed_to_receive(): void
    {
        $po = $this->createSamplePurchase('10.0000', '100000.00');
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->approvePurchase($po, $this->adminUser);

        $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '1000000.00',
            'payment_date'   => '2026-10-15',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 300, 'application/pdf'),
        ]);

        $response = $this->actingAs($this->gudangUser)->post(route('gudang.purchases.receive', $po));
        $response->assertRedirect(route('gudang.purchases.show', $po));

        $this->assertEquals(PurchaseStatus::RECEIVED, $po->fresh()->status);
        $this->assertEquals('10.0000', $po->items->first()->fresh()->qty_received);
    }

    // =========================================================================
    // SECTION 32: TESTS — STOCK (30 - 35)
    // =========================================================================

    /**
     * 30. Approval does not change stock.
     */
    public function test_30_approval_does_not_change_stock(): void
    {
        $initialStock = $this->material->fresh(['stock'])->stock->qty_on_hand;
        $po = $this->createSamplePurchase('50.0000', '40000.00');
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->approvePurchase($po, $this->adminUser);

        $this->assertEquals($initialStock, $this->material->fresh(['stock'])->stock->qty_on_hand);
        $this->assertEquals(0, StockMovement::count());
    }

    /**
     * 31. Partial payment does not change stock.
     */
    public function test_31_partial_payment_does_not_change_stock(): void
    {
        $initialStock = $this->material->fresh(['stock'])->stock->qty_on_hand;
        $po = $this->createSamplePurchase('50.0000', '40000.00');
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->approvePurchase($po, $this->adminUser);

        $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '1000000.00',
            'payment_date'   => '2026-10-15',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 300, 'application/pdf'),
        ]);

        $this->assertEquals($initialStock, $this->material->fresh(['stock'])->stock->qty_on_hand);
        $this->assertEquals(0, StockMovement::count());
    }

    /**
     * 32. Full payment / ORDERED does not change stock.
     */
    public function test_32_full_payment_ordered_does_not_change_stock(): void
    {
        $initialStock = $this->material->fresh(['stock'])->stock->qty_on_hand;
        $po = $this->createSamplePurchase('50.0000', '40000.00');
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->approvePurchase($po, $this->adminUser);

        $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '2000000.00',
            'payment_date'   => '2026-10-15',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 300, 'application/pdf'),
        ]);

        $this->assertEquals(PurchaseStatus::ORDERED, $po->fresh()->status);
        $this->assertEquals($initialStock, $this->material->fresh(['stock'])->stock->qty_on_hand);
        $this->assertEquals(0, StockMovement::count());
    }

    /**
     * 33. Receiving still creates StockMovement IN exactly once.
     */
    public function test_33_receiving_creates_stock_movement_in_exactly_once(): void
    {
        $po = $this->createSamplePurchase('50.0000', '40000.00');
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->approvePurchase($po, $this->adminUser);

        $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '2000000.00',
            'payment_date'   => '2026-10-15',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 300, 'application/pdf'),
        ]);

        $this->receivingService->receivePurchase($po->fresh(), $this->gudangUser->id);

        $this->assertEquals(1, StockMovement::count());
        $movement = StockMovement::first();
        $this->assertEquals('50.0000', $movement->qty);
        $this->assertEquals('150.0000', $this->material->fresh(['stock'])->stock->qty_on_hand);
    }

    /**
     * 34. MWA remains calculated at receiving only.
     */
    public function test_34_mwa_calculated_at_receiving_only(): void
    {
        // Initial stock: 100 m @ 30.000 = 3.000.000
        // New: 50 m @ 45.000 = 2.250.000
        // Total: 150 m, Value: 5.250.000 -> MWA = 35.000
        $po = $this->createSamplePurchase('50.0000', '45000.00');
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->approvePurchase($po, $this->adminUser);

        $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => $po->total_amount,
            'payment_date'   => '2026-10-15',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 300, 'application/pdf'),
        ]);

        // MWA unchanged before receiving
        $this->assertEquals('30000.0000', $this->material->fresh(['stock'])->stock->avg_unit_cost);

        $this->receivingService->receivePurchase($po->fresh(), $this->gudangUser->id);

        // MWA calculated after receiving
        $this->assertEquals('35000.0000', $this->material->fresh(['stock'])->stock->avg_unit_cost);
    }

    /**
     * 35. Double receiving protection remains.
     */
    public function test_35_double_receiving_protection_remains(): void
    {
        $po = $this->createSamplePurchase('50.0000', '40000.00');
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->approvePurchase($po, $this->adminUser);

        $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '2000000.00',
            'payment_date'   => '2026-10-15',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 300, 'application/pdf'),
        ]);

        // First receive succeeds
        $this->receivingService->receivePurchase($po->fresh(), $this->gudangUser->id);
        $this->assertEquals(PurchaseStatus::RECEIVED, $po->fresh()->status);

        // Second receive throws DomainException
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Hanya Purchase Order berstatus ORDERED yang dapat diterima');
        $this->receivingService->receivePurchase($po->fresh(), $this->gudangUser->id);
    }

    // =========================================================================
    // SECTION 33: TESTS — DASHBOARDS (36 - 42)
    // =========================================================================

    /**
     * 36. Gudang pending Admin approval state shown correctly.
     */
    public function test_36_gudang_pending_admin_approval_state_shown(): void
    {
        $po = $this->createSamplePurchase();
        $this->approvalService->submitForApproval($po, $this->gudangUser);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.dashboard'));
        $response->assertOk();
        $response->assertSee($po->purchase_no);
        $response->assertSee('Menunggu Persetujuan Admin');
    }

    /**
     * 37. Gudang approved-unpaid state shown.
     */
    public function test_37_gudang_approved_unpaid_state_shown(): void
    {
        $po = $this->createSamplePurchase();
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->approvePurchase($po, $this->adminUser);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.dashboard'));
        $response->assertOk();
        $response->assertSee($po->purchase_no);
        $response->assertSee('Menunggu Pembayaran Admin');
    }

    /**
     * 38. Gudang ordered state shown.
     */
    public function test_38_gudang_ordered_state_shown(): void
    {
        $po = $this->createSamplePurchase();
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->approvePurchase($po, $this->adminUser);

        $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => $po->total_amount,
            'payment_date'   => '2026-10-15',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 300, 'application/pdf'),
        ]);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.dashboard'));
        $response->assertOk();
        $response->assertSee($po->purchase_no);
        $response->assertSee('Menunggu Barang');
        $response->assertSee('Terima Barang');
    }

    /**
     * 39. Admin sees pending approval.
     */
    public function test_39_admin_sees_pending_approval(): void
    {
        $po = $this->createSamplePurchase();
        $this->approvalService->submitForApproval($po, $this->gudangUser);

        $response = $this->actingAs($this->adminUser)->get(route('admin.purchases.approvals.index'));
        $response->assertOk();
        $response->assertSee($po->purchase_no);
        $response->assertSee('Menunggu Persetujuan');

        $dashResp = $this->actingAs($this->adminUser)->get(route('admin.dashboard'));
        $dashResp->assertOk();
        $dashResp->assertSee($po->purchase_no);
        $dashResp->assertSee('Menunggu Persetujuan');
    }

    /**
     * 40. Admin sees approved unpaid/partial.
     */
    public function test_40_admin_sees_approved_unpaid_or_partial(): void
    {
        $po = $this->createSamplePurchase();
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->approvePurchase($po, $this->adminUser);

        $response = $this->actingAs($this->adminUser)->get(route('admin.purchases.payments.index'));
        $response->assertOk();
        $response->assertSee($po->purchase_no);
        $response->assertSee('Disetujui');
    }

    /**
     * 41. Owner no longer sees purchase approval action.
     */
    public function test_41_owner_no_longer_sees_purchase_approval_action(): void
    {
        $po = $this->createSamplePurchase();
        $this->approvalService->submitForApproval($po, $this->gudangUser);

        $response = $this->actingAs($this->ownerUser)->get(route('owner.dashboard'));
        $response->assertOk();
        $response->assertDontSee($po->purchase_no);
        $response->assertDontSee('Menunggu Persetujuan Pembelian');
    }

    /**
     * 42. No nonexistent route aliases introduced.
     */
    public function test_42_no_nonexistent_route_aliases_introduced(): void
    {
        $this->assertTrue(Route::has('admin.purchases.approvals.index'));
        $this->assertTrue(Route::has('admin.purchases.approvals.show'));
        $this->assertTrue(Route::has('admin.purchases.approvals.approve'));
        $this->assertTrue(Route::has('admin.purchases.approvals.request-revision'));
        $this->assertTrue(Route::has('admin.purchases.approvals.reject'));
        $this->assertTrue(Route::has('admin.purchases.payments.proof'));

        // Removed routes should not exist
        $this->assertFalse(Route::has('owner.purchase-approvals.index'));
        $this->assertFalse(Route::has('owner.purchase-approvals.show'));
        $this->assertFalse(Route::has('owner.purchase-approvals.approve'));
        $this->assertFalse(Route::has('owner.purchase-approvals.request-revision'));
        $this->assertFalse(Route::has('owner.purchase-approvals.reject'));
        $this->assertFalse(Route::has('gudang.purchases.confirm'));
    }
}
