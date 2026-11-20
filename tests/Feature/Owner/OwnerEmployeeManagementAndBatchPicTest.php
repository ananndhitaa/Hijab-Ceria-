<?php

namespace Tests\Feature\Owner;

use App\Enums\BatchStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Master\BomItem;
use App\Models\Master\Customer;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Master\Unit;
use App\Models\Production\BatchMaterial;
use App\Models\Production\ProductionBatch;
use App\Models\Production\ProductionResult;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\Sales\Payment;
use App\Models\System\Outlet;
use App\Models\System\User;
use App\Services\Production\ProductionResultService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OwnerEmployeeManagementAndBatchPicTest extends TestCase
{
    use RefreshDatabase;

    private Outlet $outlet;
    private User $owner;
    private User $admin;
    private User $gudang;
    private User $produksi;
    private User $customerUser;

    private Customer $customer;
    private Product $product;
    private ProductVariant $variant;
    private Material $material;

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
            'name'      => 'Owner Nayara',
            'email'     => 'owner@nayara.test',
            'role'      => UserRole::OWNER,
            'status'    => UserStatus::ACTIVE,
        ]);

        $this->admin = User::factory()->create([
            'outlet_id' => $this->outlet->id,
            'name'      => 'Admin Nayara',
            'email'     => 'admin@nayara.test',
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::ACTIVE,
        ]);

        $this->gudang = User::factory()->create([
            'outlet_id' => $this->outlet->id,
            'name'      => 'Gudang Nayara',
            'email'     => 'gudang@nayara.test',
            'role'      => UserRole::GUDANG,
            'status'    => UserStatus::ACTIVE,
        ]);

        $this->produksi = User::factory()->create([
            'outlet_id' => $this->outlet->id,
            'name'      => 'Siti Produksi',
            'email'     => 'siti@nayara.test',
            'role'      => UserRole::PRODUKSI,
            'status'    => UserStatus::ACTIVE,
        ]);

        $this->customerUser = User::factory()->create([
            'outlet_id' => $this->outlet->id,
            'name'      => 'Customer Portal',
            'email'     => 'cust@buyer.test',
            'role'      => UserRole::CUSTOMER,
            'status'    => UserStatus::ACTIVE,
        ]);

        $this->customer = Customer::create([
            'outlet_id'     => $this->outlet->id,
            'customer_name' => 'Butik Syari',
            'phone'         => '081234567890',
        ]);

        $unit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $this->material = Material::create([
            'material_name' => 'Kain Ceruty',
            'unit_id'       => $unit->id,
            'active'        => true,
        ]);

        $this->product = Product::create(['product_name' => 'Hijab Segiempat', 'active' => true]);
        $this->variant = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'Dusty Pink',
            'sku'                                  => 'HJB-DPK-' . uniqid(),
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => '0.00',
            'direct_labor_cost_per_unit'           => '5000.00',
            'packaging_process_cost_per_good_unit' => '1000.00',
            'other_direct_cost_per_unit'           => '500.00',
            'active'                               => true,
        ]);
    }

    private function createSampleBatch(?User $pic = null, float $targetQty = 100, ?float $actualQty = null): ProductionBatch
    {
        $order = Order::create([
            'outlet_id'   => $this->outlet->id,
            'customer_id' => $this->customer->id,
            'order_no'    => 'ORD-' . uniqid(),
            'order_date'  => '2026-10-01',
            'status'      => OrderStatus::PRODUKSI,
        ]);

        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => $targetQty,
            'unit_selling_price' => 50000,
            'subtotal'           => $targetQty * 50000,
        ]);

        $batch = ProductionBatch::create([
            'order_item_id'          => $orderItem->id,
            'batch_no'               => 'BAT-' . uniqid(),
            'target_qty'             => $targetQty,
            'planned_start_date'     => '2026-10-02',
            'planned_end_date'       => '2026-10-05',
            'status'                 => $actualQty !== null ? BatchStatus::SELESAI : BatchStatus::PRODUKSI,
            'requires_printing'      => false,
            'production_employee_id' => $pic?->id,
        ]);

        $bm = BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->material->id,
            'required_qty'  => $targetQty,
            'allocated_qty' => $targetQty,
            'used_qty'      => $targetQty,
        ]);

        if ($actualQty !== null) {
            ProductionResult::create([
                'batch_id'    => $batch->id,
                'actual_qty'  => $actualQty,
                'result_date' => '2026-10-05',
                'recorded_by' => $this->produksi->id,
            ]);
        }

        return $batch;
    }

    private function createOrderWithConfirmedDp(float $qty = 100): Order
    {
        BomItem::firstOrCreate([
            'variant_id'  => $this->variant->id,
            'material_id' => $this->material->id,
        ], [
            'qty_per_unit' => 1.2000,
        ]);

        $order = Order::create([
            'outlet_id'   => $this->outlet->id,
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-DP-' . uniqid(),
            'order_date'  => '2026-10-01',
            'status'      => OrderStatus::DEAL,
        ]);

        $order->items()->create([
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => $qty,
            'unit_selling_price' => 75000,
            'subtotal'           => $qty * 75000,
        ]);

        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-' . uniqid(),
            'invoice_date' => now()->toDateString(),
            'total_amount' => (string) ($qty * 75000),
            'status'       => InvoiceStatus::PARTIAL,
        ]);

        Payment::create([
            'invoice_id'   => $invoice->id,
            'payment_no'   => 'PAY-' . uniqid(),
            'payment_date' => now()->toDateString(),
            'amount'       => ($qty * 75000) * 0.5,
            'payment_type' => PaymentType::DP,
            'status'       => PaymentStatus::CONFIRMED,
        ]);

        return $order;
    }

    // ==================================================
    // AE. TESTS — EMPLOYEE MANAGEMENT
    // ==================================================

    public function test_01_owner_can_access_employee_list(): void
    {
        $response = $this->actingAs($this->owner)->get(route('owner.employees.index'));
        $response->assertOk();
        $response->assertSee('Karyawan');
        $response->assertSee($this->admin->name);
        $response->assertSee($this->gudang->name);
        $response->assertSee($this->produksi->name);
        $response->assertDontSee($this->customerUser->name);
    }

    public function test_02_admin_gets_403_on_employee_list(): void
    {
        $this->actingAs($this->admin)->get(route('owner.employees.index'))->assertForbidden();
    }

    public function test_03_gudang_gets_403_on_employee_list(): void
    {
        $this->actingAs($this->gudang)->get(route('owner.employees.index'))->assertForbidden();
    }

    public function test_04_produksi_gets_403_on_employee_list(): void
    {
        $this->actingAs($this->produksi)->get(route('owner.employees.index'))->assertForbidden();
    }

    public function test_05_customer_gets_403_on_employee_list(): void
    {
        $this->actingAs($this->customerUser)->get(route('owner.employees.index'))->assertForbidden();
    }

    public function test_06_owner_can_create_admin_employee(): void
    {
        $response = $this->actingAs($this->owner)->post(route('owner.employees.store'), [
            'name'                  => 'Rina Baru Admin',
            'email'                 => 'rina.admin@nayara.test',
            'role'                  => UserRole::ADMIN->value,
            'status'                => UserStatus::ACTIVE->value,
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('owner.employees.index'));
        $this->assertDatabaseHas('users', [
            'name'  => 'Rina Baru Admin',
            'email' => 'rina.admin@nayara.test',
            'role'  => UserRole::ADMIN->value,
        ]);
    }

    public function test_07_owner_can_create_gudang_employee(): void
    {
        $response = $this->actingAs($this->owner)->post(route('owner.employees.store'), [
            'name'                  => 'Budi Baru Gudang',
            'email'                 => 'budi.gudang@nayara.test',
            'role'                  => UserRole::GUDANG->value,
            'status'                => UserStatus::ACTIVE->value,
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('owner.employees.index'));
        $this->assertDatabaseHas('users', [
            'name'  => 'Budi Baru Gudang',
            'email' => 'budi.gudang@nayara.test',
            'role'  => UserRole::GUDANG->value,
        ]);
    }

    public function test_08_owner_can_create_produksi_employee(): void
    {
        $response = $this->actingAs($this->owner)->post(route('owner.employees.store'), [
            'name'                  => 'Dewi Baru Produksi',
            'email'                 => 'dewi.prod@nayara.test',
            'role'                  => UserRole::PRODUKSI->value,
            'status'                => UserStatus::ACTIVE->value,
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('owner.employees.index'));
        $this->assertDatabaseHas('users', [
            'name'  => 'Dewi Baru Produksi',
            'email' => 'dewi.prod@nayara.test',
            'role'  => UserRole::PRODUKSI->value,
        ]);
    }

    public function test_09_owner_cannot_create_owner_from_employee_form(): void
    {
        $response = $this->actingAs($this->owner)->post(route('owner.employees.store'), [
            'name'                  => 'Fake Owner',
            'email'                 => 'fake.owner@nayara.test',
            'role'                  => UserRole::OWNER->value,
            'status'                => UserStatus::ACTIVE->value,
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['role']);
        $this->assertDatabaseMissing('users', ['email' => 'fake.owner@nayara.test']);
    }

    public function test_10_owner_cannot_create_customer_from_employee_form(): void
    {
        $response = $this->actingAs($this->owner)->post(route('owner.employees.store'), [
            'name'                  => 'Fake Customer',
            'email'                 => 'fake.cust@nayara.test',
            'role'                  => UserRole::CUSTOMER->value,
            'status'                => UserStatus::ACTIVE->value,
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['role']);
        $this->assertDatabaseMissing('users', ['email' => 'fake.cust@nayara.test']);
    }

    public function test_11_password_is_hashed_on_creation(): void
    {
        $this->actingAs($this->owner)->post(route('owner.employees.store'), [
            'name'                  => 'Hashed Password User',
            'email'                 => 'hashed@nayara.test',
            'role'                  => UserRole::PRODUKSI->value,
            'status'                => UserStatus::ACTIVE->value,
            'password'              => 'superSecret123',
            'password_confirmation' => 'superSecret123',
        ]);

        $user = User::where('email', 'hashed@nayara.test')->firstOrFail();
        $this->assertNotEquals('superSecret123', $user->password);
        $this->assertTrue(Hash::check('superSecret123', $user->password));
    }

    public function test_12_duplicate_email_is_rejected(): void
    {
        $response = $this->actingAs($this->owner)->post(route('owner.employees.store'), [
            'name'                  => 'Duplicate Email Person',
            'email'                 => $this->admin->email, // existing email
            'role'                  => UserRole::ADMIN->value,
            'status'                => UserStatus::ACTIVE->value,
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_13_employee_can_be_deactivated(): void
    {
        $emp = User::factory()->create([
            'outlet_id' => $this->outlet->id,
            'role'      => UserRole::PRODUKSI,
            'status'    => UserStatus::ACTIVE,
        ]);

        $response = $this->actingAs($this->owner)->patch(route('owner.employees.toggle-status', $emp));
        $response->assertRedirect();

        $this->assertEquals(UserStatus::INACTIVE, $emp->fresh()->status);
    }

    public function test_14_inactive_employee_cannot_login(): void
    {
        $emp = User::factory()->create([
            'email'    => 'inactive.worker@nayara.test',
            'password' => 'secret123',
            'role'     => UserRole::PRODUKSI,
            'status'   => UserStatus::INACTIVE,
        ]);

        $response = $this->post(route('login.post'), [
            'email'    => 'inactive.worker@nayara.test',
            'password' => 'secret123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    public function test_15_historical_relationships_remain_after_deactivation(): void
    {
        $batch = $this->createSampleBatch($this->produksi, 100, 100);

        // Deactivate employee
        $this->produksi->update(['status' => UserStatus::INACTIVE]);

        $this->assertEquals($this->produksi->id, $batch->fresh()->production_employee_id);
        $this->assertEquals($this->produksi->name, $batch->fresh()->productionEmployee->name);
    }

    // ==================================================
    // AF. TESTS — PRODUCTION PIC
    // ==================================================

    public function test_16_active_produksi_employee_appears_in_pic_dropdown(): void
    {
        $order = $this->createOrderWithConfirmedDp();
        $response = $this->actingAs($this->admin)->get(route('admin.orders.batches.create', ['order' => $order]));
        $response->assertOk();
        $response->assertViewHas('productionUsers', function ($users) {
            return $users->contains('id', $this->produksi->id);
        });
        $response->assertSee($this->produksi->name);
    }

    public function test_17_admin_employee_does_not_appear_in_pic_dropdown(): void
    {
        $order = $this->createOrderWithConfirmedDp();
        $response = $this->actingAs($this->admin)->get(route('admin.orders.batches.create', ['order' => $order]));
        $response->assertOk();
        $response->assertViewHas('productionUsers', function ($users) {
            return ! $users->contains('id', $this->admin->id);
        });
    }

    public function test_18_gudang_employee_does_not_appear_in_pic_dropdown(): void
    {
        $order = $this->createOrderWithConfirmedDp();
        $response = $this->actingAs($this->admin)->get(route('admin.orders.batches.create', ['order' => $order]));
        $response->assertOk();
        $response->assertViewHas('productionUsers', function ($users) {
            return ! $users->contains('id', $this->gudang->id);
        });
    }

    public function test_19_owner_does_not_appear_in_pic_dropdown(): void
    {
        $order = $this->createOrderWithConfirmedDp();
        $response = $this->actingAs($this->admin)->get(route('admin.orders.batches.create', ['order' => $order]));
        $response->assertOk();
        $response->assertViewHas('productionUsers', function ($users) {
            return ! $users->contains('id', $this->owner->id);
        });
    }

    public function test_20_inactive_produksi_does_not_appear_for_new_assignment(): void
    {
        $inactiveProd = User::factory()->create([
            'name'   => 'Zaki Inactive Worker',
            'email'  => 'zaki.inactive@nayara.test',
            'role'   => UserRole::PRODUKSI,
            'status' => UserStatus::INACTIVE,
        ]);

        $order = $this->createOrderWithConfirmedDp();
        $response = $this->actingAs($this->admin)->get(route('admin.orders.batches.create', ['order' => $order]));
        $response->assertOk();
        $response->assertViewHas('productionUsers', function ($users) use ($inactiveProd) {
            return ! $users->contains('id', $inactiveProd->id);
        });
    }

    public function test_21_admin_can_assign_production_pic(): void
    {
        $order = $this->createOrderWithConfirmedDp(50);
        $item = $order->items->first();

        $response = $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'order_item_id'          => $item->id,
            'target_qty'             => 50,
            'planned_start_date'     => '2026-10-02',
            'planned_end_date'       => '2026-10-06',
            'production_employee_id' => $this->produksi->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('production_batches', [
            'order_item_id'          => $item->id,
            'production_employee_id' => $this->produksi->id,
        ]);
    }

    public function test_22_owner_can_assign_missing_historical_pic(): void
    {
        $batch = $this->createSampleBatch(null, 100, 95);
        $this->assertNull($batch->production_employee_id);

        $response = $this->actingAs($this->owner)->post(route('owner.employee-performance.assign-batch-pic', $batch), [
            'production_employee_id' => $this->produksi->id,
        ]);

        $response->assertRedirect();
        $this->assertEquals($this->produksi->id, $batch->fresh()->production_employee_id);
    }

    public function test_23_owner_cannot_edit_production_result(): void
    {
        $batch = $this->createSampleBatch($this->produksi, 100);

        $response = $this->actingAs($this->owner)->get(route('produksi.batches.production-result.create', $batch));
        $response->assertForbidden();
    }

    public function test_24_missing_pic_blocks_final_production_result_for_future_batches(): void
    {
        $batch = $this->createSampleBatch(null, 100); // no PIC assigned

        $response = $this->actingAs($this->produksi)->get(route('produksi.batches.production-result.create', $batch));
        $response->assertRedirect(route('produksi.batches.show', $batch));
        $response->assertSessionHas('error', 'PIC Produksi belum ditentukan. Tetapkan karyawan Produksi sebelum mencatat hasil produksi.');

        // Test domain service directly
        $service = app(ProductionResultService::class);
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('PIC Produksi belum ditentukan. Tetapkan karyawan Produksi sebelum mencatat hasil produksi.');

        $service->recordFinalResult(
            $batch,
            100,
            '2026-10-05',
            'Test Notes',
            $this->produksi,
            [$batch->batchMaterials->first()->id => 100]
        );
    }

    public function test_25_pic_assignment_does_not_alter_target_actual_result_date(): void
    {
        $batch = $this->createSampleBatch(null, 150, 148);
        $originalTarget = $batch->target_qty;
        $originalActual = $batch->productionResult->actual_qty;
        $originalResultDate = $batch->productionResult->result_date;

        $this->actingAs($this->owner)->post(route('owner.employee-performance.assign-batch-pic', $batch), [
            'production_employee_id' => $this->produksi->id,
        ]);

        $freshBatch = $batch->fresh();
        $this->assertEquals($this->produksi->id, $freshBatch->production_employee_id);
        $this->assertEquals($originalTarget, $freshBatch->target_qty);
        $this->assertEquals($originalActual, $freshBatch->productionResult->actual_qty);
        $this->assertEquals($originalResultDate, $freshBatch->productionResult->result_date);
    }

    // ==================================================
    // AG. TESTS — HISTORICAL
    // ==================================================

    public function test_26_existing_missing_pic_batch_remains_unassigned_until_owner_selects(): void
    {
        $batch = $this->createSampleBatch(null, 100, 100);

        $response = $this->actingAs($this->owner)->get(route('owner.employee-performance.unassigned-batches'));
        $response->assertOk();
        $response->assertSee($batch->batch_no);

        // Still null until action
        $this->assertNull($batch->fresh()->production_employee_id);
    }

    public function test_27_warning_count_matches_actual_missing_pic_batches(): void
    {
        $batch1 = $this->createSampleBatch(null, 100, 100);
        $batch2 = $this->createSampleBatch(null, 50, 50);

        $response = $this->actingAs($this->owner)->get(route('owner.employee-performance.index', ['tab' => 'production', 'preset' => 'all']));
        $response->assertOk();
        $response->assertSee('2 batch historis');
        $response->assertSee('Lengkapi PIC');
    }

    public function test_28_after_one_assignment_warning_count_decreases(): void
    {
        $batch1 = $this->createSampleBatch(null, 100, 100);
        $batch2 = $this->createSampleBatch(null, 50, 50);

        // Assign one
        $this->actingAs($this->owner)->post(route('owner.employee-performance.assign-batch-pic', $batch1), [
            'production_employee_id' => $this->produksi->id,
        ]);

        $response = $this->actingAs($this->owner)->get(route('owner.employee-performance.index', ['tab' => 'production', 'preset' => 'all']));
        $response->assertOk();
        $response->assertSee('1 batch historis');
    }

    public function test_29_at_zero_missing_pic_warning_disappears(): void
    {
        $batch = $this->createSampleBatch($this->produksi, 100, 100);

        $response = $this->actingAs($this->owner)->get(route('owner.employee-performance.index', ['tab' => 'production', 'preset' => 'all']));
        $response->assertOk();
        $response->assertDontSee('batch historis belum memiliki PIC Produksi');
        $response->assertDontSee('Lengkapi PIC');
    }

    public function test_30_no_random_automatic_assignment(): void
    {
        $batch = $this->createSampleBatch(null, 100, 100);

        // View index and unassigned batches page without submitting
        $this->actingAs($this->owner)->get(route('owner.employee-performance.index'));
        $this->actingAs($this->owner)->get(route('owner.employee-performance.unassigned-batches'));

        $this->assertNull($batch->fresh()->production_employee_id);
    }

    // ==================================================
    // AH. TESTS — PERFORMANCE
    // ==================================================

    public function test_31_assigned_employee_name_appears_in_owner_performance(): void
    {
        $this->createSampleBatch($this->produksi, 100, 98);

        $response = $this->actingAs($this->owner)->get(route('owner.employee-performance.index', ['tab' => 'production', 'preset' => 'all']));
        $response->assertOk();
        $response->assertSee($this->produksi->name);
    }

    public function test_32_historical_assigned_batch_enters_correct_employee_performance(): void
    {
        $batch = $this->createSampleBatch(null, 100, 95);

        // Before assignment: Siti has 0 completed batches
        $responseBefore = $this->actingAs($this->owner)->get(route('owner.employee-performance.production-detail', ['user' => $this->produksi->id, 'preset' => 'all']));
        $responseBefore->assertOk();
        $responseBefore->assertSee('Belum ada batch produksi yang diselesaikan oleh karyawan ini');

        // Assign to Siti
        $this->actingAs($this->owner)->post(route('owner.employee-performance.assign-batch-pic', $batch), [
            'production_employee_id' => $this->produksi->id,
        ]);

        // After assignment: Batch is now visible in Siti's performance detail
        $responseAfter = $this->actingAs($this->owner)->get(route('owner.employee-performance.production-detail', ['user' => $this->produksi->id, 'preset' => 'all']));
        $responseAfter->assertOk();
        $responseAfter->assertSee($batch->batch_no);
    }

    public function test_33_inactive_historical_employee_can_still_appear_in_historical_performance(): void
    {
        $retiredWorker = User::factory()->create([
            'name'   => 'Pensiunan Pekerja',
            'role'   => UserRole::PRODUKSI,
            'status' => UserStatus::INACTIVE,
        ]);

        $this->createSampleBatch($retiredWorker, 80, 80);

        $response = $this->actingAs($this->owner)->get(route('owner.employee-performance.index', ['tab' => 'production', 'preset' => 'all']));
        $response->assertOk();
        $response->assertSee('Pensiunan Pekerja');
    }

    public function test_34_new_assignments_cannot_select_inactive_employee(): void
    {
        $inactiveWorker = User::factory()->create([
            'name'   => 'Worker Inactive',
            'role'   => UserRole::PRODUKSI,
            'status' => UserStatus::INACTIVE,
        ]);

        $order = $this->createOrderWithConfirmedDp(50);
        $item = $order->items->first();

        // Submitting inactive user ID on new batch should fail validation
        $response = $this->actingAs($this->admin)->post(route('admin.orders.batches.store', $order), [
            'order_item_id'          => $item->id,
            'target_qty'             => 50,
            'planned_start_date'     => '2026-10-02',
            'planned_end_date'       => '2026-10-06',
            'production_employee_id' => $inactiveWorker->id,
        ]);

        $response->assertSessionHasErrors(['production_employee_id']);
    }

    public function test_35_performance_formula_unchanged(): void
    {
        // 1 on-time batch with target 100, actual 100
        $this->createSampleBatch($this->produksi, 100, 100);

        $response = $this->actingAs($this->owner)->get(route('owner.employee-performance.index', ['tab' => 'production', 'preset' => 'all']));
        $response->assertOk();
        $response->assertSee('100,00%');
    }

    // ==================================================
    // AI. TESTS — DRAFT PERSISTENCE & BULK ASSIGN UX
    // ==================================================

    public function test_36_single_row_save_flashes_saved_batch_ids_and_keeps_other_batches_unassigned(): void
    {
        $batch1 = $this->createSampleBatch(null, 100, 100);
        $batch2 = $this->createSampleBatch(null, 80, 80);

        $response = $this->actingAs($this->owner)->post(route('owner.employee-performance.assign-batch-pic', $batch1), [
            'production_employee_id' => $this->produksi->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('saved_batch_ids', [$batch1->id]);
        $response->assertSessionHas('success');

        $this->assertEquals($this->produksi->id, $batch1->fresh()->production_employee_id);
        $this->assertNull($batch2->fresh()->production_employee_id);
    }

    public function test_37_bulk_assign_multiple_batches_with_db_transaction_and_skips_unselected(): void
    {
        $batch1 = $this->createSampleBatch(null, 100, 100);
        $batch2 = $this->createSampleBatch(null, 120, 115);
        $batch3 = $this->createSampleBatch(null, 90, 90);

        $worker2 = User::factory()->create([
            'outlet_id' => $this->outlet->id,
            'name'      => 'Agus Produksi',
            'email'  => 'agus@nayara.test',
            'role'   => UserRole::PRODUKSI,
            'status' => UserStatus::ACTIVE,
        ]);

        $response = $this->actingAs($this->owner)->post(route('owner.employee-performance.bulk-assign-pic'), [
            'assignments' => [
                $batch1->id => $this->produksi->id,
                $batch2->id => $worker2->id,
                $batch3->id => '', // Unselected: should be skipped safely
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', '2 batch historis berhasil dilengkapi PIC Produksi.');
        $response->assertSessionHas('saved_batch_ids', [$batch1->id, $batch2->id]);

        $this->assertEquals($this->produksi->id, $batch1->fresh()->production_employee_id);
        $this->assertEquals($worker2->id, $batch2->fresh()->production_employee_id);
        $this->assertNull($batch3->fresh()->production_employee_id);
    }

    public function test_38_bulk_assign_rejects_invalid_production_user_and_rolls_back(): void
    {
        $batch1 = $this->createSampleBatch(null, 100, 100);
        $batch2 = $this->createSampleBatch(null, 120, 120);

        // Assigning to a customer or invalid ID must fail validation
        $response = $this->actingAs($this->owner)->post(route('owner.employee-performance.bulk-assign-pic'), [
            'assignments' => [
                $batch1->id => $this->produksi->id,
                $batch2->id => $this->customerUser->id, // invalid: role CUSTOMER, not PRODUKSI
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['assignments']);

        // Both batches remain null due to transaction rollback
        $this->assertNull($batch1->fresh()->production_employee_id);
        $this->assertNull($batch2->fresh()->production_employee_id);
    }

    public function test_39_bulk_assign_authorization_owner_only(): void
    {
        $batch = $this->createSampleBatch(null, 100, 100);

        $payload = [
            'assignments' => [$batch->id => $this->produksi->id],
        ];

        // Customer forbidden
        $this->actingAs($this->customerUser)->post(route('owner.employee-performance.bulk-assign-pic'), $payload)->assertForbidden();
        // Gudang forbidden
        $this->actingAs($this->gudang)->post(route('owner.employee-performance.bulk-assign-pic'), $payload)->assertForbidden();
        // Produksi forbidden
        $this->actingAs($this->produksi)->post(route('owner.employee-performance.bulk-assign-pic'), $payload)->assertForbidden();
        // Admin forbidden
        $this->actingAs($this->admin)->post(route('owner.employee-performance.bulk-assign-pic'), $payload)->assertForbidden();

        // Also test alias route
        $this->actingAs($this->customerUser)->post(route('owner.employee-performance.unassigned-batches.assign-bulk'), $payload)->assertForbidden();

        // Owner allowed
        $this->actingAs($this->owner)->post(route('owner.employee-performance.bulk-assign-pic'), $payload)->assertRedirect();
        $this->assertEquals($this->produksi->id, $batch->fresh()->production_employee_id);
    }

    public function test_40_bulk_assign_preserves_target_actual_and_result_date(): void
    {
        $batch = $this->createSampleBatch(null, 150, 148);
        $originalTarget = $batch->target_qty;
        $originalActual = $batch->productionResult->actual_qty;
        $originalResultDate = $batch->productionResult->result_date;

        $this->actingAs($this->owner)->post(route('owner.employee-performance.bulk-assign-pic'), [
            'assignments' => [$batch->id => $this->produksi->id],
        ]);

        $freshBatch = $batch->fresh();
        $this->assertEquals($this->produksi->id, $freshBatch->production_employee_id);
        $this->assertEquals($originalTarget, $freshBatch->target_qty);
        $this->assertEquals($originalActual, $freshBatch->productionResult->actual_qty);
        $this->assertEquals($originalResultDate, $freshBatch->productionResult->result_date);
    }

    public function test_41_all_batches_assigned_renders_empty_state_and_clears_warning(): void
    {
        $batch = $this->createSampleBatch(null, 100, 100);

        // Before: unassigned page shows batch
        $beforeResp = $this->actingAs($this->owner)->get(route('owner.employee-performance.unassigned-batches'));
        $beforeResp->assertOk();
        $beforeResp->assertSee($batch->batch_no);

        // Assign batch
        $this->actingAs($this->owner)->post(route('owner.employee-performance.bulk-assign-pic'), [
            'assignments' => [$batch->id => $this->produksi->id],
        ]);

        // After: unassigned page shows empty state
        $afterResp = $this->actingAs($this->owner)->get(route('owner.employee-performance.unassigned-batches'));
        $afterResp->assertOk();
        $afterResp->assertSee('Semua Batch Historis Sudah Memiliki PIC Produksi');

        // Performance page warning disappears
        $perfResp = $this->actingAs($this->owner)->get(route('owner.employee-performance.index', ['tab' => 'production', 'preset' => 'all']));
        $perfResp->assertOk();
        $perfResp->assertDontSee('Lengkapi PIC');
    }

    public function test_42_unassigned_batches_view_renders_draft_persistence_and_bulk_save_ui(): void
    {
        $batch = $this->createSampleBatch(null, 100, 100);

        $response = $this->actingAs($this->owner)->get(route('owner.employee-performance.unassigned-batches'));
        $response->assertOk();

        $content = $response->getContent();
        // UI elements
        $this->assertStringContainsString('Simpan Semua Pilihan', $content);
        $this->assertStringContainsString('id="btn-save-all"', $content);
        $this->assertStringContainsString('id="bulk-assign-form"', $content);
        $this->assertStringContainsString('id="unsaved-count-badge"', $content);

        // Client-side script features
        $this->assertStringContainsString('nayara:historical-pic-draft', $content);
        $this->assertStringContainsString('sessionStorage.getItem', $content);
        $this->assertStringContainsString('sessionStorage.setItem', $content);
        $this->assertStringContainsString('savedBatchIds.forEach', $content);
    }
}
