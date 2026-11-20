<?php

namespace Tests\Unit;

use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Master\Customer;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\Master\Product;
use App\Models\Production\ProductionBatch;
use App\Models\Master\ProductVariant;
use App\Models\System\User;
use App\Services\Sales\OrderStatusService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderStatusService $service;
    private User $admin;
    private Order $order;
    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OrderStatusService();
        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $customer = Customer::create(['customer_name' => 'Toko Mitra']);
        $product = Product::create(['product_name' => 'Hijab Bella', 'active' => true]);
        $this->variant = ProductVariant::create([
            'product_id'         => $product->id,
            'variant_name'       => 'Navy',
            'sku'                => 'SKU-BELLA-NVY',
            'base_selling_price' => 35000,
        ]);

        $this->order = Order::create([
            'customer_id' => $customer->id,
            'order_no'    => 'SO-202608-0001',
            'order_date'  => '2026-08-15',
            'status'      => OrderStatus::KOMUNIKASI,
        ]);

        $this->order->items()->create([
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => 50,
            'unit_selling_price' => 35000,
        ]);
    }

    public function test_admin_can_confirm_deal_from_komunikasi(): void
    {
        $updatedOrder = $this->service->confirmDeal($this->order, $this->admin, 'Deal dikonfirmasi via WhatsApp.');

        $this->assertEquals(OrderStatus::DEAL, $updatedOrder->status);
        $this->assertDatabaseHas('orders', [
            'id'     => $this->order->id,
            'status' => 'DEAL',
        ]);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id'           => $this->order->id,
            'status'             => 'DEAL',
            'changed_by_user_id' => $this->admin->id,
            'notes'              => 'Deal dikonfirmasi via WhatsApp.',
        ]);
    }

    public function test_confirm_deal_fails_if_status_is_not_komunikasi(): void
    {
        $this->order->update(['status' => OrderStatus::DEAL]);

        $this->expectException(DomainException::class);
        $this->service->confirmDeal($this->order, $this->admin);
    }

    public function test_can_edit_returns_true_for_komunikasi_and_deal_without_batches(): void
    {
        $this->order->update(['status' => OrderStatus::KOMUNIKASI]);
        $this->assertTrue($this->service->canEdit($this->order));

        $this->order->update(['status' => OrderStatus::DEAL]);
        $this->assertTrue($this->service->canEdit($this->order));
    }

    public function test_can_edit_returns_false_for_bahan_and_later_statuses(): void
    {
        $this->order->update(['status' => OrderStatus::BAHAN]);
        $this->assertFalse($this->service->canEdit($this->order));

        $this->order->update(['status' => OrderStatus::PRINTING]);
        $this->assertFalse($this->service->canEdit($this->order));

        $this->order->update(['status' => OrderStatus::PRODUKSI]);
        $this->assertFalse($this->service->canEdit($this->order));

        $this->order->update(['status' => OrderStatus::SELESAI]);
        $this->assertFalse($this->service->canEdit($this->order));
    }

    public function test_can_edit_returns_false_if_downstream_production_batch_exists(): void
    {
        $this->order->update(['status' => OrderStatus::DEAL]);
        $orderItem = $this->order->items->first();

        // Create downstream production batch attached to order item
        ProductionBatch::create([
            'order_item_id' => $orderItem->id,
            'batch_no'      => 'BAT-202608-0001',
            'target_qty'    => 50,
            'status'        => BatchStatus::BAHAN,
        ]);

        $this->assertFalse($this->service->canEdit($this->order));
    }
}
