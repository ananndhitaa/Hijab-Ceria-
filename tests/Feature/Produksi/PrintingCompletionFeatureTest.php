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
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\OrderStatusHistory;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrintingCompletionFeatureTest extends TestCase
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
            'material_name' => 'Kain Motif Printing',
            'min_stock'     => 10,
            'active'        => true,
        ]);
        $this->kainStock = MaterialStock::create([
            'material_id'   => $this->kain->id,
            'qty_on_hand'   => 150.0000,
            'avg_unit_cost' => 35000.0000,
        ]);

        $this->product = Product::create(['product_name' => 'Hijab Motif Segiempat', 'active' => true]);
    }

    private function createBatch(bool $requiresPrinting = true, BatchStatus $status = BatchStatus::PRINTING, float $targetQty = 100): array
    {
        $variant = ProductVariant::create([
            'product_id'        => $this->product->id,
            'variant_name'      => 'Motif Flora',
            'sku'               => 'SKU-' . uniqid(),
            'requires_printing' => $requiresPrinting,
            'active'            => true,
        ]);

        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'ORD-' . uniqid(),
            'order_date'  => now()->toDateString(),
            'status'      => OrderStatus::PRINTING,
        ]);

        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => $targetQty,
            'unit_selling_price' => 95000,
            'subtotal'           => $targetQty * 95000,
        ]);

        $batch = ProductionBatch::create([
            'order_item_id'     => $item->id,
            'batch_no'          => 'BAT-' . uniqid(),
            'target_qty'        => $targetQty,
            'status'            => $status,
            'requires_printing' => $requiresPrinting,
        ]);

        $bm = BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->kain->id,
            'required_qty'  => 120.0000,
            'allocated_qty' => 120.0000,
            'used_qty'      => 0.0000,
        ]);

        return [$batch, $order, $variant, $bm];
    }

    public function test_basic_printing_completion_advances_batch_to_produksi(): void
    {
        [$batch, $order] = $this->createBatch(true, BatchStatus::PRINTING);

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.complete-printing', $batch));

        $response->assertRedirect(route('produksi.batches.show', $batch));
        $response->assertSessionHas('success');

        $this->assertEquals(BatchStatus::PRODUKSI, $batch->fresh()->status);
        $this->assertEquals(OrderStatus::PRODUKSI, $order->fresh()->status);
    }

    public function test_printing_completion_has_zero_inventory_mutation(): void
    {
        [$batch] = $this->createBatch(true, BatchStatus::PRINTING);

        $initialStockQty = $this->kainStock->fresh()->qty_on_hand;
        $initialAvgCost = $this->kainStock->fresh()->avg_unit_cost;
        $initialMovementCount = StockMovement::count();

        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.complete-printing', $batch));

        // Verify stock untouched
        $this->assertEquals($initialStockQty, $this->kainStock->fresh()->qty_on_hand);
        $this->assertEquals($initialAvgCost, $this->kainStock->fresh()->avg_unit_cost);
        $this->assertEquals($initialMovementCount, StockMovement::count());

        // Verify batch material quantities untouched
        $bm = $batch->batchMaterials()->first();
        $this->assertEquals('120.0000', $bm->required_qty);
        $this->assertEquals('120.0000', $bm->allocated_qty);
        $this->assertEquals('0.0000', $bm->used_qty);
    }

    public function test_invalid_route_non_printing_batch_at_printing_is_rejected(): void
    {
        // Batch corrupted to PRINTING while requires_printing = false
        [$batch] = $this->createBatch(false, BatchStatus::PRINTING);

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.complete-printing', $batch));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertEquals(BatchStatus::PRINTING, $batch->fresh()->status);
    }

    public function test_invalid_status_batches_are_rejected(): void
    {
        $invalidStatuses = [
            BatchStatus::BAHAN,
            BatchStatus::PRODUKSI,
            BatchStatus::QC,
            BatchStatus::PACKING,
            BatchStatus::SELESAI,
            BatchStatus::CANCELLED,
        ];

        foreach ($invalidStatuses as $status) {
            [$batch] = $this->createBatch(true, $status);

            $response = $this->actingAs($this->produksiUser)
                ->post(route('produksi.batches.complete-printing', $batch));

            $response->assertRedirect();
            $response->assertSessionHas('error');
            $this->assertEquals($status, $batch->fresh()->status);
        }
    }

    public function test_printing_completion_rejected_if_material_allocation_incomplete(): void
    {
        [$batch, , , $bm] = $this->createBatch(true, BatchStatus::PRINTING);

        // Corrupt allocation
        $bm->update(['allocated_qty' => 60.0000]);

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.complete-printing', $batch));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals(BatchStatus::PRINTING, $batch->fresh()->status);
    }

    public function test_double_submit_safety_prevents_advancing_beyond_produksi(): void
    {
        [$batch] = $this->createBatch(true, BatchStatus::PRINTING);

        // First submit: PRINTING -> PRODUKSI
        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.complete-printing', $batch));
        $this->assertEquals(BatchStatus::PRODUKSI, $batch->fresh()->status);

        // Second submit attempt
        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.complete-printing', $batch));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals(BatchStatus::PRODUKSI, $batch->fresh()->status);
    }

    public function test_snapshot_route_is_authoritative_even_if_master_changes(): void
    {
        [$batch, , $variant] = $this->createBatch(true, BatchStatus::PRINTING);

        // Admin changes master ProductVariant later to false
        $variant->update(['requires_printing' => false]);

        // Printing completion still succeeds according to Batch snapshot
        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.complete-printing', $batch));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals(BatchStatus::PRODUKSI, $batch->fresh()->status);
    }

    public function test_aggregation_when_sibling_batch_is_still_printing(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'ORD-MULTI-' . uniqid(),
            'order_date'  => now()->toDateString(),
            'status'      => OrderStatus::PRINTING,
        ]);

        $variant = ProductVariant::create([
            'product_id'        => $this->product->id,
            'variant_name'      => 'Motif Sibling',
            'sku'               => 'SKU-SIB-' . uniqid(),
            'requires_printing' => true,
            'active'            => true,
        ]);

        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 200,
            'unit_selling_price' => 95000,
            'subtotal'           => 19000000,
        ]);

        // Batch 1 (100 pcs) at PRINTING
        $batch1 = ProductionBatch::create([
            'order_item_id'     => $item->id,
            'batch_no'          => 'BAT-1-' . uniqid(),
            'target_qty'        => 100,
            'status'            => BatchStatus::PRINTING,
            'requires_printing' => true,
        ]);
        BatchMaterial::create([
            'batch_id'      => $batch1->id,
            'material_id'   => $this->kain->id,
            'required_qty'  => 100,
            'allocated_qty' => 100,
        ]);

        // Batch 2 (100 pcs) at PRINTING
        $batch2 = ProductionBatch::create([
            'order_item_id'     => $item->id,
            'batch_no'          => 'BAT-2-' . uniqid(),
            'target_qty'        => 100,
            'status'            => BatchStatus::PRINTING,
            'requires_printing' => true,
        ]);
        BatchMaterial::create([
            'batch_id'      => $batch2->id,
            'material_id'   => $this->kain->id,
            'required_qty'  => 100,
            'allocated_qty' => 100,
        ]);

        // Complete Batch 1
        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.complete-printing', $batch1));

        $this->assertEquals(BatchStatus::PRODUKSI, $batch1->fresh()->status);
        $this->assertEquals(BatchStatus::PRINTING, $batch2->fresh()->status);

        // Order remains PRINTING because Batch 2 is still in PRINTING
        $this->assertEquals(OrderStatus::PRINTING, $order->fresh()->status);

        // Complete Batch 2
        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.complete-printing', $batch2));

        $this->assertEquals(BatchStatus::PRODUKSI, $batch2->fresh()->status);

        // Order now advances to PRODUKSI
        $this->assertEquals(OrderStatus::PRODUKSI, $order->fresh()->status);
    }

    public function test_aggregation_remains_bahan_if_unbatched_remaining_qty_exists(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'ORD-REM-' . uniqid(),
            'order_date'  => now()->toDateString(),
            'status'      => OrderStatus::BAHAN,
        ]);

        $variant = ProductVariant::create([
            'product_id'        => $this->product->id,
            'variant_name'      => 'Motif Remainder',
            'sku'               => 'SKU-REM-' . uniqid(),
            'requires_printing' => true,
            'active'            => true,
        ]);

        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 200,
            'unit_selling_price' => 95000,
            'subtotal'           => 19000000,
        ]);

        // Batch covers only 100 pcs out of 200 pcs ordered
        $batch = ProductionBatch::create([
            'order_item_id'     => $item->id,
            'batch_no'          => 'BAT-REM-' . uniqid(),
            'target_qty'        => 100,
            'status'            => BatchStatus::PRINTING,
            'requires_printing' => true,
        ]);
        BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->kain->id,
            'required_qty'  => 100,
            'allocated_qty' => 100,
        ]);

        // Complete Printing
        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.complete-printing', $batch));

        $this->assertEquals(BatchStatus::PRODUKSI, $batch->fresh()->status);

        // Order remains BAHAN because 100 pcs remains unbatched
        $this->assertEquals(OrderStatus::BAHAN, $order->fresh()->status);
    }

    public function test_role_authorization_on_complete_printing_endpoint(): void
    {
        [$batch] = $this->createBatch(true, BatchStatus::PRINTING);

        // Admin -> 403
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'status' => UserStatus::ACTIVE]);
        $this->actingAs($admin)
            ->post(route('produksi.batches.complete-printing', $batch))
            ->assertForbidden();

        // Gudang -> 403
        $gudang = User::factory()->create(['role' => UserRole::GUDANG, 'status' => UserStatus::ACTIVE]);
        $this->actingAs($gudang)
            ->post(route('produksi.batches.complete-printing', $batch))
            ->assertForbidden();

        // Owner -> 403
        $owner = User::factory()->create(['role' => UserRole::OWNER, 'status' => UserStatus::ACTIVE]);
        $this->actingAs($owner)
            ->post(route('produksi.batches.complete-printing', $batch))
            ->assertForbidden();

        // Customer -> 403
        $customerUser = User::factory()->create(['role' => UserRole::CUSTOMER, 'status' => UserStatus::ACTIVE]);
        $this->actingAs($customerUser)
            ->post(route('produksi.batches.complete-printing', $batch))
            ->assertForbidden();

        // Guest -> Redirect to login
        auth()->logout();
        $this->post(route('produksi.batches.complete-printing', $batch))
            ->assertRedirect(route('login'));
    }
}
