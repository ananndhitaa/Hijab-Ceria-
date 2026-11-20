<?php

namespace Tests\Feature\Owner;

use App\Enums\BatchStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Master\Material;
use App\Models\Master\Customer;
use App\Models\Master\Unit;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Master\BomItem;
use App\Models\Production\ProductionBatch;
use App\Models\Production\ProductionResult;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\Sales\Payment;
use App\Models\System\Outlet;
use App\Models\System\User;
use App\Services\Owner\ProductionPerformanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerProductionPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private Outlet $outlet;
    private User $owner;
    private User $admin;
    private User $produksi1;
    private User $produksi2;
    private User $gudang;
    private User $customerUser;
    private Customer $customer;
    private ProductVariant $variant;
    private Order $order;
    private OrderItem $orderItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outlet = Outlet::create([
            'outlet_name' => 'Nayara Hijab Kudus',
            'outlet_code' => 'OUT-KUDUS',
            'status'      => 'ACTIVE',
        ]);

        $this->owner = User::factory()->create([
            'outlet_id' => $this->outlet->id,
            'name'      => 'Owner Utama',
            'role'      => UserRole::OWNER,
            'status'    => UserStatus::ACTIVE,
        ]);

        $this->admin = User::factory()->create([
            'outlet_id' => $this->outlet->id,
            'name'      => 'Admin Operasional',
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::ACTIVE,
        ]);

        $this->produksi1 = User::factory()->create([
            'outlet_id' => $this->outlet->id,
            'name'      => 'Budi Produksi',
            'email'     => 'budi.produksi@example.com',
            'role'      => UserRole::PRODUKSI,
            'status'    => UserStatus::ACTIVE,
        ]);

        $this->produksi2 = User::factory()->create([
            'outlet_id' => $this->outlet->id,
            'name'      => 'Siti Produksi',
            'email'     => 'siti.produksi@example.com',
            'role'      => UserRole::PRODUKSI,
            'status'    => UserStatus::ACTIVE,
        ]);

        $this->gudang = User::factory()->create([
            'outlet_id' => $this->outlet->id,
            'name'      => 'Staf Gudang',
            'role'      => UserRole::GUDANG,
            'status'    => UserStatus::ACTIVE,
        ]);

        $this->customerUser = User::factory()->create([
            'outlet_id' => $this->outlet->id,
            'name'      => 'Customer User',
            'role'      => UserRole::CUSTOMER,
            'status'    => UserStatus::ACTIVE,
        ]);

        $this->customer = Customer::create([
            'customer_name'       => 'Toko Hijab Berkah',
            'phone'               => '081234567890',
            'handled_by_user_id'  => $this->admin->id,
            'first_contact_date'  => '2026-10-01',
        ]);

        $product = Product::create([
            'product_name' => 'Pashmina Silk',
            'product_code' => 'PAS-SILK',
            'status'       => 'AKTIF',
        ]);

        $this->variant = ProductVariant::create([
            'product_id'                            => $product->id,
            'variant_name'                          => 'Rose Gold',
            'sku'                                   => 'PAS-SILK-RG',
            'price'                                 => 75000,
            'requires_printing'                     => false,
            'printing_cost_per_unit'                => '0.00',
            'direct_labor_cost_per_unit'            => '5000.00',
            'packaging_process_cost_per_good_unit'  => '1000.00',
            'other_direct_cost_per_unit'            => '500.00',
        ]);

        $unit = Unit::create([
            'unit_name' => 'Meter',
            'symbol'    => 'm',
        ]);

        $material = Material::create([
            'unit_id'       => $unit->id,
            'material_name' => 'Kain Silk Premium',
            'min_stock'     => '10.0000',
            'active'        => true,
        ]);

        BomItem::create([
            'variant_id'   => $this->variant->id,
            'material_id'  => $material->id,
            'qty_per_unit' => '2.0000',
        ]);

        $this->order = Order::create([
            'order_no'            => 'ORD-202610-001',
            'customer_id'         => $this->customer->id,
            'created_by_user_id'  => $this->admin->id,
            'order_date'          => '2026-10-05',
            'status'              => OrderStatus::DEAL,
            'total_amount'        => 7500000,
        ]);

        $this->orderItem = OrderItem::create([
            'order_id'           => $this->order->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => 100,
            'unit_selling_price' => 75000,
        ]);

        $invoice = Invoice::create([
            'order_id'    => $this->order->id,
            'invoice_no'  => 'INV-202610-001',
            'invoice_date'=> '2026-10-05',
            'status'      => InvoiceStatus::PARTIAL,
            'total_amount'=> 7500000,
            'paid_amount' => 2500000,
        ]);

        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_no'     => 'PAY-202610-001',
            'amount'         => 2500000,
            'payment_type'   => PaymentType::DP,
            'payment_method' => 'TRANSFER',
            'status'         => PaymentStatus::CONFIRMED,
            'payment_date'   => '2026-10-05',
            'recorded_by'    => $this->admin->id,
            'confirmed_by'   => $this->admin->id,
            'confirmed_at'   => now(),
        ]);
    }

    private function createBatchWithResult(
        ?User $employee,
        float $targetQty,
        float $actualQty,
        ?string $plannedEndDate,
        string $resultDate,
        string $batchNoSuffix = '01'
    ): ProductionBatch {
        $batch = ProductionBatch::create([
            'order_item_id'                                 => $this->orderItem->id,
            'production_employee_id'                        => $employee?->id,
            'batch_no'                                      => 'BAT-202610-' . $batchNoSuffix,
            'target_qty'                                    => $targetQty,
            'status'                                        => BatchStatus::SELESAI,
            'requires_printing'                             => false,
            'printing_cost_per_unit_snapshot'               => '0.00',
            'direct_labor_cost_per_unit_snapshot'           => '5000.00',
            'packaging_process_cost_per_good_unit_snapshot' => '1000.00',
            'other_direct_cost_per_unit_snapshot'           => '500.00',
            'planned_start_date'                            => '2026-10-06',
            'planned_end_date'                              => $plannedEndDate,
        ]);

        ProductionResult::create([
            'batch_id'    => $batch->id,
            'actual_qty'  => $actualQty,
            'result_date' => $resultDate,
            'recorded_by' => $employee?->id ?? $this->produksi1->id,
        ]);

        return $batch;
    }

    // ── 1. Role Authorization ───────────────────────────────────────────

    public function test_owner_can_access_production_performance_tab(): void
    {
        $response = $this->actingAs($this->owner)->get(route('owner.employee-performance.index', ['tab' => 'production']));

        $response->assertStatus(200);
        $response->assertSee('Performa Karyawan');
        $response->assertSee('Performa Produksi');
        $response->assertSee('Tabel Performa Karyawan Produksi');
        $response->assertSee('Budi Produksi');
        $response->assertSee('Siti Produksi');
    }

    public function test_owner_can_access_production_detail_breakdown(): void
    {
        $response = $this->actingAs($this->owner)->get(route('owner.employee-performance.production-detail', $this->produksi1));

        $response->assertStatus(200);
        $response->assertSee('Detail Performa Produksi');
        $response->assertSee('Budi Produksi');
        $response->assertSee($this->produksi1->email);
        $response->assertSee('Daftar Batch Produksi yang Dikerjakan');
    }

    public function test_non_owner_cannot_access_employee_performance(): void
    {
        $this->actingAs($this->produksi1)
            ->get(route('owner.employee-performance.index'))
            ->assertStatus(403);

        $this->actingAs($this->admin)
            ->get(route('owner.employee-performance.index'))
            ->assertStatus(403);

        $this->actingAs($this->gudang)
            ->get(route('owner.employee-performance.index'))
            ->assertStatus(403);

        $this->actingAs($this->customerUser)
            ->get(route('owner.employee-performance.index'))
            ->assertStatus(403);
    }

    public function test_non_owner_cannot_access_production_detail(): void
    {
        $this->actingAs($this->produksi1)
            ->get(route('owner.employee-performance.production-detail', $this->produksi1))
            ->assertStatus(403);

        $this->actingAs($this->admin)
            ->get(route('owner.employee-performance.production-detail', $this->produksi1))
            ->assertStatus(403);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('owner.employee-performance.index'))
            ->assertRedirect(route('login'));

        $this->get(route('owner.employee-performance.production-detail', $this->produksi1))
            ->assertRedirect(route('login'));
    }

    public function test_production_detail_returns_404_for_non_production_user(): void
    {
        $this->actingAs($this->owner)
            ->get(route('owner.employee-performance.production-detail', $this->admin))
            ->assertStatus(404);
    }

    // ── 2. Admin PIC Assignment on Batch Creation ────────────────────────

    public function test_admin_can_assign_pic_produksi_during_batch_creation(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $this->order), [
            'order_item_id'          => $this->orderItem->id,
            'target_qty'             => 25,
            'production_employee_id' => $this->produksi1->id,
            'planned_start_date'     => '2026-10-10',
            'planned_end_date'       => '2026-10-15',
            'notes'                  => 'Prioritas batch 1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('production_batches', [
            'order_item_id'          => $this->orderItem->id,
            'target_qty'             => 25,
            'production_employee_id' => $this->produksi1->id,
        ]);
    }

    public function test_pic_produksi_is_optional_on_batch_creation(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $this->order), [
            'order_item_id'          => $this->orderItem->id,
            'target_qty'             => 20,
            'production_employee_id' => null,
            'planned_start_date'     => '2026-10-10',
            'planned_end_date'       => '2026-10-15',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('production_batches', [
            'order_item_id'          => $this->orderItem->id,
            'target_qty'             => 20,
            'production_employee_id' => null,
        ]);
    }

    public function test_batch_creation_fails_if_assigned_user_is_not_production_role(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $this->order), [
            'order_item_id'          => $this->orderItem->id,
            'target_qty'             => 20,
            'production_employee_id' => $this->admin->id, // Admin, not PRODUKSI
            'planned_start_date'     => '2026-10-10',
            'planned_end_date'       => '2026-10-15',
        ]);

        $response->assertSessionHasErrors(['production_employee_id']);
    }

    // ── 3. Time Performance Classification & Calculation ─────────────────

    public function test_time_performance_classification_faster_ontime_and_late(): void
    {
        // Batch 1: Target 2026-10-15, actual 2026-10-12 (3 days faster -> LEBIH_CEPAT)
        $batchFaster = $this->createBatchWithResult(
            $this->produksi1,
            30,
            30,
            '2026-10-15',
            '2026-10-12',
            'FST'
        );
        $this->assertEquals(3, $batchFaster->time_difference_days);
        $this->assertEquals('LEBIH_CEPAT', $batchFaster->time_performance_status);
        $this->assertEquals('3 hari lebih cepat', $batchFaster->time_performance_label);

        // Batch 2: Target 2026-10-15, actual 2026-10-15 (0 days -> TEPAT_WAKTU)
        $batchExact = $this->createBatchWithResult(
            $this->produksi1,
            30,
            30,
            '2026-10-15',
            '2026-10-15',
            'EXT'
        );
        $this->assertEquals(0, $batchExact->time_difference_days);
        $this->assertEquals('TEPAT_WAKTU', $batchExact->time_performance_status);
        $this->assertEquals('Tepat waktu', $batchExact->time_performance_label);

        // Batch 3: Target 2026-10-15, actual 2026-10-18 (3 days late -> TERLAMBAT)
        $batchLate = $this->createBatchWithResult(
            $this->produksi1,
            40,
            40,
            '2026-10-15',
            '2026-10-18',
            'LAT'
        );
        $this->assertEquals(-3, $batchLate->time_difference_days);
        $this->assertEquals('TERLAMBAT', $batchLate->time_performance_status);
        $this->assertEquals('Terlambat 3 hari', $batchLate->time_performance_label);

        // In service: 3 batches, 2 on-time (faster + exact), 1 late => 2/3 = 66.67%
        /** @var ProductionPerformanceService $service */
        $service = app(ProductionPerformanceService::class);
        $stats = $service->calculatePerformance('2026-10-01', '2026-10-31', 'all');

        $budiStat = $stats['production_stats']->firstWhere('user.id', $this->produksi1->id);
        $this->assertEquals(3, $budiStat['total_batches']);
        $this->assertEquals(3, $budiStat['batches_with_target']);
        $this->assertEquals(2, $budiStat['on_time_batches']);
        $this->assertEquals(1, $budiStat['late_batches']);
        $this->assertEquals(1, $budiStat['faster_batches']);
        $this->assertEquals(1, $budiStat['exact_batches']);
        $this->assertEquals(66.67, $budiStat['on_time_percentage']);
    }

    // ── 4. Quantity Performance Calculation ──────────────────────────────

    public function test_quantity_performance_calculated_as_sum_over_sum_and_allows_over_100(): void
    {
        // Batch 1: target 50, actual 55 (110%)
        $this->createBatchWithResult(
            $this->produksi1,
            50,
            55,
            '2026-10-15',
            '2026-10-14',
            'Q1'
        );

        // Batch 2: target 150, actual 135 (90%)
        $this->createBatchWithResult(
            $this->produksi1,
            150,
            135,
            '2026-10-15',
            '2026-10-15',
            'Q2'
        );

        /** @var ProductionPerformanceService $service */
        $service = app(ProductionPerformanceService::class);
        $detail = $service->getProductionDetail($this->produksi1, '2026-10-01', '2026-10-31', 'all');

        // Total target: 50 + 150 = 200
        // Total actual: 55 + 135 = 190
        // Correct formula: 190 / 200 * 100 = 95.00%
        // (Note: average of percentages would be (110 + 90)/2 = 100%, which is incorrect!)
        $this->assertEquals(200.0, $detail['total_target_qty']);
        $this->assertEquals(190.0, $detail['total_actual_qty']);
        $this->assertEquals(95.0, $detail['qty_achievement_percentage']);
    }

    public function test_zero_denominator_yields_belum_ada_data(): void
    {
        // Produksi 2 has no batches
        /** @var ProductionPerformanceService $service */
        $service = app(ProductionPerformanceService::class);
        $detail = $service->getProductionDetail($this->produksi2, '2026-10-01', '2026-10-31', 'all');

        $this->assertEquals(0, $detail['total_batches']);
        $this->assertNull($detail['on_time_percentage']);
        $this->assertEquals('Belum Ada Data', $detail['on_time_percentage_text']);
        $this->assertNull($detail['qty_achievement_percentage']);
        $this->assertEquals('Belum Ada Data', $detail['qty_achievement_percentage_text']);

        // Test view rendering displays "Belum Ada Data"
        $response = $this->actingAs($this->owner)->get(route('owner.employee-performance.production-detail', $this->produksi2));
        $response->assertStatus(200);
        $response->assertSee('Belum Ada Data');
    }

    // ── 5. Unassigned Historical Batches ─────────────────────────────────

    public function test_unassigned_historical_batches_are_reported_transparently(): void
    {
        // Batch with null employee
        $this->createBatchWithResult(
            null,
            50,
            50,
            '2026-10-15',
            '2026-10-15',
            'UNASSIGNED'
        );

        /** @var ProductionPerformanceService $service */
        $service = app(ProductionPerformanceService::class);
        $stats = $service->calculatePerformance('2026-10-01', '2026-10-31', 'all');

        $this->assertEquals(1, $stats['unassigned_batches_count']);
        $this->assertEquals(0, $stats['grand_total_batches']);

        // Check view displays warning banner
        $response = $this->actingAs($this->owner)->get(route('owner.employee-performance.index', ['tab' => 'production', 'preset' => 'all']));
        $response->assertStatus(200);
        $response->assertSee('1 batch historis');
        $response->assertSee('belum memiliki PIC Produksi');
        $response->assertSee('Lengkapi PIC');
    }

    // ── 6. Period Filtering ──────────────────────────────────────────────

    public function test_period_filter_filters_by_result_date(): void
    {
        // Batch completed in September
        $this->createBatchWithResult(
            $this->produksi1,
            50,
            50,
            '2026-09-15',
            '2026-09-15',
            'SEP'
        );

        // Batch completed in October
        $this->createBatchWithResult(
            $this->produksi1,
            50,
            50,
            '2026-10-15',
            '2026-10-15',
            'OCT'
        );

        /** @var ProductionPerformanceService $service */
        $service = app(ProductionPerformanceService::class);

        // Filter October only
        $octDetail = $service->getProductionDetail($this->produksi1, '2026-10-01', '2026-10-31', 'custom');
        $this->assertEquals(1, $octDetail['total_batches']);

        // Filter September only
        $sepDetail = $service->getProductionDetail($this->produksi1, '2026-09-01', '2026-09-30', 'custom');
        $this->assertEquals(1, $sepDetail['total_batches']);

        // Filter all
        $allDetail = $service->getProductionDetail($this->produksi1, '2020-01-01', '2026-12-31', 'all');
        $this->assertEquals(2, $allDetail['total_batches']);
    }
}
