<?php

namespace Tests\Feature\Admin;

use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Master\Customer;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Production\ProductionBatch;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class KanbanIndependentBatchProgressTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Customer $customer;
    private Product $product;
    private ProductVariant $variantA;
    private ProductVariant $variantB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role'   => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->customer = Customer::create([
            'customer_name' => 'Toko Cinay Hijab',
            'phone'         => '083409875678',
            'address'       => 'Cikarang',
            'active'        => true,
        ]);

        $this->product = Product::create([
            'product_name' => 'Hijab Segi Empat',
            'active'       => true,
        ]);

        $this->variantA = ProductVariant::create([
            'product_id'        => $this->product->id,
            'variant_name'      => 'Latte',
            'sku'               => 'HJB-SE-LAT',
            'requires_printing' => false,
            'active'            => true,
        ]);

        $this->variantB = ProductVariant::create([
            'product_id'        => $this->product->id,
            'variant_name'      => 'Navy',
            'sku'               => 'HJB-SE-NAV',
            'requires_printing' => false,
            'active'            => true,
        ]);
    }

    /**
     * Helper to extract the HTML for a given Kanban column using DOMXPath.
     */
    private function extractColumnHtml(string $html, string $columnKey): string
    {
        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML($html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($doc);
        $node = $xpath->query("//div[@data-kanban-column='{$columnKey}']")->item(0);

        return $node ? $doc->saveHTML($node) : '';
    }

    public function test_kanban_renders_independent_batch_cards_under_their_own_status_columns(): void
    {
        // Setup SO-202610-0003 with 2 batches:
        // Batch A -> QC
        // Batch B -> BAHAN
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-202610-0003',
            'order_date'  => '2026-10-08',
            'status'      => OrderStatus::BAHAN, // Aggregate order status remains BAHAN
        ]);

        $itemA = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variantA->id,
            'qty_ordered'        => 18,
            'unit_selling_price' => '89000.00',
        ]);

        $batchA = ProductionBatch::create([
            'order_item_id'    => $itemA->id,
            'batch_no'         => 'BAT-202610-0003',
            'target_qty'       => 18,
            'status'           => BatchStatus::QC,
            'planned_end_date' => '2026-10-12',
        ]);

        $itemB = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variantB->id,
            'qty_ordered'        => 19,
            'unit_selling_price' => '85000.00',
        ]);

        $batchB = ProductionBatch::create([
            'order_item_id'    => $itemB->id,
            'batch_no'         => 'BAT-202610-0004',
            'target_qty'       => 19,
            'status'           => BatchStatus::BAHAN,
            'planned_end_date' => '2026-10-12',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.kanban.index'));

        $response->assertOk();

        // 1. View Data Columns and Counters
        $columns = $response->viewData('columns');
        $this->assertEquals(1, $columns['QC']['total_count'], 'QC counter must be 1 for Batch A');
        $this->assertEquals(1, $columns['BAHAN']['total_count'], 'BAHAN counter must be 1 for Batch B');
        $this->assertEquals(0, $columns['PRODUKSI']['total_count'], 'PRODUKSI counter must be 0');

        // 2. HTML DOM Assertions
        $html = $response->getContent();

        $qcColHtml = $this->extractColumnHtml($html, 'QC');
        $bahanColHtml = $this->extractColumnHtml($html, 'BAHAN');
        $produksiColHtml = $this->extractColumnHtml($html, 'PRODUKSI');

        // Batch A rendered under QC
        $this->assertStringContainsString('BAT-202610-0003', $qcColHtml);
        $this->assertStringContainsString('SO-202610-0003', $qcColHtml);
        $this->assertStringContainsString('Toko Cinay Hijab', $qcColHtml);
        $this->assertStringContainsString('Latte', $qcColHtml);
        $this->assertStringContainsString('18 pcs', $qcColHtml);
        $this->assertStringContainsString(route('admin.batches.show', $batchA), $qcColHtml);

        // Batch B is NOT rendered inside QC
        $this->assertStringNotContainsString('BAT-202610-0004', $qcColHtml);

        // Batch B rendered under BAHAN
        $this->assertStringContainsString('BAT-202610-0004', $bahanColHtml);
        $this->assertStringContainsString('SO-202610-0003', $bahanColHtml);
        $this->assertStringContainsString('Toko Cinay Hijab', $bahanColHtml);
        $this->assertStringContainsString('Navy', $bahanColHtml);
        $this->assertStringContainsString('19 pcs', $bahanColHtml);
        $this->assertStringContainsString(route('admin.batches.show', $batchB), $bahanColHtml);

        // Batch A is NOT rendered inside BAHAN card
        $this->assertStringNotContainsString('BAT-202610-0003', $bahanColHtml);

        // Neither rendered under PRODUKSI
        $this->assertStringNotContainsString('BAT-202610-0003', $produksiColHtml);
        $this->assertStringNotContainsString('BAT-202610-0004', $produksiColHtml);

        // Sales Order is not duplicated as an aggregate production card hiding both batches
        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML($html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($doc);

        $qcOrderCards = $xpath->query("//div[@data-kanban-column='QC']//div[@data-card-type='order']");
        $bahanOrderCards = $xpath->query("//div[@data-kanban-column='BAHAN']//div[@data-card-type='order']");
        $qcBatchCards = $xpath->query("//div[@data-kanban-column='QC']//div[@data-card-type='batch']");
        $bahanBatchCards = $xpath->query("//div[@data-kanban-column='BAHAN']//div[@data-card-type='batch']");

        $this->assertEquals(0, $qcOrderCards->length, 'No aggregate order card in QC column');
        $this->assertEquals(0, $bahanOrderCards->length, 'No aggregate order card in BAHAN column');
        $this->assertEquals(1, $qcBatchCards->length, 'Exactly 1 batch card in QC column');
        $this->assertEquals(1, $bahanBatchCards->length, 'Exactly 1 batch card in BAHAN column');

        // 3. Database Data Safety: Statuses are NOT mutated
        $this->assertSame(OrderStatus::BAHAN, $order->fresh()->status);
        $this->assertSame(BatchStatus::QC, $batchA->fresh()->status);
        $this->assertSame(BatchStatus::BAHAN, $batchB->fresh()->status);
    }

    public function test_same_order_batches_in_produksi_and_qc_appear_independently(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-INDEP-001',
            'order_date'  => '2026-10-09',
            'status'      => OrderStatus::PRODUKSI,
        ]);

        $itemA = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variantA->id,
            'qty_ordered'        => 50,
            'unit_selling_price' => '75000.00',
        ]);

        $batchQc = ProductionBatch::create([
            'order_item_id' => $itemA->id,
            'batch_no'      => 'BAT-QC-001',
            'target_qty'    => 50,
            'status'        => BatchStatus::QC,
        ]);

        $itemB = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variantB->id,
            'qty_ordered'        => 50,
            'unit_selling_price' => '75000.00',
        ]);

        $batchProd = ProductionBatch::create([
            'order_item_id' => $itemB->id,
            'batch_no'      => 'BAT-PROD-001',
            'target_qty'    => 50,
            'status'        => BatchStatus::PRODUKSI,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.kanban.index'));

        $columns = $response->viewData('columns');
        $this->assertEquals(1, $columns['QC']['total_count']);
        $this->assertEquals(1, $columns['PRODUKSI']['total_count']);

        $html = $response->getContent();
        $qcColHtml = $this->extractColumnHtml($html, 'QC');
        $prodColHtml = $this->extractColumnHtml($html, 'PRODUKSI');

        $this->assertStringContainsString('BAT-QC-001', $qcColHtml);
        $this->assertStringNotContainsString('BAT-PROD-001', $qcColHtml);

        $this->assertStringContainsString('BAT-PROD-001', $prodColHtml);
        $this->assertStringNotContainsString('BAT-QC-001', $prodColHtml);
    }

    public function test_completed_batch_does_not_wait_for_sibling_in_earlier_stage(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-SELESAI-SIB',
            'order_date'  => '2026-10-09',
            'status'      => OrderStatus::PRODUKSI, // Parent order still active in PRODUKSI
        ]);

        $itemA = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variantA->id,
            'qty_ordered'        => 25,
            'unit_selling_price' => '75000.00',
        ]);

        $batchDone = ProductionBatch::create([
            'order_item_id' => $itemA->id,
            'batch_no'      => 'BAT-DONE-001',
            'target_qty'    => 25,
            'status'        => BatchStatus::SELESAI,
        ]);

        $itemB = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variantB->id,
            'qty_ordered'        => 25,
            'unit_selling_price' => '75000.00',
        ]);

        $batchActive = ProductionBatch::create([
            'order_item_id' => $itemB->id,
            'batch_no'      => 'BAT-ACTIVE-001',
            'target_qty'    => 25,
            'status'        => BatchStatus::PRODUKSI,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.kanban.index'));

        $html = $response->getContent();
        $prodColHtml = $this->extractColumnHtml($html, 'PRODUKSI');
        $selesaiColHtml = $this->extractColumnHtml($html, 'SELESAI');

        // Active sibling in PRODUKSI
        $this->assertStringContainsString('BAT-ACTIVE-001', $prodColHtml);
        $this->assertStringNotContainsString('BAT-DONE-001', $prodColHtml);

        // Completed batch rendered under SELESAI, does not wait for sibling in PRODUKSI
        $this->assertStringContainsString('BAT-DONE-001', $selesaiColHtml);
        $this->assertStringNotContainsString('BAT-ACTIVE-001', $selesaiColHtml);
    }

    public function test_search_filter_matches_batch_no_customer_and_variant(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-SEARCH-001',
            'order_date'  => '2026-10-10',
            'status'      => OrderStatus::QC,
        ]);

        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variantA->id,
            'qty_ordered'        => 30,
            'unit_selling_price' => '75000.00',
        ]);

        $batch = ProductionBatch::create([
            'order_item_id' => $item->id,
            'batch_no'      => 'BAT-FINDME-777',
            'target_qty'    => 30,
            'status'        => BatchStatus::QC,
        ]);

        // Search by batch_no
        $resByBatch = $this->actingAs($this->admin)->get(route('admin.kanban.index', ['search' => 'FINDME-777']));
        $resByBatch->assertSee('BAT-FINDME-777');

        // Search by variant name
        $resByVariant = $this->actingAs($this->admin)->get(route('admin.kanban.index', ['search' => 'Latte']));
        $resByVariant->assertSee('BAT-FINDME-777');

        // Search for non-existing term
        $resNotFound = $this->actingAs($this->admin)->get(route('admin.kanban.index', ['search' => 'NONEXISTENT999']));
        $resNotFound->assertDontSee('BAT-FINDME-777');
    }

    public function test_printing_route_respected_in_kanban(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-PRINT-001',
            'order_date'  => '2026-10-10',
            'status'      => OrderStatus::PRINTING,
        ]);

        $itemA = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variantA->id,
            'qty_ordered'        => 20,
            'unit_selling_price' => '80000.00',
        ]);

        // Batch requiring printing in PRINTING status
        $batchWithPrinting = ProductionBatch::create([
            'order_item_id'     => $itemA->id,
            'batch_no'          => 'BAT-PRINT-001',
            'target_qty'        => 20,
            'requires_printing' => true,
            'status'            => BatchStatus::PRINTING,
        ]);

        $itemB = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variantB->id,
            'qty_ordered'        => 20,
            'unit_selling_price' => '80000.00',
        ]);

        // Batch without printing in PRODUKSI status (skipped PRINTING naturally)
        $batchWithoutPrinting = ProductionBatch::create([
            'order_item_id'     => $itemB->id,
            'batch_no'          => 'BAT-NOPRINT-001',
            'target_qty'        => 20,
            'requires_printing' => false,
            'status'            => BatchStatus::PRODUKSI,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.kanban.index'));

        $columns = $response->viewData('columns');
        $this->assertEquals(1, $columns['PRINTING']['total_count']);
        $this->assertEquals(1, $columns['PRODUKSI']['total_count']);

        $html = $response->getContent();
        $printColHtml = $this->extractColumnHtml($html, 'PRINTING');
        $prodColHtml = $this->extractColumnHtml($html, 'PRODUKSI');

        $this->assertStringContainsString('BAT-PRINT-001', $printColHtml);
        $this->assertStringNotContainsString('BAT-NOPRINT-001', $printColHtml);

        $this->assertStringContainsString('BAT-NOPRINT-001', $prodColHtml);
        $this->assertStringNotContainsString('BAT-PRINT-001', $prodColHtml);
    }
}
