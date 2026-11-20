<?php

namespace Tests\Feature\Produksi;

use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Production\ProductionBatch;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProduksiAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $produksiUser;
    private ProductionBatch $batch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->produksiUser = User::factory()->create([
            'name'   => 'Staff Produksi',
            'role'   => UserRole::PRODUKSI,
            'status' => UserStatus::ACTIVE,
        ]);

        $product = Product::create(['product_name' => 'Hijab Bergo', 'active' => true]);
        $variant = ProductVariant::create([
            'product_id'        => $product->id,
            'variant_name'      => 'Hitam',
            'sku'               => 'HJB-BRG-HTM',
            'requires_printing' => false,
            'active'            => true,
        ]);

        $customer = \App\Models\Master\Customer::create(['customer_name' => 'Customer Test', 'phone' => '08123456789']);

        $order = Order::create([
            'customer_id' => $customer->id,
            'order_no'    => 'ORD-202608-001',
            'order_date'  => now()->toDateString(),
            'status'      => OrderStatus::BAHAN,
        ]);

        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 100,
            'unit_selling_price' => 50000,
            'subtotal'           => 5000000,
        ]);

        $this->batch = ProductionBatch::create([
            'order_item_id'          => $orderItem->id,
            'batch_no'               => 'BAT-202608-0001',
            'target_qty'             => 100,
            'status'                 => BatchStatus::BAHAN,
            'requires_printing'      => false,
            'production_employee_id' => $this->produksiUser->id,
        ]);
    }

    public function test_produksi_user_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->produksiUser)->get(route('produksi.dashboard'));
        $response->assertOk();
        $response->assertSee('Selamat Datang, Staff Produksi');
        $response->assertSee('Produksi & QC');
    }

    public function test_produksi_user_can_access_batch_list_and_detail(): void
    {
        $response = $this->actingAs($this->produksiUser)->get(route('produksi.batches.index'));
        $response->assertOk();
        $response->assertSee('BAT-202608-0001');

        $detailResponse = $this->actingAs($this->produksiUser)->get(route('produksi.batches.show', $this->batch));
        $detailResponse->assertOk();
        $detailResponse->assertSee('BAT-202608-0001');

        $shipmentResponse = $this->actingAs($this->produksiUser)->get(route('produksi.shipments.index'));
        $shipmentResponse->assertOk();
    }

    public function test_admin_is_forbidden_from_produksi_routes(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'status' => UserStatus::ACTIVE]);

        $this->actingAs($admin)->get(route('produksi.dashboard'))->assertForbidden();
        $this->actingAs($admin)->get(route('produksi.batches.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('produksi.batches.show', $this->batch))->assertForbidden();
        $this->actingAs($admin)->get(route('produksi.shipments.index'))->assertForbidden();
    }

    public function test_gudang_is_forbidden_from_produksi_routes(): void
    {
        $gudang = User::factory()->create(['role' => UserRole::GUDANG, 'status' => UserStatus::ACTIVE]);

        $this->actingAs($gudang)->get(route('produksi.dashboard'))->assertForbidden();
        $this->actingAs($gudang)->get(route('produksi.batches.index'))->assertForbidden();
        $this->actingAs($gudang)->get(route('produksi.batches.show', $this->batch))->assertForbidden();
        $this->actingAs($gudang)->get(route('produksi.shipments.index'))->assertForbidden();
    }

    public function test_owner_is_forbidden_from_produksi_routes(): void
    {
        $owner = User::factory()->create(['role' => UserRole::OWNER, 'status' => UserStatus::ACTIVE]);

        $this->actingAs($owner)->get(route('produksi.dashboard'))->assertForbidden();
        $this->actingAs($owner)->get(route('produksi.batches.index'))->assertForbidden();
        $this->actingAs($owner)->get(route('produksi.shipments.index'))->assertForbidden();
    }

    public function test_customer_is_forbidden_from_produksi_routes(): void
    {
        $customer = User::factory()->create(['role' => UserRole::CUSTOMER, 'status' => UserStatus::ACTIVE]);

        $this->actingAs($customer)->get(route('produksi.dashboard'))->assertForbidden();
        $this->actingAs($customer)->get(route('produksi.batches.index'))->assertForbidden();
        $this->actingAs($customer)->get(route('produksi.shipments.index'))->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('produksi.dashboard'))->assertRedirect(route('login'));
        $this->get(route('produksi.batches.index'))->assertRedirect(route('login'));
        $this->get(route('produksi.shipments.index'))->assertRedirect(route('login'));
    }

    public function test_inactive_produksi_user_cannot_access_routes(): void
    {
        $inactive = User::factory()->create(['role' => UserRole::PRODUKSI, 'status' => UserStatus::INACTIVE]);

        $this->actingAs($inactive)->get(route('produksi.dashboard'))->assertRedirect(route('login'));
        $this->actingAs($inactive)->get(route('produksi.shipments.index'))->assertRedirect(route('login'));
    }
}
