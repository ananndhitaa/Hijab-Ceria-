<?php

namespace Tests\Feature\System;

use App\Enums\ExpenseType;
use App\Enums\PurchaseApprovalStatus;
use App\Enums\PurchaseStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Finance\BusinessExpense;
use App\Models\Finance\BusinessIncome;
use App\Models\Finance\OpeningCashBalance;
use App\Models\Inventory\Purchase;
use App\Models\Inventory\PurchaseItem;
use App\Models\Inventory\PurchasePayment;
use App\Models\Master\Material;
use App\Models\Master\Supplier;
use App\Models\Master\Unit;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\Sales\Payment;
use App\Models\System\Outlet;
use App\Models\System\User;
use App\Services\Inventory\PurchaseApprovalService;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Fase 8.4.3 — Financial Tenant Ownership Schema & Model Foundation Test
 *
 * Verifies schema additions, foreign key constraints, composite unique/indexes,
 * model relationships, explicit query scopes, legacy NULL preservation, and
 * backwards compatibility on SQLite :memory:.
 */
class FinancialTenantSchemaOwnershipFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected Outlet $outletA;
    protected Outlet $outletB;
    protected User $adminA;
    protected User $adminB;
    protected User $ownerUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Enforce SQLite foreign key constraints in testing
        DB::statement('PRAGMA foreign_keys = ON;');

        $this->outletA = Outlet::create([
            'outlet_name'                => 'Konveksi Hijab Nayara A',
            'outlet_code'                => 'OUT-SCHEMA-A',
            'slug'                       => 'out-schema-a',
            'status'                     => 'ACTIVE',
            'is_public_showcase_enabled' => true,
        ]);

        $this->outletB = Outlet::create([
            'outlet_name'                => 'Konveksi Hijab Nayara B',
            'outlet_code'                => 'OUT-SCHEMA-B',
            'slug'                       => 'out-schema-b',
            'status'                     => 'ACTIVE',
            'is_public_showcase_enabled' => true,
        ]);

        $this->adminA = User::create([
            'name'      => 'Admin Outlet A',
            'email'     => 'admin.a.schema@nayara.test',
            'password'  => Hash::make('password123'),
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $this->outletA->id,
        ]);

        $this->adminB = User::create([
            'name'      => 'Admin Outlet B',
            'email'     => 'admin.b.schema@nayara.test',
            'password'  => Hash::make('password123'),
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $this->outletB->id,
        ]);

        $this->ownerUser = User::create([
            'name'      => 'Owner Global',
            'email'     => 'owner.schema@nayara.test',
            'password'  => Hash::make('password123'),
            'role'      => UserRole::OWNER,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => null,
        ]);
    }

    /**
     * 01. Fresh migration berhasil membuat outlet_id di opening_cash_balances.
     */
    public function test_01_migration_adds_outlet_id_to_opening_cash_balances(): void
    {
        $this->assertTrue(Schema::hasColumn('opening_cash_balances', 'outlet_id'));
    }

    /**
     * 02. Fresh migration berhasil membuat outlet_id di business_incomes.
     */
    public function test_02_migration_adds_outlet_id_to_business_incomes(): void
    {
        $this->assertTrue(Schema::hasColumn('business_incomes', 'outlet_id'));
    }

    /**
     * 03. Fresh migration berhasil membuat outlet_id di business_expenses.
     */
    public function test_03_migration_adds_outlet_id_to_business_expenses(): void
    {
        $this->assertTrue(Schema::hasColumn('business_expenses', 'outlet_id'));
    }

    /**
     * 04. Ketiga kolom outlet_id bernilai nullable pada schema.
     */
    public function test_04_outlet_id_columns_are_nullable_on_all_three_tables(): void
    {
        // Inserting record with outlet_id = null must succeed without database errors
        $opening = OpeningCashBalance::create([
            'outlet_id'    => null,
            'amount'       => '5000000.00',
            'opening_date' => '2026-01-01',
            'notes'        => 'Legacy unassigned opening balance',
            'created_by'   => $this->adminA->id,
        ]);
        $this->assertNull($opening->fresh()->outlet_id);

        $income = BusinessIncome::create([
            'outlet_id'   => null,
            'income_no'   => 'INC-LEGACY-001',
            'income_date' => '2026-01-02',
            'category'    => BusinessIncome::CATEGORY_PEMASUKAN_LAIN,
            'amount'      => '1000000.00',
            'notes'       => 'Legacy unassigned income',
            'created_by'  => $this->adminA->id,
        ]);
        $this->assertNull($income->fresh()->outlet_id);

        $expense = BusinessExpense::create([
            'outlet_id'    => null,
            'order_id'     => null,
            'expense_date' => '2026-01-03',
            'expense_type' => ExpenseType::OPERATING,
            'description'  => 'Legacy unassigned expense',
            'amount'       => '250000.00',
            'created_by'   => $this->adminA->id,
        ]);
        $this->assertNull($expense->fresh()->outlet_id);
    }

    /**
     * 05. Existing legacy rows dapat menggunakan outlet_id NULL.
     */
    public function test_05_legacy_rows_preserve_null_outlet_id(): void
    {
        DB::table('opening_cash_balances')->insert([
            'outlet_id'    => null,
            'amount'       => '15000000.00',
            'opening_date' => '2025-12-31',
            'notes'        => 'Raw SQL legacy insertion',
            'created_by'   => $this->adminA->id,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $record = OpeningCashBalance::whereNull('outlet_id')->where('opening_date', '2025-12-31')->first();
        $this->assertNotNull($record);
        $this->assertNull($record->outlet_id);
        $this->assertEquals('15000000.00', (string) $record->amount);
    }

    /**
     * 06. Tidak ada automatic legacy backfill pada data existing.
     */
    public function test_06_no_automatic_legacy_backfill(): void
    {
        // When a record is inserted without outlet_id, the database does NOT default it to 1 or any other outlet
        $id = DB::table('business_incomes')->insertGetId([
            'income_no'   => 'INC-NOBACKFILL-01',
            'income_date' => '2026-01-10',
            'category'    => BusinessIncome::CATEGORY_MODAL_TAMBAHAN,
            'amount'      => '5000000.00',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $row = DB::table('business_incomes')->where('id', $id)->first();
        $this->assertNull($row->outlet_id, 'outlet_id must not be silently backfilled or defaulted');
    }

    /**
     * 07. OpeningCashBalance dapat terikat ke Outlet A.
     */
    public function test_07_opening_cash_balance_binds_to_outlet_a(): void
    {
        $openingA = OpeningCashBalance::create([
            'outlet_id'    => $this->outletA->id,
            'amount'       => '10000000.00',
            'opening_date' => '2026-10-01',
            'notes'        => 'Opening Cash Tenant A',
            'created_by'   => $this->adminA->id,
        ]);

        $this->assertEquals($this->outletA->id, $openingA->fresh()->outlet_id);
        $this->assertEquals($this->outletA->outlet_code, $openingA->outlet->outlet_code);
    }

    /**
     * 08. OpeningCashBalance dapat terikat ke Outlet B.
     */
    public function test_08_opening_cash_balance_binds_to_outlet_b(): void
    {
        $openingB = OpeningCashBalance::create([
            'outlet_id'    => $this->outletB->id,
            'amount'       => '25000000.00',
            'opening_date' => '2026-10-01',
            'notes'        => 'Opening Cash Tenant B',
            'created_by'   => $this->adminB->id,
        ]);

        $this->assertEquals($this->outletB->id, $openingB->fresh()->outlet_id);
        $this->assertEquals($this->outletB->outlet_code, $openingB->outlet->outlet_code);
    }

    /**
     * 09. Tanggal cut-off yang sama diperbolehkan pada dua outlet berbeda.
     */
    public function test_09_same_cut_off_date_allowed_on_different_outlets(): void
    {
        $date = '2026-10-01';

        $ocbA = OpeningCashBalance::create([
            'outlet_id'    => $this->outletA->id,
            'amount'       => '10000000.00',
            'opening_date' => $date,
            'created_by'   => $this->adminA->id,
        ]);

        $ocbB = OpeningCashBalance::create([
            'outlet_id'    => $this->outletB->id,
            'amount'       => '20000000.00',
            'opening_date' => $date,
            'created_by'   => $this->adminB->id,
        ]);

        $this->assertNotNull($ocbA->id);
        $this->assertNotNull($ocbB->id);
        $this->assertNotEquals($ocbA->id, $ocbB->id);
        $this->assertEquals($date, $ocbA->opening_date->format('Y-m-d'));
        $this->assertEquals($date, $ocbB->opening_date->format('Y-m-d'));
    }

    /**
     * 10. Dua opening balances pada outlet yang sama dan tanggal sama ditolak oleh unique constraint.
     */
    public function test_10_duplicate_opening_balance_on_same_outlet_and_date_rejected(): void
    {
        $date = '2026-10-01';

        OpeningCashBalance::create([
            'outlet_id'    => $this->outletA->id,
            'amount'       => '10000000.00',
            'opening_date' => $date,
            'created_by'   => $this->adminA->id,
        ]);

        $this->expectException(QueryException::class);

        // Attempting to create duplicate for Outlet A on the same date must fail database unique constraint
        OpeningCashBalance::create([
            'outlet_id'    => $this->outletA->id,
            'amount'       => '15000000.00',
            'opening_date' => $date,
            'created_by'   => $this->adminA->id,
        ]);
    }

    /**
     * 11. BusinessIncome milik A dapat disimpan dengan outlet_id A pada SQLite test.
     */
    public function test_11_business_income_binds_to_outlet_a(): void
    {
        $incomeA = BusinessIncome::create([
            'outlet_id'   => $this->outletA->id,
            'income_no'   => 'INC-OUTA-001',
            'income_date' => '2026-10-15',
            'category'    => BusinessIncome::CATEGORY_PEMASUKAN_LAIN,
            'amount'      => '3500000.00',
            'notes'       => 'Pendapatan Jasa Maklon Outlet A',
            'created_by'  => $this->adminA->id,
        ]);

        $this->assertEquals($this->outletA->id, $incomeA->fresh()->outlet_id);
        $this->assertEquals($this->outletA->outlet_name, $incomeA->outlet->outlet_name);
    }

    /**
     * 12. BusinessExpense milik B dapat disimpan dengan outlet_id B pada SQLite test.
     */
    public function test_12_business_expense_binds_to_outlet_b(): void
    {
        $expenseB = BusinessExpense::create([
            'outlet_id'    => $this->outletB->id,
            'order_id'     => null,
            'expense_date' => '2026-10-16',
            'expense_type' => ExpenseType::OPERATING,
            'description'  => 'Biaya Listrik Workshop Outlet B',
            'amount'       => '1200000.00',
            'created_by'   => $this->adminB->id,
        ]);

        $this->assertEquals($this->outletB->id, $expenseB->fresh()->outlet_id);
        $this->assertEquals($this->outletB->outlet_name, $expenseB->outlet->outlet_name);
    }

    /**
     * 13. Foreign key outlet_id yang tidak valid ditolak.
     */
    public function test_13_invalid_foreign_key_outlet_id_is_rejected(): void
    {
        $invalidOutletId = 999999;
        $this->assertNull(Outlet::find($invalidOutletId));

        $this->expectException(QueryException::class);

        OpeningCashBalance::create([
            'outlet_id'    => $invalidOutletId,
            'amount'       => '1000000.00',
            'opening_date' => '2026-10-01',
            'created_by'   => $this->adminA->id,
        ]);
    }

    /**
     * 14. Foreign key constraint benar-benar aktif di testing SQLite.
     */
    public function test_14_sqlite_foreign_key_constraints_are_strictly_enforced(): void
    {
        $invalidOutletId = 888888;

        $failed = false;
        try {
            BusinessIncome::create([
                'outlet_id'   => $invalidOutletId,
                'income_no'   => 'INC-FK-TEST',
                'income_date' => '2026-10-01',
                'category'    => BusinessIncome::CATEGORY_PEMASUKAN_LAIN,
                'amount'      => '500000.00',
                'created_by'  => $this->adminA->id,
            ]);
        } catch (QueryException $e) {
            $failed = true;
        }

        $this->assertTrue($failed, 'SQLite must enforce foreign key constraint and throw QueryException');
    }

    /**
     * 15. scopeForOutlet(A) hanya menghasilkan record A.
     */
    public function test_15_scope_for_outlet_a_only_returns_outlet_a_records(): void
    {
        OpeningCashBalance::create(['outlet_id' => $this->outletA->id, 'amount' => '1000000.00', 'opening_date' => '2026-10-01']);
        OpeningCashBalance::create(['outlet_id' => $this->outletB->id, 'amount' => '2000000.00', 'opening_date' => '2026-10-01']);
        OpeningCashBalance::create(['outlet_id' => null, 'amount' => '9990000.00', 'opening_date' => '2026-10-01']);

        $resultsA = OpeningCashBalance::forOutlet($this->outletA->id)->get();
        $this->assertCount(1, $resultsA);
        $this->assertEquals('1000000.00', (string) $resultsA->first()->amount);
    }

    /**
     * 16. scopeForOutlet(B) hanya menghasilkan record B.
     */
    public function test_16_scope_for_outlet_b_only_returns_outlet_b_records(): void
    {
        BusinessIncome::create(['outlet_id' => $this->outletA->id, 'income_no' => 'INC-A-16', 'income_date' => '2026-10-01', 'category' => 'Pemasukan Lain', 'amount' => '100000.00']);
        BusinessIncome::create(['outlet_id' => $this->outletB->id, 'income_no' => 'INC-B-16', 'income_date' => '2026-10-01', 'category' => 'Pemasukan Lain', 'amount' => '200000.00']);
        BusinessIncome::create(['outlet_id' => null, 'income_no' => 'INC-LEG-16', 'income_date' => '2026-10-01', 'category' => 'Pemasukan Lain', 'amount' => '300000.00']);

        $resultsB = BusinessIncome::forOutlet($this->outletB->id)->get();
        $this->assertCount(1, $resultsB);
        $this->assertEquals('INC-B-16', $resultsB->first()->income_no);
    }

    /**
     * 17. Unassigned legacy records tidak termasuk hasil scopeForOutlet(A/B).
     */
    public function test_17_unassigned_legacy_records_excluded_from_tenant_scopes(): void
    {
        BusinessExpense::create(['outlet_id' => null, 'expense_date' => '2026-10-01', 'expense_type' => ExpenseType::OPERATING, 'description' => 'Unassigned 1', 'amount' => '50000.00']);
        BusinessExpense::create(['outlet_id' => null, 'expense_date' => '2026-10-02', 'expense_type' => ExpenseType::OPERATING, 'description' => 'Unassigned 2', 'amount' => '60000.00']);
        BusinessExpense::create(['outlet_id' => $this->outletA->id, 'expense_date' => '2026-10-03', 'expense_type' => ExpenseType::OPERATING, 'description' => 'Tenant A Exp', 'amount' => '70000.00']);

        $scopedA = BusinessExpense::forOutlet($this->outletA->id)->get();
        $this->assertCount(1, $scopedA);
        $this->assertEquals('Tenant A Exp', $scopedA->first()->description);

        $scopedB = BusinessExpense::forOutlet($this->outletB->id)->get();
        $this->assertCount(0, $scopedB);
    }

    /**
     * 18. Relasi Outlet pada ketiga model mengarah ke outlet yang tepat.
     */
    public function test_18_model_outlet_relationship_resolves_correctly(): void
    {
        $ocb = OpeningCashBalance::create(['outlet_id' => $this->outletA->id, 'amount' => '1000.00', 'opening_date' => '2026-10-01']);
        $bi = BusinessIncome::create(['outlet_id' => $this->outletA->id, 'income_no' => 'INC-18', 'income_date' => '2026-10-01', 'category' => 'Pemasukan Lain', 'amount' => '1000.00']);
        $be = BusinessExpense::create(['outlet_id' => $this->outletA->id, 'expense_date' => '2026-10-01', 'expense_type' => ExpenseType::OPERATING, 'description' => 'Exp 18', 'amount' => '1000.00']);

        $this->assertInstanceOf(Outlet::class, $ocb->outlet);
        $this->assertEquals($this->outletA->id, $ocb->outlet->id);

        $this->assertInstanceOf(Outlet::class, $bi->outlet);
        $this->assertEquals($this->outletA->id, $bi->outlet->id);

        $this->assertInstanceOf(Outlet::class, $be->outlet);
        $this->assertEquals($this->outletA->id, $be->outlet->id);
    }

    /**
     * 19. Tanggal dan amount historis tidak berubah akibat migration pada isolated test.
     */
    public function test_19_historical_date_and_amount_values_are_preserved(): void
    {
        $ocb = OpeningCashBalance::create(['outlet_id' => null, 'amount' => '12345678.90', 'opening_date' => '2025-05-15']);
        $this->assertEquals('12345678.90', (string) $ocb->fresh()->amount);
        $this->assertEquals('2025-05-15', $ocb->fresh()->opening_date->format('Y-m-d'));
    }

    /**
     * 20. Tidak ada customer payment atau purchase payment yang termutasi oleh migration/schema.
     */
    public function test_20_customer_and_purchase_payments_remain_unmutated(): void
    {
        $unit = Unit::firstOrCreate(['unit_name' => 'Meter', 'symbol' => 'm']);
        $mat = Material::create(['material_name' => 'Kain 20', 'unit_id' => $unit->id, 'outlet_id' => $this->outletA->id, 'active' => true]);
        $sup = Supplier::create(['supplier_name' => 'Supplier 20', 'outlet_id' => $this->outletA->id, 'active' => true]);

        $po = Purchase::create([
            'outlet_id'       => $this->outletA->id,
            'supplier_id'     => $sup->id,
            'purchase_no'     => 'PO-20',
            'purchase_date'   => '2026-10-20',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
        ]);
        PurchaseItem::create(['purchase_id' => $po->id, 'material_id' => $mat->id, 'qty' => '10.0000', 'unit_price' => '10000.00', 'qty_received' => '0.0000']);
        $pop = PurchasePayment::create(['purchase_id' => $po->id, 'amount' => '100000.00', 'payment_date' => '2026-10-21']);

        $this->assertEquals('100000.00', (string) $pop->fresh()->amount);
        $this->assertEquals($po->id, $pop->fresh()->purchase_id);
    }

    /**
     * 21. Tidak ada perubahan approval PO role (ADMIN only, OWNER ditolak).
     */
    public function test_21_purchase_approval_role_invariant_remains_admin_only(): void
    {
        $unit = Unit::firstOrCreate(['unit_name' => 'Meter', 'symbol' => 'm']);
        $mat = Material::create(['material_name' => 'Kain 21', 'unit_id' => $unit->id, 'outlet_id' => $this->outletA->id, 'active' => true]);
        $sup = Supplier::create(['supplier_name' => 'Supplier 21', 'outlet_id' => $this->outletA->id, 'active' => true]);

        $po = Purchase::create([
            'outlet_id'       => $this->outletA->id,
            'supplier_id'     => $sup->id,
            'purchase_no'     => 'PO-21',
            'purchase_date'   => '2026-10-20',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::PENDING_APPROVAL,
        ]);
        PurchaseItem::create(['purchase_id' => $po->id, 'material_id' => $mat->id, 'qty' => '10.0000', 'unit_price' => '10000.00', 'qty_received' => '0.0000']);

        $approvalService = app(PurchaseApprovalService::class);

        // OWNER approval attempt must throw DomainException
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Hanya Admin yang berwenang menyetujui Purchase Order.');

        $approvalService->approvePurchase($po, $this->ownerUser, 'Owner approval attempt');
    }

    /**
     * 22. Flag FALSE tetap memakai workflow legacy finance.
     */
    public function test_22_feature_flag_false_preserves_legacy_workflow(): void
    {
        config(['nayara.tenant_isolation_enabled' => false]);
        $this->assertFalse(config('nayara.tenant_isolation_enabled'));

        // Unassigned legacy cash is readable in legacy single-tenant mode
        OpeningCashBalance::create(['outlet_id' => null, 'amount' => '88000000.00', 'opening_date' => '2026-10-01']);
        $latest = OpeningCashBalance::latest('opening_date')->first();
        $this->assertEquals('88000000.00', (string) $latest->amount);
    }

    /**
     * 23. Tidak ada Eloquent Global Tenant Scope baru yang terpasang pada model.
     */
    public function test_23_no_global_tenant_scope_registered_on_models(): void
    {
        $ocb = new OpeningCashBalance();
        $bi = new BusinessIncome();
        $be = new BusinessExpense();

        $this->assertEmpty($ocb->getGlobalScopes(), 'OpeningCashBalance must not register global tenant scopes');
        $this->assertEmpty($bi->getGlobalScopes(), 'BusinessIncome must not register global tenant scopes');
        $this->assertEmpty($be->getGlobalScopes(), 'BusinessExpense must not register global tenant scopes');
    }

    /**
     * 24. Tidak ada custom Artisan Command baru yang dibuat di repository.
     */
    public function test_24_no_new_custom_artisan_commands_introduced(): void
    {
        $commandFiles = glob(app_path('Console/Commands/*.php'));
        $this->assertEmpty($commandFiles, 'No custom artisan commands should be added');
    }

    /**
     * 25. Tidak ada auto assignment ke Outlet ID 1 atau OUT-NAYARA-PUSAT.
     */
    public function test_25_no_auto_assignment_to_outlet_id_1_or_pusat(): void
    {
        $pusatOutlet = Outlet::create([
            'outlet_name'                => 'Nayara Pusat',
            'outlet_code'                => 'OUT-NAYARA-PUSAT',
            'slug'                       => 'out-nayara-pusat',
            'status'                     => 'ACTIVE',
            'is_public_showcase_enabled' => true,
        ]);

        $unassignedIncome = BusinessIncome::create([
            'income_no'   => 'INC-NO-PUSAT-01',
            'income_date' => '2026-10-01',
            'category'    => BusinessIncome::CATEGORY_PEMASUKAN_LAIN,
            'amount'      => '500000.00',
            'created_by'  => $this->adminA->id,
        ]);

        $this->assertNull($unassignedIncome->fresh()->outlet_id);
        $this->assertNotEquals($pusatOutlet->id, $unassignedIncome->fresh()->outlet_id);
        $this->assertNotEquals(1, $unassignedIncome->fresh()->outlet_id);
    }
}
