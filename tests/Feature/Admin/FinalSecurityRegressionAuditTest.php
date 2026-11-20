<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Master\Customer;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\OrderTrackingToken;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\System\User;
use App\Services\Tracking\TrackingTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinalSecurityRegressionAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $customerUser;
    private User $gudangUser;
    private User $produksiUser;
    private User $ownerUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin        = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->customerUser = User::factory()->create(['role' => UserRole::CUSTOMER]);
        $this->gudangUser   = User::factory()->create(['role' => UserRole::GUDANG]);
        $this->produksiUser = User::factory()->create(['role' => UserRole::PRODUKSI]);
        $this->ownerUser    = User::factory()->create(['role' => UserRole::OWNER]);
    }

    // ── 1. Role Authorization Security ────────────────────────────────────

    public function test_guest_is_redirected_to_login_from_admin_endpoints(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.orders.index'))->assertRedirect(route('login'));
        $this->get(route('admin.customers.index'))->assertRedirect(route('login'));
        $this->get(route('admin.products.index'))->assertRedirect(route('login'));
        $this->get(route('admin.invoices.index'))->assertRedirect(route('login'));
        $this->get(route('admin.expenses.index'))->assertRedirect(route('login'));
        $this->get(route('admin.kanban.index'))->assertRedirect(route('login'));
        $this->get(route('admin.batches.index'))->assertRedirect(route('login'));
    }

    public function test_non_admin_roles_receive_403_on_admin_routes(): void
    {
        $nonAdminUsers = [
            $this->customerUser,
            $this->gudangUser,
            $this->produksiUser,
            $this->ownerUser,
        ];

        foreach ($nonAdminUsers as $user) {
            $this->actingAs($user)->get(route('admin.dashboard'))->assertStatus(403);
            $this->actingAs($user)->get(route('admin.orders.index'))->assertStatus(403);
            $this->actingAs($user)->get(route('admin.customers.index'))->assertStatus(403);
            $this->actingAs($user)->get(route('admin.products.index'))->assertStatus(403);
            $this->actingAs($user)->get(route('admin.invoices.index'))->assertStatus(403);
            $this->actingAs($user)->get(route('admin.expenses.index'))->assertStatus(403);
            $this->actingAs($user)->get(route('admin.kanban.index'))->assertStatus(403);
            $this->actingAs($user)->get(route('admin.batches.index'))->assertStatus(403);
        }
    }

    // ── 2. Tracking Token Security & Data Isolation ───────────────────────

    public function test_tracking_valid_token_renders_order_progress_without_financial_data(): void
    {
        $customer = Customer::create(['customer_name' => 'Siti Khadijah', 'phone' => '08123456789']);
        $product = Product::create(['product_name' => 'Pashmina Silk Exclusive', 'active' => true]);
        $variant = ProductVariant::create([
            'product_id'         => $product->id,
            'variant_name'       => 'Dusty Rose',
            'sku'                => 'SKU-DR-01',
            'base_selling_price' => 125000.00,
            'active'             => true,
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'order_no'    => 'SO-202608-8888',
            'order_date'  => '2026-08-20',
            'status'      => OrderStatus::PRODUKSI,
            'notes'       => 'SENSITIVE_INTERNAL_ADMIN_NOTE_DO_NOT_EXPOSE',
        ]);

        $order->items()->create([
            'variant_id'         => $variant->id,
            'qty_ordered'        => 50,
            'unit_selling_price' => 125000.00,
        ]);

        $tokenService = new TrackingTokenService();
        $rawToken = $tokenService->createForOrder($order);

        $response = $this->get("/track/{$rawToken}");

        $response->assertStatus(200);
        $response->assertSee('Lacak Status Pesanan');
        $response->assertSee('SO-202608-8888');
        $response->assertSee('Siti Khadijah');
        $response->assertSee('Pashmina Silk Exclusive');
        $response->assertSee('Dusty Rose');
        $response->assertSee('50 pcs');
        $response->assertSee('Proses Jahit &amp; Finishing', false);

        // Verify strictly NO internal data is exposed
        $response->assertDontSee('SENSITIVE_INTERNAL_ADMIN_NOTE_DO_NOT_EXPOSE');
        $response->assertDontSee('HPP');
        $response->assertDontSee('Gross Profit');
        $response->assertDontSee('Net Profit');
        $response->assertDontSee('Gross Margin');
        $response->assertDontSee('Net Margin');
        $response->assertDontSee('EBITDA');
        $response->assertDontSee('BOM');
        $response->assertDontSee('Supplier');
    }

    public function test_tracking_invalid_token_returns_404(): void
    {
        $response = $this->get('/track/invalid-fake-token-12345');
        $response->assertStatus(404);
    }

    public function test_tracking_revoked_token_returns_404(): void
    {
        $customer = Customer::create(['customer_name' => 'Toko Barokah']);
        $order = Order::create([
            'customer_id' => $customer->id,
            'order_no'    => 'SO-202608-9999',
            'order_date'  => '2026-08-20',
            'status'      => OrderStatus::KOMUNIKASI,
        ]);

        $tokenService = new TrackingTokenService();
        $rawToken = $tokenService->createForOrder($order);

        // Revoke the token
        $hash = hash('sha256', $rawToken);
        OrderTrackingToken::where('token_hash', $hash)->update(['revoked_at' => now()]);

        $response = $this->get("/track/{$rawToken}");
        $response->assertStatus(404);
    }

    public function test_token_regeneration_invalidates_old_token(): void
    {
        $customer = Customer::create(['customer_name' => 'Toko Hasanah']);
        $order = Order::create([
            'customer_id' => $customer->id,
            'order_no'    => 'SO-202608-7777',
            'order_date'  => '2026-08-20',
            'status'      => OrderStatus::DEAL,
        ]);

        $tokenService = new TrackingTokenService();
        $firstToken = $tokenService->createForOrder($order);

        // First token is valid
        $this->assertNotNull($tokenService->findValidToken($firstToken));

        // Explicitly regenerate second token for same order
        $secondToken = $tokenService->regenerateForOrder($order);

        // First token is now invalidated (revoked_at set)
        $this->assertNull($tokenService->findValidToken($firstToken));
        $this->get("/track/{$firstToken}")->assertStatus(404);

        // Second token is active and valid
        $this->assertNotNull($tokenService->findValidToken($secondToken));
        $this->get("/track/{$secondToken}")->assertStatus(200);
    }

    // ── 3. Owner Data Isolation in Admin Role ──────────────────────────────

    public function test_admin_dashboard_and_order_views_do_not_expose_owner_profitability(): void
    {
        $customer = Customer::create(['customer_name' => 'Owner Isolation Customer']);
        $product = Product::create(['product_name' => 'Hijab Segiempat Premium', 'active' => true]);
        $variant = ProductVariant::create([
            'product_id'         => $product->id,
            'variant_name'       => 'Navy',
            'sku'                => 'SKU-NV-01',
            'base_selling_price' => 75000.00,
            'active'             => true,
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'order_no'    => 'SO-202608-0005',
            'order_date'  => '2026-08-20',
            'status'      => OrderStatus::DEAL,
        ]);

        $order->items()->create([
            'variant_id'         => $variant->id,
            'qty_ordered'        => 20,
            'unit_selling_price' => 75000.00,
        ]);

        // 1. Admin Dashboard
        $dashResponse = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $dashResponse->assertStatus(200);
        $dashResponse->assertDontSee('HPP');
        $dashResponse->assertDontSee('Gross Profit');
        $dashResponse->assertDontSee('Net Profit');
        $dashResponse->assertDontSee('EBITDA');
        $dashResponse->assertDontSee('ROI');
        $dashResponse->assertDontSee('MER');

        // 2. Admin Order Show: Admin is allowed to see HPP & Laba Kotor (Tahap 3 Supervisor Revision), but must not see Owner executive metrics
        $orderResponse = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));
        $orderResponse->assertStatus(200);
        $orderResponse->assertDontSee('Net Profit');
        $orderResponse->assertDontSee('EBITDA');
        $orderResponse->assertDontSee('ROI');
        $orderResponse->assertDontSee('MER');
    }

    // ── 4. Sales Order & Batch Business Rule Safeguards ──────────────────

    public function test_admin_cannot_transition_order_to_arbitrary_status(): void
    {
        $customer = Customer::create(['customer_name' => 'Test Transition Customer']);
        $order = Order::create([
            'customer_id' => $customer->id,
            'order_no'    => 'SO-202608-1111',
            'order_date'  => '2026-08-20',
            'status'      => OrderStatus::DEAL,
        ]);

        // Attempting to confirm deal on an already DEAL order throws error
        $response = $this->actingAs($this->admin)->post(route('admin.orders.confirm-deal', $order));
        $response->assertSessionHas('error');
        $this->assertEquals(OrderStatus::DEAL, $order->fresh()->status);
    }

    public function test_production_batch_cannot_exceed_order_item_remaining_qty(): void
    {
        $customer = Customer::create(['customer_name' => 'Batch Test Customer']);
        $product = Product::create(['product_name' => 'Hijab Bergo', 'active' => true]);
        $variant = ProductVariant::create([
            'product_id'   => $product->id,
            'variant_name' => 'Hitam',
            'sku'          => 'SKU-BG-01',
            'active'       => true,
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'order_no'    => 'SO-202608-2222',
            'order_date'  => '2026-08-20',
            'status'      => OrderStatus::DEAL,
        ]);

        $item = $order->items()->create([
            'variant_id'         => $variant->id,
            'qty_ordered'        => 100,
            'unit_selling_price' => 50000,
        ]);

        // Attempt creating a batch with 150 pcs (remaining is 100)
        $response = $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 150,
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('production_batches', [
            'order_item_id' => $item->id,
        ]);
    }

    public function test_payment_cannot_exceed_outstanding_invoice_balance(): void
    {
        $customer = Customer::create(['customer_name' => 'Payment Test Customer']);
        $product = Product::create(['product_name' => 'Voal Polos', 'active' => true]);
        $variant = ProductVariant::create([
            'product_id'   => $product->id,
            'variant_name' => 'Cream',
            'sku'          => 'SKU-VP-01',
            'active'       => true,
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'order_no'    => 'SO-202608-3333',
            'order_date'  => '2026-08-20',
            'status'      => OrderStatus::DEAL,
        ]);

        $order->items()->create([
            'variant_id'         => $variant->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 50000, // Total = 500,000
        ]);

        // Create invoice
        $invoiceService = app(\App\Services\Sales\InvoiceService::class);
        $invoice = $invoiceService->createForOrder($order);

        // Attempt to pay 600,000 (exceeds total 500,000)
        $response = $this->actingAs($this->admin)->post(route('admin.invoices.payments.store', $invoice), [
            'payment_type'   => \App\Enums\PaymentType::PELUNASAN->value,
            'amount'         => 600000,
            'payment_date'   => '2026-08-20',
            'payment_method' => 'Transfer Bank',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('payments', [
            'invoice_id' => $invoice->id,
        ]);
    }
}
