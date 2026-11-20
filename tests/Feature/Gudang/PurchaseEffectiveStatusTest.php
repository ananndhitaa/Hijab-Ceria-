<?php

namespace Tests\Feature\Gudang;

use App\Enums\PurchaseApprovalStatus;
use App\Enums\PurchaseStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Finance\OpeningCashBalance;
use App\Models\Inventory\Purchase;
use App\Models\Inventory\PurchaseItem;
use App\Models\Inventory\PurchasePayment;
use App\Models\Master\Material;
use App\Models\Master\Supplier;
use App\Models\Master\Unit;
use App\Models\System\User;
use App\Services\Finance\FinancialRecordService;
use App\Services\Inventory\PurchaseReceivingService;
use App\Support\StatusBadgePresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PurchaseEffectiveStatusTest extends TestCase
{
    use RefreshDatabase;

    private User $gudangUser;
    private User $adminUser;
    private User $ownerUser;
    private Supplier $supplier;
    private Material $material;
    private Unit $meter;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->gudangUser = User::factory()->create([
            'role'   => UserRole::GUDANG,
            'status' => UserStatus::ACTIVE,
            'name'   => 'Staff Gudang',
        ]);

        $this->adminUser = User::factory()->create([
            'role'   => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
            'name'   => 'Admin Keuangan',
        ]);

        $this->ownerUser = User::factory()->create([
            'role'   => UserRole::OWNER,
            'status' => UserStatus::ACTIVE,
            'name'   => 'Owner Nayara',
        ]);

        $this->supplier = Supplier::create([
            'supplier_name' => 'PT Tekstil Jaya Abadi',
            'active'        => true,
        ]);

        $this->meter = Unit::create([
            'unit_name' => 'Meter',
            'symbol'    => 'm',
        ]);

        $this->material = Material::create([
            'material_name' => 'Katun Toyobo',
            'unit_id'       => $this->meter->id,
            'cost_per_unit' => 25000,
            'active'        => true,
        ]);

        \App\Models\Inventory\MaterialStock::create([
            'material_id'   => $this->material->id,
            'qty_on_hand'   => '100.0000',
            'avg_unit_cost' => '25000.0000',
        ]);
    }

    /**
     * Helper to create a purchase with 1 item.
     */
    private function createPurchase(array $attributes = [], int $qty = 10, int $unitPrice = 25000): Purchase
    {
        $purchase = Purchase::create(array_merge([
            'supplier_id'     => $this->supplier->id,
            'purchase_no'     => 'PO-' . date('Ym') . '-' . str_pad((string) rand(1, 9999), 4, '0', STR_PAD_LEFT),
            'purchase_date'   => now()->toDateString(),
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::NOT_SUBMITTED,
        ], $attributes));

        PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'material_id' => $this->material->id,
            'qty'         => $qty,
            'unit_price'  => $unitPrice,
        ]);

        return $purchase->fresh(['items', 'payments']);
    }

    /**
     * Requirement 1: RECEIVED + APPROVED renders only "Diterima".
     */
    public function test_received_and_approved_renders_only_diterima(): void
    {
        $po = $this->createPurchase([
            'purchase_no'     => 'PO-TEST-0001',
            'status'          => PurchaseStatus::RECEIVED,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
        ]);

        $effective = $po->effectiveStatus();
        $this->assertSame('RECEIVED', $effective['key']);
        $this->assertSame('Diterima', $effective['label']);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.purchases.index'));
        $response->assertOk();
        $response->assertSee('PO-TEST-0001');
        $response->assertSee('Diterima');
        $response->assertSee('bg-emerald-50 text-emerald-700 border-emerald-200');

        // Verify "Disetujui" is NOT shown for this PO row
        $content = $response->getContent();
        // The table row for PO-TEST-0001 should not contain "Disetujui"
        $this->assertStringNotContainsString('Disetujui', $content);
    }

    /**
     * Requirement 2: ORDERED + APPROVED renders only "Dipesan".
     */
    public function test_ordered_and_approved_renders_only_dipesan(): void
    {
        $po = $this->createPurchase([
            'purchase_no'     => 'PO-TEST-0002',
            'status'          => PurchaseStatus::ORDERED,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
        ]);

        $effective = $po->effectiveStatus();
        $this->assertSame('ORDERED', $effective['key']);
        $this->assertSame('Dipesan', $effective['label']);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.purchases.index'));
        $response->assertOk();
        $response->assertSee('PO-TEST-0002');
        $response->assertSee('Dipesan');
        $this->assertStringNotContainsString('Disetujui', $response->getContent());
    }

    /**
     * Requirement 3: CANCELLED + NOT_SUBMITTED renders only "Dibatalkan".
     */
    public function test_cancelled_and_not_submitted_renders_only_dibatalkan(): void
    {
        $po = $this->createPurchase([
            'purchase_no'     => 'PO-TEST-0003',
            'status'          => PurchaseStatus::CANCELLED,
            'approval_status' => PurchaseApprovalStatus::NOT_SUBMITTED,
        ]);

        $effective = $po->effectiveStatus();
        $this->assertSame('CANCELLED', $effective['key']);
        $this->assertSame('Dibatalkan', $effective['label']);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.purchases.index'));
        $response->assertOk();
        $response->assertSee('PO-TEST-0003');
        $response->assertSee('Dibatalkan');
        $this->assertStringNotContainsString('Belum Diajukan', $response->getContent());
    }

    /**
     * Requirement 4: PENDING_APPROVAL renders only "Menunggu Persetujuan".
     */
    public function test_pending_approval_renders_only_menunggu_persetujuan(): void
    {
        $po = $this->createPurchase([
            'purchase_no'     => 'PO-TEST-0004',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::PENDING_APPROVAL,
        ]);

        $effective = $po->effectiveStatus();
        $this->assertSame('PENDING_APPROVAL', $effective['key']);
        $this->assertSame('Menunggu Persetujuan', $effective['label']);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.purchases.index'));
        $response->assertOk();
        $response->assertSee('PO-TEST-0004');
        $response->assertSee('Menunggu Persetujuan');
        $response->assertDontSee('Menunggu Persetujuan Admin');
    }

    /**
     * Requirement 5: REVISION_REQUIRED renders only "Perlu Revisi".
     */
    public function test_revision_required_renders_only_perlu_revisi(): void
    {
        $po = $this->createPurchase([
            'purchase_no'     => 'PO-TEST-0005',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::REVISION_REQUIRED,
        ]);

        $effective = $po->effectiveStatus();
        $this->assertSame('REVISION_REQUIRED', $effective['key']);
        $this->assertSame('Perlu Revisi', $effective['label']);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.purchases.index'));
        $response->assertOk();
        $response->assertSee('PO-TEST-0005');
        $response->assertSee('Perlu Revisi');
    }

    /**
     * Requirement 6: APPROVED unpaid renders only "Menunggu Pembayaran".
     */
    public function test_approved_unpaid_renders_only_menunggu_pembayaran(): void
    {
        $po = $this->createPurchase([
            'purchase_no'     => 'PO-TEST-0006',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
        ]);

        $effective = $po->effectiveStatus();
        $this->assertSame('WAITING_PAYMENT', $effective['key']);
        $this->assertSame('Menunggu Pembayaran', $effective['label']);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.purchases.index'));
        $response->assertOk();
        $response->assertSee('PO-TEST-0006');
        $response->assertSee('Menunggu Pembayaran');
        $this->assertStringNotContainsString('Disetujui', $response->getContent());
    }

    /**
     * Requirement 7: APPROVED partial renders only "Pembayaran Sebagian".
     */
    public function test_approved_partial_renders_only_pembayaran_sebagian(): void
    {
        $po = $this->createPurchase([
            'purchase_no'     => 'PO-TEST-0007',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
        ], qty: 10, unitPrice: 25000); // Total: 250.000

        PurchasePayment::create([
            'purchase_id'    => $po->id,
            'payment_date'   => now()->toDateString(),
            'amount'         => 100000,
            'payment_method' => 'TRANSFER',
            'recorded_by'    => $this->adminUser->id,
        ]);

        $po = $po->fresh(['items', 'payments']);
        $effective = $po->effectiveStatus();
        $this->assertSame('PARTIAL', $effective['key']);
        $this->assertSame('Pembayaran Sebagian', $effective['label']);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.purchases.index'));
        $response->assertOk();
        $response->assertSee('PO-TEST-0007');
        $response->assertSee('Pembayaran Sebagian');
        $this->assertStringNotContainsString('Disetujui', $response->getContent());
    }

    /**
     * Requirement 8: NOT_SUBMITTED draft renders only "Belum Diajukan".
     */
    public function test_not_submitted_draft_renders_only_belum_diajukan(): void
    {
        $po = $this->createPurchase([
            'purchase_no'     => 'PO-TEST-0008',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::NOT_SUBMITTED,
        ]);

        $effective = $po->effectiveStatus();
        $this->assertSame('NOT_SUBMITTED', $effective['key']);
        $this->assertSame('Belum Diajukan', $effective['label']);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.purchases.index'));
        $response->assertOk();
        $response->assertSee('PO-TEST-0008');
        $response->assertSee('Belum Diajukan');
    }

    /**
     * Requirement 9: No PO row renders two status badges.
     */
    public function test_no_po_row_renders_two_status_badges(): void
    {
        $this->createPurchase([
            'purchase_no'     => 'PO-TEST-0009',
            'status'          => PurchaseStatus::ORDERED,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
        ]);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.purchases.index'));
        $response->assertOk();

        $content = $response->getContent();

        // The former double badge container has been eliminated
        $this->assertStringNotContainsString('inline-flex flex-col items-center gap-1', $content);
        // Only 1 status-badge is rendered in the status cell per row
        $this->assertMatchesRegularExpression(
            '/<td class="py-2\.5 px-4 text-center whitespace-nowrap">\s*<span class="inline-flex[^>]+>.*?<\/span>\s*<\/td>/s',
            $content
        );
    }

    /**
     * Requirement 10: Approval metadata remains available in detail/audit.
     */
    public function test_approval_metadata_remains_available_in_detail_and_audit(): void
    {
        $po = $this->createPurchase([
            'purchase_no'     => 'PO-TEST-0010',
            'status'          => PurchaseStatus::ORDERED,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
            'approved_at'     => now(),
            'approved_by'     => $this->ownerUser->id,
        ]);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.purchases.show', $po));
        $response->assertOk();
        // The detail page still renders approval status and badge metadata
        $response->assertSee('Disetujui');
        $response->assertSee('Dipesan');
        $response->assertSee($po->purchase_no);
    }

    /**
     * Requirement 11: Underlying workflow statuses are unchanged.
     */
    public function test_underlying_workflow_statuses_are_unchanged(): void
    {
        $po = $this->createPurchase([
            'purchase_no'     => 'PO-TEST-0011',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
        ]);

        $this->assertSame(PurchaseStatus::DRAFT, $po->status);
        $this->assertSame(PurchaseApprovalStatus::APPROVED, $po->approval_status);

        // Effective status provides derived presentation without altering columns
        $this->assertSame('Menunggu Pembayaran', $po->effective_status['label']);
        $this->assertSame(PurchaseStatus::DRAFT, $po->fresh()->status);
        $this->assertSame(PurchaseApprovalStatus::APPROVED, $po->fresh()->approval_status);
    }

    /**
     * Requirement 12: Full payment still auto ORDERED.
     */
    public function test_full_payment_still_auto_ordered(): void
    {
        OpeningCashBalance::create([
            'amount'       => '10000000.00',
            'opening_date' => now()->toDateString(),
            'created_by'   => $this->adminUser->id,
        ]);

        $po = $this->createPurchase([
            'purchase_no'     => 'PO-TEST-0012',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
        ], qty: 2, unitPrice: 50000); // Total 100.000

        $response = $this->actingAs($this->adminUser)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '100000.00',
            'payment_date'   => now()->toDateString(),
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ]);
        $response->assertSessionHasNoErrors();

        $po->refresh();
        $this->assertSame(PurchaseStatus::ORDERED, $po->status);
        $this->assertTrue($po->isFullyPaid());
        $this->assertSame('ORDERED', $po->effective_status['key']);
        $this->assertSame('Dipesan', $po->effective_status['label']);
    }

    /**
     * Requirement 13: Receiving still results in RECEIVED.
     */
    public function test_receiving_still_results_in_received(): void
    {
        $po = $this->createPurchase([
            'purchase_no'     => 'PO-TEST-0013',
            'status'          => PurchaseStatus::ORDERED,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
        ], qty: 5, unitPrice: 20000); // Total 100.000

        PurchasePayment::create([
            'purchase_id'    => $po->id,
            'payment_date'   => now()->toDateString(),
            'amount'         => 100000,
            'payment_method' => 'TRANSFER',
            'recorded_by'    => $this->adminUser->id,
        ]);

        $receivingService = app(PurchaseReceivingService::class);
        $receivingService->receivePurchase($po, $this->gudangUser->id);

        $po->refresh();
        $this->assertSame(PurchaseStatus::RECEIVED, $po->status);
        $this->assertSame('RECEIVED', $po->effective_status['key']);
        $this->assertSame('Diterima', $po->effective_status['label']);
    }

    /**
     * Requirement: Reusable presenter maps Diterima and RECEIVED to emerald green.
     */
    public function test_status_badge_presenter_maps_diterima_and_received_to_emerald(): void
    {
        $this->assertSame('bg-emerald-50 text-emerald-700 border-emerald-200', StatusBadgePresenter::classes('RECEIVED'));
        $this->assertSame('bg-emerald-50 text-emerald-700 border-emerald-200', StatusBadgePresenter::classes('DITERIMA'));
        $this->assertSame('bg-emerald-50 text-emerald-700 border-emerald-200', StatusBadgePresenter::classes(PurchaseStatus::RECEIVED));
        $this->assertSame('bg-emerald-50 text-emerald-700 border-emerald-200', StatusBadgePresenter::classes('RECEIVED', 'purchase'));
        $this->assertSame('bg-emerald-50 text-emerald-700 border-emerald-200', StatusBadgePresenter::classes('DITERIMA', 'purchase'));
    }

    /**
     * Requirement: All purchase status colors adhere to NAYARA design tokens.
     */
    public function test_all_purchase_status_colors_match_nayara_spec(): void
    {
        // Diterima -> emerald green
        $this->assertSame('bg-emerald-50 text-emerald-700 border-emerald-200', StatusBadgePresenter::classes('RECEIVED'));
        $this->assertSame('bg-emerald-50 text-emerald-700 border-emerald-200', StatusBadgePresenter::classes('DITERIMA'));

        // Dipesan -> cyan soft
        $this->assertSame('bg-cyan-50 text-cyan-700 border-cyan-200', StatusBadgePresenter::classes('ORDERED'));
        $this->assertSame('bg-cyan-50 text-cyan-700 border-cyan-200', StatusBadgePresenter::classes('DIPESAN'));

        // Dibatalkan -> red soft
        $this->assertSame('bg-red-50 text-red-700 border-red-200', StatusBadgePresenter::classes('CANCELLED'));
        $this->assertSame('bg-red-50 text-red-700 border-red-200', StatusBadgePresenter::classes('DIBATALKAN'));

        // Menunggu Persetujuan -> amber soft
        $this->assertSame('bg-amber-50 text-amber-700 border-amber-200', StatusBadgePresenter::classes('PENDING_APPROVAL'));
        $this->assertSame('bg-amber-50 text-amber-700 border-amber-200', StatusBadgePresenter::classes('MENUNGGU PERSETUJUAN'));

        // Menunggu Pembayaran -> amber soft
        $this->assertSame('bg-amber-50 text-amber-800 border-amber-200', StatusBadgePresenter::classes('WAITING_PAYMENT'));
        $this->assertSame('bg-amber-50 text-amber-800 border-amber-200', StatusBadgePresenter::classes('MENUNGGU PEMBAYARAN'));

        // Pembayaran Sebagian -> amber soft
        $this->assertSame('bg-amber-50 text-amber-700 border-amber-200', StatusBadgePresenter::classes('PARTIAL'));
        $this->assertSame('bg-amber-50 text-amber-700 border-amber-200', StatusBadgePresenter::classes('PEMBAYARAN SEBAGIAN'));

        // Perlu Revisi -> orange soft
        $this->assertSame('bg-orange-50 text-orange-700 border-orange-200', StatusBadgePresenter::classes('REVISION_REQUIRED'));
        $this->assertSame('bg-orange-50 text-orange-700 border-orange-200', StatusBadgePresenter::classes('PERLU REVISI'));

        // Belum Diajukan -> slate neutral
        $this->assertSame('bg-slate-50 text-slate-700 border-slate-200', StatusBadgePresenter::classes('NOT_SUBMITTED'));
        $this->assertSame('bg-slate-50 text-slate-700 border-slate-200', StatusBadgePresenter::classes('BELUM DIAJUKAN'));
    }
}
