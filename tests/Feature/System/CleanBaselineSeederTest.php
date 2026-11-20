<?php

namespace Tests\Feature\System;

use App\Enums\UserRole;
use App\Models\Finance\BusinessExpense;
use App\Models\Finance\OrderFinancial;
use App\Models\Inventory\MaterialStock;
use App\Models\Inventory\Purchase;
use App\Models\Inventory\PurchaseItem;
use App\Models\Inventory\StockMovement;
use App\Models\Master\BomItem;
use App\Models\Master\BomTemplate;
use App\Models\Master\BomTemplateItem;
use App\Models\Master\Customer;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Master\Supplier;
use App\Models\Master\Unit;
use Illuminate\Support\Facades\DB;
use App\Models\Production\BatchMaterial;
use App\Models\Production\ProductionBatch;
use App\Models\Production\ProductionResult;
use App\Models\Production\QcResult;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\Sales\Payment;
use App\Models\System\DocumentSequence;
use App\Models\System\OrderStatusHistory;
use App\Models\System\OrderTrackingToken;
use App\Models\System\Shipment;
use App\Models\System\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CleanBaselineSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_database_seeder_creates_clean_baseline_with_zero_business_data(): void
    {
        // Execute default database seeder
        $this->seed(DatabaseSeeder::class);

        // 1. Bootstrap users: Exactly 5 accounts
        $this->assertSame(5, User::count(), 'Users table must contain exactly 5 bootstrap accounts.');

        $admin = User::where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin);
        $this->assertSame(UserRole::ADMIN, $admin->role);
        $this->assertTrue($admin->isActive());

        $gudang = User::where('email', 'gudang@example.com')->first();
        $this->assertNotNull($gudang);
        $this->assertSame(UserRole::GUDANG, $gudang->role);
        $this->assertTrue($gudang->isActive());

        $produksi = User::where('email', 'produksi@example.com')->first();
        $this->assertNotNull($produksi);
        $this->assertSame(UserRole::PRODUKSI, $produksi->role);
        $this->assertTrue($produksi->isActive());

        $owner = User::where('email', 'owner@example.com')->first();
        $this->assertNotNull($owner);
        $this->assertSame(UserRole::OWNER, $owner->role);
        $this->assertTrue($owner->isActive());

        $customerUser = User::where('email', 'customer@example.com')->first();
        $this->assertNotNull($customerUser);
        $this->assertSame(UserRole::CUSTOMER, $customerUser->role);
        $this->assertTrue($customerUser->isActive());

        // CUSTOMER account is strictly unlinked at baseline
        $this->assertNull($customerUser->customer, 'Customer user must have null linked Customer profile.');

        // 2. Bootstrap reference units: Exactly 9 standard units
        $this->assertSame(9, Unit::count(), 'Units table must contain exactly 9 reference units.');
        $expectedSymbols = ['pcs', 'kg', 'm', 'roll', 'lbr', 'lsn', 'gr', 'ltr', 'set'];
        $actualSymbols = Unit::pluck('symbol')->sort()->values()->all();
        sort($expectedSymbols);
        $this->assertSame($expectedSymbols, $actualSymbols);

        // 3. Business Master Tables: Strictly 0
        $this->assertSame(0, Customer::count(), 'customers must be 0.');
        $this->assertSame(0, Supplier::count(), 'suppliers must be 0.');
        $this->assertSame(0, Material::count(), 'materials must be 0.');
        $this->assertSame(0, MaterialStock::count(), 'material_stocks must be 0.');
        $this->assertSame(0, Product::count(), 'products must be 0.');
        $this->assertSame(0, ProductVariant::count(), 'product_variants must be 0.');
        $this->assertSame(0, BomItem::count(), 'bom_items must be 0.');
        $this->assertSame(0, BomTemplate::count(), 'bom_templates must be 0.');
        $this->assertSame(0, BomTemplateItem::count(), 'bom_template_items must be 0.');

        // 4. Procurement & Inventory Transaction Tables: Strictly 0
        $this->assertSame(0, Purchase::count(), 'purchases must be 0.');
        $this->assertSame(0, PurchaseItem::count(), 'purchase_items must be 0.');
        $this->assertSame(0, StockMovement::count(), 'stock_movements must be 0.');

        // 5. Sales & Production Transaction Tables: Strictly 0
        $this->assertSame(0, Order::count(), 'orders must be 0.');
        $this->assertSame(0, OrderItem::count(), 'order_items must be 0.');
        $this->assertSame(0, OrderStatusHistory::count(), 'order_status_histories must be 0.');
        $this->assertSame(0, OrderTrackingToken::count(), 'order_tracking_tokens must be 0.');
        $this->assertSame(0, ProductionBatch::count(), 'production_batches must be 0.');
        $this->assertSame(0, BatchMaterial::count(), 'batch_materials must be 0.');
        $this->assertSame(0, ProductionResult::count(), 'production_results must be 0.');
        $this->assertSame(0, QcResult::count(), 'qc_results must be 0.');
        $this->assertSame(0, DB::table('batch_costs')->count(), 'batch_costs must be 0.');
        $this->assertSame(0, Shipment::count(), 'shipments must be 0.');

        // 6. Invoicing, Payments, Financials & Sequences: Strictly 0
        $this->assertSame(0, Invoice::count(), 'invoices must be 0.');
        $this->assertSame(0, Payment::count(), 'payments must be 0.');
        $this->assertSame(0, BusinessExpense::count(), 'business_expenses must be 0.');
        $this->assertSame(0, OrderFinancial::count(), 'order_financials must be 0.');
        $this->assertSame(0, DocumentSequence::count(), 'document_sequences must be 0.');

        // 7. Customer portal safety on clean baseline
        $response = $this->actingAs($customerUser)->get(route('customer.dashboard'));
        $response->assertOk();
        $response->assertSee('Belum Ada Pesanan');
        $response->assertDontSeeText('HPP');
        $response->assertDontSeeText('Laba Kotor');

        $orderResponse = $this->actingAs($customerUser)->get(route('customer.orders.index'));
        $orderResponse->assertOk();
        $orderResponse->assertSee('Tidak ada pesanan ditemukan');
    }
}
