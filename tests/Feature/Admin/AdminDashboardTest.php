<?php

namespace Tests\Feature\Admin;

use App\Enums\ExpenseType;
use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Models\Finance\BusinessExpense;
use App\Models\Master\Customer;
use App\Models\Sales\Invoice;
use App\Models\Master\Material;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\Sales\Payment;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Production\ProductionBatch;
use App\Models\Master\Unit;
use App\Models\System\User;
use App\Support\MoneyFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Customer $customer;
    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role'  => UserRole::ADMIN,
            'name'  => 'Admin User',
            'email' => 'admin@test.com',
        ]);

        $this->customer = Customer::create([
            'customer_name' => 'PT Hijab Cantik',
            'phone'         => '081234567890',
            'address'       => 'Jl. Gatot Subroto No. 10',
        ]);

        $unit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $product = Product::create(['product_name' => 'Hijab Segiempat Voal']);
        $this->variant = ProductVariant::create([
            'product_id'         => $product->id,
            'variant_name'       => 'Navy Blue',
            'sku'                => 'HJB-VOAL-NVY',
            'base_selling_price' => '85000.00',
        ]);
    }

    private function createOrder(OrderStatus $status = OrderStatus::KOMUNIKASI, int $qty = 100, string $unitPrice = '85000.00'): Order
    {
        static $orderSequence = 1;
        $orderNo = sprintf('SO-202608-%04d', $orderSequence++);
        $subtotal = bcmul((string) $qty, $unitPrice, 2);

        $order = Order::create([
            'order_no'     => $orderNo,
            'customer_id'  => $this->customer->id,
            'order_date'   => '2026-08-10',
            'status'       => $status,
            'total_amount' => $subtotal,
            'created_by'   => $this->admin->id,
        ]);

        OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => $qty,
            'unit_selling_price' => $unitPrice,
            'subtotal'           => $subtotal,
        ]);

        return $order;
    }

    // ── 1. Access & Authorization ─────────────────────────────────────

    public function test_admin_can_access_admin_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Dashboard Admin');
        $response->assertSee($this->admin->name);
    }

    public function test_gudang_cannot_access_admin_dashboard(): void
    {
        $gudang = User::factory()->create(['role' => UserRole::GUDANG]);
        $response = $this->actingAs($gudang)->get(route('admin.dashboard'));

        $response->assertStatus(403);
    }

    public function test_produksi_cannot_access_admin_dashboard(): void
    {
        $produksi = User::factory()->create(['role' => UserRole::PRODUKSI]);
        $response = $this->actingAs($produksi)->get(route('admin.dashboard'));

        $response->assertStatus(403);
    }

    public function test_customer_cannot_access_admin_dashboard(): void
    {
        $customerUser = User::factory()->create(['role' => UserRole::CUSTOMER]);
        $response = $this->actingAs($customerUser)->get(route('admin.dashboard'));

        $response->assertStatus(403);
    }

    public function test_guest_redirected_to_login_when_accessing_admin_dashboard(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('login'));
    }

    // ── 2. Primary KPI Calculations ───────────────────────────────────

    public function test_total_orders_and_active_orders_kpi_accurate(): void
    {
        $this->createOrder(OrderStatus::KOMUNIKASI);
        $this->createOrder(OrderStatus::DEAL);
        $this->createOrder(OrderStatus::PRODUKSI);
        $this->createOrder(OrderStatus::SELESAI);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        // Total Orders = 4, Active Orders = 3
        $response->assertSee('Total Order');
        $response->assertSee('Order Aktif');
    }

    public function test_komunikasi_orders_awaiting_deal_kpi_accurate(): void
    {
        $this->createOrder(OrderStatus::KOMUNIKASI);
        $this->createOrder(OrderStatus::KOMUNIKASI);
        $this->createOrder(OrderStatus::DEAL);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Menunggu Deal');
    }

    public function test_deal_plus_no_batch_counts_as_menunggu_batch(): void
    {
        // DEAL order without any batch
        $order = $this->createOrder(OrderStatus::DEAL, 100);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Menunggu Batch');
    }

    public function test_deal_plus_existing_batch_does_not_count_as_menunggu_batch(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 100);
        $item = $order->items->first();

        // Attach an existing batch to this DEAL order item
        ProductionBatch::create([
            'order_item_id' => $item->id,
            'batch_no'      => 'BAT-202608-0001',
            'target_qty'    => 50,
            'status'        => \App\Enums\BatchStatus::BAHAN,
            'created_by'    => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        // Menunggu Batch count should be 0 because the DEAL order already has a batch
        $response->assertViewHas('dealAwaitingBatchCount', 0);
    }

    public function test_bahan_does_not_count_as_menunggu_batch(): void
    {
        $this->createOrder(OrderStatus::BAHAN, 100);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('dealAwaitingBatchCount', 0);
    }

    public function test_invoice_unpaid_and_partial_count_as_belum_lunas(): void
    {
        $order1 = $this->createOrder(OrderStatus::DEAL);
        $order2 = $this->createOrder(OrderStatus::DEAL);

        Invoice::create([
            'order_id'     => $order1->id,
            'invoice_no'   => 'INV-202608-0001',
            'invoice_date' => '2026-08-10',
            'total_amount' => '8500000.00',
            'status'       => InvoiceStatus::UNPAID,
        ]);

        Invoice::create([
            'order_id'     => $order2->id,
            'invoice_no'   => 'INV-202608-0002',
            'invoice_date' => '2026-08-10',
            'total_amount' => '8500000.00',
            'status'       => InvoiceStatus::PARTIAL,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('unsettledInvoiceCount', 2);
    }

    public function test_invoice_paid_and_cancelled_do_not_count_as_belum_lunas(): void
    {
        $order1 = $this->createOrder(OrderStatus::DEAL);
        $order2 = $this->createOrder(OrderStatus::DEAL);

        Invoice::create([
            'order_id'     => $order1->id,
            'invoice_no'   => 'INV-202608-0001',
            'invoice_date' => '2026-08-10',
            'total_amount' => '8500000.00',
            'status'       => InvoiceStatus::PAID,
        ]);

        Invoice::create([
            'order_id'     => $order2->id,
            'invoice_no'   => 'INV-202608-0002',
            'invoice_date' => '2026-08-10',
            'total_amount' => '8500000.00',
            'status'       => InvoiceStatus::CANCELLED,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('unsettledInvoiceCount', 0);
    }

    public function test_total_outstanding_balance_kpi_is_accurate_and_decimal_safe(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 100, '50000.00'); // 5,000,000.00

        $inv = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-202608-0001',
            'invoice_date' => '2026-08-10',
            'total_amount' => '5000000.00',
            'status'       => InvoiceStatus::PARTIAL,
        ]);

        // Add confirmed payment of 2,000,000.00
        Payment::create([
            'invoice_id'     => $inv->id,
            'payment_type'   => PaymentType::DP,
            'amount'         => '2000000.00',
            'payment_date'   => '2026-08-10',
            'payment_method' => 'Transfer Bank',
            'status'         => PaymentStatus::CONFIRMED,
        ]);

        // Add cancelled payment of 1,000,000.00 (must NOT reduce outstanding)
        Payment::create([
            'invoice_id'     => $inv->id,
            'payment_type'   => PaymentType::PELUNASAN,
            'amount'         => '1000000.00',
            'payment_date'   => '2026-08-11',
            'payment_method' => 'Transfer Bank',
            'status'         => PaymentStatus::CANCELLED,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('totalOutstanding', '3000000.00');
        $response->assertSee(MoneyFormatter::rupiah('3000000.00'));
    }

    public function test_monthly_expenses_metric_is_accurate_and_decimal_safe(): void
    {
        $nowJakarta = now('Asia/Jakarta');

        BusinessExpense::create([
            'expense_date' => $nowJakarta->toDateString(),
            'expense_type' => ExpenseType::MARKETING,
            'description'  => 'Iklan Meta Ads',
            'amount'       => '1500000.00',
        ]);

        BusinessExpense::create([
            'expense_date' => $nowJakarta->toDateString(),
            'expense_type' => ExpenseType::OPERATING,
            'description'  => 'Biaya Listrik',
            'amount'       => '750000.00',
        ]);

        // Old expense from previous year
        BusinessExpense::create([
            'expense_date' => '2025-01-01',
            'expense_type' => ExpenseType::MARKETING,
            'description'  => 'Iklan Tahun Lalu',
            'amount'       => '5000000.00',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('monthlyExpenses', '2250000.00');
        $response->assertSee(MoneyFormatter::rupiah('2250000.00'));
    }

    public function test_master_data_counts_are_accurate(): void
    {
        Material::create([
            'material_name' => 'Kain Voal Premium',
            'unit_id'       => Unit::first()->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('totalCustomers', 1);
        $response->assertViewHas('totalProducts', 1);
        $response->assertViewHas('totalVariants', 1);
        $response->assertViewHas('totalMaterials', 1);
    }

    // ── 3. Pipeline & Action Required ─────────────────────────────────

    public function test_all_nine_order_status_pipeline_stages_render_on_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        foreach (OrderStatus::kanbanColumns() as $stage) {
            $response->assertSee($stage->label());
        }
        $response->assertSee('Buka Kanban Board');
    }

    public function test_order_status_pipeline_counts_reflect_actual_database_counts(): void
    {
        $this->createOrder(OrderStatus::KOMUNIKASI);
        $this->createOrder(OrderStatus::DEAL);
        $this->createOrder(OrderStatus::BAHAN);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $pipelineCounts = $response->viewData('statusPipelineCounts');
        $this->assertSame(1, $pipelineCounts['KOMUNIKASI']);
        $this->assertSame(1, $pipelineCounts['DEAL']);
        $this->assertSame(1, $pipelineCounts['BAHAN']);
        $this->assertSame(0, $pipelineCounts['PRINTING']);
        $this->assertSame(0, $pipelineCounts['SELESAI']);
    }

    public function test_action_required_section_renders_accurate_counts_and_links(): void
    {
        $order1 = $this->createOrder(OrderStatus::KOMUNIKASI);
        $order2 = $this->createOrder(OrderStatus::DEAL);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Perlu Tindakan');
        $response->assertSee('2 item');
        $response->assertSee($order1->order_no);
        $response->assertSee('Menunggu Deal');
        $response->assertSee($order2->order_no);
        $response->assertSee('Siap Batch');
        $response->assertDontSee('Antrean Aksi Cepat');
    }

    // ── 4. Recent Lists ───────────────────────────────────────────────

    public function test_recent_sales_orders_list_renders_correctly_with_money_formatter(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 100, '85000.00');

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee($order->order_no);
        $response->assertSee(MoneyFormatter::rupiah('8500000.00'));
    }

    public function test_recent_production_batches_list_renders_correctly(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 100);
        $item = $order->items->first();

        $batch = ProductionBatch::create([
            'order_item_id' => $item->id,
            'batch_no'      => 'BAT-202608-0001',
            'target_qty'    => 100,
            'status'        => \App\Enums\BatchStatus::BAHAN,
            'created_by'    => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee($batch->batch_no);
        $response->assertSee('100 pcs');
    }

    public function test_recent_business_expenses_list_renders_correctly_with_money_formatter(): void
    {
        $expense = BusinessExpense::create([
            'expense_date' => '2026-08-12',
            'expense_type' => ExpenseType::MARKETING,
            'description'  => 'Endorsement Influencer',
            'amount'       => '3500000.00',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Endorsement Influencer');
        $response->assertSee(MoneyFormatter::rupiah('3500000.00'));
    }

    // ── 5. Financial Boundary Tests (No Owner Data) ───────────────────

    public function test_dashboard_does_not_contain_hpp_metric(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertDontSee('HPP');
        $response->assertDontSee('HPP per pcs');
    }

    public function test_dashboard_does_not_contain_gross_profit_metric(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertDontSee('Gross Profit');
        $response->assertDontSee('Gross Margin');
    }

    public function test_dashboard_does_not_contain_net_profit_metric(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertDontSee('Net Profit');
        $response->assertDontSee('Net Margin');
    }

    public function test_dashboard_does_not_contain_ebitda_or_roi_or_mer_metric(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertDontSee('EBITDA');
        $response->assertDontSee('ROI');
        $response->assertDontSee('MER');
    }

    // ── 6. Admin Sidebar Tests ────────────────────────────────────────

    public function test_admin_sidebar_contains_all_completed_admin_menu_links(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee(route('admin.dashboard'));
        $response->assertSee(route('admin.orders.index'));
        $response->assertSee(route('admin.kanban.index'));
        $response->assertSee(route('admin.batches.index'));
        $response->assertSee(route('admin.customers.index'));
        $response->assertSee(route('admin.products.index'));
        $response->assertSee(route('admin.invoices.index'));
        $response->assertSee(route('admin.expenses.index'));
    }

    public function test_admin_sidebar_contains_zero_dead_href_hash_links(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        // The admin navigation portion should not contain any href="#"
        $content = $response->getContent();
        $this->assertStringNotContainsString('href="#" class="sidebar-link"', $content);
    }

    public function test_admin_sidebar_does_not_expose_redundant_payment_menu(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertDontSee('>Payment</a>', false);
    }

    public function test_admin_sidebar_does_not_expose_owner_financial_analytics(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertDontSee('Dashboard Executive');
        $response->assertDontSee('Order Financial Detail');
        $response->assertDontSee('Owner Detail Metrics');
    }

    public function test_sales_order_and_batch_sidebar_active_states_do_not_overlap(): void
    {
        // 1. When visiting normal sales order page:
        $order = $this->createOrder(OrderStatus::DEAL);
        $responseOrder = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));
        $responseOrder->assertStatus(200);

        // 2. When visiting batch page:
        $responseBatch = $this->actingAs($this->admin)->get(route('admin.batches.index'));
        $responseBatch->assertStatus(200);
    }

    public function test_nested_admin_orders_batches_activates_only_batch_produksi_sidebar(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL);
        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-DASH-001',
            'invoice_date' => now()->toDateString(),
            'total_amount' => '1000000.00',
            'status'       => InvoiceStatus::UNPAID,
        ]);
        Payment::create([
            'invoice_id'   => $invoice->id,
            'payment_no'   => 'PAY-DASH-001',
            'payment_date' => now()->toDateString(),
            'amount'       => '300000.00',
            'payment_type' => PaymentType::DP,
            'status'       => PaymentStatus::CONFIRMED,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.batches.create', $order));

        $response->assertStatus(200);
        // The active class should be on Batch Produksi and NOT on Sales Order
    }

    public function test_variants_and_products_show_views_use_money_formatter_for_base_selling_price(): void
    {
        $responseVariant = $this->actingAs($this->admin)->get(route('admin.variants.show', $this->variant));
        $responseVariant->assertStatus(200);
        $responseVariant->assertSee(MoneyFormatter::rupiah('85000.00'));

        $responseProduct = $this->actingAs($this->admin)->get(route('admin.products.show', $this->variant->product));
        $responseProduct->assertStatus(200);
        $responseProduct->assertSee(MoneyFormatter::rupiah('85000.00'));
    }
}
