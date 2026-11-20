<?php

namespace Tests\Feature\Admin;

use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Inventory\MaterialStock;
use App\Models\Master\BomItem;
use App\Models\Master\Customer;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Master\Unit;
use App\Models\Production\BatchMaterial;
use App\Models\Production\ProductionBatch;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\OrderStatusHistory;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionBatchCreationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Customer $customer;
    private ProductVariant $variant;
    private Material $material;
    private Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->customer = Customer::create(['customer_name' => 'Butik Al-Fatih']);

        $this->unit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $this->material = Material::create([
            'unit_id'       => $this->unit->id,
            'material_name' => 'Kain Sutra Silk',
            'min_stock'     => 10,
            'active'        => true,
        ]);
        MaterialStock::create(['material_id' => $this->material->id, 'qty_on_hand' => 500]);

        $product = Product::create(['product_name' => 'Pashmina Silk', 'active' => true]);
        $this->variant = ProductVariant::create([
            'product_id'                           => $product->id,
            'variant_name'                         => 'Dusty Pink',
            'sku'                                  => 'PAS-SILK-DPK',
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => '0.00',
            'direct_labor_cost_per_unit'           => '5000.00',
            'packaging_process_cost_per_good_unit' => '1000.00',
            'other_direct_cost_per_unit'           => '500.00',
        ]);

        BomItem::create([
            'variant_id'   => $this->variant->id,
            'material_id'  => $this->material->id,
            'qty_per_unit' => 1.2000,
        ]);
    }

    private function createOrder(OrderStatus $status = OrderStatus::DEAL, float $qty = 1000, bool $withConfirmedDp = true): Order
    {
        static $seq = 1;
        $orderNo = sprintf('SO-202608-%04d', $seq++);
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => $orderNo,
            'order_date'  => '2026-08-10',
            'status'      => $status,
        ]);

        $order->items()->create([
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => $qty,
            'unit_selling_price' => '75000.00',
        ]);

        OrderStatusHistory::create([
            'order_id'           => $order->id,
            'status'             => $status,
            'changed_by_user_id' => $this->admin->id,
            'changed_at'         => now(),
            'notes'              => 'Initial status.',
        ]);

        if ($withConfirmedDp && in_array($status, [OrderStatus::DEAL, OrderStatus::BAHAN], true)) {
            $totalAmount = (string) ($qty * 75000);
            $invoice = \App\Models\Sales\Invoice::create([
                'order_id'     => $order->id,
                'invoice_no'   => 'INV-' . uniqid(),
                'invoice_date' => now()->toDateString(),
                'total_amount' => $totalAmount,
                'status'       => \App\Enums\InvoiceStatus::PARTIAL,
            ]);

            \App\Models\Sales\Payment::create([
                'invoice_id'   => $invoice->id,
                'payment_no'   => 'PAY-' . uniqid(),
                'payment_date' => now()->toDateString(),
                'amount'       => bcmul($totalAmount, '0.5', 2),
                'payment_type' => \App\Enums\PaymentType::DP,
                'status'       => \App\Enums\PaymentStatus::CONFIRMED,
            ]);
        }

        return $order;
    }

    public function test_admin_can_open_batch_create_page_for_deal_order(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 500);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.batches.create', $order));

        $response->assertStatus(200);
        $response->assertSee('Buat Batch Produksi');
        $response->assertSee($order->order_no);
        $response->assertSee('500 pcs');
        $response->assertSee('Jadwal Rencana (Opsional)');
        $response->assertSee('Rencana Mulai Produksi');
        $response->assertSee('Target Selesai Produksi');
        $response->assertSee('Jadwal ini hanya rencana kerja. Proses aktual dijalankan oleh tim Produksi.');
    }

    public function test_admin_can_open_batch_create_page_for_bahan_order(): void
    {
        $order = $this->createOrder(OrderStatus::BAHAN, 1000);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.batches.create', $order));

        $response->assertStatus(200);
        $response->assertSee('Buat Batch Produksi');
    }

    public function test_batch_creation_rejected_for_komunikasi_order(): void
    {
        $order = $this->createOrder(OrderStatus::KOMUNIKASI, 500);
        $item = $order->items->first();

        $response = $this->actingAs($this->admin)->get(route('admin.orders.batches.create', $order));
        $response->assertRedirect(route('admin.orders.show', $order));

        $postRes = $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 500,
        ]);

        $postRes->assertSessionHas('error');
        $this->assertDatabaseCount('production_batches', 0);
        $this->assertSame(OrderStatus::KOMUNIKASI, $order->fresh()->status);
    }

    public function test_batch_creation_rejected_for_printing_and_later_orders(): void
    {
        $statuses = [
            OrderStatus::PRINTING,
            OrderStatus::PRODUKSI,
            OrderStatus::QC,
            OrderStatus::PACKING,
            OrderStatus::DIKIRIM,
            OrderStatus::SELESAI,
        ];

        foreach ($statuses as $st) {
            $order = $this->createOrder($st, 200);
            $item = $order->items->first();

            $response = $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
                'planned_end_date' => '2099-12-31',
                'order_item_id' => $item->id,
                'target_qty'    => 200,
            ]);

            $response->assertSessionHas('error');
            $this->assertDatabaseCount('production_batches', 0);
        }
    }

    public function test_batch_number_follows_bat_yyyymm_xxxx_format(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 500);
        $item = $order->items->first();

        $response = $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 500,
        ]);

        $batch = ProductionBatch::first();
        $this->assertNotNull($batch);
        $response->assertRedirect(route('admin.batches.show', $batch));

        $this->assertMatchesRegularExpression('/^BAT-\d{6}-\d{4}$/', $batch->batch_no);
    }

    public function test_batch_initial_status_is_bahan(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 500);
        $item = $order->items->first();

        $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 500,
        ]);

        $batch = ProductionBatch::first();
        $this->assertSame(BatchStatus::BAHAN, $batch->status);
    }

    public function test_first_batch_transitions_order_from_deal_to_bahan(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 1000);
        $item = $order->items->first();

        $response = $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 600,
        ]);

        $batch = ProductionBatch::first();
        $response->assertRedirect(route('admin.batches.show', $batch));
        $response->assertSessionHas('success', "Batch produksi {$batch->batch_no} berhasil dibuat dan status pesanan berpindah ke BAHAN. Kebutuhan bahan telah dihitung otomatis berdasarkan BOM varian.");

        $this->assertSame(OrderStatus::BAHAN, $order->fresh()->status);
    }

    public function test_first_batch_creates_exactly_one_order_status_history_bahan_entry(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 1000);
        $item = $order->items->first();

        $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 500,
        ]);

        $bahanHistories = OrderStatusHistory::where('order_id', $order->id)
            ->where('status', OrderStatus::BAHAN->value)
            ->get();

        $this->assertCount(1, $bahanHistories);
        $this->assertSame($this->admin->id, $bahanHistories->first()->changed_by_user_id);
    }

    public function test_second_batch_while_order_is_bahan_succeeds(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 1000);
        $item = $order->items->first();

        // First batch: 600
        $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 600,
        ]);

        $this->assertSame(OrderStatus::BAHAN, $order->fresh()->status);

        // Second batch: 400
        $res2 = $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 400,
        ]);

        $batch2 = ProductionBatch::latest('id')->first();
        $res2->assertRedirect(route('admin.batches.show', $batch2));
        $res2->assertSessionHas('success', "Batch produksi {$batch2->batch_no} berhasil dibuat. Kebutuhan bahan telah dihitung otomatis berdasarkan BOM varian.");

        $this->assertDatabaseCount('production_batches', 2);
    }

    public function test_second_batch_does_not_create_duplicate_bahan_status_history(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 1000);
        $item = $order->items->first();

        // Batch 1
        $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 600,
        ]);

        // Batch 2
        $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 400,
        ]);

        $bahanHistories = OrderStatusHistory::where('order_id', $order->id)
            ->where('status', OrderStatus::BAHAN->value)
            ->get();

        $this->assertCount(1, $bahanHistories);
    }

    public function test_split_batch_creation_supported(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 1000);
        $item = $order->items->first();

        $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 600,
        ]);

        $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 400,
        ]);

        $batches = ProductionBatch::where('order_item_id', $item->id)->get();
        $this->assertCount(2, $batches);
        $this->assertSame('600.0000', (string) $batches[0]->target_qty);
        $this->assertSame('400.0000', (string) $batches[1]->target_qty);
    }

    public function test_target_qty_must_be_greater_than_zero(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 500);
        $item = $order->items->first();

        $res = $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 0,
        ]);

        $res->assertSessionHasErrors('target_qty');
        $this->assertDatabaseCount('production_batches', 0);
    }

    public function test_target_qty_cannot_exceed_remaining_qty(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 1000);
        $item = $order->items->first();

        // First batch 600 (remaining: 400)
        $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 600,
        ]);

        // Attempt second batch 401
        $res = $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 401,
        ]);

        $res->assertSessionHas('error');
        $this->assertDatabaseCount('production_batches', 1);
    }

    public function test_cumulative_target_qty_cannot_exceed_ordered_qty(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 500);
        $item = $order->items->first();

        $res = $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 501,
        ]);

        $res->assertSessionHas('error');
        $this->assertDatabaseCount('production_batches', 0);
    }

    public function test_fully_batched_order_item_rejects_additional_batch(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 500);
        $item = $order->items->first();

        // Batch 1 (full 500)
        $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 500,
        ]);

        // Attempt another batch
        $res = $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 1,
        ]);

        $res->assertSessionHas('error');
        $this->assertDatabaseCount('production_batches', 1);
    }

    public function test_order_item_from_another_order_is_rejected(): void
    {
        $order1 = $this->createOrder(OrderStatus::DEAL, 500);
        $order2 = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-202608-0002',
            'order_date'  => '2026-08-11',
            'status'      => OrderStatus::DEAL,
        ]);
        $item2 = $order2->items()->create([
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => 300,
            'unit_selling_price' => '75000.00',
        ]);

        // Post to order1 with item2 (belonging to order2)
        $res = $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order1), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item2->id,
            'target_qty'    => 300,
        ]);

        $res->assertSessionHasErrors('order_item_id');
        $this->assertDatabaseCount('production_batches', 0);
    }

    public function test_decimal_quantity_precision_preserved_in_batch(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 123.4567);
        $item = $order->items->first();

        $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 123.4567,
        ]);

        $batch = ProductionBatch::first();
        $this->assertNotNull($batch);
        $this->assertSame('123.4567', (string) $batch->target_qty);
    }

    public function test_non_admin_receives_403_when_creating_batch(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 500);
        $item = $order->items->first();

        $gudangUser   = User::factory()->create(['role' => UserRole::GUDANG]);
        $produksiUser = User::factory()->create(['role' => UserRole::PRODUKSI]);
        $customerUser = User::factory()->create(['role' => UserRole::CUSTOMER]);
        $ownerUser    = User::factory()->create(['role' => UserRole::OWNER]);

        // Guest
        $this->get(route('admin.orders.batches.create', $order))->assertRedirect(route('login'));
        $this->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 500,
        ])->assertRedirect(route('login'));

        // Non-admin roles
        $this->actingAs($gudangUser)->get(route('admin.orders.batches.create', $order))->assertStatus(403);
        $this->actingAs($produksiUser)->get(route('admin.orders.batches.create', $order))->assertStatus(403);
        $this->actingAs($customerUser)->get(route('admin.orders.batches.create', $order))->assertStatus(403);
        $this->actingAs($ownerUser)->get(route('admin.orders.batches.create', $order))->assertStatus(403);

        $this->actingAs($gudangUser)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 500,
        ])->assertStatus(403);
    }

    public function test_batch_destroy_route_does_not_exist(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 500);
        $item = $order->items->first();

        $batch = ProductionBatch::create([
            'order_item_id' => $item->id,
            'batch_no'      => 'BAT-202608-0001',
            'target_qty'    => 500,
            'status'        => BatchStatus::BAHAN,
        ]);

        $response = $this->actingAs($this->admin)->delete("/admin/batches/{$batch->id}");
        $response->assertStatus(405);
    }

    public function test_batch_update_route_does_not_exist(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 500);
        $item = $order->items->first();

        $batch = ProductionBatch::create([
            'order_item_id' => $item->id,
            'batch_no'      => 'BAT-202608-0001',
            'target_qty'    => 500,
            'status'        => BatchStatus::BAHAN,
        ]);

        $response = $this->actingAs($this->admin)->put("/admin/batches/{$batch->id}", [
            'target_qty' => 600,
        ]);
        $response->assertStatus(405);
    }

    public function test_deal_order_with_existing_invoice_can_still_create_batch(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 500, false);
        $item = $order->items->first();

        // Create Invoice for the DEAL order with confirmed DP
        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-202608-0001',
            'invoice_date' => '2026-08-10',
            'total_amount' => '37500000.00',
            'status'       => 'PARTIAL',
        ]);

        \App\Models\Sales\Payment::create([
            'invoice_id'   => $invoice->id,
            'payment_no'   => 'PAY-202608-0001',
            'payment_date' => '2026-08-10',
            'amount'       => '15000000.00',
            'payment_type' => \App\Enums\PaymentType::DP,
            'status'       => \App\Enums\PaymentStatus::CONFIRMED,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 500,
        ]);

        $batch = ProductionBatch::first();
        $this->assertNotNull($batch);
        $response->assertRedirect(route('admin.batches.show', $batch));
        $this->assertSame(OrderStatus::BAHAN, $order->fresh()->status);
    }

    public function test_batch_creation_automatically_generates_batch_materials_snapshot(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 500);
        $item = $order->items->first();

        $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 500,
        ]);

        $batch = ProductionBatch::first();
        $this->assertNotNull($batch);

        $bm = BatchMaterial::where('batch_id', $batch->id)
            ->where('material_id', $this->material->id)
            ->first();

        $this->assertNotNull($bm);
        $this->assertSame('600.0000', (string) $bm->required_qty); // 500 * 1.2000
        $this->assertSame('0.0000', (string) $bm->allocated_qty);
        $this->assertSame('0.0000', (string) $bm->used_qty);
    }

    public function test_every_new_batch_must_have_batch_materials_snapshot(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 200);
        $item = $order->items->first();

        $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 200,
        ]);

        $batch = ProductionBatch::first();
        $this->assertNotNull($batch);
        $this->assertGreaterThanOrEqual(1, BatchMaterial::where('batch_id', $batch->id)->count());
    }

    public function test_auto_mrp_basic_calculates_exact_quantities_for_multiple_materials(): void
    {
        $product = Product::create(['product_name' => 'Bergo Jersey', 'active' => true]);
        $variant = ProductVariant::create([
            'product_id'                           => $product->id,
            'variant_name'                         => 'Hitam',
            'sku'                                  => 'BRG-JRS-HTM',
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => '0.00',
            'direct_labor_cost_per_unit'           => '5000.00',
            'packaging_process_cost_per_good_unit' => '1000.00',
            'other_direct_cost_per_unit'           => '500.00',
        ]);

        $kain = Material::create([
            'unit_id'       => $this->unit->id,
            'material_name' => 'Kain Jersey',
            'min_stock'     => 10,
            'active'        => true,
        ]);
        MaterialStock::create(['material_id' => $kain->id, 'qty_on_hand' => 100]);

        $benang = Material::create([
            'unit_id'       => $this->unit->id,
            'material_name' => 'Benang',
            'min_stock'     => 10,
            'active'        => true,
        ]);
        MaterialStock::create(['material_id' => $benang->id, 'qty_on_hand' => 50]);

        BomItem::create(['variant_id' => $variant->id, 'material_id' => $kain->id, 'qty_per_unit' => 0.8000]);
        BomItem::create(['variant_id' => $variant->id, 'material_id' => $benang->id, 'qty_per_unit' => 0.0200]);

        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-202608-9999',
            'order_date'  => '2026-08-10',
            'status'      => OrderStatus::DEAL,
        ]);
        $item = $order->items()->create([
            'variant_id'         => $variant->id,
            'qty_ordered'        => 50,
            'unit_selling_price' => '45000.00',
        ]);
        $inv = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-9999',
            'invoice_date' => now()->toDateString(),
            'total_amount' => '2250000.00',
            'status'       => \App\Enums\InvoiceStatus::PARTIAL,
        ]);
        \App\Models\Sales\Payment::create([
            'invoice_id'   => $inv->id,
            'payment_no'   => 'PAY-9999',
            'payment_date' => now()->toDateString(),
            'amount'       => '1000000.00',
            'payment_type' => \App\Enums\PaymentType::DP,
            'status'       => \App\Enums\PaymentStatus::CONFIRMED,
        ]);

        // Create Batch with Target 20 pcs
        $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 20,
        ]);

        $batch = ProductionBatch::first();
        $this->assertNotNull($batch);

        $bmKain = BatchMaterial::where('batch_id', $batch->id)->where('material_id', $kain->id)->first();
        $bmBenang = BatchMaterial::where('batch_id', $batch->id)->where('material_id', $benang->id)->first();

        $this->assertNotNull($bmKain);
        $this->assertSame('16.0000', (string) $bmKain->required_qty); // 0.8 * 20 = 16

        $this->assertNotNull($bmBenang);
        $this->assertSame('0.4000', (string) $bmBenang->required_qty); // 0.02 * 20 = 0.4
    }

    public function test_stock_at_calc_and_shortage_are_snapshotted_at_creation_time(): void
    {
        $this->material->stock->update(['qty_on_hand' => 10]); // Stock is 10

        $order = $this->createOrder(OrderStatus::DEAL, 100);
        $item = $order->items->first();

        // Create Batch of 20 pcs -> Required: 20 * 1.2 = 24. Stock: 10 -> Shortage: 14
        $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 20,
        ]);

        $batch = ProductionBatch::first();
        $bm = BatchMaterial::where('batch_id', $batch->id)->where('material_id', $this->material->id)->first();

        $this->assertSame('10.0000', (string) $bm->stock_at_calc);
        $this->assertSame('14.0000', (string) $bm->shortage_qty);

        // Later warehouse stock changes to 50
        $this->material->stock->update(['qty_on_hand' => 50]);

        // Historical snapshot on BatchMaterial MUST NOT CHANGE
        $bmFresh = $bm->fresh();
        $this->assertSame('10.0000', (string) $bmFresh->stock_at_calc);
        $this->assertSame('14.0000', (string) $bmFresh->shortage_qty);
    }

    public function test_batch_creation_rejected_when_variant_bom_is_missing(): void
    {
        $product = Product::create(['product_name' => 'Pashmina No BOM', 'active' => true]);
        $variantNoBom = ProductVariant::create([
            'product_id'                           => $product->id,
            'variant_name'                         => 'No BOM Color',
            'sku'                                  => 'PAS-NOBOM-001',
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => '0.00',
            'direct_labor_cost_per_unit'           => '5000.00',
            'packaging_process_cost_per_good_unit' => '1000.00',
            'other_direct_cost_per_unit'           => '500.00',
        ]);

        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-202608-8888',
            'order_date'  => '2026-08-10',
            'status'      => OrderStatus::DEAL,
        ]);
        $item = $order->items()->create([
            'variant_id'         => $variantNoBom->id,
            'qty_ordered'        => 100,
            'unit_selling_price' => '75000.00',
        ]);
        $inv = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-8888',
            'invoice_date' => now()->toDateString(),
            'total_amount' => '7500000.00',
            'status'       => \App\Enums\InvoiceStatus::PARTIAL,
        ]);
        \App\Models\Sales\Payment::create([
            'invoice_id'   => $inv->id,
            'payment_no'   => 'PAY-8888',
            'payment_date' => now()->toDateString(),
            'amount'       => '2500000.00',
            'payment_type' => \App\Enums\PaymentType::DP,
            'status'       => \App\Enums\PaymentStatus::CONFIRMED,
        ]);

        $res = $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 100,
        ]);

        $res->assertSessionHas('error', 'BOM varian belum tersedia. Lengkapi BOM pada Master Produk sebelum membuat Batch Produksi.');
        $this->assertDatabaseCount('production_batches', 0);
        $this->assertDatabaseCount('batch_materials', 0);
        $this->assertSame(OrderStatus::DEAL, $order->fresh()->status);
    }

    public function test_batch_creation_rejected_when_variant_routing_is_null(): void
    {
        $product = Product::create(['product_name' => 'Pashmina Unset Routing', 'active' => true]);
        $variantUnset = ProductVariant::create([
            'product_id'                           => $product->id,
            'variant_name'                         => 'Unset Route',
            'sku'                                  => 'PAS-UNSET-001',
            'requires_printing'                    => null, // NULL
            'direct_labor_cost_per_unit'           => '5000.00',
            'packaging_process_cost_per_good_unit' => '1000.00',
            'other_direct_cost_per_unit'           => '500.00',
        ]);
        BomItem::create(['variant_id' => $variantUnset->id, 'material_id' => $this->material->id, 'qty_per_unit' => 1.0]);

        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-202608-7777',
            'order_date'  => '2026-08-10',
            'status'      => OrderStatus::DEAL,
        ]);
        $item = $order->items()->create([
            'variant_id'         => $variantUnset->id,
            'qty_ordered'        => 100,
            'unit_selling_price' => '75000.00',
        ]);
        $inv = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-7777',
            'invoice_date' => now()->toDateString(),
            'total_amount' => '7500000.00',
            'status'       => \App\Enums\InvoiceStatus::PARTIAL,
        ]);
        \App\Models\Sales\Payment::create([
            'invoice_id'   => $inv->id,
            'payment_no'   => 'PAY-7777',
            'payment_date' => now()->toDateString(),
            'amount'       => '2500000.00',
            'payment_type' => \App\Enums\PaymentType::DP,
            'status'       => \App\Enums\PaymentStatus::CONFIRMED,
        ]);

        $res = $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 100,
        ]);

        $res->assertSessionHas('error', 'Rute produksi varian belum ditetapkan. Atur kebutuhan Printing pada Master Produk terlebih dahulu.');
        $this->assertDatabaseCount('production_batches', 0);
    }

    public function test_batch_creation_rejected_when_single_bom_row_is_invalid(): void
    {
        $inactiveMat = Material::create([
            'unit_id'       => $this->unit->id,
            'material_name' => 'Label Nonaktif',
            'min_stock'     => 10,
            'active'        => false, // Inactive!
        ]);

        BomItem::create([
            'variant_id'   => $this->variant->id,
            'material_id'  => $inactiveMat->id,
            'qty_per_unit' => 1.0000,
        ]);

        $order = $this->createOrder(OrderStatus::DEAL, 100);
        $item = $order->items->first();

        $res = $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 50,
        ]);

        $res->assertSessionHas('error', 'BOM menggunakan material nonaktif (Label Nonaktif). Perbarui BOM terlebih dahulu.');
        $this->assertDatabaseCount('production_batches', 0);
        $this->assertDatabaseCount('batch_materials', 0);
        $this->assertSame(OrderStatus::DEAL, $order->fresh()->status);
    }

    public function test_bom_immutability_between_split_batches(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 100);
        $item = $order->items->first();

        // Batch A created with target 60 when BOM is 1.2000 -> Required: 72.0000
        $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 60,
        ]);

        $batchA = ProductionBatch::where('order_item_id', $item->id)->first();
        $bmA = BatchMaterial::where('batch_id', $batchA->id)->where('material_id', $this->material->id)->first();
        $this->assertSame('72.0000', (string) $bmA->required_qty);

        // Later Admin changes BOM to 1.5000
        BomItem::where('variant_id', $this->variant->id)->where('material_id', $this->material->id)->update(['qty_per_unit' => 1.5000]);

        // Batch B created with target 40 -> Required: 40 * 1.5000 = 60.0000
        $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 40,
        ]);

        $batchB = ProductionBatch::where('order_item_id', $item->id)->latest('id')->first();
        $bmB = BatchMaterial::where('batch_id', $batchB->id)->where('material_id', $this->material->id)->first();

        // Batch A requirement remains 72.0000
        $this->assertSame('72.0000', (string) $bmA->fresh()->required_qty);

        // Batch B requirement uses new BOM (60.0000)
        $this->assertSame('60.0000', (string) $bmB->required_qty);
    }

    public function test_split_batches_receive_independent_snapshots(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 100);
        $item = $order->items->first();

        // Batch A (60)
        $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 60,
        ]);

        // Batch B (40)
        $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 40,
        ]);

        $batches = ProductionBatch::where('order_item_id', $item->id)->get();
        $this->assertCount(2, $batches);

        $bmA = BatchMaterial::where('batch_id', $batches[0]->id)->get();
        $bmB = BatchMaterial::where('batch_id', $batches[1]->id)->get();

        $this->assertCount(1, $bmA);
        $this->assertCount(1, $bmB);
        $this->assertSame('72.0000', (string) $bmA->first()->required_qty); // 60 * 1.2
        $this->assertSame('48.0000', (string) $bmB->first()->required_qty); // 40 * 1.2
    }

    public function test_subsequent_manual_mrp_call_is_idempotent_and_creates_no_duplicates(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 100);
        $item = $order->items->first();

        $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 100,
        ]);

        $batch = ProductionBatch::first();
        $this->assertSame(1, BatchMaterial::where('batch_id', $batch->id)->count());

        $planningService = app(\App\Services\Inventory\BatchMaterialPlanningService::class);
        $result = $planningService->generateMaterialPlanning($batch);

        $this->assertFalse($result['created']);
        $this->assertSame('Kebutuhan bahan untuk batch ini sudah dihitung.', $result['message']);
        $this->assertSame(1, BatchMaterial::where('batch_id', $batch->id)->count());
    }

    public function test_batch_creation_does_not_create_stock_movements(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 500);
        $item = $order->items->first();

        $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 500,
        ]);

        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_failed_batch_creation_does_not_change_order_status(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 500);
        $item = $order->items->first();

        // Attempt invalid overcapacity batch
        $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 600,
        ]);

        $this->assertSame(OrderStatus::DEAL, $order->fresh()->status);
    }

    public function test_failed_batch_creation_does_not_create_status_history(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 500);
        $item = $order->items->first();

        // Attempt invalid overcapacity batch
        $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 600,
        ]);

        $bahanHistories = OrderStatusHistory::where('order_id', $order->id)
            ->where('status', OrderStatus::BAHAN->value)
            ->get();

        $this->assertCount(0, $bahanHistories);
    }

    public function test_cancelled_batch_is_excluded_from_committed_capacity(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 1000);
        $item = $order->items->first();

        $generator = app(\App\Services\System\DocumentNumberGenerator::class);

        // Active batch 1: 600
        ProductionBatch::create([
            'order_item_id' => $item->id,
            'batch_no'      => $generator->generate('BAT'),
            'target_qty'    => 600,
            'status'        => BatchStatus::BAHAN,
        ]);

        // CANCELLED batch: 300
        ProductionBatch::create([
            'order_item_id' => $item->id,
            'batch_no'      => $generator->generate('BAT'),
            'target_qty'    => 300,
            'status'        => BatchStatus::CANCELLED,
        ]);

        // Remaining capacity should be: 1000 - 600 = 400 (NOT 100)
        $response = $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id' => $item->id,
            'target_qty'    => 400,
        ]);

        $batch3 = ProductionBatch::latest('id')->first();
        $this->assertNotNull($batch3);
        $this->assertSame('400.0000', (string) $batch3->target_qty);
        $this->assertSame(BatchStatus::BAHAN, $batch3->status);
    }

    public function test_optional_dates_and_notes_behavior(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 500);
        $item = $order->items->first();

        // 1. Start date null & notes null (target end date is now mandatory) -> Success
        $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id'      => $item->id,
            'target_qty'         => 100,
            'planned_start_date' => null,
            'planned_end_date'   => '2026-12-31',
            'notes'              => null,
        ]);

        $b1 = ProductionBatch::first();
        $this->assertNull($b1->planned_start_date);
        $this->assertEquals('2026-12-31', $b1->planned_end_date->toDateString());
        $this->assertNull($b1->notes);

        // 2. planned_end_date before planned_start_date -> Fails validation
        $resInvalidDates = $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id'      => $item->id,
            'target_qty'         => 100,
            'planned_start_date' => '2026-08-20',
            'planned_end_date'   => '2026-08-15', // invalid before start
        ]);
        $resInvalidDates->assertSessionHasErrors('planned_end_date');

        // 3. Valid dates & notes -> Success
        $resValid = $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'planned_end_date' => '2099-12-31',
            'order_item_id'      => $item->id,
            'target_qty'         => 100,
            'planned_start_date' => '2026-08-15',
            'planned_end_date'   => '2026-08-20',
            'notes'              => 'Prioritas jahit cepat',
        ]);

        $b2 = ProductionBatch::latest('id')->first();
        $this->assertSame('2026-08-15', $b2->planned_start_date->format('Y-m-d'));
        $this->assertSame('2026-08-20', $b2->planned_end_date->format('Y-m-d'));
        $this->assertSame('Prioritas jahit cepat', $b2->notes);
    }

    public function test_admin_can_view_batch_index_and_show(): void
    {
        $order = $this->createOrder(OrderStatus::DEAL, 500);
        $item = $order->items->first();

        $batch = ProductionBatch::create([
            'order_item_id' => $item->id,
            'batch_no'      => 'BAT-202608-0001',
            'target_qty'    => 500,
            'status'        => BatchStatus::BAHAN,
            'notes'         => 'Catatan Batch Uji',
        ]);

        // Index page
        $resIndex = $this->actingAs($this->admin)->get(route('admin.batches.index'));
        $resIndex->assertStatus(200);
        $resIndex->assertSee('BAT-202608-0001');
        $resIndex->assertSee('Butik Al-Fatih');
        $resIndex->assertSee('500 pcs');

        // Show page
        $resShow = $this->actingAs($this->admin)->get(route('admin.batches.show', $batch));
        $resShow->assertStatus(200);
        $resShow->assertSee('BAT-202608-0001');
        $resShow->assertSee('Pashmina Silk');
        $resShow->assertSee('Catatan Batch Uji');
        $resShow->assertSee('Bahan');

        // Order detail page shows batch
        $resOrder = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));
        $resOrder->assertStatus(200);
        $resOrder->assertSee('BAT-202608-0001');
    }
}
