<?php

namespace Tests\Feature\System;

use App\Enums\BatchStatus;
use App\Enums\MaterialReadiness;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Inventory\MaterialStock;
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
use App\Support\DateTimeFormatter;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionTargetDateRolePrivacyTest extends TestCase
{
    use RefreshDatabase;

    private User $gudangUser;
    private User $produksiUser;
    private User $adminUser;
    private User $ownerUser;
    private ProductionBatch $batch;
    private Carbon $targetDate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gudangUser = User::factory()->create([
            'name'   => 'Staf Gudang',
            'role'   => UserRole::GUDANG,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->produksiUser = User::factory()->create([
            'name'   => 'Supervisor Produksi',
            'role'   => UserRole::PRODUKSI,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->adminUser = User::factory()->create([
            'name'   => 'Admin Nayara',
            'role'   => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->ownerUser = User::factory()->create([
            'name'   => 'Owner Nayara',
            'role'   => UserRole::OWNER,
            'status' => UserStatus::ACTIVE,
        ]);

        $meter = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $product = Product::create(['product_name' => 'Gamis Silk Luxury', 'active' => true]);
        $variant = ProductVariant::create([
            'product_id'   => $product->id,
            'variant_name' => 'Burgundy',
            'sku'          => 'GMS-SLK-BRG',
            'price'        => 225000,
            'active'       => true,
        ]);

        $material = Material::create([
            'unit_id'       => $meter->id,
            'material_name' => 'Silk Armani Premium',
            'min_stock'     => '10.0000',
            'active'        => true,
        ]);

        MaterialStock::create([
            'material_id'   => $material->id,
            'qty_on_hand'   => '250.0000',
            'avg_unit_cost' => '75000.0000',
        ]);

        $customer = Customer::create([
            'customer_name' => 'Boutique Zafira',
            'phone'         => '081299887766',
            'active'        => true,
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'order_no'    => 'SO-202610-0999',
            'order_date'  => Carbon::create(2026, 10, 1),
            'status'      => OrderStatus::PRODUKSI,
        ]);

        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 50,
            'unit_selling_price' => 225000,
        ]);

        // Specific future target date that won't conflict with current date formatting
        $this->targetDate = Carbon::create(2026, 11, 28);

        $this->batch = ProductionBatch::create([
            'order_item_id'      => $orderItem->id,
            'batch_no'           => 'BAT-202610-0999',
            'target_qty'         => 50,
            'status'             => BatchStatus::BAHAN,
            'planned_start_date' => Carbon::create(2026, 11, 20),
            'planned_end_date'   => $this->targetDate,
        ]);

        BatchMaterial::create([
            'batch_id'      => $this->batch->id,
            'material_id'   => $material->id,
            'required_qty'  => '125.0000',
            'allocated_qty' => '0.0000',
            'stock_at_calc' => '250.0000',
            'calculated_at' => Carbon::create(2026, 10, 1, 10, 0, 0, 'Asia/Jakarta'),
        ]);
    }

    /**
     * 1. Gudang MRP list does NOT show target completion date.
     */
    public function test_gudang_mrp_list_does_not_show_target_completion_date(): void
    {
        $response = $this->actingAs($this->gudangUser)
            ->get(route('gudang.material-planning.index'));

        $response->assertOk();
        $response->assertDontSee('Target Selesai');
        $response->assertDontSee('28 Nov 2026');
        $response->assertSee('BAT-202610-0999');
        $response->assertSee('50 pcs');
    }

    /**
     * 2. Gudang MRP detail does NOT show target completion date.
     */
    public function test_gudang_mrp_detail_does_not_show_target_completion_date(): void
    {
        $response = $this->actingAs($this->gudangUser)
            ->get(route('gudang.material-planning.show', $this->batch));

        $response->assertOk();
        $response->assertDontSee('Target Selesai');
        $response->assertDontSee('28 Nov 2026');
        $response->assertSee('Target Batch');
        $response->assertSee('50 pcs');
    }

    /**
     * 3. Gudang dashboard action queue does NOT show production target date.
     */
    public function test_gudang_dashboard_does_not_show_production_target_date(): void
    {
        $response = $this->actingAs($this->gudangUser)
            ->get(route('gudang.dashboard'));

        $response->assertOk();
        $response->assertDontSee('Target: 28/11/2026');
        $response->assertDontSee('Target 28 Nov');
    }

    /**
     * 4. Produksi DOES show production target date and start date.
     */
    public function test_produksi_does_show_production_target_date(): void
    {
        // Batch list in Produksi
        $listResponse = $this->actingAs($this->produksiUser)
            ->get(route('produksi.batches.index'));

        $listResponse->assertOk();
        $listResponse->assertSee('Target Selesai');
        $listResponse->assertSee('28 Nov 2026');

        // Batch detail in Produksi
        $showResponse = $this->actingAs($this->produksiUser)
            ->get(route('produksi.batches.show', $this->batch));

        $showResponse->assertOk();
        $showResponse->assertSee('Target Selesai');
        $showResponse->assertSee('28 Nov 2026');
    }

    /**
     * 5. Admin can still set planned_start_date and planned_end_date.
     */
    public function test_admin_can_still_set_planned_dates(): void
    {
        // 1. StoreProductionBatchRequest retains planned_start_date and planned_end_date validation
        $request = new \App\Http\Requests\Admin\StoreProductionBatchRequest();
        $rules = $request->rules();
        $this->assertArrayHasKey('planned_start_date', $rules);
        $this->assertArrayHasKey('planned_end_date', $rules);
        $this->assertContains('required', $rules['planned_end_date']);

        // 2. Admin batches view still shows the planned dates
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.batches.show', $this->batch));

        $response->assertOk();
        $response->assertSee('28 November 2026');
    }

    /**
     * 6. Database planned dates remain intact and unchanged.
     */
    public function test_database_planned_dates_remain_intact(): void
    {
        $fresh = $this->batch->fresh();
        $this->assertNotNull($fresh->planned_start_date);
        $this->assertNotNull($fresh->planned_end_date);
        $this->assertEquals('2026-11-20', $fresh->planned_start_date->toDateString());
        $this->assertEquals('2026-11-28', $fresh->planned_end_date->toDateString());
    }

    /**
     * 7. Owner and backend analytics retain target dates.
     */
    public function test_owner_and_backend_analytics_retain_target_dates(): void
    {
        $countOverdue = ProductionBatch::where('planned_end_date', '<', now())->count();
        $this->assertIsInt($countOverdue);

        $countUpcoming = ProductionBatch::where('planned_end_date', '>=', now())->count();
        $this->assertGreaterThanOrEqual(1, $countUpcoming);

        $fresh = ProductionBatch::find($this->batch->id);
        $this->assertEquals('2026-11-28', $fresh->planned_end_date->format('Y-m-d'));
    }
}
