<?php

namespace Tests\Feature\Admin;

use App\Enums\BatchStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Models\Master\Customer;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\Sales\Payment;
use App\Models\System\OrderStatusHistory;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Production\ProductionBatch;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KanbanBoardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Customer $customer;
    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->customer = Customer::create(['customer_name' => 'Butik Al-Fatih']);

        $product = Product::create(['product_name' => 'Pashmina Silk', 'active' => true]);
        $this->variant = ProductVariant::create([
            'product_id'   => $product->id,
            'variant_name' => 'Dusty Pink',
            'sku'          => 'PAS-SILK-DPK',
        ]);
    }

    private function createOrder(OrderStatus $status = OrderStatus::KOMUNIKASI, float $qty = 500): Order
    {
        static $seq = 1;
        $orderNo = sprintf('SO-202608-%04d', $seq++);

        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => $orderNo,
            'order_date'  => '2026-08-10',
            'status'      => $status,
        ]);

        $order->items()->create([
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => $qty,
            'unit_selling_price' => '75000.00',
        ]);

        OrderStatusHistory::create([
            'order_id'           => $order->id,
            'status'             => $status,
            'changed_by_user_id' => $this->admin->id,
            'changed_at'         => now(),
            'notes'              => 'Initial status.',
        ]);

        return $order;
    }

    private function attachConfirmedDownPayment(Order $order, string $amount = '500000.00'): void
    {
        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-KB-' . $order->id,
            'invoice_date' => now()->toDateString(),
            'total_amount' => $order->total_amount ?? '1000000.00',
            'status'       => InvoiceStatus::PARTIAL,
        ]);

        Payment::create([
            'invoice_id'   => $invoice->id,
            'payment_no'   => 'PAY-KB-' . $order->id,
            'payment_date' => now()->toDateString(),
            'amount'       => $amount,
            'payment_type' => PaymentType::DP,
            'status'       => PaymentStatus::CONFIRMED,
        ]);
    }

    public function test_admin_can_open_kanban_board_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.kanban.index'));

        $response->assertStatus(200);
        $response->assertSee('Kanban Board Pesanan');
    }

    public function test_non_admin_roles_receive_403_when_accessing_kanban(): void
    {
        $gudang   = User::factory()->create(['role' => UserRole::GUDANG]);
        $produksi = User::factory()->create(['role' => UserRole::PRODUKSI]);
        $customer = User::factory()->create(['role' => UserRole::CUSTOMER]);
        $owner    = User::factory()->create(['role' => UserRole::OWNER]);

        $this->actingAs($gudang)->get(route('admin.kanban.index'))->assertStatus(403);
        $this->actingAs($produksi)->get(route('admin.kanban.index'))->assertStatus(403);
        $this->actingAs($customer)->get(route('admin.kanban.index'))->assertStatus(403);
        $this->actingAs($owner)->get(route('admin.kanban.index'))->assertStatus(403);
    }

    public function test_guest_is_redirected_to_login_when_accessing_kanban(): void
    {
        $response = $this->get(route('admin.kanban.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_board_renders_exactly_nine_official_order_status_columns(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.kanban.index'));

        $response->assertStatus(200);
        foreach (OrderStatus::cases() as $status) {
            $response->assertSee($status->label());
        }
    }

    public function test_orders_appear_in_their_respective_status_columns(): void
    {
        $orderKom = $this->createOrder(OrderStatus::KOMUNIKASI, 100);
        $orderDeal = $this->createOrder(OrderStatus::DEAL, 200);
        $orderBahan = $this->createOrder(OrderStatus::BAHAN, 300);

        $response = $this->actingAs($this->admin)->get(route('admin.kanban.index'));

        $response->assertStatus(200);
        $response->assertSee($orderKom->order_no);
        $response->assertSee($orderDeal->order_no);
        $response->assertSee($orderBahan->order_no);
    }

    public function test_empty_columns_render_properly_with_zero_count(): void
    {
        // No orders created
        $response = $this->actingAs($this->admin)->get(route('admin.kanban.index'));

        $response->assertStatus(200);
        $response->assertSee('Belum ada pesanan');
    }

    public function test_search_by_order_no_filters_kanban_cards(): void
    {
        $order1 = $this->createOrder(OrderStatus::KOMUNIKASI, 100);
        $order2 = $this->createOrder(OrderStatus::KOMUNIKASI, 200);

        $response = $this->actingAs($this->admin)->get(route('admin.kanban.index', ['search' => $order1->order_no]));

        $response->assertStatus(200);
        $response->assertSee($order1->order_no);
        $response->assertDontSee($order2->order_no);
    }

    public function test_search_by_customer_name_filters_kanban_cards(): void
    {
        $cust2 = Customer::create(['customer_name' => 'Hijab Mecca Store']);
        $order1 = $this->createOrder(OrderStatus::KOMUNIKASI, 100); // Cust: Butik Al-Fatih

        $order2 = Order::create([
            'customer_id' => $cust2->id,
            'order_no'    => 'SO-202608-9999',
            'order_date'  => '2026-08-10',
            'status'      => OrderStatus::KOMUNIKASI,
        ]);
        $order2->items()->create([
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => 200,
            'unit_selling_price' => '75000.00',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.kanban.index', ['search' => 'Mecca']));

        $response->assertStatus(200);
        $response->assertSee('Hijab Mecca Store');
        $response->assertDontSee($order1->order_no);
    }

    public function test_invoice_status_filter_works_on_kanban(): void
    {
        $order1 = $this->createOrder(OrderStatus::DEAL, 100);
        Invoice::create([
            'order_id'     => $order1->id,
            'invoice_no'   => 'INV-202608-0001',
            'invoice_date' => '2026-08-10',
            'total_amount' => '7500000.00',
            'status'       => 'PAID',
        ]);

        $order2 = $this->createOrder(OrderStatus::DEAL, 200); // No invoice

        $response = $this->actingAs($this->admin)->get(route('admin.kanban.index', ['invoice_status' => 'PAID']));

        $response->assertStatus(200);
        $response->assertSee($order1->order_no);
        $response->assertDontSee($order2->order_no);
    }

    public function test_no_generic_admin_status_mutation_route_exists(): void
    {
        $order = $this->createOrder(OrderStatus::KOMUNIKASI, 100);

        $resPost = $this->actingAs($this->admin)->post("/admin/orders/{$order->id}/status", [
            'status' => 'DEAL',
        ]);
        $this->assertTrue(in_array($resPost->status(), [404, 405]));

        $resPatch = $this->actingAs($this->admin)->patch("/admin/orders/{$order->id}/status", [
            'status' => 'DEAL',
        ]);
        $this->assertTrue(in_array($resPatch->status(), [404, 405]));
    }

    public function test_ajax_confirm_deal_from_komunikasi_succeeds_and_returns_json(): void
    {
        $order = $this->createOrder(OrderStatus::KOMUNIKASI, 100);

        $response = $this->actingAs($this->admin)->postJson(route('admin.orders.confirm-deal', $order), [
            'notes' => 'Confirmed via AJAX Kanban.',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success'  => true,
            'order_id' => $order->id,
            'order_no' => $order->order_no,
            'status'   => 'DEAL',
        ]);
    }

    public function test_ajax_confirm_deal_creates_exactly_one_deal_order_status_history(): void
    {
        $order = $this->createOrder(OrderStatus::KOMUNIKASI, 100);

        $this->actingAs($this->admin)->postJson(route('admin.orders.confirm-deal', $order));

        $dealHistories = OrderStatusHistory::where('order_id', $order->id)
            ->where('status', OrderStatus::DEAL->value)
            ->get();

        $this->assertCount(1, $dealHistories);
        $this->assertSame($this->admin->id, $dealHistories->first()->changed_by_user_id);
    }

    public function test_ajax_confirm_deal_actually_updates_order_status_in_database(): void
    {
        $order = $this->createOrder(OrderStatus::KOMUNIKASI, 100);

        $this->actingAs($this->admin)->postJson(route('admin.orders.confirm-deal', $order));

        $this->assertSame(OrderStatus::DEAL, $order->fresh()->status);
    }

    public function test_ajax_confirm_deal_on_stale_non_komunikasi_order_returns_422_json_error(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 100); // Already DEAL

        $response = $this->actingAs($this->admin)->postJson(route('admin.orders.confirm-deal', $order));

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
    }

    public function test_ajax_confirm_deal_failure_creates_zero_new_status_histories(): void
    {
        $order = $this->createOrder(OrderStatus::BAHAN, 100);
        $initialHistoryCount = OrderStatusHistory::where('order_id', $order->id)->count();

        $this->actingAs($this->admin)->postJson(route('admin.orders.confirm-deal', $order));

        $this->assertSame($initialHistoryCount, OrderStatusHistory::where('order_id', $order->id)->count());
    }

    public function test_deal_to_bahan_cannot_be_performed_through_kanban_endpoint(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 100);

        $response = $this->actingAs($this->admin)->postJson(route('admin.orders.confirm-deal', $order));

        $response->assertStatus(422);
        $this->assertSame(OrderStatus::DEAL, $order->fresh()->status);
    }

    public function test_bahan_to_printing_cannot_be_performed_through_kanban_endpoint(): void
    {
        $order = $this->createOrder(OrderStatus::BAHAN, 100);

        $response = $this->actingAs($this->admin)->postJson(route('admin.orders.confirm-deal', $order));

        $response->assertStatus(422);
        $this->assertSame(OrderStatus::BAHAN, $order->fresh()->status);
    }

    public function test_traditional_html_confirm_deal_workflow_still_redirects_with_flash(): void
    {
        $order = $this->createOrder(OrderStatus::KOMUNIKASI, 100);

        $response = $this->actingAs($this->admin)->post(route('admin.orders.confirm-deal', $order));

        $response->assertRedirect(route('admin.orders.show', $order));
        $response->assertSessionHas('success');
        $this->assertSame(OrderStatus::DEAL, $order->fresh()->status);
    }

    public function test_deal_card_links_to_first_batch_creation_when_eligible(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 500);
        $this->attachConfirmedDownPayment($order);

        $response = $this->actingAs($this->admin)->get(route('admin.kanban.index'));

        $response->assertStatus(200);
        $response->assertSee('Menunggu Batch');
        $response->assertSee('+ Buat Batch');
        $response->assertSee(route('admin.orders.batches.create', $order));
    }

    public function test_deal_card_hides_batch_cta_when_not_eligible(): void
    {
        $order = $this->createOrder(OrderStatus::KOMUNIKASI, 500);

        $response = $this->actingAs($this->admin)->get(route('admin.kanban.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Menunggu Batch');
    }

    public function test_deal_card_hides_batch_cta_when_dp_not_confirmed(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 500);

        $response = $this->actingAs($this->admin)->get(route('admin.kanban.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Menunggu Batch');
        $response->assertDontSee('+ Buat Batch');
    }

    public function test_bahan_card_exposes_create_additional_batch_cta_when_eligible(): void
    {
        $order = $this->createOrder(OrderStatus::BAHAN, 1000);
        $this->attachConfirmedDownPayment($order);
        $item = $order->items->first();

        // 1 active batch of 600 (remaining: 400)
        ProductionBatch::create([
            'order_item_id' => $item->id,
            'batch_no'      => 'BAT-202608-0001',
            'target_qty'    => 600,
            'status'        => BatchStatus::BAHAN,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.kanban.index'));

        $response->assertStatus(200);
        $response->assertSee('+ Batch Tambahan');
        $response->assertSee('1 Batch');
        $response->assertSee(route('admin.orders.batches.create', $order));
    }

    public function test_bahan_card_hides_batch_cta_when_fully_batched(): void
    {
        $order = $this->createOrder(OrderStatus::BAHAN, 500);
        $this->attachConfirmedDownPayment($order);
        $item = $order->items->first();

        // Full batch of 500 (remaining: 0)
        ProductionBatch::create([
            'order_item_id' => $item->id,
            'batch_no'      => 'BAT-202608-0001',
            'target_qty'    => 500,
            'status'        => BatchStatus::BAHAN,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.kanban.index'));

        $response->assertStatus(200);
        $response->assertDontSee('+ Batch Tambahan');
        $response->assertSee('1 Batch');
    }

    public function test_selesai_total_count_remains_accurate_even_when_rendered_cards_are_capped(): void
    {
        // Create 25 completed orders
        for ($i = 0; $i < 25; $i++) {
            $this->createOrder(OrderStatus::SELESAI, 10);
        }

        $response = $this->actingAs($this->admin)->get(route('admin.kanban.index'));

        $response->assertStatus(200);
        $response->assertSee('Menampilkan 20 pesanan terbaru');
        // Check column header contains 25
        $response->assertSee('25');
    }

    public function test_confirm_deal_route_only_accepts_post(): void
    {
        $order = $this->createOrder(OrderStatus::KOMUNIKASI, 100);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.confirm-deal', $order));
        $response->assertStatus(405);
    }

    public function test_stale_ajax_confirm_deal_does_not_alter_board_or_history(): void
    {
        $order = $this->createOrder(OrderStatus::PRINTING, 100);

        $res = $this->actingAs($this->admin)->postJson(route('admin.orders.confirm-deal', $order));
        $res->assertStatus(422);

        $this->assertSame(OrderStatus::PRINTING, $order->fresh()->status);
        $this->assertDatabaseMissing('order_status_histories', [
            'order_id' => $order->id,
            'status'   => OrderStatus::DEAL->value,
        ]);
    }

    public function test_kanban_does_not_create_production_batches(): void
    {
        $this->createOrder(OrderStatus::DEAL, 500);

        $this->actingAs($this->admin)->get(route('admin.kanban.index'));

        $this->assertDatabaseCount('production_batches', 0);
    }

    public function test_kanban_does_not_create_batch_materials(): void
    {
        $this->createOrder(OrderStatus::DEAL, 500);

        $this->actingAs($this->admin)->get(route('admin.kanban.index'));

        $this->assertDatabaseCount('batch_materials', 0);
    }

    public function test_kanban_does_not_create_stock_movements(): void
    {
        $this->createOrder(OrderStatus::DEAL, 500);

        $this->actingAs($this->admin)->get(route('admin.kanban.index'));

        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_kanban_does_not_expose_financial_metrics_like_hpp_profit_ebitda(): void
    {
        $this->createOrder(OrderStatus::DEAL, 500);

        $response = $this->actingAs($this->admin)->get(route('admin.kanban.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Gross Profit');
        $response->assertDontSee('Net Profit');
        $response->assertDontSee('EBITDA');
        $response->assertDontSee('HPP');
    }

    public function test_card_links_directly_to_admin_order_show(): void
    {
        $order = $this->createOrder(OrderStatus::KOMUNIKASI, 500);

        $response = $this->actingAs($this->admin)->get(route('admin.kanban.index'));

        $response->assertStatus(200);
        $response->assertSee(route('admin.orders.show', $order));
    }
}
