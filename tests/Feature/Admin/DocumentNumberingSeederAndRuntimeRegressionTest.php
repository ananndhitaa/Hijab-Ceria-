<?php

namespace Tests\Feature\Admin;

use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Enums\PurchaseStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Inventory\Purchase;
use App\Models\Master\Customer;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Master\Supplier;
use App\Models\Production\BatchMaterial;
use App\Models\Production\ProductionBatch;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\System\DocumentSequence;
use App\Models\System\User;
use Database\Seeders\CustomerSeeder;
use Database\Seeders\MaterialSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\SupplierSeeder;
use Database\Seeders\UatScenarioSeeder;
use Database\Seeders\UnitSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentNumberingSeederAndRuntimeRegressionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $gudang;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        \Carbon\Carbon::setTestNow('2026-09-06 10:00:00');

        // Run foundational and UAT seeders
        $this->seed([
            UserSeeder::class,
            UnitSeeder::class,
            MaterialSeeder::class,
            ProductSeeder::class,
            CustomerSeeder::class,
            SupplierSeeder::class,
            UatScenarioSeeder::class,
        ]);

        $this->admin  = User::where('role', UserRole::ADMIN)->first();
        $this->gudang = User::where('role', UserRole::GUDANG)->first();
        $this->owner  = User::where('role', UserRole::OWNER)->first();
    }

    protected function tearDown(): void
    {
        \Carbon\Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_exact_uat_scenario_admin_creates_batch_after_seed_produces_bat_0004_without_collision(): void
    {
        // 1. Verify sequence authority state immediately after seed
        $batSeq = DocumentSequence::where('prefix', 'BAT')->where('period', '202609')->first();
        $this->assertNotNull($batSeq, 'Document sequence for BAT 202609 must exist after seed.');
        $this->assertSame(3, $batSeq->last_sequence, 'Seeded BAT sequence must be 3.');

        // 2. Identify eligible Order 1 (DEAL status, no batch yet)
        $order1 = Order::where('order_no', 'SO-202609-0001')->with('items.variant')->firstOrFail();
        $this->assertSame(OrderStatus::DEAL, $order1->status);
        $item = $order1->items->first();
        $this->assertNotNull($item);

        $inv1 = \App\Models\Sales\Invoice::create([
            'order_id'     => $order1->id,
            'invoice_no'   => 'INV-UAT-001',
            'invoice_date' => '2026-09-06',
            'total_amount' => '4250000.00',
            'status'       => \App\Enums\InvoiceStatus::PARTIAL,
        ]);
        \App\Models\Sales\Payment::create([
            'invoice_id'   => $inv1->id,
            'payment_no'   => 'PAY-UAT-001',
            'payment_date' => '2026-09-06',
            'amount'       => '2000000.00',
            'payment_type' => \App\Enums\PaymentType::DP,
            'status'       => \App\Enums\PaymentStatus::CONFIRMED,
        ]);

        // 3. Admin submits POST /admin/orders/{order}/batches (exact manual UAT request)
        $response = $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order1), [
            'order_item_id'      => $item->id,
            'target_qty'         => 50,
            'planned_start_date' => '2026-09-06',
            'planned_end_date'   => '2026-09-08',
            'notes'              => 'Batch UAT creation test.',
        ]);

        // 4. Assert HTTP success (redirect 302 to show page, NO 500 UniqueConstraintViolationException)
        $response->assertSessionHasNoErrors();
        $newBatch = ProductionBatch::where('batch_no', 'BAT-202609-0004')->first();
        $this->assertNotNull($newBatch, 'New batch BAT-202609-0004 must be created successfully.');
        $response->assertRedirect(route('admin.batches.show', $newBatch));

        // 5. Assert batch attributes, automatic MRP, route & cost rate snapshots
        $this->assertSame('BAT-202609-0004', $newBatch->batch_no);
        $this->assertSame(50.0, (float) $newBatch->target_qty);
        $this->assertSame(BatchStatus::BAHAN, $newBatch->status);
        $this->assertFalse($newBatch->requires_printing);
        $this->assertSame('0.00', $newBatch->printing_cost_per_unit_snapshot);
        $this->assertSame('5000.00', $newBatch->direct_labor_cost_per_unit_snapshot);
        $this->assertSame('1000.00', $newBatch->packaging_process_cost_per_good_unit_snapshot);
        $this->assertSame('500.00', $newBatch->other_direct_cost_per_unit_snapshot);

        // 6. Assert automatic MRP generated BatchMaterial snapshots
        $materialsCount = BatchMaterial::where('batch_id', $newBatch->id)->count();
        $this->assertGreaterThan(0, $materialsCount, 'BatchMaterial rows must be generated automatically from BOM.');

        // 7. Assert Order status handoff from DEAL to BAHAN
        $order1->refresh();
        $this->assertSame(OrderStatus::BAHAN, $order1->status);

        // 8. Assert document_sequences updated to 4
        $batSeq->refresh();
        $this->assertSame(4, $batSeq->last_sequence);
    }

    public function test_real_create_flow_for_so_po_and_inv_generates_next_expected_numbers(): void
    {
        // Assert initial sequence counters from seed
        $this->assertSame(3, (int) DocumentSequence::where('prefix', 'SO')->where('period', '202609')->value('last_sequence'));
        $this->assertSame(2, (int) DocumentSequence::where('prefix', 'INV')->where('period', '202609')->value('last_sequence'));

        // ── 1. Create next Sales Order via Admin OrderController ──────────
        $cust = Customer::first();
        $variant = ProductVariant::where('active', true)->first();
        $orderResponse = $this->actingAs($this->admin)->post(route('admin.orders.store'), [
            'customer_id' => $cust->id,
            'order_date'  => '2026-09-06',
            'notes'       => 'Next SO test',
            'items'       => [
                [
                    'variant_id'  => $variant->id,
                    'qty_ordered' => 20,
                ],
            ],
        ]);
        $orderResponse->assertSessionHasNoErrors();
        $newOrder = Order::where('order_no', 'SO-202609-0004')->first();
        $this->assertNotNull($newOrder, 'Next Sales Order must receive SO-202609-0004.');
        $this->assertSame(4, (int) DocumentSequence::where('prefix', 'SO')->where('period', '202609')->value('last_sequence'));

        // ── 2. Create next Invoice via Admin InvoiceController ────────────
        $newOrder->update(['status' => OrderStatus::DEAL]);
        $invoiceResponse = $this->actingAs($this->admin)->post(route('admin.orders.invoice.store', $newOrder));
        $invoiceResponse->assertSessionHasNoErrors();
        $newInvoice = Invoice::where('invoice_no', 'INV-202609-0003')->first();
        $this->assertNotNull($newInvoice, 'Next Invoice must receive INV-202609-0003.');
        $this->assertSame(3, (int) DocumentSequence::where('prefix', 'INV')->where('period', '202609')->value('last_sequence'));

        // ── 3. Create next Purchase Order via Gudang PurchaseController ───
        $supplier = Supplier::where('active', true)->first();
        $material = Material::where('active', true)->first();
        $supplier->materials()->syncWithoutDetaching([$material->id]);
        $poResponse = $this->actingAs($this->gudang)->post(route('gudang.purchases.store'), [
            'supplier_id'   => $supplier->id,
            'purchase_date' => '2026-09-06',
            'notes'         => 'First PO test',
            'items'         => [
                [
                    'material_id' => $material->id,
                    'qty'         => 50,
                    'unit_price'  => 25000,
                ],
            ],
        ]);
        $poResponse->assertSessionHasNoErrors();
        $newPo = Purchase::where('purchase_no', 'PO-202609-0001')->first();
        $this->assertNotNull($newPo, 'First Purchase Order must receive PO-202609-0001.');
        $this->assertSame(1, (int) DocumentSequence::where('prefix', 'PO')->where('period', '202609')->value('last_sequence'));
    }

    public function test_artificial_batch_number_collision_rolls_back_entire_transaction_and_fails_friendly(): void
    {
        $order1 = Order::where('order_no', 'SO-202609-0001')->with('items')->firstOrFail();
        $item = $order1->items->first();

        $inv1 = \App\Models\Sales\Invoice::create([
            'order_id'     => $order1->id,
            'invoice_no'   => 'INV-UAT-002',
            'invoice_date' => '2026-09-06',
            'total_amount' => '4250000.00',
            'status'       => \App\Enums\InvoiceStatus::PARTIAL,
        ]);
        \App\Models\Sales\Payment::create([
            'invoice_id'   => $inv1->id,
            'payment_no'   => 'PAY-UAT-002',
            'payment_date' => '2026-09-06',
            'amount'       => '2000000.00',
            'payment_type' => \App\Enums\PaymentType::DP,
            'status'       => \App\Enums\PaymentStatus::CONFIRMED,
        ]);

        // Artificially insert a future batch with BAT-202609-0004 while leaving sequence at 3
        // to simulate an external numbering collision
        ProductionBatch::create([
            'order_item_id'                                 => $item->id,
            'batch_no'                                      => 'BAT-202609-0004',
            'target_qty'                                    => 1,
            'status'                                        => BatchStatus::CANCELLED,
            'requires_printing'                             => false,
            'printing_cost_per_unit_snapshot'               => '0.00',
            'direct_labor_cost_per_unit_snapshot'           => '5000.00',
            'packaging_process_cost_per_good_unit_snapshot' => '1000.00',
            'other_direct_cost_per_unit_snapshot'           => '500.00',
        ]);

        $initialBatchesCount = ProductionBatch::count();
        $initialBatchMaterialsCount = BatchMaterial::count();

        // Attempt to create batch (generator will return BAT-202609-0004 which hits collision)
        $response = $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order1), [
            'order_item_id'      => $item->id,
            'target_qty'         => 50,
            'planned_start_date' => '2026-09-06',
            'planned_end_date'   => '2026-09-08',
        ]);

        // Must redirect back with friendly flash error message (NO raw Laravel 500 error screen)
        $response->assertRedirect();
        $response->assertSessionHas('error', 'Nomor batch mengalami konflik saat dibuat. Silakan coba kembali.');

        // Entire transaction must be completely rolled back
        $this->assertSame($initialBatchesCount, ProductionBatch::count(), 'No new batch should remain committed.');
        $this->assertSame($initialBatchMaterialsCount, BatchMaterial::count(), 'No orphaned BatchMaterial rows should remain.');

        $order1->refresh();
        $this->assertSame(OrderStatus::DEAL, $order1->status, 'Order status must remain unchanged on collision.');
    }

    public function test_owner_hpp_ready_and_not_ready_filter_smoke(): void
    {
        // Verify GET /owner/hpp with READY filter executes without column not found SQL error
        $readyResponse = $this->actingAs($this->owner)->get(route('owner.hpp.index', ['status' => 'READY']));
        $readyResponse->assertOk();
        $readyResponse->assertDontSee('direct_costs_confirmed_at');

        // Verify GET /owner/hpp with NOT_READY filter executes cleanly
        $notReadyResponse = $this->actingAs($this->owner)->get(route('owner.hpp.index', ['status' => 'NOT_READY']));
        $notReadyResponse->assertOk();
    }
}
