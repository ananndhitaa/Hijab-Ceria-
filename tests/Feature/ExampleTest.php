<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_application_renders_landing_page(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSeeText('NAYARA');
        $response->assertSeeText('Production & MRP');
        $response->assertSeeText('Sistem Produksi & Perencanaan Bahan');
        $response->assertSeeText('Pesanan, kebutuhan bahan, dan proses produksi dalam satu sistem.');
        $response->assertSeeText('Masuk Sistem');
        $response->assertSeeText('Lacak Pesanan');
    }
}
