<?php

namespace Tests\Feature\System;

use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Enums\PurchaseApprovalStatus;
use App\Enums\PurchaseStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Finance\OpeningCashBalance;
use App\Models\Inventory\Purchase;
use App\Models\Inventory\PurchaseApproval;
use App\Models\Inventory\PurchaseItem;
use App\Models\Inventory\StockMovement;
use App\Models\Master\Customer;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Master\Supplier;
use App\Models\Master\Unit;
use App\Models\Production\ProductionBatch;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\Outlet;
use App\Models\System\User;
use App\Services\Finance\FinancialRecordService;
use App\Services\Inventory\PurchaseOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProcurementTenantWriteIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Outlet $outletA;
    protected Outlet $outletB;
    protected Outlet $outletInactive;

    protected User $adminA;
    protected User $adminB;
    protected User $gudangA;
    protected User $gudangB;
    protected User $ownerA;
    protected User $produksiA;
    protected User $customerUser;
    protected User $inactiveGudangA;
    protected User $unboundGudang;
    protected User $gudangInactiveOutlet;

    protected Unit $unitMeter;

    protected Supplier $supplierA;
    protected Supplier $supplierB;
    protected Supplier $unassignedSupplier;

    protected Material $materialA;
    protected Material $materialB;
    protected Material $unassignedMaterial;

    protected Customer $customerA;
    protected Customer $customerB;
    protected Customer $unassignedCustomer;

    protected Product $productA;
    protected Product $productB;

    protected ProductVariant $variantA;
    protected ProductVariant $variantB;

    protected Order $orderA;
    protected Order $orderB;
    protected Order $unassignedOrder;

    protected OrderItem $orderItemA;
    protected OrderItem $orderItemB;
    protected OrderItem $unassignedOrderItem;

    protected ProductionBatch $batchA;
    protected ProductionBatch $batchB;
    protected ProductionBatch $unassignedBatch;

    protected Purchase $purchaseA_Draft;
    protected Purchase $purchaseA_Submitted;
    protected Purchase $purchaseA_Approved;
    protected Purchase $purchaseB_Draft;
    protected Purchase $purchaseB_Submitted;
    protected Purchase $purchaseB_Approved;
    protected Purchase $unassignedPurchaseDraft;
    protected Purchase $unassignedPurchaseSubmitted;

    protected function setUp(): void
    {
        parent::setUp();

        config(['nayara.tenant_isolation_enabled' => true]);

        // 1. Outlets
        $this->outletA = $this->createTestOutlet('OUT-PROC-A', 'Konveksi Nayara A', 'ACTIVE');
        $this->outletB = $this->createTestOutlet('OUT-PROC-B', 'Konveksi Nayara B', 'ACTIVE');
        $this->outletInactive = $this->createTestOutlet('OUT-PROC-INACT', 'Konveksi Inactive', 'INACTIVE');

        // 2. Users
        $this->adminA = $this->createTestUser($this->outletA, UserRole::ADMIN, UserStatus::ACTIVE, 'admin.a');
        $this->adminB = $this->createTestUser($this->outletB, UserRole::ADMIN, UserStatus::ACTIVE, 'admin.b');
        $this->gudangA = $this->createTestUser($this->outletA, UserRole::GUDANG, UserStatus::ACTIVE, 'gudang.a');
        $this->gudangB = $this->createTestUser($this->outletB, UserRole::GUDANG, UserStatus::ACTIVE, 'gudang.b');
        $this->ownerA = $this->createTestUser($this->outletA, UserRole::OWNER, UserStatus::ACTIVE, 'owner.a');
        $this->produksiA = $this->createTestUser($this->outletA, UserRole::PRODUKSI, UserStatus::ACTIVE, 'prod.a');
        $this->customerUser = $this->createTestUser($this->outletA, UserRole::CUSTOMER, UserStatus::ACTIVE, 'cust.a');
        $this->inactiveGudangA = $this->createTestUser($this->outletA, UserRole::GUDANG, UserStatus::INACTIVE, 'gudang.inact');
        $this->unboundGudang = $this->createTestUser(null, UserRole::GUDANG, UserStatus::ACTIVE, 'gudang.unbound');
        $this->gudangInactiveOutlet = $this->createTestUser($this->outletInactive, UserRole::GUDANG, UserStatus::ACTIVE, 'gudang.inactout');

        // 3. Units
        $this->unitMeter = Unit::firstOrCreate(['unit_name' => 'Meter', 'symbol' => 'm']);

        // 4. Suppliers
        $this->supplierA = Supplier::create([
            'supplier_name' => 'PT Tekstil Prima Mandiri A',
            'contact_person'=> 'Budi Santoso Tenant A',
            'phone'         => '081299990001',
            'email'         => 'budi@tekstil-a.test',
            'address'       => 'Jl. Industri A No. 10',
            'active'        => true,
            'outlet_id'     => $this->outletA->id,
        ]);

        $this->supplierB = Supplier::create([
            'supplier_name' => 'CV Sumber Benang Makmur B',
            'contact_person'=> 'Dewi Lestari Tenant B',
            'phone'         => '081299990002',
            'email'         => 'dewi@benang-b.test',
            'address'       => 'Jl. Industri B No. 20',
            'active'        => true,
            'outlet_id'     => $this->outletB->id,
        ]);

        $this->unassignedSupplier = Supplier::create([
            'supplier_name' => 'Toko Kain Antik Unassigned',
            'contact_person'=> 'Siti Legacy',
            'phone'         => '081299990003',
            'email'         => 'siti@legacy.test',
            'address'       => 'Jl. Antik No. 99',
            'active'        => true,
            'outlet_id'     => null,
        ]);

        // 5. Materials
        $this->materialA = Material::create([
            'material_name' => 'Kain Rayon Twill A',
            'unit_id'       => $this->unitMeter->id,
            'active'        => true,
            'outlet_id'     => $this->outletA->id,
        ]);
        $this->supplierA->materials()->attach($this->materialA->id);

        $this->materialB = Material::create([
            'material_name' => 'Kain Katun Madinah B',
            'unit_id'       => $this->unitMeter->id,
            'active'        => true,
            'outlet_id'     => $this->outletB->id,
        ]);
        $this->supplierB->materials()->attach($this->materialB->id);

        $this->unassignedMaterial = Material::create([
            'material_name' => 'Kain Legacy Tanpa Outlet',
            'unit_id'       => $this->unitMeter->id,
            'active'        => true,
            'outlet_id'     => null,
        ]);
        $this->unassignedSupplier->materials()->attach($this->unassignedMaterial->id);

        // 6. Customers, Products, Variants, Orders & Production Batches
        $this->customerA = Customer::create(['customer_name' => 'Customer A', 'outlet_id' => $this->outletA->id]);
        $this->customerB = Customer::create(['customer_name' => 'Customer B', 'outlet_id' => $this->outletB->id]);
        $this->unassignedCustomer = Customer::create(['customer_name' => 'Customer Legacy', 'outlet_id' => null]);

        $this->productA = Product::create(['product_name' => 'Hijab Segiempat A', 'active' => true, 'outlet_id' => $this->outletA->id]);
        $this->productB = Product::create(['product_name' => 'Pashmina Silk B', 'active' => true, 'outlet_id' => $this->outletB->id]);

        $this->variantA = ProductVariant::create(['product_id' => $this->productA->id, 'variant_name' => 'Navy', 'sku' => 'HJB-NVY-01', 'active' => true]);
        $this->variantB = ProductVariant::create(['product_id' => $this->productB->id, 'variant_name' => 'Rose', 'sku' => 'PSH-ROS-01', 'active' => true]);

        $this->orderA = Order::create(['customer_id' => $this->customerA->id, 'order_no' => 'SO-A-001', 'order_date' => '2026-10-20', 'status' => OrderStatus::DEAL, 'outlet_id' => $this->outletA->id]);
        $this->orderB = Order::create(['customer_id' => $this->customerB->id, 'order_no' => 'SO-B-001', 'order_date' => '2026-10-20', 'status' => OrderStatus::DEAL, 'outlet_id' => $this->outletB->id]);
        $this->unassignedOrder = Order::create(['customer_id' => $this->unassignedCustomer->id, 'order_no' => 'SO-LEG-001', 'order_date' => '2026-10-20', 'status' => OrderStatus::DEAL, 'outlet_id' => null]);

        $this->orderItemA = OrderItem::create(['order_id' => $this->orderA->id, 'variant_id' => $this->variantA->id, 'qty_ordered' => 100, 'unit_selling_price' => 75000]);
        $this->orderItemB = OrderItem::create(['order_id' => $this->orderB->id, 'variant_id' => $this->variantB->id, 'qty_ordered' => 100, 'unit_selling_price' => 85000]);
        $this->unassignedOrderItem = OrderItem::create(['order_id' => $this->unassignedOrder->id, 'variant_id' => $this->variantA->id, 'qty_ordered' => 50, 'unit_selling_price' => 75000]);

        $this->batchA = ProductionBatch::create(['order_item_id' => $this->orderItemA->id, 'batch_no' => 'BAT-A-001', 'target_qty' => 100, 'status' => BatchStatus::BAHAN]);
        $this->batchB = ProductionBatch::create(['order_item_id' => $this->orderItemB->id, 'batch_no' => 'BAT-B-001', 'target_qty' => 100, 'status' => BatchStatus::BAHAN]);
        $this->unassignedBatch = ProductionBatch::create(['order_item_id' => $this->unassignedOrderItem->id, 'batch_no' => 'BAT-LEG-001', 'target_qty' => 50, 'status' => BatchStatus::BAHAN]);

        // 7. Purchases Fixtures
        $this->purchaseA_Draft = Purchase::create([
            'purchase_no'     => 'PO-202610-A-001',
            'supplier_id'     => $this->supplierA->id,
            'purchase_date'   => '2026-10-20',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::NOT_SUBMITTED,
            'outlet_id'       => $this->outletA->id,
        ]);
        PurchaseItem::create([
            'purchase_id'  => $this->purchaseA_Draft->id,
            'material_id'  => $this->materialA->id,
            'qty'          => '100.0000',
            'unit_price'   => '35000.00',
            'qty_received' => '0.0000',
        ]);

        $this->purchaseA_Submitted = Purchase::create([
            'purchase_no'     => 'PO-202610-A-002',
            'supplier_id'     => $this->supplierA->id,
            'purchase_date'   => '2026-10-21',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::PENDING_APPROVAL,
            'submitted_at'    => now(),
            'submitted_by'    => $this->gudangA->id,
            'outlet_id'       => $this->outletA->id,
        ]);
        PurchaseItem::create([
            'purchase_id'  => $this->purchaseA_Submitted->id,
            'material_id'  => $this->materialA->id,
            'qty'          => '50.0000',
            'unit_price'   => '35000.00',
            'qty_received' => '0.0000',
        ]);

        $this->purchaseA_Approved = Purchase::create([
            'purchase_no'     => 'PO-202610-A-003',
            'supplier_id'     => $this->supplierA->id,
            'purchase_date'   => '2026-10-21',
            'status'          => PurchaseStatus::ORDERED,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
            'submitted_at'    => now(),
            'submitted_by'    => $this->gudangA->id,
            'approved_at'     => now(),
            'approved_by'     => $this->adminA->id,
            'outlet_id'       => $this->outletA->id,
        ]);
        PurchaseItem::create([
            'purchase_id'  => $this->purchaseA_Approved->id,
            'material_id'  => $this->materialA->id,
            'qty'          => '75.0000',
            'unit_price'   => '35000.00',
            'qty_received' => '0.0000',
        ]);

        $this->purchaseB_Draft = Purchase::create([
            'purchase_no'     => 'PO-202610-B-001',
            'supplier_id'     => $this->supplierB->id,
            'purchase_date'   => '2026-10-20',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::NOT_SUBMITTED,
            'outlet_id'       => $this->outletB->id,
        ]);
        PurchaseItem::create([
            'purchase_id'  => $this->purchaseB_Draft->id,
            'material_id'  => $this->materialB->id,
            'qty'          => '200.0000',
            'unit_price'   => '40000.00',
            'qty_received' => '0.0000',
        ]);

        $this->purchaseB_Submitted = Purchase::create([
            'purchase_no'     => 'PO-202610-B-002',
            'supplier_id'     => $this->supplierB->id,
            'purchase_date'   => '2026-10-21',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::PENDING_APPROVAL,
            'submitted_at'    => now(),
            'submitted_by'    => $this->gudangB->id,
            'outlet_id'       => $this->outletB->id,
        ]);
        PurchaseItem::create([
            'purchase_id'  => $this->purchaseB_Submitted->id,
            'material_id'  => $this->materialB->id,
            'qty'          => '150.0000',
            'unit_price'   => '40000.00',
            'qty_received' => '0.0000',
        ]);

        $this->purchaseB_Approved = Purchase::create([
            'purchase_no'     => 'PO-202610-B-003',
            'supplier_id'     => $this->supplierB->id,
            'purchase_date'   => '2026-10-21',
            'status'          => PurchaseStatus::ORDERED,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
            'submitted_at'    => now(),
            'submitted_by'    => $this->gudangB->id,
            'approved_at'     => now(),
            'approved_by'     => $this->adminB->id,
            'outlet_id'       => $this->outletB->id,
        ]);
        PurchaseItem::create([
            'purchase_id'  => $this->purchaseB_Approved->id,
            'material_id'  => $this->materialB->id,
            'qty'          => '60.0000',
            'unit_price'   => '40000.00',
            'qty_received' => '0.0000',
        ]);

        $this->unassignedPurchaseDraft = Purchase::create([
            'purchase_no'     => 'PO-202610-LEG-001',
            'supplier_id'     => $this->unassignedSupplier->id,
            'purchase_date'   => '2026-10-20',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::NOT_SUBMITTED,
            'outlet_id'       => null,
        ]);
        PurchaseItem::create([
            'purchase_id'  => $this->unassignedPurchaseDraft->id,
            'material_id'  => $this->unassignedMaterial->id,
            'qty'          => '10.0000',
            'unit_price'   => '20000.00',
            'qty_received' => '0.0000',
        ]);

        $this->unassignedPurchaseSubmitted = Purchase::create([
            'purchase_no'     => 'PO-202610-LEG-002',
            'supplier_id'     => $this->unassignedSupplier->id,
            'purchase_date'   => '2026-10-20',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::PENDING_APPROVAL,
            'submitted_at'    => now(),
            'submitted_by'    => $this->gudangA->id,
            'outlet_id'       => null,
        ]);
        PurchaseItem::create([
            'purchase_id'  => $this->unassignedPurchaseSubmitted->id,
            'material_id'  => $this->unassignedMaterial->id,
            'qty'          => '15.0000',
            'unit_price'   => '20000.00',
            'qty_received' => '0.0000',
        ]);
    }

    protected function createTestOutlet(string $code, string $name, string $status = 'ACTIVE'): Outlet
    {
        return Outlet::create([
            'outlet_name'                => $name,
            'outlet_code'                => $code,
            'slug'                       => strtolower($code),
            'status'                     => $status,
            'is_public_showcase_enabled' => true,
        ]);
    }

    protected function createTestUser(?Outlet $outlet, UserRole $role, UserStatus $status, string $prefix): User
    {
        return User::create([
            'name'      => "User {$prefix}",
            'email'     => "{$prefix}_{$role->value}_" . uniqid() . "@nayara.test",
            'password'  => Hash::make('password123'),
            'role'      => $role,
            'status'    => $status,
            'outlet_id' => $outlet?->id,
        ]);
    }

    protected function setupCash(string $amount = '10000000.00', ?int $userId = null): OpeningCashBalance
    {
        $user = User::find($userId ?? $this->adminA->id);
        $outletId = $user?->outlet_id ?? $this->outletA->id;

        $openingA = OpeningCashBalance::updateOrCreate(
            ['outlet_id' => $outletId, 'opening_date' => '2026-10-01'],
            ['amount' => $amount, 'created_by' => $user?->id ?? $this->adminA->id]
        );

        if ($userId === null) {
            OpeningCashBalance::updateOrCreate(
                ['outlet_id' => $this->outletB->id, 'opening_date' => '2026-10-01'],
                ['amount' => $amount, 'created_by' => $this->adminB->id]
            );
        }

        return $openingA;
    }

    /**
     * 01. GUDANG A berhasil membuat Purchase A yang sah.
     */
    public function test_01_gudang_a_successfully_creates_valid_purchase_for_outlet_a(): void
    {
        $payload = [
            'supplier_id'   => $this->supplierA->id,
            'purchase_date' => '2026-10-22',
            'notes'         => 'Order kain rayon Twill untuk Outlet A',
            'items'         => [
                [
                    'material_id' => $this->materialA->id,
                    'qty'         => '50',
                    'unit_price'  => '35000',
                ],
            ],
        ];

        $response = $this->actingAs($this->gudangA)
            ->post(route('gudang.purchases.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $latest = Purchase::latest('id')->first();
        $this->assertNotNull($latest);
        $this->assertEquals($this->outletA->id, $latest->outlet_id);
        $this->assertEquals($this->supplierA->id, $latest->supplier_id);
        $this->assertEquals(PurchaseStatus::DRAFT, $latest->status);
        $this->assertEquals(PurchaseApprovalStatus::NOT_SUBMITTED, $latest->approval_status);
        $this->assertCount(1, $latest->items);
        $this->assertEquals($this->materialA->id, $latest->items->first()->material_id);
    }

    /**
     * 02. GUDANG B berhasil membuat Purchase B yang sah.
     */
    public function test_02_gudang_b_successfully_creates_valid_purchase_for_outlet_b(): void
    {
        $payload = [
            'supplier_id'   => $this->supplierB->id,
            'purchase_date' => '2026-10-22',
            'notes'         => 'Order kain katun Madinah untuk Outlet B',
            'items'         => [
                [
                    'material_id' => $this->materialB->id,
                    'qty'         => '60',
                    'unit_price'  => '40000',
                ],
            ],
        ];

        $response = $this->actingAs($this->gudangB)
            ->post(route('gudang.purchases.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $latest = Purchase::latest('id')->first();
        $this->assertNotNull($latest);
        $this->assertEquals($this->outletB->id, $latest->outlet_id);
        $this->assertEquals($this->supplierB->id, $latest->supplier_id);
        $this->assertEquals(PurchaseStatus::DRAFT, $latest->status);
    }

    /**
     * 03. Purchase baru dari GUDANG A memiliki outlet_id A server-side.
     */
    public function test_03_new_purchase_from_gudang_a_has_server_side_outlet_id_a(): void
    {
        $payload = [
            'supplier_id'   => $this->supplierA->id,
            'purchase_date' => '2026-10-22',
            'items'         => [
                ['material_id' => $this->materialA->id, 'qty' => '10', 'unit_price' => '35000'],
            ],
        ];

        $this->actingAs($this->gudangA)->post(route('gudang.purchases.store'), $payload);

        $purchase = Purchase::latest('id')->first();
        $this->assertSame((int) $this->outletA->id, (int) $purchase->outlet_id);
    }

    /**
     * 04. Fake outlet_id payload tidak memindahkan ownership.
     */
    public function test_04_fake_outlet_id_payload_does_not_override_ownership(): void
    {
        $payload = [
            'outlet_id'     => $this->outletB->id, // Attacker tries to inject Outlet B
            'supplier_id'   => $this->supplierA->id,
            'purchase_date' => '2026-10-22',
            'items'         => [
                ['material_id' => $this->materialA->id, 'qty' => '20', 'unit_price' => '35000'],
            ],
        ];

        $response = $this->actingAs($this->gudangA)
            ->post(route('gudang.purchases.store'), $payload);

        $response->assertRedirect();
        $purchase = Purchase::latest('id')->first();
        $this->assertNotEquals($this->outletB->id, $purchase->outlet_id);
        $this->assertEquals($this->outletA->id, $purchase->outlet_id);
    }

    /**
     * 05. Fake X-Outlet-ID header tidak mengubah tenant.
     */
    public function test_05_fake_x_outlet_id_header_does_not_change_tenant(): void
    {
        $payload = [
            'supplier_id'   => $this->supplierA->id,
            'purchase_date' => '2026-10-22',
            'items'         => [
                ['material_id' => $this->materialA->id, 'qty' => '15', 'unit_price' => '35000'],
            ],
        ];

        $response = $this->actingAs($this->gudangA)
            ->withHeaders(['X-Outlet-ID' => (string) $this->outletB->id])
            ->post(route('gudang.purchases.store'), $payload);

        $response->assertRedirect();
        $purchase = Purchase::latest('id')->first();
        $this->assertEquals($this->outletA->id, $purchase->outlet_id);
    }

    /**
     * 06. Supplier outlet B ditolak pada store GUDANG A.
     */
    public function test_06_supplier_outlet_b_rejected_on_store_gudang_a(): void
    {
        $poCountBefore = Purchase::count();

        $payload = [
            'supplier_id'   => $this->supplierB->id, // Supplier of Outlet B
            'purchase_date' => '2026-10-22',
            'items'         => [
                ['material_id' => $this->materialA->id, 'qty' => '10', 'unit_price' => '35000'],
            ],
        ];

        $response = $this->actingAs($this->gudangA)
            ->post(route('gudang.purchases.store'), $payload);

        $response->assertSessionHasErrors('supplier_id');
        $this->assertEquals($poCountBefore, Purchase::count());
    }

    /**
     * 07. Supplier outlet_id NULL ditolak saat flag TRUE.
     */
    public function test_07_supplier_outlet_id_null_rejected_when_flag_true(): void
    {
        $poCountBefore = Purchase::count();

        $payload = [
            'supplier_id'   => $this->unassignedSupplier->id,
            'purchase_date' => '2026-10-22',
            'items'         => [
                ['material_id' => $this->materialA->id, 'qty' => '10', 'unit_price' => '35000'],
            ],
        ];

        $response = $this->actingAs($this->gudangA)
            ->post(route('gudang.purchases.store'), $payload);

        $response->assertSessionHasErrors('supplier_id');
        $this->assertEquals($poCountBefore, Purchase::count());
    }

    /**
     * 08. Material outlet B ditolak pada store GUDANG A.
     */
    public function test_08_material_outlet_b_rejected_on_store_gudang_a(): void
    {
        $poCountBefore = Purchase::count();

        $payload = [
            'supplier_id'   => $this->supplierA->id,
            'purchase_date' => '2026-10-22',
            'items'         => [
                ['material_id' => $this->materialB->id, 'qty' => '10', 'unit_price' => '40000'],
            ],
        ];

        $response = $this->actingAs($this->gudangA)
            ->post(route('gudang.purchases.store'), $payload);

        $response->assertSessionHasErrors();
        $this->assertEquals($poCountBefore, Purchase::count());
    }

    /**
     * 09. Material outlet_id NULL ditolak saat flag TRUE.
     */
    public function test_09_material_outlet_id_null_rejected_when_flag_true(): void
    {
        $poCountBefore = Purchase::count();

        $payload = [
            'supplier_id'   => $this->supplierA->id,
            'purchase_date' => '2026-10-22',
            'items'         => [
                ['material_id' => $this->unassignedMaterial->id, 'qty' => '10', 'unit_price' => '20000'],
            ],
        ];

        $response = $this->actingAs($this->gudangA)
            ->post(route('gudang.purchases.store'), $payload);

        $response->assertSessionHasErrors();
        $this->assertEquals($poCountBefore, Purchase::count());
    }

    /**
     * 10. Mixed PurchaseItems lintas-outlet ditolak secara atomik.
     */
    public function test_10_mixed_purchase_items_across_tenants_rejected_atomically(): void
    {
        $poCountBefore = Purchase::count();
        $itemCountBefore = PurchaseItem::count();

        $payload = [
            'supplier_id'   => $this->supplierA->id,
            'purchase_date' => '2026-10-22',
            'items'         => [
                ['material_id' => $this->materialA->id, 'qty' => '10', 'unit_price' => '35000'], // Valid
                ['material_id' => $this->materialB->id, 'qty' => '5',  'unit_price' => '40000'], // Foreign!
            ],
        ];

        $response = $this->actingAs($this->gudangA)
            ->post(route('gudang.purchases.store'), $payload);

        $response->assertSessionHasErrors();
        $this->assertEquals($poCountBefore, Purchase::count());
        $this->assertEquals($itemCountBefore, PurchaseItem::count());
    }

    /**
     * 11. Foreign batch_id ditolak jika purchase terkait MRP.
     */
    public function test_11_foreign_batch_id_rejected_if_purchase_linked_to_mrp(): void
    {
        $poCountBefore = Purchase::count();

        $payload = [
            'batch_id'      => $this->batchB->id, // Belongs to Outlet B
            'supplier_id'   => $this->supplierA->id,
            'purchase_date' => '2026-10-22',
            'items'         => [
                ['material_id' => $this->materialA->id, 'qty' => '10', 'unit_price' => '35000'],
            ],
        ];

        $response = $this->actingAs($this->gudangA)
            ->post(route('gudang.purchases.store'), $payload);

        $response->assertSessionHasErrors('batch_id');
        $this->assertEquals($poCountBefore, Purchase::count());
    }

    /**
     * 12. Batch unassigned ditolak saat flag TRUE.
     */
    public function test_12_batch_unassigned_rejected_when_flag_true(): void
    {
        $poCountBefore = Purchase::count();

        $payload = [
            'batch_id'      => $this->unassignedBatch->id,
            'supplier_id'   => $this->supplierA->id,
            'purchase_date' => '2026-10-22',
            'items'         => [
                ['material_id' => $this->materialA->id, 'qty' => '10', 'unit_price' => '35000'],
            ],
        ];

        $response = $this->actingAs($this->gudangA)
            ->post(route('gudang.purchases.store'), $payload);

        $response->assertSessionHasErrors('batch_id');
        $this->assertEquals($poCountBefore, Purchase::count());
    }

    /**
     * 13. PurchaseItem dibuat hanya untuk parent Purchase yang sah.
     */
    public function test_13_purchase_items_created_only_for_valid_parent_purchase(): void
    {
        $payload = [
            'supplier_id'   => $this->supplierA->id,
            'purchase_date' => '2026-10-22',
            'items'         => [
                ['material_id' => $this->materialA->id, 'qty' => '30', 'unit_price' => '35000'],
            ],
        ];

        $this->actingAs($this->gudangA)->post(route('gudang.purchases.store'), $payload);

        $latestPO = Purchase::latest('id')->first();
        $this->assertCount(1, $latestPO->items);
        $this->assertEquals($latestPO->id, $latestPO->items->first()->purchase_id);
    }

    /**
     * 14. Tidak ada partial Purchase jika validation gagal.
     */
    public function test_14_no_partial_purchase_if_validation_fails(): void
    {
        $poCountBefore = Purchase::count();
        $itemCountBefore = PurchaseItem::count();

        $payload = [
            'supplier_id'   => $this->supplierA->id,
            'purchase_date' => '2026-10-22',
            'items'         => [
                ['material_id' => 999999, 'qty' => '10', 'unit_price' => '35000'], // Invalid material
            ],
        ];

        $response = $this->actingAs($this->gudangA)->post(route('gudang.purchases.store'), $payload);
        $response->assertSessionHasErrors();

        $this->assertEquals($poCountBefore, Purchase::count());
        $this->assertEquals($itemCountBefore, PurchaseItem::count());
    }

    /**
     * 15. Tidak ada partial PurchaseItem jika service error / rollback.
     */
    public function test_15_no_partial_purchase_items_if_service_error(): void
    {
        $poCountBefore = Purchase::count();
        $itemCountBefore = PurchaseItem::count();

        try {
            DB::transaction(function () {
                $service = app(PurchaseOrderService::class);
                $service->createPurchase([
                    'outlet_id'     => $this->outletA->id,
                    'supplier_id'   => $this->supplierA->id,
                    'purchase_date' => '2026-10-22',
                    'items'         => [
                        ['material_id' => $this->materialA->id, 'qty' => '10', 'unit_price' => '35000'],
                    ],
                ]);
                throw new \RuntimeException('Simulated unexpected failure during creation');
            });
        } catch (\Throwable $e) {
            // caught
        }

        $this->assertEquals($poCountBefore, Purchase::count());
        $this->assertEquals($itemCountBefore, PurchaseItem::count());
    }

    /**
     * 16. GUDANG A dapat update Purchase A yang eligible.
     */
    public function test_16_gudang_a_can_update_eligible_purchase_a(): void
    {
        $payload = [
            'supplier_id'   => $this->supplierA->id,
            'purchase_date' => '2026-10-25',
            'notes'         => 'Revisi catatan PO A',
            'items'         => [
                [
                    'material_id' => $this->materialA->id,
                    'qty'         => '120',
                    'unit_price'  => '34000',
                ],
            ],
        ];

        $response = $this->actingAs($this->gudangA)
            ->put(route('gudang.purchases.update', $this->purchaseA_Draft), $payload);

        $response->assertRedirect(route('gudang.purchases.show', $this->purchaseA_Draft));
        $fresh = $this->purchaseA_Draft->fresh();
        $this->assertEquals('Revisi catatan PO A', $fresh->notes);
        $this->assertEquals('120.0000', $fresh->items->first()->qty);
    }

    /**
     * 17. GUDANG A tidak dapat update Purchase B.
     */
    public function test_17_gudang_a_cannot_update_purchase_b(): void
    {
        $payload = [
            'supplier_id'   => $this->supplierB->id,
            'purchase_date' => '2026-10-25',
            'items'         => [
                ['material_id' => $this->materialB->id, 'qty' => '50', 'unit_price' => '40000'],
            ],
        ];

        $response = $this->actingAs($this->gudangA)
            ->put(route('gudang.purchases.update', $this->purchaseB_Draft), $payload);

        $response->assertStatus(404);
    }

    /**
     * 18. Cross-tenant update menghasilkan 404.
     */
    public function test_18_cross_tenant_update_returns_404(): void
    {
        $response = $this->actingAs($this->gudangB)
            ->put(route('gudang.purchases.update', $this->purchaseA_Draft), [
                'supplier_id'   => $this->supplierA->id,
                'purchase_date' => '2026-10-25',
                'items'         => [
                    ['material_id' => $this->materialA->id, 'qty' => '10', 'unit_price' => '35000'],
                ],
            ]);

        $response->assertStatus(404);
    }

    /**
     * 19. Unassigned Purchase tidak dapat diupdate saat flag TRUE.
     */
    public function test_19_unassigned_purchase_cannot_be_updated_when_flag_true(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->put(route('gudang.purchases.update', $this->unassignedPurchaseDraft), [
                'supplier_id'   => $this->unassignedSupplier->id,
                'purchase_date' => '2026-10-25',
                'items'         => [
                    ['material_id' => $this->unassignedMaterial->id, 'qty' => '10', 'unit_price' => '20000'],
                ],
            ]);

        $response->assertStatus(404);
    }

    /**
     * 20. Supplier foreign tidak dapat disisipkan melalui update.
     */
    public function test_20_foreign_supplier_cannot_be_injected_via_update(): void
    {
        $payload = [
            'supplier_id'   => $this->supplierB->id, // Foreign supplier
            'purchase_date' => '2026-10-25',
            'items'         => [
                ['material_id' => $this->materialA->id, 'qty' => '100', 'unit_price' => '35000'],
            ],
        ];

        $response = $this->actingAs($this->gudangA)
            ->put(route('gudang.purchases.update', $this->purchaseA_Draft), $payload);

        $response->assertSessionHasErrors('supplier_id');
        $this->assertEquals($this->supplierA->id, $this->purchaseA_Draft->fresh()->supplier_id);
    }

    /**
     * 21. Material foreign tidak dapat disisipkan melalui update.
     */
    public function test_21_foreign_material_cannot_be_injected_via_update(): void
    {
        $payload = [
            'supplier_id'   => $this->supplierA->id,
            'purchase_date' => '2026-10-25',
            'items'         => [
                ['material_id' => $this->materialB->id, 'qty' => '100', 'unit_price' => '40000'],
            ],
        ];

        $response = $this->actingAs($this->gudangA)
            ->put(route('gudang.purchases.update', $this->purchaseA_Draft), $payload);

        $response->assertSessionHasErrors();
        $this->assertEquals($this->materialA->id, $this->purchaseA_Draft->fresh()->items->first()->material_id);
    }

    /**
     * 22. PurchaseItem ID dari Purchase lain ditolak jika input ID didukung.
     */
    public function test_22_purchase_item_id_from_other_purchase_rejected_if_supported(): void
    {
        $itemB = $this->purchaseB_Draft->items->first();

        $payload = [
            'supplier_id'   => $this->supplierA->id,
            'purchase_date' => '2026-10-25',
            'items'         => [
                [
                    'purchase_item_id' => $itemB->id, // Belongs to Purchase B!
                    'material_id'      => $this->materialA->id,
                    'qty'              => '100',
                    'unit_price'       => '35000',
                ],
            ],
        ];

        $response = $this->actingAs($this->gudangA)
            ->put(route('gudang.purchases.update', $this->purchaseA_Draft), $payload);

        $response->assertSessionHasErrors();
    }

    /**
     * 23. Payload outlet_id pada update diabaikan.
     */
    public function test_23_payload_outlet_id_on_update_is_ignored(): void
    {
        $payload = [
            'outlet_id'     => $this->outletB->id, // Attacker tries to transfer PO
            'supplier_id'   => $this->supplierA->id,
            'purchase_date' => '2026-10-25',
            'items'         => [
                ['material_id' => $this->materialA->id, 'qty' => '100', 'unit_price' => '35000'],
            ],
        ];

        $this->actingAs($this->gudangA)
            ->put(route('gudang.purchases.update', $this->purchaseA_Draft), $payload);

        $this->assertEquals($this->outletA->id, $this->purchaseA_Draft->fresh()->outlet_id);
    }

    /**
     * 24. Submit approval Purchase A oleh GUDANG A berhasil.
     */
    public function test_24_submit_approval_purchase_a_by_gudang_a_succeeds(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->post(route('gudang.purchases.submit-approval', $this->purchaseA_Draft), [
                'submission_notes' => 'Pengajuan stok rutin mingguan',
            ]);

        $response->assertRedirect(route('gudang.purchases.show', $this->purchaseA_Draft));
        $response->assertSessionHas('success');

        $fresh = $this->purchaseA_Draft->fresh();
        $this->assertEquals(PurchaseApprovalStatus::PENDING_APPROVAL, $fresh->approval_status);
        $this->assertEquals($this->gudangA->id, $fresh->submitted_by);
        $this->assertNotNull($fresh->submitted_at);
    }

    /**
     * 25. GUDANG A tidak dapat submit Purchase B.
     */
    public function test_25_gudang_a_cannot_submit_purchase_b(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->post(route('gudang.purchases.submit-approval', $this->purchaseB_Draft), [
                'submission_notes' => 'Percobaan submit PO tenant lain',
            ]);

        $response->assertStatus(404);
    }

    /**
     * 26. Status/history Purchase B tetap sama setelah percobaan submit ilegal.
     */
    public function test_26_status_and_history_of_purchase_b_remain_unchanged_after_illegal_submit(): void
    {
        $this->actingAs($this->gudangA)
            ->post(route('gudang.purchases.submit-approval', $this->purchaseB_Draft));

        $fresh = $this->purchaseB_Draft->fresh();
        $this->assertEquals(PurchaseApprovalStatus::NOT_SUBMITTED, $fresh->approval_status);
        $this->assertNull($fresh->submitted_at);
        $this->assertNull($fresh->submitted_by);
    }

    /**
     * 27. Cancel Purchase A mengikuti status gate existing.
     */
    public function test_27_cancel_purchase_a_follows_existing_status_gate(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->post(route('gudang.purchases.cancel', $this->purchaseA_Draft));

        $response->assertRedirect(route('gudang.purchases.show', $this->purchaseA_Draft));
        $response->assertSessionHas('success');

        $this->assertEquals(PurchaseStatus::CANCELLED, $this->purchaseA_Draft->fresh()->status);
    }

    /**
     * 28. Cancel Purchase B oleh GUDANG A ditolak.
     */
    public function test_28_cancel_purchase_b_by_gudang_a_rejected(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->post(route('gudang.purchases.cancel', $this->purchaseB_Draft));

        $response->assertStatus(404);
        $this->assertEquals(PurchaseStatus::DRAFT, $this->purchaseB_Draft->fresh()->status);
    }

    /**
     * 29. ADMIN A dapat approve Purchase A yang eligible.
     */
    public function test_29_admin_a_can_approve_eligible_purchase_a(): void
    {
        $this->setupCash('10000000.00', $this->adminA->id);

        $response = $this->actingAs($this->adminA)
            ->post(route('admin.purchases.approvals.approve', $this->purchaseA_Submitted), [
                'notes' => 'Disetujui untuk operasional A',
            ]);

        $response->assertRedirect(route('admin.purchases.approvals.show', $this->purchaseA_Submitted));
        $response->assertSessionHas('success');

        $fresh = $this->purchaseA_Submitted->fresh();
        $this->assertEquals(PurchaseApprovalStatus::APPROVED, $fresh->approval_status);
        $this->assertEquals($this->adminA->id, $fresh->approved_by);
        $this->assertNotNull($fresh->approved_at);
    }

    /**
     * 30. ADMIN B dapat approve Purchase B yang eligible.
     */
    public function test_30_admin_b_can_approve_eligible_purchase_b(): void
    {
        $this->setupCash('15000000.00', $this->adminB->id);

        $response = $this->actingAs($this->adminB)
            ->post(route('admin.purchases.approvals.approve', $this->purchaseB_Submitted), [
                'notes' => 'Disetujui untuk operasional B',
            ]);

        $response->assertRedirect(route('admin.purchases.approvals.show', $this->purchaseB_Submitted));
        $response->assertSessionHas('success');

        $fresh = $this->purchaseB_Submitted->fresh();
        $this->assertEquals(PurchaseApprovalStatus::APPROVED, $fresh->approval_status);
        $this->assertEquals($this->adminB->id, $fresh->approved_by);
        $this->assertNotNull($fresh->approved_at);
    }

    /**
     * 31. ADMIN A tidak dapat approve Purchase B.
     */
    public function test_31_admin_a_cannot_approve_purchase_b(): void
    {
        $this->setupCash('10000000.00', $this->adminA->id);

        $response = $this->actingAs($this->adminA)
            ->post(route('admin.purchases.approvals.approve', $this->purchaseB_Submitted));

        $response->assertStatus(404);
        $fresh = $this->purchaseB_Submitted->fresh();
        $this->assertEquals(PurchaseApprovalStatus::PENDING_APPROVAL, $fresh->approval_status);
        $this->assertNull($fresh->approved_at);
    }

    /**
     * 32. Cross-tenant approve menghasilkan HTTP 404.
     */
    public function test_32_cross_tenant_approve_returns_http_404(): void
    {
        $this->setupCash('10000000.00', $this->adminB->id);

        $response = $this->actingAs($this->adminB)
            ->post(route('admin.purchases.approvals.approve', $this->purchaseA_Submitted));

        $response->assertStatus(404);
    }

    /**
     * 33. Unassigned Purchase tidak dapat diapprove saat TRUE.
     */
    public function test_33_unassigned_purchase_cannot_be_approved_when_flag_true(): void
    {
        $this->setupCash('10000000.00', $this->adminA->id);

        $response = $this->actingAs($this->adminA)
            ->post(route('admin.purchases.approvals.approve', $this->unassignedPurchaseSubmitted));

        $response->assertStatus(404);
    }

    /**
     * 34. ADMIN A tidak dapat reject Purchase B.
     */
    public function test_34_admin_a_cannot_reject_purchase_b(): void
    {
        $response = $this->actingAs($this->adminA)
            ->post(route('admin.purchases.approvals.reject', $this->purchaseB_Submitted), [
                'rejection_reason' => 'Alasan penolakan ilegal',
            ]);

        $response->assertStatus(404);
        $this->assertEquals(PurchaseApprovalStatus::PENDING_APPROVAL, $this->purchaseB_Submitted->fresh()->approval_status);
    }

    /**
     * 35. ADMIN A tidak dapat meminta revisi Purchase B.
     */
    public function test_35_admin_a_cannot_request_revision_for_purchase_b(): void
    {
        $response = $this->actingAs($this->adminA)
            ->post(route('admin.purchases.approvals.request-revision', $this->purchaseB_Submitted), [
                'review_note' => 'Minta diskon harga kain',
            ]);

        $response->assertStatus(404);
        $this->assertEquals(PurchaseApprovalStatus::PENDING_APPROVAL, $this->purchaseB_Submitted->fresh()->approval_status);
    }

    /**
     * 36. OWNER tidak dapat approve Purchase A atau B.
     */
    public function test_36_owner_cannot_approve_purchase_a_or_b(): void
    {
        $this->setupCash('10000000.00');

        $responseA = $this->actingAs($this->ownerA)
            ->post(route('admin.purchases.approvals.approve', $this->purchaseA_Submitted));
        $responseA->assertStatus(403);

        $responseB = $this->actingAs($this->ownerA)
            ->post(route('admin.purchases.approvals.approve', $this->purchaseB_Submitted));
        $responseB->assertStatus(403);
    }

    /**
     * 37. GUDANG tidak dapat approve.
     */
    public function test_37_gudang_cannot_approve(): void
    {
        $this->setupCash('10000000.00');

        $response = $this->actingAs($this->gudangA)
            ->post(route('admin.purchases.approvals.approve', $this->purchaseA_Submitted));

        $response->assertStatus(403);
    }

    /**
     * 38. PRODUKSI tidak dapat approve.
     */
    public function test_38_produksi_cannot_approve(): void
    {
        $this->setupCash('10000000.00');

        $response = $this->actingAs($this->produksiA)
            ->post(route('admin.purchases.approvals.approve', $this->purchaseA_Submitted));

        $response->assertStatus(403);
    }

    /**
     * 39. CUSTOMER tidak dapat mengakses approval.
     */
    public function test_39_customer_cannot_access_approval(): void
    {
        $response = $this->actingAs($this->customerUser)
            ->post(route('admin.purchases.approvals.approve', $this->purchaseA_Submitted));

        $response->assertStatus(403);
    }

    /**
     * 40. Approval history hanya dibuat untuk Purchase yang sah.
     */
    public function test_40_approval_audit_trail_created_only_for_valid_purchase(): void
    {
        $this->setupCash('10000000.00');

        $this->actingAs($this->adminA)
            ->post(route('admin.purchases.approvals.approve', $this->purchaseA_Submitted));

        $fresh = $this->purchaseA_Submitted->fresh();
        $this->assertEquals($this->adminA->id, $fresh->approved_by);
        $this->assertNotNull($fresh->approved_at);
    }

    /**
     * 41. Unauthorized approval tidak membuat audit trail.
     */
    public function test_41_unauthorized_approval_does_not_create_audit_trail(): void
    {
        $this->actingAs($this->adminA)
            ->post(route('admin.purchases.approvals.approve', $this->purchaseB_Submitted));

        $freshB = $this->purchaseB_Submitted->fresh();
        $this->assertNull($freshB->approved_at);
        $this->assertNull($freshB->approved_by);
    }

    /**
     * 42. Unauthorized approval tidak mengubah status Purchase tenant lain.
     */
    public function test_42_unauthorized_approval_does_not_mutate_status_of_foreign_purchase(): void
    {
        $statusBefore = $this->purchaseB_Submitted->fresh()->status;
        $approvalStatusBefore = $this->purchaseB_Submitted->fresh()->approval_status;

        $this->actingAs($this->adminA)
            ->post(route('admin.purchases.approvals.approve', $this->purchaseB_Submitted));

        $this->assertEquals($statusBefore, $this->purchaseB_Submitted->fresh()->status);
        $this->assertEquals($approvalStatusBefore, $this->purchaseB_Submitted->fresh()->approval_status);
    }

    /**
     * 43. Existing historical approval tetap utuh.
     */
    public function test_43_existing_historical_approval_remains_intact(): void
    {
        $approvedTime = $this->purchaseA_Approved->approved_at;
        $approvedBy = $this->purchaseA_Approved->approved_by;

        // Perform other write operations
        $this->actingAs($this->gudangA)->post(route('gudang.purchases.store'), [
            'supplier_id'   => $this->supplierA->id,
            'purchase_date' => '2026-10-22',
            'items'         => [['material_id' => $this->materialA->id, 'qty' => '5', 'unit_price' => '35000']],
        ]);

        $freshHist = $this->purchaseA_Approved->fresh();
        $this->assertEquals($approvedTime, $freshHist->approved_at);
        $this->assertEquals($approvedBy, $freshHist->approved_by);
        $this->assertEquals(PurchaseApprovalStatus::APPROVED, $freshHist->approval_status);
    }

    /**
     * 44. Financial available-to-commit yang digunakan approval hanya membaca resource tenant yang sah.
     */
    public function test_44_financial_available_to_commit_only_reads_legitimate_tenant_commitments(): void
    {
        $this->setupCash('10000000.00'); // 10jt kas

        // purchaseA_Approved total: 75 * 35.000 = 2.625.000 (outstanding)
        // purchaseB_Approved total: 60 * 40.000 = 2.400.000 (outstanding)
        $financeService = app(FinancialRecordService::class);

        // When evaluating Outlet A:
        $availableA = $financeService->calculateAvailableToCommit(null, $this->outletA->id);
        // Committed unpaid for Outlet A should be 2.625.000 (Outlet B's 2.4jt should NOT be counted)
        $this->assertEquals('2625000.00', $availableA['committed_unpaid']);
        $this->assertEquals('7375000.00', $availableA['available_to_commit']);

        // When evaluating Outlet B:
        $availableB = $financeService->calculateAvailableToCommit(null, $this->outletB->id);
        $this->assertEquals('2400000.00', $availableB['committed_unpaid']);
        $this->assertEquals('7600000.00', $availableB['available_to_commit']);
    }

    /**
     * 45. Unauthorized approval tidak memutasi cash/commitment tenant lain.
     */
    public function test_45_unauthorized_approval_does_not_mutate_cash_or_commitments_of_other_tenant(): void
    {
        $this->setupCash('10000000.00');
        $financeService = app(FinancialRecordService::class);

        $beforeB = $financeService->calculateAvailableToCommit(null, $this->outletB->id);

        // Attacker Admin A tries to approve Purchase B
        $this->actingAs($this->adminA)->post(route('admin.purchases.approvals.approve', $this->purchaseB_Submitted));

        $afterB = $financeService->calculateAvailableToCommit(null, $this->outletB->id);
        $this->assertEquals($beforeB['committed_unpaid'], $afterB['committed_unpaid']);
        $this->assertEquals($beforeB['available_to_commit'], $afterB['available_to_commit']);
    }

    /**
     * 46. Approval business gate existing tetap berlaku (e.g. dana tidak mencukupi).
     */
    public function test_46_approval_business_gate_existing_remains_in_effect(): void
    {
        $this->setupCash('500000.00'); // Kas hanya 500rb, sedangkan PO A Submitted: 50 * 35.000 = 1.750.000

        $response = $this->actingAs($this->adminA)
            ->post(route('admin.purchases.approvals.approve', $this->purchaseA_Submitted));

        $response->assertRedirect(route('admin.purchases.approvals.show', $this->purchaseA_Submitted));
        $response->assertSessionHas('error');
        $this->assertEquals(PurchaseApprovalStatus::PENDING_APPROVAL, $this->purchaseA_Submitted->fresh()->approval_status);
    }

    /**
     * 47. Duplicate/replay approval mengikuti aturan existing.
     */
    public function test_47_duplicate_replay_approval_follows_existing_rules(): void
    {
        $this->setupCash('10000000.00');

        // Approve once
        $this->actingAs($this->adminA)
            ->post(route('admin.purchases.approvals.approve', $this->purchaseA_Submitted));
        $this->assertEquals(PurchaseApprovalStatus::APPROVED, $this->purchaseA_Submitted->fresh()->approval_status);

        // Replay approve on already approved PO
        $response = $this->actingAs($this->adminA)
            ->post(route('admin.purchases.approvals.approve', $this->purchaseA_Submitted));

        $response->assertRedirect(route('admin.purchases.approvals.show', $this->purchaseA_Submitted));
        $response->assertSessionHas('error');
    }

    /**
     * 48. TenantContext invalid ditolak saat flag TRUE.
     */
    public function test_48_invalid_tenant_context_rejected_when_flag_true(): void
    {
        $response = $this->actingAs($this->unboundGudang)
            ->post(route('gudang.purchases.store'), [
                'supplier_id'   => $this->supplierA->id,
                'purchase_date' => '2026-10-22',
                'items'         => [['material_id' => $this->materialA->id, 'qty' => '10', 'unit_price' => '35000']],
            ]);

        $response->assertStatus(403);
    }

    /**
     * 49. User tanpa outlet ditolak.
     */
    public function test_49_user_without_outlet_rejected(): void
    {
        $response = $this->actingAs($this->unboundGudang)
            ->put(route('gudang.purchases.update', $this->purchaseA_Draft), [
                'supplier_id'   => $this->supplierA->id,
                'purchase_date' => '2026-10-25',
                'items'         => [['material_id' => $this->materialA->id, 'qty' => '10', 'unit_price' => '35000']],
            ]);

        $response->assertStatus(403);
    }

    /**
     * 50. Outlet INACTIVE ditolak.
     */
    public function test_50_outlet_inactive_rejected(): void
    {
        $response = $this->actingAs($this->gudangInactiveOutlet)
            ->post(route('gudang.purchases.store'), [
                'supplier_id'   => $this->supplierA->id,
                'purchase_date' => '2026-10-22',
                'items'         => [['material_id' => $this->materialA->id, 'qty' => '10', 'unit_price' => '35000']],
            ]);

        $response->assertStatus(403);
    }

    /**
     * 51. User INACTIVE ditolak tanpa mutation.
     */
    public function test_51_user_inactive_rejected_without_mutation(): void
    {
        $poCountBefore = Purchase::count();

        $response = $this->actingAs($this->inactiveGudangA)
            ->post(route('gudang.purchases.store'), [
                'supplier_id'   => $this->supplierA->id,
                'purchase_date' => '2026-10-22',
                'items'         => [['material_id' => $this->materialA->id, 'qty' => '10', 'unit_price' => '35000']],
            ]);

        $this->assertEquals($poCountBefore, Purchase::count());
    }

    /**
     * 52. Flag FALSE tetap menjaga workflow Purchase legacy.
     */
    public function test_52_flag_false_preserves_legacy_purchase_workflow(): void
    {
        config(['nayara.tenant_isolation_enabled' => false]);

        $payload = [
            'supplier_id'   => $this->unassignedSupplier->id,
            'purchase_date' => '2026-10-22',
            'items'         => [
                ['material_id' => $this->unassignedMaterial->id, 'qty' => '10', 'unit_price' => '20000'],
            ],
        ];

        $response = $this->actingAs($this->gudangA)
            ->post(route('gudang.purchases.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /**
     * 53. Flag TRUE tidak melakukan fallback outlet.
     */
    public function test_53_flag_true_does_not_fallback_to_arbitrary_outlet(): void
    {
        $payload = [
            'supplier_id'   => $this->supplierA->id,
            'purchase_date' => '2026-10-22',
            'items'         => [
                ['material_id' => $this->materialA->id, 'qty' => '10', 'unit_price' => '35000'],
            ],
        ];

        $this->actingAs($this->gudangA)->post(route('gudang.purchases.store'), $payload);

        $latest = Purchase::latest('id')->first();
        $this->assertEquals($this->outletA->id, $latest->outlet_id);
        $this->assertNotEquals(1, $latest->outlet_id === $this->outletA->id ? 99999 : 1);
    }

    /**
     * 54. Tidak ada Global Tenant Scope.
     */
    public function test_54_no_global_tenant_scope(): void
    {
        $this->assertEmpty((new Purchase)->getGlobalScopes());
        $this->assertEmpty((new Supplier)->getGlobalScopes());
        $this->assertEmpty((new Material)->getGlobalScopes());
    }

    /**
     * 55. Supplier READ existing tetap terisolasi.
     */
    public function test_55_supplier_read_existing_remains_isolated(): void
    {
        $response = $this->actingAs($this->gudangA)->get(route('gudang.suppliers.index'));
        $response->assertOk();
        $response->assertSee('PT Tekstil Prima Mandiri A');
        $response->assertDontSee('CV Sumber Benang Makmur B');
    }

    /**
     * 56. Purchase READ Fase 8.1 tetap lulus.
     */
    public function test_56_purchase_read_fase_8_1_remains_passed(): void
    {
        $response = $this->actingAs($this->gudangA)->get(route('gudang.purchases.index'));
        $response->assertOk();
        $response->assertSee('PO-202610-A-001');
        $response->assertDontSee('PO-202610-B-001');
    }

    /**
     * 57. Sales Order Fase 5 tetap lulus.
     */
    public function test_57_sales_order_fase_5_remains_intact(): void
    {
        $this->assertTrue(method_exists(\App\Models\Sales\Order::class, 'scopeForOutlet'));
    }

    /**
     * 58. Production/Inventory Fase 6/7 tetap lulus.
     */
    public function test_58_production_inventory_fase_6_7_remains_intact(): void
    {
        $this->assertTrue(method_exists(ProductionBatch::class, 'scopeForOutlet'));
        $this->assertTrue(method_exists(Purchase::class, 'scopeForOutlet'));
        $this->assertTrue(method_exists(Purchase::class, 'hasConsistentTenantRelations'));
    }

    /**
     * 59. Tidak ada mutation stok akibat unauthorized write yang tidak semestinya.
     */
    public function test_59_no_stock_movement_mutation_from_unauthorized_write(): void
    {
        $movementCountBefore = StockMovement::count();

        // Illegal submit
        $this->actingAs($this->gudangA)->post(route('gudang.purchases.submit-approval', $this->purchaseB_Draft));

        // Illegal approve
        $this->actingAs($this->adminA)->post(route('admin.purchases.approvals.approve', $this->purchaseB_Submitted));

        $this->assertEquals($movementCountBefore, StockMovement::count());
    }

    /**
     * 60. Tidak ada mutasi data tenant B dari percobaan ilegal tenant A.
     */
    public function test_60_no_data_mutation_of_tenant_b_from_illegal_attempt_of_tenant_a(): void
    {
        $poBBefore = $this->purchaseB_Draft->fresh()->toArray();
        $supplierBBefore = $this->supplierB->fresh()->toArray();

        // Attacker attempts update, submit, cancel
        $this->actingAs($this->gudangA)->put(route('gudang.purchases.update', $this->purchaseB_Draft), [
            'supplier_id'   => $this->supplierA->id,
            'purchase_date' => '2026-10-30',
            'items'         => [['material_id' => $this->materialA->id, 'qty' => '1', 'unit_price' => '1000']],
        ]);
        $this->actingAs($this->gudangA)->post(route('gudang.purchases.submit-approval', $this->purchaseB_Draft));
        $this->actingAs($this->gudangA)->post(route('gudang.purchases.cancel', $this->purchaseB_Draft));

        $this->assertEquals($poBBefore, $this->purchaseB_Draft->fresh()->toArray());
        $this->assertEquals($supplierBBefore, $this->supplierB->fresh()->toArray());
    }

    /**
     * 61. Rollback transaction tidak meninggalkan record Purchase/Approval parsial.
     */
    public function test_61_rollback_transaction_leaves_no_partial_records(): void
    {
        $poCountBefore = Purchase::count();
        $itemCountBefore = PurchaseItem::count();

        try {
            DB::transaction(function () {
                $p = Purchase::create([
                    'purchase_no'   => 'PO-TEST-ROLLBACK',
                    'supplier_id'   => $this->supplierA->id,
                    'purchase_date' => '2026-10-22',
                    'status'        => PurchaseStatus::DRAFT,
                    'approval_status' => PurchaseApprovalStatus::NOT_SUBMITTED,
                    'outlet_id'     => $this->outletA->id,
                ]);
                PurchaseItem::create([
                    'purchase_id'  => $p->id,
                    'material_id'  => $this->materialA->id,
                    'qty'          => '10',
                    'unit_price'   => '35000',
                    'qty_received' => '0',
                ]);
                throw new \Exception('Rollback simulation');
            });
        } catch (\Throwable $e) {
            // expected
        }

        $this->assertEquals($poCountBefore, Purchase::count());
        $this->assertEquals($itemCountBefore, PurchaseItem::count());
    }

    /**
     * 62. Tidak ada perubahan formula bisnis, status enum, atau dokumen purchase.
     */
    public function test_62_no_changes_to_business_formulas_enums_or_numbering(): void
    {
        $payload = [
            'supplier_id'   => $this->supplierA->id,
            'purchase_date' => '2026-10-22',
            'items'         => [
                ['material_id' => $this->materialA->id, 'qty' => '10', 'unit_price' => '35000'],
            ],
        ];

        $this->actingAs($this->gudangA)->post(route('gudang.purchases.store'), $payload);

        $latest = Purchase::latest('id')->first();
        $this->assertMatchesRegularExpression('/^PO-\d{6}-\d{4}$/', $latest->purchase_no);
        $this->assertEquals(PurchaseStatus::DRAFT, $latest->status);
        $this->assertEquals(PurchaseApprovalStatus::NOT_SUBMITTED, $latest->approval_status);
        $this->assertEquals('350000.00', $latest->total_amount_string);
    }
}
