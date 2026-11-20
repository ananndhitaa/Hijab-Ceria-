<?php

namespace Tests\Feature\Admin;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Master\Customer;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\System\User;
use App\Services\Sales\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private InvoiceService $invoiceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->invoiceService = app(InvoiceService::class);
    }

    private function createSampleOrder(OrderStatus $status = OrderStatus::DEAL, int $qty = 2, int $unitPrice = 75000): Order
    {
        $customer = Customer::create([
            'customer_name' => 'PT Hijab Cantik',
            'phone'         => '081234567892',
            'address'       => 'Jl. Buah Batu No. 12 Bandung',
        ]);
        $product = Product::create([
            'product_name' => 'Pashmina Silk',
            'active'       => true,
        ]);
        $variant = ProductVariant::create([
            'product_id'         => $product->id,
            'variant_name'       => 'Maroon',
            'sku'                => 'SKU-SILK-MRN-' . uniqid(),
            'base_selling_price' => $unitPrice,
        ]);

        $order = Order::create([
            'order_no'    => 'SO-202608-' . str_pad((string) (Order::count() + 1), 4, '0', STR_PAD_LEFT),
            'customer_id' => $customer->id,
            'order_date'  => '2026-08-27',
            'status'      => $status,
            'total_qty'   => $qty,
            'grand_total' => $qty * $unitPrice,
        ]);

        OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => $qty,
            'unit_selling_price' => $unitPrice,
            'subtotal'           => $qty * $unitPrice,
        ]);

        return $order;
    }

    public function test_admin_can_view_invoice_list_with_search_and_status_filter(): void
    {
        $order = $this->createSampleOrder(OrderStatus::DEAL);
        $invoice = $this->invoiceService->createForOrder($order);

        $response = $this->actingAs($this->admin)->get(route('admin.invoices.index'));
        $response->assertStatus(200);
        $response->assertSee($invoice->invoice_no);
        $response->assertSee('PT Hijab Cantik');

        // Test search filter
        $searchResponse = $this->actingAs($this->admin)->get(route('admin.invoices.index', ['search' => 'Hijab Cantik']));
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee($invoice->invoice_no);

        // Test status filter
        $statusResponse = $this->actingAs($this->admin)->get(route('admin.invoices.index', ['status' => InvoiceStatus::UNPAID->value]));
        $statusResponse->assertStatus(200);
        $statusResponse->assertSee($invoice->invoice_no);
    }

    public function test_admin_can_create_invoice_from_deal_order(): void
    {
        $order = $this->createSampleOrder(OrderStatus::DEAL);

        $response = $this->actingAs($this->admin)->post(route('admin.orders.invoice.store', $order));

        $response->assertRedirect();
        $this->assertDatabaseHas('invoices', [
            'order_id'     => $order->id,
            'status'       => InvoiceStatus::UNPAID->value,
            'total_amount' => '150000.00',
        ]);
    }

    public function test_cannot_create_invoice_for_komunikasi_order(): void
    {
        $order = $this->createSampleOrder(OrderStatus::KOMUNIKASI);

        $response = $this->actingAs($this->admin)->post(route('admin.orders.invoice.store', $order));

        $response->assertRedirect(route('admin.orders.show', $order));
        $response->assertSessionHas('error', 'Invoice hanya dapat dibuat untuk pesanan yang sudah mencapai status DEAL atau setelahnya.');
        $this->assertDatabaseMissing('invoices', ['order_id' => $order->id]);
    }

    public function test_sales_order_editing_blocked_after_invoice_created(): void
    {
        $order = $this->createSampleOrder(OrderStatus::DEAL);
        $this->invoiceService->createForOrder($order);

        // Attempting to visit edit page
        $editPageResponse = $this->actingAs($this->admin)->get(route('admin.orders.edit', $order));
        $editPageResponse->assertRedirect(route('admin.orders.show', $order));
        $editPageResponse->assertSessionHas('error');

        // Attempting to submit update
        $updateResponse = $this->actingAs($this->admin)->put(route('admin.orders.update', $order), [
            'customer_id' => $order->customer_id,
            'order_date'  => '2026-08-28',
            'items'       => [
                [
                    'variant_id'         => $order->items->first()->variant_id,
                    'qty_ordered'        => 5,
                    'unit_selling_price' => 75000,
                ],
            ],
        ]);

        $updateResponse->assertSessionHasErrors(['order' => 'Pesanan tidak dapat diubah karena invoice sudah diterbitkan.']);
    }

    public function test_invoice_detail_displays_snapshot_order_items_and_financials(): void
    {
        $order = $this->createSampleOrder(OrderStatus::DEAL);
        $invoice = $this->invoiceService->createForOrder($order);

        $response = $this->actingAs($this->admin)->get(route('admin.invoices.show', $invoice));

        $response->assertStatus(200);
        $response->assertSee($invoice->invoice_no);
        $response->assertSee('Pashmina Silk');
        $response->assertSee('150.000');
    }

    public function test_invoice_destroy_route_is_absent(): void
    {
        $order = $this->createSampleOrder(OrderStatus::DEAL);
        $invoice = $this->invoiceService->createForOrder($order);

        $response = $this->actingAs($this->admin)->delete("/admin/invoices/{$invoice->id}");
        $this->assertContains($response->status(), [404, 405]);
    }

    public function test_invoice_cancel_route_is_absent_in_phase_2c(): void
    {
        $order = $this->createSampleOrder(OrderStatus::DEAL);
        $invoice = $this->invoiceService->createForOrder($order);

        $response = $this->actingAs($this->admin)->post("/admin/invoices/{$invoice->id}/cancel");
        $this->assertContains($response->status(), [404, 405]);
    }

    public function test_non_admin_cannot_access_invoices(): void
    {
        $order = $this->createSampleOrder(OrderStatus::DEAL);
        $invoice = $this->invoiceService->createForOrder($order);

        $roles = [UserRole::GUDANG, UserRole::PRODUKSI, UserRole::CUSTOMER];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get(route('admin.invoices.index'))->assertStatus(403);
            $this->actingAs($user)->get(route('admin.invoices.show', $invoice))->assertStatus(403);
            $this->actingAs($user)->post(route('admin.orders.invoice.store', $order))->assertStatus(403);
        }
    }

    public function test_invoice_creation_cta_absent_in_komunikasi_status(): void
    {
        $order = $this->createSampleOrder(OrderStatus::KOMUNIKASI);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee('Invoice dapat dibuat setelah pesanan DEAL.');
        $response->assertDontSee('Buat Invoice');
    }

    public function test_invoice_creation_cta_present_in_deal_status(): void
    {
        $order = $this->createSampleOrder(OrderStatus::DEAL);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee('Buat Invoice');
        $response->assertDontSee('Invoice dapat dibuat setelah pesanan DEAL.');
    }

    public function test_invoice_creation_cta_present_in_bahan_status(): void
    {
        $order = $this->createSampleOrder(OrderStatus::BAHAN);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee('Buat Invoice');
    }

    public function test_invoice_creation_cta_present_in_later_operational_statuses(): void
    {
        $statuses = [
            OrderStatus::PRINTING,
            OrderStatus::PRODUKSI,
            OrderStatus::QC,
            OrderStatus::PACKING,
            OrderStatus::DIKIRIM,
            OrderStatus::SELESAI,
        ];

        foreach ($statuses as $status) {
            $order = $this->createSampleOrder($status);

            $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

            $response->assertStatus(200);
            $response->assertSee('Buat Invoice');
        }
    }

    public function test_invoice_creation_cta_absent_if_invoice_already_exists(): void
    {
        $order = $this->createSampleOrder(OrderStatus::DEAL);
        $invoice = $this->invoiceService->createForOrder($order);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        $response->assertDontSee('Buat Invoice');
        $response->assertSee($invoice->invoice_no);
        $response->assertSee('Kelola Pembayaran', false);
    }
}
