<?php

namespace Tests\Feature\Produksi;

use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Inventory\MaterialStock;
use App\Models\Inventory\StockMovement;
use App\Models\Master\Customer;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Master\Unit;
use App\Models\Production\BatchMaterial;
use App\Models\Production\ProductionBatch;
use App\Models\Production\ProductionResult;
use App\Models\Production\QcResult;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\Shipment;
use App\Models\System\User;
use App\Services\Tracking\TrackingTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackingCompletionFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $produksiUser;
    private Customer $customer;
    private Unit $meterUnit;
    private Material $kain;
    private MaterialStock $kainStock;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->produksiUser = User::factory()->create([
            'role'   => UserRole::PRODUKSI,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->customer = Customer::create(['customer_name' => 'Butik Nabila', 'phone' => '08123456789']);

        $this->meterUnit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $this->kain = Material::create([
            'unit_id'       => $this->meterUnit->id,
            'material_name' => 'Kain Katun Premium',
            'min_stock'     => 10,
            'active'        => true,
        ]);
        $this->kainStock = MaterialStock::create([
            'material_id'   => $this->kain->id,
            'qty_on_hand'   => 150.0000,
            'avg_unit_cost' => 35000.0000,
        ]);

        $this->product = Product::create(['product_name' => 'Hijab Pashmina Katun', 'active' => true]);
    }

    private function createBatchReadyForPacking(
        BatchStatus $status = BatchStatus::PACKING,
        float $targetQty = 100,
        float $actualQty = 100,
        float $goodQty = 98,
        float $rejectQty = 2
    ): array {
        $variant = ProductVariant::create([
            'product_id'        => $this->product->id,
            'variant_name'      => 'Cream',
            'sku'               => 'SKU-' . uniqid(),
            'requires_printing' => false,
            'active'            => true,
        ]);

        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'ORD-' . uniqid(),
            'order_date'  => '2026-08-28',
            'status'      => OrderStatus::PACKING,
        ]);

        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => $targetQty,
            'unit_selling_price' => 75000,
            'subtotal'           => $targetQty * 75000,
        ]);

        $batch = ProductionBatch::create([
            'order_item_id'     => $item->id,
            'batch_no'          => 'BAT-' . uniqid(),
            'target_qty'        => $targetQty,
            'status'            => $status,
            'requires_printing' => false,
        ]);

        $bm = BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->kain->id,
            'required_qty'  => 120.0000,
            'allocated_qty' => 120.0000,
            'used_qty'      => 0.0000,
        ]);

        $prodResult = ProductionResult::create([
            'batch_id'    => $batch->id,
            'actual_qty'  => $actualQty,
            'result_date' => '2026-08-29',
            'notes'       => 'Hasil fisik',
            'recorded_by' => $this->produksiUser->id,
        ]);

        $qcResult = QcResult::create([
            'production_result_id' => $prodResult->id,
            'good_qty'             => $goodQty,
            'reject_qty'           => $rejectQty,
            'qc_date'              => '2026-08-29',
            'notes'                => 'QC pass',
            'qc_by'                => $this->produksiUser->id,
        ]);

        return [$batch, $order, $variant, $bm, $prodResult, $qcResult];
    }

    public function test_basic_packing_completion_transitions_batch_to_selesai(): void
    {
        [$batch, $order] = $this->createBatchReadyForPacking(BatchStatus::PACKING, 100, 100, 100, 0);

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.complete-packing', $batch));

        $response->assertRedirect(route('produksi.batches.show', $batch));
        $response->assertSessionHas('success');

        $this->assertEquals(BatchStatus::SELESAI, $batch->fresh()->status);
        // Order reaches PACKING (all 100 good units fulfilled and batch SELESAI)
        $this->assertEquals(OrderStatus::PACKING, $order->fresh()->status);
    }

    public function test_all_reject_batch_can_complete_packing_to_selesai(): void
    {
        [$batch, $order] = $this->createBatchReadyForPacking(BatchStatus::PACKING, 100, 100, 0, 100);

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.complete-packing', $batch));

        $response->assertRedirect(route('produksi.batches.show', $batch));
        $response->assertSessionHas('success');

        $this->assertEquals(BatchStatus::SELESAI, $batch->fresh()->status);
        // Order returns to BAHAN because 0 good units exist (full 100 pcs replacement needed)
        $this->assertEquals(OrderStatus::BAHAN, $order->fresh()->status);
    }

    public function test_invalid_batch_status_rejects_packing_completion(): void
    {
        $invalidStatuses = [
            BatchStatus::BAHAN,
            BatchStatus::PRINTING,
            BatchStatus::PRODUKSI,
            BatchStatus::QC,
            BatchStatus::SELESAI,
            BatchStatus::CANCELLED,
        ];

        foreach ($invalidStatuses as $status) {
            [$batch] = $this->createBatchReadyForPacking($status, 100, 100, 100, 0);

            $response = $this->actingAs($this->produksiUser)
                ->post(route('produksi.batches.complete-packing', $batch));

            $response->assertRedirect();
            $response->assertSessionHas('error');
            $this->assertEquals($status, $batch->fresh()->status);
        }
    }

    public function test_missing_production_result_rejects_packing_completion(): void
    {
        [$batch, , , , $prodResult, $qcResult] = $this->createBatchReadyForPacking(BatchStatus::PACKING, 100, 100, 100, 0);

        // Delete production result (and cascaded qc result)
        $qcResult->delete();
        $prodResult->delete();

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.complete-packing', $batch));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals(BatchStatus::PACKING, $batch->fresh()->status);
    }

    public function test_missing_qc_result_rejects_packing_completion(): void
    {
        [$batch, , , , , $qcResult] = $this->createBatchReadyForPacking(BatchStatus::PACKING, 100, 100, 100, 0);

        // Delete QC result only
        $qcResult->delete();

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.complete-packing', $batch));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals(BatchStatus::PACKING, $batch->fresh()->status);
    }

    public function test_corrupt_qc_equation_rejects_packing_completion(): void
    {
        [$batch, , , , , $qcResult] = $this->createBatchReadyForPacking(BatchStatus::PACKING, 100, 100, 100, 0);

        // Corrupt QC data: 90 + 5 != 100
        $qcResult->update(['good_qty' => 90, 'reject_qty' => 5]);

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.complete-packing', $batch));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals(BatchStatus::PACKING, $batch->fresh()->status);
    }

    public function test_double_submit_safety_prevents_retransition(): void
    {
        [$batch] = $this->createBatchReadyForPacking(BatchStatus::PACKING, 100, 100, 100, 0);

        // 1st call
        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.complete-packing', $batch));
        $this->assertEquals(BatchStatus::SELESAI, $batch->fresh()->status);

        // 2nd call
        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.complete-packing', $batch));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals(BatchStatus::SELESAI, $batch->fresh()->status);
    }

    public function test_zero_inventory_mutation_on_packing_completion(): void
    {
        [$batch] = $this->createBatchReadyForPacking(BatchStatus::PACKING, 100, 100, 100, 0);

        $initialStockQty = $this->kainStock->fresh()->qty_on_hand;
        $initialAvgCost = $this->kainStock->fresh()->avg_unit_cost;
        $initialMovementCount = StockMovement::count();

        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.complete-packing', $batch));

        $this->assertEquals($initialStockQty, $this->kainStock->fresh()->qty_on_hand);
        $this->assertEquals($initialAvgCost, $this->kainStock->fresh()->avg_unit_cost);
        $this->assertEquals($initialMovementCount, StockMovement::count());

        $bm = $batch->batchMaterials()->first();
        $this->assertEquals('120.0000', $bm->required_qty);
        $this->assertEquals('120.0000', $bm->allocated_qty);
        $this->assertEquals('0.0000', $bm->used_qty);
    }

    public function test_production_and_qc_results_immutability(): void
    {
        [$batch, , , , $prodResult, $qcResult] = $this->createBatchReadyForPacking(BatchStatus::PACKING, 100, 100, 98, 2);

        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.complete-packing', $batch));

        $freshProd = $prodResult->fresh();
        $freshQc = $qcResult->fresh();

        $this->assertEquals('100.0000', $freshProd->actual_qty);
        $this->assertEquals('2026-08-29', $freshProd->result_date->toDateString());
        $this->assertEquals('98.0000', $freshQc->good_qty);
        $this->assertEquals('2.0000', $freshQc->reject_qty);
    }

    public function test_shipment_boundary_enforced(): void
    {
        [$batch, $order] = $this->createBatchReadyForPacking(BatchStatus::PACKING, 100, 100, 100, 0);

        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.complete-packing', $batch));

        // Must never create Shipment
        $this->assertEquals(0, Shipment::count());

        // Order status must NOT become DIKIRIM or SELESAI
        $this->assertNotEquals(OrderStatus::DIKIRIM, $order->fresh()->status);
        $this->assertNotEquals(OrderStatus::SELESAI, $order->fresh()->status);
        $this->assertEquals(OrderStatus::PACKING, $order->fresh()->status);
    }

    public function test_order_aggregation_scenarios_for_packing_completion(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'ORD-PACK-AGG-' . uniqid(),
            'order_date'  => '2026-08-28',
            'status'      => OrderStatus::PACKING,
        ]);

        $variant = ProductVariant::create([
            'product_id'        => $this->product->id,
            'variant_name'      => 'Dusty Rose',
            'sku'               => 'SKU-DR-' . uniqid(),
            'requires_printing' => false,
            'active'            => true,
        ]);

        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 200,
            'unit_selling_price' => 75000,
            'subtotal'           => 15000000,
        ]);

        // Batch 1 (100 pcs) at PACKING
        $batch1 = ProductionBatch::create([
            'order_item_id'     => $item->id,
            'batch_no'          => 'BAT-P1-' . uniqid(),
            'target_qty'        => 100,
            'status'            => BatchStatus::PACKING,
            'requires_printing' => false,
        ]);
        BatchMaterial::create([
            'batch_id'      => $batch1->id,
            'material_id'   => $this->kain->id,
            'required_qty'  => 100,
            'allocated_qty' => 100,
        ]);
        $pr1 = ProductionResult::create([
            'batch_id'    => $batch1->id,
            'actual_qty'  => 100,
            'result_date' => '2026-08-29',
            'recorded_by' => $this->produksiUser->id,
        ]);
        QcResult::create([
            'production_result_id' => $pr1->id,
            'good_qty'             => 100,
            'reject_qty'           => 0,
            'qc_date'              => '2026-08-29',
            'qc_by'                => $this->produksiUser->id,
        ]);

        // Batch 2 (100 pcs) at PACKING
        $batch2 = ProductionBatch::create([
            'order_item_id'     => $item->id,
            'batch_no'          => 'BAT-P2-' . uniqid(),
            'target_qty'        => 100,
            'status'            => BatchStatus::PACKING,
            'requires_printing' => false,
        ]);
        BatchMaterial::create([
            'batch_id'      => $batch2->id,
            'material_id'   => $this->kain->id,
            'required_qty'  => 100,
            'allocated_qty' => 100,
        ]);
        $pr2 = ProductionResult::create([
            'batch_id'    => $batch2->id,
            'actual_qty'  => 100,
            'result_date' => '2026-08-29',
            'recorded_by' => $this->produksiUser->id,
        ]);
        QcResult::create([
            'production_result_id' => $pr2->id,
            'good_qty'             => 100,
            'reject_qty'           => 0,
            'qc_date'              => '2026-08-29',
            'qc_by'                => $this->produksiUser->id,
        ]);

        // Complete Batch 1
        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.complete-packing', $batch1));

        $this->assertEquals(BatchStatus::SELESAI, $batch1->fresh()->status);
        $this->assertEquals(BatchStatus::PACKING, $batch2->fresh()->status);
        $this->assertEquals(OrderStatus::PACKING, $order->fresh()->status);

        // Complete Batch 2 -> All batches SELESAI & Good total 200 == Ordered 200
        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.complete-packing', $batch2));

        $this->assertEquals(BatchStatus::SELESAI, $batch1->fresh()->status);
        $this->assertEquals(BatchStatus::SELESAI, $batch2->fresh()->status);
        // Order remains PACKING (capped at shipment boundary)
        $this->assertEquals(OrderStatus::PACKING, $order->fresh()->status);
    }

    public function test_customer_tracking_remains_packing_and_not_completed(): void
    {
        [$batch, $order] = $this->createBatchReadyForPacking(BatchStatus::PACKING, 100, 100, 100, 0);

        $tokenService = app(TrackingTokenService::class);
        $token = $tokenService->createForOrder($order);

        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.complete-packing', $batch));

        $this->assertEquals(BatchStatus::SELESAI, $batch->fresh()->status);
        $this->assertEquals(OrderStatus::PACKING, $order->fresh()->status);

        $response = $this->get("/track/{$token}");
        $response->assertOk();
        $response->assertSee('Pengemasan');
        $response->assertDontSee('Pesanan Selesai</p>\n<p class="text-[11px] text-emerald-600 mt-0.5">Selesai</p>');
    }

    public function test_role_authorization_for_complete_packing(): void
    {
        [$batch] = $this->createBatchReadyForPacking(BatchStatus::PACKING, 100, 100, 100, 0);

        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'status' => UserStatus::ACTIVE]);
        $this->actingAs($admin)
            ->post(route('produksi.batches.complete-packing', $batch))
            ->assertForbidden();

        $gudang = User::factory()->create(['role' => UserRole::GUDANG, 'status' => UserStatus::ACTIVE]);
        $this->actingAs($gudang)
            ->post(route('produksi.batches.complete-packing', $batch))
            ->assertForbidden();

        $owner = User::factory()->create(['role' => UserRole::OWNER, 'status' => UserStatus::ACTIVE]);
        $this->actingAs($owner)
            ->post(route('produksi.batches.complete-packing', $batch))
            ->assertForbidden();

        $customer = User::factory()->create(['role' => UserRole::CUSTOMER, 'status' => UserStatus::ACTIVE]);
        $this->actingAs($customer)
            ->post(route('produksi.batches.complete-packing', $batch))
            ->assertForbidden();

        auth()->logout();
        $this->post(route('produksi.batches.complete-packing', $batch))
            ->assertRedirect(route('login'));
    }

    public function test_batch_show_completed_batch_with_packing_order_and_no_shipment_presentation(): void
    {
        [$batch, $order] = $this->createBatchReadyForPacking(BatchStatus::SELESAI, 100, 100, 100, 0);

        $response = $this->actingAs($this->produksiUser)
            ->get(route('produksi.batches.show', $batch));

        $response->assertOk();
        $response->assertSee('Batch Selesai');
        $response->assertSee('Pesanan belum dianggap selesai sampai proses pengiriman selesai.');
        $response->assertSee('Status Pesanan: Menunggu Pengiriman');
        $response->assertDontSee('Status Pesanan: Selesai');
    }

    public function test_batch_show_completed_batch_with_packing_order_and_pending_shipment_presentation(): void
    {
        [$batch, $order] = $this->createBatchReadyForPacking(BatchStatus::SELESAI, 100, 100, 100, 0);

        Shipment::create([
            'order_id'          => $order->id,
            'courier'           => 'JNE',
            'status'            => \App\Enums\ShipmentStatus::PENDING,
            'recipient_address' => $this->customer->address ?? 'Alamat',
        ]);

        $response = $this->actingAs($this->produksiUser)
            ->get(route('produksi.batches.show', $batch));

        $response->assertOk();
        $response->assertSee('Batch Selesai');
        $response->assertSee('Status Pesanan: Menunggu Pengiriman');
        $response->assertDontSee('Status Pesanan: Selesai');
    }

    public function test_batch_show_completed_batch_with_shipped_order_presentation(): void
    {
        [$batch, $order] = $this->createBatchReadyForPacking(BatchStatus::SELESAI, 100, 100, 100, 0);

        $order->update(['status' => OrderStatus::DIKIRIM]);
        Shipment::create([
            'order_id'          => $order->id,
            'courier'           => 'JNE',
            'tracking_no'       => 'JNE-REG-8888',
            'status'            => \App\Enums\ShipmentStatus::SHIPPED,
            'shipped_at'        => now(),
            'recipient_address' => $this->customer->address ?? 'Alamat',
        ]);

        $response = $this->actingAs($this->produksiUser)
            ->get(route('produksi.batches.show', $batch));

        $response->assertOk();
        $response->assertSee('Batch Selesai');
        $response->assertSee('Status Pesanan: Sedang Dikirim');
        $response->assertDontSee('Status Pesanan: Selesai');
    }

    public function test_batch_show_completed_batch_with_delivered_order_presentation(): void
    {
        [$batch, $order] = $this->createBatchReadyForPacking(BatchStatus::SELESAI, 100, 100, 100, 0);

        $order->update(['status' => OrderStatus::SELESAI]);
        Shipment::create([
            'order_id'          => $order->id,
            'courier'           => 'JNE',
            'tracking_no'       => 'JNE-REG-8888',
            'status'            => \App\Enums\ShipmentStatus::DELIVERED,
            'shipped_at'        => now()->subDay(),
            'delivered_at'      => now(),
            'recipient_address' => $this->customer->address ?? 'Alamat',
        ]);

        $response = $this->actingAs($this->produksiUser)
            ->get(route('produksi.batches.show', $batch));

        $response->assertOk();
        $response->assertSee('Batch Selesai');
        $response->assertSee('Status Pesanan: Selesai');
    }
}
