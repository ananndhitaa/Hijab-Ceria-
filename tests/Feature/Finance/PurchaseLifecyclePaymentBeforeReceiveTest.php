<?php

namespace Tests\Feature\Finance;

use App\Enums\MovementType;
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
use App\Services\Inventory\PurchaseOrderService;
use App\Services\Inventory\PurchaseReceivingService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PurchaseLifecyclePaymentBeforeReceiveTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $gudang;
    private Supplier $supplier;
    private Material $material;
    private PurchaseOrderService $orderService;
    private PurchaseReceivingService $receivingService;
    private FinancialRecordService $financeService;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->orderService = app(PurchaseOrderService::class);
        $this->receivingService = app(PurchaseReceivingService::class);
        $this->financeService = app(FinancialRecordService::class);

        $this->admin = User::factory()->create([
            'role'   => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
            'name'   => 'Admin Keuangan',
        ]);

        $this->gudang = User::factory()->create([
            'role'   => UserRole::GUDANG,
            'status' => UserStatus::ACTIVE,
            'name'   => 'Staff Gudang',
        ]);

        $this->supplier = Supplier::create([
            'supplier_name' => 'PT Tekstil Jaya Abadi',
            'active'        => true,
        ]);

        $unit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);

        $this->material = Material::create([
            'unit_id'       => $unit->id,
            'material_name' => 'Kain Katun Rayon',
            'min_stock'     => '10.0000',
            'active'        => true,
        ]);

        MaterialStock::create([
            'material_id'   => $this->material->id,
            'qty_on_hand'   => '100.0000',
            'avg_unit_cost' => '20000.0000',
        ]);
    }

    private function createDraftPurchase(string $qty = '50.0000', string $unitPrice = '30000.00'): Purchase
    {
        return $this->orderService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-10-01',
            'items'         => [
                [
                    'material_id' => $this->material->id,
                    'qty'         => $qty,
                    'unit_price'  => $unitPrice,
                ],
            ],
        ], $this->gudang);
    }

    private function createApprovedPurchase(string $qty = '50.0000', string $unitPrice = '30000.00'): Purchase
    {
        $po = $this->createDraftPurchase($qty, $unitPrice);
        $po->update([
            'approval_status' => \App\Enums\PurchaseApprovalStatus::APPROVED,
            'approved_at'     => now(),
        ]);
        return $po;
    }

    private function setupCash(string $amount = '10000000.00', string $date = '2026-10-01'): OpeningCashBalance
    {
        return OpeningCashBalance::create([
            'amount'       => $amount,
            'opening_date' => $date,
            'created_by'   => $this->admin->id,
        ]);
    }

    private function postPayment(Purchase $po, string $amount, string $paymentDate = '2026-10-02', ?UploadedFile $proof = null)
    {
        return $this->actingAs($this->admin)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => $amount,
            'payment_date'   => $paymentDate,
            'transfer_proof' => $proof ?? UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ]);
    }

    /**
     * 1. DRAFT PO cannot be paid.
     */
    public function test_01_draft_po_cannot_be_paid(): void
    {
        $this->setupCash('10000000.00');
        $draftPo = $this->createDraftPurchase();

        $response = $this->postPayment($draftPo, '500000.00');
        $response->assertSessionHasErrors('amount');
    }

    /**
     * 2. DRAFT PO cannot be received.
     */
    public function test_02_draft_po_cannot_be_received(): void
    {
        $draftPo = $this->createDraftPurchase();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Hanya Purchase Order berstatus ORDERED yang dapat diterima');

        $this->receivingService->receivePurchase($draftPo, $this->gudang->id);
    }

    /**
     * 3. ORDERED unpaid PO: payment action available, receive blocked.
     */
    public function test_03_ordered_unpaid_po_payment_available_receive_blocked(): void
    {
        $this->setupCash('10000000.00');
        $po = $this->createApprovedPurchase('50.0000', '30000.00'); // Total: 1,500,000
        $po->update(['status' => PurchaseStatus::ORDERED]);

        $this->assertEquals(PurchaseStatus::ORDERED, $po->fresh()->status);
        $this->assertEquals('1500000.00', $po->fresh()->outstanding_amount);
        $this->assertFalse($po->fresh()->isFullyPaid());

        // Admin can view payment form
        $response = $this->actingAs($this->admin)->get(route('admin.purchases.payments.create', $po));
        $response->assertOk();

        // Gudang cannot receive unpaid PO (blocked by domain exception)
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Barang belum dapat diterima karena pembayaran supplier belum lunas.');

        $this->receivingService->receivePurchase($po, $this->gudang->id);
    }

    /**
     * 4. ORDERED partially paid PO: receive blocked.
     */
    public function test_04_ordered_partially_paid_po_receive_blocked(): void
    {
        $this->setupCash('10000000.00');
        $po = $this->createApprovedPurchase('100.0000', '50000.00'); // Total: 5,000,000
        $po->update(['status' => PurchaseStatus::ORDERED]);

        // Pay partial 2,000,000
        $this->postPayment($po, '2000000.00');

        $freshPo = $po->fresh();
        $this->assertEquals('2000000.00', $freshPo->paid_amount);
        $this->assertEquals('3000000.00', $freshPo->outstanding_amount);
        $this->assertFalse($freshPo->isFullyPaid());

        // Gudang receive attempt is blocked
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Barang belum dapat diterima karena pembayaran supplier belum lunas.');

        $this->receivingService->receivePurchase($freshPo, $this->gudang->id);
    }

    /**
     * 5. ORDERED fully paid PO: receive allowed.
     */
    public function test_05_ordered_fully_paid_po_receive_allowed(): void
    {
        $this->setupCash('10000000.00');
        $po = $this->createApprovedPurchase('100.0000', '50000.00'); // Total: 5,000,000

        // Payment 1: 2,000,000
        $this->postPayment($po, '2000000.00');

        // Payment 2: 3,000,000 (settles outstanding)
        $this->postPayment($po, '3000000.00', '2026-10-03');

        $freshPo = $po->fresh();
        $this->assertEquals('5000000.00', $freshPo->paid_amount);
        $this->assertEquals('0.00', $freshPo->outstanding_amount);
        $this->assertTrue($freshPo->isFullyPaid());
        $this->assertEquals(PurchaseStatus::ORDERED, $freshPo->status);

        // Gudang can now receive PO
        $received = $this->receivingService->receivePurchase($freshPo->fresh(), $this->gudang->id);
        $this->assertEquals(PurchaseStatus::RECEIVED, $received->status);
    }

    /**
     * 6. Supplier payment reduces cash.
     */
    public function test_06_supplier_payment_reduces_cash(): void
    {
        $this->setupCash('10000000.00');
        $po = $this->createApprovedPurchase('50.0000', '40000.00'); // Total: 2,000,000

        $before = $this->financeService->getConsolidatedRecords();
        $this->assertEquals('10000000.00', $before['cash_position']['kas_tersedia']);

        $this->postPayment($po, '2000000.00');

        $after = $this->financeService->getConsolidatedRecords();
        $this->assertEquals('8000000.00', $after['cash_position']['kas_tersedia']);
        $this->assertEquals('2000000.00', $after['cash_position']['all_time_keluar']);
    }

    /**
     * 7. Supplier payment does not alter stock.
     */
    public function test_07_supplier_payment_does_not_alter_stock(): void
    {
        $this->setupCash('10000000.00');
        $po = $this->createApprovedPurchase('50.0000', '40000.00');

        $stockBefore = $this->material->fresh(['stock'])->stock->qty_on_hand;
        $this->assertEquals('100.0000', $stockBefore);

        $this->postPayment($po, '2000000.00');

        $stockAfter = $this->material->fresh(['stock'])->stock->qty_on_hand;
        $this->assertEquals('100.0000', $stockAfter);
    }

    /**
     * 8. Supplier payment does not create StockMovement.
     */
    public function test_08_supplier_payment_does_not_create_stock_movement(): void
    {
        $this->setupCash('10000000.00');
        $po = $this->createApprovedPurchase('50.0000', '40000.00');

        $this->assertEquals(0, StockMovement::count());

        $this->postPayment($po, '2000000.00');

        $this->assertEquals(0, StockMovement::count());
    }

    /**
     * 9. Receive increases stock.
     */
    public function test_09_receive_increases_stock(): void
    {
        $this->setupCash('10000000.00');
        $po = $this->createApprovedPurchase('50.0000', '40000.00');

        // Pay fully first
        $this->postPayment($po, '2000000.00');

        $this->assertEquals('100.0000', $this->material->fresh(['stock'])->stock->qty_on_hand);

        // Receive
        $this->receivingService->receivePurchase($po->fresh(), $this->gudang->id);

        $this->assertEquals('150.0000', $this->material->fresh(['stock'])->stock->qty_on_hand);
    }

    /**
     * 10. Receive recalculates MWA.
     */
    public function test_10_receive_recalculates_mwa(): void
    {
        // Initial stock: 100 m @ Rp20.000 = Rp2.000.000
        $this->setupCash('10000000.00');
        $po = $this->createApprovedPurchase('50.0000', '50000.00'); // 50 m @ Rp50.000 = Rp2.500.000

        // Pay fully
        $this->postPayment($po, '2500000.00');

        // Receive
        $this->receivingService->receivePurchase($po->fresh(), $this->gudang->id);

        // Expected MWA: (2,000,000 + 2,500,000) / 150 = 4,500,000 / 150 = 30000.0000
        $stock = $this->material->fresh(['stock'])->stock;
        $this->assertEquals('150.0000', $stock->qty_on_hand);
        $this->assertEquals('30000.0000', $stock->avg_unit_cost);
    }

    /**
     * 11. Receive does not reduce cash again.
     */
    public function test_11_receive_does_not_reduce_cash_again(): void
    {
        $this->setupCash('10000000.00');
        $po = $this->createApprovedPurchase('50.0000', '40000.00'); // Total: 2,000,000

        // Pay fully
        $this->postPayment($po, '2000000.00');

        $cashBeforeReceive = $this->financeService->getConsolidatedRecords()['cash_position']['kas_tersedia'];
        $this->assertEquals('8000000.00', $cashBeforeReceive);

        // Receive
        $this->receivingService->receivePurchase($po->fresh(), $this->gudang->id);

        $cashAfterReceive = $this->financeService->getConsolidatedRecords()['cash_position']['kas_tersedia'];
        $this->assertEquals('8000000.00', $cashAfterReceive);
        $this->assertEquals('2000000.00', $this->financeService->getConsolidatedRecords()['cash_position']['all_time_keluar']);
    }

    /**
     * 12. Cancelled PO cannot be paid.
     */
    public function test_12_cancelled_po_cannot_be_paid(): void
    {
        $this->setupCash('10000000.00');
        $po = $this->createApprovedPurchase('50.0000', '40000.00');
        $this->orderService->cancelPurchase($po, 'Dibatalkan oleh user', $this->gudang);

        $this->assertEquals(PurchaseStatus::CANCELLED, $po->fresh()->status);

        $response = $this->postPayment($po, '1000000.00');
        $response->assertSessionHasErrors('amount');
    }

    /**
     * 13. Cancelled PO cannot be received.
     */
    public function test_13_cancelled_po_cannot_be_received(): void
    {
        $po = $this->createDraftPurchase('50.0000', '40000.00');
        $this->orderService->cancelPurchase($po, 'Dibatalkan oleh user', $this->gudang);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Hanya Purchase Order berstatus ORDERED yang dapat diterima');

        $this->receivingService->receivePurchase($po, $this->gudang->id);
    }

    /**
     * 14. Historical RECEIVED PO without PurchasePayment remains valid.
     */
    public function test_14_historical_received_po_without_purchase_payment_remains_valid(): void
    {
        // Simulate legacy received PO created prior to payment feature
        $legacyPo = Purchase::create([
            'supplier_id'   => $this->supplier->id,
            'purchase_no'   => 'PO-LEGACY-001',
            'purchase_date' => '2026-08-01',
            'status'        => PurchaseStatus::RECEIVED,
        ]);
        $item = PurchaseItem::create([
            'purchase_id'  => $legacyPo->id,
            'material_id'  => $this->material->id,
            'qty'          => '50.0000',
            'unit_price'   => '20000.00',
            'qty_received' => '50.0000',
        ]);

        $this->assertEquals(PurchaseStatus::RECEIVED, $legacyPo->status);
        $this->assertEquals('1000000.00', $legacyPo->total_amount_string);
        $this->assertEquals('0.00', $legacyPo->paid_amount);
        $this->assertEquals('1000000.00', $legacyPo->outstanding_amount);

        // Legacy PO is untouched and preserved
        $this->assertDatabaseHas('purchases', ['id' => $legacyPo->id, 'status' => PurchaseStatus::RECEIVED]);
        $this->assertEquals(0, $legacyPo->payments()->count());
    }

    /**
     * 15. Historical data is not automatically changed.
     */
    public function test_15_historical_data_is_not_automatically_changed(): void
    {
        $legacyPo = Purchase::create([
            'supplier_id'   => $this->supplier->id,
            'purchase_no'   => 'PO-HISTORICAL-002',
            'purchase_date' => '2026-08-15',
            'status'        => PurchaseStatus::RECEIVED,
        ]);
        PurchaseItem::create([
            'purchase_id'  => $legacyPo->id,
            'material_id'  => $this->material->id,
            'qty'          => '30.0000',
            'unit_price'   => '25000.00',
            'qty_received' => '30.0000',
        ]);

        // Calling financial service consolidation must not mutate historical records
        $this->financeService->getConsolidatedRecords();

        $fresh = $legacyPo->fresh();
        $this->assertEquals(PurchaseStatus::RECEIVED, $fresh->status);
        $this->assertEquals('2026-08-15', $fresh->purchase_date->toDateString());
        $this->assertEquals(0, PurchasePayment::count());
    }

    /**
     * 16. Full payment + receive creates exactly one cash-out financial event and one Stock IN physical event.
     */
    public function test_16_full_payment_and_receive_creates_exactly_one_cash_out_and_one_stock_in(): void
    {
        $this->setupCash('10000000.00');
        $po = $this->createApprovedPurchase('50.0000', '40000.00'); // Total: 2,000,000

        // 1. Pay supplier (Cash-out event)
        $this->postPayment($po, '2000000.00');

        $this->assertEquals(1, PurchasePayment::count());
        $this->assertEquals(0, StockMovement::count());

        // 2. Receive material (Stock-in event)
        $this->receivingService->receivePurchase($po->fresh(), $this->gudang->id);

        $this->assertEquals(1, PurchasePayment::count());
        $this->assertEquals(1, StockMovement::where('movement_type', MovementType::IN)->count());

        // Verify cash records only have exactly one cash-out row
        $records = $this->financeService->getConsolidatedRecords()['records'];
        $cashOutRecords = array_filter($records->items(), fn($r) => $r['type'] === 'KELUAR');
        $this->assertCount(1, $cashOutRecords);
    }

    /**
     * 17. Double receiving remains blocked.
     */
    public function test_17_double_receiving_remains_blocked(): void
    {
        $this->setupCash('10000000.00');
        $po = $this->createApprovedPurchase('50.0000', '40000.00');

        // Pay fully
        $this->postPayment($po, '2000000.00');

        // First receive succeeds
        $this->receivingService->receivePurchase($po->fresh(), $this->gudang->id);
        $this->assertEquals('150.0000', $this->material->fresh(['stock'])->stock->qty_on_hand);
        $this->assertEquals(1, StockMovement::count());

        // Second receive fails
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Hanya Purchase Order berstatus ORDERED yang dapat diterima');

        $this->receivingService->receivePurchase($po->fresh(), $this->gudang->id);

        // Stock does not increase again
        $this->assertEquals('150.0000', $this->material->fresh(['stock'])->stock->qty_on_hand);
        $this->assertEquals(1, StockMovement::count());
    }

    /**
     * 18. Hard cash guard remains active.
     */
    public function test_18_hard_cash_guard_remains_active(): void
    {
        // Opening cash is 1,000,000
        $this->setupCash('1000000.00', '2026-10-01');

        // PO is 5,000,000
        $po = $this->createApprovedPurchase('100.0000', '50000.00');

        // Admin attempts to pay 2,000,000 on or after opening date -> exceeds cash (1,000,000)
        $response = $this->postPayment($po, '2000000.00');
        $response->assertSessionHasErrors('amount');
    }
}
