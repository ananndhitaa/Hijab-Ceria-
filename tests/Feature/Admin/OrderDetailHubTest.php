<?php

namespace Tests\Feature\Admin;

use App\Enums\BatchStatus;
use App\Enums\ExpenseType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Models\Finance\BusinessExpense;
use App\Models\Master\Customer;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\System\OrderStatusHistory;
use App\Models\System\OrderTrackingToken;
use App\Models\Sales\Payment;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Production\ProductionBatch;
use App\Models\System\User;
use App\Support\MoneyFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderDetailHubTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Customer $customer;
    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->customer = Customer::create([
            'customer_name' => 'Butik Al-Fatih',
            'phone'         => '081234567890',
            'address'       => 'Jl. Thamrin No. 10, Jakarta',
        ]);

        $product = Product::create(['product_name' => 'Pashmina Silk Premium', 'active' => true]);
        $this->variant = ProductVariant::create([
            'product_id'   => $product->id,
            'variant_name' => 'Rose Gold',
            'sku'          => 'PAS-SILK-RSG',
        ]);
    }

    private function createOrder(OrderStatus $status = OrderStatus::KOMUNIKASI, float $qty = 1000, string $price = '75000.00'): Order
    {
        static $seq = 1;
        $orderNo = sprintf('SO-202608-%04d', $seq++);

        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => $orderNo,
            'order_date'  => '2026-08-10',
            'status'      => $status,
            'notes'       => 'Packaging kardus tebal.',
        ]);

        $order->items()->create([
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => $qty,
            'unit_selling_price' => $price,
        ]);

        OrderStatusHistory::create([
            'order_id'           => $order->id,
            'status'             => $status,
            'changed_by_user_id' => $this->admin->id,
            'changed_at'         => now(),
            'notes'              => 'Initial order creation.',
        ]);

        return $order;
    }

    public function test_admin_can_open_integrated_order_detail_page(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee($order->order_no);
    }

    public function test_non_admin_roles_receive_403_when_accessing_order_detail(): void
    {
        $order = $this->createOrder();
        $gudang = User::factory()->create(['role' => UserRole::GUDANG]);
        $customer = User::factory()->create(['role' => UserRole::CUSTOMER]);

        $this->actingAs($gudang)->get(route('admin.orders.show', $order))->assertStatus(403);
        $this->actingAs($customer)->get(route('admin.orders.show', $order))->assertStatus(403);
    }

    public function test_guest_redirected_to_login_when_accessing_order_detail(): void
    {
        $order = $this->createOrder();
        $this->get(route('admin.orders.show', $order))->assertRedirect(route('login'));
    }

    public function test_customer_information_rendered_completely_without_email_requirement(): void
    {
        $order = $this->createOrder();

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee('Butik Al-Fatih');
        $response->assertSee('081234567890');
        $response->assertSee('Jl. Thamrin No. 10, Jakarta');
        $response->assertSee('Packaging kardus tebal.');
    }

    public function test_order_items_and_price_snapshots_rendered_accurately(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 1000, '75000.00');

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee('Pashmina Silk Premium');
        $response->assertSee('Rose Gold');
        $response->assertSee('PAS-SILK-RSG');
        $response->assertSee('1.000 pcs');
        $response->assertSee(MoneyFormatter::rupiah('75000.00'));
        $response->assertSee(MoneyFormatter::rupiah('75000000.00'));
    }

    public function test_all_monetary_displays_use_money_formatter_rupiah(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 500, '125500.00');

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee(MoneyFormatter::rupiah('125500.00'));
        $response->assertSee(MoneyFormatter::rupiah('62750000.00'));
    }

    public function test_production_batch_section_renders_all_related_batches(): void
    {
        $order = $this->createOrder(OrderStatus::BAHAN, 1000);
        $item = $order->items->first();

        $batch1 = ProductionBatch::create([
            'order_item_id' => $item->id,
            'batch_no'      => 'BAT-202608-0001',
            'target_qty'    => 600,
            'status'        => BatchStatus::BAHAN,
        ]);

        $batch2 = ProductionBatch::create([
            'order_item_id' => $item->id,
            'batch_no'      => 'BAT-202608-0002',
            'target_qty'    => 400,
            'status'        => BatchStatus::BAHAN,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee('BAT-202608-0001');
        $response->assertSee('BAT-202608-0002');
        $response->assertSee('600 pcs');
        $response->assertSee('400 pcs');
    }

    public function test_cancelled_batch_does_not_count_toward_committed_capacity_display(): void
    {
        $order = $this->createOrder(OrderStatus::BAHAN, 1000);
        $item = $order->items->first();

        // 1 Active batch of 600
        ProductionBatch::create([
            'order_item_id' => $item->id,
            'batch_no'      => 'BAT-202608-0001',
            'target_qty'    => 600,
            'status'        => BatchStatus::BAHAN,
        ]);

        // 1 Cancelled batch of 300
        ProductionBatch::create([
            'order_item_id' => $item->id,
            'batch_no'      => 'BAT-202608-0002',
            'target_qty'    => 300,
            'status'        => BatchStatus::CANCELLED,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        // Sisa must be 400 pcs (1000 - 600)
        $response->assertSee('Sisa 400 pcs');
        $response->assertSee('600 pcs');
    }

    public function test_remaining_unbatched_quantity_rendered_accurately(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 800);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee('Sisa 800 pcs');
    }

    public function test_batches_from_other_orders_do_not_appear(): void
    {
        $order1 = $this->createOrder(OrderStatus::BAHAN, 500);
        $order2 = $this->createOrder(OrderStatus::BAHAN, 500);

        ProductionBatch::create([
            'order_item_id' => $order2->items->first()->id,
            'batch_no'      => 'BAT-202608-9999',
            'target_qty'    => 500,
            'status'        => BatchStatus::BAHAN,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order1));

        $response->assertStatus(200);
        $response->assertDontSee('BAT-202608-9999');
    }

    public function test_attributed_business_expense_appears_in_order_detail(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL);

        $expense = BusinessExpense::create([
            'order_id'      => $order->id,
            'expense_date'  => '2026-08-11',
            'expense_type'  => ExpenseType::MARKETING,
            'campaign_name' => 'TikTok Flash Sale',
            'description'   => 'Endorsement Selebgram',
            'amount'        => '1500000.00',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee('Biaya Terkait');
        $response->assertSee(MoneyFormatter::rupiah('1500000.00'));
        $response->assertSee('1 pengeluaran terkait');
        $response->assertSee('Lihat Detail');
        $response->assertSee('Catat Pengeluaran');
        // Does NOT render full multi-column Expense table headers
        $response->assertDontSee('<th class="px-3 py-2 text-[10px] font-bold text-[#786B72] uppercase tracking-wider whitespace-nowrap">Tanggal</th>', false);
    }

    public function test_general_business_expense_does_not_appear_in_order_detail(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL);

        BusinessExpense::create([
            'order_id'      => null, // General expense
            'expense_date'  => '2026-08-11',
            'expense_type'  => ExpenseType::OPERATING,
            'description'   => 'Biaya Listrik Kantor Umum',
            'amount'        => '2500000.00',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        $response->assertDontSee('Biaya Terkait');
        $response->assertDontSee('Biaya Listrik Kantor Umum');
    }

    public function test_expense_from_another_order_does_not_appear(): void
    {
        $order1 = $this->createOrder(OrderStatus::DEAL);
        $order2 = $this->createOrder(OrderStatus::DEAL);

        BusinessExpense::create([
            'order_id'      => $order2->id,
            'expense_date'  => '2026-08-11',
            'expense_type'  => ExpenseType::MARKETING,
            'description'   => 'Iklan Spesifik Order 2',
            'amount'        => '800000.00',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order1));

        $response->assertStatus(200);
        $response->assertDontSee('Biaya Terkait');
        $response->assertDontSee('Iklan Spesifik Order 2');
    }

    public function test_catat_pengeluaran_cta_contains_order_id_query_parameter(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL);

        BusinessExpense::create([
            'order_id'      => $order->id,
            'expense_date'  => '2026-08-11',
            'expense_type'  => ExpenseType::MARKETING,
            'description'   => 'Iklan Order',
            'amount'        => '100000.00',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee(route('admin.expenses.create', ['order_id' => $order->id]));
    }

    public function test_expense_create_page_preselects_valid_order_from_query_param(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL);

        $response = $this->actingAs($this->admin)->get(route('admin.expenses.create', ['order_id' => $order->id]));

        $response->assertStatus(200);
        $response->assertSee('value="' . $order->id . '" selected', false);
    }

    public function test_expense_create_page_handles_invalid_or_nonexistent_order_id_gracefully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.expenses.create', ['order_id' => 99999]));

        $response->assertStatus(200);
        $response->assertSee('Catat Pengeluaran Bisnis');
    }

    public function test_invoice_summary_renders_accurately_when_invoice_exists(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 1000, '75000.00');

        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-202608-0001',
            'invoice_date' => '2026-08-10',
            'total_amount' => '75000000.00',
            'status'       => 'PARTIAL',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee('INV-202608-0001');
        $response->assertSee(MoneyFormatter::rupiah('75000000.00'));
    }

    public function test_confirmed_payments_contribute_to_paid_summary_display(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 1000, '75000.00');

        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-202608-0001',
            'invoice_date' => '2026-08-10',
            'total_amount' => '75000000.00',
            'status'       => 'PARTIAL',
        ]);

        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_type'   => 'DP',
            'amount'         => '35000000.00',
            'payment_date'   => '2026-08-10',
            'payment_method' => 'Transfer BCA',
            'status'         => 'CONFIRMED',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee(MoneyFormatter::rupiah('35000000.00'));
        $response->assertSee(MoneyFormatter::rupiah('40000000.00')); // Balance
        $response->assertSee('Transfer Bank');
    }

    public function test_cancelled_payments_do_not_contribute_to_paid_summary(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 1000, '75000.00');

        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-202608-0001',
            'invoice_date' => '2026-08-10',
            'total_amount' => '75000000.00',
            'status'       => 'UNPAID',
        ]);

        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_type'   => 'DP',
            'amount'         => '35000000.00',
            'payment_date'   => '2026-08-10',
            'payment_method' => 'Transfer BCA',
            'status'         => 'CANCELLED',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee(MoneyFormatter::rupiah('0.00'));
        $response->assertSee(MoneyFormatter::rupiah('75000000.00'));
    }

    public function test_payment_list_does_not_display_fabricated_payment_numbers(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 100, '10000.00');

        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-202608-0001',
            'invoice_date' => '2026-08-10',
            'total_amount' => '1000000.00',
            'status'       => 'UNPAID',
        ]);

        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_type'   => 'PELUNASAN',
            'amount'         => '1000000.00',
            'payment_date'   => '2026-08-10',
            'payment_method' => 'Transfer Bank',
            'status'         => 'CONFIRMED',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        $response->assertDontSee('PAY-0001');
        $response->assertDontSee('No. Referensi');
        $response->assertSee('Transfer Bank');
        $response->assertSee(MoneyFormatter::rupiah('1000000.00'));
    }

    public function test_outstanding_balance_matches_money_formatter_display(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 100, '15000.00');

        Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-202608-0001',
            'invoice_date' => '2026-08-10',
            'total_amount' => '1500000.00',
            'status'       => 'UNPAID',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee(MoneyFormatter::rupiah('1500000.00'));
    }

    public function test_empty_invoice_state_renders_gracefully(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee('Tagihan');
        $response->assertSee('Buat Invoice');
    }

    public function test_existing_invoice_exposes_correct_invoice_view_action(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 100, '15000.00');

        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-202608-0001',
            'invoice_date' => '2026-08-10',
            'total_amount' => '1500000.00',
            'status'       => 'UNPAID',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee(route('admin.invoices.show', $invoice));
        $response->assertSee('Kelola Pembayaran', false);
    }

    public function test_empty_expense_state_hides_entire_expense_block(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        $response->assertDontSee('Biaya Terkait');
        $response->assertDontSee('Pengeluaran Terkait');
        $response->assertDontSee('pengeluaran terkait');
        $response->assertDontSee('Belum ada biaya operasional');
    }

    public function test_empty_production_batch_state_renders_gracefully(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee('Belum ada batch produksi yang dibuat untuk pesanan ini.');
    }

    public function test_tracking_section_does_not_leak_token_hash_or_secret(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL);

        OrderTrackingToken::create([
            'order_id'   => $order->id,
            'token_hash' => hash('sha256', 'supersecretrawtoken123456789'),
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee('Pelacakan Pesanan');
        $response->assertDontSee('supersecretrawtoken123456789');
        $response->assertDontSee(hash('sha256', 'supersecretrawtoken123456789'));
    }

    public function test_customer_master_update_immediately_reflected_on_sales_order_detail_customer_profile_while_transaction_price_remains_unchanged(): void
    {
        // 1. Create Customer A
        $customer = Customer::create([
            'customer_name' => 'Customer Awal Asli',
            'phone'         => '081111111111',
            'address'       => 'Jl. Asli No. 1, Bandung',
        ]);

        // 2. Create Sales Order for Customer A
        $order = Order::create([
            'customer_id' => $customer->id,
            'order_no'    => 'SO-202608-TEST',
            'order_date'  => '2026-08-15',
            'status'      => OrderStatus::DEAL,
            'notes'       => 'Catatan awal.',
        ]);

        $order->items()->create([
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => 50,
            'unit_selling_price' => '85000.00',
        ]);

        // Verify initial view shows Customer A
        $initResponse = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));
        $initResponse->assertSee('Customer Awal Asli');
        $initResponse->assertSee('081111111111');
        $initResponse->assertSee('Jl. Asli No. 1, Bandung');
        $initResponse->assertSee(MoneyFormatter::rupiah('85000.00'));

        // 3. Update Customer master to B
        $customer->update([
            'customer_name' => 'Customer Baru Diperbarui',
            'phone'         => '089999999999',
            'address'       => 'Jl. Baru No. 99, Surabaya',
        ]);

        // 4. Request Admin Sales Order Detail again
        $updatedResponse = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        // 5. Expected: Displays updated B values and no stale A values
        $updatedResponse->assertSee('Customer Baru Diperbarui');
        $updatedResponse->assertSee('089999999999');
        $updatedResponse->assertSee('Jl. Baru No. 99, Surabaya');
        $updatedResponse->assertDontSee('Customer Awal Asli');
        $updatedResponse->assertDontSee('081111111111');
        $updatedResponse->assertDontSee('Jl. Asli No. 1, Bandung');

        // And transactional OrderItem selling price remains unchanged snapshot
        $updatedResponse->assertSee(MoneyFormatter::rupiah('85000.00'));
        $this->assertEquals('85000.00', $order->fresh()->items->first()->unit_selling_price);
    }

    public function test_one_time_raw_tracking_url_shown_only_when_in_session(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL);
        $fakeTrackingUrl = 'http://localhost/track/raw-secret-token-sample';

        $response = $this->actingAs($this->admin)
            ->withSession(['raw_tracking_url' => $fakeTrackingUrl])
            ->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee($fakeTrackingUrl);
    }

    public function test_status_history_renders_chronological_timeline(): void
    {
        $order = $this->createOrder(OrderStatus::KOMUNIKASI);

        OrderStatusHistory::create([
            'order_id'           => $order->id,
            'status'             => OrderStatus::DEAL,
            'changed_by_user_id' => $this->admin->id,
            'changed_at'         => now(),
            'notes'              => 'Deal confirmed by Admin.',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee('Deal confirmed by Admin.');
        $response->assertSee($this->admin->name);
    }

    public function test_kanban_shortcut_links_to_kanban_with_order_search(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee(route('admin.kanban.index', ['search' => $order->order_no]));
    }

    public function test_contextual_action_buttons_rendered_according_to_domain_eligibility(): void
    {
        // 1. KOMUNIKASI: Edit Order + Konfirmasi Deal; NO Create Batch
        $orderKomunikasi = $this->createOrder(OrderStatus::KOMUNIKASI);
        $resKom = $this->actingAs($this->admin)->get(route('admin.orders.show', $orderKomunikasi));
        $resKom->assertStatus(200);
        $resKom->assertSee('Konfirmasi Deal');
        $resKom->assertSee('Edit Order');
        $resKom->assertDontSee('Buat Batch Produksi');

        // 2. DEAL: Create Batch allowed, Edit Order allowed (no batches/invoices); NO Konfirmasi Deal
        $orderDeal = $this->createOrder(OrderStatus::DEAL);
        $resDeal = $this->actingAs($this->admin)->get(route('admin.orders.show', $orderDeal));
        $resDeal->assertStatus(200);
        $resDeal->assertSee('Buat Batch Produksi');
        $resDeal->assertSee('Edit Order');
        $resDeal->assertDontSee('Konfirmasi Deal');

        // 3. BAHAN with remaining qty: Create Batch allowed, NO Edit Order, NO Konfirmasi Deal
        $orderBahanPartial = $this->createOrder(OrderStatus::BAHAN, 100);
        ProductionBatch::create([
            'order_item_id' => $orderBahanPartial->items->first()->id,
            'batch_no'      => 'BAT-202608-0088',
            'target_qty'    => 40,
            'status'        => BatchStatus::BAHAN,
        ]);
        $resBahanPartial = $this->actingAs($this->admin)->get(route('admin.orders.show', $orderBahanPartial));
        $resBahanPartial->assertStatus(200);
        $resBahanPartial->assertSee('Buat Batch Produksi');
        $resBahanPartial->assertDontSee('Edit Order');
        $resBahanPartial->assertDontSee('Konfirmasi Deal');

        // 4. BAHAN fully batched: NO Create Batch, NO Edit Order, NO Konfirmasi Deal
        $orderBahanFull = $this->createOrder(OrderStatus::BAHAN, 100);
        ProductionBatch::create([
            'order_item_id' => $orderBahanFull->items->first()->id,
            'batch_no'      => 'BAT-202608-0089',
            'target_qty'    => 100,
            'status'        => BatchStatus::BAHAN,
        ]);
        $resBahanFull = $this->actingAs($this->admin)->get(route('admin.orders.show', $orderBahanFull));
        $resBahanFull->assertStatus(200);
        $resBahanFull->assertDontSee('Buat Batch Produksi');
        $resBahanFull->assertDontSee('Edit Order');
        $resBahanFull->assertDontSee('Konfirmasi Deal');
    }

    public function test_split_batch_pills_provide_direct_navigation_to_batch_detail(): void
    {
        $order = $this->createOrder(OrderStatus::BAHAN, 100);
        $item = $order->items->first();

        $batch1 = ProductionBatch::create([
            'order_item_id' => $item->id,
            'batch_no'      => 'BAT-202608-0010',
            'target_qty'    => 60,
            'status'        => BatchStatus::BAHAN,
        ]);

        $batch2 = ProductionBatch::create([
            'order_item_id' => $item->id,
            'batch_no'      => 'BAT-202608-0011',
            'target_qty'    => 40,
            'status'        => BatchStatus::BAHAN,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee('BAT-202608-0010');
        $response->assertSee('BAT-202608-0011');
        $response->assertSee(route('admin.batches.show', $batch1));
        $response->assertSee(route('admin.batches.show', $batch2));
    }

    public function test_expense_overview_provides_direct_navigation_to_expense_detail(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL);

        $expense = BusinessExpense::create([
            'order_id'      => $order->id,
            'expense_date'  => '2026-08-11',
            'expense_type'  => ExpenseType::MARKETING,
            'campaign_name' => 'Meta Ads Agustus',
            'description'   => 'Iklan Instagram Story',
            'amount'        => '500000.00',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee(route('admin.expenses.show', $expense));
        $response->assertSee('Lihat Detail');
        $response->assertSee('Catat Pengeluaran');
        $response->assertSee(MoneyFormatter::rupiah('500000.00'));
        $response->assertSee('1 pengeluaran terkait');
    }

    public function test_admin_order_detail_does_not_leak_owner_executive_metrics_such_as_net_profit_or_ebitda(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        $response->assertDontSee('Net Profit');
        $response->assertDontSee('EBITDA');
    }

    public function test_batch_section_does_not_contain_duplicate_kelola_pembayaran_action_when_awaiting_dp(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 100, '15000.00');

        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-202608-0002',
            'invoice_date' => '2026-08-10',
            'total_amount' => '1500000.00',
            'status'       => 'UNPAID',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertStatus(200);

        // 1. Batch section: No "Menunggu DP" or "Menunggu Pembayaran DP" visual / text, no yellow callout
        $response->assertDontSee('Menunggu Pembayaran DP');
        $response->assertDontSee('Menunggu DP');
        $response->assertDontSee('Menunggu pembayaran DP');
        $response->assertDontSee('bg-amber-50/70');

        // 2. Operational labels are present in batch card
        $response->assertSee('Dipesan');
        $response->assertSee('Terjadwal');
        $response->assertSee('Sisa');
        $response->assertSee('Batch');
        $response->assertSee('Belum Dijadwalkan');

        // 3. Kelola Pembayaran appears only once on the whole page (in invoice section, NOT in batch section)
        $response->assertSee('Kelola Pembayaran', false);
        $this->assertSame(1, substr_count($response->getContent(), 'Kelola Pembayaran'));

        // 4. Buat Batch Produksi button is disabled
        $response->assertSee('cursor-not-allowed', false);

        // 5. Direct backend create is blocked without confirmed payment
        $item = $order->items->first();
        $postRes = $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id'    => $item->id,
            'target_qty'       => 50,
        ]);
        $postRes->assertSessionHas('error', 'Batch produksi belum dapat dibuat karena pembayaran DP belum diterima.');
        $this->assertDatabaseCount('production_batches', 0);

        // 6. Invoice / Tagihan section retains metrics and action
        $response->assertSee('Total Tagihan');
        $response->assertSee('Terbayar');
        $response->assertSee('INV-202608-0002');

        // 7. After confirmed payment > 0
        Payment::create([
            'invoice_id'   => $invoice->id,
            'payment_no'   => 'PAY-202608-0002',
            'payment_date' => '2026-08-10',
            'amount'       => '500000.00',
            'payment_type' => PaymentType::DP,
            'status'       => PaymentStatus::CONFIRMED,
        ]);

        $responseAfterPay = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));
        $responseAfterPay->assertStatus(200);

        // Menunggu Pembayaran DP / Menunggu DP remain absent
        $responseAfterPay->assertDontSee('Menunggu Pembayaran DP');
        $responseAfterPay->assertDontSee('Menunggu DP');
        $responseAfterPay->assertDontSee('bg-amber-50/70');

        // Create Batch link is active / available
        $responseAfterPay->assertSee(route('admin.orders.batches.create', $order));

        // Kelola Pembayaran still in Invoice section (single action)
        $responseAfterPay->assertSee('Kelola Pembayaran', false);
        $this->assertSame(1, substr_count($responseAfterPay->getContent(), 'Kelola Pembayaran'));
    }
}
