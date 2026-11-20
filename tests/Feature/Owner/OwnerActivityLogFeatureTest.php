<?php

namespace Tests\Feature\Owner;

use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Master\Customer;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Production\ProductionBatch;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\Outlet;
use App\Models\System\OutletActivityLog;
use App\Models\System\User;
use App\Services\System\TenantActivitySyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerActivityLogFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected Outlet $outlet;
    protected User $owner;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outlet = Outlet::create([
            'outlet_name' => 'Nayara Test Outlet',
            'outlet_code' => 'OUT-TEST-01',
            'status'      => 'ACTIVE',
        ]);

        $this->owner = User::create([
            'name'      => 'Owner Toko Test',
            'email'     => 'owner@example.com',
            'password'  => bcrypt('password'),
            'role'      => UserRole::OWNER,
            'outlet_id' => $this->outlet->id,
            'status'    => 'ACTIVE',
        ]);

        $this->admin = User::create([
            'name'      => 'Sinta Admin',
            'email'     => 'admin@example.com',
            'password'  => bcrypt('password'),
            'role'      => UserRole::ADMIN,
            'outlet_id' => $this->outlet->id,
            'status'    => 'ACTIVE',
        ]);
    }

    public function test_owner_can_access_activity_logs_and_synchronizes_backend_data(): void
    {
        // Create an order in backend
        $customer = Customer::create([
            'outlet_id'     => $this->outlet->id,
            'customer_name' => 'Raisya Hijab',
        ]);

        $order = Order::create([
            'outlet_id'    => $this->outlet->id,
            'customer_id'  => $customer->id,
            'order_no'     => 'ORD-202611-0001',
            'order_date'   => now(),
            'status'       => OrderStatus::PRODUKSI,
            'total_amount' => 500000,
        ]);

        $product = Product::create([
            'outlet_id'    => $this->outlet->id,
            'product_name' => 'Pashmina Viscose',
        ]);

        $variant = ProductVariant::create([
            'product_id'   => $product->id,
            'variant_name' => 'Navy',
            'sku'          => 'PAS-NAVY',
            'price'        => 50000,
        ]);

        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 50000,
        ]);

        $batch = ProductionBatch::create([
            'order_item_id' => $orderItem->id,
            'batch_no'      => 'BAT-202611-0001',
            'target_qty'    => 10,
            'status'        => BatchStatus::PRODUKSI,
        ]);

        // When Owner opens activity logs, index triggers sync
        $response = $this->actingAs($this->owner)->get(route('owner.activity-logs.index'));

        $response->assertOk();
        $response->assertSee('Log Aktivitas Konveksi');
        $response->assertSee('ORD-202611-0001');
        $response->assertSee('BAT-202611-0001');
        $response->assertDontSee('.0000 pcs');
        $response->assertDontSee('clock</'); // header icon should not render literal text 'clock'
    }

    public function test_activity_log_displays_time_in_asia_jakarta_wib(): void
    {
        // Log created with explicit UTC timestamp
        $log = new OutletActivityLog();
        $log->timestamps = false;
        $log->outlet_id = $this->outlet->id;
        $log->user_name = 'Sinta';
        $log->user_role = 'ADMIN';
        $log->module = 'SALES_ORDER';
        $log->action_type = 'CREATE_ORDER';
        $log->reference_no = 'ORD-TZ-01';
        $log->description = 'Timezone test order.';
        $log->created_at = \Carbon\Carbon::parse('2026-11-19 06:15:30', 'UTC');
        $log->updated_at = \Carbon\Carbon::parse('2026-11-19 06:15:30', 'UTC');
        $log->save();

        $response = $this->actingAs($this->owner)->get(route('owner.activity-logs.index'));
        $response->assertOk();
        // In Asia/Jakarta (UTC+7), 06:15:30 UTC becomes 13:15:30 WIB
        $response->assertSee('13:15:30 WIB');
        $response->assertDontSee('06:15:30 WIB');
    }

    public function test_activity_log_filters_by_module(): void
    {
        TenantActivitySyncService::syncOutletLogs($this->outlet->id);

        OutletActivityLog::create([
            'outlet_id'    => $this->outlet->id,
            'user_name'    => 'Sinta',
            'user_role'    => 'ADMIN',
            'module'       => 'SALES_ORDER',
            'action_type'  => 'CREATE_ORDER',
            'reference_no' => 'ORD-FILTER-01',
            'description'  => 'Sales order filter test.',
        ]);

        OutletActivityLog::create([
            'outlet_id'    => $this->outlet->id,
            'user_name'    => 'Agus',
            'user_role'    => 'PRODUKSI',
            'module'       => 'PRODUKSI',
            'action_type'  => 'RECORD_PRODUCTION',
            'reference_no' => 'BAT-FILTER-01',
            'description'  => 'Production filter test.',
        ]);

        // Filter by SALES_ORDER
        $response = $this->actingAs($this->owner)->get(route('owner.activity-logs.index', ['module' => 'SALES_ORDER']));
        $response->assertOk();
        $response->assertSee('ORD-FILTER-01');
        $response->assertDontSee('BAT-FILTER-01');

        // Filter by PRODUKSI
        $response2 = $this->actingAs($this->owner)->get(route('owner.activity-logs.index', ['module' => 'PRODUKSI']));
        $response2->assertOk();
        $response2->assertSee('BAT-FILTER-01');
        $response2->assertDontSee('ORD-FILTER-01');
    }
}
