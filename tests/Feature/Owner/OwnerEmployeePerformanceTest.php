<?php

namespace Tests\Feature\Owner;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Master\Customer;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\Sales\Payment;
use App\Models\System\Outlet;
use App\Models\System\User;
use App\Services\Owner\AdminPerformanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerEmployeePerformanceTest extends TestCase
{
    use RefreshDatabase;

    private Outlet $outlet;
    private User $owner;
    private User $adminA;
    private User $adminB;
    private User $adminC;
    private User $gudang;
    private User $produksi;
    private User $customerUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outlet = Outlet::create([
            'outlet_name' => 'Nayara Hijab Kudus',
            'outlet_code' => 'OUT-KUDUS',
            'status'      => 'ACTIVE',
        ]);

        $this->owner = User::factory()->create([
            'outlet_id' => $this->outlet->id,
            'name'      => 'Owner Utama',
            'role'      => UserRole::OWNER,
            'status'    => UserStatus::ACTIVE,
        ]);

        $this->adminA = User::factory()->create([
            'outlet_id' => $this->outlet->id,
            'name'      => 'Admin Aisyah',
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::ACTIVE,
        ]);

        $this->adminB = User::factory()->create([
            'outlet_id' => $this->outlet->id,
            'name'      => 'Admin Budi',
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::ACTIVE,
        ]);

        $this->adminC = User::factory()->create([
            'outlet_id' => $this->outlet->id,
            'name'      => 'Admin Cantika',
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::ACTIVE,
        ]);

        $this->gudang = User::factory()->create([
            'outlet_id' => $this->outlet->id,
            'name'      => 'Staf Gudang',
            'role'      => UserRole::GUDANG,
            'status'    => UserStatus::ACTIVE,
        ]);

        $this->produksi = User::factory()->create([
            'outlet_id' => $this->outlet->id,
            'name'      => 'Staf Produksi',
            'role'      => UserRole::PRODUKSI,
            'status'    => UserStatus::ACTIVE,
        ]);

        $this->customerUser = User::factory()->create([
            'outlet_id' => $this->outlet->id,
            'name'      => 'Customer User',
            'role'      => UserRole::CUSTOMER,
            'status'    => UserStatus::ACTIVE,
        ]);
    }

    // ── 1. Role Authorization ───────────────────────────────────────────

    public function test_owner_can_access_employee_performance_dashboard(): void
    {
        $response = $this->actingAs($this->owner)->get(route('owner.employee-performance.index'));

        $response->assertStatus(200);
        $response->assertSee('Performa Karyawan');
        $response->assertSee('Performa Admin');
        $response->assertSee('Tabel Performa Admin');
    }

    public function test_owner_can_access_admin_detail_breakdown(): void
    {
        $response = $this->actingAs($this->owner)->get(route('owner.employee-performance.admin-detail', $this->adminA));

        $response->assertStatus(200);
        $response->assertSee('Detail Performa Admin');
        $response->assertSee('Admin Aisyah');
        $response->assertSee($this->adminA->email);
        $response->assertSee('Daftar Customer');
    }

    public function test_admin_cannot_access_owner_employee_performance(): void
    {
        $response = $this->actingAs($this->adminA)->get(route('owner.employee-performance.index'));
        $response->assertStatus(403);

        $detailResponse = $this->actingAs($this->adminA)->get(route('owner.employee-performance.admin-detail', $this->adminA));
        $detailResponse->assertStatus(403);
    }

    public function test_gudang_cannot_access_owner_employee_performance(): void
    {
        $response = $this->actingAs($this->gudang)->get(route('owner.employee-performance.index'));
        $response->assertStatus(403);
    }

    public function test_produksi_cannot_access_owner_employee_performance(): void
    {
        $response = $this->actingAs($this->produksi)->get(route('owner.employee-performance.index'));
        $response->assertStatus(403);
    }

    public function test_customer_cannot_access_owner_employee_performance(): void
    {
        $response = $this->actingAs($this->customerUser)->get(route('owner.employee-performance.index'));
        $response->assertStatus(403);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('owner.employee-performance.index'));
        $response->assertRedirect('/login');
    }

    // ── 2. Performance Metrics & Formula Calculation ────────────────────

    public function test_admin_performance_metrics_and_distinct_deal_ratio(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        // Admin A: 4 customers
        // Cust A1: Deal (1 SO, 1 confirmed payment)
        $custA1 = Customer::create([
            'customer_name'      => 'Toko A1',
            'phone'              => '0811111111',
            'handled_by_user_id' => $this->adminA->id,
            'first_contact_date' => $today,
        ]);
        $orderA1 = Order::create([
            'customer_id'  => $custA1->id,
            'order_no'     => 'SO-A1-1',
            'order_date'   => $today,
            'status'       => OrderStatus::DEAL,
            'total_amount' => 1000000,
        ]);
        $invA1 = Invoice::create([
            'order_id'     => $orderA1->id,
            'invoice_no'   => 'INV-A1-1',
            'invoice_date' => $today,
            'total_amount' => '1000000.00',
            'status'       => InvoiceStatus::PARTIAL,
        ]);
        Payment::create([
            'invoice_id'   => $invA1->id,
            'payment_no'   => 'PAY-A1-1',
            'payment_date' => $today,
            'amount'       => '500000.00',
            'payment_type' => PaymentType::DP,
            'status'       => PaymentStatus::CONFIRMED,
        ]);

        // Cust A2: Deal with 2 repeat orders (still counts as 1 Deal customer!)
        $custA2 = Customer::create([
            'customer_name'      => 'Toko A2',
            'phone'              => '0822222222',
            'handled_by_user_id' => $this->adminA->id,
            'first_contact_date' => $today,
        ]);
        $orderA2_1 = Order::create([
            'customer_id'  => $custA2->id,
            'order_no'     => 'SO-A2-1',
            'order_date'   => $today,
            'status'       => OrderStatus::SELESAI,
            'total_amount' => 1000000,
        ]);
        $invA2_1 = Invoice::create([
            'order_id'     => $orderA2_1->id,
            'invoice_no'   => 'INV-A2-1',
            'invoice_date' => $today,
            'total_amount' => '1000000.00',
            'status'       => InvoiceStatus::PAID,
        ]);
        Payment::create([
            'invoice_id'   => $invA2_1->id,
            'payment_no'   => 'PAY-A2-1',
            'payment_date' => $today,
            'amount'       => '1000000.00',
            'payment_type' => PaymentType::PELUNASAN,
            'status'       => PaymentStatus::CONFIRMED,
        ]);

        $orderA2_2 = Order::create([
            'customer_id'  => $custA2->id,
            'order_no'     => 'SO-A2-2',
            'order_date'   => $today,
            'status'       => OrderStatus::DEAL,
            'total_amount' => 2000000,
        ]);
        $invA2_2 = Invoice::create([
            'order_id'     => $orderA2_2->id,
            'invoice_no'   => 'INV-A2-2',
            'invoice_date' => $today,
            'total_amount' => '2000000.00',
            'status'       => InvoiceStatus::PARTIAL,
        ]);
        Payment::create([
            'invoice_id'   => $invA2_2->id,
            'payment_no'   => 'PAY-A2-2',
            'payment_date' => $today,
            'amount'       => '1000000.00',
            'payment_type' => PaymentType::DP,
            'status'       => PaymentStatus::CONFIRMED,
        ]);

        // Cust A3: Belum Deal (Order exists, but payment is PENDING)
        $custA3 = Customer::create([
            'customer_name'      => 'Toko A3',
            'phone'              => '0833333333',
            'handled_by_user_id' => $this->adminA->id,
            'first_contact_date' => $today,
        ]);
        $orderA3 = Order::create([
            'customer_id'  => $custA3->id,
            'order_no'     => 'SO-A3-1',
            'order_date'   => $today,
            'status'       => OrderStatus::KOMUNIKASI,
            'total_amount' => 1000000,
        ]);
        $invA3 = Invoice::create([
            'order_id'     => $orderA3->id,
            'invoice_no'   => 'INV-A3-1',
            'invoice_date' => $today,
            'total_amount' => '1000000.00',
            'status'       => InvoiceStatus::UNPAID,
        ]);
        Payment::create([
            'invoice_id'   => $invA3->id,
            'payment_no'   => 'PAY-A3-1',
            'payment_date' => $today,
            'amount'       => '500000.00',
            'payment_type' => PaymentType::DP,
            'status'       => PaymentStatus::PENDING,
        ]);

        // Cust A4: Belum Deal (Prospect only, no order)
        Customer::create([
            'customer_name'      => 'Toko A4',
            'phone'              => '0844444444',
            'handled_by_user_id' => $this->adminA->id,
            'first_contact_date' => $today,
        ]);

        // Admin B: 2 customers, both Deal
        $custB1 = Customer::create([
            'customer_name'      => 'Toko B1',
            'phone'              => '0855555555',
            'handled_by_user_id' => $this->adminB->id,
            'first_contact_date' => $today,
        ]);
        $orderB1 = Order::create([
            'customer_id'  => $custB1->id,
            'order_no'     => 'SO-B1-1',
            'order_date'   => $today,
            'status'       => OrderStatus::DEAL,
            'total_amount' => 800000,
        ]);
        $invB1 = Invoice::create([
            'order_id'     => $orderB1->id,
            'invoice_no'   => 'INV-B1-1',
            'invoice_date' => $today,
            'total_amount' => '800000.00',
            'status'       => InvoiceStatus::PARTIAL,
        ]);
        Payment::create([
            'invoice_id'   => $invB1->id,
            'payment_no'   => 'PAY-B1-1',
            'payment_date' => $today,
            'amount'       => '400000.00',
            'payment_type' => PaymentType::DP,
            'status'       => PaymentStatus::CONFIRMED,
        ]);

        $custB2 = Customer::create([
            'customer_name'      => 'Toko B2',
            'phone'              => '0866666666',
            'handled_by_user_id' => $this->adminB->id,
            'first_contact_date' => $today,
        ]);
        $orderB2 = Order::create([
            'customer_id'  => $custB2->id,
            'order_no'     => 'SO-B2-1',
            'order_date'   => $today,
            'status'       => OrderStatus::DEAL,
            'total_amount' => 1200000,
        ]);
        $invB2 = Invoice::create([
            'order_id'     => $orderB2->id,
            'invoice_no'   => 'INV-B2-1',
            'invoice_date' => $today,
            'total_amount' => '1200000.00',
            'status'       => InvoiceStatus::PARTIAL,
        ]);
        Payment::create([
            'invoice_id'   => $invB2->id,
            'payment_no'   => 'PAY-B2-1',
            'payment_date' => $today,
            'amount'       => '600000.00',
            'payment_type' => PaymentType::DP,
            'status'       => PaymentStatus::CONFIRMED,
        ]);

        // Service calculation test
        $service = app(AdminPerformanceService::class);
        $result = $service->calculatePerformance($today, $today, 'today');

        $statsA = $result['admin_stats']->firstWhere('admin.id', $this->adminA->id);
        $this->assertNotNull($statsA);
        $this->assertSame(4, $statsA['total_customers']);
        $this->assertSame(2, $statsA['deal_customers']); // Distinct deal customers: A1 and A2
        $this->assertSame(2, $statsA['belum_deal_customers']);
        $this->assertEquals(50.0, $statsA['deal_percentage']);
        $this->assertSame('50,00%', $statsA['deal_percentage_text']);
        $this->assertSame(4, $statsA['total_orders']); // 4 orders total (SO-A1-1, SO-A2-1, SO-A2-2, SO-A3-1)

        $statsB = $result['admin_stats']->firstWhere('admin.id', $this->adminB->id);
        $this->assertNotNull($statsB);
        $this->assertSame(2, $statsB['total_customers']);
        $this->assertSame(2, $statsB['deal_customers']);
        $this->assertSame(0, $statsB['belum_deal_customers']);
        $this->assertEquals(100.0, $statsB['deal_percentage']);
        $this->assertSame('100,00%', $statsB['deal_percentage_text']);
        $this->assertSame(2, $statsB['total_orders']);

        // Admin C has 0 customers: zero denominator must be null and "Belum Ada Data"
        $statsC = $result['admin_stats']->firstWhere('admin.id', $this->adminC->id);
        $this->assertNotNull($statsC);
        $this->assertSame(0, $statsC['total_customers']);
        $this->assertNull($statsC['deal_percentage']);
        $this->assertSame('Belum Ada Data', $statsC['deal_percentage_text']);

        // Check HTML rendering in Owner view
        $response = $this->actingAs($this->owner)->get(route('owner.employee-performance.index', ['preset' => 'today']));
        $response->assertStatus(200);
        $response->assertSee('Admin Aisyah');
        $response->assertSee('50,00%');
        $response->assertSee('Admin Cantika');
        $response->assertSee('Belum Ada Data');
    }

    // ── 3. Period Cohort Filtering ──────────────────────────────────────

    public function test_cohort_period_filter_matches_first_contact_date(): void
    {
        $lastMonthDate = Carbon::now('Asia/Jakarta')->subMonthNoOverflow()->startOfMonth()->toDateString();
        $thisMonthDate = Carbon::now('Asia/Jakarta')->startOfMonth()->toDateString();

        // Customer from last month
        Customer::create([
            'customer_name'      => 'Customer Bulan Lalu',
            'phone'              => '0877771111',
            'handled_by_user_id' => $this->adminA->id,
            'first_contact_date' => $lastMonthDate,
        ]);

        // Customer from this month
        Customer::create([
            'customer_name'      => 'Customer Bulan Ini',
            'phone'              => '0877772222',
            'handled_by_user_id' => $this->adminA->id,
            'first_contact_date' => $thisMonthDate,
        ]);

        $service = app(AdminPerformanceService::class);

        // Filter this_month
        [$startThis, $endThis] = $service->resolveDateRange('this_month', null, null);
        $thisMonthResult = $service->calculatePerformance($startThis, $endThis, 'this_month');
        $statsAThis = $thisMonthResult['admin_stats']->firstWhere('admin.id', $this->adminA->id);
        $this->assertSame(1, $statsAThis['total_customers']);

        // Filter last_month
        [$startLast, $endLast] = $service->resolveDateRange('last_month', null, null);
        $lastMonthResult = $service->calculatePerformance($startLast, $endLast, 'last_month');
        $statsALast = $lastMonthResult['admin_stats']->firstWhere('admin.id', $this->adminA->id);
        $this->assertSame(1, $statsALast['total_customers']);

        // Filter all
        [$startAll, $endAll] = $service->resolveDateRange('all', null, null);
        $allResult = $service->calculatePerformance($startAll, $endAll, 'all');
        $statsAAll = $allResult['admin_stats']->firstWhere('admin.id', $this->adminA->id);
        $this->assertSame(2, $statsAAll['total_customers']);
    }

    // ── 4. Unassigned Historical Customers ──────────────────────────────

    public function test_unassigned_historical_customers_are_not_randomly_attributed(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        Customer::create([
            'customer_name'      => 'Customer Tanpa PIC',
            'phone'              => '0899990000',
            'handled_by_user_id' => null,
            'first_contact_date' => $today,
        ]);

        $service = app(AdminPerformanceService::class);
        $result = $service->calculatePerformance($today, $today, 'today');

        $this->assertSame(1, $result['unassigned_count']);

        // Denominator of Admin A, B, C is NOT inflated
        $statsA = $result['admin_stats']->firstWhere('admin.id', $this->adminA->id);
        $this->assertSame(0, $statsA['total_customers']);
    }

    // ── 5. Customer Name & Admin Identity in Detail View ────────────────
    public function test_owner_admin_detail_renders_actual_customer_name_and_missing_name_fallback(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        // Customer with valid name
        $c1 = Customer::create([
            'customer_name'      => 'Toko Cinay Hijab',
            'phone'              => '083409875678',
            'handled_by_user_id' => $this->adminA->id,
            'first_contact_date' => $today,
        ]);

        // Customer with empty/missing name
        $c2 = Customer::create([
            'customer_name'      => '',
            'phone'              => '081299990000',
            'handled_by_user_id' => $this->adminA->id,
            'first_contact_date' => $today,
        ]);

        $response = $this->actingAs($this->owner)->get(route('owner.employee-performance.admin-detail', $this->adminA));

        $response->assertStatus(200);
        // Customer name is rendered clearly
        $response->assertSee('Toko Cinay Hijab');
        // Missing name displays fallback
        $response->assertSee('Nama belum diisi');
        // Admin name and email rendered in header
        $response->assertSee($this->adminA->name);
        $response->assertSee($this->adminA->email);
        // Formatted phone is visible
        $response->assertSee('0834-0987-5678');
        // Order count rendered
        $response->assertSee('0');
    }

    public function test_customer_model_display_name_and_name_accessors(): void
    {
        $c1 = new Customer(['customer_name' => 'Toko Aisyah Hijab']);
        $this->assertSame('Toko Aisyah Hijab', $c1->customer_name);
        $this->assertSame('Toko Aisyah Hijab', $c1->display_name);
        $this->assertSame('Toko Aisyah Hijab', $c1->name);

        $c2 = new Customer(['customer_name' => '']);
        $this->assertSame('', $c2->customer_name);
        $this->assertSame('Nama belum diisi', $c2->display_name);
        $this->assertSame('', $c2->name);
    }

    // ── 7. Multi-Order Customer Deal Isolation & Consistency ───────────

    public function test_customer_with_two_orders_one_confirmed_dp_and_one_awaiting_deal(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        $customer = Customer::create([
            'customer_name'      => 'Toko Cinay Hijab',
            'phone'              => '081234567890',
            'handled_by_user_id' => $this->adminA->id,
            'first_contact_date' => $today,
        ]);

        // Order 1: Has active invoice with confirmed DP payment -> Deal
        $order1 = Order::create([
            'customer_id'  => $customer->id,
            'order_no'     => 'SO-202610-0003',
            'order_date'   => $today,
            'status'       => OrderStatus::DIKIRIM,
            'total_amount' => 3217000,
        ]);
        $inv1 = Invoice::create([
            'order_id'     => $order1->id,
            'invoice_no'   => 'INV-202610-0003',
            'invoice_date' => $today,
            'total_amount' => '3217000.00',
            'status'       => InvoiceStatus::PAID,
        ]);
        Payment::create([
            'invoice_id'   => $inv1->id,
            'payment_no'   => 'PAY-202610-0001',
            'payment_date' => $today,
            'amount'       => '1608500.00',
            'payment_type' => PaymentType::DP,
            'status'       => PaymentStatus::CONFIRMED,
        ]);
        Payment::create([
            'invoice_id'   => $inv1->id,
            'payment_no'   => 'PAY-202610-0002',
            'payment_date' => $today,
            'amount'       => '1608500.00',
            'payment_type' => PaymentType::PELUNASAN,
            'status'       => PaymentStatus::CONFIRMED,
        ]);

        // Order 2: In KOMUNIKASI, no invoice, no payments -> Menunggu Deal
        $order2 = Order::create([
            'customer_id'  => $customer->id,
            'order_no'     => 'SO-202610-0002',
            'order_date'   => $today,
            'status'       => OrderStatus::KOMUNIKASI,
            'total_amount' => 7600000,
        ]);

        // Model-level assertions: Deal status must NOT leak to order2
        $this->assertTrue($customer->isDeal(), 'Customer is Deal because at least 1 order reached confirmed Deal.');
        $this->assertTrue($order1->isDeal(), 'Order 1 is Deal based on its own confirmed payments.');
        $this->assertFalse($order2->isDeal(), 'Order 2 is NOT Deal; it must not inherit Deal from order 1 or customer.');
        $this->assertSame('Menunggu Deal', $order2->status_label);
        $this->assertSame('Dikirim', $order1->status_label);

        // Owner Admin Performance Detail View check
        $perfResponse = $this->actingAs($this->owner)->get(route('owner.employee-performance.admin-detail', $this->adminA));
        $perfResponse->assertOk();
        $perfResponse->assertSee('Toko Cinay Hijab');
        $perfResponse->assertSee('Status');
        $perfResponse->assertSee('Jumlah Order');
        $perfResponse->assertSee('2');

        // Production batch gate: cannot create batch for order2 because no confirmed DP
        $batchService = app(\App\Services\Production\BatchService::class);
        $this->assertFalse($batchService->canCreateForOrder($order2), 'Order 2 cannot create production batch because it has no confirmed DP.');
        $this->assertFalse($order2->hasConfirmedDownPayment());

        // Admin Dashboard check: SO-202610-0002 in Perlu Tindakan as Menunggu Deal
        $dashResponse = $this->actingAs($this->adminA)->get(route('admin.dashboard'));
        $dashResponse->assertOk();
        $dashResponse->assertSee('SO-202610-0002');
        $dashResponse->assertSee('Menunggu Deal');
    }

    public function test_pending_or_cancelled_payments_do_not_count_as_confirmed_dp_or_deal(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        $customer = Customer::create([
            'customer_name'      => 'Toko Pending Test',
            'phone'              => '081299998888',
            'handled_by_user_id' => $this->adminA->id,
            'first_contact_date' => $today,
        ]);

        $order = Order::create([
            'customer_id'  => $customer->id,
            'order_no'     => 'SO-PENDING-01',
            'order_date'   => $today,
            'status'       => OrderStatus::DEAL,
            'total_amount' => 1000000,
        ]);

        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-PENDING-01',
            'invoice_date' => $today,
            'total_amount' => '1000000.00',
            'status'       => InvoiceStatus::UNPAID,
        ]);

        // 1. Payment with PENDING status does NOT make order Deal
        $payment = Payment::create([
            'invoice_id'   => $invoice->id,
            'payment_no'   => 'PAY-PENDING-01',
            'payment_date' => $today,
            'amount'       => '500000.00',
            'payment_type' => PaymentType::DP,
            'status'       => PaymentStatus::PENDING,
        ]);

        $this->assertFalse($order->fresh()->isDeal());
        $this->assertFalse($customer->fresh()->isDeal());

        // 2. Payment with CANCELLED status does NOT make order Deal
        $payment->update(['status' => PaymentStatus::CANCELLED]);

        $this->assertFalse($order->fresh()->isDeal());
        $this->assertFalse($customer->fresh()->isDeal());

        // 3. CANCELLED invoice even with confirmed payment does NOT make order Deal
        $payment->update(['status' => PaymentStatus::CONFIRMED]);
        $invoice->update(['status' => InvoiceStatus::CANCELLED]);

        $this->assertFalse($order->fresh()->isDeal());
        $this->assertFalse($customer->fresh()->isDeal());

        // 4. Active invoice + confirmed payment makes order and customer Deal
        $invoice->update(['status' => InvoiceStatus::PARTIAL]);

        $this->assertTrue($order->fresh()->isDeal());
        $this->assertTrue($customer->fresh()->isDeal());
    }

    public function test_kpi_counts_unique_customers_and_total_orders_accurately(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        // Customer 1: 2 orders (both deal)
        $c1 = Customer::create([
            'customer_name'      => 'Customer Deal 2 Orders',
            'phone'              => '0811000001',
            'handled_by_user_id' => $this->adminB->id,
            'first_contact_date' => $today,
        ]);
        foreach (['SO-C1-1', 'SO-C1-2'] as $soNo) {
            $o = Order::create([
                'customer_id'  => $c1->id,
                'order_no'     => $soNo,
                'order_date'   => $today,
                'status'       => OrderStatus::DEAL,
                'total_amount' => 1000000,
            ]);
            $inv = Invoice::create([
                'order_id'     => $o->id,
                'invoice_no'   => 'INV-' . $soNo,
                'invoice_date' => $today,
                'total_amount' => '1000000.00',
                'status'       => InvoiceStatus::PARTIAL,
            ]);
            Payment::create([
                'invoice_id'   => $inv->id,
                'payment_no'   => 'PAY-' . $soNo,
                'payment_date' => $today,
                'amount'       => '500000.00',
                'payment_type' => PaymentType::DP,
                'status'       => PaymentStatus::CONFIRMED,
            ]);
        }

        // Customer 2: 2 orders (1 deal, 1 belum deal)
        $c2 = Customer::create([
            'customer_name'      => 'Customer Mixed 2 Orders',
            'phone'              => '0811000002',
            'handled_by_user_id' => $this->adminB->id,
            'first_contact_date' => $today,
        ]);
        $o2_1 = Order::create([
            'customer_id'  => $c2->id,
            'order_no'     => 'SO-C2-DEAL',
            'order_date'   => $today,
            'status'       => OrderStatus::DEAL,
            'total_amount' => 1000000,
        ]);
        $inv2_1 = Invoice::create([
            'order_id'     => $o2_1->id,
            'invoice_no'   => 'INV-C2-DEAL',
            'invoice_date' => $today,
            'total_amount' => '1000000.00',
            'status'       => InvoiceStatus::PARTIAL,
        ]);
        Payment::create([
            'invoice_id'   => $inv2_1->id,
            'payment_no'   => 'PAY-C2-DEAL',
            'payment_date' => $today,
            'amount'       => '500000.00',
            'payment_type' => PaymentType::DP,
            'status'       => PaymentStatus::CONFIRMED,
        ]);
        Order::create([
            'customer_id'  => $c2->id,
            'order_no'     => 'SO-C2-WAITING',
            'order_date'   => $today,
            'status'       => OrderStatus::KOMUNIKASI,
            'total_amount' => 1000000,
        ]);

        // Customer 3: 1 order (belum deal)
        $c3 = Customer::create([
            'customer_name'      => 'Customer Belum Deal 1 Order',
            'phone'              => '0811000003',
            'handled_by_user_id' => $this->adminB->id,
            'first_contact_date' => $today,
        ]);
        Order::create([
            'customer_id'  => $c3->id,
            'order_no'     => 'SO-C3-WAITING',
            'order_date'   => $today,
            'status'       => OrderStatus::KOMUNIKASI,
            'total_amount' => 1000000,
        ]);

        // Customer 4: 0 orders (prospek)
        Customer::create([
            'customer_name'      => 'Customer Prospek 0 Order',
            'phone'              => '0811000004',
            'handled_by_user_id' => $this->adminB->id,
            'first_contact_date' => $today,
        ]);

        $service = app(AdminPerformanceService::class);
        $detail = $service->getAdminDetail($this->adminB, $today, $today, 'today');

        // Customer level counts
        $this->assertSame(4, $detail['total_customers'], 'Must count unique customers (4).');
        $this->assertSame(2, $detail['deal_customers'], 'Must count distinct deal customers (C1, C2).');
        $this->assertSame(2, $detail['belum_deal_customers'], 'Must count distinct belum deal customers (C3, C4).');
        $this->assertEquals(50.0, $detail['deal_percentage'], 'Deal percentage = 2/4 * 100 = 50%');

        // Order level count
        $this->assertSame(5, $detail['total_orders'], 'Must count total orders (2 + 2 + 1 + 0 = 5).');

        // Assert response renders without mixing
        $response = $this->actingAs($this->owner)->get(route('owner.employee-performance.admin-detail', [
            'admin'  => $this->adminB,
            'preset' => 'today',
        ]));

        $response->assertOk();
        $response->assertSee('50,00%');
    }
}
