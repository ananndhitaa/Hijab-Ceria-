<?php

namespace Tests\Unit\Master;

use App\Models\Master\Product;
use App\Services\Master\ProductCodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCodeGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private ProductCodeGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new ProductCodeGenerator();
    }

    public function test_derives_correct_code_for_multi_word_products_with_qualifier(): void
    {
        // "Hijab Voal Polos Premium" must yield "VP" (Voal + Premium), skipping descriptor "Polos"
        $this->assertEquals('VP', $this->generator->deriveBaseCode('Hijab Voal Polos Premium'));

        // Standard 2-word products: First letter + second letter
        $this->assertEquals('VM', $this->generator->deriveBaseCode('Hijab Voal Motif'));
        $this->assertEquals('BJ', $this->generator->deriveBaseCode('Hijab Bergo Jersey'));
        $this->assertEquals('PK', $this->generator->deriveBaseCode('Pashmina Kaos'));
    }

    public function test_derives_correct_code_for_single_word_products(): void
    {
        // "Hijab Viscose" -> "VS" (First letter 'V' + first consonant 'S')
        $this->assertEquals('VS', $this->generator->deriveBaseCode('Hijab Viscose'));

        // "Hijab Pashmina" -> "PS"
        $this->assertEquals('PS', $this->generator->deriveBaseCode('Hijab Pashmina'));

        // "Bella" -> "BL"
        $this->assertEquals('BL', $this->generator->deriveBaseCode('Bella'));
    }

    public function test_collision_resolution_produces_unique_codes(): void
    {
        // First product gets VP
        Product::create(['product_name' => 'Hijab Voal Polos Premium', 'code' => 'VP']);

        // Second product that would also yield VP base code should get a distinct code
        $code2 = $this->generator->generate('Hijab Voal Premium');
        $this->assertNotEquals('VP', $code2);
        $this->assertStringStartsWith('VP', $code2);
    }
}
