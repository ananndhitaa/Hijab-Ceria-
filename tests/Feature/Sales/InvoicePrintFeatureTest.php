<?php

namespace Tests\Feature\Sales;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Master\Customer;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\Sales\Payment;
use App\Models\System\Outlet;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePrintFeatureTest extends TestCase
{
    use RefreshDatabase;

    private Outlet $outletA;
    private Outlet $outletB;
    private User $admin;
    private User $owner;
    private User $customerUserA;
    private Customer $customerProfileA;
    private User $customerUserB;
    private Customer $customerProfileB;
    private Invoice $invoiceA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outletA = Outlet::create([
            'outlet_code' => 'OUT-A',
            'outlet_name' => 'Nayara Hijab Flagship Bandung',
            'address'     => 'Jl. Buah Batu No. 120 Bandung',
            'phone'       => '081234567890',
            'status'      => 'ACTIVE',
        ]);

        $this->outletB = Outlet::create([
            'outlet_code' => 'OUT-B',
            'outlet_name' => 'Nayara Hijab Cabang Jakarta',
            'address'     => 'Jl. Senopati No. 50 Jakarta',
            'phone'       => '081298765432',
            'status'      => 'ACTIVE',
        ]);

        $this->admin = User::factory()->create([
            'name'      => 'Admin Operasional',
            'email'     => 'admin@nayara.test',
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $this->outletA->id,
        ]);

        $this->owner = User::factory()->create([
            'name'      => 'Owner Toko',
            'email'     => 'owner@nayara.test',
            'role'      => UserRole::OWNER,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $this->outletA->id,
        ]);

        $this->customerUserA = User::factory()->create([
            'name'      => 'Siti Aisyah',
            'email'     => 'siti@nayara.test',
            'role'      => UserRole::CUSTOMER,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $this->outletA->id,
        ]);

        $this->customerProfileA = Customer::create([
            'user_id'       => $this->customerUserA->id,
            'customer_name' => 'Siti Aisyah Store',
            'phone'         => '08111222333',
            'address'       => 'Jl. Riau No. 45 Bandung',
            'email'         => 'siti@nayara.test',
            'active'        => true,
            'outlet_id'     => $this->outletA->id,
        ]);

        $this->customerUserB = User::factory()->create([
            'name'      => 'Dewi Sartika',
            'email'     => 'dewi@nayara.test',
            'role'      => UserRole::CUSTOMER,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $this->outletB->id,
        ]);

        $this->customerProfileB = Customer::create([
            'user_id'       => $this->customerUserB->id,
            'customer_name' => 'Dewi Boutique',
            'phone'         => '08222333444',
            'address'       => 'Jl. Kemang Raya No. 10 Jakarta',
            'email'         => 'dewi@nayara.test',
            'active'        => true,
            'outlet_id'     => $this->outletB->id,
        ]);

        $product = Product::create([
            'product_name' => 'Pashmina Ceruty Premium',
            'active'       => true,
        ]);

        $variant = ProductVariant::create([
            'product_id'         => $product->id,
            'variant_name'       => 'Dusty Pink',
            'sku'                => 'SKU-CERUTY-DP',
            'base_selling_price' => 75000,
        ]);

        $order = Order::create([
            'order_no'     => 'SO-202611-0001',
            'customer_id'  => $this->customerProfileA->id,
            'user_id'      => $this->customerUserA->id,
            'outlet_id'    => $this->outletA->id,
            'order_date'   => '2026-11-19',
            'status'       => OrderStatus::DEAL,
            'total_qty'    => 10,
            'grand_total'  => 750000,
            'total_amount' => 750000,
        ]);

        OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 75000,
        ]);

        $this->invoiceA = Invoice::create([
            'invoice_no'   => 'INV-202611-0001',
            'order_id'     => $order->id,
            'invoice_date' => '2026-11-19',
            'due_date'     => '2026-11-26',
            'subtotal'     => 750000,
            'tax_amount'   => 0,
            'total_amount' => 750000,
            'status'       => InvoiceStatus::PARTIAL,
            'outlet_id'    => $this->outletA->id,
        ]);

        Payment::create([
            'payment_no'       => 'PAY-202611-0001',
            'invoice_id'       => $this->invoiceA->id,
            'payment_date'     => '2026-11-19',
            'amount'           => 375000,
            'payment_method'   => PaymentMethod::TRANSFER_BANK,
            'payment_type'     => PaymentType::DP,
            'status'           => PaymentStatus::CONFIRMED,
            'confirmed_by'     => $this->admin->id,
            'confirmed_at'     => now(),
            'notes'            => 'DP 50% via BCA',
        ]);
    }

    public function test_guest_cannot_access_print_invoice(): void
    {
        $response = $this->get(route('admin.invoices.print', $this->invoiceA));
        $response->assertRedirect('/login');

        $custResponse = $this->get(route('customer.invoices.print', $this->invoiceA));
        $custResponse->assertRedirect('/login');
    }

    public function test_admin_can_access_print_invoice(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.invoices.print', $this->invoiceA));

        $response->assertOk();
        $response->assertViewIs('admin.invoices.print');
        $response->assertSee('INV-202611-0001');
        $response->assertSee('Nayara Hijab Flagship Bandung');
        $response->assertSee('Siti Aisyah Store');
        $response->assertSee('Pashmina Ceruty Premium');
        $response->assertSee('Dusty Pink');
        $response->assertSee('Cetak / Simpan PDF');
    }

    public function test_owner_can_access_print_invoice(): void
    {
        $response = $this->actingAs($this->owner)->get(route('owner.invoices.print', $this->invoiceA));

        $response->assertOk();
        $response->assertViewIs('admin.invoices.print');
        $response->assertSee('INV-202611-0001');
        $response->assertSee('Nayara Hijab Flagship Bandung');
    }

    public function test_customer_can_print_their_own_invoice(): void
    {
        $response = $this->actingAs($this->customerUserA)->get(route('customer.invoices.print', $this->invoiceA));

        $response->assertOk();
        $response->assertViewIs('admin.invoices.print');
        $response->assertSee('INV-202611-0001');
        $response->assertSee('Siti Aisyah Store');
        $response->assertSee('Pashmina Ceruty Premium');
    }

    public function test_customer_cannot_print_other_customers_invoice(): void
    {
        $response = $this->actingAs($this->customerUserB)->get(route('customer.invoices.print', $this->invoiceA));

        $response->assertForbidden();
    }
}
