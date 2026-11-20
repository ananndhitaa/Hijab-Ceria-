<?php

namespace Tests\Feature\Admin;

use App\Enums\ExpenseType;
use App\Enums\UserRole;
use App\Models\Finance\BusinessExpense;
use App\Models\Master\Customer;
use App\Models\Sales\Order;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
    }

    public function test_admin_can_view_expense_index(): void
    {
        BusinessExpense::create([
            'expense_date' => '2026-08-15',
            'expense_type' => ExpenseType::OPERATING,
            'description'  => 'Biaya Listrik Kantor Agustus',
            'amount'       => '750000.00',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.expenses.index'));

        $response->assertStatus(200);
        $response->assertSee('Biaya &amp; Pengeluaran', false);
        $response->assertSee('Biaya Listrik Kantor Agustus');
        $response->assertSee('Rp 750.000');
    }

    public function test_admin_can_open_expense_create_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.expenses.create'));

        $response->assertStatus(200);
        $response->assertSee('Catat Pengeluaran Bisnis');
    }

    public function test_admin_can_create_general_expense_with_null_order(): void
    {
        $payload = [
            'expense_date' => '2026-08-20',
            'expense_type' => ExpenseType::OPERATING->value,
            'order_id'     => '',
            'description'  => 'Langganan Internet & Wifi Kantor',
            'amount'       => '450000.00',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.expenses.store'), $payload);

        $expense = BusinessExpense::first();
        $this->assertNotNull($expense);
        $response->assertRedirect(route('admin.expenses.show', $expense));

        $this->assertDatabaseHas('business_expenses', [
            'id'           => $expense->id,
            'order_id'     => null,
            'expense_type' => ExpenseType::OPERATING->value,
            'description'  => 'Langganan Internet & Wifi Kantor',
            'amount'       => '450000.00',
            'created_by'   => $this->admin->id,
        ]);
        $this->assertFalse($expense->isAttributedToOrder());
    }

    public function test_admin_can_create_order_attributed_expense(): void
    {
        $customer = Customer::create(['customer_name' => 'Butik Al-Fatih']);
        $order = Order::create([
            'customer_id' => $customer->id,
            'order_no'    => 'SO-202608-0001',
            'order_date'  => '2026-08-10',
            'status'      => 'DEAL',
        ]);

        $payload = [
            'expense_date'  => '2026-08-12',
            'expense_type'  => ExpenseType::MARKETING->value,
            'campaign_name' => 'TikTok Ads Campaign',
            'order_id'      => $order->id,
            'description'   => 'Iklan Berbayar TikTok Khusus Order Butik Al-Fatih',
            'amount'        => '350000.50',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.expenses.store'), $payload);

        $expense = BusinessExpense::first();
        $this->assertNotNull($expense);
        $response->assertRedirect(route('admin.expenses.show', $expense));

        $this->assertDatabaseHas('business_expenses', [
            'id'            => $expense->id,
            'order_id'      => $order->id,
            'expense_type'  => ExpenseType::MARKETING->value,
            'campaign_name' => 'TikTok Ads Campaign',
            'amount'        => '350000.50',
            'created_by'    => $this->admin->id,
        ]);
        $this->assertTrue($expense->isAttributedToOrder());
        $this->assertTrue($expense->isMarketingExpense());
    }

    public function test_expense_amount_must_be_greater_than_zero(): void
    {
        $payload = [
            'expense_date' => '2026-08-20',
            'expense_type' => ExpenseType::OPERATING->value,
            'description'  => 'Biaya Nol',
            'amount'       => '0.00',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.expenses.store'), $payload);

        $response->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('business_expenses', 0);
    }

    public function test_invalid_expense_type_rejected(): void
    {
        $payload = [
            'expense_date' => '2026-08-20',
            'expense_type' => 'INVALID_TYPE',
            'description'  => 'Jenis Palsu',
            'amount'       => '100000.00',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.expenses.store'), $payload);

        $response->assertSessionHasErrors('expense_type');
        $this->assertDatabaseCount('business_expenses', 0);
    }

    public function test_invalid_order_id_rejected(): void
    {
        $payload = [
            'expense_date' => '2026-08-20',
            'expense_type' => ExpenseType::OPERATING->value,
            'order_id'     => 99999, // non-existent order
            'description'  => 'Order Palsu',
            'amount'       => '100000.00',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.expenses.store'), $payload);

        $response->assertSessionHasErrors('order_id');
        $this->assertDatabaseCount('business_expenses', 0);
    }

    public function test_admin_can_update_expense(): void
    {
        $expense = BusinessExpense::create([
            'expense_date' => '2026-08-10',
            'expense_type' => ExpenseType::OPERATING,
            'description'  => 'Keterangan Lama',
            'amount'       => '200000.00',
        ]);

        $payload = [
            'expense_date' => '2026-08-11',
            'expense_type' => ExpenseType::OPERATING->value,
            'description'  => 'Keterangan Baru Setelah Edit',
            'amount'       => '250000.00',
            'order_id'     => null,
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.expenses.update', $expense), $payload);

        $response->assertRedirect(route('admin.expenses.show', $expense));
        $this->assertDatabaseHas('business_expenses', [
            'id'          => $expense->id,
            'description' => 'Keterangan Baru Setelah Edit',
            'amount'      => '250000.00',
        ]);
    }

    public function test_campaign_name_optional_for_marketing(): void
    {
        $payload = [
            'expense_date'  => '2026-08-15',
            'expense_type'  => ExpenseType::MARKETING->value,
            'campaign_name' => '', // optional, left empty
            'description'   => 'Biaya Brosur Marketing Toko',
            'amount'        => '120000.00',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.expenses.store'), $payload);

        $expense = BusinessExpense::first();
        $this->assertNotNull($expense);
        $response->assertRedirect(route('admin.expenses.show', $expense));

        $this->assertDatabaseHas('business_expenses', [
            'id'            => $expense->id,
            'campaign_name' => null,
            'expense_type'  => ExpenseType::MARKETING->value,
        ]);
    }

    public function test_non_marketing_expense_normalizes_campaign_name_to_null(): void
    {
        $payload = [
            'expense_date'  => '2026-08-15',
            'expense_type'  => ExpenseType::OPERATING->value,
            'campaign_name' => 'Irrelevant Campaign Name',
            'description'   => 'Beli Alat Tulis Kantor',
            'amount'        => '50000.00',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.expenses.store'), $payload);

        $expense = BusinessExpense::first();
        $this->assertNotNull($expense);
        $response->assertRedirect(route('admin.expenses.show', $expense));

        $this->assertDatabaseHas('business_expenses', [
            'id'            => $expense->id,
            'expense_type'  => ExpenseType::OPERATING->value,
            'campaign_name' => null, // normalized to NULL
        ]);
    }

    public function test_money_amount_stored_without_precision_loss(): void
    {
        $payload = [
            'expense_date' => '2026-08-15',
            'expense_type' => ExpenseType::TAX->value,
            'description'  => 'Pajak PPh 21',
            'amount'       => '1234567.89',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.expenses.store'), $payload);

        $expense = BusinessExpense::first();
        $this->assertNotNull($expense);

        $this->assertDatabaseHas('business_expenses', [
            'id'     => $expense->id,
            'amount' => '1234567.89',
        ]);
        $this->assertSame('1234567.89', (string) $expense->amount);
    }

    public function test_expense_destroy_route_is_not_available(): void
    {
        $expense = BusinessExpense::create([
            'expense_date' => '2026-08-10',
            'expense_type' => ExpenseType::OPERATING,
            'description'  => 'Biaya Keamanan',
            'amount'       => '100000.00',
        ]);

        $response = $this->actingAs($this->admin)->delete("/admin/expenses/{$expense->id}");

        $response->assertStatus(405); // Method Not Allowed since destroy is excluded
        $this->assertDatabaseHas('business_expenses', ['id' => $expense->id]);
    }

    public function test_non_admin_cannot_access_expense_routes(): void
    {
        $gudangUser   = User::factory()->create(['role' => UserRole::GUDANG]);
        $produksiUser = User::factory()->create(['role' => UserRole::PRODUKSI]);
        $customerUser = User::factory()->create(['role' => UserRole::CUSTOMER]);
        $ownerUser    = User::factory()->create(['role' => UserRole::OWNER]);

        // Guest redirected to login
        $this->get(route('admin.expenses.index'))->assertRedirect(route('login'));

        // Non-admin roles get 403 Forbidden
        $this->actingAs($gudangUser)->get(route('admin.expenses.index'))->assertStatus(403);
        $this->actingAs($produksiUser)->get(route('admin.expenses.index'))->assertStatus(403);
        $this->actingAs($customerUser)->get(route('admin.expenses.index'))->assertStatus(403);
        $this->actingAs($ownerUser)->get(route('admin.expenses.index'))->assertStatus(403);

        $this->actingAs($gudangUser)->post(route('admin.expenses.store'), [
            'expense_date' => '2026-08-01',
            'expense_type' => ExpenseType::OPERATING->value,
            'description'  => 'Illegal write',
            'amount'       => '50000',
        ])->assertStatus(403);
    }

    public function test_expense_list_search_and_filters_work(): void
    {
        $customer = Customer::create(['customer_name' => 'Toko Hijab Syar\'i']);
        $order = Order::create([
            'customer_id' => $customer->id,
            'order_no'    => 'SO-202608-0099',
            'order_date'  => '2026-08-01',
            'status'      => 'DEAL',
        ]);

        $exp1 = BusinessExpense::create([
            'expense_date'  => '2026-08-05',
            'expense_type'  => ExpenseType::MARKETING,
            'campaign_name' => 'Instagram Reels Blast',
            'order_id'      => $order->id,
            'description'   => 'Promosi Khusus Pelanggan Syari',
            'amount'        => '500000.00',
        ]);

        $exp2 = BusinessExpense::create([
            'expense_date' => '2026-08-15',
            'expense_type' => ExpenseType::OPERATING,
            'description'  => 'Beli Kertas Print Resi',
            'amount'       => '75000.00',
        ]);

        // Search by description
        $resSearch = $this->actingAs($this->admin)->get(route('admin.expenses.index', ['search' => 'Kertas Print']));
        $resSearch->assertSee('Beli Kertas Print Resi');
        $resSearch->assertDontSee('Instagram Reels Blast');

        // Search by related order no
        $resOrder = $this->actingAs($this->admin)->get(route('admin.expenses.index', ['search' => 'SO-202608-0099']));
        $resOrder->assertSee('Instagram Reels Blast');
        $resOrder->assertDontSee('Beli Kertas Print Resi');

        // Filter by expense_type
        $resType = $this->actingAs($this->admin)->get(route('admin.expenses.index', ['expense_type' => ExpenseType::OPERATING->value]));
        $resType->assertSee('Beli Kertas Print Resi');
        $resType->assertDontSee('Instagram Reels Blast');

        // Filter by attribution (order)
        $resAttrOrder = $this->actingAs($this->admin)->get(route('admin.expenses.index', ['attribution' => 'order']));
        $resAttrOrder->assertSee('Instagram Reels Blast');
        $resAttrOrder->assertDontSee('Beli Kertas Print Resi');

        // Filter by attribution (general)
        $resAttrGeneral = $this->actingAs($this->admin)->get(route('admin.expenses.index', ['attribution' => 'general']));
        $resAttrGeneral->assertSee('Beli Kertas Print Resi');
        $resAttrGeneral->assertDontSee('Instagram Reels Blast');

        // Filter by date range
        $resDate = $this->actingAs($this->admin)->get(route('admin.expenses.index', [
            'start_date' => '2026-08-01',
            'end_date'   => '2026-08-10',
        ]));
        $resDate->assertSee('Instagram Reels Blast');
        $resDate->assertDontSee('Beli Kertas Print Resi');
    }

    public function test_related_order_and_customer_displayed_correctly_on_show_page(): void
    {
        $customer = Customer::create([
            'customer_name' => 'Toko Mecca Hijab',
            'phone'         => '081299887766',
            'address'       => 'Jl. Thamrin No. 5 Jakarta',
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'order_no'    => 'SO-202608-0055',
            'order_date'  => '2026-08-01',
            'status'      => 'DEAL',
        ]);

        $expense = BusinessExpense::create([
            'expense_date'  => '2026-08-05',
            'expense_type'  => ExpenseType::MARKETING,
            'campaign_name' => 'Endorsement Selebgram',
            'order_id'      => $order->id,
            'description'   => 'Biaya Endorsement untuk Pesanan Mecca',
            'amount'        => '1500000.00',
            'created_by'    => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.expenses.show', $expense));

        $response->assertStatus(200);
        $response->assertSee('Endorsement Selebgram');
        $response->assertSee('SO-202608-0055');
        $response->assertSee('Toko Mecca Hijab');
        $response->assertSee('Rp 1.500.000');
        $response->assertSee($this->admin->name);
        $response->assertDontSee('Hapus');
    }

    public function test_admin_can_open_expense_edit_page(): void
    {
        $expense = BusinessExpense::create([
            'expense_date' => '2026-08-10',
            'expense_type' => ExpenseType::OPERATING,
            'description'  => 'Biaya Edit Form',
            'amount'       => '300000.00',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.expenses.edit', $expense));

        $response->assertStatus(200);
        $response->assertSee('Edit Pengeluaran Bisnis');
        $response->assertSee('Biaya Edit Form');
    }

    public function test_expense_type_enum_labels_and_helpers(): void
    {
        $this->assertSame('Marketing', ExpenseType::MARKETING->label());
        $this->assertSame('Operasional', ExpenseType::OPERATING->label());
        $this->assertSame('Bunga', ExpenseType::INTEREST->label());
        $this->assertSame('Pajak', ExpenseType::TAX->label());
        $this->assertSame('Depresiasi', ExpenseType::DEPRECIATION->label());
        $this->assertSame('Amortisasi', ExpenseType::AMORTIZATION->label());

        $this->assertFalse(ExpenseType::MARKETING->isEbitdaExcluded());
        $this->assertFalse(ExpenseType::OPERATING->isEbitdaExcluded());
        $this->assertTrue(ExpenseType::INTEREST->isEbitdaExcluded());
        $this->assertTrue(ExpenseType::TAX->isEbitdaExcluded());
        $this->assertTrue(ExpenseType::DEPRECIATION->isEbitdaExcluded());
        $this->assertTrue(ExpenseType::AMORTIZATION->isEbitdaExcluded());
    }
}
