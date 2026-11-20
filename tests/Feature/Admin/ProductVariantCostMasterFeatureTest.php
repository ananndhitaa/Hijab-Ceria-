<?php

namespace Tests\Feature\Admin;

use App\Enums\BatchStatus;
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
use Tests\TestCase;

class ProductVariantCostMasterFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $owner;

    private User $produksi;

    private User $gudang;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
        ]);
        $this->owner = User::factory()->create([
            'role' => UserRole::OWNER,
            'status' => UserStatus::ACTIVE,
        ]);
        $this->produksi = User::factory()->create([
            'role' => UserRole::PRODUKSI,
            'status' => UserStatus::ACTIVE,
        ]);
        $this->gudang = User::factory()->create([
            'role' => UserRole::GUDANG,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->product = Product::create([
            'product_name' => 'Hijab Master Test',
            'active' => true,
        ]);
    }

    public function test_admin_can_create_variant_with_complete_cost_rates(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.products.variants.store', $this->product), [
            'variant_name' => 'Emerald Motif',
            'requires_printing' => '1',
            'printing_cost_per_unit' => '2500.00',
            'direct_labor_cost_per_unit' => '5000.00',
            'packaging_process_cost_per_good_unit' => '1000.00',
            'other_direct_cost_per_unit' => '500.00',
            'active' => '1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $this->product->id,
            'variant_name' => 'Emerald Motif',
            'requires_printing' => 1,
            'printing_cost_per_unit' => '2500.00',
            'direct_labor_cost_per_unit' => '5000.00',
            'packaging_process_cost_per_good_unit' => '1000.00',
            'other_direct_cost_per_unit' => '500.00',
        ]);
        $variant = ProductVariant::where('variant_name', 'Emerald Motif')->first();
        $this->assertNotNull($variant);
        $this->assertStringStartsWith('HJB-', $variant->sku);
    }

    public function test_admin_can_update_variant_cost_rates(): void
    {
        $variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'variant_name' => 'Polos Hitam',
            'sku' => 'HJB-MST-BLK',
            'requires_printing' => false,
            'printing_cost_per_unit' => '0.00',
            'direct_labor_cost_per_unit' => '4000.00',
            'packaging_process_cost_per_good_unit' => '800.00',
            'other_direct_cost_per_unit' => '200.00',
            'active' => true,
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.variants.update', $variant), [
            'variant_name' => 'Polos Hitam Updated',
            'sku' => 'HJB-MST-BLK',
            'requires_printing' => '0',
            'direct_labor_cost_per_unit' => '4500.00',
            'packaging_process_cost_per_good_unit' => '900.00',
            'other_direct_cost_per_unit' => '250.00',
            'active' => '1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'direct_labor_cost_per_unit' => '4500.00',
            'packaging_process_cost_per_good_unit' => '900.00',
            'other_direct_cost_per_unit' => '250.00',
        ]);
    }

    public function test_variant_can_be_created_without_any_production_tariffs(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.products.variants.store', $this->product), [
            'variant_name' => 'Dusty Pink',
            'requires_printing' => '0',
            'active' => '1',
        ]);

        $response->assertRedirect();
        $variant = ProductVariant::where('variant_name', 'Dusty Pink')->first();
        $this->assertNotNull($variant);
        $this->assertEquals('Dusty Pink', $variant->variant_name);
        $this->assertFalse($variant->requires_printing);
        $this->assertEquals('0.00', $variant->printing_cost_per_unit);
        $this->assertNull($variant->direct_labor_cost_per_unit);
        $this->assertNull($variant->packaging_process_cost_per_good_unit);
        $this->assertNull($variant->other_direct_cost_per_unit);
        $this->assertNull($variant->base_selling_price);
        $this->assertTrue($variant->active);
    }

    public function test_variant_without_printing_normalizes_print_cost_to_zero(): void
    {
        // Even if print cost is omitted or provided as empty string
        $response = $this->actingAs($this->admin)->post(route('admin.products.variants.store', $this->product), [
            'variant_name' => 'Hitam Polos',
            'requires_printing' => '0',
            'printing_cost_per_unit' => '',
            'active' => '1',
        ]);

        $response->assertRedirect();
        $variant = ProductVariant::where('variant_name', 'Hitam Polos')->first();
        $this->assertNotNull($variant);
        $this->assertFalse($variant->requires_printing);
        $this->assertEquals('0.00', $variant->printing_cost_per_unit);
    }

    public function test_variant_with_printing_and_empty_print_cost_stores_as_null(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.products.variants.store', $this->product), [
            'variant_name' => 'Motif Voal Belum Tahu Biaya',
            'requires_printing' => '1',
            'printing_cost_per_unit' => '',
            'active' => '1',
        ]);

        $response->assertRedirect();
        $variant = ProductVariant::where('variant_name', 'Motif Voal Belum Tahu Biaya')->first();
        $this->assertNotNull($variant);
        $this->assertTrue($variant->requires_printing);
        $this->assertNull($variant->printing_cost_per_unit);
    }

    public function test_variant_with_printing_and_valid_rate_stores_correctly(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.products.variants.store', $this->product), [
            'variant_name' => 'Motif Voal Printing Jelas',
            'requires_printing' => '1',
            'printing_cost_per_unit' => '3500.00',
            'active' => '1',
        ]);

        $response->assertRedirect();
        $variant = ProductVariant::where('variant_name', 'Motif Voal Printing Jelas')->first();
        $this->assertNotNull($variant);
        $this->assertTrue($variant->requires_printing);
        $this->assertEquals('3500.00', $variant->printing_cost_per_unit);
    }

    public function test_labor_rate_empty_is_accepted_as_null(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.products.variants.store', $this->product), [
            'variant_name' => 'Varian No Labor',
            'requires_printing' => '0',
            'direct_labor_cost_per_unit' => '',
        ]);

        $response->assertRedirect();
        $variant = ProductVariant::where('variant_name', 'Varian No Labor')->first();
        $this->assertNotNull($variant);
        $this->assertNull($variant->direct_labor_cost_per_unit);
    }

    public function test_packaging_rate_empty_is_accepted_as_null(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.products.variants.store', $this->product), [
            'variant_name' => 'Varian No Pack',
            'requires_printing' => '0',
            'packaging_process_cost_per_good_unit' => '',
        ]);

        $response->assertRedirect();
        $variant = ProductVariant::where('variant_name', 'Varian No Pack')->first();
        $this->assertNotNull($variant);
        $this->assertNull($variant->packaging_process_cost_per_good_unit);
    }

    public function test_additional_production_cost_empty_is_accepted_as_null(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.products.variants.store', $this->product), [
            'variant_name' => 'Varian No Other',
            'requires_printing' => '0',
            'other_direct_cost_per_unit' => '',
        ]);

        $response->assertRedirect();
        $variant = ProductVariant::where('variant_name', 'Varian No Other')->first();
        $this->assertNotNull($variant);
        $this->assertNull($variant->other_direct_cost_per_unit);
    }

    public function test_negative_production_rates_are_rejected(): void
    {
        $cases = [
            ['printing_cost_per_unit' => '-1000.00', 'requires_printing' => '1', 'field' => 'printing_cost_per_unit'],
            ['direct_labor_cost_per_unit' => '-500.00', 'requires_printing' => '0', 'field' => 'direct_labor_cost_per_unit'],
            ['packaging_process_cost_per_good_unit' => '-200.00', 'requires_printing' => '0', 'field' => 'packaging_process_cost_per_good_unit'],
            ['other_direct_cost_per_unit' => '-100.00', 'requires_printing' => '0', 'field' => 'other_direct_cost_per_unit'],
        ];

        foreach ($cases as $index => $case) {
            $payload = array_merge([
                'variant_name' => "Negative Rate Variant {$index}",
                'sku' => "HJB-NEG-{$index}",
                'requires_printing' => $case['requires_printing'],
            ], [$case['field'] => $case[$case['field']]]);

            $response = $this->actingAs($this->admin)->post(
                route('admin.products.variants.store', $this->product),
                $payload
            );

            $response->assertSessionHasErrors($case['field']);
        }
    }

    public function test_update_variant_can_clear_optional_rates_to_null(): void
    {
        $variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'variant_name' => 'To Clear',
            'sku' => 'HJB-CLR-001',
            'requires_printing' => true,
            'printing_cost_per_unit' => '2000.00',
            'direct_labor_cost_per_unit' => '5000.00',
            'packaging_process_cost_per_good_unit' => '1000.00',
            'other_direct_cost_per_unit' => '500.00',
            'active' => true,
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.variants.update', $variant), [
            'variant_name' => 'Cleared Rates',
            'sku' => 'HJB-CLR-001',
            'requires_printing' => '1',
            'printing_cost_per_unit' => '',
            'direct_labor_cost_per_unit' => '',
            'packaging_process_cost_per_good_unit' => '',
            'other_direct_cost_per_unit' => '',
            'active' => '1',
        ]);

        $response->assertRedirect();
        $variant->refresh();
        $this->assertNull($variant->printing_cost_per_unit);
        $this->assertNull($variant->direct_labor_cost_per_unit);
        $this->assertNull($variant->packaging_process_cost_per_good_unit);
        $this->assertNull($variant->other_direct_cost_per_unit);
    }

    public function test_changing_printing_from_yes_to_no_resets_print_rate_to_zero(): void
    {
        $variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'variant_name' => 'Was Printing',
            'sku' => 'HJB-WPR-001',
            'requires_printing' => true,
            'printing_cost_per_unit' => '3000.00',
            'direct_labor_cost_per_unit' => '5000.00',
            'packaging_process_cost_per_good_unit' => '1000.00',
            'other_direct_cost_per_unit' => '500.00',
            'active' => true,
        ]);

        // When updated to requires_printing = 0, even if someone submits the old rate
        $response = $this->actingAs($this->admin)->put(route('admin.variants.update', $variant), [
            'variant_name' => 'Now Non Printing',
            'sku' => 'HJB-WPR-001',
            'requires_printing' => '0',
            'printing_cost_per_unit' => '3000.00', // Stale or leftover value
            'direct_labor_cost_per_unit' => '5000.00',
            'packaging_process_cost_per_good_unit' => '1000.00',
            'other_direct_cost_per_unit' => '500.00',
            'active' => '1',
        ]);

        $response->assertRedirect();
        $variant->refresh();
        $this->assertFalse($variant->requires_printing);
        $this->assertEquals('0.00', $variant->printing_cost_per_unit);
    }

    public function test_unauthorized_roles_cannot_modify_variant_cost_rates(): void
    {
        $variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'variant_name' => 'Varian Guard',
            'sku' => 'HJB-GRD-001',
            'requires_printing' => false,
            'direct_labor_cost_per_unit' => '4000.00',
            'packaging_process_cost_per_good_unit' => '800.00',
            'other_direct_cost_per_unit' => '200.00',
            'active' => true,
        ]);

        $payload = [
            'variant_name' => 'Hacked Variant',
            'sku' => 'HJB-GRD-001',
            'requires_printing' => '0',
            'direct_labor_cost_per_unit' => '1.00',
            'packaging_process_cost_per_good_unit' => '1.00',
            'other_direct_cost_per_unit' => '1.00',
            'active' => '1',
        ];

        $this->actingAs($this->owner)->put(route('admin.variants.update', $variant), $payload)->assertForbidden();
        $this->actingAs($this->produksi)->put(route('admin.variants.update', $variant), $payload)->assertForbidden();
        $this->actingAs($this->gudang)->put(route('admin.variants.update', $variant), $payload)->assertForbidden();
    }

    public function test_admin_batch_detail_shows_snapshot_cost_rates_without_leaking_owner_profit_metrics(): void
    {
        $cust = Customer::create(['customer_name' => 'Cust Batch View', 'phone' => '08123456789']);
        $order = Order::create([
            'customer_id' => $cust->id,
            'order_no' => 'ORD-BV-01',
            'order_date' => now()->toDateString(),
            'status' => 'DEAL',
            'total_qty' => 50,
            'total_amount' => 2500000,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'variant_name' => 'Varian BV',
            'sku' => 'HJB-BV-001',
            'requires_printing' => true,
            'printing_cost_per_unit' => '2000.00',
            'direct_labor_cost_per_unit' => '5000.00',
            'packaging_process_cost_per_good_unit' => '1000.00',
            'other_direct_cost_per_unit' => '500.00',
            'active' => true,
        ]);
        $item = OrderItem::create([
            'order_id' => $order->id,
            'variant_id' => $variant->id,
            'qty_ordered' => 50,
            'unit_selling_price' => 50000,
            'subtotal' => 2500000,
        ]);

        $batch = ProductionBatch::create([
            'order_item_id' => $item->id,
            'batch_no' => 'BAT-BV-01',
            'target_qty' => 50,
            'status' => BatchStatus::PRODUKSI,
            'requires_printing' => true,
            'printing_cost_per_unit_snapshot' => '2000.00',
            'direct_labor_cost_per_unit_snapshot' => '5000.00',
            'packaging_process_cost_per_good_unit_snapshot' => '1000.00',
            'other_direct_cost_per_unit_snapshot' => '500.00',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.batches.show', $batch));

        $response->assertOk();
        $response->assertSee('Perhitungan HPP Otomatis');
        $response->assertSee('SNAPSHOT TARIF MASTER');
        $response->assertSee('2.000');
        $response->assertSee('5.000');
        $response->assertSee('1.000');
        $response->assertSee('500');

        // Security boundary: Admin MUST NOT see Owner profit / financial metrics
        $response->assertDontSee('Gross Profit');
        $response->assertDontSee('Laba Kotor');
        $response->assertDontSee('Net Profit');
        $response->assertDontSee('Laba Bersih');
        $response->assertDontSee('EBITDA');
        $response->assertDontSee('ROI');
        $response->assertDontSee('MER');
    }
}
