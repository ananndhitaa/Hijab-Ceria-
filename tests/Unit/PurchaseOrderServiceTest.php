<?php

namespace Tests\Unit;

use App\Enums\PurchaseStatus;
use App\Models\Inventory\MaterialStock;
use App\Models\Inventory\Purchase;
use App\Models\Inventory\PurchaseItem;
use App\Models\Inventory\StockMovement;
use App\Models\Master\Material;
use App\Models\Master\Supplier;
use App\Models\Master\Unit;
use App\Services\Inventory\PurchaseOrderService;
use App\Services\System\DocumentNumberGenerator;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private PurchaseOrderService $service;
    private Supplier $activeSupplier;
    private Supplier $inactiveSupplier;
    private Material $kainVoal;
    private Material $benang;
    private Material $inactiveMaterial;
    private Unit $meter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PurchaseOrderService::class);

        $this->meter = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);

        $this->activeSupplier = Supplier::create([
            'supplier_name' => 'PT. Tekstil Maju',
            'phone'         => '021-1234567',
            'active'        => true,
        ]);

        $this->inactiveSupplier = Supplier::create([
            'supplier_name' => 'CV. Supplier Lama',
            'phone'         => '021-9999999',
            'active'        => false,
        ]);

        $this->kainVoal = Material::create([
            'unit_id'       => $this->meter->id,
            'material_name' => 'Kain Voal Premium',
            'min_stock'     => 50,
            'active'        => true,
        ]);
        MaterialStock::create(['material_id' => $this->kainVoal->id, 'qty_on_hand' => 100, 'avg_unit_cost' => 35000]);

        $this->benang = Material::create([
            'unit_id'       => $this->meter->id,
            'material_name' => 'Benang Jahit',
            'min_stock'     => 10,
            'active'        => true,
        ]);
        MaterialStock::create(['material_id' => $this->benang->id, 'qty_on_hand' => 20, 'avg_unit_cost' => 15000]);

        $this->inactiveMaterial = Material::create([
            'unit_id'       => $this->meter->id,
            'material_name' => 'Kain Lama Discontinued',
            'min_stock'     => 10,
            'active'        => false,
        ]);
        MaterialStock::create(['material_id' => $this->inactiveMaterial->id, 'qty_on_hand' => 0, 'avg_unit_cost' => 20000]);
    }

    private function approveAndPay(Purchase $purchase): Purchase
    {
        $purchase->update([
            'status'          => PurchaseStatus::ORDERED,
            'approval_status' => \App\Enums\PurchaseApprovalStatus::APPROVED,
        ]);

        \App\Models\Inventory\PurchasePayment::create([
            'purchase_id'    => $purchase->id,
            'amount'         => $purchase->total_amount_string,
            'payment_date'   => '2026-08-28',
            'payment_method' => 'TRANSFER',
        ]);

        return $purchase->fresh(['items', 'payments', 'supplier']);
    }

    public function test_create_valid_draft_purchase_order_with_correct_numbering(): void
    {
        $data = [
            'supplier_id'   => $this->activeSupplier->id,
            'purchase_date' => '2026-08-28',
            'notes'         => 'Catatan pengadaan bahan baku',
            'items'         => [
                [
                    'material_id' => $this->kainVoal->id,
                    'qty'         => '50.0000',
                    'unit_price'  => '35000.00',
                ],
                [
                    'material_id' => $this->benang->id,
                    'qty'         => '10.0000',
                    'unit_price'  => '15000.00',
                ],
            ],
        ];

        $purchase = $this->service->createPurchase($data);

        $this->assertEquals(PurchaseStatus::DRAFT, $purchase->status);
        $this->assertMatchesRegularExpression('/^PO-\d{6}-\d{4}$/', $purchase->purchase_no);
        $this->assertEquals($this->activeSupplier->id, $purchase->supplier_id);
        $this->assertCount(2, $purchase->items);

        // Verify items initialized with qty_received = 0
        foreach ($purchase->items as $item) {
            $this->assertEquals('0.0000', $item->qty_received);
        }

        // Verify total calculation: (50 * 35000) + (10 * 15000) = 1,750,000 + 150,000 = 1,900,000.00
        $this->assertEquals('1900000.00', $this->service->calculateTotal($purchase));
    }

    public function test_create_fails_if_supplier_inactive(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Supplier yang dipilih tidak aktif');

        $this->service->createPurchase([
            'supplier_id'   => $this->inactiveSupplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [
                ['material_id' => $this->kainVoal->id, 'qty' => 10, 'unit_price' => 35000],
            ],
        ]);
    }

    public function test_create_fails_if_material_inactive(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('tidak aktif dan tidak dapat dipilih');

        $this->service->createPurchase([
            'supplier_id'   => $this->activeSupplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [
                ['material_id' => $this->inactiveMaterial->id, 'qty' => 10, 'unit_price' => 20000],
            ],
        ]);
    }

    public function test_create_fails_on_duplicate_materials(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Terdapat material duplikat');

        $this->service->createPurchase([
            'supplier_id'   => $this->activeSupplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [
                ['material_id' => $this->kainVoal->id, 'qty' => 10, 'unit_price' => 35000],
                ['material_id' => $this->kainVoal->id, 'qty' => 20, 'unit_price' => 35000],
            ],
        ]);
    }

    public function test_create_fails_on_invalid_quantity_or_price(): void
    {
        $this->expectException(DomainException::class);

        $this->service->createPurchase([
            'supplier_id'   => $this->activeSupplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [
                ['material_id' => $this->kainVoal->id, 'qty' => 0, 'unit_price' => 35000],
            ],
        ]);
    }

    public function test_update_draft_po_retains_immutable_po_number(): void
    {
        $purchase = $this->service->createPurchase([
            'supplier_id'   => $this->activeSupplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [
                ['material_id' => $this->kainVoal->id, 'qty' => 10, 'unit_price' => 35000],
            ],
        ]);

        $originalPoNo = $purchase->purchase_no;

        $updated = $this->service->updatePurchase($purchase, [
            'supplier_id'   => $this->activeSupplier->id,
            'purchase_date' => '2026-08-30',
            'notes'         => 'Catatan diperbarui',
            'items'         => [
                ['material_id' => $this->kainVoal->id, 'qty' => 25, 'unit_price' => 36000],
                ['material_id' => $this->benang->id, 'qty' => 5, 'unit_price' => 16000],
            ],
        ]);

        $this->assertEquals($originalPoNo, $updated->purchase_no);
        $this->assertEquals('2026-08-30', $updated->purchase_date->format('Y-m-d'));
        $this->assertEquals('Catatan diperbarui', $updated->notes);
        $this->assertCount(2, $updated->items);
        $this->assertEquals('980000.00', $this->service->calculateTotal($updated));
    }

    public function test_update_draft_po_allows_unchanged_deactivated_master(): void
    {
        $purchase = $this->service->createPurchase([
            'supplier_id'   => $this->activeSupplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [
                ['material_id' => $this->kainVoal->id, 'qty' => 10, 'unit_price' => 35000],
            ],
        ]);

        $item = $purchase->items->first();

        // Deactivate supplier and material AFTER draft creation
        $this->activeSupplier->update(['active' => false]);
        $this->kainVoal->update(['active' => false]);

        // Simple edit (changing notes and qty of existing item) should not fail on draft
        $updated = $this->service->updatePurchase($purchase, [
            'supplier_id'   => $this->activeSupplier->id,
            'purchase_date' => '2026-08-28',
            'notes'         => 'Catatan baru draft',
            'items'         => [
                ['purchase_item_id' => $item->id, 'material_id' => $this->kainVoal->id, 'qty' => 15, 'unit_price' => 35000],
            ],
        ]);

        $this->assertEquals('Catatan baru draft', $updated->notes);
        $this->assertEquals('15.0000', $updated->items->first()->qty);
    }

    public function test_update_draft_fails_if_injecting_cross_purchase_item_id(): void
    {
        $purchase1 = $this->service->createPurchase([
            'supplier_id'   => $this->activeSupplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [['material_id' => $this->kainVoal->id, 'qty' => 10, 'unit_price' => 35000]],
        ]);

        $purchase2 = $this->service->createPurchase([
            'supplier_id'   => $this->activeSupplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [['material_id' => $this->benang->id, 'qty' => 5, 'unit_price' => 15000]],
        ]);

        $item2 = $purchase2->items->first();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Item pembelian tidak valid atau bukan milik PO ini');

        $this->service->updatePurchase($purchase1, [
            'supplier_id'   => $this->activeSupplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [
                ['purchase_item_id' => $item2->id, 'material_id' => $this->kainVoal->id, 'qty' => 10, 'unit_price' => 35000],
            ],
        ]);
    }

    public function test_ordered_po_is_locked_against_edits(): void
    {
        $purchase = $this->service->createPurchase([
            'supplier_id'   => $this->activeSupplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [
                ['material_id' => $this->kainVoal->id, 'qty' => 50, 'unit_price' => 35000],
            ],
        ]);

        $purchase = $this->approveAndPay($purchase);
        $this->assertEquals(PurchaseStatus::ORDERED, $purchase->status);

        // Edit on ORDERED PO must fail
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('tidak dapat diubah');

        $this->service->updatePurchase($purchase, [
            'supplier_id'   => $this->activeSupplier->id,
            'purchase_date' => '2026-08-29',
            'items'         => [['material_id' => $this->kainVoal->id, 'qty' => 60, 'unit_price' => 35000]],
        ]);
    }

    public function test_confirm_purchase_action_is_obsolete_and_throws_domain_exception(): void
    {
        $purchase = $this->service->createPurchase([
            'supplier_id'   => $this->activeSupplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [
                ['material_id' => $this->kainVoal->id, 'qty' => 50, 'unit_price' => 35000],
            ],
        ]);

        $this->assertFalse($this->service->canConfirm($purchase));

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Alur konfirmasi manual ke supplier telah ditiadakan');

        $this->service->confirmPurchase($purchase);
    }

    public function test_cancel_draft_and_ordered_purchase_order(): void
    {
        // Cancel Draft
        $purchaseDraft = $this->service->createPurchase([
            'supplier_id'   => $this->activeSupplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [['material_id' => $this->kainVoal->id, 'qty' => 10, 'unit_price' => 35000]],
        ]);
        $cancelledDraft = $this->service->cancelPurchase($purchaseDraft);
        $this->assertEquals(PurchaseStatus::CANCELLED, $cancelledDraft->status);

        // Cancel Ordered
        $purchaseOrdered = $this->service->createPurchase([
            'supplier_id'   => $this->activeSupplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [['material_id' => $this->benang->id, 'qty' => 5, 'unit_price' => 15000]],
        ]);
        $purchaseOrdered = $this->approveAndPay($purchaseOrdered);
        $cancelledOrdered = $this->service->cancelPurchase($purchaseOrdered);
        $this->assertEquals(PurchaseStatus::CANCELLED, $cancelledOrdered->status);

        // Repeated cancel fails
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('PO sudah dalam status Dibatalkan');
        $this->service->cancelPurchase($cancelledOrdered);
    }

    public function test_cancel_ordered_fails_if_qty_received_greater_than_zero(): void
    {
        $purchase = $this->service->createPurchase([
            'supplier_id'   => $this->activeSupplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [['material_id' => $this->kainVoal->id, 'qty' => 10, 'unit_price' => 35000]],
        ]);
        $purchase = $this->approveAndPay($purchase);

        // Simulate partial received (future proofing)
        $purchase->items()->first()->update(['qty_received' => '2.0000']);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('sudah memiliki kuantitas barang yang diterima');

        $this->service->cancelPurchase($purchase);
    }

    public function test_no_stock_movement_or_physical_stock_mutation_occurs(): void
    {
        $initialStock = $this->kainVoal->fresh(['stock'])->stock->qty_on_hand;

        $purchase = $this->service->createPurchase([
            'supplier_id'   => $this->activeSupplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [['material_id' => $this->kainVoal->id, 'qty' => 100, 'unit_price' => 35000]],
        ]);

        $this->assertEquals($initialStock, $this->kainVoal->fresh(['stock'])->stock->qty_on_hand);
        $this->assertEquals(0, StockMovement::count());

        $purchase = $this->approveAndPay($purchase);

        $this->assertEquals($initialStock, $this->kainVoal->fresh(['stock'])->stock->qty_on_hand);
        $this->assertEquals(0, StockMovement::count());

        $this->service->cancelPurchase($purchase);

        $this->assertEquals($initialStock, $this->kainVoal->fresh(['stock'])->stock->qty_on_hand);
        $this->assertEquals(0, StockMovement::count());
    }
}
