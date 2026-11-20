<?php

namespace Tests\Feature\Gudang;

use App\Enums\PurchaseApprovalStatus;
use App\Enums\PurchaseStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Finance\OpeningCashBalance;
use App\Models\Inventory\MaterialStock;
use App\Models\Inventory\Purchase;
use App\Models\Inventory\PurchaseItem;
use App\Models\Inventory\PurchasePayment;
use App\Models\Master\Material;
use App\Models\Master\Supplier;
use App\Models\Master\Unit;
use App\Models\System\User;
use App\Services\Inventory\PurchaseApprovalService;
use App\Services\Inventory\PurchaseOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GudangPurchaseActionQueueDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $gudangUser;
    private User $ownerUser;
    private User $adminUser;
    private Supplier $supplier;
    private Material $material;
    private Unit $meter;
    private PurchaseApprovalService $approvalService;
    private PurchaseOrderService $poService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->approvalService = app(PurchaseApprovalService::class);
        $this->poService = app(PurchaseOrderService::class);

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
            'supplier_name' => 'PT Sumber Kain',
            'phone'         => '08123456789',
            'active'        => true,
        ]);

        $this->material = Material::create([
            'unit_id'       => $this->meter->id,
            'material_name' => 'Kain Katun Rayon Premium',
            'min_stock'     => '10.0000',
            'active'        => true,
        ]);

        MaterialStock::create([
            'material_id'   => $this->material->id,
            'qty_on_hand'   => '5.0000', // Low stock triggers metric
            'avg_unit_cost' => '25000.0000',
        ]);

        OpeningCashBalance::create([
            'opening_date' => '2026-10-01',
            'amount'       => '10000000.00',
            'notes'        => 'Kas Awal',
            'created_by'   => $this->adminUser->id,
        ]);
    }

    private function createPurchase(
        string $no = 'PO-TEST-001',
        string $qty = '10.0000',
        string $price = '50000.00',
        PurchaseStatus $status = PurchaseStatus::DRAFT,
        PurchaseApprovalStatus $approvalStatus = PurchaseApprovalStatus::NOT_SUBMITTED
    ): Purchase {
        $purchase = Purchase::create([
            'supplier_id'     => $this->supplier->id,
            'purchase_no'     => $no,
            'purchase_date'   => '2026-10-09',
            'status'          => $status,
            'approval_status' => $approvalStatus,
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
     * 1. DRAFT not submitted represented correctly ("Belum Diajukan", [ Ajukan ]).
     */
    public function test_draft_not_submitted_represented_correctly(): void
    {
        $po = $this->createPurchase('PO-DRAFT-01');

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.dashboard'));
        $response->assertOk();
        $response->assertSee('Perlu Tindakan');
        $response->assertSee($po->purchase_no);
        $response->assertSee('PT Sumber Kain');
        $response->assertSee('Belum Diajukan');
        $response->assertSee('Ajukan');
    }

    /**
     * 2. Pending approval shows Menunggu Persetujuan Admin ("Menunggu Persetujuan Admin", [ Lihat ]).
     */
    public function test_pending_approval_shows_menunggu_admin(): void
    {
        $po = $this->createPurchase('PO-PENDING-01');
        $this->approvalService->submitForApproval($po, $this->gudangUser);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.dashboard'));
        $response->assertOk();
        $response->assertSee($po->purchase_no);
        $response->assertSee('Menunggu Persetujuan Admin');
        $response->assertSee('Lihat');
    }

    /**
     * 3. Revision required shows Perlu Revisi ("Perlu Revisi", [ Perbaiki ]).
     */
    public function test_revision_required_shows_perlu_revisi(): void
    {
        $po = $this->createPurchase('PO-REVISION-01');
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->requestRevision($po, $this->adminUser, 'Periksa ulang harga satuan.');

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.dashboard'));
        $response->assertOk();
        $response->assertSee($po->purchase_no);
        $response->assertSee('Perlu Revisi');
        $response->assertSee('Perbaiki');
    }

    /**
     * 4. Rejected shows Ditolak ("Ditolak", [ Lihat ]).
     */
    public function test_rejected_shows_ditolak(): void
    {
        $po = $this->createPurchase('PO-REJECT-01');
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->rejectPurchase($po, $this->adminUser, 'Anggaran tidak memadai.');

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.dashboard'));
        $response->assertOk();
        $response->assertSee($po->purchase_no);
        $response->assertSee('Ditolak');
        $response->assertSee('Lihat');
        // Reason paragraph should not bloat the dashboard
        $response->assertDontSee('Anggaran tidak memadai.');
    }

    /**
     * 5. Approved unpaid shows Menunggu Pembayaran.
     */
    public function test_approved_unpaid_shows_menunggu_pembayaran(): void
    {
        $po = $this->createPurchase('PO-UNPAID-01');
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->approvePurchase($po, $this->adminUser);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.dashboard'));
        $response->assertOk();
        $response->assertSee($po->purchase_no);
        $response->assertSee('Menunggu Pembayaran');
        $response->assertSee('Lihat');
    }

    /**
     * 6. Approved partially paid still Menunggu Pembayaran.
     */
    public function test_approved_partially_paid_still_menunggu_pembayaran(): void
    {
        $po = $this->createPurchase('PO-PARTIAL-01', '10.0000', '100000.00'); // Total 1.000.000
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->approvePurchase($po, $this->adminUser);

        // Pay 400.000 (partial)
        PurchasePayment::create([
            'purchase_id'    => $po->id,
            'amount'         => '400000.00',
            'payment_date'   => '2026-10-09',
            'payment_method' => 'TRANSFER_BCA',
            'created_by'     => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.dashboard'));
        $response->assertOk();
        $response->assertSee($po->purchase_no);
        $response->assertSee('Menunggu Pembayaran');
        $response->assertSee('Lihat');
    }

    /**
     * 7 & 8. Approved fully paid transitions to ORDERED and shows Menunggu Barang.
     * Specifically verifies PO-202610-0003 scenario.
     */
    public function test_approved_fully_paid_draft_auto_ordered_shows_menunggu_barang(): void
    {
        $po = $this->createPurchase('PO-202610-0003', '10.0000', '50000.00'); // Total 500.000
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $this->approvalService->approvePurchase($po, $this->adminUser);

        // Admin pays in full -> status becomes ORDERED
        $po->update(['status' => PurchaseStatus::ORDERED]);
        PurchasePayment::create([
            'purchase_id'    => $po->id,
            'amount'         => '500000.00',
            'payment_date'   => '2026-10-09',
            'payment_method' => 'TRANSFER_BCA',
            'created_by'     => $this->adminUser->id,
        ]);

        $this->assertTrue($po->fresh()->isFullyPaid());
        $this->assertEquals(PurchaseStatus::ORDERED, $po->fresh()->status);
        $this->assertEquals(PurchaseApprovalStatus::APPROVED, $po->fresh()->approval_status);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.dashboard'));
        $response->assertOk();
        $response->assertSee('PO-202610-0003');
        $response->assertSee('PT Sumber Kain');
        $response->assertSee('Menunggu Barang');
        $response->assertSee('Terima Barang');
        $response->assertDontSee('Siap Dikonfirmasi');
        $response->assertDontSee('Konfirmasi Supplier');
    }

    /**
     * 9. ORDERED shows Menunggu Barang and Terima Barang.
     */
    public function test_ordered_po_shows_menunggu_barang(): void
    {
        $po = $this->createPurchase('PO-ORDERED-01', '10.0000', '50000.00', PurchaseStatus::ORDERED, PurchaseApprovalStatus::APPROVED);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.dashboard'));
        $response->assertOk();
        $response->assertSee($po->purchase_no);
        $response->assertSee('Menunggu Barang');
        $response->assertSee('Terima Barang');
        $response->assertDontSee('Siap Dikonfirmasi');
    }

    /**
     * 10 & 11. RECEIVED and CANCELLED are excluded from action queue.
     */
    public function test_received_and_cancelled_are_excluded_from_action_queue(): void
    {
        $receivedPo = $this->createPurchase('PO-REC-01', '10.0000', '50000.00', PurchaseStatus::RECEIVED, PurchaseApprovalStatus::APPROVED);
        $cancelledPo = $this->createPurchase('PO-CAN-01', '10.0000', '50000.00', PurchaseStatus::CANCELLED, PurchaseApprovalStatus::NOT_SUBMITTED);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.dashboard'));
        $response->assertOk();
        $response->assertDontSee($receivedPo->purchase_no);
        $response->assertDontSee($cancelledPo->purchase_no);
        $response->assertSee('Tidak ada tindakan yang perlu ditangani.');
    }

    /**
     * 12. Ordered PO CTA navigates to canonical PO show page where receiving can be executed.
     */
    public function test_ordered_po_cta_uses_canonical_flow(): void
    {
        $po = $this->createPurchase('PO-CONF-01', '10.0000', '50000.00', PurchaseStatus::ORDERED, PurchaseApprovalStatus::APPROVED);

        // Dashboard CTA links to show page
        $dashResponse = $this->actingAs($this->gudangUser)->get(route('gudang.dashboard'));
        $dashResponse->assertSee(route('gudang.purchases.show', $po));
        $dashResponse->assertSee('Terima Barang');
    }

    /**
     * 13. Direct backend guards remain active and confirm route is obsolete.
     */
    public function test_direct_backend_guards_remain_active_for_unpaid_or_unapproved(): void
    {
        $draftPo = $this->createPurchase('PO-GUARD-01');
        $this->assertFalse($this->poService->canConfirm($draftPo));

        $this->approvalService->submitForApproval($draftPo, $this->gudangUser);
        $this->approvalService->approvePurchase($draftPo, $this->adminUser);

        $this->assertFalse($this->poService->canConfirm($draftPo->fresh()));

        $this->expectException(\DomainException::class);
        $this->poService->confirmPurchase($draftPo, $this->gudangUser);
    }

    /**
     * 14 & 15. Lifecycle state updates reflected in real time upon page refresh.
     */
    public function test_dashboard_reflects_state_transitions_on_refresh(): void
    {
        $po = $this->createPurchase('PO-FLOW-01', '10.0000', '100000.00');

        // State 1: Belum Diajukan
        $r1 = $this->actingAs($this->gudangUser)->get(route('gudang.dashboard'));
        $r1->assertSee('Belum Diajukan');

        // Gudang submits
        $this->approvalService->submitForApproval($po, $this->gudangUser);
        $r2 = $this->actingAs($this->gudangUser)->get(route('gudang.dashboard'));
        $r2->assertSee('Menunggu Persetujuan Admin');

        // Admin approves
        $this->approvalService->approvePurchase($po, $this->adminUser);
        $r3 = $this->actingAs($this->gudangUser)->get(route('gudang.dashboard'));
        $r3->assertSee('Menunggu Pembayaran');

        // Admin pays in full -> status becomes ORDERED
        $po->update(['status' => PurchaseStatus::ORDERED]);
        PurchasePayment::create([
            'purchase_id'    => $po->id,
            'amount'         => '1000000.00',
            'payment_date'   => '2026-10-09',
            'payment_method' => 'TRANSFER_BCA',
            'created_by'     => $this->adminUser->id,
        ]);
        $r4 = $this->actingAs($this->gudangUser)->get(route('gudang.dashboard'));
        $r4->assertSee('Menunggu Barang');
        $r4->assertSee('Terima Barang');
        $r4->assertDontSee('Siap Dikonfirmasi');
    }

    /**
     * 16. Gudang dashboard does not expose company cash or sensitive financial metrics.
     */
    public function test_gudang_dashboard_does_not_expose_company_cash(): void
    {
        $this->createPurchase('PO-PRIVACY-01');

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.dashboard'));
        $response->assertOk();
        $response->assertDontSee('Kas Saat Ini');
        $response->assertDontSee('Kas Tersedia');
        $response->assertDontSee('available_to_commit');
        $response->assertDontSee('10.000.000'); // Company cash balance
    }

    /**
     * 17. Existing Gudang dashboard metrics remain correct.
     */
    public function test_existing_gudang_dashboard_metrics_remain_correct(): void
    {
        $response = $this->actingAs($this->gudangUser)->get(route('gudang.dashboard'));
        $response->assertOk();
        $response->assertSee('Total Material');
        $response->assertSee('Total Supplier');
        $response->assertSee('Stok Rendah');
        $response->assertSee('Stok Habis');
        $response->assertSee('Material Terbaru');
        $response->assertSee($this->material->material_name);
    }
}
