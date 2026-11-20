<?php

namespace Tests\Feature\Gudang;

use App\Enums\MovementType;
use App\Enums\PurchaseStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Inventory\MaterialStock;
use App\Models\Inventory\Purchase;
use App\Models\Inventory\PurchaseItem;
use App\Models\Inventory\PurchasePayment;
use App\Models\Inventory\StockMovement;
use App\Models\Master\Material;
use App\Models\Master\Supplier;
use App\Models\Master\Unit;
use App\Models\System\User;
use App\Services\Inventory\PurchaseOrderService;
use App\Services\Inventory\PurchaseReceivingService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $gudangUser;
    private User $customerUser;
    private Supplier $supplierA;
    private Supplier $supplierB;
    private Material $materialA;
    private Material $materialB;
    private Unit $meter;
    private PurchaseOrderService $poService;
    private PurchaseReceivingService $receivingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->poService = app(PurchaseOrderService::class);
        $this->receivingService = app(PurchaseReceivingService::class);

        $this->gudangUser = User::factory()->create([
            'role'   => UserRole::GUDANG,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->customerUser = User::factory()->create([
            'role'   => UserRole::CUSTOMER,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->meter = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);

        $this->supplierA = Supplier::create([
            'supplier_name' => 'PT Tekstil Prima',
            'active'        => true,
        ]);

        $this->supplierB = Supplier::create([
            'supplier_name' => 'CV Benang Jaya',
            'active'        => true,
        ]);

        $this->materialA = Material::create([
            'unit_id'       => $this->meter->id,
            'material_name' => 'Kain Kaos Hitam',
            'min_stock'     => 10,
            'active'        => true,
        ]);
        MaterialStock::create([
            'material_id'   => $this->materialA->id,
            'qty_on_hand'   => '0.0000',
            'avg_unit_cost' => '0.0000',
        ]);

        $this->materialB = Material::create([
            'unit_id'       => $this->meter->id,
            'material_name' => 'Kain Kaos Mocca',
            'min_stock'     => 10,
            'active'        => true,
        ]);
        MaterialStock::create([
            'material_id'   => $this->materialB->id,
            'qty_on_hand'   => '0.0000',
            'avg_unit_cost' => '0.0000',
        ]);
    }

    private function payPurchase(Purchase $purchase): void
    {
        $purchase->update([
            'status'          => PurchaseStatus::ORDERED,
            'approval_status' => \App\Enums\PurchaseApprovalStatus::APPROVED,
            'approved_at'     => now(),
        ]);
        $purchase->load('items');
        PurchasePayment::create([
            'purchase_id'    => $purchase->id,
            'payment_date'   => $purchase->purchase_date->toDateString(),
            'amount'         => $purchase->total_amount,
            'payment_method' => 'TRANSFER',
            'created_by'     => $this->gudangUser->id,
        ]);
    }

    public function test_gudang_can_view_purchase_history(): void
    {
        // Create, confirm, and receive PO
        $purchase = $this->poService->createPurchase([
            'supplier_id'   => $this->supplierA->id,
            'purchase_date' => '2026-09-01',
            'items'         => [
                [
                    'material_id' => $this->materialA->id,
                    'qty'         => '100.0000',
                    'unit_price'  => '25000.00',
                ],
            ],
        ], $this->gudangUser);

        $this->payPurchase($purchase);
        $this->receivingService->receivePurchase($purchase, $this->gudangUser->id);

        $response = $this->actingAs($this->gudangUser)
            ->get(route('gudang.purchases.history'));

        $response->assertOk();
        $response->assertViewIs('gudang.purchases.history');
        $response->assertSee($purchase->purchase_no);
        $response->assertSee('PT Tekstil Prima');
        $response->assertSee('Kain Kaos Hitam');
        $response->assertSee('100 m');
        $response->assertSee('Rp 25.000');
        $response->assertSee('Rp 2.500.000');
    }

    public function test_purchase_history_only_includes_received_purchases(): void
    {
        // 1. DRAFT PO
        $draftPurchase = $this->poService->createPurchase([
            'supplier_id'   => $this->supplierA->id,
            'purchase_date' => '2026-09-01',
            'items'         => [
                ['material_id' => $this->materialA->id, 'qty' => '50.0000', 'unit_price' => '25000.00'],
            ],
        ], $this->gudangUser);

        // 2. ORDERED PO
        $orderedPurchase = $this->poService->createPurchase([
            'supplier_id'   => $this->supplierB->id,
            'purchase_date' => '2026-09-02',
            'items'         => [
                ['material_id' => $this->materialB->id, 'qty' => '75.0000', 'unit_price' => '30000.00'],
            ],
        ], $this->gudangUser);
        $orderedPurchase->update([
            'status'          => PurchaseStatus::ORDERED,
            'approval_status' => \App\Enums\PurchaseApprovalStatus::APPROVED,
        ]);

        // 3. CANCELLED PO
        $cancelledPurchase = $this->poService->createPurchase([
            'supplier_id'   => $this->supplierA->id,
            'purchase_date' => '2026-09-03',
            'items'         => [
                ['material_id' => $this->materialA->id, 'qty' => '30.0000', 'unit_price' => '25000.00'],
            ],
        ], $this->gudangUser);
        $this->poService->cancelPurchase($cancelledPurchase, 'Vendor out of stock', $this->gudangUser);

        // 4. RECEIVED PO
        $receivedPurchase = $this->poService->createPurchase([
            'supplier_id'   => $this->supplierB->id,
            'purchase_date' => '2026-09-04',
            'items'         => [
                ['material_id' => $this->materialB->id, 'qty' => '40.0000', 'unit_price' => '30000.00'],
            ],
        ], $this->gudangUser);
        $this->payPurchase($receivedPurchase);
        $this->receivingService->receivePurchase($receivedPurchase, $this->gudangUser->id);

        $response = $this->actingAs($this->gudangUser)
            ->get(route('gudang.purchases.history'));

        $response->assertOk();
        $response->assertSee($receivedPurchase->purchase_no);
        $response->assertDontSee($draftPurchase->purchase_no);
        $response->assertDontSee($orderedPurchase->purchase_no);
        $response->assertDontSee($cancelledPurchase->purchase_no);
    }

    public function test_purchase_history_excludes_production_out_and_stock_opname(): void
    {
        // Create manual stock movements that are NOT purchase_items
        StockMovement::create([
            'material_id'          => $this->materialA->id,
            'movement_type'        => MovementType::OUT,
            'reference_type'       => 'batch_materials',
            'reference_id'         => 999,
            'qty'                  => '15.0000',
            'unit_cost_snapshot'   => '25000.0000',
            'total_cost_snapshot'  => '375000.0000',
            'movement_date'        => '2026-09-05',
            'notes'                => 'Production consumption',
            'created_by'           => $this->gudangUser->id,
        ]);

        StockMovement::create([
            'material_id'          => $this->materialA->id,
            'movement_type'        => MovementType::ADJUSTMENT,
            'reference_type'       => 'stock_opnames',
            'reference_id'         => 888,
            'qty'                  => '2.0000',
            'unit_cost_snapshot'   => '25000.0000',
            'total_cost_snapshot'  => '50000.0000',
            'movement_date'        => '2026-09-06',
            'notes'                => 'Opname adjustment',
            'created_by'           => $this->gudangUser->id,
        ]);

        $response = $this->actingAs($this->gudangUser)
            ->get(route('gudang.purchases.history'));

        $response->assertOk();
        $response->assertDontSee('Production consumption');
        $response->assertDontSee('Opname adjustment');
        $response->assertSee('Belum ada riwayat penerimaan material.');
    }

    public function test_purchase_history_filters_by_supplier_and_material(): void
    {
        // PO 1: Supplier A, Material A
        $po1 = $this->poService->createPurchase([
            'supplier_id'   => $this->supplierA->id,
            'purchase_date' => '2026-09-01',
            'items'         => [
                ['material_id' => $this->materialA->id, 'qty' => '100.0000', 'unit_price' => '25000.00'],
            ],
        ], $this->gudangUser);
        $this->payPurchase($po1);
        $this->receivingService->receivePurchase($po1, $this->gudangUser->id);

        // PO 2: Supplier B, Material B
        $po2 = $this->poService->createPurchase([
            'supplier_id'   => $this->supplierB->id,
            'purchase_date' => '2026-09-02',
            'items'         => [
                ['material_id' => $this->materialB->id, 'qty' => '50.0000', 'unit_price' => '30000.00'],
            ],
        ], $this->gudangUser);
        $this->payPurchase($po2);
        $this->receivingService->receivePurchase($po2, $this->gudangUser->id);

        // Filter by Supplier A
        $responseSupA = $this->actingAs($this->gudangUser)
            ->get(route('gudang.purchases.history', ['supplier_id' => $this->supplierA->id]));
        $responseSupA->assertOk();
        $responseSupA->assertSee($po1->purchase_no);
        $responseSupA->assertDontSee($po2->purchase_no);

        // Filter by Material B
        $responseMatB = $this->actingAs($this->gudangUser)
            ->get(route('gudang.purchases.history', ['material_id' => $this->materialB->id]));
        $responseMatB->assertOk();
        $responseMatB->assertSee($po2->purchase_no);
        $responseMatB->assertDontSee($po1->purchase_no);
    }

    public function test_customer_cannot_access_purchase_history(): void
    {
        $response = $this->actingAs($this->customerUser)
            ->get(route('gudang.purchases.history'));

        $response->assertForbidden();
    }

    /**
     * Requirement 1: Audit Material Creation & Edit
     * Creating a new Material results in stock = 0.
     * Material creation and edit do NOT allow arbitrary initial stock or stock tampering.
     */
    public function test_material_creation_sets_stock_to_zero_and_cannot_tamper_initial_stock(): void
    {
        // 1. Create new Material with attempted arbitrary positive initial stock payload
        $response = $this->actingAs($this->gudangUser)->post(route('gudang.materials.store'), [
            'material_name' => 'Kain Organza Kristal',
            'unit_id'       => $this->meter->id,
            'min_stock'     => 15,
            'active'        => 1,
            // Tamper attempts
            'qty_on_hand'   => 500,
            'stock'         => 500,
            'avg_unit_cost' => 75000,
        ]);

        $response->assertRedirect();
        $material = Material::where('material_name', 'Kain Organza Kristal')->firstOrFail();

        // MaterialStock must exist with exact decimal 0
        $stock = MaterialStock::where('material_id', $material->id)->firstOrFail();
        $this->assertEquals('0.0000', $stock->qty_on_hand);
        $this->assertEquals('0.0000', $stock->avg_unit_cost);

        // 2. Edit Material with attempted direct stock tampering
        $updateResponse = $this->actingAs($this->gudangUser)->put(route('gudang.materials.update', $material), [
            'material_name' => 'Kain Organza Kristal Super',
            'unit_id'       => $this->meter->id,
            'min_stock'     => 20,
            'active'        => 1,
            // Tamper attempts
            'qty_on_hand'   => 9999,
            'stock'         => 9999,
        ]);

        $updateResponse->assertRedirect();
        $stock->refresh();
        $this->assertEquals('0.0000', $stock->qty_on_hand);
        $this->assertEquals('0.0000', $stock->avg_unit_cost);
    }

    /**
     * Requirement 2: Complete Purchase Lifecycle
     * A. Create Material -> stock = 0
     * B. Create PO -> stock remains 0
     * C. Confirm / ORDER PO -> stock remains 0
     * D. Receive PO -> stock increases by exact received qty, MWA updates, 1 StockMovement IN, history appears
     * E. Attempting to receive completed PO again -> must NOT increase stock a second time
     */
    public function test_full_purchase_lifecycle_stock_invariant_a_to_e(): void
    {
        // A. Create Material -> stock = 0
        $newMat = Material::create([
            'unit_id'       => $this->meter->id,
            'material_name' => 'Kain Satin Silk',
            'min_stock'     => 5,
            'active'        => true,
        ]);
        $stock = MaterialStock::create([
            'material_id'   => $newMat->id,
            'qty_on_hand'   => '0.0000',
            'avg_unit_cost' => '0.0000',
        ]);
        $this->assertEquals('0.0000', $stock->qty_on_hand);

        // B. Create Purchase Order -> stock remains 0
        $po = $this->poService->createPurchase([
            'supplier_id'   => $this->supplierA->id,
            'purchase_date' => '2026-09-10',
            'items'         => [
                [
                    'material_id' => $newMat->id,
                    'qty'         => '80.0000',
                    'unit_price'  => '45000.00',
                ],
            ],
        ], $this->gudangUser);
        $this->assertEquals(PurchaseStatus::DRAFT, $po->status);
        $this->assertEquals('0.0000', $stock->fresh()->qty_on_hand);

        // Pay PO before receiving -> auto ORDERED
        $this->payPurchase($po);
        $this->assertEquals(PurchaseStatus::ORDERED, $po->fresh()->status);
        $this->assertEquals('0.0000', $stock->fresh()->qty_on_hand);

        // D. Receive Purchase Order ->
        // - stock increases by exact received qty
        // - avg_unit_cost updates using MWA logic
        // - exactly one StockMovement IN exists
        // - Purchase History row appears automatically
        $receiveResponse = $this->actingAs($this->gudangUser)
            ->post(route('gudang.purchases.receive', $po));
        $receiveResponse->assertRedirect(route('gudang.purchases.show', $po));

        $freshPo = $po->fresh(['items']);
        $this->assertEquals(PurchaseStatus::RECEIVED, $freshPo->status);
        $this->assertEquals('80.0000', $freshPo->items->first()->qty_received);

        // Stock increased by exact 80.0000, MWA is 45000.0000
        $stock->refresh();
        $this->assertEquals('80.0000', $stock->qty_on_hand);
        $this->assertEquals('45000.0000', $stock->avg_unit_cost);

        // Exactly one StockMovement IN
        $movements = StockMovement::where('reference_type', 'purchase_items')
            ->where('reference_id', $freshPo->items->first()->id)
            ->where('movement_type', MovementType::IN)
            ->get();
        $this->assertCount(1, $movements);
        $this->assertEquals('80.0000', $movements->first()->qty);
        $this->assertEquals('45000.0000', $movements->first()->unit_cost_snapshot);

        // Purchase history row appears automatically
        $historyResponse = $this->actingAs($this->gudangUser)
            ->get(route('gudang.purchases.history'));
        $historyResponse->assertOk();
        $historyResponse->assertSee($po->purchase_no);
        $historyResponse->assertSee('Kain Satin Silk');
        $historyResponse->assertSee('80 m');
        $historyResponse->assertSee('Rp 45.000');
        $historyResponse->assertSee('Rp 3.600.000');

        // E. Attempting to receive the same completed PO again -> must NOT increase stock a second time
        // via controller
        $secondReceiveResponse = $this->actingAs($this->gudangUser)
            ->post(route('gudang.purchases.receive', $po));
        $secondReceiveResponse->assertRedirect(route('gudang.purchases.show', $po));
        $secondReceiveResponse->assertSessionHas('error');

        // via service directly
        try {
            $this->receivingService->receivePurchase($po, $this->gudangUser->id);
            $this->fail('Expected DomainException when receiving already-received purchase order.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('Hanya Purchase Order berstatus ORDERED yang dapat diterima', $e->getMessage());
        }

        // Stock must still strictly be 80.0000, not 160.0000
        $this->assertEquals('80.0000', $stock->fresh()->qty_on_hand);
        $this->assertCount(1, StockMovement::where('reference_type', 'purchase_items')->where('reference_id', $freshPo->items->first()->id)->get());
    }

    /**
     * Requirement 3: Stock Opname Boundary
     * Stock Opname is an ADJUSTMENT and never appears in Purchase History.
     */
    public function test_stock_opname_adjustment_is_strictly_excluded_from_purchase_history(): void
    {
        // Receive legitimate PO first
        $po = $this->poService->createPurchase([
            'supplier_id'   => $this->supplierA->id,
            'purchase_date' => '2026-09-01',
            'items'         => [
                ['material_id' => $this->materialA->id, 'qty' => '50.0000', 'unit_price' => '20000.00'],
            ],
        ], $this->gudangUser);
        $this->payPurchase($po);
        $this->receivingService->receivePurchase($po, $this->gudangUser->id);

        // Record a Stock Opname ADJUSTMENT
        StockMovement::create([
            'material_id'          => $this->materialA->id,
            'movement_type'        => MovementType::ADJUSTMENT,
            'reference_type'       => 'stock_opnames',
            'reference_id'         => 777,
            'qty'                  => '-5.0000',
            'unit_cost_snapshot'   => '20000.0000',
            'total_cost_snapshot'  => '-100000.0000',
            'movement_date'        => '2026-09-05',
            'notes'                => 'Penyesuaian Fisik Opname Bulan September',
            'created_by'           => $this->gudangUser->id,
        ]);

        $response = $this->actingAs($this->gudangUser)
            ->get(route('gudang.purchases.history'));

        $response->assertOk();
        // Purchase line appears
        $response->assertSee($po->purchase_no);
        $response->assertSee('50 m');
        // Opname adjustment is NOT in the purchase history
        $response->assertDontSee('Penyesuaian Fisik Opname Bulan September');
        $response->assertDontSee('-5 m');
        $response->assertDontSee('-5,0000');
    }

    /**
     * Requirement 4: Route Ordering
     * GET /gudang/purchases/history must resolve to history and never be swallowed by /gudang/purchases/{purchase}
     */
    public function test_route_ordering_history_is_not_swallowed_by_purchase_show(): void
    {
        $po = $this->poService->createPurchase([
            'supplier_id'   => $this->supplierA->id,
            'purchase_date' => '2026-09-01',
            'items'         => [
                ['material_id' => $this->materialA->id, 'qty' => '10.0000', 'unit_price' => '20000.00'],
            ],
        ], $this->gudangUser);

        // History route
        $historyResp = $this->actingAs($this->gudangUser)->get('/gudang/purchases/history');
        $historyResp->assertOk();
        $historyResp->assertViewIs('gudang.purchases.history');

        // Show route
        $showResp = $this->actingAs($this->gudangUser)->get('/gudang/purchases/' . $po->id);
        $showResp->assertOk();
        $showResp->assertViewIs('gudang.purchases.show');
    }

    /**
     * Requirement 4: Ordering newest receiving first & search filter
     */
    public function test_purchase_history_ordering_newest_first_and_search_filter(): void
    {
        // PO 1: received first
        $po1 = $this->poService->createPurchase([
            'supplier_id'   => $this->supplierA->id,
            'purchase_date' => '2026-09-01',
            'items'         => [
                ['material_id' => $this->materialA->id, 'qty' => '10.0000', 'unit_price' => '10000.00'],
            ],
        ], $this->gudangUser);
        $this->payPurchase($po1);
        $this->receivingService->receivePurchase($po1, $this->gudangUser->id);

        // PO 2: received later
        $po2 = $this->poService->createPurchase([
            'supplier_id'   => $this->supplierB->id,
            'purchase_date' => '2026-09-02',
            'items'         => [
                ['material_id' => $this->materialB->id, 'qty' => '20.0000', 'unit_price' => '20000.00'],
            ],
        ], $this->gudangUser);
        $this->payPurchase($po2);
        $this->receivingService->receivePurchase($po2, $this->gudangUser->id);

        // Search PO 1 specifically
        $searchResp = $this->actingAs($this->gudangUser)
            ->get(route('gudang.purchases.history', ['search' => $po1->purchase_no]));
        $searchResp->assertOk();
        $searchResp->assertSee($po1->purchase_no);
        $searchResp->assertDontSee($po2->purchase_no);
    }

    /**
     * Requirement 6: Security
     * Customer cannot access internal purchase prices or financial endpoints
     */
    public function test_customer_cannot_see_purchase_prices_or_internal_costs(): void
    {
        // Customer cannot access Gudang purchases
        $this->actingAs($this->customerUser)->get(route('gudang.purchases.index'))->assertForbidden();
        $this->actingAs($this->customerUser)->get(route('gudang.purchases.history'))->assertForbidden();
        $this->actingAs($this->customerUser)->get(route('gudang.materials.index'))->assertForbidden();

        // Customer cannot access Owner financial routes
        $this->actingAs($this->customerUser)->get(route('owner.dashboard'))->assertForbidden();
        $this->actingAs($this->customerUser)->get(route('owner.profitability.index'))->assertForbidden();
    }

    /**
     * Requirement: Filter toolbar layout, spacious gap, height consistency, and period date group
     */
    public function test_purchase_history_filter_toolbar_ui_layout_and_spacing(): void
    {
        $response = $this->actingAs($this->gudangUser)->get(route('gudang.purchases.history'));
        $response->assertOk();

        $content = $response->getContent();

        // 1. Grid gap and flexible one-row desktop layout with minmax(0, fr) and 128px compact filter
        $this->assertStringContainsString('gap-x-3', $content);
        $this->assertStringContainsString('xl:grid-cols-[minmax(0,1.05fr)_minmax(0,0.85fr)_minmax(0,1fr)_minmax(0,1.45fr)_128px]', $content);
        $this->assertStringNotContainsString('xl:grid-cols-12', $content);
        $this->assertStringNotContainsString('xl:grid-cols-[1.5fr_1fr_1fr_1.8fr_auto]', $content);
        $this->assertStringNotContainsString('minmax(250px', $content);

        // 2. Wrappers and controls use min-w-0
        $this->assertStringContainsString('min-w-0', $content);

        // 3. Labels and mb-2 spacing
        $this->assertStringContainsString('Pencarian', $content);
        $this->assertStringContainsString('Supplier', $content);
        $this->assertStringContainsString('Material', $content);
        $this->assertStringContainsString('Periode Terima', $content);
        $this->assertStringContainsString('mb-2', $content);

        // 4. Height consistency (h-10)
        $this->assertStringContainsString('h-10 pl-9', $content); // search
        $this->assertStringContainsString('h-10 px-3', $content); // select & dates
        $this->assertStringContainsString('h-10 inline-flex items-center justify-center', $content); // filter button

        // 5. Period date group 3-column inner grid and separator
        $this->assertStringContainsString('grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)]', $content);
        $this->assertStringContainsString('name="date_from"', $content);
        $this->assertStringContainsString('name="date_to"', $content);
        $this->assertStringContainsString('&ndash;', $content);
        $this->assertStringContainsString('gap-2', $content);

        // 6. Container padding
        $this->assertStringContainsString('rounded-2xl p-5 sm:px-6 sm:py-5 shadow-card', $content);
    }

    private function getMaterialSelectHtml(string $html): string
    {
        preg_match('/<select id="material_id"[^>]*>(.*?)<\/select>/s', $html, $matches);
        return $matches[1] ?? '';
    }

    /**
     * Dependent Filter Requirement 1, 2, 3:
     * - All Supplier shows all historical materials and excludes unpurchased materials
     * - Selecting a supplier limits Material options to historical materials of that supplier
     * - Materials from other suppliers do NOT appear
     */
    public function test_dependent_filter_material_options_per_supplier_and_excludes_other_suppliers(): void
    {
        // Material C is never purchased/received
        $materialC = Material::create([
            'unit_id'       => $this->meter->id,
            'material_name' => 'Kain Belum Pernah Dibeli',
            'min_stock'     => 5,
            'active'        => true,
        ]);

        // PO 1: Supplier A, Material A (Received)
        $po1 = $this->poService->createPurchase([
            'supplier_id'   => $this->supplierA->id,
            'purchase_date' => '2026-09-01',
            'items'         => [
                ['material_id' => $this->materialA->id, 'qty' => '10.0000', 'unit_price' => '25000.00'],
            ],
        ], $this->gudangUser);
        $this->payPurchase($po1);
        $this->receivingService->receivePurchase($po1, $this->gudangUser->id);

        // PO 2: Supplier B, Material B (Received)
        $po2 = $this->poService->createPurchase([
            'supplier_id'   => $this->supplierB->id,
            'purchase_date' => '2026-09-02',
            'items'         => [
                ['material_id' => $this->materialB->id, 'qty' => '20.0000', 'unit_price' => '30000.00'],
            ],
        ], $this->gudangUser);
        $this->payPurchase($po2);
        $this->receivingService->receivePurchase($po2, $this->gudangUser->id);

        // Case 1: All Supplier -> shows Material A and Material B, excludes Material C
        $responseAll = $this->actingAs($this->gudangUser)->get(route('gudang.purchases.history'));
        $responseAll->assertOk();
        $selectHtmlAll = $this->getMaterialSelectHtml($responseAll->getContent());
        $this->assertStringContainsString('Semua Material', $selectHtmlAll);
        $this->assertStringContainsString('value="' . $this->materialA->id . '"', $selectHtmlAll);
        $this->assertStringContainsString('Kain Kaos Hitam (m)', $selectHtmlAll);
        $this->assertStringContainsString('value="' . $this->materialB->id . '"', $selectHtmlAll);
        $this->assertStringContainsString('Kain Kaos Mocca (m)', $selectHtmlAll);
        $this->assertStringNotContainsString('Kain Belum Pernah Dibeli', $selectHtmlAll);

        // Case 2: Supplier A selected -> shows only Material A, excludes Material B and Material C
        $responseSupA = $this->actingAs($this->gudangUser)
            ->get(route('gudang.purchases.history', ['supplier_id' => $this->supplierA->id]));
        $responseSupA->assertOk();
        $selectHtmlA = $this->getMaterialSelectHtml($responseSupA->getContent());
        $this->assertStringContainsString('value="' . $this->materialA->id . '"', $selectHtmlA);
        $this->assertStringContainsString('Kain Kaos Hitam (m)', $selectHtmlA);
        $this->assertStringNotContainsString('value="' . $this->materialB->id . '"', $selectHtmlA);
        $this->assertStringNotContainsString('Kain Kaos Mocca', $selectHtmlA);
        $this->assertStringNotContainsString('Kain Belum Pernah Dibeli', $selectHtmlA);

        // Case 3: Supplier B selected -> shows only Material B, excludes Material A and Material C
        $responseSupB = $this->actingAs($this->gudangUser)
            ->get(route('gudang.purchases.history', ['supplier_id' => $this->supplierB->id]));
        $responseSupB->assertOk();
        $selectHtmlB = $this->getMaterialSelectHtml($responseSupB->getContent());
        $this->assertStringContainsString('value="' . $this->materialB->id . '"', $selectHtmlB);
        $this->assertStringContainsString('Kain Kaos Mocca (m)', $selectHtmlB);
        $this->assertStringNotContainsString('value="' . $this->materialA->id . '"', $selectHtmlB);
        $this->assertStringNotContainsString('Kain Kaos Hitam', $selectHtmlB);
        $this->assertStringNotContainsString('Kain Belum Pernah Dibeli', $selectHtmlB);
    }

    /**
     * Dependent Filter Requirement 4, 5, 6:
     * - Valid supplier-material pair persists after submit
     * - Manual invalid query pair is normalized safely (materialFilter reset to null, Supplier items shown, dropdown consistent)
     * - Client-side script ensures instantaneous reset of incompatible material on supplier change
     */
    public function test_dependent_filter_persistence_safe_normalization_and_client_script(): void
    {
        // PO 1: Supplier A, Material A
        $po1 = $this->poService->createPurchase([
            'supplier_id'   => $this->supplierA->id,
            'purchase_date' => '2026-09-01',
            'items'         => [
                ['material_id' => $this->materialA->id, 'qty' => '10.0000', 'unit_price' => '25000.00'],
            ],
        ], $this->gudangUser);
        $this->payPurchase($po1);
        $this->receivingService->receivePurchase($po1, $this->gudangUser->id);

        // PO 2: Supplier B, Material B
        $po2 = $this->poService->createPurchase([
            'supplier_id'   => $this->supplierB->id,
            'purchase_date' => '2026-09-02',
            'items'         => [
                ['material_id' => $this->materialB->id, 'qty' => '20.0000', 'unit_price' => '30000.00'],
            ],
        ], $this->gudangUser);
        $this->payPurchase($po2);
        $this->receivingService->receivePurchase($po2, $this->gudangUser->id);

        // 1. Valid pair persists after submit
        $validResponse = $this->actingAs($this->gudangUser)->get(route('gudang.purchases.history', [
            'supplier_id' => $this->supplierA->id,
            'material_id' => $this->materialA->id,
        ]));
        $validResponse->assertOk();
        $selectHtmlValid = $this->getMaterialSelectHtml($validResponse->getContent());
        $this->assertStringContainsString('value="' . $this->materialA->id . '" selected', $selectHtmlValid);
        $validResponse->assertSee('<option value="' . $this->supplierA->id . '" selected>', false);
        $validResponse->assertSee($po1->purchase_no);
        $validResponse->assertDontSee($po2->purchase_no);

        // 2. Manual invalid query pair is safely normalized:
        // Request: Supplier A + Material B (Material B belongs to Supplier B, not Supplier A)
        $invalidResponse = $this->actingAs($this->gudangUser)->get(route('gudang.purchases.history', [
            'supplier_id' => $this->supplierA->id,
            'material_id' => $this->materialB->id,
        ]));
        $invalidResponse->assertOk();
        // Server normalizes by ignoring the invalid material filter -> Supplier A items are returned (NOT 0 rows)
        $invalidResponse->assertSee($po1->purchase_no);
        $invalidResponse->assertDontSee($po2->purchase_no);
        // In the rendered material dropdown, Material B is NOT present in Supplier A's options
        $selectHtmlInvalid = $this->getMaterialSelectHtml($invalidResponse->getContent());
        $this->assertStringNotContainsString('value="' . $this->materialB->id . '"', $selectHtmlInvalid);
        $this->assertStringNotContainsString('Kain Kaos Mocca', $selectHtmlInvalid);
        // Material A is present and NOT selected (defaults to Semua Material)
        $this->assertStringContainsString('value="' . $this->materialA->id . '"', $selectHtmlInvalid);
        $this->assertStringNotContainsString('selected', $selectHtmlInvalid);

        // 3. Client-side UX script check
        $content = $invalidResponse->getContent();
        $this->assertStringContainsString("supplierSelect.addEventListener('change', updateMaterialOptions)", $content);
        $this->assertStringContainsString("supplierMaterialMap", $content);
        $this->assertStringContainsString("materialSelect.value = ''", $content);
    }

    /**
     * Dependent Filter Requirement 7, 8, 9:
     * - Date filter still works
     * - Search still works
     * - Pagination query persistence preserves all filter parameters
     */
    public function test_dependent_filter_date_search_and_pagination_query_persistence(): void
    {
        // Create 25 received purchases for pagination testing
        for ($i = 1; $i <= 25; $i++) {
            $date = Carbon::parse('2026-09-01')->addDays($i - 1)->toDateString();
            $po = $this->poService->createPurchase([
                'supplier_id'   => $this->supplierA->id,
                'purchase_date' => $date,
                'items'         => [
                    ['material_id' => $this->materialA->id, 'qty' => '1.0000', 'unit_price' => '10000.00'],
                ],
            ], $this->gudangUser);
            $this->payPurchase($po);
            $this->receivingService->receivePurchase($po, $this->gudangUser->id);
        }

        // Search + Date filter + Supplier filter + Material filter + Page 2
        $response = $this->actingAs($this->gudangUser)->get(route('gudang.purchases.history', [
            'supplier_id' => $this->supplierA->id,
            'material_id' => $this->materialA->id,
            'search'      => 'PO-',
            'date_from'   => '2026-09-01',
            'date_to'     => '2026-09-30',
            'page'        => 2,
        ]));

        $response->assertOk();
        $content = $response->getContent();
        $this->assertStringContainsString('supplier_id=' . $this->supplierA->id, $content);
        $this->assertStringContainsString('material_id=' . $this->materialA->id, $content);
        $this->assertStringContainsString('search=PO-', $content);
        $this->assertStringContainsString('date_from=2026-09-01', $content);
        $this->assertStringContainsString('date_to=2026-09-30', $content);
    }

    /**
     * Dependent Filter Requirement 10:
     * - No N+1 query regression on purchase history
     */
    public function test_dependent_filter_no_n_plus_one_query_regression(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $po = $this->poService->createPurchase([
                'supplier_id'   => $this->supplierA->id,
                'purchase_date' => Carbon::parse('2026-09-01')->addDays($i)->toDateString(),
                'items'         => [
                    ['material_id' => $this->materialA->id, 'qty' => '1.0000', 'unit_price' => '10000.00'],
                ],
            ], $this->gudangUser);
            $this->payPurchase($po);
            $this->receivingService->receivePurchase($po, $this->gudangUser->id);
        }

        \Illuminate\Support\Facades\DB::flushQueryLog();
        \Illuminate\Support\Facades\DB::enableQueryLog();

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.purchases.history'));
        $response->assertOk();

        $queries = \Illuminate\Support\Facades\DB::getQueryLog();
        $this->assertLessThanOrEqual(15, count($queries), 'Expected no N+1 query regression on purchase history.');
    }
}
