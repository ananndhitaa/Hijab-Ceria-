<?php

namespace Tests\Feature\Customer;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\ShipmentStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Master\Customer;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\Sales\Payment;
use App\Models\System\Shipment;
use App\Models\System\User;
use App\Support\MoneyFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPortalFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $customerUserA;
    private Customer $customerProfileA;
    private User $customerUserB;
    private Customer $customerProfileB;
    private ProductVariant $variant1;
    private ProductVariant $variant2;

    protected function setUp(): void
    {
        parent::setUp();

        // Customer A setup
        $this->customerUserA = User::factory()->create([
            'name'   => 'Customer User A',
            'email'  => 'customerA@nayara.test',
            'role'   => UserRole::CUSTOMER,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->customerProfileA = Customer::create([
            'user_id'       => $this->customerUserA->id,
            'customer_name' => 'Toko Hijab Aisyah',
            'phone'         => '081234567801',
            'address'       => 'Jl. Buah Batu No. 10 Bandung',
            'email'         => 'customerA@nayara.test',
            'active'        => true,
        ]);

        // Customer B setup
        $this->customerUserB = User::factory()->create([
            'name'   => 'Customer User B',
            'email'  => 'customerB@nayara.test',
            'role'   => UserRole::CUSTOMER,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->customerProfileB = Customer::create([
            'user_id'       => $this->customerUserB->id,
            'customer_name' => 'Butik Khadijah',
            'phone'         => '081234567802',
            'address'       => 'Jl. Riau No. 45 Bandung',
            'email'         => 'customerB@nayara.test',
            'active'        => true,
        ]);

        // Master Product & Variants
        $product = Product::create([
            'product_name' => 'Pashmina Silk Premium',
            'active'       => true,
        ]);

        $this->variant1 = ProductVariant::create([
            'product_id'         => $product->id,
            'variant_name'       => 'Rose Gold',
            'sku'                => 'SKU-SILK-RG',
            'base_selling_price' => 75000,
        ]);

        $this->variant2 = ProductVariant::create([
            'product_id'         => $product->id,
            'variant_name'       => 'Champagne',
            'sku'                => 'SKU-SILK-CH',
            'base_selling_price' => 85000,
        ]);
    }

    public function test_guest_is_redirected_to_login_for_customer_routes(): void
    {
        $this->get(route('customer.dashboard'))->assertRedirect(route('login'));
        $this->get(route('customer.orders.index'))->assertRedirect(route('login'));
        $this->get('/customer/orders/1')->assertRedirect(route('login'));
    }

    public function test_internal_roles_are_forbidden_from_customer_portal(): void
    {
        $order = Order::create([
            'customer_id' => $this->customerProfileA->id,
            'order_no'    => 'SO-TEST-AUTH-1',
            'order_date'  => '2026-09-01',
            'status'      => OrderStatus::DEAL,
        ]);

        $internalRoles = [
            UserRole::ADMIN,
            UserRole::GUDANG,
            UserRole::PRODUKSI,
            UserRole::OWNER,
        ];

        foreach ($internalRoles as $role) {
            $user = User::factory()->create(['role' => $role, 'status' => UserStatus::ACTIVE]);

            $this->actingAs($user)->get(route('customer.dashboard'))->assertStatus(403);
            $this->actingAs($user)->get(route('customer.orders.index'))->assertStatus(403);
            $this->actingAs($user)->get(route('customer.orders.show', $order))->assertStatus(403);
        }
    }

    public function test_customer_dashboard_displays_correct_live_metrics_and_recent_orders(): void
    {
        // Active order for Customer A (PRODUKSI is an active status)
        $activeOrder = Order::create([
            'customer_id' => $this->customerProfileA->id,
            'order_no'    => 'SO-202609-0001',
            'order_date'  => '2026-09-01',
            'status'      => OrderStatus::PRODUKSI,
        ]);
        OrderItem::create([
            'order_id'           => $activeOrder->id,
            'variant_id'         => $this->variant1->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 75000,
        ]);

        // Completed order for Customer A (SELESAI)
        $completedOrder = Order::create([
            'customer_id' => $this->customerProfileA->id,
            'order_no'    => 'SO-202609-0002',
            'order_date'  => '2026-09-02',
            'status'      => OrderStatus::SELESAI,
        ]);
        OrderItem::create([
            'order_id'           => $completedOrder->id,
            'variant_id'         => $this->variant2->id,
            'qty_ordered'        => 20,
            'unit_selling_price' => 85000,
        ]);

        // Order for Customer B (must NOT be counted for Customer A)
        $orderB = Order::create([
            'customer_id' => $this->customerProfileB->id,
            'order_no'    => 'SO-202609-0099',
            'order_date'  => '2026-09-03',
            'status'      => OrderStatus::SELESAI,
        ]);
        OrderItem::create([
            'order_id'           => $orderB->id,
            'variant_id'         => $this->variant1->id,
            'qty_ordered'        => 50,
            'unit_selling_price' => 75000,
        ]);

        // Expected Total Belanja for A: (10 * 75000) + (20 * 85000) = 750,000 + 1,700,000 = 2,450,000
        $expectedTotalBelanja = MoneyFormatter::rupiah('2450000.00');

        $response = $this->actingAs($this->customerUserA)->get(route('customer.dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('activeOrdersCount', 1);
        $response->assertViewHas('completedOrdersCount', 1);
        $response->assertViewHas('totalBelanja', '2450000.00');

        $response->assertSee('1 Pesanan');
        $response->assertSee($expectedTotalBelanja);
        $response->assertSee('SO-202609-0001');
        $response->assertSee('SO-202609-0002');
        $response->assertDontSee('SO-202609-0099');
    }

    public function test_customer_without_linked_profile_handles_dashboard_gracefully(): void
    {
        $unlinkedCustomerUser = User::factory()->create([
            'role'   => UserRole::CUSTOMER,
            'status' => UserStatus::ACTIVE,
        ]);

        $response = $this->actingAs($unlinkedCustomerUser)->get(route('customer.dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('customer', null);
        $response->assertViewHas('activeOrdersCount', 0);
        $response->assertViewHas('completedOrdersCount', 0);
        $response->assertViewHas('totalBelanja', '0.00');
        $response->assertSee('Profil Pelanggan Belum Terhubung');
        $response->assertSee('Belum Ada Pesanan');
    }

    public function test_customer_orders_index_isolates_orders_per_authenticated_customer(): void
    {
        $orderA = Order::create([
            'customer_id' => $this->customerProfileA->id,
            'order_no'    => 'SO-202609-A001',
            'order_date'  => '2026-09-05',
            'status'      => OrderStatus::DEAL,
        ]);
        OrderItem::create([
            'order_id'           => $orderA->id,
            'variant_id'         => $this->variant1->id,
            'qty_ordered'        => 5,
            'unit_selling_price' => 75000,
        ]);

        $orderB = Order::create([
            'customer_id' => $this->customerProfileB->id,
            'order_no'    => 'SO-202609-B001',
            'order_date'  => '2026-09-05',
            'status'      => OrderStatus::DEAL,
        ]);
        OrderItem::create([
            'order_id'           => $orderB->id,
            'variant_id'         => $this->variant2->id,
            'qty_ordered'        => 8,
            'unit_selling_price' => 85000,
        ]);

        // Customer A visits orders list
        $responseA = $this->actingAs($this->customerUserA)->get(route('customer.orders.index'));
        $responseA->assertStatus(200);
        $responseA->assertSee('SO-202609-A001');
        $responseA->assertDontSee('SO-202609-B001');

        // Customer B visits orders list
        $responseB = $this->actingAs($this->customerUserB)->get(route('customer.orders.index'));
        $responseB->assertStatus(200);
        $responseB->assertSee('SO-202609-B001');
        $responseB->assertDontSee('SO-202609-A001');
    }

    public function test_customer_a_cannot_see_customer_b_order_number_or_content_in_index_response(): void
    {
        $orderB = Order::create([
            'customer_id' => $this->customerProfileB->id,
            'order_no'    => 'SO-SECRET-B-9988',
            'order_date'  => '2026-09-05',
            'status'      => OrderStatus::DEAL,
        ]);
        OrderItem::create([
            'order_id'           => $orderB->id,
            'variant_id'         => $this->variant2->id,
            'qty_ordered'        => 99,
            'unit_selling_price' => 85000,
        ]);

        $responseA = $this->actingAs($this->customerUserA)->get(route('customer.orders.index'));

        $responseA->assertStatus(200);
        $responseA->assertDontSee('SO-SECRET-B-9988');
        $responseA->assertDontSee('Butik Khadijah');
    }

    public function test_customer_orders_index_can_search_and_filter_by_status(): void
    {
        $order1 = Order::create([
            'customer_id' => $this->customerProfileA->id,
            'order_no'    => 'SO-202609-SEARCH-1',
            'order_date'  => '2026-09-01',
            'status'      => OrderStatus::DEAL,
        ]);
        OrderItem::create([
            'order_id'           => $order1->id,
            'variant_id'         => $this->variant1->id,
            'qty_ordered'        => 2,
            'unit_selling_price' => 75000,
        ]);

        $order2 = Order::create([
            'customer_id' => $this->customerProfileA->id,
            'order_no'    => 'SO-202609-SEARCH-2',
            'order_date'  => '2026-09-02',
            'status'      => OrderStatus::SELESAI,
        ]);
        OrderItem::create([
            'order_id'           => $order2->id,
            'variant_id'         => $this->variant2->id,
            'qty_ordered'        => 3,
            'unit_selling_price' => 85000,
        ]);

        // Search test
        $searchResponse = $this->actingAs($this->customerUserA)->get(route('customer.orders.index', [
            'search' => 'SEARCH-1',
        ]));
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('SO-202609-SEARCH-1');
        $searchResponse->assertDontSee('SO-202609-SEARCH-2');

        // Status filter test
        $filterResponse = $this->actingAs($this->customerUserA)->get(route('customer.orders.index', [
            'status' => OrderStatus::SELESAI->value,
        ]));
        $filterResponse->assertStatus(200);
        $filterResponse->assertSee('SO-202609-SEARCH-2');
        $filterResponse->assertDontSee('SO-202609-SEARCH-1');
    }

    public function test_customer_can_view_own_order_detail_with_items_invoice_and_shipment(): void
    {
        $order = Order::create([
            'customer_id' => $this->customerProfileA->id,
            'order_no'    => 'SO-202609-DETAIL-1',
            'order_date'  => '2026-09-06',
            'status'      => OrderStatus::DIKIRIM,
            'notes'       => 'Tolong dipack dengan bubble wrap tebal.',
        ]);

        OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variant1->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 75000,
        ]);

        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-202609-001',
            'invoice_date' => '2026-09-06',
            'total_amount' => '750000.00',
            'status'       => InvoiceStatus::PAID,
        ]);

        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_type'   => PaymentType::PELUNASAN,
            'amount'         => '750000.00',
            'payment_date'   => '2026-09-06',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
            'reference_no'   => 'TRX-BCA-998877',
            'status'         => PaymentStatus::CONFIRMED,
        ]);

        Shipment::create([
            'order_id'          => $order->id,
            'tracking_no'       => 'JNE-REG-882233',
            'courier'           => 'JNE',
            'courier_service'   => 'REG',
            'shipping_cost'     => 25000,
            'recipient_address' => 'Jl. Buah Batu No. 10 Bandung',
            'shipped_at'        => now(),
            'status'            => ShipmentStatus::SHIPPED,
        ]);

        $response = $this->actingAs($this->customerUserA)->get(route('customer.orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee('SO-202609-DETAIL-1');
        $response->assertSee('Dikirim'); // Customer friendly label
        $response->assertSee('Pashmina Silk Premium');
        $response->assertSee('Rose Gold');
        $response->assertSee(MoneyFormatter::rupiah(75000));
        $response->assertSee(MoneyFormatter::rupiah('750000.00'));

        // Invoice & Payment checks
        $response->assertSee('INV-202609-001');
        $response->assertSee('Lunas');
        $response->assertSee('TRX-BCA-998877');

        // Shipment checks
        $response->assertSee('JNE');
        $response->assertSee('JNE-REG-882233');

        // Note checks
        $response->assertSee('Tolong dipack dengan bubble wrap tebal.');
    }

    public function test_customer_a_using_customer_b_order_id_returns_404(): void
    {
        $orderB = Order::create([
            'customer_id' => $this->customerProfileB->id,
            'order_no'    => 'SO-202609-PRIVATE-B',
            'order_date'  => '2026-09-07',
            'status'      => OrderStatus::DEAL,
        ]);

        // Customer A attempts to view Customer B's order ID
        $response = $this->actingAs($this->customerUserA)->get(route('customer.orders.show', $orderB));

        // Must abort 404 to avoid enumeration of another customer's orders
        $response->assertStatus(404);
    }

    public function test_unlinked_customer_user_opening_arbitrary_order_returns_404(): void
    {
        $unlinkedCustomerUser = User::factory()->create([
            'role'   => UserRole::CUSTOMER,
            'status' => UserStatus::ACTIVE,
        ]);

        $orderA = Order::create([
            'customer_id' => $this->customerProfileA->id,
            'order_no'    => 'SO-202609-DETAIL-A',
            'order_date'  => '2026-09-07',
            'status'      => OrderStatus::DEAL,
        ]);

        // Unlinked user accessing existing order => 404
        $responseExisting = $this->actingAs($unlinkedCustomerUser)->get(route('customer.orders.show', $orderA));
        $responseExisting->assertStatus(404);

        // Unlinked user accessing arbitrary non-existing order ID => 404
        $responseArbitrary = $this->actingAs($unlinkedCustomerUser)->get('/customer/orders/999999');
        $responseArbitrary->assertStatus(404);
    }

    public function test_customer_responses_do_not_contain_internal_financial_terms_or_values(): void
    {
        $order = Order::create([
            'customer_id' => $this->customerProfileA->id,
            'order_no'    => 'SO-202609-LEAK-TEST',
            'order_date'  => '2026-09-08',
            'status'      => OrderStatus::PRODUKSI,
        ]);
        OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variant1->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 75000,
        ]);

        $detailResponse = $this->actingAs($this->customerUserA)->get(route('customer.orders.show', $order));
        $indexResponse = $this->actingAs($this->customerUserA)->get(route('customer.orders.index'));
        $dashboardResponse = $this->actingAs($this->customerUserA)->get(route('customer.dashboard'));

        $detailResponse->assertStatus(200);
        $indexResponse->assertStatus(200);
        $dashboardResponse->assertStatus(200);

        // Explicit list of forbidden internal financial and operational terms
        $forbiddenTerms = [
            'HPP',
            'gross profit',
            'net profit',
            'direct labor cost',
            'material cost',
            'packaging cost',
            'operating expense',
            'EBITDA',
            'ROI',
            'MER',
            'COGS',
            'raw_material_cost',
            'overhead_cost',
            'cost_per_piece',
            'hpp_calculated',
            'BatchMaterial',
        ];

        foreach ($forbiddenTerms as $term) {
            $detailResponse->assertDontSeeText($term, false);
            $indexResponse->assertDontSeeText($term, false);
            $dashboardResponse->assertDontSeeText($term, false);
        }
    }

    public function test_customer_order_routes_are_strictly_read_only(): void
    {
        // Assert mutation routes do not exist
        $this->actingAs($this->customerUserA)
            ->post('/customer/orders', ['order_no' => 'SO-NEW'])
            ->assertStatus(405);

        $this->actingAs($this->customerUserA)
            ->put('/customer/orders/1', ['status' => 'SELESAI'])
            ->assertStatus(405);

        $this->actingAs($this->customerUserA)
            ->delete('/customer/orders/1')
            ->assertStatus(405);
    }

    public function test_clean_baseline_seeded_customer_account_is_unlinked_and_renders_empty_portal(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        // 1. seeded CUSTOMER User exists
        $demoUser = User::where('email', 'customer@example.com')->first();
        $this->assertNotNull($demoUser, 'Seeded demo customer user must exist');

        // 2. role === UserRole::CUSTOMER
        $this->assertSame(UserRole::CUSTOMER, $demoUser->role);

        // 3. user is active
        $this->assertTrue($demoUser->isActive());

        // 4. In clean baseline, User::customer is intentionally null (unlinked)
        $this->assertNull($demoUser->customer, 'User->customer relation must be null in clean baseline');
        $this->assertFalse(Customer::where('user_id', $demoUser->id)->exists(), 'Demo customer user must have no linked customer record');
        $this->assertFalse(Customer::where('customer_name', 'Hijab Fatima Store')->exists(), 'Hijab Fatima Store must not be seeded in clean baseline');

        // 5. existing internal users remain seeded
        $this->assertNotNull(User::where('email', 'admin@example.com')->first());
        $this->assertNotNull(User::where('email', 'gudang@example.com')->first());
        $this->assertNotNull(User::where('email', 'produksi@example.com')->first());
        $this->assertNotNull(User::where('email', 'owner@example.com')->first());

        // 6. Authenticate and verify unlinked portal dashboard access (returns 200 with professional empty state)
        $response = $this->actingAs($demoUser)->get(route('customer.dashboard'));
        $response->assertOk();
        $response->assertSee('Beranda Akun');
        $response->assertSee('Riwayat Pesanan');
        $response->assertSee('Belum Ada Pesanan');

        // Verify no internal financial data leaked to customer
        $response->assertDontSeeText('HPP');
        $response->assertDontSeeText('Modal Produksi');
        $response->assertDontSeeText('Beban Usaha');
        $response->assertDontSeeText('Laba Kotor');

        // Verify no placeholder navigation items appear
        $response->assertDontSeeText('Pembayaran & Invoice');
        $response->assertDontSeeText('Alamat Saya');
        $response->assertDontSeeText('Profil Saya');

        // 7. Verify customer order list returns 200 and empty state
        $orderResponse = $this->actingAs($demoUser)->get(route('customer.orders.index'));
        $orderResponse->assertOk();
        $orderResponse->assertSee('Tidak ada pesanan ditemukan');
    }

    public function test_explicit_customer_seeder_links_demo_customer_profile_when_needed(): void
    {
        $this->seed([
            \Database\Seeders\UserSeeder::class,
            \Database\Seeders\CustomerSeeder::class,
        ]);

        $demoUser = User::where('email', 'customer@example.com')->first();
        $this->assertNotNull($demoUser);
        $this->assertNotNull($demoUser->customer);
        $this->assertSame('Hijab Fatima Store', $demoUser->customer->customer_name);

        $response = $this->actingAs($demoUser)->get(route('customer.dashboard'));
        $response->assertOk();
        $response->assertSee('Hijab Fatima Store');
    }

    public function test_customer_sidebar_has_no_placeholder_href_hash_links(): void
    {
        $response = $this->actingAs($this->customerUserA)->get(route('customer.dashboard'));
        $response->assertOk();

        // Ensure no href="#" placeholder links exist in the customer view
        $response->assertDontSee('href="#"', false);
        $response->assertDontSeeText('Pembayaran & Invoice');
        $response->assertDontSeeText('Alamat Saya');
        $response->assertDontSeeText('Profil Saya');
    }

    public function test_customer_can_submit_order_review(): void
    {
        $order = Order::create([
            'customer_id' => $this->customerProfileA->id,
            'order_no'    => 'SO-202609-REVIEW-1',
            'order_date'  => '2026-09-08',
            'status'      => OrderStatus::SELESAI,
        ]);

        $response = $this->actingAs($this->customerUserA)
            ->post(route('customer.orders.review', $order), [
                'rating'      => 5,
                'review_text' => 'Kualitas jahitan hijab rapi dan bahan sangat lembut.',
            ]);

        $response->assertRedirect(route('customer.orders.show', $order));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('order_reviews', [
            'order_id'      => $order->id,
            'customer_id'   => $this->customerProfileA->id,
            'rating'        => 5,
            'review_text'   => 'Kualitas jahitan hijab rapi dan bahan sangat lembut.',
        ]);

        // Viewing detail now displays the existing review
        $detailResponse = $this->actingAs($this->customerUserA)->get(route('customer.orders.show', $order));
        $detailResponse->assertOk();
        $detailResponse->assertSee('Ulasan Diterima');
        $detailResponse->assertSee('Kualitas jahitan hijab rapi dan bahan sangat lembut.');
    }
}
