<?php

namespace Tests\Unit\Master;

use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\System\DocumentSequence;
use App\Services\Master\ProductVariantSkuGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OverflowException;
use Tests\TestCase;

class ProductVariantSkuGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private ProductVariantSkuGenerator $generator;
    private Product $voalProduct;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new ProductVariantSkuGenerator();
        $this->voalProduct = Product::create([
            'product_name' => 'Hijab Voal Polos Premium',
            'code'         => 'VP',
            'active'       => true,
        ]);
    }

    public function test_generates_semantic_sku_with_correct_structure(): void
    {
        $sku = $this->generator->generate($this->voalProduct, 'Dusty Pink');

        $this->assertEquals('HJB-VP-DP-001', $sku);
    }

    public function test_generates_sequential_skus_under_same_product(): void
    {
        $sku1 = $this->generator->generate($this->voalProduct, 'Dusty Pink');
        ProductVariant::create([
            'product_id'        => $this->voalProduct->id,
            'variant_name'      => 'Dusty Pink',
            'sku'               => $sku1,
            'requires_printing' => false,
            'active'            => true,
        ]);

        $sku2 = $this->generator->generate($this->voalProduct, 'Mocca');
        ProductVariant::create([
            'product_id'        => $this->voalProduct->id,
            'variant_name'      => 'Mocca',
            'sku'               => $sku2,
            'requires_printing' => false,
            'active'            => true,
        ]);

        $sku3 = $this->generator->generate($this->voalProduct, 'Cream');

        $this->assertEquals('HJB-VP-DP-001', $sku1);
        $this->assertEquals('HJB-VP-MC-002', $sku2);
        $this->assertEquals('HJB-VP-CR-003', $sku3);
    }

    public function test_sequence_resets_for_different_products(): void
    {
        // Voal product first variant
        $voalSku = $this->generator->generate($this->voalProduct, 'Dusty Pink');
        $this->assertEquals('HJB-VP-DP-001', $voalSku);

        // Different product
        $viscoseProduct = Product::create([
            'product_name' => 'Hijab Viscose',
            'code'         => 'VS',
            'active'       => true,
        ]);

        $viscoseSku = $this->generator->generate($viscoseProduct, 'Black');
        $this->assertEquals('HJB-VS-BL-001', $viscoseSku);
    }

    public function test_detects_existing_manual_variant_sequence_and_increments_correctly(): void
    {
        // Simulate existing manual record HJB-VP-DP-001
        ProductVariant::create([
            'product_id'        => $this->voalProduct->id,
            'variant_name'      => 'Dusty Pink',
            'sku'               => 'HJB-VP-DP-001',
            'requires_printing' => false,
            'active'            => true,
        ]);

        // Creating next variant (Mocca) should detect 001 and produce 002
        $nextSku = $this->generator->generate($this->voalProduct, 'Mocca');

        $this->assertEquals('HJB-VP-MC-002', $nextSku);
    }

    public function test_handles_variant_code_collision_within_same_product(): void
    {
        // First variant: Mocca -> MC
        $sku1 = $this->generator->generate($this->voalProduct, 'Mocca');
        ProductVariant::create([
            'product_id'        => $this->voalProduct->id,
            'variant_name'      => 'Mocca',
            'sku'               => $sku1,
            'requires_printing' => false,
            'active'            => true,
        ]);
        $this->assertEquals('HJB-VP-MC-001', $sku1);

        // Second variant: Macchiato (also would produce MC base)
        $sku2 = $this->generator->generate($this->voalProduct, 'Macchiato');

        // Variant code should be resolved without collision
        $this->assertNotEquals($sku1, $sku2);
        $this->assertStringStartsWith('HJB-VP-', $sku2);
        $this->assertStringEndsWith('-002', $sku2);
    }

    public function test_throws_overflow_exception_when_sequence_exceeds_max(): void
    {
        $period = sprintf('P%05d', $this->voalProduct->id % 100000);
        DocumentSequence::create([
            'prefix'        => ProductVariantSkuGenerator::SEQUENCE_PREFIX,
            'period'        => $period,
            'last_sequence' => ProductVariantSkuGenerator::MAX_SEQUENCE,
        ]);

        $this->expectException(OverflowException::class);
        $this->generator->generate($this->voalProduct, 'Black');
    }
}
