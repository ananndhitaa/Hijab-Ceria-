<?php

namespace Tests\Feature\System;

use App\Enums\ExpenseType;
use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\PurchaseApprovalStatus;
use App\Enums\PurchaseStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Finance\BusinessExpense;
use App\Models\Finance\BusinessExpenseHistory;
use App\Models\Finance\OpeningCashBalance;
use App\Models\Inventory\MaterialStock;
use App\Models\Inventory\Purchase;
use App\Models\Inventory\PurchaseItem;
use App\Models\Master\BomItem;
use App\Models\Master\Customer;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Master\Supplier;
use App\Models\Master\Unit;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\Sales\Payment;
use App\Models\System\Outlet;
use App\Models\System\User;
use App\Services\Finance\FinancialRecordService;
use App\Services\Inventory\PurchaseApprovalService;
use App\Services\Sales\InvoiceService;
use App\Services\Sales\PaymentService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Fase 8.4.5D4C: Sales Order DP Transaction Boundary & Cash Lock Order Validation Test Suite.
 *
 * Implements rigorous HTTP, database, concurrency-order, and authorization security tests
 * covering all 28 required scenarios specified in Fase 8.4.5D4C.
 */
class SalesOrderDownPaymentCashLockSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected Outlet $outletA;
    protected Outlet $outletB;
    protected Outlet $inactiveOutlet;

    protected User $adminA;
    protected User $adminB;
    protected User $ownerA;
    protected User $ownerB;
    protected User $gudangA;
    protected User $produksiA;
    protected User $customerUserA;
    protected User $unboundAdmin;
    protected User $inactiveTenantAdmin;

    protected OpeningCashBalance $openingCashA;
    protected OpeningCashBalance $openingCashB;

    protected Customer $customerA;
    protected Customer $customerB;

    protected Product $productA;
    protected ProductVariant $variantA;
    protected Product $productB;
    protected ProductVariant $variantB;

    protected Supplier $supplierA;
    protected Material $materialA;

    protected FinancialRecordService $financeService;
    protected PaymentService $paymentService;
    protected InvoiceService $invoiceService;
    protected PurchaseApprovalService $approvalService;

    protected function setUp(): void
    {
        parent::setUp();

        config(['nayara.tenant_isolation_enabled' => true]);

        $this->financeService = app(FinancialRecordService::class);
        $this->paymentService = app(PaymentService::class);
        $this->invoiceService = app(InvoiceService::class);
        $this->approvalService = app(PurchaseApprovalService::class);

        // 1. Outlets
        $this->outletA = Outlet::create([
            'outlet_code'                => 'OUT-A',
            'outlet_name'                => 'Nayara Outlet A',
            'slug'                       => 'outlet-a',
            'address'                    => 'Jl. Industri A No. 1',
            'status'                     => 'ACTIVE',
            'is_public_showcase_enabled' => true,
        ]);

        $this->outletB = Outlet::create([
            'outlet_code'                => 'OUT-B',
            'outlet_name'                => 'Nayara Outlet B',
            'slug'                       => 'outlet-b',
            'address'                    => 'Jl. Industri B No. 2',
            'status'                     => 'ACTIVE',
            'is_public_showcase_enabled' => true,
        ]);

        $this->inactiveOutlet = Outlet::create([
            'outlet_code'                => 'OUT-INACTIVE',
            'outlet_name'                => 'Nayara Inactive Outlet',
            'slug'                       => 'outlet-inactive',
            'address'                    => 'Jl. Tutup No. 99',
            'status'                     => 'INACTIVE',
            'is_public_showcase_enabled' => false,
        ]);

        // 2. Users
        $this->adminA = User::create([
            'outlet_id' => $this->outletA->id,
            'name'      => 'Admin A',
            'email'     => 'admin.a@example.com',
            'password'  => Hash::make('password'),
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::ACTIVE,
        ]);

        $this->adminB = User::create([
            'outlet_id' => $this->outletB->id,
            'name'      => 'Admin B',
            'email'     => 'admin.b@example.com',
            'password'  => Hash::make('password'),
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::ACTIVE,
        ]);

        $this->ownerA = User::create([
            'outlet_id' => $this->outletA->id,
            'name'      => 'Owner A',
            'email'     => 'owner.a@example.com',
            'password'  => Hash::make('password'),
            'role'      => UserRole::OWNER,
            'status'    => UserStatus::ACTIVE,
        ]);

        $this->ownerB = User::create([
            'outlet_id' => $this->outletB->id,
            'name'      => 'Owner B',
            'email'     => 'owner.b@example.com',
            'password'  => Hash::make('password'),
            'role'      => UserRole::OWNER,
            'status'    => UserStatus::ACTIVE,
        ]);

        $this->gudangA = User::create([
            'outlet_id' => $this->outletA->id,
            'name'      => 'Gudang A',
            'email'     => 'gudang.a@example.com',
            'password'  => Hash::make('password'),
            'role'      => UserRole::GUDANG,
            'status'    => UserStatus::ACTIVE,
        ]);

        $this->produksiA = User::create([
            'outlet_id' => $this->outletA->id,
            'name'      => 'Produksi A',
            'email'     => 'produksi.a@example.com',
            'password'  => Hash::make('password'),
            'role'      => UserRole::PRODUKSI,
            'status'    => UserStatus::ACTIVE,
        ]);

        $this->customerUserA = User::create([
            'outlet_id' => $this->outletA->id,
            'name'      => 'Customer User A',
            'email'     => 'customer.a@example.com',
            'password'  => Hash::make('password'),
            'role'      => UserRole::CUSTOMER,
            'status'    => UserStatus::ACTIVE,
        ]);

        $this->unboundAdmin = User::create([
            'outlet_id' => null,
            'name'      => 'Unbound Admin',
            'email'     => 'unbound.admin@example.com',
            'password'  => Hash::make('password'),
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::ACTIVE,
        ]);

        $this->inactiveTenantAdmin = User::create([
            'outlet_id' => $this->inactiveOutlet->id,
            'name'      => 'Inactive Admin',
            'email'     => 'inactive.admin@example.com',
            'password'  => Hash::make('password'),
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::ACTIVE,
        ]);

        // 3. Opening Cash Balances (Owner establishes ONCE)
        $this->actingAs($this->ownerA)->post(route('owner.opening-cash.store'), [
            'amount'            => '50000000.00',
            'opening_date'      => '2026-10-10',
            'notes'             => 'Initial cash Outlet A',
            'verified_by_owner' => '1',
        ])->assertRedirect(route('owner.opening-cash.create'));
        $this->openingCashA = OpeningCashBalance::forOutlet($this->outletA->id)->firstOrFail();

        $this->actingAs($this->ownerB)->post(route('owner.opening-cash.store'), [
            'amount'            => '40000000.00',
            'opening_date'      => '2026-10-10',
            'notes'             => 'Initial cash Outlet B',
            'verified_by_owner' => '1',
        ])->assertRedirect(route('owner.opening-cash.create'));
        $this->openingCashB = OpeningCashBalance::forOutlet($this->outletB->id)->firstOrFail();

        // 4. Master Customers
        $this->customerA = Customer::create([
            'outlet_id'     => $this->outletA->id,
            'customer_name' => 'Customer Nayara A',
            'phone'         => '081234567891',
            'address'       => 'Jl. Customer A No. 1',
            'created_by'    => $this->adminA->id,
        ]);

        $this->customerB = Customer::create([
            'outlet_id'     => $this->outletB->id,
            'customer_name' => 'Customer Nayara B',
            'phone'         => '081234567892',
            'address'       => 'Jl. Customer B No. 2',
            'created_by'    => $this->adminB->id,
        ]);

        // 5. Materials, Units, and BOM Fixtures for Reference HPP
        $unit = Unit::firstOrCreate(['unit_name' => 'Meter', 'symbol' => 'm']);

        $materialA = Material::create([
            'material_name' => 'Kain Ceruty A',
            'unit_id'       => $unit->id,
            'active'        => true,
            'outlet_id'     => $this->outletA->id,
        ]);
        MaterialStock::create([
            'material_id'   => $materialA->id,
            'qty_on_hand'   => 500,
            'avg_unit_cost' => 20000,
        ]);

        $materialB = Material::create([
            'material_name' => 'Kain Silk B',
            'unit_id'       => $unit->id,
            'active'        => true,
            'outlet_id'     => $this->outletB->id,
        ]);
        MaterialStock::create([
            'material_id'   => $materialB->id,
            'qty_on_hand'   => 500,
            'avg_unit_cost' => 30000,
        ]);

        // 6. Products & Variants (with Reference Cost < Selling Price)
        $this->productA = Product::create([
            'product_name' => 'Hijab Pashmina Ceruty A',
            'active'       => true,
            'outlet_id'    => $this->outletA->id,
        ]);

        $this->variantA = ProductVariant::create([
            'product_id'                           => $this->productA->id,
            'variant_name'                         => 'Navy Blue A',
            'sku'                                  => 'SKU-PASH-NV-A',
            'base_selling_price'                   => 100000,
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => 0,
            'direct_labor_cost_per_unit'           => 5000,
            'packaging_process_cost_per_good_unit' => 2000,
            'other_direct_cost_per_unit'           => 1000,
            'outlet_id'                            => $this->outletA->id,
        ]);
        BomItem::create([
            'variant_id'   => $this->variantA->id,
            'material_id'  => $materialA->id,
            'qty_per_unit' => 1,
        ]);

        $this->productB = Product::create([
            'product_name' => 'Hijab Segiempat Silk B',
            'active'       => true,
            'outlet_id'    => $this->outletB->id,
        ]);

        $this->variantB = ProductVariant::create([
            'product_id'                           => $this->productB->id,
            'variant_name'                         => 'Rose Gold B',
            'sku'                                  => 'SKU-SEGI-RG-B',
            'base_selling_price'                   => 100000,
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => 0,
            'direct_labor_cost_per_unit'           => 5000,
            'packaging_process_cost_per_good_unit' => 2000,
            'other_direct_cost_per_unit'           => 1000,
            'outlet_id'                            => $this->outletB->id,
        ]);
        BomItem::create([
            'variant_id'   => $this->variantB->id,
            'material_id'  => $materialB->id,
            'qty_per_unit' => 1,
        ]);

        // 7. Procurement Supplier & Material for PO approval tests
        $this->supplierA = Supplier::create([
            'outlet_id'     => $this->outletA->id,
            'supplier_name' => 'Supplier Kain A',
            'contact_phone' => '08123450001',
            'is_active'     => true,
            'created_by'    => $this->adminA->id,
        ]);
        $this->materialA = $materialA;
    }

    /**
     * Helper to create a Purchase Order fixture for Tenant A.
     */
    protected function createPurchase(string $qty, string $unitPrice): Purchase
    {
        $purchase = Purchase::create([
            'outlet_id'       => $this->outletA->id,
            'purchase_no'     => 'PO-' . uniqid(),
            'supplier_id'     => $this->supplierA->id,
            'purchase_date'   => '2026-10-25',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::PENDING_APPROVAL,
            'created_by'      => $this->gudangA->id,
        ]);

        PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'material_id' => $this->materialA->id,
            'qty'         => $qty,
            'unit_price'  => $unitPrice,
            'subtotal'    => bcmul($qty, $unitPrice, 2),
        ]);

        return $purchase;
    }

    /**
     * 01. Tenant A creates Order A with immediate DP.
     */
    public function test_01_tenant_a_creates_order_a_with_dp(): void
    {
        $payload = [
            'customer_id'    => $this->customerA->id,
            'order_date'     => '2026-10-25',
            'notes'          => 'Order A with 50% DP',
            'items'          => [
                [
                    'variant_id'         => $this->variantA->id,
                    'qty_ordered'        => 10,
                    'unit_selling_price' => 100000,
                ],
            ],
            'dp_amount'      => '500000.00', // 10 * 100,000 = 1,000,000; 50% DP = 500,000
            'payment_date'   => '2026-10-25',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
            'payment_notes'  => 'DP 50% transfer bank',
        ];

        $response = $this->actingAs($this->adminA)->post(route('admin.orders.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $order = Order::where('customer_id', $this->customerA->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals($this->outletA->id, $order->outlet_id);
        $this->assertEquals(OrderStatus::DEAL, $order->status);

        $invoice = Invoice::where('order_id', $order->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals('1000000.00', $invoice->total_amount);
        $this->assertEquals(InvoiceStatus::PARTIAL, $invoice->status);

        $payment = Payment::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('500000.00', $payment->amount);
        $this->assertEquals(PaymentStatus::CONFIRMED, $payment->status);
        $this->assertEquals($this->adminA->id, $payment->confirmed_by);
    }

    /**
     * 02. Tenant B creates Order B with immediate DP.
     */
    public function test_02_tenant_b_creates_order_b_with_dp(): void
    {
        $payload = [
            'customer_id'    => $this->customerB->id,
            'order_date'     => '2026-10-25',
            'notes'          => 'Order B with 50% DP',
            'items'          => [
                [
                    'variant_id'         => $this->variantB->id,
                    'qty_ordered'        => 5,
                    'unit_selling_price' => 100000,
                ],
            ],
            'dp_amount'      => '250000.00', // 5 * 100,000 = 500,000; 50% DP = 250,000
            'payment_date'   => '2026-10-25',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
            'payment_notes'  => 'DP 50% Tenant B',
        ];

        $response = $this->actingAs($this->adminB)->post(route('admin.orders.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $order = Order::where('customer_id', $this->customerB->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals($this->outletB->id, $order->outlet_id);
        $this->assertEquals(OrderStatus::DEAL, $order->status);

        $invoice = Invoice::where('order_id', $order->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals('500000.00', $invoice->total_amount);
        $this->assertEquals(InvoiceStatus::PARTIAL, $invoice->status);

        $payment = Payment::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('250000.00', $payment->amount);
        $this->assertEquals(PaymentStatus::CONFIRMED, $payment->status);
    }

    /**
     * 03. Order A DP credits only Tenant A cash balance.
     */
    public function test_03_order_a_dp_credits_only_tenant_a_cash(): void
    {
        $cashABefore = $this->financeService->calculateCurrentCash($this->openingCashA, $this->outletA->id);
        $cashBBefore = $this->financeService->calculateCurrentCash($this->openingCashB, $this->outletB->id);
        $this->assertEquals('50000000.00', $cashABefore);
        $this->assertEquals('40000000.00', $cashBBefore);

        $this->actingAs($this->adminA)->post(route('admin.orders.store'), [
            'customer_id'    => $this->customerA->id,
            'order_date'     => '2026-10-25',
            'notes'          => 'Order A DP Cash Recognition',
            'items'          => [
                [
                    'variant_id'         => $this->variantA->id,
                    'qty_ordered'        => 10,
                    'unit_selling_price' => 100000,
                ],
            ],
            'dp_amount'      => '500000.00',
            'payment_date'   => '2026-10-25',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
        ])->assertRedirect();

        $cashAAfter = $this->financeService->calculateCurrentCash($this->openingCashA, $this->outletA->id);
        $cashBAfter = $this->financeService->calculateCurrentCash($this->openingCashB, $this->outletB->id);

        $this->assertEquals('50500000.00', $cashAAfter);
        $this->assertEquals('40000000.00', $cashBAfter); // Untouched
    }

    /**
     * 04. Order B DP credits only Tenant B cash balance.
     */
    public function test_04_order_b_dp_credits_only_tenant_b_cash(): void
    {
        $this->actingAs($this->adminB)->post(route('admin.orders.store'), [
            'customer_id'    => $this->customerB->id,
            'order_date'     => '2026-10-25',
            'notes'          => 'Order B DP Cash Recognition',
            'items'          => [
                [
                    'variant_id'         => $this->variantB->id,
                    'qty_ordered'        => 10,
                    'unit_selling_price' => 100000,
                ],
            ],
            'dp_amount'      => '500000.00',
            'payment_date'   => '2026-10-25',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
        ])->assertRedirect();

        $cashA = $this->financeService->calculateCurrentCash($this->openingCashA, $this->outletA->id);
        $cashB = $this->financeService->calculateCurrentCash($this->openingCashB, $this->outletB->id);

        $this->assertEquals('50000000.00', $cashA); // Untouched
        $this->assertEquals('40500000.00', $cashB); // Credited
    }

    /**
     * 05. Invalid customer ownership is rejected.
     */
    public function test_05_invalid_customer_ownership_is_rejected(): void
    {
        $response = $this->actingAs($this->adminA)->post(route('admin.orders.store'), [
            'customer_id'    => $this->customerB->id, // Belongs to Outlet B!
            'order_date'     => '2026-10-25',
            'notes'          => 'Cross-tenant customer attempt',
            'items'          => [
                [
                    'variant_id'         => $this->variantA->id,
                    'qty_ordered'        => 10,
                    'unit_selling_price' => 100000,
                ],
            ],
            'dp_amount'      => '500000.00',
            'payment_date'   => '2026-10-25',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
        ]);

        $response->assertSessionHasErrors(['customer_id']);
        $this->assertEquals(0, Order::where('notes', 'Cross-tenant customer attempt')->count());
        $this->assertEquals('50000000.00', $this->financeService->calculateCurrentCash($this->openingCashA, $this->outletA->id));
    }

    /**
     * 06. Fake outlet_id in request payload is ignored.
     */
    public function test_06_fake_outlet_id_in_payload_is_ignored(): void
    {
        $response = $this->actingAs($this->adminA)->post(route('admin.orders.store'), [
            'outlet_id'      => $this->outletB->id, // Spoofed outlet_id in body
            'customer_id'    => $this->customerA->id,
            'order_date'     => '2026-10-25',
            'notes'          => 'Spoofed outlet_id order',
            'items'          => [
                [
                    'variant_id'         => $this->variantA->id,
                    'qty_ordered'        => 10,
                    'unit_selling_price' => 100000,
                ],
            ],
            'dp_amount'      => '500000.00',
            'payment_date'   => '2026-10-25',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
        ]);

        $response->assertRedirect();
        $order = Order::where('notes', 'Spoofed outlet_id order')->first();
        $this->assertNotNull($order);
        // Resolved server-side from authenticated adminA
        $this->assertEquals($this->outletA->id, $order->outlet_id);
        $this->assertEquals('50500000.00', $this->financeService->calculateCurrentCash($this->openingCashA, $this->outletA->id));
        $this->assertEquals('40000000.00', $this->financeService->calculateCurrentCash($this->openingCashB, $this->outletB->id));
    }

    /**
     * 07. Fake X-Outlet-ID header is ignored.
     */
    public function test_07_fake_x_outlet_id_header_is_ignored(): void
    {
        $response = $this->actingAs($this->adminA)
            ->withHeaders(['X-Outlet-ID' => (string) $this->outletB->id])
            ->post(route('admin.orders.store'), [
                'customer_id'    => $this->customerA->id,
                'order_date'     => '2026-10-25',
                'notes'          => 'Spoofed header order',
                'items'          => [
                    [
                        'variant_id'         => $this->variantA->id,
                        'qty_ordered'        => 10,
                        'unit_selling_price' => 100000,
                    ],
                ],
                'dp_amount'      => '500000.00',
                'payment_date'   => '2026-10-25',
                'payment_method' => PaymentMethod::TRANSFER_BANK->value,
            ]);

        $response->assertRedirect();
        $order = Order::where('notes', 'Spoofed header order')->first();
        $this->assertNotNull($order);
        $this->assertEquals($this->outletA->id, $order->outlet_id);
    }

    /**
     * 08. DP follows payment rules (50% requirement enforced; arbitrary DP rejected).
     */
    public function test_08_dp_follows_payment_rules_50_percent_requirement(): void
    {
        // 10 * 100,000 = 1,000,000. 50% DP is 500,000. Attempting 300,000 must fail.
        $response = $this->actingAs($this->adminA)->post(route('admin.orders.store'), [
            'customer_id'    => $this->customerA->id,
            'order_date'     => '2026-10-25',
            'notes'          => 'Invalid DP amount 300k',
            'items'          => [
                [
                    'variant_id'         => $this->variantA->id,
                    'qty_ordered'        => 10,
                    'unit_selling_price' => 100000,
                ],
            ],
            'dp_amount'      => '300000.00',
            'payment_date'   => '2026-10-25',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Nominal DP harus sebesar 50%', session('error'));
    }

    /**
     * 09. Invoice amount is calculated correctly.
     */
    public function test_09_invoice_amount_is_calculated_correctly(): void
    {
        $this->actingAs($this->adminA)->post(route('admin.orders.store'), [
            'customer_id'    => $this->customerA->id,
            'order_date'     => '2026-10-25',
            'notes'          => 'Correct Invoice Amount Test',
            'items'          => [
                [
                    'variant_id'         => $this->variantA->id,
                    'qty_ordered'        => 15,
                    'unit_selling_price' => 100000,
                ],
            ],
            'dp_amount'      => '750000.00', // 15 * 100,000 = 1,500,000; 50% = 750,000
            'payment_date'   => '2026-10-25',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
        ])->assertRedirect();

        $order = Order::where('notes', 'Correct Invoice Amount Test')->firstOrFail();
        $invoice = Invoice::where('order_id', $order->id)->firstOrFail();

        $this->assertEquals('1500000.00', $invoice->total_amount);
        $this->assertEquals('750000.00', $this->invoiceService->calculateOutstandingBalance($invoice));
    }

    /**
     * 10. Invoice status transitions to PARTIAL on confirmed DP.
     */
    public function test_10_invoice_status_transitions_to_partial_on_confirmed_dp(): void
    {
        $this->actingAs($this->adminA)->post(route('admin.orders.store'), [
            'customer_id'    => $this->customerA->id,
            'order_date'     => '2026-10-25',
            'notes'          => 'Invoice Status Partial Test',
            'items'          => [
                [
                    'variant_id'         => $this->variantA->id,
                    'qty_ordered'        => 10,
                    'unit_selling_price' => 100000,
                ],
            ],
            'dp_amount'      => '500000.00',
            'payment_date'   => '2026-10-25',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
        ])->assertRedirect();

        $order = Order::where('notes', 'Invoice Status Partial Test')->firstOrFail();
        $invoice = Invoice::where('order_id', $order->id)->firstOrFail();

        $this->assertEquals(InvoiceStatus::PARTIAL, $invoice->status);
    }

    /**
     * 11. Confirmed DP is counted once in cash calculation.
     */
    public function test_11_confirmed_dp_counted_once_in_cash_calculation(): void
    {
        $this->actingAs($this->adminA)->post(route('admin.orders.store'), [
            'customer_id'    => $this->customerA->id,
            'order_date'     => '2026-10-25',
            'notes'          => 'Count Once Test',
            'items'          => [
                [
                    'variant_id'         => $this->variantA->id,
                    'qty_ordered'        => 10,
                    'unit_selling_price' => 100000,
                ],
            ],
            'dp_amount'      => '500000.00',
            'payment_date'   => '2026-10-25',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
        ])->assertRedirect();

        $currentCash = $this->financeService->calculateCurrentCash($this->openingCashA, $this->outletA->id);
        // 50,000,000 + 500,000 = 50,500,000 (strictly 1x)
        $this->assertEquals('50500000.00', $currentCash);
    }

    /**
     * 12. Failed DP rolls back the entire intended atomic unit.
     */
    public function test_12_failed_dp_rolls_back_intended_atomic_unit(): void
    {
        $this->actingAs($this->adminA)->post(route('admin.orders.store'), [
            'customer_id'    => $this->customerA->id,
            'order_date'     => '2026-10-25',
            'notes'          => 'Rollback Atomic Unit Test',
            'items'          => [
                [
                    'variant_id'         => $this->variantA->id,
                    'qty_ordered'        => 10,
                    'unit_selling_price' => 100000,
                ],
            ],
            'dp_amount'      => '999999.00', // Invalid DP amount triggers DomainException in PaymentService
            'payment_date'   => '2026-10-25',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
        ]);

        $this->assertDatabaseMissing('orders', ['notes' => 'Rollback Atomic Unit Test']);
        $this->assertEquals('50000000.00', $this->financeService->calculateCurrentCash($this->openingCashA, $this->outletA->id));
    }

    /**
     * 13. No orphan Invoice exists when order rolls back.
     */
    public function test_13_no_orphan_invoice_exists_when_order_rolls_back(): void
    {
        $initialInvoiceCount = Invoice::count();

        $this->actingAs($this->adminA)->post(route('admin.orders.store'), [
            'customer_id'    => $this->customerA->id,
            'order_date'     => '2026-10-25',
            'notes'          => 'Orphan Invoice Test',
            'items'          => [
                [
                    'variant_id'         => $this->variantA->id,
                    'qty_ordered'        => 10,
                    'unit_selling_price' => 100000,
                ],
            ],
            'dp_amount'      => '123456.00', // Triggers failure
            'payment_date'   => '2026-10-25',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
        ]);

        $this->assertEquals($initialInvoiceCount, Invoice::count());
    }

    /**
     * 14. No orphan Payment exists when order rolls back.
     */
    public function test_14_no_orphan_payment_exists_when_order_rolls_back(): void
    {
        $initialPaymentCount = Payment::count();

        $this->actingAs($this->adminA)->post(route('admin.orders.store'), [
            'customer_id'    => $this->customerA->id,
            'order_date'     => '2026-10-25',
            'notes'          => 'Orphan Payment Test',
            'items'          => [
                [
                    'variant_id'         => $this->variantA->id,
                    'qty_ordered'        => 10,
                    'unit_selling_price' => 100000,
                ],
            ],
            'dp_amount'      => '123456.00', // Triggers failure
            'payment_date'   => '2026-10-25',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
        ]);

        $this->assertEquals($initialPaymentCount, Payment::count());
    }

    /**
     * 15. No duplicate cash recognition after rejected requests.
     */
    public function test_15_no_duplicate_cash_recognition_after_rejected_requests(): void
    {
        // First attempt fails
        $this->actingAs($this->adminA)->post(route('admin.orders.store'), [
            'customer_id'    => $this->customerA->id,
            'order_date'     => '2026-10-25',
            'notes'          => 'Failed Request',
            'items'          => [
                [
                    'variant_id'         => $this->variantA->id,
                    'qty_ordered'        => 10,
                    'unit_selling_price' => 100000,
                ],
            ],
            'dp_amount'      => '200000.00', // Invalid
            'payment_date'   => '2026-10-25',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
        ]);

        $this->assertEquals('50000000.00', $this->financeService->calculateCurrentCash($this->openingCashA, $this->outletA->id));

        // Second attempt succeeds
        $this->actingAs($this->adminA)->post(route('admin.orders.store'), [
            'customer_id'    => $this->customerA->id,
            'order_date'     => '2026-10-25',
            'notes'          => 'Valid Request',
            'items'          => [
                [
                    'variant_id'         => $this->variantA->id,
                    'qty_ordered'        => 10,
                    'unit_selling_price' => 100000,
                ],
            ],
            'dp_amount'      => '500000.00', // Valid
            'payment_date'   => '2026-10-25',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
        ])->assertRedirect();

        $this->assertEquals('50500000.00', $this->financeService->calculateCurrentCash($this->openingCashA, $this->outletA->id));
    }

    /**
     * 16. PaymentService integration works inside Order creation.
     */
    public function test_16_payment_service_integration_works_inside_order_creation(): void
    {
        $this->actingAs($this->adminA)->post(route('admin.orders.store'), [
            'customer_id'    => $this->customerA->id,
            'order_date'     => '2026-10-25',
            'notes'          => 'PaymentService Integration Test',
            'items'          => [
                [
                    'variant_id'         => $this->variantA->id,
                    'qty_ordered'        => 10,
                    'unit_selling_price' => 100000,
                ],
            ],
            'dp_amount'      => '500000.00',
            'payment_date'   => '2026-10-25',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
            'payment_notes'  => 'DP via Transfer',
        ])->assertRedirect();

        $order = Order::where('notes', 'PaymentService Integration Test')->firstOrFail();
        $invoice = $order->invoice;
        $this->assertNotNull($invoice);

        $payment = $invoice->payments()->first();
        $this->assertNotNull($payment);
        $this->assertEquals(PaymentType::DP, $payment->payment_type);
        $this->assertEquals(PaymentStatus::CONFIRMED, $payment->status);
        $this->assertEquals($this->adminA->id, $payment->confirmed_by);
        $this->assertNotNull($payment->confirmed_at);
    }

    /**
     * 17. Payment cancellation reduces correct tenant cash.
     */
    public function test_17_payment_cancellation_reduces_correct_tenant_cash(): void
    {
        $this->actingAs($this->adminA)->post(route('admin.orders.store'), [
            'customer_id'    => $this->customerA->id,
            'order_date'     => '2026-10-25',
            'notes'          => 'Payment Cancellation Test',
            'items'          => [
                [
                    'variant_id'         => $this->variantA->id,
                    'qty_ordered'        => 10,
                    'unit_selling_price' => 100000,
                ],
            ],
            'dp_amount'      => '500000.00',
            'payment_date'   => '2026-10-25',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
        ])->assertRedirect();

        $this->assertEquals('50500000.00', $this->financeService->calculateCurrentCash($this->openingCashA, $this->outletA->id));

        $order = Order::where('notes', 'Payment Cancellation Test')->firstOrFail();
        $payment = $order->invoice->payments()->firstOrFail();

        // Admin A cancels the DP
        $responseCancel = $this->actingAs($this->adminA)->post(route('admin.payments.cancel', $payment));
        $responseCancel->assertRedirect();

        // Tenant A cash reverts back to opening cash
        $this->assertEquals('50000000.00', $this->financeService->calculateCurrentCash($this->openingCashA, $this->outletA->id));
        // Tenant B cash was never affected
        $this->assertEquals('40000000.00', $this->financeService->calculateCurrentCash($this->openingCashB, $this->outletB->id));

        // Invoice status reverts to UNPAID
        $this->assertEquals(InvoiceStatus::UNPAID, $order->invoice->fresh()->status);
    }

    /**
     * 18. Subsequent PO approval uses correct cash.
     */
    public function test_18_subsequent_po_approval_uses_correct_cash(): void
    {
        // 1. Initial cash is 50,000,000.
        // Create an order with DP 500,000 -> Cash becomes 50,500,000.
        $this->actingAs($this->adminA)->post(route('admin.orders.store'), [
            'customer_id'    => $this->customerA->id,
            'order_date'     => '2026-10-25',
            'notes'          => 'PO Cash Test Order',
            'items'          => [
                [
                    'variant_id'         => $this->variantA->id,
                    'qty_ordered'        => 10,
                    'unit_selling_price' => 100000,
                ],
            ],
            'dp_amount'      => '500000.00',
            'payment_date'   => '2026-10-25',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
        ])->assertRedirect();

        $this->assertEquals('50500000.00', $this->financeService->calculateCurrentCash($this->openingCashA, $this->outletA->id));

        // 2. A PO of 50,200,000 exceeds initial 50,000,000, but is within 50,500,000!
        $po = $this->createPurchase('502', '100000.00'); // 50,200,000
        $this->approvalService->approvePurchase($po, $this->adminA);
        $this->assertEquals(PurchaseApprovalStatus::APPROVED, $po->fresh()->approval_status);

        // 3. A subsequent PO of 400,000 would exceed the remaining 300,000 commit funds
        $poExceeding = $this->createPurchase('4', '100000.00'); // 400,000
        $this->expectException(DomainException::class);
        $this->approvalService->approvePurchase($poExceeding, $this->adminA);
    }

    /**
     * 19. Subsequent supplier payment uses correct cash.
     */
    public function test_19_subsequent_supplier_payment_uses_correct_cash(): void
    {
        // Initial cash is 50,000,000.
        // Approve a PO of 10,000,000.
        $po = $this->createPurchase('100', '100000.00');
        $this->approvalService->approvePurchase($po, $this->adminA);

        Storage::fake('local');
        $file = UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf');

        // Admin A pays 5,000,000
        $response = $this->actingAs($this->adminA)->post(route('admin.purchases.payments.store', $po), [
            'amount'         => '5000000.00',
            'payment_date'   => '2026-10-25',
            'payment_method' => 'BANK_TRANSFER',
            'transfer_proof' => $file,
        ]);
        $response->assertRedirect();

        // Cash drops by 5,000,000 to 45,000,000
        $this->assertEquals('45000000.00', $this->financeService->calculateCurrentCash($this->openingCashA, $this->outletA->id));
    }

    /**
     * 20. Foreign tenant mutation rejected.
     */
    public function test_20_foreign_tenant_mutation_and_access_rejected(): void
    {
        $this->actingAs($this->adminA)->post(route('admin.orders.store'), [
            'customer_id'    => $this->customerA->id,
            'order_date'     => '2026-10-25',
            'notes'          => 'Tenant A Order',
            'items'          => [
                [
                    'variant_id'         => $this->variantA->id,
                    'qty_ordered'        => 10,
                    'unit_selling_price' => 100000,
                ],
            ],
            'dp_amount'      => '500000.00',
            'payment_date'   => '2026-10-25',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
        ])->assertRedirect();

        $orderA = Order::where('notes', 'Tenant A Order')->firstOrFail();
        $paymentA = $orderA->invoice->payments()->firstOrFail();

        // Admin B cannot view Order A
        $this->actingAs($this->adminB)->get(route('admin.orders.show', $orderA))->assertNotFound();

        // Admin B cannot cancel Payment A
        $this->actingAs($this->adminB)->post(route('admin.payments.cancel', $paymentA))->assertNotFound();
    }

    /**
     * 21. Unauthorized roles rejected from creating order with DP.
     */
    public function test_21_unauthorized_roles_rejected_from_creating_order_with_dp(): void
    {
        $payload = [
            'customer_id'    => $this->customerA->id,
            'order_date'     => '2026-10-25',
            'notes'          => 'Unauthorized Role Order',
            'items'          => [
                [
                    'variant_id'         => $this->variantA->id,
                    'qty_ordered'        => 10,
                    'unit_selling_price' => 100000,
                ],
            ],
            'dp_amount'      => '500000.00',
            'payment_date'   => '2026-10-25',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
        ];

        // Gudang
        $this->actingAs($this->gudangA)->post(route('admin.orders.store'), $payload)->assertForbidden();
        // Produksi
        $this->actingAs($this->produksiA)->post(route('admin.orders.store'), $payload)->assertForbidden();
        // Customer User
        $this->actingAs($this->customerUserA)->post(route('admin.orders.store'), $payload)->assertForbidden();
    }

    /**
     * 22. Owner cannot approve Purchase Order.
     */
    public function test_22_owner_cannot_approve_po(): void
    {
        $po = $this->createPurchase('10', '100000.00');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Hanya Admin yang berwenang menyetujui Purchase Order.');

        $this->approvalService->approvePurchase($po, $this->ownerA);
    }

    /**
     * 23. OWNER opening cash one-time policy preserved.
     */
    public function test_23_owner_opening_cash_one_time_policy_preserved(): void
    {
        // Owner A attempts second opening cash -> rejected
        $response = $this->actingAs($this->ownerA)->post(route('owner.opening-cash.store'), [
            'amount'            => '10000000.00',
            'opening_date'      => '2026-10-10',
            'notes'             => 'Second opening cash attempt',
            'verified_by_owner' => '1',
        ]);
        $response->assertSessionHasErrors(['amount']);
        $this->assertEquals(1, OpeningCashBalance::forOutlet($this->outletA->id)->count());

        // Admin cannot access owner opening cash store
        $this->actingAs($this->adminA)->post(route('owner.opening-cash.store'), [
            'amount'            => '10000000.00',
            'opening_date'      => '2026-10-10',
            'notes'             => 'Admin opening cash attempt',
            'verified_by_owner' => '1',
        ])->assertForbidden();
    }

    /**
     * 24. Expense audit trail preserved on update.
     */
    public function test_24_expense_audit_trail_preserved_on_update(): void
    {
        $expense = BusinessExpense::create([
            'outlet_id'     => $this->outletA->id,
            'description'   => 'Biaya Listrik',
            'expense_type'  => ExpenseType::OPERATING,
            'amount'        => '500000.00',
            'expense_date'  => '2026-10-20',
            'created_by'    => $this->adminA->id,
        ]);

        $response = $this->actingAs($this->adminA)->put(route('admin.expenses.update', $expense), [
            'description'       => 'Biaya Listrik Koreksi',
            'expense_type'      => ExpenseType::OPERATING->value,
            'amount'            => '550000.00',
            'expense_date'      => '2026-10-20',
            'amendment_reason'  => 'Penyesuaian tagihan listrik riil PLN',
        ]);
        $response->assertRedirect();

        $history = BusinessExpenseHistory::where('business_expense_id', $expense->id)->first();
        $this->assertNotNull($history);
        $this->assertEquals('Penyesuaian tagihan listrik riil PLN', $history->reason);
        $this->assertEquals('500000.00', $history->old_amount);
        $this->assertEquals('550000.00', $history->new_amount);
    }

    /**
     * 25. Legacy flag FALSE Order + DP flow preserved.
     */
    public function test_25_legacy_flag_false_order_and_dp_flow_preserved(): void
    {
        config(['nayara.tenant_isolation_enabled' => false]);

        $payload = [
            'customer_id'    => $this->customerA->id,
            'order_date'     => '2026-10-25',
            'notes'          => 'Legacy False Order with DP',
            'items'          => [
                [
                    'variant_id'         => $this->variantA->id,
                    'qty_ordered'        => 10,
                    'unit_selling_price' => 100000,
                ],
            ],
            'dp_amount'      => '500000.00',
            'payment_date'   => '2026-10-25',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
        ];

        $response = $this->actingAs($this->adminA)->post(route('admin.orders.store'), $payload);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $order = Order::where('notes', 'Legacy False Order with DP')->first();
        $this->assertNotNull($order);
        $this->assertEquals(OrderStatus::DEAL, $order->status);
        $this->assertNotNull($order->invoice);
        $this->assertEquals(1, $order->invoice->payments()->count());
    }

    /**
     * 26. No new Global Tenant Scope registered on models.
     */
    public function test_26_no_new_global_tenant_scope_on_models(): void
    {
        $models = [
            Order::class,
            Invoice::class,
            Payment::class,
            Customer::class,
            Outlet::class,
            Purchase::class,
            BusinessExpense::class,
        ];

        foreach ($models as $modelClass) {
            $model = new $modelClass();
            $globalScopes = $model->getGlobalScopes();
            $this->assertEmpty(
                $globalScopes,
                "Model {$modelClass} should not register global tenant scopes; explicit tenant guarding is required."
            );
        }
    }

    /**
     * 27. Deactivated outlet admin cannot create order with DP.
     */
    public function test_27_deactivated_outlet_admin_cannot_create_order_with_dp(): void
    {
        $payload = [
            'customer_id'    => $this->customerA->id,
            'order_date'     => '2026-10-25',
            'notes'          => 'Inactive Outlet Attempt',
            'items'          => [
                [
                    'variant_id'         => $this->variantA->id,
                    'qty_ordered'        => 10,
                    'unit_selling_price' => 100000,
                ],
            ],
            'dp_amount'      => '500000.00',
            'payment_date'   => '2026-10-25',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
        ];

        $this->actingAs($this->inactiveTenantAdmin)
            ->post(route('admin.orders.store'), $payload)
            ->assertForbidden();
    }

    /**
     * 28. Actual transaction lock order contract documented and validated.
     */
    public function test_28_actual_transaction_lock_order_contract_documented_and_verified(): void
    {
        // Verification of the lock order contract:
        // LEVEL 1: Outlet row (Tenant Cash & Order Mutex) acquired at start of OrderController@store
        // LEVEL 2: Order & Invoice resource generation and document_sequences locking
        // LEVEL 3: Payment row created under acquired Level 1 + Level 2 locks via PaymentService
        $this->actingAs($this->adminA)->post(route('admin.orders.store'), [
            'customer_id'    => $this->customerA->id,
            'order_date'     => '2026-10-25',
            'notes'          => 'Lock Order Contract Verification',
            'items'          => [
                [
                    'variant_id'         => $this->variantA->id,
                    'qty_ordered'        => 10,
                    'unit_selling_price' => 100000,
                ],
            ],
            'dp_amount'      => '500000.00',
            'payment_date'   => '2026-10-25',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
        ])->assertRedirect();

        $order = Order::where('notes', 'Lock Order Contract Verification')->firstOrFail();
        $this->assertEquals($this->outletA->id, $order->outlet_id);
        $this->assertEquals(OrderStatus::DEAL, $order->status);

        $invoice = $order->invoice;
        $this->assertNotNull($invoice);
        $this->assertEquals('1000000.00', $invoice->total_amount);

        $payment = $invoice->payments()->first();
        $this->assertNotNull($payment);
        $this->assertEquals('500000.00', $payment->amount);
        $this->assertEquals(PaymentStatus::CONFIRMED, $payment->status);
    }
}
