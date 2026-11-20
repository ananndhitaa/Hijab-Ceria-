<?php

namespace Tests\Feature\Owner;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Finance\OpeningCashBalance;
use App\Models\Master\Customer;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Platform\TenantSubscription;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\Outlet;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OwnerFreeTierDashboardAndAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private Outlet $outletFree;
    private Outlet $outletPremium;
    private Outlet $outletAddon;
    private Outlet $outletExpired;

    private User $ownerFree;
    private User $ownerPremium;
    private User $ownerAddon;
    private User $ownerExpired;

    protected function setUp(): void
    {
        parent::setUp();
        config(['nayara.tenant_isolation_enabled' => true]);

        // 1. Outlet Free
        $this->outletFree = Outlet::create([
            'outlet_name' => 'Konveksi Hijab Berkah (Free)',
            'outlet_code' => 'OUT-FREE-01',
            'status' => 'ACTIVE',
        ]);
        $this->ownerFree = User::factory()->create([
            'name' => 'Owner Free',
            'email' => 'owner.free@nayara.test',
            'role' => UserRole::OWNER,
            'status' => UserStatus::ACTIVE,
            'outlet_id' => $this->outletFree->id,
        ]);

        // 2. Outlet Premium
        $this->outletPremium = Outlet::create([
            'outlet_name' => 'Konveksi Hijab Makmur (Premium)',
            'outlet_code' => 'OUT-PREM-01',
            'status' => 'ACTIVE',
        ]);
        $this->ownerPremium = User::factory()->create([
            'name' => 'Owner Premium',
            'email' => 'owner.premium@nayara.test',
            'role' => UserRole::OWNER,
            'status' => UserStatus::ACTIVE,
            'outlet_id' => $this->outletPremium->id,
        ]);
        TenantSubscription::create([
            'outlet_id' => $this->outletPremium->id,
            'type' => TenantSubscription::TYPE_PREMIUM,
            'status' => TenantSubscription::STATUS_ACTIVE,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonth(),
        ]);

        // 3. Outlet Add-On
        $this->outletAddon = Outlet::create([
            'outlet_name' => 'Konveksi Hijab Jaya (Add-on)',
            'outlet_code' => 'OUT-ADDON-01',
            'status' => 'ACTIVE',
        ]);
        $this->ownerAddon = User::factory()->create([
            'name' => 'Owner Addon',
            'email' => 'owner.addon@nayara.test',
            'role' => UserRole::OWNER,
            'status' => UserStatus::ACTIVE,
            'outlet_id' => $this->outletAddon->id,
        ]);
        TenantSubscription::create([
            'outlet_id' => $this->outletAddon->id,
            'type' => TenantSubscription::TYPE_ANALYTICS_ADDON,
            'status' => TenantSubscription::STATUS_ACTIVE,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonth(),
        ]);

        // 4. Outlet Expired
        $this->outletExpired = Outlet::create([
            'outlet_name' => 'Konveksi Hijab Sentosa (Expired)',
            'outlet_code' => 'OUT-EXP-01',
            'status' => 'ACTIVE',
        ]);
        $this->ownerExpired = User::factory()->create([
            'name' => 'Owner Expired',
            'email' => 'owner.expired@nayara.test',
            'role' => UserRole::OWNER,
            'status' => UserStatus::ACTIVE,
            'outlet_id' => $this->outletExpired->id,
        ]);
        TenantSubscription::create([
            'outlet_id' => $this->outletExpired->id,
            'type' => TenantSubscription::TYPE_PREMIUM,
            'status' => TenantSubscription::STATUS_EXPIRED,
            'starts_at' => now()->subMonths(2),
            'expires_at' => now()->subDay(),
        ]);

        // Seed basic financial records for Free owner
        OpeningCashBalance::create([
            'outlet_id' => $this->outletFree->id,
            'opening_date' => now()->startOfMonth()->toDateString(),
            'amount' => '15000000.00',
            'notes' => 'Kas Awal Free',
            'created_by' => $this->ownerFree->id,
        ]);

        $cust = Customer::create([
            'outlet_id' => $this->outletFree->id,
            'customer_name' => 'Toko Busana Muslim',
            'phone' => '081234567890',
        ]);
        $prod = Product::create([
            'outlet_id' => $this->outletFree->id,
            'product_name' => 'Pashmina Silk',
        ]);
        $var = ProductVariant::create([
            'product_id' => $prod->id,
            'variant_name' => 'Mocca',
            'sku' => 'PSH-MCC-01',
            'base_selling_price' => 50000,
        ]);

        $order = Order::create([
            'outlet_id' => $this->outletFree->id,
            'customer_id' => $cust->id,
            'order_no' => 'SO-FREE-001',
            'order_date' => now()->toDateString(),
            'status' => OrderStatus::SELESAI,
            'total_qty' => 20,
            'total_amount' => 1000000,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'variant_id' => $var->id,
            'qty_ordered' => 20,
            'unit_selling_price' => 50000,
            'subtotal' => 1000000,
        ]);

        // Seed data for Premium owner
        $custPrem = Customer::create([
            'outlet_id' => $this->outletPremium->id,
            'customer_name' => 'Toko Busana Premium',
            'phone' => '081234567899',
        ]);
        $prodPrem = Product::create([
            'outlet_id' => $this->outletPremium->id,
            'product_name' => 'Hijab Segiempat Premium',
        ]);
        $varPrem = ProductVariant::create([
            'product_id' => $prodPrem->id,
            'variant_name' => 'Navy',
            'sku' => 'HJB-NVY-PRM',
            'base_selling_price' => 75000,
        ]);
        $orderPrem = Order::create([
            'outlet_id' => $this->outletPremium->id,
            'customer_id' => $custPrem->id,
            'order_no' => 'SO-PREM-001',
            'order_date' => now()->toDateString(),
            'status' => OrderStatus::SELESAI,
            'total_qty' => 10,
            'total_amount' => 750000,
        ]);
        OrderItem::create([
            'order_id' => $orderPrem->id,
            'variant_id' => $varPrem->id,
            'qty_ordered' => 10,
            'unit_selling_price' => 75000,
            'subtotal' => 750000,
        ]);
        \App\Models\Finance\BusinessExpense::create([
            'outlet_id' => $this->outletPremium->id,
            'expense_date' => now()->toDateString(),
            'expense_type' => \App\Enums\ExpenseType::OPERATING,
            'description' => 'Listrik & Internet',
            'amount' => 150000,
            'created_by' => $this->ownerPremium->id,
        ]);
    }

    /**
     * Skenario 1.A: Owner FREE dashboard tidak menampilkan grafik chart lanjutan,
     * tetapi menampilkan CTA upgrade dan Basic Finance tetap utuh.
     */
    public function test_free_owner_dashboard_hides_charts_and_shows_upgrade_cta(): void
    {
        $response = $this->actingAs($this->ownerFree)->get(route('owner.dashboard'));

        $response->assertOk();

        // 1. Chart canvas container TIDAK ADA pada dashboard Free
        $response->assertDontSee('id="revenueTrendChart"', false);
        $response->assertDontSee('id="profitComparisonChart"', false);
        $response->assertDontSee('id="expenseCompositionChart"', false);
        $response->assertDontSee('id="topProductsChart"', false);
        $response->assertDontSee('id="topCustomersChart"', false);

        // 2. CTA Banner upgrade ditampilkan
        $response->assertSee('Buka Grafik Analisis Penjualan &amp; Tren Bisnis Lanjutan', false);
        $response->assertSee('Fitur Premium');
        $response->assertSee('Pratinjau Analitik');
        $response->assertSee('Upgrade Sekarang');

        // 3. Basic Finance tetap ada dan lengkap
        $response->assertSee('Ringkasan Keuangan');
        $response->assertSee('Kas Saat Ini');
        $response->assertSee('Pendapatan');
        $response->assertSee('HPP Tercatat');
        $response->assertSee('Laba Kotor');
        $response->assertSee('Laba Bersih');
        $response->assertSee('Indikator Kinerja');
        $response->assertSee('Gross Margin');
        $response->assertSee('Net Margin');
        $response->assertSee('ROI');
        $response->assertSee('Penerimaan Kas');
        $response->assertSee('Ringkasan Operasional');
    }

    /**
     * Skenario 1.B: Owner FREE mengakses /owner/analytics melihat Lock Screen / Upgrade Teaser.
     */
    public function test_free_owner_visiting_analytics_sees_lock_screen(): void
    {
        $response = $this->actingAs($this->ownerFree)->get(route('owner.analytics.index'));

        $response->assertOk();

        // Lock screen headline & benefits
        $response->assertSee('Analisis Bisnis Lebih Mendalam');
        $response->assertSee('Bandingkan performa usaha dan evaluasi kondisi keuangan antarperiode.');
        $response->assertSee('MODUL EKSKLUSIF TINGKAT EKSEKUTIF');
        $response->assertSee('Tren Omzet &amp; Margin', false);
        $response->assertSee('Komposisi Beban Usaha');
        $response->assertSee('Evaluasi Antar-Periode');
        $response->assertSee('Produk &amp; Pelanggan Utama', false);
        $response->assertSee('Ekspor PDF &amp; CSV/Excel', false);

        // CTA buttons
        $response->assertSee('Upgrade Premium');
        $response->assertSee('Beli Add-On Financial Analytics');

        // Teaser preview
        $response->assertSee('Pratinjau Tampilan Visual');
        $response->assertSee('Grafik Interaktif &amp; Ekspor Terkunci', false);

        // Chart canvases should NOT be rendered for Free
        $response->assertDontSee('id="revenueTrendChart"', false);
    }

    /**
     * Skenario 1.C: Request langsung ke endpoint ekspor PDF/CSV ditolak untuk akun FREE.
     */
    public function test_free_owner_export_endpoints_fail_closed(): void
    {
        // 1. Export CSV
        try {
            $responseCsv = $this->actingAs($this->ownerFree)->get(route('owner.analytics.export.csv'));
            // If redirected back with session errors
            $responseCsv->assertSessionHasErrors('entitlement');
        } catch (ValidationException $e) {
            $this->assertTrue(isset($e->errors()['entitlement']));
        }

        // 2. Export PDF
        try {
            $responsePdf = $this->actingAs($this->ownerFree)->get(route('owner.analytics.export.pdf'));
            $responsePdf->assertSessionHasErrors('entitlement');
        } catch (ValidationException $e) {
            $this->assertTrue(isset($e->errors()['entitlement']));
        }
    }

    /**
     * Skenario 2: Akun Owner dengan paket PREMIUM dapat melihat visualisasi penuh
     * dan dapat mengunduh CSV & PDF.
     */
    public function test_premium_owner_has_full_financial_analytics_and_export_access(): void
    {
        // 1. Analytics page displays full charts and export buttons
        $response = $this->actingAs($this->ownerPremium)->get(route('owner.analytics.index'));
        $response->assertOk();

        $response->assertSee('id="revenueTrendChart"', false);
        $response->assertSee('id="profitComparisonChart"', false);
        $response->assertSee('id="expenseCompositionChart"', false);
        $response->assertSee('id="topProductsChart"', false);
        $response->assertSee('id="topCustomersChart"', false);
        $response->assertSee('Unduh CSV/Excel');
        $response->assertSee('Cetak Laporan PDF');
        $response->assertDontSee('Analisis Bisnis Lebih Mendalam');

        // 2. Export CSV succeeds
        $csvResponse = $this->actingAs($this->ownerPremium)->get(route('owner.analytics.export.csv'));
        $csvResponse->assertOk();
        $this->assertStringContainsString('attachment; filename=', $csvResponse->headers->get('Content-Disposition') ?? '');

        // 3. Export PDF succeeds
        $pdfResponse = $this->actingAs($this->ownerPremium)->get(route('owner.analytics.export.pdf'));
        $pdfResponse->assertOk();
    }

    /**
     * Skenario 3: Akun Owner dengan ADD-ON Financial Analytics memiliki hak akses yang sama dengan Premium.
     */
    public function test_addon_owner_has_full_financial_analytics_and_export_access(): void
    {
        // 1. Analytics page displays full charts and export buttons
        $response = $this->actingAs($this->ownerAddon)->get(route('owner.analytics.index'));
        $response->assertOk();

        $response->assertSee('id="revenueTrendChart"', false);
        $response->assertSee('id="profitComparisonChart"', false);
        $response->assertSee('Unduh CSV/Excel');
        $response->assertSee('Cetak Laporan PDF');
        $response->assertDontSee('Analisis Bisnis Lebih Mendalam');

        // 2. Export CSV succeeds
        $csvResponse = $this->actingAs($this->ownerAddon)->get(route('owner.analytics.export.csv'));
        $csvResponse->assertOk();

        // 3. Export PDF succeeds
        $pdfResponse = $this->actingAs($this->ownerAddon)->get(route('owner.analytics.export.pdf'));
        $pdfResponse->assertOk();
    }

    /**
     * Skenario 4: Akun Owner yang masa langganannya habis (expired) otomatis terkunci kembali seperti Free.
     */
    public function test_expired_subscription_automatically_reverts_to_locked(): void
    {
        // 1. Dashboard hides charts and shows CTA
        $dashResponse = $this->actingAs($this->ownerExpired)->get(route('owner.dashboard'));
        $dashResponse->assertOk();
        $dashResponse->assertDontSee('id="revenueTrendChart"', false);
        $dashResponse->assertSee('Buka Grafik Analisis Penjualan &amp; Tren Bisnis Lanjutan', false);

        // 2. Analytics page shows lock screen
        $analyticsResponse = $this->actingAs($this->ownerExpired)->get(route('owner.analytics.index'));
        $analyticsResponse->assertOk();
        $analyticsResponse->assertSee('Analisis Bisnis Lebih Mendalam');
        $analyticsResponse->assertDontSee('id="revenueTrendChart"', false);

        // 3. Export CSV is blocked
        try {
            $csvResponse = $this->actingAs($this->ownerExpired)->get(route('owner.analytics.export.csv'));
            $csvResponse->assertSessionHasErrors('entitlement');
        } catch (ValidationException $e) {
            $this->assertTrue(isset($e->errors()['entitlement']));
        }
    }
}
