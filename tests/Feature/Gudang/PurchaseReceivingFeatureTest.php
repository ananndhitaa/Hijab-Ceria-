<?php

namespace Tests\Feature\Gudang;

use App\Enums\BatchStatus;
use App\Enums\MovementType;
use App\Enums\OrderStatus;
use App\Enums\PurchaseStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Inventory\PurchasePayment;
use App\Models\Inventory\MaterialStock;
use App\Models\Inventory\Purchase;
use App\Models\Inventory\StockMovement;
use App\Models\Master\BomItem;
use App\Models\Master\Customer;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Master\Supplier;
use App\Models\Master\Unit;
use App\Models\Production\BatchMaterial;
use App\Models\Production\ProductionBatch;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\User;
use App\Services\Inventory\BatchMaterialPlanningService;
use App\Services\Inventory\PurchaseOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseReceivingFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $gudangUser;
    private Supplier $supplier;
    private Material $material;
    private Unit $meter;
    private PurchaseOrderService $poService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->poService = app(PurchaseOrderService::class);

        $this->gudangUser = User::factory()->create([
            'role'   => UserRole::GUDANG,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->meter = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);

        $this->supplier = Supplier::create([
            'supplier_name' => 'PT. Tekstil Maju',
            'active'        => true,
        ]);

        $this->material = Material::create([
            'unit_id'       => $this->meter->id,
            'material_name' => 'Kain Katun Premium',
            'min_stock'     => 20,
            'active'        => true,
        ]);
        MaterialStock::create([
            'material_id'   => $this->material->id,
            'qty_on_hand'   => '100.0000',
            'avg_unit_cost' => '40000.0000',
        ]);
    }

    private function payPurchase(Purchase $purchase, ?string $amount = null): PurchasePayment
    {
        $paidAmount = $amount ?? $purchase->total_amount;
        $purchase->load(['items', 'payments']);
        $totalPaidAfter = bcadd((string) $purchase->paid_amount, (string) $paidAmount, 2);
        $isFull = bccomp($totalPaidAfter, (string) $purchase->total_amount, 2) >= 0;
        $updateData = [
            'approval_status' => \App\Enums\PurchaseApprovalStatus::APPROVED,
            'approved_at'     => now(),
        ];
        if ($isFull) {
            $updateData['status'] = PurchaseStatus::ORDERED;
        }
        $purchase->update($updateData);

        return PurchasePayment::create([
            'purchase_id'    => $purchase->id,
            'payment_date'   => $purchase->purchase_date->toDateString(),
            'amount'         => $paidAmount,
            'payment_method' => 'TRANSFER',
            'created_by'     => $this->gudangUser->id,
        ]);
    }

    public function test_gudang_can_receive_ordered_purchase_order(): void
    {
        $purchase = $this->poService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [
                ['material_id' => $this->material->id, 'qty' => '50.0000', 'unit_price' => '50000.00'],
            ],
        ]);
        $this->payPurchase($purchase);

        $response = $this->actingAs($this->gudangUser)
            ->post(route('gudang.purchases.receive', $purchase));

        $response->assertRedirect(route('gudang.purchases.show', $purchase));
        $response->assertSessionHas('success', 'Barang berhasil diterima. Stok dan harga rata-rata material telah diperbarui.');

        $fresh = $purchase->fresh(['items']);
        $this->assertEquals(PurchaseStatus::RECEIVED, $fresh->status);
        $this->assertEquals('50.0000', $fresh->items->first()->qty_received);

        $stock = $this->material->fresh(['stock'])->stock;
        $this->assertEquals('150.0000', $stock->qty_on_hand);
        $this->assertEquals('43333.3333', $stock->avg_unit_cost);
    }

    public function test_receive_request_ignores_client_payload_tampering(): void
    {
        $purchase = $this->poService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [
                ['material_id' => $this->material->id, 'qty' => '50.0000', 'unit_price' => '50000.00'],
            ],
        ]);
        $this->payPurchase($purchase);

        // Attempt malicious payload
        $response = $this->actingAs($this->gudangUser)
            ->post(route('gudang.purchases.receive', $purchase), [
                'qty'          => '999999',
                'qty_received' => '999999',
                'unit_price'   => '1',
            ]);

        $response->assertRedirect(route('gudang.purchases.show', $purchase));

        $fresh = $purchase->fresh(['items']);
        $this->assertEquals('50.0000', $fresh->items->first()->qty_received);
        $this->assertEquals('150.0000', $this->material->fresh(['stock'])->stock->qty_on_hand);
    }

    public function test_purchase_detail_displays_received_status_and_quantities(): void
    {
        $purchase = $this->poService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [
                ['material_id' => $this->material->id, 'qty' => '50.0000', 'unit_price' => '50000.00'],
            ],
        ]);
        $this->payPurchase($purchase);
        app(\App\Services\Inventory\PurchaseReceivingService::class)->receivePurchase($purchase);

        $response = $this->actingAs($this->gudangUser)
            ->get(route('gudang.purchases.show', $purchase));

        $response->assertOk();
        $response->assertSee('Diterima');
        $response->assertSee('50,0000');
        $response->assertDontSee('Edit Draft');
        $response->assertDontSee('Konfirmasi Pesanan');
        $response->assertDontSee('Batalkan PO');
    }

    public function test_mrp_live_shortage_reflects_received_stock_while_historical_snapshot_remains(): void
    {
        $customer = Customer::create(['customer_name' => 'Butik Hijab']);
        $product = Product::create(['product_name' => 'Gamis Katun', 'active' => true]);
        $variant = ProductVariant::create([
            'product_id'   => $product->id,
            'variant_name' => 'Biru',
            'sku'          => 'GMS-001',
            'active'       => true,
        ]);
        BomItem::create([
            'variant_id'   => $variant->id,
            'material_id'  => $this->material->id,
            'qty_per_unit' => 1.2900,
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'order_no'    => 'SO-202608-0002',
            'order_date'  => '2026-08-28',
            'status'      => OrderStatus::DEAL,
        ]);
        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 100,
            'unit_selling_price' => 120000,
        ]);
        $batch = ProductionBatch::create([
            'order_item_id' => $orderItem->id,
            'batch_no'      => 'BAT-202608-0002',
            'target_qty'    => 100,
            'status'        => BatchStatus::BAHAN,
        ]);

        $mrpService = app(BatchMaterialPlanningService::class);
        $mrpService->generateMaterialPlanning($batch);

        // Before receiving: stock 100, required 129 -> live shortage 29 (KURANG)
        $mrpDataBefore = $mrpService->getMaterialRequirementsData($batch);
        $this->assertEquals('29.0000', $mrpDataBefore['items'][0]['shortage_qty']);
        $this->assertTrue($mrpDataBefore['has_shortage']);

        // Create & receive PO for 50 m
        $purchase = $this->poService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [['material_id' => $this->material->id, 'qty' => 50, 'unit_price' => 40000]],
        ]);
        $this->payPurchase($purchase);

        $this->actingAs($this->gudangUser)
            ->post(route('gudang.purchases.receive', $purchase));

        // After receiving: stock 150, required 129 -> live shortage 0 (CUKUP)
        $mrpDataAfter = $mrpService->getMaterialRequirementsData($batch);
        $this->assertEquals('0.0000', $mrpDataAfter['items'][0]['shortage_qty']);
        $this->assertFalse($mrpDataAfter['has_shortage']);

        // Historical snapshot in batch_materials remains untouched
        $bm = BatchMaterial::where('batch_id', $batch->id)->first();
        $this->assertEquals('129.0000', $bm->required_qty);
        $this->assertEquals('100.0000', $bm->stock_at_calc);
        $this->assertEquals('29.0000', $bm->shortage_qty);
    }

    public function test_gudang_can_view_stock_movements_history(): void
    {
        $purchase = $this->poService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [
                ['material_id' => $this->material->id, 'qty' => '50.0000', 'unit_price' => '50000.00'],
            ],
        ]);
        $this->payPurchase($purchase);

        $this->actingAs($this->gudangUser)
            ->post(route('gudang.purchases.receive', $purchase));

        $response = $this->actingAs($this->gudangUser)
            ->get(route('gudang.stock-movements.index'));

        $response->assertOk();
        $response->assertSee('Riwayat Mutasi Stok');
        $response->assertSee('Kain Katun Premium');
        $response->assertSee('Stok Masuk');
        $response->assertSee('+50 m');
        $response->assertSee($purchase->purchase_no);
    }

    public function test_material_detail_shows_recent_movements(): void
    {
        $purchase = $this->poService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [
                ['material_id' => $this->material->id, 'qty' => '50.0000', 'unit_price' => '50000.00'],
            ],
        ]);
        $this->payPurchase($purchase);

        $this->actingAs($this->gudangUser)
            ->post(route('gudang.purchases.receive', $purchase));

        $response = $this->actingAs($this->gudangUser)
            ->get(route('gudang.materials.show', $this->material));

        $response->assertOk();
        $response->assertSee('Mutasi Stok Terakhir (Maks. 5)');
        $response->assertSee('Stok Masuk');
    }

    public function test_authorization_for_receiving_and_stock_movements(): void
    {
        $purchase = $this->poService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [
                ['material_id' => $this->material->id, 'qty' => '50.0000', 'unit_price' => '50000.00'],
            ],
        ]);
        $purchase->update(['status' => PurchaseStatus::ORDERED]);

        // Guest is redirected
        $this->post(route('gudang.purchases.receive', $purchase))
            ->assertRedirect(route('login'));
        $this->get(route('gudang.stock-movements.index'))
            ->assertRedirect(route('login'));

        // Unauthorized roles get 403
        foreach ([UserRole::ADMIN, UserRole::PRODUKSI, UserRole::OWNER, UserRole::CUSTOMER] as $role) {
            $user = User::factory()->create(['role' => $role, 'status' => UserStatus::ACTIVE]);

            $this->actingAs($user)
                ->post(route('gudang.purchases.receive', $purchase))
                ->assertForbidden();

            $this->actingAs($user)
                ->get(route('gudang.stock-movements.index'))
                ->assertForbidden();
        }
    }

    public function test_gudang_cannot_receive_unpaid_or_partially_paid_purchase_order(): void
    {
        $purchase = $this->poService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [
                ['material_id' => $this->material->id, 'qty' => '50.0000', 'unit_price' => '50000.00'],
            ],
        ]);
        $purchase->update(['status' => PurchaseStatus::ORDERED]);

        // 1. Unpaid: POST receive must fail
        $responseUnpaid = $this->actingAs($this->gudangUser)
            ->post(route('gudang.purchases.receive', $purchase));

        $responseUnpaid->assertRedirect(route('gudang.purchases.show', $purchase));
        $responseUnpaid->assertSessionHas('error', 'Barang belum dapat diterima karena pembayaran supplier belum lunas.');
        $this->assertEquals(PurchaseStatus::ORDERED, $purchase->fresh()->status);
        $this->assertEquals(0, StockMovement::count());

        // 2. Partially paid: POST receive must fail
        $this->payPurchase($purchase, '1000000.00'); // total is 2,500,000

        $responsePartial = $this->actingAs($this->gudangUser)
            ->post(route('gudang.purchases.receive', $purchase));

        $responsePartial->assertRedirect(route('gudang.purchases.show', $purchase));
        $responsePartial->assertSessionHas('error', 'Barang belum dapat diterima karena pembayaran supplier belum lunas.');
        $this->assertEquals(PurchaseStatus::ORDERED, $purchase->fresh()->status);
        $this->assertEquals(0, StockMovement::count());

        // 3. Fully paid: POST receive succeeds
        $this->payPurchase($purchase, '1500000.00');

        $responseFull = $this->actingAs($this->gudangUser)
            ->post(route('gudang.purchases.receive', $purchase));

        $responseFull->assertRedirect(route('gudang.purchases.show', $purchase));
        $responseFull->assertSessionHas('success', 'Barang berhasil diterima. Stok dan harga rata-rata material telah diperbarui.');
        $this->assertEquals(PurchaseStatus::RECEIVED, $purchase->fresh()->status);
        $this->assertEquals(1, StockMovement::count());
    }
}
