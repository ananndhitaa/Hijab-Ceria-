<?php

namespace Tests\Feature\Gudang;

use App\Enums\BatchStatus;
use App\Enums\MovementType;
use App\Enums\OrderStatus;
use App\Enums\PurchaseStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Inventory\MaterialStock;
use App\Models\Inventory\Purchase;
use App\Models\Inventory\PurchaseItem;
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
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\OrderStatusHistory;
use App\Models\System\OrderTrackingToken;
use App\Models\System\Shipment;
use App\Models\System\User;
use App\Services\Tracking\TrackingTokenService;
use App\Support\DateTimeFormatter;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimezoneAuditDisplayTest extends TestCase
{
    use RefreshDatabase;

    private User $gudangUser;
    private User $adminUser;
    private User $customerUser;
    private Customer $customer;
    private Unit $meter;
    private Material $material;
    private Product $product;
    private ProductVariant $variant;
    private Order $order;
    private ProductionBatch $batch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gudangUser = User::factory()->create([
            'role'   => UserRole::GUDANG,
            'status' => UserStatus::ACTIVE,
            'name'   => 'Staf Gudang Test',
        ]);

        $this->adminUser = User::factory()->create([
            'role'   => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
            'name'   => 'Admin Test',
        ]);

        $this->customerUser = User::factory()->create([
            'role'   => UserRole::CUSTOMER,
            'status' => UserStatus::ACTIVE,
            'name'   => 'Aisyah',
        ]);

        $this->customer = Customer::create([
            'user_id'       => $this->customerUser->id,
            'customer_name' => 'Toko Aisyah Hijab',
            'phone'         => '081234567890',
            'address'       => 'Jl. Sukajadi No. 12',
            'active'        => true,
        ]);

        $this->meter = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);

        $this->material = Material::create([
            'material_code' => 'MAT-CERUTY',
            'material_name' => 'Kain Ceruty Baby Doll',
            'unit_id'       => $this->meter->id,
            'active'        => true,
        ]);

        MaterialStock::create([
            'material_id'   => $this->material->id,
            'qty_on_hand'   => '50.0000',
            'avg_unit_cost' => '25000.0000',
        ]);

        $this->product = Product::create([
            'product_name' => 'Pashmina Ceruty Premium',
            'active'       => true,
        ]);

        $this->variant = ProductVariant::create([
            'product_id'   => $this->product->id,
            'variant_name' => 'Pashmina Moka',
            'sku'          => 'PASH-MOKA',
            'active'       => true,
        ]);

        BomItem::create([
            'variant_id'   => $this->variant->id,
            'material_id'  => $this->material->id,
            'qty_per_unit' => '2.0000',
        ]);

        $this->order = Order::create([
            'order_no'      => 'SO-202609-0001',
            'customer_id'   => $this->customer->id,
            'order_date'    => '2026-09-30',
            'status'        => OrderStatus::PACKING,
            'total_amount'  => '5700000.00',
            'created_by'    => $this->adminUser->id,
        ]);

        $item = OrderItem::create([
            'order_id'            => $this->order->id,
            'variant_id'          => $this->variant->id,
            'qty_ordered'         => 10,
            'unit_selling_price'  => '570000.00',
            'subtotal'            => '5700000.00',
        ]);

        $this->batch = ProductionBatch::create([
            'batch_no'      => 'BAT-202609-0001',
            'order_item_id' => $item->id,
            'target_qty'    => 10,
            'status'        => BatchStatus::SELESAI,
            'created_by'    => $this->adminUser->id,
        ]);
    }

    public function test_mrp_calculated_at_displayed_in_wib(): void
    {
        // 04:00:00 UTC corresponds to 11:00:00 Asia/Jakarta (WIB)
        $utcCalculatedAt = Carbon::create(2026, 9, 30, 4, 0, 0, 'UTC');

        $bm = BatchMaterial::create([
            'batch_id'             => $this->batch->id,
            'material_id'          => $this->material->id,
            'planned_qty'          => '20.0000',
            'allocated_qty'        => '0.0000',
            'used_qty'             => '0.0000',
            'stock_at_calculation' => '50.0000',
            'shortage_at_calc'     => '0.0000',
            'calculated_at'        => $utcCalculatedAt,
        ]);

        $response = $this->actingAs($this->gudangUser)
            ->get(route('gudang.material-planning.show', $this->batch));

        $response->assertOk();
        // Must show converted 11:00 WIB, NOT 04:00
        $response->assertSee('Dihitung pada: 30 September 2026, 11:00 WIB');
        $response->assertDontSee('Dihitung pada: 30 September 2026, 04:00');

        // Historical raw DB timestamp is not mutated
        $this->assertEquals('2026-09-30 04:00:00', $bm->fresh()->getRawOriginal('calculated_at'));
    }

    public function test_mrp_allocated_at_displayed_in_wib(): void
    {
        // Allocation at 04:15:00 UTC corresponds to 11:15:00 WIB
        $utcAllocatedAt = Carbon::create(2026, 9, 30, 4, 15, 0, 'UTC');

        $bm = BatchMaterial::create([
            'batch_id'             => $this->batch->id,
            'material_id'          => $this->material->id,
            'planned_qty'          => '20.0000',
            'allocated_qty'        => '20.0000',
            'used_qty'             => '0.0000',
            'stock_at_calculation' => '50.0000',
            'shortage_at_calc'     => '0.0000',
            'calculated_at'        => Carbon::create(2026, 9, 30, 4, 0, 0, 'UTC'),
        ]);

        $movement = StockMovement::create([
            'material_id'         => $this->material->id,
            'movement_type'       => MovementType::OUT,
            'qty'                 => '20.0000',
            'unit_cost_snapshot'  => '25000.0000',
            'total_cost_snapshot' => '500000.00',
            'qty_before'          => '50.0000',
            'qty_after'           => '30.0000',
            'reference_type'      => 'batch_materials',
            'reference_id'        => $bm->id,
            'movement_date'       => '2026-09-30',
            'notes'               => 'Alokasi bahan batch',
            'created_by'          => $this->gudangUser->id,
        ]);

        // Manually update created_at to deterministic UTC time
        StockMovement::where('id', $movement->id)->update(['created_at' => $utcAllocatedAt]);

        $response = $this->actingAs($this->gudangUser)
            ->get(route('gudang.material-planning.show', $this->batch));

        $response->assertOk();
        // Dialokasikan: 30 September 2026, 11:15 WIB
        $response->assertSee('30 September 2026, 11:15 WIB');
    }

    public function test_stock_movement_created_at_displayed_in_wib(): void
    {
        // 04:30:00 UTC = 11:30:00 WIB
        $utcTime = Carbon::create(2026, 9, 30, 4, 30, 0, 'UTC');

        $movement = StockMovement::create([
            'material_id'         => $this->material->id,
            'movement_type'       => MovementType::IN,
            'qty'                 => '10.0000',
            'unit_cost_snapshot'  => '25000.0000',
            'total_cost_snapshot' => '250000.00',
            'qty_before'          => '40.0000',
            'qty_after'           => '50.0000',
            'movement_date'       => '2026-09-30',
            'notes'               => 'Penerimaan stok test',
            'created_by'          => $this->gudangUser->id,
        ]);

        StockMovement::where('id', $movement->id)->update(['created_at' => $utcTime]);

        $response = $this->actingAs($this->gudangUser)
            ->get(route('gudang.stock-movements.index'));

        $response->assertOk();
        $response->assertSee('30/09/2026');
        $response->assertSee('11:30 WIB');
    }

    public function test_purchase_history_received_at_displayed_in_wib_and_po_date_preserved(): void
    {
        $supplier = Supplier::create([
            'supplier_name' => 'Supplier Kain',
            'active'        => true,
        ]);

        $purchase = Purchase::create([
            'supplier_id'   => $supplier->id,
            'purchase_no'   => 'PO-202609-0099',
            'purchase_date' => '2026-09-30', // DATE-only
            'status'        => PurchaseStatus::RECEIVED,
        ]);

        $item = PurchaseItem::create([
            'purchase_id'   => $purchase->id,
            'material_id'   => $this->material->id,
            'qty'           => '10.0000',
            'unit_price'    => '25000.00',
            'qty_received'  => '10.0000',
        ]);

        $utcReceivedAt = Carbon::create(2026, 9, 30, 6, 45, 0, 'UTC'); // 13:45 WIB

        $movement = StockMovement::create([
            'material_id'         => $this->material->id,
            'movement_type'       => MovementType::IN,
            'qty'                 => '10.0000',
            'unit_cost_snapshot'  => '25000.0000',
            'total_cost_snapshot' => '250000.00',
            'qty_before'          => '0.0000',
            'qty_after'           => '10.0000',
            'reference_type'      => 'purchase_items',
            'reference_id'        => $item->id,
            'movement_date'       => '2026-09-30',
            'created_by'          => $this->gudangUser->id,
        ]);

        StockMovement::where('id', $movement->id)->update(['created_at' => $utcReceivedAt]);

        $response = $this->actingAs($this->gudangUser)
            ->get(route('gudang.purchases.history'));

        $response->assertOk();
        // PO date DATE-only is preserved as 30 Sep 2026
        $response->assertSee('30 Sep 2026');
        // received_at is 13:45 WIB
        $response->assertSee('13:45 WIB');
        $response->assertDontSee('06:45 WIB');
    }

    public function test_order_status_history_displays_wib_in_admin(): void
    {
        $utcChangedAt = Carbon::create(2026, 9, 30, 2, 20, 0, 'UTC'); // 09:20 WIB

        OrderStatusHistory::create([
            'order_id'           => $this->order->id,
            'status'             => OrderStatus::DEAL,
            'changed_at'         => $utcChangedAt,
            'changed_by_user_id' => $this->adminUser->id,
            'notes'              => 'Order dikonfirmasi admin',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.orders.show', $this->order));

        $response->assertOk();
        $response->assertSee('30/09/2026 09:20 WIB');
    }

    public function test_shipment_shipped_at_displayed_in_wib_in_tracking_and_customer_portal(): void
    {
        $rawToken = app(TrackingTokenService::class)->createForOrder($this->order);

        $utcShippedAt = Carbon::create(2026, 9, 30, 8, 10, 0, 'UTC'); // 15:10 WIB

        Shipment::create([
            'order_id'          => $this->order->id,
            'courier'           => 'JNE',
            'courier_service'   => 'REG',
            'tracking_no'       => 'JNE9876543210',
            'recipient_address' => 'Jl. Sukajadi No. 12',
            'shipped_at'        => $utcShippedAt,
        ]);

        // 1. Public tracking
        $publicResponse = $this->get(route('track.show', $rawToken));
        $publicResponse->assertOk();
        $publicResponse->assertSee('30 Sep 2026, 15:10 WIB');

        // 2. Customer portal
        $portalResponse = $this->actingAs($this->customerUser)
            ->get(route('customer.orders.show', $this->order));
        $portalResponse->assertOk();
        $portalResponse->assertSee('30 Sep 2026, 15:10 WIB');
    }

    public function test_no_manual_add_hours_seven_in_views(): void
    {
        $viewsPath = resource_path('views');
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($viewsPath));

        foreach ($files as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $content = file_get_contents($file->getPathname());
                $this->assertStringNotContainsString(
                    'addHours(7)',
                    $content,
                    "File {$file->getPathname()} contains prohibited manual 'addHours(7)' arithmetic."
                );
            }
        }
    }
}
