<?php

namespace Tests\Feature\Owner;

use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Enums\PurchaseApprovalStatus;
use App\Enums\PurchaseStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Inventory\Purchase;
use App\Models\Inventory\PurchaseItem;
use App\Models\Master\Customer;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Master\Supplier;
use App\Models\Master\Unit;
use App\Models\Production\ProductionBatch;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerAuthorizationAndFoundationTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $admin;
    private User $gudang;
    private User $produksi;
    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create([
            'name'   => 'Owner User',
            'role'   => UserRole::OWNER,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->admin = User::factory()->create([
            'name'   => 'Admin User',
            'role'   => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->gudang = User::factory()->create([
            'name'   => 'Gudang User',
            'role'   => UserRole::GUDANG,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->produksi = User::factory()->create([
            'name'   => 'Produksi User',
            'role'   => UserRole::PRODUKSI,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->customer = User::factory()->create([
            'name'   => 'Customer User',
            'role'   => UserRole::CUSTOMER,
            'status' => UserStatus::ACTIVE,
        ]);
    }

    public function test_guest_is_redirected_to_login_when_accessing_owner_dashboard(): void
    {
        $response = $this->get(route('owner.dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_owner_can_access_owner_dashboard(): void
    {
        $response = $this->actingAs($this->owner)->get(route('owner.dashboard'));
        $response->assertOk();
        $response->assertSee('Selamat Datang, Owner');
        $response->assertSee('Owner &amp; Monitoring', false);
        $response->assertSee('Pantau penjualan, produksi, biaya, dan kinerja usaha NAYARA.');
    }

    public function test_admin_cannot_access_owner_dashboard_returns_403(): void
    {
        $response = $this->actingAs($this->admin)->get(route('owner.dashboard'));
        $response->assertForbidden();
    }

    public function test_gudang_cannot_access_owner_dashboard_returns_403(): void
    {
        $response = $this->actingAs($this->gudang)->get(route('owner.dashboard'));
        $response->assertForbidden();
    }

    public function test_produksi_cannot_access_owner_dashboard_returns_403(): void
    {
        $response = $this->actingAs($this->produksi)->get(route('owner.dashboard'));
        $response->assertForbidden();
    }

    public function test_customer_cannot_access_owner_dashboard_returns_403(): void
    {
        $response = $this->actingAs($this->customer)->get(route('owner.dashboard'));
        $response->assertForbidden();
    }

    public function test_dashboard_redirect_for_owner_goes_to_owner_dashboard(): void
    {
        $response = $this->actingAs($this->owner)->get(route('dashboard'));
        $response->assertRedirect(route('owner.dashboard'));
    }

    public function test_owner_dashboard_displays_real_operational_counts_and_no_fake_financial_numbers(): void
    {
        $cust = Customer::create(['customer_name' => 'Cust A', 'phone' => '08123456789']);
        $unit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $mat = Material::create(['material_name' => 'Kain', 'unit_id' => $unit->id, 'cost_per_unit' => 10000]);
        $prod = Product::create(['product_name' => 'Hijab A']);
        $var = ProductVariant::create(['product_id' => $prod->id, 'variant_name' => 'Navy', 'sku' => 'HJB-NVY']);

        // 2 orders: 1 in DIKIRIM, 1 in SELESAI
        $o1 = Order::create([
            'customer_id'  => $cust->id,
            'order_no'     => 'ORD-202609-0010',
            'order_date'   => now()->toDateString(),
            'status'       => OrderStatus::DIKIRIM,
            'total_qty'    => 10,
            'total_amount' => 500000,
        ]);
        $item1 = OrderItem::create([
            'order_id'           => $o1->id,
            'variant_id'         => $var->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 50000,
            'subtotal'           => 500000,
        ]);

        $o2 = Order::create([
            'customer_id'  => $cust->id,
            'order_no'     => 'ORD-202609-0011',
            'order_date'   => now()->toDateString(),
            'status'       => OrderStatus::SELESAI,
            'total_qty'    => 5,
            'total_amount' => 250000,
        ]);
        $item2 = OrderItem::create([
            'order_id'           => $o2->id,
            'variant_id'         => $var->id,
            'qty_ordered'        => 5,
            'unit_selling_price' => 50000,
            'subtotal'           => 250000,
        ]);

        // 1 active batch (PRODUKSI), 1 completed batch (SELESAI)
        ProductionBatch::create([
            'order_item_id' => $item1->id,
            'batch_no'      => 'BAT-202609-0010',
            'target_qty'    => 10,
            'status'        => BatchStatus::PRODUKSI,
        ]);
        ProductionBatch::create([
            'order_item_id' => $item2->id,
            'batch_no'      => 'BAT-202609-0011',
            'target_qty'    => 5,
            'status'        => BatchStatus::SELESAI,
        ]);

        $response = $this->actingAs($this->owner)->get(route('owner.dashboard'));
        $response->assertOk();

        // Check operational cards
        $response->assertSee('Total Pesanan');
        $response->assertSee('2');
        $response->assertSee('Batch Aktif');
        $response->assertSee('1');
        $response->assertSee('Dalam Pengiriman');
        $response->assertSee('1');
        $response->assertSee('Pesanan Selesai');
        $response->assertSee('1');

        // Verify NO fake financial numbers or labels
        $response->assertDontSee('Total pendapatan bersih');
        $response->assertDontSee('Margin: 0%');
        $response->assertDontSee('Net Margin: 0%');
    }

    public function test_owner_cannot_perform_operational_order_mutations(): void
    {
        $response = $this->actingAs($this->owner)->post(route('admin.orders.store'), [
            'customer_id' => 1,
            'order_date'  => now()->toDateString(),
            'notes'       => 'Unauthorized attempt',
            'items'       => [],
        ]);
        $response->assertForbidden();
    }

    public function test_owner_cannot_perform_batch_creation_mutations(): void
    {
        $cust = Customer::create(['customer_name' => 'Cust B', 'phone' => '08123456788']);
        $order = Order::create([
            'customer_id'  => $cust->id,
            'order_no'     => 'ORD-202609-0099',
            'order_date'   => now()->toDateString(),
            'status'       => OrderStatus::DEAL,
            'total_qty'    => 10,
            'total_amount' => 500000,
        ]);

        $response = $this->actingAs($this->owner)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => 1,
            'target_qty'    => 10,
        ]);
        $response->assertForbidden();
    }

    public function test_owner_cannot_perform_gudang_material_allocation_mutations(): void
    {
        $cust = Customer::create(['customer_name' => 'Cust C', 'phone' => '08123456787']);
        $prod = Product::create(['product_name' => 'Hijab B']);
        $var = ProductVariant::create(['product_id' => $prod->id, 'variant_name' => 'Black', 'sku' => 'HJB-BLK']);
        $order = Order::create([
            'customer_id'  => $cust->id,
            'order_no'     => 'ORD-202609-0098',
            'order_date'   => now()->toDateString(),
            'status'       => OrderStatus::BAHAN,
            'total_qty'    => 10,
            'total_amount' => 500000,
        ]);
        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $var->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 50000,
            'subtotal'           => 500000,
        ]);
        $batch = ProductionBatch::create([
            'order_item_id' => $item->id,
            'batch_no'      => 'BAT-202609-0098',
            'target_qty'    => 10,
            'status'        => BatchStatus::BAHAN,
        ]);

        $response = $this->actingAs($this->owner)->post(route('gudang.material-planning.allocate', $batch));
        $response->assertForbidden();
    }

    public function test_owner_cannot_perform_produksi_result_mutations(): void
    {
        $cust = Customer::create(['customer_name' => 'Cust D', 'phone' => '08123456786']);
        $prod = Product::create(['product_name' => 'Hijab C']);
        $var = ProductVariant::create(['product_id' => $prod->id, 'variant_name' => 'Grey', 'sku' => 'HJB-GRY']);
        $order = Order::create([
            'customer_id'  => $cust->id,
            'order_no'     => 'ORD-202609-0097',
            'order_date'   => now()->toDateString(),
            'status'       => OrderStatus::PRODUKSI,
            'total_qty'    => 10,
            'total_amount' => 500000,
        ]);
        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $var->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 50000,
            'subtotal'           => 500000,
        ]);
        $batch = ProductionBatch::create([
            'order_item_id' => $item->id,
            'batch_no'      => 'BAT-202609-0097',
            'target_qty'    => 10,
            'status'        => BatchStatus::PRODUKSI,
        ]);

        $response = $this->actingAs($this->owner)->post(route('produksi.batches.production-result.store', $batch), [
            'actual_qty'  => 10,
            'result_date' => now()->toDateString(),
        ]);
        $response->assertForbidden();
    }

    public function test_owner_cannot_perform_qc_mutations(): void
    {
        $cust = Customer::create(['customer_name' => 'Cust E', 'phone' => '08123456785']);
        $prod = Product::create(['product_name' => 'Hijab D']);
        $var = ProductVariant::create(['product_id' => $prod->id, 'variant_name' => 'Mocca', 'sku' => 'HJB-MCC']);
        $order = Order::create([
            'customer_id'  => $cust->id,
            'order_no'     => 'ORD-202609-0096',
            'order_date'   => now()->toDateString(),
            'status'       => OrderStatus::QC,
            'total_qty'    => 10,
            'total_amount' => 500000,
        ]);
        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $var->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 50000,
            'subtotal'           => 500000,
        ]);
        $batch = ProductionBatch::create([
            'order_item_id' => $item->id,
            'batch_no'      => 'BAT-202609-0096',
            'target_qty'    => 10,
            'status'        => BatchStatus::QC,
        ]);

        $response = $this->actingAs($this->owner)->post(route('produksi.batches.qc.store', $batch), [
            'good_qty'   => 10,
            'reject_qty' => 0,
            'qc_date'    => now()->toDateString(),
        ]);
        $response->assertForbidden();
    }

    public function test_owner_cannot_perform_shipment_mutations(): void
    {
        $response = $this->actingAs($this->owner)->post(route('produksi.shipments.store'), [
            'order_id' => 1,
        ]);
        $response->assertForbidden();
    }

    public function test_owner_cannot_perform_payment_mutations(): void
    {
        $cust = Customer::create(['customer_name' => 'Cust F', 'phone' => '08123456784']);
        $order = Order::create([
            'customer_id'  => $cust->id,
            'order_no'     => 'ORD-202609-0095',
            'order_date'   => now()->toDateString(),
            'status'       => OrderStatus::DEAL,
            'total_qty'    => 10,
            'total_amount' => 500000,
        ]);
        $invoice = \App\Models\Sales\Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-202609-0095',
            'invoice_date' => now()->toDateString(),
            'total_amount' => 500000,
            'status'       => 'UNPAID',
        ]);

        $response = $this->actingAs($this->owner)->post(route('admin.invoices.payments.store', $invoice), [
            'payment_type'   => 'DP',
            'amount'         => 100000,
            'payment_date'   => now()->toDateString(),
            'payment_method' => 'Transfer',
        ]);
        $response->assertForbidden();
    }

    public function test_owner_dashboard_with_no_pending_po_renders_successfully(): void
    {
        $response = $this->actingAs($this->owner)->get(route('owner.dashboard'));
        $response->assertOk();
        $response->assertSee('Tidak ada tindakan.');
    }

    public function test_owner_dashboard_with_pending_approval_po_displays_review_action_with_canonical_route(): void
    {
        $supplier = Supplier::create([
            'supplier_name' => 'Supplier Kain Jaya',
            'active'        => true,
        ]);
        $unit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $mat = Material::create(['material_name' => 'Kain Toyobo', 'unit_id' => $unit->id, 'cost_per_unit' => 12000]);

        $po = Purchase::create([
            'purchase_no'     => 'PO-202610-0088',
            'supplier_id'     => $supplier->id,
            'purchase_date'   => '2026-10-12',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::PENDING_APPROVAL,
            'submitted_at'    => now(),
            'submitted_by'    => $this->gudang->id,
        ]);

        PurchaseItem::create([
            'purchase_id' => $po->id,
            'material_id' => $mat->id,
            'qty'         => 100,
            'unit_price'  => 12000,
            'subtotal'    => 1200000,
        ]);

        // Scenario B: GET /owner/dashboard returns HTTP 200 without PO approval queue
        $dashboardResponse = $this->actingAs($this->owner)->get(route('owner.dashboard'));
        $dashboardResponse->assertOk();
        // Owner no longer receives PO approval queue in final workflow
        $dashboardResponse->assertDontSee('PO-202610-0088');
    }

    public function test_owner_cannot_access_admin_purchase_approval_routes(): void
    {
        $supplier = Supplier::create(['supplier_name' => 'Supplier Kain Jaya', 'active' => true]);
        $po = Purchase::create([
            'purchase_no'     => 'PO-202610-0089',
            'supplier_id'     => $supplier->id,
            'purchase_date'   => '2026-10-12',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::PENDING_APPROVAL,
            'submitted_at'    => now(),
        ]);

        // OWNER cannot access Admin purchase approval route (403 Forbidden)
        $this->actingAs($this->owner)
            ->get(route('admin.purchases.approvals.show', $po))
            ->assertForbidden();

        // GUDANG, PRODUKSI, CUSTOMER cannot access (403)
        $this->actingAs($this->gudang)
            ->get(route('admin.purchases.approvals.show', $po))
            ->assertForbidden();

        $this->actingAs($this->produksi)
            ->get(route('admin.purchases.approvals.show', $po))
            ->assertForbidden();

        $this->actingAs($this->customer)
            ->get(route('admin.purchases.approvals.show', $po))
            ->assertForbidden();

        // ADMIN can access (200)
        $this->actingAs($this->admin)
            ->get(route('admin.purchases.approvals.show', $po))
            ->assertOk();
    }
}
