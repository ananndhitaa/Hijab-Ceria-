<?php

namespace Tests\Feature\Admin;

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
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardActionDeduplicationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $gudang;
    private Customer $customer;
    private Supplier $supplier;
    private ProductVariant $variant;
    private Material $material;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role'   => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
            'name'   => 'Admin Nayara',
        ]);

        $this->gudang = User::factory()->create([
            'role'   => UserRole::GUDANG,
            'status' => UserStatus::ACTIVE,
            'name'   => 'Staff Gudang',
        ]);

        $this->customer = Customer::create([
            'customer_name' => 'Toko Cinay Hijab',
            'phone'         => '081234567890',
        ]);

        $this->supplier = Supplier::create([
            'supplier_name' => 'PT Sumber Kain',
            'active'        => true,
        ]);

        $unit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $product = Product::create(['product_name' => 'Hijab Segiempat Voal']);
        $this->variant = ProductVariant::create([
            'product_id'         => $product->id,
            'variant_name'       => 'Navy Blue',
            'sku'                => 'HJB-VOAL-NVY-001',
            'base_selling_price' => '85000.00',
        ]);

        $this->material = Material::create([
            'unit_id'       => $unit->id,
            'material_name' => 'Kain Voal Ultrafine',
            'min_stock'     => '10.0000',
            'active'        => true,
        ]);
    }

    private function createPendingPo(string $poNo = 'PO-202610-0005'): Purchase
    {
        $po = Purchase::create([
            'supplier_id'     => $this->supplier->id,
            'purchase_no'     => $poNo,
            'purchase_date'   => '2026-10-15',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::PENDING_APPROVAL,
            'submitted_at'    => now(),
            'submitted_by'    => $this->gudang->id,
        ]);

        PurchaseItem::create([
            'purchase_id'  => $po->id,
            'material_id'  => $this->material->id,
            'qty'          => '50.0000',
            'unit_price'   => '30000.00',
            'qty_received' => '0.0000',
        ]);

        return $po;
    }

    private function createKomunikasiOrder(string $soNo = 'SO-202610-0002'): Order
    {
        $order = Order::create([
            'order_no'     => $soNo,
            'customer_id'  => $this->customer->id,
            'order_date'   => '2026-10-15',
            'status'       => OrderStatus::KOMUNIKASI,
            'total_amount' => '850000.00',
            'created_by'   => $this->admin->id,
        ]);

        OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => '85000.00',
            'subtotal'           => '850000.00',
        ]);

        return $order;
    }

    /**
     * 1. Admin dashboard renders "Perlu Tindakan".
     */
    public function test_01_admin_dashboard_renders_perlu_tindakan(): void
    {
        $this->createPendingPo();
        $this->createKomunikasiOrder();

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Perlu Tindakan');
    }

    /**
     * 2. Admin dashboard DOES NOT render "Antrean Aksi Cepat".
     */
    public function test_02_admin_dashboard_does_not_render_antrean_aksi_cepat(): void
    {
        $this->createPendingPo();
        $this->createKomunikasiOrder();

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertDontSee('Antrean Aksi Cepat');
    }

    /**
     * 3. Pending PO appears exactly once.
     */
    public function test_03_pending_po_appears_exactly_once(): void
    {
        $po = $this->createPendingPo('PO-202610-0005');

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $html = $response->getContent();

        // PO should only be listed once across the entire page
        $this->assertSame(1, substr_count($html, $po->purchase_no));
    }

    /**
     * 4. Waiting Deal Sales Order appears in Perlu Tindakan action queue.
     */
    public function test_04_waiting_deal_sales_order_appears_in_perlu_tindakan(): void
    {
        $order = $this->createKomunikasiOrder('SO-202610-0002');

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Menunggu Deal');
        $response->assertSee($order->order_no);
        $response->assertSee($this->customer->customer_name);
    }

    /**
     * 5. PO reference displayed.
     */
    public function test_05_po_reference_displayed(): void
    {
        $po = $this->createPendingPo('PO-202610-0005');

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('PO-202610-0005');
        $response->assertSee('Menunggu Persetujuan');
    }

    /**
     * 6. Supplier displayed.
     */
    public function test_06_supplier_displayed(): void
    {
        $this->createPendingPo('PO-202610-0005');

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('PT Sumber Kain');
    }

    /**
     * 7. Sales Order reference displayed.
     */
    public function test_07_sales_order_reference_displayed(): void
    {
        $this->createKomunikasiOrder('SO-202610-0002');

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('SO-202610-0002');
    }

    /**
     * 8. Customer displayed.
     */
    public function test_08_customer_displayed(): void
    {
        $this->createKomunikasiOrder('SO-202610-0002');

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Toko Cinay Hijab');
    }

    /**
     * 9. Review route correct for PO.
     */
    public function test_09_review_route_correct_for_po(): void
    {
        $po = $this->createPendingPo('PO-202610-0005');

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee(route('admin.purchases.approvals.show', $po));
    }

    /**
     * 10. Review route correct for Sales Order.
     */
    public function test_10_review_route_correct_for_sales_order(): void
    {
        $order = $this->createKomunikasiOrder('SO-202610-0002');

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee(route('admin.orders.show', $order));
    }

    /**
     * 11. Header action count equals actual action records.
     */
    public function test_11_header_action_count_equals_actual_action_records(): void
    {
        $this->createPendingPo('PO-202610-0005');
        $this->createKomunikasiOrder('SO-202610-0002');

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        // 1 PO + 1 SO = 2 item
        $response->assertSee('2 item');
    }

    /**
     * 12. Multiple records counted individually.
     */
    public function test_12_multiple_records_counted_individually(): void
    {
        // 3 Pending POs
        $this->createPendingPo('PO-202610-0001');
        $this->createPendingPo('PO-202610-0002');
        $this->createPendingPo('PO-202610-0003');

        // 2 Pending SOs
        $this->createKomunikasiOrder('SO-202610-0001');
        $this->createKomunikasiOrder('SO-202610-0002');

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        // 3 PO + 2 SO = 5 item
        $response->assertSee('5 item');
        $response->assertSee('PO-202610-0001');
        $response->assertSee('PO-202610-0002');
        $response->assertSee('PO-202610-0003');
        $response->assertSee('SO-202610-0001');
        $response->assertSee('SO-202610-0002');
    }

    /**
     * 13. No-action state renders cleanly.
     */
    public function test_13_no_action_state_renders_cleanly(): void
    {
        // No pending actions
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Perlu Tindakan');
        $response->assertSee('Tidak ada tindakan yang perlu ditangani.');
        $response->assertDontSee('0 item');
        $response->assertDontSee('Antrean Aksi Cepat');
    }

    /**
     * 14. Existing dashboard KPIs unchanged.
     */
    public function test_14_existing_dashboard_kpis_unchanged(): void
    {
        $this->createKomunikasiOrder('SO-202610-0001');

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Total Order');
        $response->assertSee('Order Aktif');
        $response->assertSee('Menunggu Deal');
        $response->assertSee('Menunggu Batch');
        $response->assertSee('Belum Lunas');
        $response->assertSee('Sisa Tagihan');
        $response->assertViewHas('totalOrders', 1);
        $response->assertViewHas('activeOrders', 1);
        $response->assertViewHas('komunikasiOrdersCount', 1);
    }
}
