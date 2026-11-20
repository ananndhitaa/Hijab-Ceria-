<?php

namespace Tests\Feature\Purchase;

use App\Enums\BatchStatus;
use App\Enums\MovementType;
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
use App\Models\Inventory\StockMovement;
use App\Models\Master\BomItem;
use App\Models\Master\Customer;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Master\Supplier;
use App\Models\Master\Unit;
use App\Models\Production\ProductionBatch;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\User;
use App\Services\Finance\FinancialRecordService;
use App\Services\Inventory\BatchMaterialPlanningService;
use App\Services\Inventory\PurchaseApprovalService;
use App\Services\Inventory\PurchaseOrderService;
use App\Services\Inventory\PurchaseReceivingService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PurchaseApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $gudangUser;
    private User $ownerUser;
    private User $adminUser;
    private Supplier $supplier;
    private Material $material;
    private Unit $meter;
    private PurchaseOrderService $poService;
    private PurchaseApprovalService $approvalService;
    private FinancialRecordService $financeService;
    private PurchaseReceivingService $receivingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->poService = app(PurchaseOrderService::class);
        $this->approvalService = app(PurchaseApprovalService::class);
        $this->financeService = app(FinancialRecordService::class);
        $this->receivingService = app(PurchaseReceivingService::class);

        $this->gudangUser = User::factory()->create([
            'name'   => 'Staf Gudang',
            'role'   => UserRole::GUDANG,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->ownerUser = User::factory()->create([
            'name'   => 'Owner Nayara',
            'role'   => UserRole::OWNER,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->adminUser = User::factory()->create([
            'name'   => 'Admin Keuangan',
            'role'   => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->meter = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);

        $this->supplier = Supplier::create([
            'supplier_name' => 'PT Tekstil Sejahtera',
            'phone'         => '0811223344',
            'active'        => true,
        ]);

        $this->material = Material::create([
            'unit_id'       => $this->meter->id,
            'material_name' => 'Kain Ceruty Premium',
            'min_stock'     => '10.0000',
            'active'        => true,
        ]);

        MaterialStock::create([
            'material_id'   => $this->material->id,
            'qty_on_hand'   => '100.0000',
            'avg_unit_cost' => '25000.0000',
        ]);

        $this->supplier->materials()->attach($this->material->id);
    }

    private function setupCash(string $amount = '10000000.00'): OpeningCashBalance
    {
        return OpeningCashBalance::create([
            'amount'       => $amount,
            'opening_date' => '2026-10-01',
            'created_by'   => $this->adminUser->id,
        ]);
    }

    private function createSamplePurchase(string $qty = '50.0000', string $unitPrice = '30000.00'): Purchase
    {
        return $this->poService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-10-09',
            'notes'         => 'Pengadaan material rutin',
            'items'         => [
                [
                    'material_id' => $this->material->id,
                    'qty'         => $qty,
                    'unit_price'  => $unitPrice,
                ],
            ],
        ], $this->gudangUser);
    }

    /**
     * 1. Gudang creates DRAFT.
     */
    public function test_01_gudang_creates_draft(): void
    {
        $response = $this->actingAs($this->gudangUser)->post(route('gudang.purchases.store'), [
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-10-09',
            'notes'         => 'Draft baru',
            'items'         => [
                ['material_id' => $this->material->id, 'qty' => '50', 'unit_price' => '30000'],
            ],
        ]);

        $purchase = Purchase::first();
        $this->assertNotNull($purchase);
        $response->assertRedirect(route('gudang.purchases.show', $purchase));
        $this->assertEquals(PurchaseStatus::DRAFT, $purchase->status);
        $this->assertEquals(PurchaseApprovalStatus::NOT_SUBMITTED, $purchase->approval_status);
        $this->assertEquals('1500000.00', $purchase->total_amount_string);
    }

    /**
     * 2. DRAFT shows Ajukan Persetujuan.
     */
    public function test_02_draft_shows_ajukan_persetujuan(): void
    {
        $po = $this->createSamplePurchase();

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.purchases.show', $po));
        $response->assertOk();
        $response->assertSee('Ajukan Persetujuan');
        $response->assertSee('Belum Diajukan');
    }

    /**
     * 3. DRAFT does not show Konfirmasi Pesanan.
     */
    public function test_03_draft_does_not_show_konfirmasi_pesanan(): void
    {
        $po = $this->createSamplePurchase();

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.purchases.show', $po));
        $response->assertOk();
        $response->assertDontSee('Konfirmasi Pesanan');
        $response->assertDontSee('Konfirmasi ke Supplier');
    }

    /**
     * 4. Direct confirmation on unapproved DRAFT rejected.
     */
    public function test_04_direct_confirmation_on_unapproved_draft_rejected(): void
    {
        $po = $this->createSamplePurchase();

        $this->assertFalse($this->poService->canConfirm($po));

        $this->expectException(DomainException::class);
        $this->poService->confirmPurchase($po, $this->gudangUser);
    }

    /**
     * 5. Gudang submits approval.
     */
    public function test_05_gudang_submits_approval(): void
    {
        $po = $this->createSamplePurchase();

        $response = $this->actingAs($this->gudangUser)->post(route('gudang.purchases.submit-approval', $po), [
            'submission_notes' => 'Mohon disetujui untuk stok minggu ini',
        ]);

        $response->assertRedirect(route('gudang.purchases.show', $po));
        $response->assertSessionHas('success');

        $fresh = $po->fresh();
        $this->assertEquals(PurchaseApprovalStatus::PENDING_APPROVAL, $fresh->approval_status);
        $this->assertNotNull($fresh->submitted_at);
        $this->assertEquals($this->gudangUser->id, $fresh->submitted_by);
    }

    /**
     * 6. PENDING approval shown.
     */
    public function test_06_pending_approval_shown_in_gudang_ui(): void
    {
        $po = $this->createSamplePurchase();
        $this->approvalService->submitForApproval($po, $this->gudangUser);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.purchases.show', $po));
        $response->assertOk();
        $response->assertSee('Menunggu Persetujuan Admin');
        $response->assertDontSee('Ajukan Persetujuan');
    }

    /**
     * 7. Pending PO read-only.
     */
    public function test_07_pending_po_read_only(): void
    {
        $po = $this->createSamplePurchase();
        $this->approvalService->submitForApproval($po, $this->gudangUser);

        // Edit route blocked
        $response = $this->actingAs($this->gudangUser)->get(route('gudang.purchases.edit', $po));
        $response->assertRedirect(route('gudang.purchases.show', $po));
        $response->assertSessionHas('error');

        // Direct update request blocked
        $updateResp = $this->actingAs($this->gudangUser)->put(route('gudang.purchases.update', $po), [
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-10-09',
            'items'         => [
                ['material_id' => $this->material->id, 'qty' => '80', 'unit_price' => '30000'],
            ],
        ]);
        $updateResp->assertRedirect(route('gudang.purchases.show', $po));
        $updateResp->assertSessionHas('error');
    }

    /**
     * 8. Admin sees pending PO in queue.
     */
    public function test_08_owner_sees_pending_po(): void
    {
        $po = $this->createSamplePurchase();
        $this->approvalService->submitForApproval($po, $this->gudangUser);

        $response = $this->actingAs($this->adminUser)->get(route('admin.purchases.approvals.index'));
        $response->assertOk();
        $response->assertSee($po->purchase_no);
        $response->assertSee('PT Tekstil Sejahtera');
        $response->assertSee('Menunggu Persetujuan');

        // Owner cannot access admin purchase approvals
        $this->actingAs($this->ownerUser)->get(route('admin.purchases.approvals.index'))->assertStatus(403);
    }

    /**
     * 9. Admin sees operational context.
     */
    public function test_09_owner_sees_operational_context(): void
    {
        // 1. Setup batch with shortage
        $customer = Customer::create(['customer_name' => 'Toko Busana']);
        $product = Product::create(['product_name' => 'Gamis Cantik', 'active' => true]);
        $variant = ProductVariant::create([
            'product_id'   => $product->id,
            'variant_name' => 'Maroon',
            'sku'          => 'GMS-MRN-01',
            'active'       => true,
        ]);
        BomItem::create([
            'variant_id'   => $variant->id,
            'material_id'  => $this->material->id,
            'qty_per_unit' => 1.5,
        ]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'order_no'    => 'SO-202610-0099',
            'order_date'  => '2026-10-09',
            'status'      => OrderStatus::DEAL,
        ]);
        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 100,
            'unit_selling_price' => 150000,
        ]);
        $batch = ProductionBatch::create([
            'order_item_id' => $orderItem->id,
            'batch_no'      => 'BAT-202610-0099',
            'target_qty'    => 100,
            'status'        => BatchStatus::BAHAN,
        ]);

        $mrpService = app(BatchMaterialPlanningService::class);
        $mrpService->generateMaterialPlanning($batch);

        // Required: 150, Current: 100, Shortage: 50
        $po = $this->poService->createPurchase([
            'batch_id'      => $batch->id,
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-10-09',
            'items'         => [
                ['material_id' => $this->material->id, 'qty' => '50', 'unit_price' => '30000'],
            ],
        ], $this->gudangUser);
        $this->approvalService->submitForApproval($po, $this->gudangUser);

        $response = $this->actingAs($this->adminUser)->get(route('admin.purchases.approvals.show', $po));
        $response->assertOk();
        $response->assertSee('BAT-202610-0099');
        $response->assertSee('Perencanaan Kebutuhan Bahan (MRP)');
        $response->assertSee('100,0000'); // Stok saat ini
        $response->assertSee('50,0000');  // Shortage / Qty diajukan
    }

    /**
     * 10. Admin sees financial context.
     */
    public function test_10_owner_sees_financial_context(): void
    {
        $this->setupCash('10000000.00'); // Kas: 10jt
        $po = $this->createSamplePurchase('50.0000', '30000.00'); // PO: 1.5jt
        $this->approvalService->submitForApproval($po, $this->gudangUser);

        $response = $this->actingAs($this->adminUser)->get(route('admin.purchases.approvals.show', $po));
        $response->assertOk();
        $response->assertSee('Kas Saat Ini');
        $response->assertSee('10.000.000');
        $response->assertSee('Dana Tersedia untuk Komitmen Baru');
        $response->assertSee('Nilai PO Diajukan');
        $response->assertSee('1.500.000');
        $response->assertSee('Proyeksi Sisa Kas');
        $response->assertSee('8.500.000');
    }

    /**
     * 11. Gudang cannot see company cash.
     */
    public function test_11_gudang_cannot_see_company_cash(): void
    {
        $this->setupCash('10000000.00');
        $po = $this->createSamplePurchase();
        $this->approvalService->submitForApproval($po, $this->gudangUser);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.purchases.show', $po));
        $response->assertOk();
        $response->assertDontSee('Kas Saat Ini');
        $response->assertDontSee('Dana Tersedia');
        $response->assertDontSee('Proyeksi Sisa');
        $response->assertDontSee('10.000.000');
    }

    /**
     * 12. Admin approves sufficient PO.
     */
    public function test_12_owner_approves_sufficient_po(): void
    {
        $this->setupCash('5000000.00');
        $po = $this->createSamplePurchase('50.0000', '30000.00'); // 1.5jt
        $this->approvalService->submitForApproval($po, $this->gudangUser);

        $response = $this->actingAs($this->adminUser)->post(route('admin.purchases.approvals.approve', $po), [
            'notes' => 'Disetujui untuk kebutuhan produksi',
        ]);

        $response->assertRedirect(route('admin.purchases.approvals.show', $po));
        $response->assertSessionHas('success');

        $fresh = $po->fresh();
        $this->assertEquals(PurchaseApprovalStatus::APPROVED, $fresh->approval_status);
        $this->assertNotNull($fresh->approved_at);
        $this->assertEquals($this->adminUser->id, $fresh->approved_by);
    }

    /**
     * 13. Admin cannot approve insufficient PO.
     */
    public function test_13_owner_cannot_approve_insufficient_po(): void
    {
        $this->setupCash('1000000.00'); // Kas 1jt
        $po = $this->createSamplePurchase('50.0000', '30000.00'); // PO 1.5jt
        $this->approvalService->submitForApproval($po, $this->gudangUser);

        $response = $this->actingAs($this->adminUser)->post(route('admin.purchases.approvals.approve', $po));
        $response->assertRedirect(route('admin.purchases.approvals.show', $po));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Dana tersedia belum mencukupi', session('error'));

        $this->assertEquals(PurchaseApprovalStatus::PENDING_APPROVAL, $po->fresh()->approval_status);
    }

    /**
     * 14. Admin requests revision with reason.
     */
    public function test_14_owner_requests_revision_with_reason(): void
    {
        $po = $this->createSamplePurchase();
        $this->approvalService->submitForApproval($po, $this->gudangUser);

        // Validation error if note empty
        $emptyNoteResp = $this->actingAs($this->adminUser)->post(route('admin.purchases.approvals.request-revision', $po), [
            'review_note' => '',
        ]);
        $emptyNoteResp->assertSessionHasErrors('review_note');

        // Valid revision
        $response = $this->actingAs($this->adminUser)->post(route('admin.purchases.approvals.request-revision', $po), [
            'review_note' => 'Tolong negosiasikan harga ke Rp28.000 per meter',
        ]);

        $response->assertRedirect(route('admin.purchases.approvals.index'));
        $response->assertSessionHas('success');

        $fresh = $po->fresh();
        $this->assertEquals(PurchaseApprovalStatus::REVISION_REQUIRED, $fresh->approval_status);
        $this->assertEquals('Tolong negosiasikan harga ke Rp28.000 per meter', $fresh->review_note);
    }

    /**
     * 15. Gudang can edit revision.
     */
    public function test_15_gudang_can_edit_revision(): void
    {
        $po = $this->createSamplePurchase('50.0000', '30000.00');
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->requestRevision($po, $this->adminUser, 'Harga terlalu tinggi');

        // Gudang views edit form
        $response = $this->actingAs($this->gudangUser)->get(route('gudang.purchases.edit', $po));
        $response->assertOk();

        // Gudang updates unit price to 28000
        $updateResp = $this->actingAs($this->gudangUser)->put(route('gudang.purchases.update', $po), [
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-10-09',
            'items'         => [
                ['material_id' => $this->material->id, 'qty' => '50', 'unit_price' => '28000'],
            ],
        ]);

        $updateResp->assertRedirect(route('gudang.purchases.show', $po));
        $this->assertEquals('1400000.00', $po->fresh()->total_amount_string);
    }

    /**
     * 16. Edited revision needs re-approval.
     */
    public function test_16_edited_revision_needs_re_approval(): void
    {
        $po = $this->createSamplePurchase();
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->requestRevision($po, $this->adminUser, 'Revisi qty');

        // Gudang edits
        $this->poService->updatePurchase($po, [
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-10-09',
            'items'         => [
                ['material_id' => $this->material->id, 'qty' => '40', 'unit_price' => '30000'],
            ],
        ], $this->gudangUser);

        // Status falls back to NOT_SUBMITTED
        $this->assertEquals(PurchaseApprovalStatus::NOT_SUBMITTED, $po->fresh()->approval_status);

        // Gudang must resubmit
        $this->approvalService->submitForApproval($po->fresh(), $this->gudangUser);
        $this->assertEquals(PurchaseApprovalStatus::PENDING_APPROVAL, $po->fresh()->approval_status);
    }

    /**
     * 17. Admin rejects with reason.
     */
    public function test_17_owner_rejects_with_reason(): void
    {
        $po = $this->createSamplePurchase();
        $this->approvalService->submitForApproval($po, $this->gudangUser);

        // Empty reason fails
        $this->actingAs($this->adminUser)->post(route('admin.purchases.approvals.reject', $po), [
            'reason' => '',
        ])->assertSessionHasErrors('reason');

        // Valid reject
        $response = $this->actingAs($this->adminUser)->post(route('admin.purchases.approvals.reject', $po), [
            'reason' => 'Pengadaan ditolak karena stok kain masih cukup di gudang cabang',
        ]);

        $response->assertRedirect(route('admin.purchases.approvals.index'));
        $this->assertEquals(PurchaseApprovalStatus::REJECTED, $po->fresh()->approval_status);
    }

    /**
     * 18. Admin cannot pay unapproved PO.
     */
    public function test_18_admin_cannot_pay_unapproved_po(): void
    {
        Storage::fake('local');
        $this->setupCash('10000000.00');
        $po = $this->createSamplePurchase();

        // NOT_SUBMITTED
        $resp1 = $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '500000.00',
            'payment_date'   => '2026-10-09',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ]);
        $resp1->assertSessionHasErrors('amount');

        // PENDING_APPROVAL
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $resp2 = $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '500000.00',
            'payment_date'   => '2026-10-09',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ]);
        $resp2->assertSessionHasErrors('amount');
    }

    /**
     * 19. Admin can pay approved PO and full payment auto-transitions to ORDERED.
     */
    public function test_19_admin_can_pay_approved_po(): void
    {
        Storage::fake('local');
        $this->setupCash('10000000.00');
        $po = $this->createSamplePurchase();
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->approvePurchase($po, $this->adminUser);

        $response = $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '1500000.00',
            'payment_date'   => '2026-10-09',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect();
        $this->assertEquals('1500000.00', $po->fresh()->paid_amount);
        $this->assertTrue($po->fresh()->isFullyPaid());
        $this->assertEquals(PurchaseStatus::ORDERED, $po->fresh()->status);
    }

    /**
     * 20. Partial payment keeps PO in DRAFT and not ORDERED.
     */
    public function test_20_partial_payment_does_not_enable_supplier_confirmation(): void
    {
        Storage::fake('local');
        $this->setupCash('10000000.00');
        $po = $this->createSamplePurchase(); // 1.5jt
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->approvePurchase($po, $this->adminUser);

        // Pay 1jt (outstanding 500rb)
        $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '1000000.00',
            'payment_date'   => '2026-10-09',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ]);

        $this->assertFalse($po->fresh()->isFullyPaid());
        $this->assertEquals(PurchaseStatus::DRAFT, $po->fresh()->status);
        $this->assertFalse($this->poService->canConfirm($po->fresh()));
    }

    /**
     * 21. Full payment automatically orders PO.
     */
    public function test_21_full_payment_enables_supplier_confirmation(): void
    {
        Storage::fake('local');
        $this->setupCash('10000000.00');
        $po = $this->createSamplePurchase(); // 1.5jt
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->approvePurchase($po, $this->adminUser);

        // Pay in full
        $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '1500000.00',
            'payment_date'   => '2026-10-09',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ]);

        $this->assertTrue($po->fresh()->isFullyPaid());
        $this->assertEquals(PurchaseStatus::ORDERED, $po->fresh()->status);
        $this->assertFalse($this->poService->canConfirm($po->fresh()));

        // UI shows ORDERED, does not show Konfirmasi ke Supplier
        $showResp = $this->actingAs($this->gudangUser)->get(route('gudang.purchases.show', $po));
        $showResp->assertOk();
        $showResp->assertDontSee('Konfirmasi ke Supplier');
    }

    /**
     * 22. Manual confirm to supplier is obsolete.
     */
    public function test_22_gudang_cannot_confirm_supplier_before_approval(): void
    {
        $po = $this->createSamplePurchase();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Alur konfirmasi manual ke supplier telah ditiadakan');

        $this->poService->confirmPurchase($po, $this->gudangUser);
    }

    /**
     * 23. Manual confirm to supplier is obsolete even when partially paid.
     */
    public function test_23_gudang_cannot_confirm_supplier_before_full_payment(): void
    {
        $this->setupCash('10000000.00');
        $po = $this->createSamplePurchase();
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->approvePurchase($po, $this->adminUser);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Alur konfirmasi manual ke supplier telah ditiadakan');

        $this->poService->confirmPurchase($po, $this->gudangUser);
    }

    /**
     * 24. Full payment automatically orders PO without manual supplier confirmation.
     */
    public function test_24_approved_and_fully_paid_supplier_confirmation_works(): void
    {
        Storage::fake('local');
        $this->setupCash('10000000.00');
        $po = $this->createSamplePurchase();
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->approvePurchase($po, $this->adminUser);

        $response = $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '1500000.00',
            'payment_date'   => '2026-10-09',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect();
        $this->assertEquals(PurchaseStatus::ORDERED, $po->fresh()->status);
    }

    /**
     * 25. Status becomes ORDERED upon full payment.
     */
    public function test_25_status_becomes_ordered(): void
    {
        Storage::fake('local');
        $this->setupCash('10000000.00');
        $po = $this->createSamplePurchase();
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->approvePurchase($po, $this->adminUser);

        $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '1500000.00',
            'payment_date'   => '2026-10-09',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ]);

        $this->assertEquals(PurchaseStatus::ORDERED, $po->fresh()->status);
    }

    /**
     * 26. Receive still works only after ORDERED.
     */
    public function test_26_receive_still_works_only_after_ordered(): void
    {
        Storage::fake('local');
        $this->setupCash('10000000.00');
        $po = $this->createSamplePurchase();
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->approvePurchase($po, $this->adminUser);

        // Attempt receiving while still DRAFT
        try {
            $this->receivingService->receivePurchase($po, $this->gudangUser->id);
            $this->fail('Expected DomainException on receiving draft PO');
        } catch (DomainException $e) {
            $this->assertStringContainsString('Hanya Purchase Order berstatus ORDERED yang dapat diterima', $e->getMessage());
        }

        // Full payment auto-transitions to ORDERED
        $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '1500000.00',
            'payment_date'   => '2026-10-09',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ]);
        $this->assertEquals(PurchaseStatus::ORDERED, $po->fresh()->status);

        // Receive now succeeds
        $received = $this->receivingService->receivePurchase($po->fresh(), $this->gudangUser->id);
        $this->assertEquals(PurchaseStatus::RECEIVED, $received->status);
    }

    /**
     * 27. Receive changes stock once.
     */
    public function test_27_receive_changes_stock_once(): void
    {
        Storage::fake('local');
        $this->setupCash('10000000.00');
        $po = $this->createSamplePurchase('50.0000', '30000.00');
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->approvePurchase($po, $this->adminUser);

        // Full payment auto-orders
        $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => $po->total_amount,
            'payment_date'   => '2026-10-09',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ]);

        $initialStock = $this->material->fresh(['stock'])->stock->qty_on_hand; // 100
        $this->receivingService->receivePurchase($po->fresh(), $this->gudangUser->id);

        $this->assertEquals('150.0000', $this->material->fresh(['stock'])->stock->qty_on_hand);
        $this->assertEquals(1, StockMovement::count());
    }

    /**
     * 28. Receive updates MWA.
     */
    public function test_28_receive_updates_mwa(): void
    {
        Storage::fake('local');
        // Old: 100 m @ 25.000 = 2.500.000
        // New: 50 m @ 40.000 = 2.000.000
        // Total: 150 m, Cost: 4.500.000 -> MWA = 30.000
        $this->setupCash('10000000.00');
        $po = $this->createSamplePurchase('50.0000', '40000.00');
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->approvePurchase($po, $this->adminUser);

        $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => $po->total_amount,
            'payment_date'   => '2026-10-09',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ]);

        $this->receivingService->receivePurchase($po->fresh(), $this->gudangUser->id);

        $stock = $this->material->fresh(['stock'])->stock;
        $this->assertEquals('150.0000', $stock->qty_on_hand);
        $this->assertEquals('30000.0000', $stock->avg_unit_cost);
    }

    /**
     * 29. Receive does not change cash.
     */
    public function test_29_receive_does_not_change_cash(): void
    {
        Storage::fake('local');
        $this->setupCash('10000000.00');
        $po = $this->createSamplePurchase('50.0000', '40000.00'); // 2jt
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->approvePurchase($po, $this->adminUser);

        // Payment reduces cash from 10jt to 8jt
        $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '2000000.00',
            'payment_date'   => '2026-10-09',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ]);
        $this->assertEquals('8000000.00', $this->financeService->getConsolidatedRecords()['cash_position']['kas_tersedia']);

        // Receive
        $this->receivingService->receivePurchase($po->fresh(), $this->gudangUser->id);

        // Cash remains exactly 8jt
        $this->assertEquals('8000000.00', $this->financeService->getConsolidatedRecords()['cash_position']['kas_tersedia']);
    }

    /**
     * 30. PO-202610-0003 remains unapproved after migration/feature.
     */
    public function test_30_po_202610_0003_remains_unapproved_in_current_state(): void
    {
        $uatPo = Purchase::create([
            'purchase_no'     => 'PO-202610-0003',
            'supplier_id'     => $this->supplier->id,
            'purchase_date'   => '2026-10-09',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::NOT_SUBMITTED,
        ]);
        PurchaseItem::create([
            'purchase_id'  => $uatPo->id,
            'material_id'  => $this->material->id,
            'qty'          => '50.0000',
            'unit_price'   => '30000.00',
            'qty_received' => '0.0000',
        ]);

        $this->assertEquals(PurchaseStatus::DRAFT, $uatPo->fresh()->status);
        $this->assertEquals(PurchaseApprovalStatus::NOT_SUBMITTED, $uatPo->fresh()->approval_status);
        $this->assertFalse($this->poService->canConfirm($uatPo->fresh()));
        $this->assertTrue($this->poService->canSubmitApproval($uatPo->fresh()));

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.purchases.show', $uatPo));
        $response->assertSee('Ajukan Persetujuan');
        $response->assertDontSee('Konfirmasi Pesanan');
        $response->assertDontSee('Konfirmasi ke Supplier');
    }

    /**
     * 31. Legacy received PO remain valid.
     */
    public function test_31_legacy_received_po_remain_valid(): void
    {
        $legacyPo = Purchase::create([
            'purchase_no'     => 'PO-202609-0001',
            'supplier_id'     => $this->supplier->id,
            'purchase_date'   => '2026-09-01',
            'status'          => PurchaseStatus::RECEIVED,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
        ]);
        PurchaseItem::create([
            'purchase_id'  => $legacyPo->id,
            'material_id'  => $this->material->id,
            'qty'          => '100.0000',
            'unit_price'   => '20000.00',
            'qty_received' => '100.0000',
        ]);

        $fresh = $legacyPo->fresh();
        $this->assertEquals(PurchaseStatus::RECEIVED, $fresh->status);
        $this->assertEquals(PurchaseApprovalStatus::APPROVED, $fresh->approval_status);
        $this->assertEquals('2000000.00', $fresh->total_amount_string);
    }

    /**
     * 32. Multiple commitment guard & finance tests pass.
     */
    public function test_32_multiple_commitment_guard_prevents_overcommitting_cash(): void
    {
        // Kas 10jt
        $this->setupCash('10000000.00');

        // PO A: 8jt approved but unpaid
        $poA = $this->createSamplePurchase('100.0000', '80000.00');
        $this->approvalService->submitForApproval($poA, $this->gudangUser);
        $this->approvalService->approvePurchase($poA, $this->adminUser);

        // Available to commit now: 10jt - 8jt = 2jt
        $finance = $this->financeService->calculateAvailableToCommit();
        $this->assertEquals('2000000.00', $finance['available_to_commit']);

        // PO B: 5jt pending
        $poB = $this->createSamplePurchase('100.0000', '50000.00');
        $this->approvalService->submitForApproval($poB, $this->gudangUser);

        // Admin tries to approve PO B (5jt > 2jt available)
        $response = $this->actingAs($this->adminUser)->post(route('admin.purchases.approvals.approve', $poB));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Dana tersedia belum mencukupi', session('error'));

        $this->assertEquals(PurchaseApprovalStatus::PENDING_APPROVAL, $poB->fresh()->approval_status);
    }

    /**
     * 33. Revision invalidates old approval.
     */
    public function test_33_revision_invalidates_old_approval(): void
    {
        $this->setupCash('10000000.00');
        $po = $this->createSamplePurchase('50.0000', '30000.00'); // 1.5jt
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->approvePurchase($po, $this->adminUser);

        $this->assertEquals(PurchaseApprovalStatus::APPROVED, $po->fresh()->approval_status);

        // Gudang modifies commercial data
        $this->poService->updatePurchase($po, [
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-10-09',
            'items'         => [
                ['material_id' => $this->material->id, 'qty' => '60', 'unit_price' => '30000'],
            ],
        ], $this->gudangUser);

        // Approval is invalidated!
        $this->assertEquals(PurchaseApprovalStatus::NOT_SUBMITTED, $po->fresh()->approval_status);
        $this->assertNull($po->fresh()->approved_at);
        $this->assertNull($po->fresh()->approved_by);
    }

    /**
     * 34. Production and QC unaffected by purchase approval lifecycle.
     */
    public function test_34_production_and_qc_unaffected_by_approval_lifecycle(): void
    {
        $customer = Customer::create(['customer_name' => 'Test Cust']);
        $product = Product::create(['product_name' => 'Test Prod', 'active' => true]);
        $variant = ProductVariant::create([
            'product_id'   => $product->id,
            'variant_name' => 'Test Var',
            'sku'          => 'SKU-001',
            'active'       => true,
        ]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'order_no'    => 'SO-202610-9999',
            'order_date'  => '2026-10-09',
            'status'      => OrderStatus::DEAL,
        ]);
        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 50,
            'unit_selling_price' => 100000,
        ]);
        $batch = ProductionBatch::create([
            'order_item_id' => $orderItem->id,
            'batch_no'      => 'BAT-202610-0999',
            'target_qty'    => 50,
            'status'        => BatchStatus::BAHAN,
        ]);

        $po = $this->createSamplePurchase();
        $this->approvalService->submitForApproval($po, $this->gudangUser);

        // Batch status is still BAHAN
        $this->assertEquals(BatchStatus::BAHAN, $batch->fresh()->status);

        $this->setupCash('10000000.00');
        $this->approvalService->approvePurchase($po, $this->adminUser);

        $this->assertEquals(BatchStatus::BAHAN, $batch->fresh()->status);
    }
}
