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
use App\Models\System\User;
use App\Services\Tracking\TrackingTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionResultFeatureTest extends TestCase
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

    private function createBatch(BatchStatus $status = BatchStatus::PRODUKSI, float $targetQty = 100): array
    {
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
            'order_date'  => now()->toDateString(),
            'status'      => OrderStatus::PRODUKSI,
        ]);

        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => $targetQty,
            'unit_selling_price' => 75000,
            'subtotal'           => $targetQty * 75000,
        ]);

        $batch = ProductionBatch::create([
            'order_item_id'          => $item->id,
            'batch_no'               => 'BAT-' . uniqid(),
            'target_qty'             => $targetQty,
            'status'                 => $status,
            'requires_printing'      => false,
            'production_employee_id' => $this->produksiUser->id,
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

    public function test_basic_result_recording_under_target(): void
    {
        [$batch, $order, , $bm] = $this->createBatch(BatchStatus::PRODUKSI, 100);

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 98,
                'result_date'    => now()->toDateString(),
                'notes'          => 'Selesai jahit dengan sedikit cacat serat 2 pcs.',
                'material_usage' => [$bm->id => 118.5],
            ]);

        $response->assertRedirect(route('produksi.batches.show', $batch));
        $response->assertSessionHas('success');

        $this->assertEquals(BatchStatus::QC, $batch->fresh()->status);
        $this->assertEquals(OrderStatus::QC, $order->fresh()->status);

        $result = ProductionResult::where('batch_id', $batch->id)->first();
        $this->assertNotNull($result);
        $this->assertEquals('98.0000', $result->actual_qty);
        $this->assertEquals($this->produksiUser->id, $result->recorded_by);
        $this->assertEquals('-2.0000', $batch->fresh()->variance_qty);

        // P6A: check used_qty and markers
        $this->assertEquals('118.5000', $bm->fresh()->used_qty);
        $this->assertNotNull($batch->fresh()->material_usage_recorded_at);
        $this->assertEquals($this->produksiUser->id, $batch->fresh()->material_usage_recorded_by);
    }

    public function test_result_recording_over_target_is_accepted(): void
    {
        [$batch, , , $bm] = $this->createBatch(BatchStatus::PRODUKSI, 100);

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 103,
                'result_date'    => now()->toDateString(),
                'material_usage' => [$bm->id => 120],
            ]);

        $response->assertRedirect(route('produksi.batches.show', $batch));
        $response->assertSessionHas('success');

        $freshBatch = $batch->fresh(['productionResult']);
        $this->assertEquals(BatchStatus::QC, $freshBatch->status);
        $this->assertEquals(1, ProductionResult::count());
        $this->assertEquals('100.0000', (string) $freshBatch->target_qty);
        $this->assertEquals('103.0000', (string) $freshBatch->productionResult->actual_qty);
        $this->assertEquals(103.0, (float) $freshBatch->actual_qty);
        $this->assertEquals('3.0000', $freshBatch->variance_qty);
    }

    public function test_result_recording_exact_target(): void
    {
        [$batch, , , $bm] = $this->createBatch(BatchStatus::PRODUKSI, 100);

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 100,
                'result_date'    => now()->toDateString(),
                'material_usage' => [$bm->id => 120],
            ]);

        $response->assertRedirect(route('produksi.batches.show', $batch));
        $response->assertSessionHas('success');

        $this->assertEquals(BatchStatus::QC, $batch->fresh()->status);
        $this->assertEquals('0.0000', $batch->fresh()->variance_qty);
    }

    public function test_invalid_actual_quantities_are_rejected(): void
    {
        [$batch, , , $bm] = $this->createBatch(BatchStatus::PRODUKSI, 100);

        $invalidQuantities = [0, -5, 'invalid_number'];

        foreach ($invalidQuantities as $invalidQty) {
            $response = $this->actingAs($this->produksiUser)
                ->post(route('produksi.batches.production-result.store', $batch), [
                    'actual_qty'     => $invalidQty,
                    'result_date'    => now()->toDateString(),
                    'material_usage' => [$bm->id => 120],
                ]);

            $response->assertSessionHasErrors('actual_qty');
            $this->assertEquals(BatchStatus::PRODUKSI, $batch->fresh()->status);
            $this->assertEquals(0, ProductionResult::count());
        }
    }

    public function test_invalid_batch_status_is_rejected(): void
    {
        $invalidStatuses = [
            BatchStatus::BAHAN,
            BatchStatus::PRINTING,
            BatchStatus::QC,
            BatchStatus::PACKING,
            BatchStatus::SELESAI,
            BatchStatus::CANCELLED,
        ];

        foreach ($invalidStatuses as $status) {
            [$batch, , , $bm] = $this->createBatch($status, 100);

            $response = $this->actingAs($this->produksiUser)
                ->post(route('produksi.batches.production-result.store', $batch), [
                    'actual_qty'     => 100,
                    'result_date'    => now()->toDateString(),
                    'material_usage' => [$bm->id => 120],
                ]);

            $response->assertRedirect();
            $response->assertSessionHas('error');
            $this->assertEquals($status, $batch->fresh()->status);
            $this->assertEquals(0, ProductionResult::where('batch_id', $batch->id)->count());
        }
    }

    public function test_duplicate_result_submission_is_prevented(): void
    {
        [$batch, , , $bm] = $this->createBatch(BatchStatus::PRODUKSI, 100);

        // First submission succeeds
        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 98,
                'result_date'    => now()->toDateString(),
                'material_usage' => [$bm->id => 120],
            ]);

        $this->assertEquals(1, ProductionResult::where('batch_id', $batch->id)->count());
        $this->assertEquals(BatchStatus::QC, $batch->fresh()->status);

        // Second submission attempt is rejected
        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 98,
                'result_date'    => now()->toDateString(),
                'material_usage' => [$bm->id => 120],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals(1, ProductionResult::where('batch_id', $batch->id)->count());
        $this->assertEquals(BatchStatus::QC, $batch->fresh()->status);
    }

    public function test_material_allocation_integrity_required(): void
    {
        [$batch, , , $bm] = $this->createBatch(BatchStatus::PRODUKSI, 100);

        // Corrupt allocation
        $bm->update(['allocated_qty' => 50.0000]);

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 100,
                'result_date'    => now()->toDateString(),
                'material_usage' => [$bm->id => 50],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals(BatchStatus::PRODUKSI, $batch->fresh()->status);
        $this->assertEquals(0, ProductionResult::where('batch_id', $batch->id)->count());
    }

    public function test_tampered_recorded_by_payload_is_ignored(): void
    {
        [$batch, , , $bm] = $this->createBatch(BatchStatus::PRODUKSI, 100);
        $otherUser = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 100,
                'result_date'    => now()->toDateString(),
                'recorded_by'    => $otherUser->id, // Malicious spoof
                'material_usage' => [$bm->id => 120],
            ]);

        $result = ProductionResult::where('batch_id', $batch->id)->first();
        $this->assertEquals($this->produksiUser->id, $result->recorded_by);
    }

    public function test_result_date_and_notes_normalization(): void
    {
        [$batch, , , $bm] = $this->createBatch(BatchStatus::PRODUKSI, 100);

        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 100,
                'result_date'    => '2026-08-29',
                'notes'          => '   Catatan khusus penjahitan.   ',
                'material_usage' => [$bm->id => 120],
            ]);

        $result = ProductionResult::where('batch_id', $batch->id)->first();
        $this->assertEquals('2026-08-29', $result->result_date->toDateString());
        $this->assertEquals('Catatan khusus penjahitan.', $result->notes);
    }

    public function test_inventory_mutation_on_production_result_recording_reconciles_surplus(): void
    {
        [$batch, , , $bm] = $this->createBatch(BatchStatus::PRODUKSI, 100);

        $initialStockQty = (string) $this->kainStock->fresh()->qty_on_hand;
        $initialAvgCost = (string) $this->kainStock->fresh()->avg_unit_cost;
        $initialMovementCount = StockMovement::count();

        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 100,
                'result_date'    => now()->toDateString(),
                'material_usage' => [$bm->id => 115.5],
            ]);

        // Surplus (120 - 115.5 = 4.5) automatically returns to warehouse
        $expectedStock = bcadd($initialStockQty, '4.5000', 4);
        $this->assertEquals($expectedStock, (string) $this->kainStock->fresh()->qty_on_hand);
        $this->assertEquals($initialAvgCost, (string) $this->kainStock->fresh()->avg_unit_cost);
        $this->assertEquals($initialMovementCount + 1, StockMovement::count());

        $movement = StockMovement::latest('id')->first();
        $this->assertEquals(\App\Enums\MovementType::IN, $movement->movement_type);
        $this->assertEquals('4.5000', (string) $movement->qty);

        $freshBm = $bm->fresh();
        $this->assertEquals('120.0000', $freshBm->required_qty);
        $this->assertEquals('120.0000', $freshBm->allocated_qty);
        $this->assertEquals('115.5000', $freshBm->used_qty);
    }

    public function test_no_qc_result_auto_created(): void
    {
        [$batch, , , $bm] = $this->createBatch(BatchStatus::PRODUKSI, 100);

        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 100,
                'result_date'    => now()->toDateString(),
                'material_usage' => [$bm->id => 120],
            ]);

        $this->assertEquals(1, ProductionResult::where('batch_id', $batch->id)->count());
        $this->assertEquals(0, QcResult::count());
        $this->assertEquals(BatchStatus::QC, $batch->fresh()->status);
    }

    public function test_order_aggregation_with_sibling_batches(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'ORD-AGG-' . uniqid(),
            'order_date'  => now()->toDateString(),
            'status'      => OrderStatus::PRODUKSI,
        ]);

        $variant = ProductVariant::create([
            'product_id'        => $this->product->id,
            'variant_name'      => 'Dusty Pink',
            'sku'               => 'SKU-DP-' . uniqid(),
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

        // Batch 1 (100 pcs) at PRODUKSI
        $batch1 = ProductionBatch::create([
            'order_item_id'          => $item->id,
            'batch_no'               => 'BAT-1-' . uniqid(),
            'target_qty'             => 100,
            'status'                 => BatchStatus::PRODUKSI,
            'requires_printing'      => false,
            'production_employee_id' => $this->produksiUser->id,
        ]);
        $bm1 = BatchMaterial::create([
            'batch_id'      => $batch1->id,
            'material_id'   => $this->kain->id,
            'required_qty'  => 100,
            'allocated_qty' => 100,
        ]);

        // Batch 2 (100 pcs) at PRODUKSI
        $batch2 = ProductionBatch::create([
            'order_item_id'          => $item->id,
            'batch_no'               => 'BAT-2-' . uniqid(),
            'target_qty'             => 100,
            'status'                 => BatchStatus::PRODUKSI,
            'requires_printing'      => false,
            'production_employee_id' => $this->produksiUser->id,
        ]);
        $bm2 = BatchMaterial::create([
            'batch_id'      => $batch2->id,
            'material_id'   => $this->kain->id,
            'required_qty'  => 100,
            'allocated_qty' => 100,
        ]);

        // Record result for Batch 1 -> Batch 1 reaches QC
        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch1), [
                'actual_qty'     => 100,
                'result_date'    => now()->toDateString(),
                'material_usage' => [$bm1->id => 100],
            ]);

        $this->assertEquals(BatchStatus::QC, $batch1->fresh()->status);
        $this->assertEquals(BatchStatus::PRODUKSI, $batch2->fresh()->status);

        // Order remains PRODUKSI because Batch 2 is still at PRODUKSI
        $this->assertEquals(OrderStatus::PRODUKSI, $order->fresh()->status);

        // Record result for Batch 2 -> Batch 2 reaches QC
        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch2), [
                'actual_qty'     => 98,
                'result_date'    => now()->toDateString(),
                'material_usage' => [$bm2->id => 98],
            ]);

        $this->assertEquals(BatchStatus::QC, $batch2->fresh()->status);

        // Order now advances to QC
        $this->assertEquals(OrderStatus::QC, $order->fresh()->status);
    }

    public function test_customer_tracking_reflects_qc_status(): void
    {
        [$batch, $order, , $bm] = $this->createBatch(BatchStatus::PRODUKSI, 100);

        $tokenService = app(TrackingTokenService::class);
        $token = $tokenService->createForOrder($order);

        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 100,
                'result_date'    => now()->toDateString(),
                'material_usage' => [$bm->id => 120],
            ]);

        $this->assertEquals(OrderStatus::QC, $order->fresh()->status);

        $response = $this->get("/track/{$token}");
        $response->assertOk();
        $response->assertSee('Pemeriksaan Kualitas (QC)');
    }

    public function test_role_authorization_for_production_result(): void
    {
        [$batch, , , $bm] = $this->createBatch(BatchStatus::PRODUKSI, 100);

        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'status' => UserStatus::ACTIVE]);
        $this->actingAs($admin)
            ->get(route('produksi.batches.production-result.create', $batch))
            ->assertForbidden();
        $this->actingAs($admin)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 100,
                'result_date'    => '2026-08-29',
                'material_usage' => [$bm->id => 120],
            ])
            ->assertForbidden();

        $gudang = User::factory()->create(['role' => UserRole::GUDANG, 'status' => UserStatus::ACTIVE]);
        $this->actingAs($gudang)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 100,
                'result_date'    => '2026-08-29',
                'material_usage' => [$bm->id => 120],
            ])
            ->assertForbidden();

        $owner = User::factory()->create(['role' => UserRole::OWNER, 'status' => UserStatus::ACTIVE]);
        $this->actingAs($owner)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 100,
                'result_date'    => '2026-08-29',
                'material_usage' => [$bm->id => 120],
            ])
            ->assertForbidden();

        $customer = User::factory()->create(['role' => UserRole::CUSTOMER, 'status' => UserStatus::ACTIVE]);
        $this->actingAs($customer)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 100,
                'result_date'    => '2026-08-29',
                'material_usage' => [$bm->id => 120],
            ])
            ->assertForbidden();

        auth()->logout();
        $this->get(route('produksi.batches.production-result.create', $batch))
            ->assertRedirect(route('login'));
    }

    public function test_production_result_create_view_presentation_and_draft_persistence_contract(): void
    {
        [$batch, , , $bm] = $this->createBatch(BatchStatus::PRODUKSI, 19);

        $response = $this->actingAs($this->produksiUser)
            ->get(route('produksi.batches.production-result.create', $batch));

        $response->assertOk();

        // 1. Title does not contain double encoded &amp;amp;
        $response->assertSee('Catat Pemakaian Bahan &amp; Hasil Produksi', false);
        $response->assertDontSee('Catat Pemakaian Bahan &amp;amp;', false);

        // 2. Table header Dipakai Aktual renders cleanly with inline asterisk
        $response->assertSee('Dipakai Aktual');
        $response->assertSee('whitespace-nowrap');

        // 3. Maks helper and example placeholder removed
        $response->assertDontSee('Maks. 19 pcs');
        $response->assertDontSee('Contoh: 98');

        // 4. Draft persistence contract present in DOM
        $response->assertSee('nayara:production-result-draft:' . $batch->batch_no);
        $response->assertSee('saveDraft');
        $response->assertSee('sessionStorage');

        // 5. Verify no ProductionResult exists prematurely
        $this->assertDatabaseMissing('production_results', ['batch_id' => $batch->id]);
    }

    public function test_additional_allocation_updates_stock_without_finalizing_production_result(): void
    {
        [$batch, , , $bm] = $this->createBatch(BatchStatus::PRODUKSI, 19);
        $bm->update(['required_qty' => 19, 'allocated_qty' => 19]);
        $initialStock = $this->kainStock->fresh()->qty_on_hand;

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.materials.additional', [$batch, $bm->id]), [
                'additional_qty' => 2,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Allocation increased from 19 to 21, required remains 19
        $bm->refresh();
        $this->assertEquals(21, (float) $bm->allocated_qty);
        $this->assertEquals(19, (float) $bm->required_qty);

        // Stock decreased by 2
        $this->assertEquals($initialStock - 2, (float) $this->kainStock->fresh()->qty_on_hand);

        // Stock movement OUT created
        $this->assertDatabaseHas('stock_movements', [
            'material_id'   => $this->kain->id,
            'movement_type' => \App\Enums\MovementType::OUT->value,
            'qty'           => '2.0000',
        ]);

        // Batch remains in PRODUKSI and NO ProductionResult is created
        $batch->refresh();
        $this->assertEquals(BatchStatus::PRODUKSI, $batch->status);
        $this->assertDatabaseMissing('production_results', ['batch_id' => $batch->id]);
    }
}
