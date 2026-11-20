<?php

namespace Tests\Feature\System;

use App\Enums\BatchStatus;
use App\Enums\ExpenseType;
use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\PurchaseStatus;
use App\Enums\ShipmentStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Finance\BusinessExpense;
use App\Models\Inventory\MaterialStock;
use App\Models\Inventory\Purchase;
use App\Models\Inventory\PurchaseItem;
use App\Models\Master\BomTemplate;
use App\Models\Master\Customer;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Master\Supplier;
use App\Models\Master\Unit;
use App\Models\Platform\AdCampaign;
use App\Models\Platform\OwnerComplaint;
use App\Models\Platform\PlatformInvoice;
use App\Models\Platform\PlatformRefund;
use App\Models\Platform\PlatformServiceRequest;
use App\Models\Platform\TenantRegistration;
use App\Models\Production\ProductionBatch;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\Outlet;
use App\Models\System\Shipment;
use App\Models\System\User;
use App\Services\Tracking\TrackingTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WholeSiteHealthCheckTest extends TestCase
{
    use RefreshDatabase;

    protected Outlet $outlet;
    protected User $superAdmin;
    protected User $owner;
    protected User $admin;
    protected User $gudang;
    protected User $produksi;
    protected User $customerUser;
    protected Customer $customer;
    protected Product $product;
    protected ProductVariant $variant;
    protected Order $order;
    protected Invoice $invoice;
    protected ProductionBatch $batch;
    protected Material $material;
    protected Supplier $supplier;
    protected Unit $unit;
    protected Purchase $purchase;
    protected Shipment $shipment;
    protected PlatformInvoice $platformInvoice;
    protected AdCampaign $adCampaign;
    protected PlatformRefund $refund;
    protected PlatformServiceRequest $serviceRequest;
    protected OwnerComplaint $complaint;
    protected TenantRegistration $registration;
    protected BomTemplate $bomTemplate;
    protected BusinessExpense $expense;

    protected function setUp(): void
    {
        parent::setUp();

        config(['nayara.tenant_isolation_enabled' => false]);

        // Outlet
        $this->outlet = Outlet::create([
            'outlet_name' => 'Nayara Official Outlet',
            'outlet_code' => 'OUT-TEST-01',
            'phone_wa'    => '081234567890',
            'status'      => 'ACTIVE',
        ]);

        // Users
        $this->superAdmin = User::factory()->create([
            'name'      => 'Super Admin',
            'email'     => 'superadmin@nayara.test',
            'role'      => UserRole::SUPER_ADMIN,
            'outlet_id' => null,
            'status'    => UserStatus::ACTIVE,
        ]);

        $this->owner = User::factory()->create([
            'name'      => 'Owner Toko',
            'email'     => 'owner@nayara.test',
            'role'      => UserRole::OWNER,
            'outlet_id' => $this->outlet->id,
            'status'    => UserStatus::ACTIVE,
        ]);

        $this->admin = User::factory()->create([
            'name'      => 'Admin Operasional',
            'email'     => 'admin@nayara.test',
            'role'      => UserRole::ADMIN,
            'outlet_id' => $this->outlet->id,
            'status'    => UserStatus::ACTIVE,
        ]);

        $this->gudang = User::factory()->create([
            'name'      => 'Staf Gudang',
            'email'     => 'gudang@nayara.test',
            'role'      => UserRole::GUDANG,
            'outlet_id' => $this->outlet->id,
            'status'    => UserStatus::ACTIVE,
        ]);

        $this->produksi = User::factory()->create([
            'name'      => 'Staf Produksi',
            'email'     => 'produksi@nayara.test',
            'role'      => UserRole::PRODUKSI,
            'outlet_id' => $this->outlet->id,
            'status'    => UserStatus::ACTIVE,
        ]);

        $this->customerUser = User::factory()->create([
            'name'      => 'Customer Toko',
            'email'     => 'customer@nayara.test',
            'role'      => UserRole::CUSTOMER,
            'outlet_id' => $this->outlet->id,
            'status'    => UserStatus::ACTIVE,
        ]);

        // Customer Profile
        $this->customer = Customer::create([
            'user_id'       => $this->customerUser->id,
            'outlet_id'     => $this->outlet->id,
            'customer_name' => 'Toko Raisya Hijab',
            'phone'         => '081234567891',
            'address'       => 'Jl. Sukajadi No. 12 Bandung',
            'email'         => 'customer@nayara.test',
            'active'        => true,
        ]);

        // Master Data
        $this->unit = Unit::create([
            'unit_name' => 'Meter',
            'symbol'    => 'm',
        ]);

        $this->material = Material::create([
            'outlet_id'      => $this->outlet->id,
            'material_name'  => 'Kain Voal Ultrafine',
            'unit_id'        => $this->unit->id,
            'active'         => true,
        ]);

        MaterialStock::create([
            'material_id'        => $this->material->id,
            'total_stock'        => 500,
            'allocated_stock'    => 0,
            'avg_cost_per_unit'  => 15000,
            'active'             => true,
        ]);

        $this->supplier = Supplier::create([
            'outlet_id'     => $this->outlet->id,
            'supplier_name' => 'Supplier Kain Mandiri',
            'phone'         => '081987654321',
            'active'        => true,
        ]);

        $this->product = Product::create([
            'outlet_id'     => $this->outlet->id,
            'product_name'  => 'Hijab Voal Polos Premium',
            'category'      => 'Hijab Polos',
            'active'        => true,
        ]);

        $this->variant = ProductVariant::create([
            'outlet_id'          => $this->outlet->id,
            'product_id'         => $this->product->id,
            'variant_name'       => 'Mauve Dream',
            'sku'                => 'HJB-VOAL-MV',
            'base_selling_price' => 75000,
            'active'             => true,
        ]);

        $this->bomTemplate = BomTemplate::create([
            'outlet_id'   => $this->outlet->id,
            'product_id'  => $this->product->id,
            'name'        => 'Template Standar Voal',
            'is_active'   => true,
        ]);

        // Sales Order & Items
        $this->order = Order::create([
            'outlet_id'       => $this->outlet->id,
            'customer_id'     => $this->customer->id,
            'order_no'        => 'SO-202611-0001',
            'order_date'      => now(),
            'status'          => OrderStatus::DEAL,
        ]);

        $orderItem = OrderItem::create([
            'order_id'           => $this->order->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => 20,
            'unit_selling_price' => 75000,
        ]);

        // Invoice
        $this->invoice = Invoice::create([
            'order_id'       => $this->order->id,
            'invoice_no'     => 'INV-202611-0001',
            'invoice_date'   => now(),
            'total_amount'   => 1500000,
            'status'         => InvoiceStatus::UNPAID,
        ]);

        // Production Batch
        $this->batch = ProductionBatch::create([
            'order_item_id' => $orderItem->id,
            'batch_no'      => 'BATCH-202611-0001',
            'target_qty'    => 20,
            'status'        => BatchStatus::BAHAN,
        ]);

        // Purchase Order
        $this->purchase = Purchase::create([
            'outlet_id'     => $this->outlet->id,
            'supplier_id'   => $this->supplier->id,
            'purchase_no'   => 'PO-202611-0001',
            'purchase_date' => now(),
            'status'        => PurchaseStatus::DRAFT,
        ]);

        PurchaseItem::create([
            'purchase_id' => $this->purchase->id,
            'material_id' => $this->material->id,
            'qty'         => 100,
            'unit_price'  => 15000,
        ]);

        // Expense
        $this->expense = BusinessExpense::create([
            'outlet_id'        => $this->outlet->id,
            'expense_type'     => ExpenseType::OPERATING,
            'amount'           => 100000,
            'description'      => 'Biaya Listrik Kantor',
            'expense_date'     => now(),
        ]);

        // Shipment
        $this->shipment = Shipment::create([
            'order_id'    => $this->order->id,
            'tracking_no' => 'JNE123456789',
            'courier'     => 'JNE',
            'status'      => ShipmentStatus::PENDING,
        ]);

        // Platform Invoice
        $this->platformInvoice = PlatformInvoice::create([
            'outlet_id'               => $this->outlet->id,
            'invoice_no'              => 'INV-PLT-202611-TEST',
            'service_type'            => PlatformInvoice::SERVICE_PREMIUM,
            'package_duration_months' => 3,
            'amount'                  => 169000,
            'status'                  => PlatformInvoice::STATUS_UNPAID,
            'expires_at'              => now()->addDay(),
        ]);

        // Platform Ad Campaign
        $this->adCampaign = AdCampaign::create([
            'outlet_id'     => $this->outlet->id,
            'created_by'    => $this->owner->id,
            'ad_type'       => AdCampaign::TYPE_SHOP_AD,
            'title'         => 'Promo Akhir Tahun',
            'tagline'       => 'Diskon Heboh',
            'duration_days' => 3,
            'total_budget'  => 150000,
            'status'        => AdCampaign::STATUS_DRAFT,
        ]);

        // Platform Refund
        $this->refund = PlatformRefund::create([
            'outlet_id'                  => $this->outlet->id,
            'platform_invoice_id'        => $this->platformInvoice->id,
            'refund_type'                => PlatformRefund::TYPE_FULL_100_PERCENT,
            'amount'                     => 50000,
            'reason'                     => 'Pembatalan layanan',
            'status'                     => PlatformRefund::STATUS_REQUESTED,
            'destination_bank_name'      => 'BCA',
            'destination_account_number' => '1234567890',
            'destination_account_name'   => 'Owner Solo',
        ]);

        // Platform Service Request
        $this->serviceRequest = PlatformServiceRequest::create([
            'outlet_id'     => $this->outlet->id,
            'created_by'    => $this->owner->id,
            'service_type'  => PlatformServiceRequest::TYPE_SETUP_ONBOARDING,
            'scope_details' => 'Pelatihan sistem produksi',
            'status'        => PlatformServiceRequest::STATUS_SUBMITTED,
        ]);

        // Platform Complaint
        $this->complaint = OwnerComplaint::create([
            'complaint_code' => 'CMP-202611-0001',
            'outlet_id'      => $this->outlet->id,
            'user_id'        => $this->owner->id,
            'category'       => OwnerComplaint::CATEGORY_ADS_PROMOTION,
            'title'          => 'Pertanyaan Fitur Iklan',
            'description'    => 'Bagaimana cara menjadwalkan ulang iklan?',
            'status'         => OwnerComplaint::STATUS_NEW,
            'priority'       => OwnerComplaint::PRIORITY_NORMAL,
        ]);

        // Tenant Registration
        $this->registration = TenantRegistration::create([
            'company_name' => 'Hijab Baru Bersama',
            'owner_name'   => 'Siti Aminah',
            'email'        => 'siti@example.com',
            'password'     => bcrypt('password'),
            'phone_wa'     => '081234567888',
            'address'      => 'Bandung',
            'status'       => TenantRegistration::STATUS_PENDING,
        ]);
    }

    public function test_all_public_and_guest_pages_render_without_500(): void
    {
        $urls = [
            '/',
            '/login',
            '/forgot-password',
            '/storefront',
            '/storefront/shops',
            "/storefront/shop/{$this->outlet->id}",
            "/storefront/product/{$this->product->id}",
            '/track',
            '/register-tenant',
            '/register-tenant/status',
            '/register-tenant/success',
        ];

        foreach ($urls as $url) {
            $response = $this->get($url);
            if (! in_array($response->getStatusCode(), [200, 302], true)) {
                echo "[PUBLIC FAIL: $url => {$response->getStatusCode()}]\n";
            }
            $this->assertNotEquals(500, $response->getStatusCode(), "Public route {$url} returned HTTP 500");
        }
    }

    public function test_all_customer_pages_render_without_500(): void
    {
        $urls = [
            '/customer/dashboard',
            '/customer/orders',
            "/customer/orders/{$this->order->id}",
            '/customer/invoices',
        ];

        foreach ($urls as $url) {
            $response = $this->actingAs($this->customerUser)->get($url);
            if (! in_array($response->getStatusCode(), [200, 302], true)) {
                echo "[CUSTOMER FAIL: $url => {$response->getStatusCode()}]\n";
            }
            $this->assertNotEquals(500, $response->getStatusCode(), "Customer route {$url} returned HTTP 500");
        }
    }

    public function test_all_owner_pages_render_without_500(): void
    {
        $urls = [
            '/owner/dashboard',
            '/owner/opening-cash',
            '/owner/hpp',
            "/owner/hpp/batches/{$this->batch->id}",
            "/owner/hpp/orders/{$this->order->id}",
            '/owner/profitability',
            "/owner/profitability/orders/{$this->order->id}",
            '/owner/performance',
            '/owner/financial-summary',
            '/owner/employees',
            '/owner/employees/create',
            "/owner/employees/{$this->admin->id}",
            "/owner/employees/{$this->admin->id}/edit",
            '/owner/employee-performance',
            "/owner/employee-performance/admins/{$this->admin->id}",
            "/owner/employee-performance/production/{$this->produksi->id}",
            '/owner/employee-performance/unassigned-batches',
            '/owner/activity-logs',
            '/owner/publications',
            '/owner/subscriptions',
            "/owner/subscriptions/invoice/{$this->platformInvoice->id}",
            '/owner/ads',
            '/owner/ads/create',
            "/owner/ads/{$this->adCampaign->id}",
            '/owner/analytics',
            '/owner/analytics/export/csv',
            '/owner/services',
            '/owner/services/create',
            '/owner/notifications',
            '/owner/complaints',
            '/owner/complaints/create',
            "/owner/complaints/{$this->complaint->id}",
        ];

        foreach ($urls as $url) {
            $response = $this->actingAs($this->owner)->get($url);
            if (! in_array($response->getStatusCode(), [200, 302], true)) {
                echo "[OWNER FAIL: $url => {$response->getStatusCode()}]\n";
            }
            $this->assertNotEquals(500, $response->getStatusCode(), "Owner route {$url} returned HTTP 500");
        }
    }

    public function test_all_admin_pages_render_without_500(): void
    {
        $urls = [
            '/admin/dashboard',
            '/admin/customers',
            '/admin/customers/create',
            "/admin/customers/{$this->customer->id}",
            "/admin/customers/{$this->customer->id}/edit",
            '/admin/orders',
            '/admin/orders/create',
            "/admin/orders/{$this->order->id}",
            "/admin/orders/{$this->order->id}/edit",
            "/admin/orders/{$this->order->id}/batches/create",
            '/admin/batches',
            "/admin/batches/{$this->batch->id}",
            '/admin/kanban',
            '/admin/financial-records',
            '/admin/opening-cash/edit',
            '/admin/purchases/approvals',
            "/admin/purchases/approvals/{$this->purchase->id}",
            '/admin/purchases/payments',
            "/admin/purchases/{$this->purchase->id}/payments/create",
            '/admin/invoices',
            "/admin/invoices/{$this->invoice->id}",
            "/admin/invoices/{$this->invoice->id}/export-csv",
            '/admin/reports/sales',
            '/admin/reports/sales/export-csv',
            '/admin/incomes/create',
            '/admin/expenses',
            '/admin/expenses/create',
            "/admin/expenses/{$this->expense->id}",
            "/admin/expenses/{$this->expense->id}/edit",
            '/admin/products',
            '/admin/products/create',
            "/admin/products/{$this->product->id}",
            "/admin/products/{$this->product->id}/edit",
            "/admin/variants/{$this->variant->id}",
            "/admin/variants/{$this->variant->id}/edit",
            '/admin/bom-templates',
            '/admin/bom-templates/create',
            "/admin/bom-templates/{$this->bomTemplate->id}",
            "/admin/bom-templates/{$this->bomTemplate->id}/edit",
        ];

        foreach ($urls as $url) {
            $response = $this->actingAs($this->admin)->get($url);
            if (! in_array($response->getStatusCode(), [200, 302], true)) {
                echo "[ADMIN FAIL: $url => {$response->getStatusCode()}]\n";
            }
            $this->assertNotEquals(500, $response->getStatusCode(), "Admin route {$url} returned HTTP 500");
        }
    }

    public function test_all_gudang_pages_render_without_500(): void
    {
        $urls = [
            '/gudang/dashboard',
            '/gudang/units',
            '/gudang/units/create',
            "/gudang/units/{$this->unit->id}/edit",
            '/gudang/materials',
            '/gudang/materials/create',
            "/gudang/materials/{$this->material->id}",
            "/gudang/materials/{$this->material->id}/edit",
            "/gudang/materials/{$this->material->id}/stock-opname",
            '/gudang/material-planning',
            "/gudang/material-planning/{$this->batch->id}",
            '/gudang/stock-movements',
            '/gudang/suppliers',
            '/gudang/suppliers/create',
            "/gudang/suppliers/{$this->supplier->id}",
            "/gudang/suppliers/{$this->supplier->id}/edit",
            '/gudang/purchases',
            '/gudang/purchases/create',
            '/gudang/purchases/history',
            "/gudang/purchases/{$this->purchase->id}",
            "/gudang/purchases/{$this->purchase->id}/edit",
        ];

        foreach ($urls as $url) {
            $response = $this->actingAs($this->gudang)->get($url);
            if (! in_array($response->getStatusCode(), [200, 302], true)) {
                echo "[GUDANG FAIL: $url => {$response->getStatusCode()}]\n";
            }
            $this->assertNotEquals(500, $response->getStatusCode(), "Gudang route {$url} returned HTTP 500");
        }
    }

    public function test_all_produksi_pages_render_without_500(): void
    {
        $urls = [
            '/produksi/dashboard',
            '/produksi/batches',
            '/produksi/history',
            "/produksi/batches/{$this->batch->id}",
            "/produksi/batches/{$this->batch->id}/production-result/create",
            "/produksi/batches/{$this->batch->id}/qc/create",
            '/produksi/shipments',
            '/produksi/shipments/create',
            "/produksi/shipments/{$this->shipment->id}",
        ];

        foreach ($urls as $url) {
            $response = $this->actingAs($this->produksi)->get($url);
            if (! in_array($response->getStatusCode(), [200, 302], true)) {
                echo "[PRODUKSI FAIL: $url => {$response->getStatusCode()}]\n";
            }
            $this->assertNotEquals(500, $response->getStatusCode(), "Produksi route {$url} returned HTTP 500");
        }
    }

    public function test_all_platform_super_admin_pages_render_without_500(): void
    {
        $urls = [
            '/platform/dashboard',
            '/platform/registrations',
            "/platform/registrations/{$this->registration->id}",
            '/platform/tenants',
            "/platform/tenants/{$this->outlet->id}",
            '/platform/billing',
            "/platform/billing/{$this->platformInvoice->id}",
            '/platform/ads',
            "/platform/ads/{$this->adCampaign->id}",
            '/platform/refunds',
            "/platform/refunds/{$this->refund->id}",
            '/platform/analytics',
            '/platform/services',
            "/platform/services/{$this->serviceRequest->id}",
            '/platform/audit',
            '/platform/system',
            '/platform/complaints',
            "/platform/complaints/{$this->complaint->id}",
            '/platform/notifications',
            '/platform/roles',
            '/platform/settings',
        ];

        foreach ($urls as $url) {
            $response = $this->actingAs($this->superAdmin)->get($url);
            if (! in_array($response->getStatusCode(), [200, 302], true)) {
                echo "[PLATFORM FAIL: $url => {$response->getStatusCode()}]\n";
            }
            $this->assertNotEquals(500, $response->getStatusCode(), "Platform route {$url} returned HTTP 500");
        }
    }
}
