<?php

namespace Tests\Feature\Production;

use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Inventory\MaterialStock;
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
use App\Models\Sales\Payment;
use App\Models\System\User;
use App\Services\Production\BatchService;
use App\Services\Production\BatchWorkflowService;
use App\Services\Production\ProductionResultService;
use App\Services\Production\QcResultService;
use App\Services\Sales\OrderStatusService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartialProductionAndShortfallHandlingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $produksiUserA;
    private User $produksiUserB;
    private Customer $customer;
    private ProductVariant $variant;
    private Material $kain;
    private BatchService $batchService;
    private BatchWorkflowService $workflowService;
    private ProductionResultService $resultService;
    private QcResultService $qcService;
    private OrderStatusService $orderStatusService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->batchService = app(BatchService::class);
        $this->workflowService = app(BatchWorkflowService::class);
        $this->resultService = app(ProductionResultService::class);
        $this->qcService = app(QcResultService::class);
        $this->orderStatusService = app(OrderStatusService::class);

        $this->admin = User::factory()->create([
            'name'   => 'Admin Nayara',
            'role'   => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->produksiUserA = User::factory()->create([
            'name'   => 'Karyawan Jahit A',
            'role'   => UserRole::PRODUKSI,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->produksiUserB = User::factory()->create([
            'name'   => 'Karyawan Jahit B',
            'role'   => UserRole::PRODUKSI,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->customer = Customer::create([
            'customer_name' => 'Nayara Retail Customer',
            'phone'         => '081234567890',
        ]);

        $unit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $this->kain = Material::create([
            'material_name' => 'Kain Ceruty Babydoll',
            'unit_id'       => $unit->id,
            'cost_per_unit' => 20000,
            'active'        => true,
        ]);

        MaterialStock::create([
            'material_id'   => $this->kain->id,
            'qty_on_hand'   => 1000,
            'avg_unit_cost' => 20000,
        ]);

        $product = Product::create([
            'product_name' => 'Pashmina Ceruty',
            'active'       => true,
        ]);

        $this->variant = ProductVariant::create([
            'product_id'                           => $product->id,
            'variant_name'                         => 'Mauve Silk',
            'sku'                                  => 'PSH-CRTY-MV',
            'requires_printing'                    => false,
            'direct_labor_cost_per_unit'           => '4000.00',
            'packaging_process_cost_per_good_unit' => '1000.00',
            'other_direct_cost_per_unit'           => '500.00',
            'active'                               => true,
        ]);

        \App\Models\Master\BomItem::create([
            'variant_id'   => $this->variant->id,
            'material_id'  => $this->kain->id,
            'qty_per_unit' => 2.0,
        ]);
    }

    private function createOrderWithDp(int $qty = 100): array
    {
        $order = Order::create([
            'order_no'     => 'SO-' . uniqid(),
            'order_date'   => now()->toDateString(),
            'customer_id'  => $this->customer->id,
            'status'       => OrderStatus::DEAL,
            'total_qty'    => $qty,
            'total_amount' => $qty * 50000,
        ]);

        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => $qty,
            'unit_selling_price' => 50000,
            'subtotal'           => $qty * 50000,
        ]);

        $invoice = Invoice::create([
            'order_id'       => $order->id,
            'invoice_no'     => 'INV-' . uniqid(),
            'invoice_date'   => now()->toDateString(),
            'total_amount'   => (string) ($qty * 50000),
            'status'         => \App\Enums\InvoiceStatus::PARTIAL,
        ]);

        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_no'     => 'PAY-' . uniqid(),
            'payment_date'   => now()->toDateString(),
            'amount'         => (string) ($qty * 25000),
            'payment_type'   => \App\Enums\PaymentType::DP,
            'status'         => \App\Enums\PaymentStatus::CONFIRMED,
        ]);

        return [$order, $item];
    }

    private function createBatchReadyForProduction(OrderItem $item, int $targetQty, User $pic): ProductionBatch
    {
        $res = $this->batchService->createBatch($item->order, [
            'order_item_id'          => $item->id,
            'target_qty'             => $targetQty,
            'planned_start_date'     => now()->toDateString(),
            'planned_end_date'       => now()->addDays(3)->toDateString(),
            'production_employee_id' => $pic->id,
        ], $this->admin);

        /** @var ProductionBatch $batch */
        $batch = $res['batch'];

        // Allocate material completely so it is ready
        foreach ($batch->batchMaterials as $bm) {
            $bm->allocated_qty = $bm->required_qty;
            $bm->save();
        }

        // Start production (BAHAN -> PRODUKSI)
        $batch = $this->workflowService->startBatch($batch, $pic);

        return $batch;
    }

    /**
     * Skenario 1: Produksi Penuh
     * Target 100 pcs, Aktual 100 pcs, QC good 98, reject 2 -> Packing -> Selesai.
     */
    public function test_skenario_1_produksi_penuh_target_tercapai_lengkap_ke_selesai(): void
    {
        [$order, $item] = $this->createOrderWithDp(100);
        $batch = $this->createBatchReadyForProduction($item, 100, $this->produksiUserA);

        $this->assertEquals(BatchStatus::PRODUKSI, $batch->status);
        $this->assertEquals(100.0, (float) $batch->target_qty);

        // Record 100 pcs actual
        $bm = $batch->batchMaterials->first();
        $this->resultService->recordFinalResult(
            $batch,
            100,
            now()->toDateString(),
            'Selesai jahit 100 pcs',
            $this->produksiUserA,
            [$bm->id => (string) $bm->allocated_qty]
        );

        $batch->refresh();
        $this->assertEquals(BatchStatus::QC, $batch->status);
        $this->assertEquals(100.0, $batch->total_actual_qty);
        $this->assertEquals(0.0, $batch->remaining_target_qty);
        $this->assertEquals(100.0, $batch->achievement_percentage);

        // Record QC: 98 good, 2 reject
        $this->qcService->recordFinalQc(
            $batch,
            98,
            '2 reject jahitan miring',
            $this->produksiUserA,
            now()->toDateString(),
            2
        );

        $batch->refresh();
        $this->assertEquals(BatchStatus::PACKING, $batch->status);
        $this->assertEquals(98.0, $batch->total_good_qty);
        $this->assertEquals(2.0, $batch->total_reject_qty);

        // Complete Packing -> SELESAI
        $batch = $this->workflowService->completePacking($batch, $this->produksiUserA);
        $this->assertEquals(BatchStatus::SELESAI, $batch->status);
        $this->assertEquals(0.0, $batch->calculated_shortfall_qty);
    }

    /**
     * Skenario 2: Produksi Parsial
     * Target 100 pcs, Aktual 90 pcs.
     * Sisa target: 100 - 90 = 10 pcs.
     * Sistem tidak boleh menganggap kekurangan produksi (10 pcs) sebagai barang reject.
     */
    public function test_skenario_2_produksi_parsial_target_lebih_besar_dari_aktual_shortfall_bukan_reject(): void
    {
        [$order, $item] = $this->createOrderWithDp(100);
        $batch = $this->createBatchReadyForProduction($item, 100, $this->produksiUserA);

        $bm = $batch->batchMaterials->first();
        $this->resultService->recordFinalResult(
            $batch,
            90,
            now()->toDateString(),
            'Sesi jahit parsial 90 pcs',
            $this->produksiUserA,
            [$bm->id => (string) $bm->allocated_qty]
        );

        $batch->refresh();
        $this->assertEquals(BatchStatus::QC, $batch->status);
        $this->assertEquals(90.0, $batch->total_actual_qty);

        // Formula check: Sisa Target Produksi = Target Produksi - Total Aktual Produksi
        $this->assertEquals(10.0, $batch->remaining_target_qty);
        $this->assertEquals(90.0, $batch->achievement_percentage);

        // Perform QC on the 90 produced units: 88 good, 2 reject
        $this->qcService->recordFinalQc(
            $batch,
            88,
            'Pemeriksaan 90 unit aktual',
            $this->produksiUserA,
            now()->toDateString(),
            2
        );

        $batch->refresh();
        // Reject must be strictly 2 pcs, NOT 12 pcs (shortfall of 10 is NOT reject!)
        $this->assertEquals(2.0, $batch->total_reject_qty);
        $this->assertEquals(88.0, $batch->total_good_qty);
        $this->assertEquals(90.0, $batch->total_inspected_qty);
        $this->assertEquals(10.0, $batch->remaining_target_qty);
    }

    /**
     * Skenario 3: QC Bertahap dan pembatasan pemeriksaan
     * QC hanya memeriksa barang yang sudah benar-benar diproduksi (<= aktual_qty).
     * Good + Reject = Inspected Qty.
     * Barang belum diproduksi tidak boleh masuk perhitungan QC.
     */
    public function test_skenario_3_qc_bertahap_dan_hanya_memeriksa_barang_yang_sudah_diproduksi(): void
    {
        [$order, $item] = $this->createOrderWithDp(100);
        $batch = $this->createBatchReadyForProduction($item, 100, $this->produksiUserA);

        $bm = $batch->batchMaterials->first();
        $this->resultService->recordFinalResult(
            $batch,
            90,
            now()->toDateString(),
            'Aktual 90 pcs',
            $this->produksiUserA,
            [$bm->id => (string) $bm->allocated_qty]
        );

        $batch->refresh();

        // Expect exception if attempting to inspect more than actual production (e.g. 95 pcs inspected when only 90 produced)
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Total unit diperiksa');
        $this->qcService->recordFinalQc(
            $batch,
            90,
            'Inspeksi berlebih',
            $this->produksiUserA,
            now()->toDateString(),
            5 // 90 + 5 = 95 > 90
        );
    }

    /**
     * Skenario 4: Produksi Lanjutan (Opsi 1 - PIC Sama)
     * Batch dilanjutkan kembali ke PRODUKSI.
     * PIC sama, target lama tidak berubah.
     * Karyawan mencatat produksi lanjutan 10 pcs -> Total Aktual = 100 pcs.
     * Histori multi-run tersimpan rapi.
     */
    public function test_skenario_4_produksi_lanjutan_batch_sama_dengan_pic_sama(): void
    {
        [$order, $item] = $this->createOrderWithDp(100);
        $batch = $this->createBatchReadyForProduction($item, 100, $this->produksiUserA);

        // Run 1: 90 pcs
        $bm = $batch->batchMaterials->first();
        $this->resultService->recordFinalResult(
            $batch,
            90,
            now()->toDateString(),
            'Run #1 jahit 90 pcs',
            $this->produksiUserA,
            [$bm->id => (string) $bm->allocated_qty]
        );

        // QC Run 1: 90 good
        $this->qcService->recordFinalQc($batch, 90, 'QC Run 1', $this->produksiUserA, now()->toDateString());
        $batch->refresh();
        $this->assertEquals(BatchStatus::PACKING, $batch->status);
        $this->assertEquals(10.0, $batch->remaining_target_qty);

        // Admin invokes resumeProduction()
        $this->workflowService->resumeProduction($batch, $this->admin, 'Instruksi: jahit sisa 10 pcs');

        $batch->refresh();
        $this->assertEquals(BatchStatus::PRODUKSI, $batch->status);
        $this->assertEquals($this->produksiUserA->id, $batch->production_employee_id); // PIC sama
        $this->assertEquals(100.0, (float) $batch->target_qty); // Target lama tidak berubah

        // Karyawan jahitan mencatat Run #2: 10 pcs
        $this->resultService->recordFinalResult(
            $batch,
            10,
            now()->toDateString(),
            'Run #2 sisa 10 pcs selesai',
            $this->produksiUserA
        );

        $batch->refresh();
        $this->assertEquals(BatchStatus::QC, $batch->status);
        $this->assertEquals(100.0, $batch->total_actual_qty);
        $this->assertEquals(0.0, $batch->remaining_target_qty);

        // QC Run 2: 10 good
        $this->qcService->recordFinalQc($batch, 10, 'QC Run 2', $this->produksiUserA, now()->toDateString());

        $batch->refresh();
        $this->assertEquals(BatchStatus::PACKING, $batch->status);
        $this->assertEquals(100.0, $batch->total_good_qty);
        $this->assertEquals(2, $batch->productionResults()->count()); // 2 historical production runs
        $this->assertEquals(2, $batch->qcResults()->count());         // 2 historical QC results

        // Complete Packing -> SELESAI
        $batch = $this->workflowService->completePacking($batch, $this->produksiUserA);
        $this->assertEquals(BatchStatus::SELESAI, $batch->status);
    }

    /**
     * Skenario 5: Pengalihan Sisa Target (Opsi 2 - Batch Baru)
     * Target 100, aktual 90, ditutup parsial.
     * Sisa 10 pcs dialihkan ke batch baru dengan PIC Karyawan B.
     * Target lama tidak berubah, alokasi total tidak melebihi kuantitas pesanan.
     */
    public function test_skenario_5_pengalihan_sisa_target_ke_karyawan_lain_melalui_batch_baru(): void
    {
        [$order, $item] = $this->createOrderWithDp(100);
        $batch1 = $this->createBatchReadyForProduction($item, 100, $this->produksiUserA);

        $bm = $batch1->batchMaterials->first();
        $this->resultService->recordFinalResult($batch1, 90, now()->toDateString(), 'Run 1', $this->produksiUserA, [$bm->id => (string) $bm->allocated_qty]);
        $this->qcService->recordFinalQc($batch1, 90, 'QC Run 1', $this->produksiUserA, now()->toDateString());

        // Admin closes batch 1 partially
        $batch1 = $this->workflowService->closeBatchPartially($batch1, 'Karyawan A sakit, sisa 10 pcs dialihkan', $this->admin);
        $this->assertEquals(BatchStatus::SELESAI_PARSIAL, $batch1->status);
        $this->assertEquals(100.0, (float) $batch1->target_qty); // Target lama TIDAK berubah otomatis

        // Check remaining capacity on OrderItem
        $coverage = $this->batchService->calculateProductionCoverage($item);
        $this->assertEquals('10.0000', $coverage['remaining_to_plan']);

        // Admin creates Batch 2 for the exact deficit (10 pcs) assigned to Karyawan B
        $res2 = $this->batchService->createBatch($order, [
            'order_item_id'          => $item->id,
            'target_qty'             => 10,
            'planned_start_date'     => now()->toDateString(),
            'planned_end_date'       => now()->addDays(2)->toDateString(),
            'production_employee_id' => $this->produksiUserB->id,
        ], $this->admin);

        /** @var ProductionBatch $batch2 */
        $batch2 = $res2['batch'];
        $this->assertEquals(10.0, (float) $batch2->target_qty);
        $this->assertEquals($this->produksiUserB->id, $batch2->production_employee_id);

        // Attempting to create another batch must fail because total coverage is 100% (90 + 10 = 100)
        $this->expectException(DomainException::class);
        $this->batchService->createBatch($order, [
            'order_item_id'          => $item->id,
            'target_qty'             => 5,
            'planned_start_date'     => now()->toDateString(),
            'planned_end_date'       => now()->addDays(2)->toDateString(),
            'production_employee_id' => $this->produksiUserB->id,
        ], $this->admin);
    }

    /**
     * Skenario 6: Penutupan Parsial (Opsi 3 - SELESAI_PARSIAL)
     * Wajib mencatat: target awal, aktual produksi, sisa target, hasil QC, good, reject, alasan, closed_by, closed_at.
     * Status SELESAI_PARSIAL dibedakan dari SELESAI.
     * Penutupan parsial tidak boleh otomatis membuat Sales Order selesai.
     */
    public function test_skenario_6_penyelesaian_parsial_catat_target_awal_aktual_sisa_alasan_dan_persetujuan(): void
    {
        [$order, $item] = $this->createOrderWithDp(100);
        $batch = $this->createBatchReadyForProduction($item, 100, $this->produksiUserA);

        $bm = $batch->batchMaterials->first();
        $this->resultService->recordFinalResult($batch, 90, now()->toDateString(), 'Run parsial', $this->produksiUserA, [$bm->id => (string) $bm->allocated_qty]);
        $this->qcService->recordFinalQc($batch, 85, '5 reject cacat tenun', $this->produksiUserA, now()->toDateString(), 5);

        // Admin performs partial close via HTTP POST
        $response = $this->actingAs($this->admin)->post(route('admin.batches.partial-close', $batch), [
            'reason' => 'Bahan baku dari supplier habis dan customer menyetujui pemenuhan parsial 85 pcs.',
        ]);

        $response->assertRedirect(route('admin.batches.show', $batch));
        $response->assertSessionHas('success');

        $batch->refresh();

        // 1. Status is SELESAI_PARSIAL (distinct from SELESAI)
        $this->assertEquals(BatchStatus::SELESAI_PARSIAL, $batch->status);
        $this->assertNotEquals(BatchStatus::SELESAI, $batch->status);

        // 2. Target awal, aktual, sisa target, hasil QC
        $this->assertEquals(100.0, (float) $batch->target_qty);
        $this->assertEquals(90.0, $batch->total_actual_qty);
        $this->assertEquals(10.0, $batch->calculated_shortfall_qty);
        $this->assertEquals(85.0, $batch->total_good_qty);
        $this->assertEquals(5.0, $batch->total_reject_qty);

        // 3. Alasan kekurangan, pengguna yang menyetujui, waktu persetujuan
        $this->assertEquals('Bahan baku dari supplier habis dan customer menyetujui pemenuhan parsial 85 pcs.', $batch->close_reason);
        $this->assertEquals($this->admin->id, $batch->closed_by_user_id);
        $this->assertNotNull($batch->closed_at);

        // 4. Sales Order does NOT falsely complete
        $order->refresh();
        $this->assertNotEquals(OrderStatus::SELESAI, $order->status);
        $this->assertNotEquals(OrderStatus::DIKIRIM, $order->status);
        // Order remains at BAHAN because 15 pcs shortfall remains unbatched
        $this->assertEquals(OrderStatus::BAHAN, $order->status);
    }

    /**
     * Skenario 4B: Admin melakukan resume langsung saat status batch masih QC (sebelum QC di-input).
     * Karyawan dapat langsung memperbarui hasil produksi menjadi lengkap (100 pcs) tanpa deadlock.
     */
    public function test_skenario_4b_resume_langsung_dari_qc_sebelum_inspeksi_update_hasil_ke_lengkap(): void
    {
        [$order, $item] = $this->createOrderWithDp(10);
        $batch = $this->createBatchReadyForProduction($item, 10, $this->produksiUserA);

        // Karyawan input aktual 9 pcs (kurang 1 pcs), batch otomatis masuk QC
        $bm = $batch->batchMaterials->first();
        $this->resultService->recordFinalResult(
            $batch,
            9,
            now()->toDateString(),
            'Setor 9 pcs',
            $this->produksiUserA,
            [$bm->id => (string) $bm->allocated_qty]
        );

        $batch->refresh();
        $this->assertEquals(BatchStatus::QC, $batch->status);
        $this->assertEquals(9.0, $batch->total_actual_qty);
        $this->assertEquals(1.0, $batch->remaining_target_qty);

        // Admin langsung klik Lanjutkan Produksi dari status QC
        $this->workflowService->resumeProduction($batch, $this->admin, 'Instruksi: lengkapi jadi 10 pcs');

        $batch->refresh();
        $this->assertEquals(BatchStatus::PRODUKSI, $batch->status);

        // Karyawan sekarang dapat memperbarui hasil produksi menjadi 10 pcs tanpa terhalang "menunggu QC"
        $this->resultService->recordFinalResult(
            $batch,
            10,
            now()->toDateString(),
            'Lengkap 10 pcs selesai jahit',
            $this->produksiUserA
        );

        $batch->refresh();
        $this->assertEquals(BatchStatus::QC, $batch->status);
        $this->assertEquals(10.0, $batch->total_actual_qty);
        $this->assertEquals(0.0, $batch->remaining_target_qty);

        // QC periksa penuh 10 pcs
        $this->qcService->recordFinalQc($batch, 10, 'QC Lengkap 10 pcs', $this->produksiUserA, now()->toDateString());
        $batch->refresh();
        $this->assertEquals(BatchStatus::PACKING, $batch->status);
        $this->assertEquals(10.0, $batch->total_good_qty);
    }
}
