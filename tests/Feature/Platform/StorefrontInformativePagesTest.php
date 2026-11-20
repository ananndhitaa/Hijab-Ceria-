<?php

namespace Tests\Feature\Platform;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StorefrontInformativePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_footer_informative_pages_return_status_200(): void
    {
        $routes = [
            'storefront.about'        => 'Tentang NAYARA',
            'storefront.help'         => 'Pusat Bantuan',
            'storefront.how-to-order' => 'Cara Mudah Belanja',
            'storefront.returns'      => 'Kebijakan Garansi & Pengembalian Produk',
            'storefront.terms'        => 'Syarat & Ketentuan Layanan NAYARA',
            'storefront.privacy'      => 'Kebijakan Privasi & Perlindungan Data',
            'storefront.usage-policy' => 'Kebijakan Penggunaan Ekosistem NAYARA',
            'storefront.contact'      => 'Kami Siap Membantu Anda',
        ];

        foreach ($routes as $route => $expectedText) {
            $response = $this->get(route($route));
            $response->assertStatus(200);
            $response->assertSeeText($expectedText);
            // Verify footer links are present on each page
            $response->assertSee(route('storefront.about'));
            $response->assertSee(route('storefront.help'));
            $response->assertSee(route('storefront.how-to-order'));
            $response->assertSee(route('storefront.returns'));
            $response->assertSee(route('storefront.terms'));
            $response->assertSee(route('storefront.privacy'));
            $response->assertSee(route('storefront.usage-policy'));
            $response->assertSee(route('storefront.contact'));
        }
    }

    public function test_storefront_landing_footer_has_active_links_for_all_sections(): void
    {
        $response = $this->get(route('landing'));
        $response->assertStatus(200);

        // Jelajahi
        $response->assertSee(route('landing'));
        $response->assertSee(route('landing') . '#kategori');
        $response->assertSee(route('landing') . '#produk-unggulan');
        $response->assertSee(route('landing') . '#promo-iklan');

        // Bantuan
        $response->assertSee(route('storefront.help'));
        $response->assertSee(route('storefront.how-to-order'));
        $response->assertSee(route('storefront.returns'));
        $response->assertSee(route('storefront.terms'));

        // Tentang NAYARA
        $response->assertSee(route('storefront.about'));
        $response->assertSee(route('storefront.privacy'));
        $response->assertSee(route('storefront.usage-policy'));
        $response->assertSee(route('storefront.contact'));
    }
}

