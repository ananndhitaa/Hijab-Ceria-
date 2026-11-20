<?php

namespace Tests\Feature\Customer;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Master\Customer;
use App\Models\System\Outlet;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\Sales\Payment;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerInvoiceFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $customerUserA;
    private Customer $customerProfileA;
    private User $customerUserB;
    private Customer $customerProfileB;
    private Outlet $outlet;
    private Order $orderA1;
    private Order $orderA2;
    private Order $orderB;
    private Invoice $invoiceA1;
    private Invoice $invoiceA2;
    private Invoice $invoiceB1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outlet = Outlet::create([
            'outlet_code' => 'OUT-INV-01',
            'outlet_name' => 'Outlet Invoice Test',
            'address'     => 'Jl. Uji No. 1',
            'phone'       => '081234567890',
            'status'      => 'ACTIVE',
        ]);

        // Customer User A
        $this->customerUserA = User::factory()->create([
            'name'      => 'Customer User A',
            'email'     => 'customer_inv_a@nayara.test',
            'role'      => UserRole::CUSTOMER,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $this->outlet->id,
        ]);

        $this->customerProfileA = Customer::create([
            'user_id'       => $this->customerUserA->id,
            'customer_name' => 'Nayara Hijab Client A',
            'phone'         => '08111111111',
            'address'       => 'Jl. Buah Batu Bandung',
            'email'         => 'customer_inv_a@nayara.test',
            'active'        => true,
            'outlet_id'     => $this->outlet->id,
        ]);

        // Customer User B
        $this->customerUserB = User::factory()->create([
            'name'      => 'Customer User B',
            'email'     => 'customer_inv_b@nayara.test',
            'role'      => UserRole::CUSTOMER,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $this->outlet->id,
        ]);

        $this->customerProfileB = Customer::create([
            'user_id'       => $this->customerUserB->id,
            'customer_name' => 'Nayara Hijab Client B',
            'phone'         => '08222222222',
            'address'       => 'Jl. Dago Bandung',
            'email'         => 'customer_inv_b@nayara.test',
            'active'        => true,
            'outlet_id'     => $this->outlet->id,
        ]);

        // Order A1 for Customer A
        $this->orderA1 = Order::create([
            'order_no'     => 'ORD-INV-A01',
            'order_date'   => now()->toDateString(),
            'customer_id'  => $this->customerProfileA->id,
            'user_id'      => $this->customerUserA->id,
            'outlet_id'    => $this->outlet->id,
            'status'       => OrderStatus::DEAL,
            'total_amount' => 500000,
        ]);

        // Invoice A1 (UNPAID)
        $this->invoiceA1 = Invoice::create([
            'invoice_no'   => 'INV-2026-001',
            'order_id'     => $this->orderA1->id,
            'invoice_date' => now()->toDateString(),
            'due_date'     => now()->addDays(7)->toDateString(),
            'subtotal'     => 500000,
            'tax_amount'   => 0,
            'total_amount' => 500000,
            'status'       => InvoiceStatus::UNPAID,
            'outlet_id'    => $this->outlet->id,
        ]);

        // Order A2 for Customer A
        $this->orderA2 = Order::create([
            'order_no'     => 'ORD-INV-A02',
            'order_date'   => now()->subDay()->toDateString(),
            'customer_id'  => $this->customerProfileA->id,
            'user_id'      => $this->customerUserA->id,
            'outlet_id'    => $this->outlet->id,
            'status'       => OrderStatus::DEAL,
            'total_amount' => 300000,
        ]);

        // Invoice A2 (PAID)
        $this->invoiceA2 = Invoice::create([
            'invoice_no'   => 'INV-2026-002',
            'order_id'     => $this->orderA2->id,
            'invoice_date' => now()->subDay()->toDateString(),
            'due_date'     => now()->addDays(5)->toDateString(),
            'subtotal'     => 300000,
            'tax_amount'   => 0,
            'total_amount' => 300000,
            'status'       => InvoiceStatus::PAID,
            'outlet_id'    => $this->outlet->id,
        ]);

        Payment::create([
            'payment_no'     => 'PAY-INV-001',
            'invoice_id'     => $this->invoiceA2->id,
            'payment_date'   => now()->toDateString(),
            'amount'         => 300000,
            'payment_method' => PaymentMethod::TRANSFER_BANK,
            'payment_type'   => PaymentType::PELUNASAN,
            'status'         => PaymentStatus::CONFIRMED,
            'outlet_id'      => $this->outlet->id,
        ]);

        // Order B for Customer B
        $this->orderB = Order::create([
            'order_no'     => 'ORD-INV-B01',
            'order_date'   => now()->toDateString(),
            'customer_id'  => $this->customerProfileB->id,
            'user_id'      => $this->customerUserB->id,
            'outlet_id'    => $this->outlet->id,
            'status'       => OrderStatus::DEAL,
            'total_amount' => 750000,
        ]);

        // Invoice B1
        $this->invoiceB1 = Invoice::create([
            'invoice_no'   => 'INV-2026-B01',
            'order_id'     => $this->orderB->id,
            'invoice_date' => now()->toDateString(),
            'due_date'     => now()->addDays(7)->toDateString(),
            'subtotal'     => 750000,
            'tax_amount'   => 0,
            'total_amount' => 750000,
            'status'       => InvoiceStatus::UNPAID,
            'outlet_id'    => $this->outlet->id,
        ]);
    }

    public function test_guest_cannot_access_customer_invoices(): void
    {
        $response = $this->get(route('customer.invoices.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_customer_can_view_own_invoices_and_not_see_other_customer_invoices(): void
    {
        $response = $this->actingAs($this->customerUserA)
            ->get(route('customer.invoices.index'));

        $response->assertOk();
        $response->assertSee('INV-2026-001');
        $response->assertSee('INV-2026-002');
        $response->assertSee('ORD-INV-A01');

        // Customer B's invoice MUST NOT be visible
        $response->assertDontSee('INV-2026-B01');
        $response->assertDontSee('ORD-INV-B01');
    }

    public function test_customer_without_profile_renders_empty_state_safely(): void
    {
        $orphanUser = User::factory()->create([
            'name'      => 'Orphan Customer',
            'email'     => 'orphan@nayara.test',
            'role'      => UserRole::CUSTOMER,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $this->outlet->id,
        ]);

        $response = $this->actingAs($orphanUser)
            ->get(route('customer.invoices.index'));

        $response->assertOk();
        $response->assertSee('Belum Ada Tagihan');
        $response->assertDontSee('INV-2026-001');
    }

    public function test_customer_invoices_search_by_invoice_number(): void
    {
        $response = $this->actingAs($this->customerUserA)
            ->get(route('customer.invoices.index', ['search' => 'INV-2026-001']));

        $response->assertOk();
        $response->assertSee('INV-2026-001');
        $response->assertDontSee('INV-2026-002');
    }

    public function test_customer_invoices_filter_by_status(): void
    {
        $response = $this->actingAs($this->customerUserA)
            ->get(route('customer.invoices.index', ['status' => InvoiceStatus::PAID->value]));

        $response->assertOk();
        $response->assertSee('INV-2026-002');
        $response->assertDontSee('INV-2026-001');
    }

    public function test_admin_opening_cash_edit_renders_read_only_notice_for_admin(): void
    {
        $admin = User::factory()->create([
            'name'      => 'Admin User',
            'email'     => 'admin_test@nayara.test',
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $this->outlet->id,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.opening-cash.edit'));

        $response->assertOk();
        $response->assertSee('Saldo Awal Kas');
        $response->assertSee('Kewenangan Eksklusif Pemilik Konveksi (Owner)');
    }

    public function test_owner_accessing_admin_opening_cash_redirects_to_owner_route(): void
    {
        $owner = User::factory()->create([
            'name'      => 'Owner User',
            'email'     => 'owner_test@nayara.test',
            'role'      => UserRole::OWNER,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $this->outlet->id,
        ]);

        $response = $this->actingAs($owner)
            ->get(route('admin.opening-cash.edit'));

        $response->assertRedirect(route('owner.opening-cash.create'));
    }

    public function test_admin_cannot_post_opening_cash_when_isolation_enabled(): void
    {
        config(['nayara.tenant_isolation_enabled' => true]);

        $admin = User::factory()->create([
            'name'      => 'Admin Mutate User',
            'email'     => 'admin_mutate@nayara.test',
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $this->outlet->id,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.opening-cash.store'), [
                'opening_date' => now()->toDateString(),
                'balance'      => 10000000,
            ]);

        $response->assertForbidden();
    }
}
