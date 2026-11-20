<?php

namespace Tests\Feature\Gudang;

use App\Enums\MovementType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Inventory\StockMovement;
use App\Models\Master\Material;
use App\Models\Master\Unit;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockMovementDisplayFormattingTest extends TestCase
{
    use RefreshDatabase;

    private User $gudangUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gudangUser = User::factory()->create([
            'role'   => UserRole::GUDANG,
            'status' => UserStatus::ACTIVE,
        ]);
    }

    public function test_stock_movement_renders_formatted_quantities_without_trailing_zeros(): void
    {
        $unitPcs = Unit::create(['unit_name' => 'Pieces', 'symbol' => 'pcs']);
        $unitMeter = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $unitRoll = Unit::create(['unit_name' => 'Roll', 'symbol' => 'roll']);

        $materialPcs = Material::create(['unit_id' => $unitPcs->id, 'material_name' => 'Kancing Mutiara', 'min_stock' => 10, 'active' => true]);
        $materialMeter = Material::create(['unit_id' => $unitMeter->id, 'material_name' => 'Kain Ceruty Babydoll', 'min_stock' => 10, 'active' => true]);
        $materialRoll = Material::create(['unit_id' => $unitRoll->id, 'material_name' => 'Pita Satin Roll', 'min_stock' => 5, 'active' => true]);

        // 1. OUT discrete pcs: 18.0000 pcs from 274.0000 to 256.0000
        StockMovement::create([
            'material_id'        => $materialPcs->id,
            'movement_type'      => MovementType::OUT,
            'qty'                => '18.0000',
            'qty_before'         => '274.0000',
            'qty_after'          => '256.0000',
            'unit_cost_snapshot' => '500.00',
            'total_cost_snapshot'=> '9000.00',
            'reference_type'     => 'manual_test',
            'reference_id'       => 1,
            'movement_date'      => now()->toDateString(),
            'created_by'         => $this->gudangUser->id,
        ]);

        // 2. IN continuous meter: +20.0000 m
        StockMovement::create([
            'material_id'        => $materialMeter->id,
            'movement_type'      => MovementType::IN,
            'qty'                => '20.0000',
            'qty_before'         => '100.0000',
            'qty_after'          => '120.0000',
            'unit_cost_snapshot' => '25000.00',
            'total_cost_snapshot'=> '500000.00',
            'reference_type'     => 'manual_test',
            'reference_id'       => 2,
            'movement_date'      => now()->toDateString(),
            'created_by'         => $this->gudangUser->id,
        ]);

        // 3. OUT continuous meter: -23.4000 m from 41.0800 to 17.6800
        StockMovement::create([
            'material_id'        => $materialMeter->id,
            'movement_type'      => MovementType::OUT,
            'qty'                => '23.4000',
            'qty_before'         => '41.0800',
            'qty_after'          => '17.6800',
            'unit_cost_snapshot' => '25000.00',
            'total_cost_snapshot'=> '585000.00',
            'reference_type'     => 'manual_test',
            'reference_id'       => 3,
            'movement_date'      => now()->toDateString(),
            'created_by'         => $this->gudangUser->id,
        ]);

        // 4. OUT continuous roll: -0.3960 roll from 83.3600 to 82.9640 -> 82.9600
        StockMovement::create([
            'material_id'        => $materialRoll->id,
            'movement_type'      => MovementType::OUT,
            'qty'                => '0.3960',
            'qty_before'         => '83.3600',
            'qty_after'          => '82.9600',
            'unit_cost_snapshot' => '150000.00',
            'total_cost_snapshot'=> '59400.00',
            'reference_type'     => 'manual_test',
            'reference_id'       => 4,
            'movement_date'      => now()->toDateString(),
            'created_by'         => $this->gudangUser->id,
        ]);

        // 5. OUT continuous roll: -0.4000 roll
        StockMovement::create([
            'material_id'        => $materialRoll->id,
            'movement_type'      => MovementType::OUT,
            'qty'                => '0.4000',
            'qty_before'         => '10.0000',
            'qty_after'          => '9.6000',
            'unit_cost_snapshot' => '150000.00',
            'total_cost_snapshot'=> '60000.00',
            'reference_type'     => 'manual_test',
            'reference_id'       => 5,
            'movement_date'      => now()->toDateString(),
            'created_by'         => $this->gudangUser->id,
        ]);

        // 6. OUT continuous meter: -17.6800 m
        StockMovement::create([
            'material_id'        => $materialMeter->id,
            'movement_type'      => MovementType::OUT,
            'qty'                => '17.6800',
            'qty_before'         => '17.6800',
            'qty_after'          => '0.0000',
            'unit_cost_snapshot' => '25000.00',
            'total_cost_snapshot'=> '442000.00',
            'reference_type'     => 'manual_test',
            'reference_id'       => 6,
            'movement_date'      => now()->toDateString(),
            'created_by'         => $this->gudangUser->id,
        ]);

        $response = $this->actingAs($this->gudangUser)
            ->get(route('gudang.stock-movements.index'));

        $response->assertOk();

        // Signed quantities verification
        $response->assertSee('-18 pcs');
        $response->assertSee('+20 m');
        $response->assertSee('-23,4 m');
        $response->assertSee('-0,396 roll');
        $response->assertSee('-0,4 roll');
        $response->assertSee('-17,68 m');

        // No ugly database trailing zeroes
        $response->assertDontSee('-18,0000 pcs');
        $response->assertDontSee('+20,0000 m');
        $response->assertDontSee('-23,4000 m');
        $response->assertDontSee('-0,3960 roll');
        $response->assertDontSee('-0,4000 roll');
        $response->assertDontSee('-17,6800 m');

        // Before & After verification
        $response->assertSee('274 &rarr; 256', false);
        $response->assertSee('41,08 &rarr; 17,68', false);
        $response->assertSee('83,36 &rarr; 82,96', false);
        $response->assertDontSee('274,00 &rarr; 256,00', false);
    }

    public function test_stock_movement_loads_batch_materials_and_production_batches_references_with_tenant_isolation(): void
    {
        config(['nayara.tenant_isolation_enabled' => true]);

        $outlet = \App\Models\System\Outlet::create([
            'outlet_name' => 'Nayara Test Outlet',
            'outlet_code' => 'OUT-TEST-01',
            'status'      => 'ACTIVE',
        ]);

        $this->gudangUser->update([
            'outlet_id' => $outlet->id,
        ]);

        $unitMeter = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $materialMeter = Material::create([
            'outlet_id'     => $outlet->id,
            'unit_id'       => $unitMeter->id,
            'material_name' => 'Kain Viscose Premium',
            'min_stock'     => 10,
            'active'        => true,
        ]);

        $customer = \App\Models\Master\Customer::create([
            'outlet_id'     => $outlet->id,
            'customer_name' => 'Customer Test',
        ]);

        $order = \App\Models\Sales\Order::create([
            'outlet_id'   => $outlet->id,
            'customer_id' => $customer->id,
            'order_no'    => 'ORD-TEST-01',
            'order_date'  => now(),
            'status'      => \App\Enums\OrderStatus::PRODUKSI,
        ]);

        $product = \App\Models\Master\Product::create([
            'outlet_id'    => $outlet->id,
            'product_name' => 'Pashmina Viscose',
        ]);

        $variant = \App\Models\Master\ProductVariant::create([
            'product_id'   => $product->id,
            'variant_name' => 'Navy',
            'sku'          => 'PAS-NAVY',
            'price'        => 50000,
        ]);

        $orderItem = \App\Models\Sales\OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 50000,
        ]);

        $batch = \App\Models\Production\ProductionBatch::create([
            'order_item_id' => $orderItem->id,
            'batch_no'      => 'BATCH-TEST-99',
            'target_qty'    => 10,
            'status'        => \App\Enums\BatchStatus::PRODUKSI,
        ]);

        $batchMaterial = \App\Models\Production\BatchMaterial::create([
            'batch_id'     => $batch->id,
            'material_id'  => $materialMeter->id,
            'required_qty' => 15,
        ]);

        // Stock movement referencing batch_materials
        StockMovement::create([
            'outlet_id'           => $outlet->id,
            'material_id'         => $materialMeter->id,
            'movement_type'       => MovementType::OUT,
            'qty'                 => '15.0000',
            'qty_before'          => '100.0000',
            'qty_after'           => '85.0000',
            'unit_cost_snapshot'  => '20000.00',
            'total_cost_snapshot' => '300000.00',
            'reference_type'      => 'batch_materials',
            'reference_id'        => $batchMaterial->id,
            'movement_date'       => now()->toDateString(),
            'created_by'          => $this->gudangUser->id,
        ]);

        // Stock movement referencing production_batches
        StockMovement::create([
            'outlet_id'           => $outlet->id,
            'material_id'         => $materialMeter->id,
            'movement_type'       => MovementType::IN,
            'qty'                 => '5.0000',
            'qty_before'          => '85.0000',
            'qty_after'           => '90.0000',
            'unit_cost_snapshot'  => '20000.00',
            'total_cost_snapshot' => '100000.00',
            'reference_type'      => 'production_batches',
            'reference_id'        => $batch->id,
            'movement_date'       => now()->toDateString(),
            'created_by'          => $this->gudangUser->id,
        ]);

        $response = $this->actingAs($this->gudangUser)
            ->get(route('gudang.stock-movements.index'));

        $response->assertOk();
        $response->assertSee('BATCH-TEST-99');
    }
}
