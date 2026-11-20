<?php

namespace Tests\Feature\Purchase;

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
use App\Services\Inventory\PurchaseApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PurchasePaymentFormCleanupTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $gudangUser;
    protected Supplier $supplier;
    protected Material $material;
    protected OpeningCashBalance $openingRecord;
    protected PurchaseApprovalService $approvalService;
    protected FinancialRecordService $financialRecordService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->approvalService = app(PurchaseApprovalService::class);
        $this->financialRecordService = app(FinancialRecordService::class);

        $this->adminUser = User::factory()->create([
            'role'   => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->gudangUser = User::factory()->create([
            'role'   => UserRole::GUDANG,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->supplier = Supplier::create([
            'supplier_name' => 'PT Sumber Kain Utama',
            'phone'         => '081234567890',
            'active'        => true,
        ]);

        $unit = Unit::create([
            'unit_name' => 'Meter',
            'symbol'    => 'm',
            'active'    => true,
        ]);

        $this->material = Material::create([
            'material_name' => 'Kain Katun Rayon Premium',
            'unit_id'       => $unit->id,
            'active'        => true,
        ]);

        \App\Models\Inventory\MaterialStock::create([
            'material_id'   => $this->material->id,
            'qty_on_hand'   => '0.0000',
            'avg_unit_cost' => '50000.0000',
        ]);

        $this->openingRecord = OpeningCashBalance::create([
            'opening_date' => '2026-10-01',
            'amount'       => '50000000.00',
            'notes'        => 'Saldo Awal Kas',
            'created_by'   => $this->adminUser->id,
        ]);
    }

    protected function createApprovedPurchase(string $poNo = 'PO-202610-0005', string $qty = '5.0000', string $unitPrice = '50000.00'): Purchase
    {
        $purchase = Purchase::create([
            'purchase_no'     => $poNo,
            'supplier_id'     => $this->supplier->id,
            'purchase_date'   => '2026-10-05',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::NOT_SUBMITTED,
            'created_by'      => $this->gudangUser->id,
        ]);

        PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'material_id' => $this->material->id,
            'qty'         => $qty,
            'unit_price'  => $unitPrice,
            'subtotal'    => bcmul($qty, $unitPrice, 2),
        ]);

        $this->approvalService->submitForApproval($purchase, $this->gudangUser);
        $this->approvalService->approvePurchase($purchase, $this->adminUser);

        return $purchase->fresh();
    }

    /**
     * 1. First payment view shows Total Tagihan and merged PO info.
     */
    public function test_first_payment_view_shows_total_tagihan(): void
    {
        $purchase = $this->createApprovedPurchase('PO-202610-0005', '5.0000', '50000.00'); // total 250.000

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.purchases.payments.create', $purchase));

        $response->assertOk();
        $response->assertSee('Tagihan PO');
        $response->assertSee('250.000');
        $response->assertSee($purchase->purchase_no);
        $response->assertSee($purchase->supplier->supplier_name);
        $response->assertSee('Disetujui');
        $response->assertSee($this->adminUser->name);
    }

    /**
     * 2. First payment view does NOT show "Sudah Dibayar Rp0".
     */
    public function test_first_payment_view_does_not_show_sudah_dibayar_rp0(): void
    {
        $purchase = $this->createApprovedPurchase('PO-202610-0005', '5.0000', '50000.00');

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.purchases.payments.create', $purchase));

        $response->assertOk();
        $response->assertDontSee('Sudah Dibayar');
        $response->assertDontSee('Rp 0');
    }

    /**
     * 3. First payment view does NOT show duplicate Sisa equal total.
     */
    public function test_first_payment_view_does_not_show_duplicate_sisa_equal_total(): void
    {
        $purchase = $this->createApprovedPurchase('PO-202610-0005', '5.0000', '50000.00');

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.purchases.payments.create', $purchase));

        $response->assertOk();
        $response->assertDontSee('Sisa Tagihan');
    }

    /**
     * 4. Normal state does NOT show current cash card.
     */
    public function test_normal_state_does_not_show_current_cash_card(): void
    {
        $purchase = $this->createApprovedPurchase('PO-202610-0005', '5.0000', '50000.00');

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.purchases.payments.create', $purchase));

        $response->assertOk();
        $response->assertDontSee('Kas & Maks. Bayar');
        $response->assertDontSee('Kas Saat Ini');
    }

    /**
     * 5. Normal state does NOT show "Maks. Bayar".
     */
    public function test_normal_state_does_not_show_maks_bayar(): void
    {
        $purchase = $this->createApprovedPurchase('PO-202610-0005', '5.0000', '50000.00');

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.purchases.payments.create', $purchase));

        $response->assertOk();
        $response->assertDontSee('Maks. Bayar');
        $response->assertDontSee('Maksimal saat ini:');
    }

    /**
     * 6. Normal state does NOT show "Kas Cukup".
     */
    public function test_normal_state_does_not_show_kas_cukup(): void
    {
        $purchase = $this->createApprovedPurchase('PO-202610-0005', '5.0000', '50000.00');

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.purchases.payments.create', $purchase));

        $response->assertOk();
        $response->assertDontSee('KAS CUKUP');
        $response->assertDontSee('✓ KAS CUKUP');
    }

    /**
     * 7. Partial payment state shows paid amount.
     */
    public function test_partial_payment_state_shows_paid_amount(): void
    {
        $purchase = $this->createApprovedPurchase('PO-202610-0006', '20.0000', '50000.00'); // total 1.000.000

        PurchasePayment::create([
            'purchase_id'         => $purchase->id,
            'amount'              => '400000.00',
            'payment_date'        => '2026-10-06',
            'payment_method'      => 'TRANSFER_BCA',
            'transfer_proof_path' => 'transfer-proofs/proof1.jpg',
            'created_by'          => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.purchases.payments.create', $purchase));

        $response->assertOk();
        $response->assertSee('Dibayar Sebagian');
        $response->assertSee('1.000.000');
        $response->assertSee('Dibayar');
        $response->assertSee('400.000');
    }

    /**
     * 8. Partial payment state shows remaining outstanding.
     */
    public function test_partial_payment_state_shows_remaining_outstanding(): void
    {
        $purchase = $this->createApprovedPurchase('PO-202610-0006', '20.0000', '50000.00'); // total 1.000.000

        PurchasePayment::create([
            'purchase_id'         => $purchase->id,
            'amount'              => '400000.00',
            'payment_date'        => '2026-10-06',
            'payment_method'      => 'TRANSFER_BCA',
            'transfer_proof_path' => 'transfer-proofs/proof1.jpg',
            'created_by'          => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.purchases.payments.create', $purchase));

        $response->assertOk();
        $response->assertSee('Sisa');
        $response->assertSee('600.000');
    }

    /**
     * 9. Insufficient cash state shows compact warning.
     */
    public function test_insufficient_cash_state_shows_compact_warning(): void
    {
        $purchase = $this->createApprovedPurchase('PO-202610-0007', '12.0000', '50000.00'); // total 600.000

        // Cash becomes insufficient (300.000 vs 600.000)
        $this->openingRecord->update([
            'amount' => '300000.00',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.purchases.payments.create', $purchase));

        $response->assertOk();
        $response->assertSee('Kas tidak mencukupi untuk melunasi tagihan ini.');
        $response->assertSee('Kas tersedia: Rp 300.000 · Sisa tagihan: Rp 600.000');
    }

    /**
     * 10. Cash backend validation remains active.
     */
    public function test_cash_backend_validation_remains_active(): void
    {
        Storage::fake('local');
        $purchase = $this->createApprovedPurchase('PO-202610-0008', '12.0000', '50000.00'); // total 600.000

        // Cash becomes insufficient (300.000 vs 600.000)
        $this->openingRecord->update([
            'amount' => '300000.00',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.purchases.payments.store', $purchase), [
                'amount'         => '400000.00', // exceeds cash 300.000
                'payment_date'   => '2026-10-09',
                'payment_method' => 'TRANSFER_BCA',
                'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
            ]);

        $response->assertSessionHasErrors('amount');
        $this->assertEquals(0, PurchasePayment::where('purchase_id', $purchase->id)->count());
    }

    /**
     * 11. Outstanding validation remains active.
     */
    public function test_outstanding_validation_remains_active(): void
    {
        Storage::fake('local');
        $purchase = $this->createApprovedPurchase('PO-202610-0009', '10.0000', '50000.00'); // total 500.000

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.purchases.payments.store', $purchase), [
                'amount'         => '700000.00', // exceeds outstanding 500.000
                'payment_date'   => '2026-10-09',
                'payment_method' => 'TRANSFER_BCA',
                'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
            ]);

        $response->assertSessionHasErrors('amount');
        $this->assertEquals(0, PurchasePayment::where('purchase_id', $purchase->id)->count());
    }

    /**
     * 12. Full payment still auto sets ORDERED.
     */
    public function test_full_payment_still_auto_sets_ordered(): void
    {
        Storage::fake('local');
        $purchase = $this->createApprovedPurchase('PO-202610-0010', '10.0000', '50000.00'); // total 500.000

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.purchases.payments.store', $purchase), [
                'amount'         => '500000.00',
                'payment_date'   => '2026-10-09',
                'payment_method' => 'TRANSFER_BCA',
                'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
            ]);

        $response->assertRedirect(route('admin.financial-records.index'));
        $fresh = $purchase->fresh();
        $this->assertTrue($fresh->isFullyPaid());
        $this->assertEquals(PurchaseStatus::ORDERED, $fresh->status);
    }

    /**
     * 13. Transfer proof remains required.
     */
    public function test_transfer_proof_remains_required(): void
    {
        $purchase = $this->createApprovedPurchase('PO-202610-0011', '10.0000', '50000.00');

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.purchases.payments.store', $purchase), [
                'amount'         => '200000.00',
                'payment_date'   => '2026-10-09',
                'payment_method' => 'TRANSFER_BCA',
                // Missing transfer_proof
            ]);

        $response->assertSessionHasErrors('transfer_proof');
        $this->assertEquals(0, PurchasePayment::where('purchase_id', $purchase->id)->count());
    }

    /**
     * 14. Stock unchanged until receiving.
     */
    public function test_stock_unchanged_until_receiving(): void
    {
        Storage::fake('local');
        $purchase = $this->createApprovedPurchase('PO-202610-0012', '10.0000', '50000.00');

        $stock = \App\Models\Inventory\MaterialStock::where('material_id', $this->material->id)->first();
        $this->assertEquals('0.0000', $stock->qty_on_hand);

        // Record full payment
        $this->actingAs($this->adminUser)
            ->post(route('admin.purchases.payments.store', $purchase), [
                'amount'         => '500000.00',
                'payment_date'   => '2026-10-09',
                'payment_method' => 'TRANSFER_BCA',
                'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
            ]);

        // Stock must remain 0.0000 until Gudang actually receives the items
        $stockFresh = \App\Models\Inventory\MaterialStock::where('material_id', $this->material->id)->first();
        $this->assertEquals('0.0000', $stockFresh->qty_on_hand);
    }

    /**
     * 15. Form does not render No. Referensi and placeholder.
     */
    public function test_form_does_not_render_no_referensi(): void
    {
        $purchase = $this->createApprovedPurchase('PO-202610-0013');

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.purchases.payments.create', $purchase));

        $response->assertOk();
        $response->assertDontSee('No. Referensi');
        $response->assertDontSee('reference_no');
        $response->assertDontSee('TRF-20261007-001');
    }

    /**
     * 16. Successful purchase payment sets concise human-readable message without (ORDERED).
     */
    public function test_successful_purchase_payment_sets_concise_human_readable_message(): void
    {
        Storage::fake('local');
        $purchase = $this->createApprovedPurchase('PO-202610-0014', '5.0000', '50000.00'); // total 250.000

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.purchases.payments.store', $purchase), [
                'amount'         => '250000.00',
                'payment_date'   => '2026-10-09',
                'payment_method' => 'TRANSFER_BCA',
                'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
            ]);

        $response->assertRedirect(route('admin.financial-records.index'));
        $response->assertSessionHas('success', 'Pembayaran Rp250.000 untuk PO-202610-0014 berhasil. PO kini Dipesan.');

        $flash = session('success');
        $this->assertStringNotContainsString('(ORDERED)', $flash);
        $this->assertStringNotContainsString('LUNAS dan status otomatis berubah menjadi', $flash);
        $this->assertStringContainsString('Dipesan', $flash);
    }

    /**
     * 17. Financial records renders flash message exactly once.
     */
    public function test_financial_records_renders_flash_message_exactly_once(): void
    {
        $message = 'Pembayaran Rp250.000 untuk PO-202610-0005 berhasil. PO kini Dipesan.';

        $response = $this->actingAs($this->adminUser)
            ->withSession(['success' => $message])
            ->get(route('admin.financial-records.index'));

        $response->assertOk();
        $content = $response->getContent();

        // Must appear exactly once in the rendered HTML
        $this->assertEquals(1, substr_count($content, $message));
    }
}
