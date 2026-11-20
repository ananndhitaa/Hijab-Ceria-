<?php

namespace Tests\Feature\Admin;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Master\Customer;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Master\Unit;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\Sales\Payment;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerProspectFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $otherAdmin;
    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name'   => 'Admin Siti',
            'role'   => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->otherAdmin = User::factory()->create([
            'name'   => 'Admin Budi',
            'role'   => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
        ]);

        $unit = Unit::create(['unit_name' => 'Pcs', 'symbol' => 'pcs']);
        $product = Product::create([
            'product_name' => 'Hijab Voal Miracle',
            'code'         => 'HVM',
            'description'  => 'Hijab Segiempat Voal',
            'active'       => true,
        ]);

        $materialUnit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $material = \App\Models\Master\Material::create([
            'material_name' => 'Kain Voal',
            'unit_id'       => $materialUnit->id,
            'cost_per_unit' => 20000,
            'active'        => true,
        ]);

        \App\Models\Inventory\MaterialStock::create([
            'material_id'   => $material->id,
            'qty_on_hand'   => 100,
            'avg_unit_cost' => 20000,
        ]);

        $this->variant = ProductVariant::create([
            'product_id'                           => $product->id,
            'sku'                                  => 'HVM-NVY',
            'variant_name'                         => 'Navy',
            'unit_id'                              => $unit->id,
            'hpp'                                  => 30000,
            'base_selling_price'                   => 75000,
            'selling_price'                        => 75000,
            'status'                               => 'ACTIVE',
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => 0,
            'direct_labor_cost_per_unit'           => 5000,
            'packaging_process_cost_per_good_unit' => 2000,
            'other_direct_cost_per_unit'           => 1000,
        ]);

        \App\Models\Master\BomItem::create([
            'variant_id'   => $this->variant->id,
            'material_id'  => $material->id,
            'qty_per_unit' => 1.0,
        ]);
    }

    public function test_admin_can_create_prospect_customer_without_sales_order(): void
    {
        $payload = [
            'customer_name'      => 'Toko Aisyah Hijab',
            'phone'              => '081234567890',
            'first_contact_date' => '2026-10-14',
            'notes'              => 'Tanya Voal Navy 20 pcs via WA',
            'address'            => 'Jl. Riau No. 12 Bandung',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.customers.store'), $payload);

        $response->assertRedirect(route('admin.customers.index'));
        $this->assertDatabaseHas('customers', [
            'customer_name'      => 'Toko Aisyah Hijab',
            'phone'              => '081234567890',
            'notes'              => 'Tanya Voal Navy 20 pcs via WA',
            'handled_by_user_id' => $this->admin->id,
            'user_id'            => null, // No portal account required
        ]);

        $customer = Customer::where('customer_name', 'Toko Aisyah Hijab')->firstOrFail();
        $this->assertEquals('2026-10-14', $customer->first_contact_date->toDateString());
        $this->assertFalse($customer->isDeal());
        $this->assertSame('Belum Deal', $customer->status_label);
        $this->assertSame($this->admin->id, $customer->handled_by_user_id);
    }

    public function test_duplicate_whatsapp_number_is_rejected_with_friendly_message(): void
    {
        Customer::create([
            'customer_name'      => 'Toko Barokah',
            'phone'              => '081299998888',
            'handled_by_user_id' => $this->admin->id,
            'first_contact_date' => '2026-10-01',
        ]);

        // Attempting to register another customer with different formatting but same digits
        $payload = [
            'customer_name'      => 'Toko Berkah Baru',
            'phone'              => '+62 812-9999-8888',
            'first_contact_date' => '2026-10-14',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.customers.store'), $payload);

        $response->assertSessionHasErrors('phone');
        $errorMsg = session('errors')->first('phone');
        $this->assertStringContainsString('Nomor WhatsApp ini sudah terdaftar pada customer Toko Barokah', $errorMsg);
        $this->assertDatabaseMissing('customers', ['customer_name' => 'Toko Berkah Baru']);
    }

    public function test_customer_with_no_invoice_or_no_payment_is_belum_deal(): void
    {
        $customer = Customer::create([
            'customer_name'      => 'Customer Tanya Saja',
            'phone'              => '081111222333',
            'handled_by_user_id' => $this->admin->id,
            'first_contact_date' => '2026-10-10',
        ]);

        $this->assertFalse($customer->isDeal());
        $this->assertSame('Belum Deal', $customer->status_label);

        // Add order without invoice
        $order = Order::create([
            'order_no'     => 'SO-TEST-001',
            'customer_id'  => $customer->id,
            'order_date'   => '2026-10-11',
            'status'       => OrderStatus::DEAL,
            'total_amount' => 1500000,
        ]);

        $customer = Customer::withDealStatus()->find($customer->id);
        $this->assertFalse($customer->isDeal());
        $this->assertSame('Belum Deal', $customer->status_label);

        // Add unpaid invoice without payment
        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-TEST-001',
            'invoice_date' => '2026-10-11',
            'total_amount' => '1500000.00',
            'status'       => InvoiceStatus::UNPAID,
        ]);

        $customer = Customer::withDealStatus()->find($customer->id);
        $this->assertFalse($customer->isDeal());
        $this->assertSame('Belum Deal', $customer->status_label);
    }

    public function test_customer_with_pending_or_cancelled_payment_remains_belum_deal(): void
    {
        $customer = Customer::create([
            'customer_name'      => 'Customer Bayar Pending',
            'phone'              => '082222333444',
            'handled_by_user_id' => $this->admin->id,
            'first_contact_date' => '2026-10-10',
        ]);

        $order = Order::create([
            'order_no'     => 'SO-TEST-002',
            'customer_id'  => $customer->id,
            'order_date'   => '2026-10-11',
            'status'       => OrderStatus::DEAL,
            'total_amount' => 1500000,
        ]);

        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-TEST-002',
            'invoice_date' => '2026-10-11',
            'total_amount' => '1500000.00',
            'status'       => InvoiceStatus::UNPAID,
        ]);

        $payment = Payment::create([
            'invoice_id'   => $invoice->id,
            'payment_no'   => 'PAY-TEST-001',
            'payment_date' => '2026-10-11',
            'amount'       => '500000.00',
            'payment_type' => PaymentType::DP,
            'status'       => PaymentStatus::PENDING,
        ]);

        $customer = Customer::withDealStatus()->find($customer->id);
        $this->assertFalse($customer->isDeal());

        // Change payment status to CANCELLED
        $payment->update(['status' => PaymentStatus::CANCELLED]);
        $customer = Customer::withDealStatus()->find($customer->id);
        $this->assertFalse($customer->isDeal());
    }

    public function test_customer_with_confirmed_dp_becomes_deal(): void
    {
        $customer = Customer::create([
            'customer_name'      => 'Customer Sah Deal',
            'phone'              => '083333444555',
            'handled_by_user_id' => $this->admin->id,
            'first_contact_date' => '2026-10-10',
        ]);

        $order = Order::create([
            'order_no'     => 'SO-TEST-003',
            'customer_id'  => $customer->id,
            'order_date'   => '2026-10-11',
            'status'       => OrderStatus::DEAL,
            'total_amount' => 1500000,
        ]);

        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-TEST-003',
            'invoice_date' => '2026-10-11',
            'total_amount' => '1500000.00',
            'status'       => InvoiceStatus::PARTIAL,
        ]);

        Payment::create([
            'invoice_id'   => $invoice->id,
            'payment_no'   => 'PAY-TEST-003',
            'payment_date' => '2026-10-11',
            'amount'       => '500000.00',
            'payment_type' => PaymentType::DP,
            'status'       => PaymentStatus::CONFIRMED,
        ]);

        $customer = Customer::withDealStatus()->find($customer->id);
        $this->assertTrue($customer->isDeal());
        $this->assertSame('Deal', $customer->status_label);
    }

    public function test_repeat_orders_do_not_duplicate_deal_customer(): void
    {
        $customer = Customer::create([
            'customer_name'      => 'Customer Setia',
            'phone'              => '085555666777',
            'handled_by_user_id' => $this->admin->id,
            'first_contact_date' => '2026-10-01',
        ]);

        // Order 1 with confirmed payment
        $order1 = Order::create([
            'order_no'     => 'SO-REP-001',
            'customer_id'  => $customer->id,
            'order_date'   => '2026-10-02',
            'status'       => OrderStatus::SELESAI,
            'total_amount' => 1000000,
        ]);
        $inv1 = Invoice::create([
            'order_id'     => $order1->id,
            'invoice_no'   => 'INV-REP-001',
            'invoice_date' => '2026-10-02',
            'total_amount' => '1000000.00',
            'status'       => InvoiceStatus::PAID,
        ]);
        Payment::create([
            'invoice_id'   => $inv1->id,
            'payment_no'   => 'PAY-REP-001',
            'payment_date' => '2026-10-02',
            'amount'       => '1000000.00',
            'payment_type' => PaymentType::PELUNASAN,
            'status'       => PaymentStatus::CONFIRMED,
        ]);

        // Order 2 with confirmed payment
        $order2 = Order::create([
            'order_no'     => 'SO-REP-002',
            'customer_id'  => $customer->id,
            'order_date'   => '2026-10-08',
            'status'       => OrderStatus::DEAL,
            'total_amount' => 2000000,
        ]);
        $inv2 = Invoice::create([
            'order_id'     => $order2->id,
            'invoice_no'   => 'INV-REP-002',
            'invoice_date' => '2026-10-08',
            'total_amount' => '2000000.00',
            'status'       => InvoiceStatus::PARTIAL,
        ]);
        Payment::create([
            'invoice_id'   => $inv2->id,
            'payment_no'   => 'PAY-REP-002',
            'payment_date' => '2026-10-08',
            'amount'       => '1000000.00',
            'payment_type' => PaymentType::DP,
            'status'       => PaymentStatus::CONFIRMED,
        ]);

        $customer = Customer::withDealStatus()->find($customer->id);
        $this->assertTrue($customer->isDeal());
        $this->assertCount(2, $customer->orders);
    }

    public function test_admin_customer_page_does_not_show_kpi_cards_or_conversion_rates(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.customers.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Persentase Deal');
        $response->assertDontSee('Performa Admin');
        $response->assertDontSee('Conversion Rate');
        $response->assertDontSee('Ranking');
        $response->assertSee('Customer');
    }

    public function test_admin_can_create_order_with_instant_dp_atomically(): void
    {
        $customer = Customer::create([
            'customer_name'      => 'Toko Muslimah',
            'phone'              => '087777888999',
            'handled_by_user_id' => $this->admin->id,
            'first_contact_date' => '2026-10-14',
        ]);

        $payload = [
            'customer_id'    => $customer->id,
            'order_date'     => '2026-10-14',
            'target_delivery_date' => '2026-10-25',
            'notes'          => 'Pesanan seragam pengajian',
            'items'          => [
                [
                    'variant_id'         => $this->variant->id,
                    'qty_ordered'        => 20,
                    'unit_selling_price' => 75000,
                ],
            ],
            'dp_amount'      => 750000,
            'payment_date'   => '2026-10-14',
            'payment_method' => 'Transfer Bank',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.orders.store'), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'customer_id' => $customer->id,
            'status'      => OrderStatus::DEAL->value,
        ]);

        $order = Order::where('customer_id', $customer->id)->firstOrFail();
        $this->assertNotNull($order->invoice);
        $this->assertSame(InvoiceStatus::PARTIAL, $order->invoice->status);
        $this->assertDatabaseHas('payments', [
            'invoice_id'   => $order->invoice->id,
            'amount'       => '750000.00',
            'payment_type' => PaymentType::DP->value,
            'status'       => PaymentStatus::CONFIRMED->value,
        ]);

        // Customer status is derived as DEAL
        $customer = Customer::withDealStatus()->find($customer->id);
        $this->assertTrue($customer->isDeal());
        $this->assertSame('Deal', $customer->status_label);
    }
}
