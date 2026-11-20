<?php

namespace Tests\Feature\Gudang;

use App\Enums\BatchStatus;
use App\Enums\MovementType;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Inventory\MaterialStock;
use App\Models\Inventory\StockMovement;
use App\Models\Master\BomItem;
use App\Models\Master\Customer;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Master\Unit;
use App\Models\Production\BatchMaterial;
use App\Models\Production\ProductionBatch;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\User;
use App\Services\Inventory\BatchMaterialPlanningService;
use App\Services\Inventory\MaterialAllocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaterialAllocationFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $gudangUser;
    private Material $kainKatun;
    private Unit $meter;
    private BatchMaterialPlanningService $mrpService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mrpService = app(BatchMaterialPlanningService::class);

        $this->gudangUser = User::factory()->create([
            'role'   => UserRole::GUDANG,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->meter = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);

        $this->kainKatun = Material::create([
            'unit_id'       => $this->meter->id,
            'material_name' => 'Kain Katun Rayon',
            'min_stock'     => 20,
            'active'        => true,
        ]);
        MaterialStock::create([
            'material_id'   => $this->kainKatun->id,
            'qty_on_hand'   => '150.0000',
            'avg_unit_cost' => '35000.0000',
        ]);
    }

    private function createBatch(string $batchNo, int $targetQty, float $bomQty = 1.0): ProductionBatch
    {
        $customer = Customer::create(['customer_name' => "Customer {$batchNo}"]);
        $product = Product::create(['product_name' => "Produk {$batchNo}", 'active' => true]);
        $variant = ProductVariant::create([
            'product_id'   => $product->id,
            'variant_name' => 'Default',
            'sku'          => "SKU-{$batchNo}",
            'active'       => true,
        ]);
        BomItem::create([
            'variant_id'   => $variant->id,
            'material_id'  => $this->kainKatun->id,
            'qty_per_unit' => $bomQty,
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'order_no'    => "SO-{$batchNo}",
            'order_date'  => '2026-08-28',
            'status'      => OrderStatus::DEAL,
        ]);
        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => $targetQty,
            'unit_selling_price' => 80000,
        ]);
        $batch = ProductionBatch::create([
            'order_item_id' => $orderItem->id,
            'batch_no'      => $batchNo,
            'target_qty'    => $targetQty,
            'status'        => BatchStatus::BAHAN,
        ]);

        $this->mrpService->generateMaterialPlanning($batch);

        return $batch;
    }

    public function test_gudang_can_allocate_batch_materials(): void
    {
        $batch = $this->createBatch('BAT-202608-0201', 100, 1.25); // Needs 125 m

        $response = $this->actingAs($this->gudangUser)
            ->post(route('gudang.material-planning.allocate', $batch));

        $response->assertRedirect(route('gudang.material-planning.show', $batch));
        $response->assertSessionHas('success');

        $stock = $this->kainKatun->fresh(['stock'])->stock;
        $this->assertEquals('25.0000', $stock->qty_on_hand); // 150 - 125

        $bm = BatchMaterial::where('batch_id', $batch->id)->first();
        $this->assertEquals('125.0000', $bm->allocated_qty);
        $this->assertEquals('0.0000', $bm->used_qty);
    }

    public function test_allocation_ignores_client_tampering_payload(): void
    {
        $batch = $this->createBatch('BAT-202608-0202', 100, 1.0); // Needs 100 m

        $response = $this->actingAs($this->gudangUser)
            ->post(route('gudang.material-planning.allocate', $batch), [
                'required_qty'  => '1',
                'allocated_qty' => '99999',
                'unit_cost'     => '100',
            ]);

        $response->assertRedirect(route('gudang.material-planning.show', $batch));

        // Persisted server data determines exact allocation (100 m)
        $bm = BatchMaterial::where('batch_id', $batch->id)->first();
        $this->assertEquals('100.0000', $bm->allocated_qty);
        $this->assertEquals('50.0000', $this->kainKatun->fresh(['stock'])->stock->qty_on_hand);
    }

    public function test_mrp_detail_displays_allocated_state_and_removes_po_cta(): void
    {
        $batch = $this->createBatch('BAT-202608-0203', 100, 1.0);
        app(MaterialAllocationService::class)->allocateBatchMaterials($batch, $this->gudangUser->id);

        $response = $this->actingAs($this->gudangUser)
            ->get(route('gudang.material-planning.show', $batch));

        $response->assertOk();
        $response->assertSee('Bahan Telah Dialokasikan');
        $response->assertSee('TERALOKASI');
        $response->assertDontSee('Buat Purchase Order');
        $response->assertDontSee('Alokasikan Bahan');
    }

    public function test_multiple_batch_semantics_allocated_batch_is_ready_while_unallocated_batch_shows_shortage(): void
    {
        // Warehouse stock starts at 150 m
        // Batch A requires 100 m
        $batchA = $this->createBatch('BAT-202608-0204A', 100, 1.0);
        // Batch B requires 80 m
        $batchB = $this->createBatch('BAT-202608-0204B', 80, 1.0);

        // Allocate Batch A
        $this->actingAs($this->gudangUser)
            ->post(route('gudang.material-planning.allocate', $batchA));

        // Warehouse stock is now 50 m (150 - 100)
        $this->assertEquals('50.0000', $this->kainKatun->fresh(['stock'])->stock->qty_on_hand);

        // Batch A MRP Data: TERALOKASI, effective shortage = 0, is_shortage = false
        $mrpA = $this->mrpService->getMaterialRequirementsData($batchA);
        $this->assertTrue($mrpA['is_fully_allocated']);
        $this->assertFalse($mrpA['has_shortage']);
        $this->assertEquals('0.0000', $mrpA['items'][0]['effective_shortage_qty']);
        $this->assertEquals('TERALOKASI', $mrpA['items'][0]['status_label']);

        // Batch B MRP Data: UNALLOCATED, required = 80, current stock = 50 -> live shortage = 30 (KURANG)
        $mrpB = $this->mrpService->getMaterialRequirementsData($batchB);
        $this->assertFalse($mrpB['is_fully_allocated']);
        $this->assertTrue($mrpB['has_shortage']);
        $this->assertEquals('30.0000', $mrpB['items'][0]['live_shortage_qty']);
        $this->assertEquals('KURANG', $mrpB['items'][0]['status_label']);
    }

    public function test_stock_movement_history_shows_out_movement_with_batch_link(): void
    {
        $batch = $this->createBatch('BAT-202608-0205', 100, 1.0);
        $this->actingAs($this->gudangUser)
            ->post(route('gudang.material-planning.allocate', $batch));

        $response = $this->actingAs($this->gudangUser)
            ->get(route('gudang.stock-movements.index'));

        $response->assertOk();
        $response->assertSee('Stok Keluar');
        $response->assertSee('-100 m');
        $response->assertSee($batch->batch_no);
    }

    public function test_authorization_for_allocation(): void
    {
        $batch = $this->createBatch('BAT-202608-0206', 100, 1.0);

        // Guest is redirected
        $this->post(route('gudang.material-planning.allocate', $batch))
            ->assertRedirect(route('login'));

        // Unauthorized roles get 403
        foreach ([UserRole::ADMIN, UserRole::PRODUKSI, UserRole::OWNER, UserRole::CUSTOMER] as $role) {
            $user = User::factory()->create(['role' => $role, 'status' => UserStatus::ACTIVE]);

            $this->actingAs($user)
                ->post(route('gudang.material-planning.allocate', $batch))
                ->assertForbidden();
        }
    }
}
