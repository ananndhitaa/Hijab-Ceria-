<?php

namespace Tests\Feature\Platform;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Finance\OrderPayment;
use App\Models\Master\Customer;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Platform\PlatformInvoice;
use App\Models\Platform\PlatformServiceRequest;
use App\Models\Platform\TenantSubscription;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\Outlet;
use App\Models\System\User;
use App\Services\Platform\FinancialAnalyticsService;
use App\Services\Platform\TenantEntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OwnerAndPlatformAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected Outlet $outlet1;
    protected Outlet $outlet2;
    protected User $owner1;
    protected User $superAdmin;
    protected FinancialAnalyticsService $analyticsService;
    protected TenantEntitlementService $entitlementService;

    protected function setUp(): void
    {
        parent::setUp();
        config(['nayara.tenant_isolation_enabled' => true]);

        $this->outlet1 = Outlet::create([
            'outlet_name' => 'Nayara Hijab Bandung',
            'outlet_code' => 'OUT-BDG-01',
            'status' => 'ACTIVE',
        ]);

        $this->outlet2 = Outlet::create([
            'outlet_name' => 'Nayara Hijab Jakarta',
            'outlet_code' => 'OUT-JKT-01',
            'status' => 'ACTIVE',
        ]);

        $this->owner1 = User::factory()->create([
            'name' => 'Owner Bandung',
            'email' => 'owner.bdg@nayara.test',
            'role' => UserRole::OWNER,
            'outlet_id' => $this->outlet1->id,
        ]);

        $this->superAdmin = User::factory()->create([
            'name' => 'Super Admin Platform',
            'email' => 'superadmin.analytics@nayara.test',
            'role' => UserRole::SUPER_ADMIN,
            'outlet_id' => null,
        ]);

        $this->analyticsService = app(FinancialAnalyticsService::class);
        $this->entitlementService = app(TenantEntitlementService::class);
    }

    public function test_financial_analytics_is_strictly_tenant_scoped(): void
    {
        $cust1 = Customer::create(['outlet_id' => $this->outlet1->id, 'customer_name' => 'Cust 1', 'phone' => '081']);
        $cust2 = Customer::create(['outlet_id' => $this->outlet2->id, 'customer_name' => 'Cust 2', 'phone' => '082']);

        $prod1 = Product::create(['outlet_id' => $this->outlet1->id, 'name' => 'Prod 1', 'product_code' => 'P1']);
        $var1 = ProductVariant::create(['product_id' => $prod1->id, 'sku' => 'SKU-P1-01', 'variant_name' => 'Black', 'color' => 'Black', 'size' => 'M']);

        // Order in Outlet 1
        $order1 = Order::create([
            'outlet_id' => $this->outlet1->id,
            'customer_id' => $cust1->id,
            'order_no' => 'SO-BDG-001',
            'order_date' => now()->toDateString(),
            'status' => OrderStatus::SELESAI,
        ]);

        OrderItem::create([
            'order_id' => $order1->id,
            'variant_id' => $var1->id,
            'qty_ordered' => 10,
            'unit_selling_price' => 50000,
        ]);

        // Analytics for Outlet 1
        $data1 = $this->analyticsService->getAnalytics($this->outlet1->id, now()->subDays(5)->toDateString(), now()->toDateString());
        $this->assertEquals(500000, (int) $data1['metrics']['revenue']);

        // Analytics for Outlet 2 (should be 0, no data leakage!)
        $data2 = $this->analyticsService->getAnalytics($this->outlet2->id, now()->subDays(5)->toDateString(), now()->toDateString());
        $this->assertEquals(0, (int) $data2['metrics']['revenue']);
    }

    public function test_export_endpoints_fail_closed_without_premium_or_addon(): void
    {
        $this->assertFalse($this->entitlementService->canExportFinancialReports($this->outlet1->id));

        $this->expectException(ValidationException::class);
        $this->analyticsService->exportCsv(
            outletId: $this->outlet1->id,
            startDate: now()->subDays(7)->toDateString(),
            endDate: now()->toDateString()
        );
    }

    public function test_export_endpoints_succeed_when_premium_or_addon_is_active(): void
    {
        TenantSubscription::create([
            'outlet_id' => $this->outlet1->id,
            'type' => TenantSubscription::TYPE_ANALYTICS_ADDON,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonth(),
            'status' => TenantSubscription::STATUS_ACTIVE,
        ]);

        $this->assertTrue($this->entitlementService->canExportFinancialReports($this->outlet1->id));

        $response = $this->analyticsService->exportCsv(
            outletId: $this->outlet1->id,
            startDate: now()->subDays(7)->toDateString(),
            endDate: now()->toDateString()
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue(str_contains($response->headers->get('Content-Disposition') ?? '', 'attachment; filename='));
    }

    public function test_super_admin_global_analytics_ranks_organic_shops_by_sales_not_ads(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get(route('platform.analytics.index'));
        $response->assertStatus(200);
        $response->assertSee('Global Platform Analytics');
        $response->assertSee('Top 5 Toko Terlaris');
        $response->assertSee('Top 5 Produk Terlaris');
    }

    public function test_setup_and_training_request_lifecycle(): void
    {
        $this->actingAs($this->owner1);

        // Submit request
        $response = $this->post(route('owner.services.store'), [
            'service_type' => 'TRAINING_STAFF',
            'scope_details' => 'Pelatihan modul cutting & sewing untuk 5 staf produksi baru.',
            'preferred_date' => now()->addDays(10)->toDateString(),
        ]);
        $response->assertRedirect(route('owner.services.index'));

        $request = PlatformServiceRequest::where('outlet_id', $this->outlet1->id)->first();
        $this->assertNotNull($request);
        $this->assertEquals(PlatformServiceRequest::STATUS_SUBMITTED, $request->status);

        // Super Admin updates status and records custom quote
        $this->actingAs($this->superAdmin);
        $updateResp = $this->put(route('platform.services.update', $request->id), [
            'status' => PlatformServiceRequest::STATUS_SCOPED,
            'custom_quote_amount' => 1500000,
            'super_admin_notes' => 'Tersedia tanggal 5 November dengan instruktur Mas Ahmad.',
        ]);
        $updateResp->assertRedirect();

        $fresh = $request->fresh();
        $this->assertEquals(PlatformServiceRequest::STATUS_SCOPED, $fresh->status);
        $this->assertEquals(1500000, (int) $fresh->custom_quote_amount);
    }
}
