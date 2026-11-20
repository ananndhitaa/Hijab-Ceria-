<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Master\Customer;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\OrderTrackingToken;
use App\Models\System\User;
use App\Services\Tracking\TrackingTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTrackingFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Order $order;
    private TrackingTokenService $tokenService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->tokenService = app(TrackingTokenService::class);

        $customer = Customer::create([
            'customer_name' => 'Siti Khadijah',
            'phone'         => '081234567891',
            'address'       => 'Jl. Riau No. 20 Bandung',
        ]);

        $product = Product::create([
            'product_name' => 'Pashmina Ceruty',
            'active'       => true,
        ]);

        $variant = ProductVariant::create([
            'product_id'         => $product->id,
            'variant_name'       => 'Sage Green',
            'sku'                => 'SKU-CERUTY-SG',
            'base_selling_price' => 65000,
        ]);

        $this->order = Order::create([
            'customer_id' => $customer->id,
            'order_no'    => 'SO-202608-0001',
            'order_date'  => '2026-08-28',
            'status'      => OrderStatus::DEAL,
            'notes'       => 'Internal secret note: Priority VIP client.',
        ]);

        OrderItem::create([
            'order_id'           => $this->order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 20,
            'unit_selling_price' => 65000,
        ]);
    }

    public function test_active_token_direct_url_works_repeatedly(): void
    {
        $rawToken = $this->tokenService->createForOrder($this->order);

        // 1st request
        $response1 = $this->get("/track/{$rawToken}");
        $response1->assertStatus(200);
        $response1->assertSee('SO-202608-0001');

        // 2nd request (proves it is NOT single-use)
        $response2 = $this->get("/track/{$rawToken}");
        $response2->assertStatus(200);
        $response2->assertSee('SO-202608-0001');
    }

    public function test_track_page_loads_with_updated_copy(): void
    {
        $response = $this->get(route('track.index'));

        $response->assertStatus(200);
        $response->assertSee('Lacak Pesanan Anda');
        $response->assertSee('Masukkan token atau tautan pelacakan pesanan.');
        $response->assertSee('Token atau tautan tracking');
    }

    public function test_track_accepts_and_resolves_full_tracking_url(): void
    {
        $rawToken = $this->tokenService->createForOrder($this->order);
        $fullUrl = "http://127.0.0.1:8000/track/{$rawToken}";

        // When user enters full URL directly into path or extracts via service
        $extracted = TrackingTokenService::extractToken($fullUrl);
        $this->assertEquals($rawToken, $extracted);

        // Accessing route with full URL string (e.g. pasted into browser or encoded)
        $response = $this->get("/track/{$fullUrl}");
        $response->assertStatus(200);
        $response->assertSee('SO-202608-0001');
    }

    public function test_malformed_url_or_invalid_token_returns_friendly_404(): void
    {
        $response = $this->get('/track/invalid-non-hex-token');
        $response->assertStatus(404);
        $response->assertSee('Token atau tautan pelacakan tidak valid.');

        $randomHex = bin2hex(random_bytes(32));
        $responseRandom = $this->get("/track/{$randomHex}");
        $responseRandom->assertStatus(404);
        $responseRandom->assertSee('Link tracking tidak valid atau pesanan tidak ditemukan.');
        $responseRandom->assertDontSee('kedaluwarsa');
    }

    public function test_admin_can_regenerate_tracking_token(): void
    {
        $oldToken = $this->tokenService->createForOrder($this->order);
        $this->get("/track/{$oldToken}")->assertStatus(200);

        // Admin regenerates token
        $response = $this->actingAs($this->admin)->post(route('admin.orders.regenerate-tracking-token', $this->order));

        $response->assertRedirect(route('admin.orders.show', $this->order));
        $response->assertSessionHas('success', 'Tautan tracking baru berhasil dibuat.');
        $response->assertSessionHas('raw_tracking_url');

        $newTrackingUrl = session('raw_tracking_url');
        $this->assertNotEmpty($newTrackingUrl);
        $newToken = basename($newTrackingUrl);

        $this->assertNotEquals($oldToken, $newToken);

        // Old token is now revoked / invalid (404)
        $this->get("/track/{$oldToken}")->assertStatus(404);

        // New token works
        $this->get("/track/{$newToken}")->assertStatus(200);
        $this->get("/track/{$newToken}")->assertSee('SO-202608-0001');
    }

    public function test_only_token_hash_is_persisted_and_raw_token_is_not_stored(): void
    {
        $rawToken = $this->tokenService->createForOrder($this->order);

        $hash = hash('sha256', $rawToken);

        $this->assertDatabaseHas('order_tracking_tokens', [
            'order_id'   => $this->order->id,
            'token_hash' => $hash,
        ]);

        $this->assertDatabaseMissing('order_tracking_tokens', [
            'token_hash' => $rawToken,
        ]);
    }

    public function test_raw_url_appears_in_show_page_when_flashed_and_disappears_on_refresh(): void
    {
        $rawToken = $this->tokenService->createForOrder($this->order);
        $rawUrl = url("/track/{$rawToken}");

        // Flash raw url in session
        $responseWithFlash = $this->actingAs($this->admin)
            ->withSession(['raw_tracking_url' => $rawUrl])
            ->get(route('admin.orders.show', $this->order));

        $responseWithFlash->assertStatus(200);
        $responseWithFlash->assertSee('Tautan tracking baru berhasil dibuat.');
        $responseWithFlash->assertSee($rawUrl);
        $responseWithFlash->assertSee('Salin Tautan');

        // Subsequent refresh without flash in session
        $this->flushSession();
        $responseRefresh = $this->actingAs($this->admin)->get(route('admin.orders.show', $this->order));

        $responseRefresh->assertStatus(200);
        $responseRefresh->assertDontSee('Tautan tracking baru berhasil dibuat.');
        $responseRefresh->assertDontSee($rawUrl);
        $responseRefresh->assertSee('AKTIF');
        $responseRefresh->assertSee('Regenerasi Tautan');
    }

    public function test_non_admin_cannot_regenerate_tracking_token(): void
    {
        $roles = [UserRole::GUDANG, UserRole::PRODUKSI, UserRole::CUSTOMER];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($user)
                ->post(route('admin.orders.regenerate-tracking-token', $this->order))
                ->assertStatus(403);
        }
    }

    public function test_customer_tracking_page_does_not_expose_internal_confidential_metrics(): void
    {
        $rawToken = $this->tokenService->createForOrder($this->order);

        $response = $this->get("/track/{$rawToken}");
        $response->assertStatus(200);

        // Publicly relevant info visible
        $response->assertSee('SO-202608-0001');
        $response->assertSee('Pashmina Ceruty');

        // Internal secrets NOT visible
        $response->assertDontSee('Priority VIP client'); // internal notes
        $response->assertDontSee('HPP');
        $response->assertDontSee('Profit');
        $response->assertDontSee('EBITDA');
        $response->assertDontSee('Supplier');
    }

    public function test_customer_tracking_reflects_order_production_status(): void
    {
        $this->order->update(['status' => OrderStatus::PRODUKSI]);
        $rawToken = $this->tokenService->createForOrder($this->order);

        $response = $this->get("/track/{$rawToken}");
        $response->assertStatus(200);
        $response->assertSee('Produksi');
    }

    public function test_token_with_null_expires_at_is_permanently_valid(): void
    {
        $rawToken = $this->tokenService->createForOrder($this->order);
        $tokenRecord = OrderTrackingToken::where('token_hash', hash('sha256', $rawToken))->first();

        $this->assertNull($tokenRecord->expires_at);
        $this->assertNull($tokenRecord->revoked_at);
        $this->assertTrue($tokenRecord->isValid());

        $response = $this->get("/track/{$rawToken}");
        $response->assertStatus(200);
        $response->assertSee('SO-202608-0001');
    }

    public function test_token_does_not_expire_automatically_over_time(): void
    {
        $rawToken = $this->tokenService->createForOrder($this->order);

        // Fast forward 1 year and 5 years
        $this->travel(365)->days();
        $this->get("/track/{$rawToken}")->assertStatus(200);

        $this->travel(1825)->days();
        $this->get("/track/{$rawToken}")->assertStatus(200);
    }

    public function test_old_order_with_null_expiration_remains_valid(): void
    {
        $oldOrder = Order::create([
            'customer_id' => $this->order->customer_id,
            'order_no'    => 'SO-202001-0001',
            'order_date'  => '2020-01-10',
            'status'      => OrderStatus::SELESAI,
            'created_at'  => '2020-01-10 08:00:00',
        ]);

        $rawToken = $this->tokenService->createForOrder($oldOrder);
        $response = $this->get("/track/{$rawToken}");

        $response->assertStatus(200);
        $response->assertSee('SO-202001-0001');
    }

    public function test_updating_sales_order_does_not_change_or_revoke_token(): void
    {
        $rawToken = $this->tokenService->createForOrder($this->order);
        $hashBefore = hash('sha256', $rawToken);

        // Simulate admin updating customer, notes, or items
        $this->order->update([
            'notes' => 'Updated notes by customer service',
        ]);

        // Token record remains untouched
        $this->assertDatabaseHas('order_tracking_tokens', [
            'order_id'   => $this->order->id,
            'token_hash' => $hashBefore,
            'revoked_at' => null,
        ]);

        $this->get("/track/{$rawToken}")->assertStatus(200);
    }

    public function test_deal_confirmation_does_not_change_or_revoke_token(): void
    {
        $commOrder = Order::create([
            'customer_id' => $this->order->customer_id,
            'order_no'    => 'SO-202609-0099',
            'order_date'  => '2026-09-01',
            'status'      => OrderStatus::KOMUNIKASI,
        ]);

        $rawToken = $this->tokenService->createForOrder($commOrder);

        // Transition KOMUNIKASI -> DEAL
        $commOrder->update(['status' => OrderStatus::DEAL]);

        $this->get("/track/{$rawToken}")->assertStatus(200);
        $this->assertNotNull($this->tokenService->findValidToken($rawToken));
    }

    public function test_production_batch_creation_does_not_change_or_revoke_token(): void
    {
        $rawToken = $this->tokenService->createForOrder($this->order);

        // Transition to BAHAN simulating batch creation
        $this->order->update(['status' => OrderStatus::BAHAN]);

        $this->get("/track/{$rawToken}")->assertStatus(200);
        $this->assertNotNull($this->tokenService->findValidToken($rawToken));
    }

    public function test_payment_updates_do_not_change_or_revoke_token(): void
    {
        $rawToken = $this->tokenService->createForOrder($this->order);

        $invoiceService = app(\App\Services\Sales\InvoiceService::class);
        $invoice = $invoiceService->createForOrder($this->order);

        $paymentService = app(\App\Services\Sales\PaymentService::class);
        $paymentService->recordPayment($invoice, [
            'amount'         => '650000', // DP
            'payment_date'   => now()->toDateString(),
            'payment_type'   => \App\Enums\PaymentType::DP,
            'payment_method' => 'TRANSFER',
        ]);

        $this->get("/track/{$rawToken}")->assertStatus(200);
        $this->assertNotNull($this->tokenService->findValidToken($rawToken));
    }

    public function test_packing_and_status_transitions_do_not_change_or_revoke_token(): void
    {
        $rawToken = $this->tokenService->createForOrder($this->order);

        foreach ([OrderStatus::PRODUKSI, OrderStatus::QC, OrderStatus::PACKING] as $status) {
            $this->order->update(['status' => $status]);
            $this->get("/track/{$rawToken}")->assertStatus(200);
            $this->assertNotNull($this->tokenService->findValidToken($rawToken));
        }
    }

    public function test_shipment_does_not_change_or_revoke_token(): void
    {
        $rawToken = $this->tokenService->createForOrder($this->order);

        $this->order->update(['status' => OrderStatus::DIKIRIM]);

        $this->get("/track/{$rawToken}")->assertStatus(200);
        $this->assertNotNull($this->tokenService->findValidToken($rawToken));
    }

    public function test_completed_order_tracking_remains_valid(): void
    {
        $rawToken = $this->tokenService->createForOrder($this->order);

        $this->order->update(['status' => OrderStatus::SELESAI]);

        $response = $this->get("/track/{$rawToken}");
        $response->assertStatus(200);
        $response->assertSee('Pesanan Selesai');
        $this->assertNotNull($this->tokenService->findValidToken($rawToken));
    }

    public function test_order_with_cancelled_invoice_remains_accessible_on_tracking(): void
    {
        $rawToken = $this->tokenService->createForOrder($this->order);

        $invoiceService = app(\App\Services\Sales\InvoiceService::class);
        $invoice = $invoiceService->createForOrder($this->order);
        $invoice->update(['status' => \App\Enums\InvoiceStatus::CANCELLED]);

        $response = $this->get("/track/{$rawToken}");
        $response->assertStatus(200);
        $this->assertNotNull($this->tokenService->findValidToken($rawToken));
    }

    public function test_partially_paid_packed_order_shows_packing_and_upcoming_shipment(): void
    {
        $rawToken = $this->tokenService->createForOrder($this->order);
        $this->order->update(['status' => OrderStatus::PACKING]);

        $response = $this->get("/track/{$rawToken}");
        $response->assertStatus(200);
        $response->assertSee('Pengemasan Produk');
        $response->assertSee('Sedang dalam tahapan ini');
        $response->assertSee('Pesanan Dikirim'); // Upcoming stage exists in list
    }
}
