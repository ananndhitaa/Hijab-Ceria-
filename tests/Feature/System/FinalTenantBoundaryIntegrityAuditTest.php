<?php

namespace Tests\Feature\System;

use App\Enums\BatchStatus;
use App\Enums\MovementType;
use App\Enums\PurchaseStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Inventory\MaterialStock;
use App\Models\Inventory\Purchase;
use App\Models\Inventory\PurchaseItem;
use App\Models\Inventory\StockMovement;
use App\Models\Master\BomItem;
use App\Models\Master\Customer;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Master\Supplier;
use App\Models\Master\Unit;
use App\Models\Production\BatchMaterial;
use App\Models\Production\ProductionBatch;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\Outlet;
use App\Models\System\User;
use App\Services\Inventory\MaterialAllocationService;
use App\Services\Inventory\StockOpnameService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Fase 8.6A: Final Tenant Boundary, Inventory Reservation & Cross-Module Integrity Diagnostic Test Suite.
 *
 * Diagnoses:
 * 1. Stock opname behavior under unallocated planned demand & allocation shortage (Section E).
 * 2. Physical allocation decoupling & preservation during stock opname (Scenario 23 / Section G).
 * 3. Cross-tenant polymorphic reference safety in Stock Movement history (Section F).
 * 4. Cross-tenant BOM material selection boundary (Section H).
 * 5. Legacy unassigned NULL material fail-closed behavior under isolation TRUE (Section K).
 */
class FinalTenantBoundaryIntegrityAuditTest extends TestCase
{
    use RefreshDatabase;

    protected Outlet $outletA;
    protected Outlet $outletB;

    protected User $adminA;
    protected User $gudangA;
    protected User $gudangB;

    protected Unit $unitMeter;

    protected Material $materialA;
    protected MaterialStock $stockA;

    protected Material $materialB;
    protected MaterialStock $stockB;

    protected Material $legacyMaterial;
    protected MaterialStock $legacyStock;

    protected Supplier $supplierB;

    protected function setUp(): void
    {
        parent::setUp();

        config(['nayara.tenant_isolation_enabled' => true]);

        $this->outletA = Outlet::create([
            'outlet_code' => 'OUT-A',
            'outlet_name' => 'Outlet Konveksi A',
            'is_active'   => true,
        ]);

        $this->outletB = Outlet::create([
            'outlet_code' => 'OUT-B',
            'outlet_name' => 'Outlet Konveksi B',
            'is_active'   => true,
        ]);

        $this->adminA = User::create([
            'name'      => 'Admin A',
            'email'     => 'admin.a@nayara.test',
            'password'  => Hash::make('password'),
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $this->outletA->id,
        ]);

        $this->gudangA = User::create([
            'name'      => 'Gudang A',
            'email'     => 'gudang.a@nayara.test',
            'password'  => Hash::make('password'),
            'role'      => UserRole::GUDANG,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $this->outletA->id,
        ]);

        $this->gudangB = User::create([
            'name'      => 'Gudang B',
            'email'     => 'gudang.b@nayara.test',
            'password'  => Hash::make('password'),
            'role'      => UserRole::GUDANG,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $this->outletB->id,
        ]);

        $this->unitMeter = Unit::create([
            'unit_name' => 'Meter',
            'symbol'    => 'm',
            'active'    => true,
        ]);

        $this->materialA = Material::create([
            'outlet_id'     => $this->outletA->id,
            'material_code' => 'MAT-A',
            'material_name' => 'Kain Katun A',
            'unit_id'       => $this->unitMeter->id,
            'min_stock'     => 10,
            'active'        => true,
        ]);
        $this->stockA = MaterialStock::create([
            'material_id'   => $this->materialA->id,
            'qty_on_hand'   => '100.0000',
            'avg_unit_cost' => '25000.0000',
        ]);

        $this->materialB = Material::create([
            'outlet_id'     => $this->outletB->id,
            'material_code' => 'MAT-B',
            'material_name' => 'Kain Katun B',
            'unit_id'       => $this->unitMeter->id,
            'min_stock'     => 10,
            'active'        => true,
        ]);
        $this->stockB = MaterialStock::create([
            'material_id'   => $this->materialB->id,
            'qty_on_hand'   => '100.0000',
            'avg_unit_cost' => '30000.0000',
        ]);

        $this->supplierB = Supplier::create([
            'outlet_id'     => $this->outletB->id,
            'supplier_name' => 'Supplier Kain B',
            'contact_person'=> 'Pak B',
            'phone'         => '082222222222',
            'active'        => true,
        ]);

        $this->legacyMaterial = Material::create([
            'outlet_id'     => null,
            'material_code' => 'MAT-LEGACY',
            'material_name' => 'Kain Katun Legacy Unassigned',
            'unit_id'       => $this->unitMeter->id,
            'min_stock'     => 10,
            'active'        => true,
        ]);
        $this->legacyStock = MaterialStock::create([
            'material_id'   => $this->legacyMaterial->id,
            'qty_on_hand'   => '50.0000',
            'avg_unit_cost' => '20000.0000',
        ]);
    }

