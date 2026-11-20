<?php

namespace Tests\Feature\Produksi;

use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Inventory\MaterialStock;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Master\Unit;
use App\Models\Production\BatchMaterial;
use App\Models\Production\ProductionBatch;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchWorkflowFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $produksiUser;
    private Unit $meterUnit;
    private Material $kain;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->produksiUser = User::factory()->create([
            'role'   => UserRole::PRODUKSI,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->meterUnit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $this->kain = Material::create([
            'unit_id'       => $this->meterUnit->id,
            'material_name' => 'Kain Voal',
            'min_stock'     => 10,
            'active'        => true,
        ]);
        MaterialStock::create(['material_id' => $this->kain->id, 'qty_on_hand' => 100, 'avg_unit_cost' => 30000]);

        $this->product = Product::create(['product_name' => 'Hijab Pashmina', 'active' => true]);
    }

    private function createBatch(bool|null $requiresPrinting, string $status = 'BAHAN'): array
    {
        $variant = ProductVariant::create([
            'product_id'        => $this->product->id,
            'variant_name'      => 'Varian Test',
            'sku'               => 'SKU-' . uniqid(),
            'requires_printing' => $requiresPrinting,
            'active'            => true,
        ]);

        $customer = \App\Models\Master\Customer::create(['customer_name' => 'Customer ' . uniqid(), 'phone' => '08123456789']);

        $order = Order::create([
            'customer_id' => $customer->id,
            'order_no'    => 'ORD-' . uniqid(),
            'order_date'  => now()->toDateString(),
            'status'      => OrderStatus::BAHAN,
        ]);

        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 50,
            'unit_selling_price' => 75000,
            'subtotal'           => 3750000,
        ]);

        $batch = ProductionBatch::create([
            'order_item_id'     => $item->id,
            'batch_no'          => 'BAT-' . uniqid(),
            'target_qty'        => 50,
            'status'            => $status,
            'requires_printing' => $requiresPrinting,
        ]);

        return [$batch, $order, $variant, $item];
    }

    public function test_batch_cannot_start_if_no_mrp_snapshot(): void
    {
        [$batch] = $this->createBatch(false);

        $response = $this->actingAs($this->produksiUser)->post(route('produksi.batches.start', $batch));
        $response->assertRedirect();
        $response->assertSessionHas('error', 'Bahan batch belum selesai dialokasikan oleh Gudang.');
        $this->assertEquals(BatchStatus::BAHAN, $batch->fresh()->status);
    }

    public function test_batch_cannot_start_if_materials_partially_allocated(): void
    {
        [$batch] = $this->createBatch(false);

        BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->kain->id,
            'required_qty'  => 100,
            'allocated_qty' => 50, // Partial
        ]);

        $response = $this->actingAs($this->produksiUser)->post(route('produksi.batches.start', $batch));
        $response->assertRedirect();
        $response->assertSessionHas('error', 'Bahan batch belum selesai dialokasikan oleh Gudang.');
        $this->assertEquals(BatchStatus::BAHAN, $batch->fresh()->status);
    }

    public function test_batch_with_printing_transitions_bahan_to_printing(): void
    {
        [$batch, $order] = $this->createBatch(true);

        BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->kain->id,
            'required_qty'  => 100,
            'allocated_qty' => 100, // Fully allocated
        ]);

        $response = $this->actingAs($this->produksiUser)->post(route('produksi.batches.start', $batch));
        $response->assertRedirect(route('produksi.batches.show', $batch));
        $response->assertSessionHas('success');

        $this->assertEquals(BatchStatus::PRINTING, $batch->fresh()->status);
        $this->assertEquals(OrderStatus::PRINTING, $order->fresh()->status);
    }

    public function test_batch_without_printing_transitions_bahan_directly_to_produksi(): void
    {
        [$batch, $order] = $this->createBatch(false);

        BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->kain->id,
            'required_qty'  => 100,
            'allocated_qty' => 100, // Fully allocated
        ]);

        $response = $this->actingAs($this->produksiUser)->post(route('produksi.batches.start', $batch));
        $response->assertRedirect(route('produksi.batches.show', $batch));
        $response->assertSessionHas('success');

        $this->assertEquals(BatchStatus::PRODUKSI, $batch->fresh()->status);
        $this->assertEquals(OrderStatus::PRODUKSI, $order->fresh()->status);
    }

    public function test_double_start_is_prevented(): void
    {
        [$batch] = $this->createBatch(false);

        BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->kain->id,
            'required_qty'  => 100,
            'allocated_qty' => 100,
        ]);

        // First start succeeds
        $this->actingAs($this->produksiUser)->post(route('produksi.batches.start', $batch));
        $this->assertEquals(BatchStatus::PRODUKSI, $batch->fresh()->status);

        // Second start attempt fails
        $response = $this->actingAs($this->produksiUser)->post(route('produksi.batches.start', $batch));
        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals(BatchStatus::PRODUKSI, $batch->fresh()->status);
    }

    public function test_legacy_batch_null_snapshot_master_false_ui_offers_mulai_produksi_and_starts_to_produksi(): void
    {
        [$batch, $order, $variant] = $this->createBatch(null);
        $variant->update(['requires_printing' => false]);

        BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->kain->id,
            'required_qty'  => 100,
            'allocated_qty' => 100,
        ]);

        // 1. UI Check: GET Batch Detail
        $viewResponse = $this->actingAs($this->produksiUser)->get(route('produksi.batches.show', $batch));
        $viewResponse->assertOk();
        $viewResponse->assertSee('Tanpa Printing');
        $viewResponse->assertSee('Mulai Produksi');
        $viewResponse->assertSee('Rute batch akan dikunci dari master saat proses dimulai.');

        // 2. GET does NOT mutate snapshot
        $this->assertNull($batch->fresh()->requires_printing);

        // 3. Start batch
        $startResponse = $this->actingAs($this->produksiUser)->post(route('produksi.batches.start', $batch));
        $startResponse->assertRedirect(route('produksi.batches.show', $batch));
        $startResponse->assertSessionHas('success');

        // 4. Batch snapshot initialized to FALSE, status PRODUKSI
        $this->assertFalse($batch->fresh()->requires_printing);
        $this->assertEquals(BatchStatus::PRODUKSI, $batch->fresh()->status);
        $this->assertEquals(OrderStatus::PRODUKSI, $order->fresh()->status);
    }

    public function test_legacy_batch_null_snapshot_master_true_ui_offers_mulai_printing_and_starts_to_printing(): void
    {
        [$batch, $order, $variant] = $this->createBatch(null);
        $variant->update(['requires_printing' => true]);

        BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->kain->id,
            'required_qty'  => 100,
            'allocated_qty' => 100,
        ]);

        // 1. UI Check: GET Batch Detail
        $viewResponse = $this->actingAs($this->produksiUser)->get(route('produksi.batches.show', $batch));
        $viewResponse->assertOk();
        $viewResponse->assertSee('Dengan Printing');
        $viewResponse->assertSee('Mulai Printing');
        $viewResponse->assertSee('Rute batch akan dikunci dari master saat proses dimulai.');

        // 2. GET does NOT mutate snapshot
        $this->assertNull($batch->fresh()->requires_printing);

        // 3. Start batch
        $startResponse = $this->actingAs($this->produksiUser)->post(route('produksi.batches.start', $batch));
        $startResponse->assertRedirect(route('produksi.batches.show', $batch));
        $startResponse->assertSessionHas('success');

        // 4. Batch snapshot initialized to TRUE, status PRINTING
        $this->assertTrue($batch->fresh()->requires_printing);
        $this->assertEquals(BatchStatus::PRINTING, $batch->fresh()->status);
        $this->assertEquals(OrderStatus::PRINTING, $order->fresh()->status);
    }

    public function test_legacy_batch_null_snapshot_master_null_shows_unassigned_route_and_no_start_action(): void
    {
        [$batch, $order, $variant] = $this->createBatch(null);
        $variant->update(['requires_printing' => null]);

        BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->kain->id,
            'required_qty'  => 100,
            'allocated_qty' => 100,
        ]);

        // 1. UI Check: GET Batch Detail
        $viewResponse = $this->actingAs($this->produksiUser)->get(route('produksi.batches.show', $batch));
        $viewResponse->assertOk();
        $viewResponse->assertSee('Rute Belum Ditetapkan');
        $viewResponse->assertSee('Rute produksi varian belum ditetapkan pada Master Produk. Hubungi Admin untuk menentukan kebutuhan Printing.');
        $viewResponse->assertDontSee('Mulai Produksi');
        $viewResponse->assertDontSee('Mulai Printing');

        // 2. Start attempt fails
        $startResponse = $this->actingAs($this->produksiUser)->post(route('produksi.batches.start', $batch));
        $startResponse->assertRedirect();
        $startResponse->assertSessionHas('error', 'Rute produksi varian belum ditetapkan.');
        $this->assertNull($batch->fresh()->requires_printing);
        $this->assertEquals(BatchStatus::BAHAN, $batch->fresh()->status);
    }

    public function test_get_batch_detail_does_not_persist_snapshot(): void
    {
        [$batch, $order, $variant] = $this->createBatch(null);
        $variant->update(['requires_printing' => false]);

        $this->actingAs($this->produksiUser)->get(route('produksi.batches.show', $batch));
        $this->actingAs($this->produksiUser)->get(route('produksi.batches.show', $batch));

        $this->assertNull($batch->fresh()->requires_printing);
    }

    public function test_after_onetime_snapshot_false_master_later_changes_to_true_existing_batch_remains_false(): void
    {
        [$batch, $order, $variant] = $this->createBatch(null);
        $variant->update(['requires_printing' => false]);

        BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->kain->id,
            'required_qty'  => 100,
            'allocated_qty' => 100,
        ]);

        // Start locks snapshot to false
        $this->actingAs($this->produksiUser)->post(route('produksi.batches.start', $batch));
        $this->assertFalse($batch->fresh()->requires_printing);

        // Admin updates master variant to true
        $variant->update(['requires_printing' => true]);

        // Existing Batch snapshot remains false
        $this->assertFalse($batch->fresh()->requires_printing);
    }

    public function test_starting_legacy_batch_has_zero_inventory_side_effects(): void
    {
        [$batch, $order, $variant] = $this->createBatch(null);
        $variant->update(['requires_printing' => false]);

        $bm = BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->kain->id,
            'required_qty'  => 100,
            'allocated_qty' => 100,
            'used_qty'      => 0,
        ]);

        $stockBefore = MaterialStock::where('material_id', $this->kain->id)->first();
        $qtyBefore = $stockBefore->qty_on_hand;
        $costBefore = $stockBefore->avg_unit_cost;
        $movementsBefore = \App\Models\Inventory\StockMovement::count();

        $this->actingAs($this->produksiUser)->post(route('produksi.batches.start', $batch));

        $stockAfter = MaterialStock::where('material_id', $this->kain->id)->first();
        $movementsAfter = \App\Models\Inventory\StockMovement::count();
        $bmFresh = $bm->fresh();

        $this->assertEquals($qtyBefore, $stockAfter->qty_on_hand);
        $this->assertEquals($costBefore, $stockAfter->avg_unit_cost);
        $this->assertEquals($movementsBefore, $movementsAfter);
        $this->assertEquals('100.0000', (string) $bmFresh->allocated_qty);
        $this->assertEquals('0.0000', (string) $bmFresh->used_qty);
    }

    public function test_tampered_client_payload_is_ignored(): void
    {
        [$batch] = $this->createBatch(false); // Route is FALSE (Tanpa Printing)

        BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->kain->id,
            'required_qty'  => 100,
            'allocated_qty' => 100,
        ]);

        // Malicious client payload trying to set next_status = QC or requires_printing = true
        $response = $this->actingAs($this->produksiUser)->post(route('produksi.batches.start', $batch), [
            'next_status'       => 'QC',
            'requires_printing' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Server authoritative check: Must go to PRODUKSI, not QC
        $this->assertEquals(BatchStatus::PRODUKSI, $batch->fresh()->status);
    }
}
