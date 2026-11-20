<?php

namespace Tests\Feature\Platform;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Platform\PlatformInvoice;
use App\Models\System\Outlet;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlatformBillingUiTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected Outlet $outlet;

    protected function setUp(): void
    {
        parent::setUp();

        config(['nayara.tenant_isolation_enabled' => false]);

        $this->superAdmin = User::create([
            'name'      => 'Platform Super Admin',
            'email'     => 'superadmin@nayara.test',
            'password'  => Hash::make('Secret123!'),
            'role'      => UserRole::SUPER_ADMIN->value,
            'status'    => UserStatus::ACTIVE->value,
            'outlet_id' => null,
        ]);

        $this->outlet = Outlet::create([
            'outlet_name' => 'NAYARA Hijab Pusat',
            'outlet_code' => 'OUT-NAYARA-PUSAT',
            'slug'        => 'nayara-hijab-pusat',
            'status'      => 'ACTIVE',
        ]);
    }

    /**
     * Test 1: Super admin can access billing index and sees official headers and KPIs.
     */
    public function test_super_admin_can_access_billing_directory(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('platform.billing.index'));

        $response->assertStatus(200);
        $response->assertSee('Verifikasi Pembayaran Platform');
        $response->assertSee('Monetisasi');
        $response->assertSee('Tagihan Platform');
        $response->assertSee('MENUNGGU VERIFIKASI');
        $response->assertSee('PEMBAYARAN TERVERIFIKASI');
        $response->assertSee('BELUM DIBAYAR');
        $response->assertSee('TOTAL DANA MASUK SAH');
    }

    /**
     * Test 2: Non-Super Admin roles receive 403 Forbidden.
     */
    public function test_non_super_admin_roles_receive_forbidden_on_billing(): void
    {
        $staffUser = User::create([
            'name'      => 'Gudang Staff',
            'email'     => 'gudang@tenant.test',
            'password'  => Hash::make('password123'),
            'role'      => UserRole::GUDANG->value,
            'status'    => UserStatus::ACTIVE->value,
            'outlet_id' => $this->outlet->id,
        ]);

        $response = $this->actingAs($staffUser)->get(route('platform.billing.index'));
        $response->assertStatus(403);
    }

    /**
     * Test 3: Empty state is rendered when no invoices exist.
     */
    public function test_empty_state_rendered_when_no_invoices(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('platform.billing.index'));

        $response->assertStatus(200);
        $response->assertSee('Tidak ada tagihan yang ditemukan.');
        $response->assertSee('Belum ada pembayaran yang perlu diverifikasi.');
    }

    /**
     * Test 4: Real database counts for KPI cards and status tabs.
     */
    public function test_billing_kpi_cards_and_tabs_reflect_database_counts(): void
    {
        PlatformInvoice::create([
            'invoice_no'   => 'INV-2026-001',
            'outlet_id'    => $this->outlet->id,
            'service_type' => PlatformInvoice::SERVICE_PREMIUM,
            'amount'       => 569000,
            'status'       => PlatformInvoice::STATUS_PAYMENT_SUBMITTED,
            'expires_at'   => now()->addDays(7),
        ]);

        PlatformInvoice::create([
            'invoice_no'   => 'INV-2026-002',
            'outlet_id'    => $this->outlet->id,
            'service_type' => PlatformInvoice::SERVICE_SHOP_AD,
            'amount'       => 250000,
            'status'       => PlatformInvoice::STATUS_VERIFIED,
            'expires_at'   => now()->addDays(7),
        ]);

        PlatformInvoice::create([
            'invoice_no'   => 'INV-2026-003',
            'outlet_id'    => $this->outlet->id,
            'service_type' => PlatformInvoice::SERVICE_ANALYTICS_ADDON,
            'amount'       => 129000,
            'status'       => PlatformInvoice::STATUS_UNPAID,
            'expires_at'   => now()->addDays(7),
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('platform.billing.index'));

        $response->assertStatus(200);
        $response->assertSee('Rp 250.000'); // Verified sum
        $response->assertSee('INV-2026-001');
        $response->assertSee('INV-2026-002');
        $response->assertSee('INV-2026-003');
    }

    /**
     * Test 5: Status tab filtering.
     */
    public function test_status_tab_filtering(): void
    {
        PlatformInvoice::create([
            'invoice_no'   => 'INV-SUBMITTED-01',
            'outlet_id'    => $this->outlet->id,
            'service_type' => PlatformInvoice::SERVICE_PREMIUM,
            'amount'       => 100000,
            'status'       => PlatformInvoice::STATUS_PAYMENT_SUBMITTED,
            'expires_at'   => now()->addDays(7),
        ]);

        PlatformInvoice::create([
            'invoice_no'   => 'INV-VERIFIED-02',
            'outlet_id'    => $this->outlet->id,
            'service_type' => PlatformInvoice::SERVICE_PREMIUM,
            'amount'       => 200000,
            'status'       => PlatformInvoice::STATUS_VERIFIED,
            'expires_at'   => now()->addDays(7),
        ]);

        // Filter: PAYMENT_SUBMITTED
        $resSubmitted = $this->actingAs($this->superAdmin)->get(route('platform.billing.index', ['status' => 'PAYMENT_SUBMITTED']));
        $resSubmitted->assertStatus(200);
        $resSubmitted->assertSee('INV-SUBMITTED-01');
        $resSubmitted->assertDontSee('INV-VERIFIED-02');

        // Filter: VERIFIED
        $resVerified = $this->actingAs($this->superAdmin)->get(route('platform.billing.index', ['status' => 'VERIFIED']));
        $resVerified->assertStatus(200);
        $resVerified->assertSee('INV-VERIFIED-02');
        $resVerified->assertDontSee('INV-SUBMITTED-01');
    }

    /**
     * Test 6: Search filtering by invoice number and amount.
     */
    public function test_search_filtering(): void
    {
        PlatformInvoice::create([
            'invoice_no'   => 'INV-ALPHA-999',
            'outlet_id'    => $this->outlet->id,
            'service_type' => PlatformInvoice::SERVICE_PREMIUM,
            'amount'       => 750000,
            'status'       => PlatformInvoice::STATUS_PAYMENT_SUBMITTED,
            'expires_at'   => now()->addDays(7),
        ]);

        PlatformInvoice::create([
            'invoice_no'   => 'INV-BETA-888',
            'outlet_id'    => $this->outlet->id,
            'service_type' => PlatformInvoice::SERVICE_LISTING_SLOTS,
            'amount'       => 89000,
            'status'       => PlatformInvoice::STATUS_UNPAID,
            'expires_at'   => now()->addDays(7),
        ]);

        $resSearch = $this->actingAs($this->superAdmin)->get(route('platform.billing.index', ['search' => 'ALPHA']));
        $resSearch->assertStatus(200);
        $resSearch->assertSee('INV-ALPHA-999');
        $resSearch->assertDontSee('INV-BETA-888');
    }

    /**
     * Test 7: Sorting dropdown functions cleanly without SQL errors.
     */
    public function test_sorting_dropdown(): void
    {
        PlatformInvoice::create([
            'invoice_no'   => 'INV-LOW',
            'outlet_id'    => $this->outlet->id,
            'service_type' => PlatformInvoice::SERVICE_LISTING_SLOTS,
            'amount'       => 50000,
            'status'       => PlatformInvoice::STATUS_UNPAID,
            'expires_at'   => now()->addDays(7),
        ]);

        PlatformInvoice::create([
            'invoice_no'   => 'INV-HIGH',
            'outlet_id'    => $this->outlet->id,
            'service_type' => PlatformInvoice::SERVICE_PREMIUM,
            'amount'       => 900000,
            'status'       => PlatformInvoice::STATUS_UNPAID,
            'expires_at'   => now()->addDays(7),
        ]);

        $resSortDesc = $this->actingAs($this->superAdmin)->get(route('platform.billing.index', ['sort' => 'amount_desc']));
        $resSortDesc->assertStatus(200);
        $resSortDesc->assertSeeInOrder(['INV-HIGH', 'INV-LOW']);

        $resSortAsc = $this->actingAs($this->superAdmin)->get(route('platform.billing.index', ['sort' => 'amount_asc']));
        $resSortAsc->assertStatus(200);
        $resSortAsc->assertSeeInOrder(['INV-LOW', 'INV-HIGH']);
    }

    /**
     * Test 8: Sidebar single active state on billing route.
     */
    public function test_single_active_navigation_item_on_billing_page(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('platform.billing.index'));

        $response->assertStatus(200);
        // Tagihan link should be active
        $response->assertSee('href="' . route('platform.billing.index') . '" class="n-nav-link is-active"', false);
        // Dashboard link should NOT be active
        $response->assertDontSee('href="' . route('platform.dashboard') . '" class="n-nav-link is-active"', false);
    }
}