    /**
     * Diagnostic 1: Stock Opname reducing physical stock below planned unallocated requirement.
     * Demonstrates that Stock Opname adjusts physical warehouse stock on hand, and subsequent
     * material allocation correctly blocks (throws DomainException) when stock becomes insufficient.
     */
    public function test_diagnostic_01_stock_opname_reduction_blocks_subsequent_allocation_safely(): void
    {
        // 1. Create Order, Item, Batch in BAHAN with planned requirement = 80 m
        $customer = Customer::create([
            'outlet_id'     => $this->outletA->id,
            'customer_name' => 'Pelanggan A',
            'phone'         => '081234567890',
            'active'        => true,
        ]);

        $order = Order::create([
            'outlet_id'    => $this->outletA->id,
            'customer_id'  => $customer->id,
            'order_no'     => 'ORD-2026-0001',
            'order_date'   => now()->toDateString(),
            'total_amount' => '1000000.00',
        ]);

        $product = Product::create([
            'outlet_id'    => $this->outletA->id,
            'product_name' => 'Hijab A',
            'active'       => true,
        ]);

        $variant = ProductVariant::create([
            'outlet_id'          => $this->outletA->id,
            'product_id'         => $product->id,
            'variant_name'       => 'Hijab A Hitam',
            'sku'                => 'SKU-HA-H',
            'base_selling_price' => '50000.00',
            'active'             => true,
        ]);

        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 40,
            'unit_selling_price' => '50000.00',
        ]);

        $batch = ProductionBatch::create([
            'order_item_id'     => $orderItem->id,
            'outlet_id'         => $this->outletA->id,
            'batch_no'          => 'BAT-2026-0001',
            'target_qty'        => 40,
            'status'            => BatchStatus::BAHAN,
            'requires_printing' => false,
        ]);

        // MRP snapshot: required = 80 m, allocated = 0 m (unallocated)
        $batchMaterial = BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->materialA->id,
            'required_qty'  => '80.0000',
            'stock_at_calc' => '100.0000',
            'shortage_qty'  => '0.0000',
            'allocated_qty' => '0.0000',
            'used_qty'      => '0.0000',
            'calculated_at' => now(),
        ]);

        // Initial warehouse stock on hand is 100 m
        $this->assertEquals('100.0000', (string) $this->stockA->qty_on_hand);

        // 2. Warehouse performs Stock Opname: physical count is 50 m (e.g. damaged or lost stock)
        $response = $this->actingAs($this->gudangA)->post(route('gudang.materials.stock-opname.store', $this->materialA), [
            'physical_qty' => '50.0000',
            'notes'        => 'Stock opname penyesuaian fisik berkurang',
        ]);
        $response->assertRedirect(route('gudang.materials.show', $this->materialA));

        $this->stockA->refresh();
        $this->assertEquals('50.0000', (string) $this->stockA->qty_on_hand);

        // 3. Attempting full allocation for the batch must fail safely because live stock (50) < required (80)
        $allocationService = app(MaterialAllocationService::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Stok belum mencukupi untuk mengalokasikan seluruh kebutuhan bahan batch.');

        $allocationService->allocateBatchMaterials($batch, $this->gudangA->id);
    }

    /**
     * Diagnostic 2: Scenario 23 verification — Production allocation preserved during Stock Opname.
     * When a batch has already had materials physically allocated (allocated_qty > 0), performing
     * a stock opname on remaining warehouse stock does NOT mutate or corrupt the batch allocation.
     */
    public function test_diagnostic_02_production_allocation_preserved_during_stock_opname(): void
    {
        // Setup batch and allocate 30 m of materialA
        $customer = Customer::create([
            'outlet_id'     => $this->outletA->id,
            'customer_name' => 'Pelanggan A',
            'phone'         => '081234567891',
            'active'        => true,
        ]);
        $order = Order::create([
            'outlet_id'    => $this->outletA->id,
            'customer_id'  => $customer->id,
            'order_no'     => 'ORD-2026-0002',
            'order_date'   => now()->toDateString(),
            'total_amount' => '500000.00',
        ]);
        $product = Product::create([
            'outlet_id'    => $this->outletA->id,
            'product_name' => 'Hijab A2',
            'active'       => true,
        ]);
        $variant = ProductVariant::create([
            'outlet_id'          => $this->outletA->id,
            'product_id'         => $product->id,
            'variant_name'       => 'Hijab A2 Putih',
            'sku'                => 'SKU-HA-P',
            'base_selling_price' => '50000.00',
            'active'             => true,
        ]);
        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 20,
            'unit_selling_price' => '50000.00',
        ]);
        $batch = ProductionBatch::create([
            'order_item_id'     => $orderItem->id,
            'outlet_id'         => $this->outletA->id,
            'batch_no'          => 'BAT-2026-0002',
            'target_qty'        => 20,
            'status'            => BatchStatus::BAHAN,
            'requires_printing' => false,
        ]);
        $batchMaterial = BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->materialA->id,
            'required_qty'  => '30.0000',
            'stock_at_calc' => '100.0000',
            'shortage_qty'  => '0.0000',
            'allocated_qty' => '0.0000',
            'used_qty'      => '0.0000',
            'calculated_at' => now(),
        ]);

        // Allocate 30 m to the batch
        $this->actingAs($this->gudangA);
        app(MaterialAllocationService::class)->allocateBatchMaterials($batch, $this->gudangA->id);

        $this->stockA->refresh();
        $batchMaterial->refresh();

        // Warehouse stock decremented to 70 m, batchMaterial allocated_qty = 30 m
        $this->assertEquals('70.0000', (string) $this->stockA->qty_on_hand);
        $this->assertEquals('30.0000', (string) $batchMaterial->allocated_qty);

        // Perform Stock Opname on remaining warehouse stock: physical count is 65 m
        $this->actingAs($this->gudangA)->post(route('gudang.materials.stock-opname.store', $this->materialA), [
            'physical_qty' => '65.0000',
            'notes'        => 'Warehouse recount',
        ])->assertRedirect();

        $this->stockA->refresh();
        $batchMaterial->refresh();

        // Warehouse stock updated to 65 m
        $this->assertEquals('65.0000', (string) $this->stockA->qty_on_hand);

        // Production allocation remains strictly preserved at 30 m!
        $this->assertEquals('30.0000', (string) $batchMaterial->allocated_qty);
    }

    /**
     * Diagnostic 3: Cross-tenant polymorphic stock movement reference safety.
     * If a StockMovement for Material A has a corrupt reference pointing to a PurchaseItem of Outlet B,
     * the view query must NOT leak Tenant B's purchase reference or purchase number.
     */
    public function test_diagnostic_03_corrupt_cross_tenant_polymorphic_reference_does_not_leak_details(): void
    {
        // Create Purchase belonging to Outlet B
        $purchaseB = Purchase::create([
            'outlet_id'     => $this->outletB->id,
            'supplier_id'   => $this->supplierB->id,
            'purchase_no'   => 'PO-OUTLET-B-SECRET-999',
            'purchase_date' => now()->toDateString(),
            'status'        => PurchaseStatus::RECEIVED,
            'total_cost'    => '2000000.00',
            'created_by'    => $this->gudangB->id,
        ]);
        $purchaseItemB = PurchaseItem::create([
            'purchase_id' => $purchaseB->id,
            'material_id' => $this->materialB->id,
            'qty'         => '40.0000',
            'unit_price'  => '50000.00',
            'subtotal'    => '2000000.00',
        ]);

        // Corrupt movement: belongs to Material A (Outlet A), but reference_id points to PurchaseItem of Outlet B
        $corruptMovement = StockMovement::create([
            'material_id'         => $this->materialA->id,
            'movement_type'       => MovementType::IN,
            'qty'                 => '10.0000',
            'unit_cost_snapshot'  => '25000.0000',
            'total_cost_snapshot' => '250000.00',
            'qty_before'          => '90.0000',
            'qty_after'           => '100.0000',
            'movement_date'       => now()->toDateString(),
            'reference_type'      => 'purchase_items',
            'reference_id'        => $purchaseItemB->id, // Foreign item!
            'notes'               => 'Corrupt legacy entry',
            'created_by'          => $this->gudangA->id,
        ]);

        // Gudang A views movement history
        $response = $this->actingAs($this->gudangA)->get(route('gudang.stock-movements.index'));
        $response->assertOk();

        // 1. purchaseItemsMap must NOT contain the foreign PurchaseItem
        $purchaseItemsMap = $response->viewData('purchaseItemsMap');
        $this->assertArrayNotHasKey($purchaseItemB->id, $purchaseItemsMap);

        // 2. The rendered HTML must NOT contain Outlet B's secret purchase number
        $response->assertDontSee('PO-OUTLET-B-SECRET-999');
    }

    /**
     * Diagnostic 4 (Converted to Security Assertion): Cross-tenant BOM material selection security.
     * Verifies that adding a foreign material to a variant's BOM is strictly rejected,
     * no cross-tenant BOM item is created, and valid own-tenant material is accepted.
     */
    public function test_diagnostic_04_bom_cross_tenant_material_selection_boundary_audit(): void
    {
        $productA = Product::create([
            'outlet_id'    => $this->outletA->id,
            'product_name' => 'Product A',
            'active'       => true,
        ]);

        $variantA = ProductVariant::create([
            'outlet_id'          => $this->outletA->id,
            'product_id'         => $productA->id,
            'variant_name'       => 'Variant A',
            'sku'                => 'SKU-VA-1',
            'base_selling_price' => '100000.00',
            'active'             => true,
        ]);

        // 1. Admin A attempts to add Material B (Outlet B) to Variant A's BOM -> Rejected
        $response = $this->actingAs($this->adminA)->post(route('admin.variants.bom.store', $variantA), [
            'material_id'  => $this->materialB->id,
            'qty_per_unit' => '2.5000',
        ]);
        $response->assertSessionHasErrors('material_id');

        // Verify no BOM item created for foreign material
        $this->assertFalse(
            BomItem::where('variant_id', $variantA->id)->where('material_id', $this->materialB->id)->exists(),
            'Foreign material must not be attached to variant BOM.'
        );

        // 2. Admin A adds valid own-tenant Material A -> Accepted
        $responseValid = $this->actingAs($this->adminA)->post(route('admin.variants.bom.store', $variantA), [
            'material_id'  => $this->materialA->id,
            'qty_per_unit' => '1.5000',
        ]);
        $responseValid->assertRedirect(route('admin.variants.show', $variantA));

        $this->assertTrue(
            BomItem::where('variant_id', $variantA->id)->where('material_id', $this->materialA->id)->exists(),
            'Valid own-tenant material must be attached to variant BOM.'
        );
    }

    /**
     * Diagnostic 5: Legacy unassigned NULL material fails closed under tenant isolation TRUE.
     */
    public function test_diagnostic_05_legacy_unassigned_material_fails_closed_under_isolation_true(): void
    {
        // 1. Stock opname form GET returns 404
        $this->actingAs($this->gudangA)
            ->get(route('gudang.materials.stock-opname', $this->legacyMaterial))
            ->assertNotFound();

        // 2. Stock opname POST returns 404
        $this->actingAs($this->gudangA)
            ->post(route('gudang.materials.stock-opname.store', $this->legacyMaterial), [
                'physical_qty' => '75.0000',
                'notes'        => 'Attempt claim',
            ])
            ->assertNotFound();

        // 3. Stock remains unchanged
        $this->legacyStock->refresh();
        $this->assertEquals('50.0000', (string) $this->legacyStock->qty_on_hand);
    }
}
