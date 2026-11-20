<?php

namespace Database\Seeders;

use App\Models\Master\BomItem;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // ── Products ──────────────────────────────────────────────────

        $products = [
            [
                'product_name' => 'Hijab Voal Polos Premium',
                'description'  => 'Hijab segiempat berbahan voal premium, lembut dan nyaman dipakai.',
                'active'       => true,
            ],
            [
                'product_name' => 'Hijab Voal Motif',
                'description'  => 'Hijab segiempat voal dengan motif printing eksklusif.',
                'active'       => true,
            ],
            [
                'product_name' => 'Hijab Pashmina',
                'description'  => 'Pashmina lembut berbahan campuran katun dan sifon.',
                'active'       => true,
            ],
            [
                'product_name' => 'Hijab Bergo Jersey',
                'description'  => 'Bergo instan berbahan jersey stretch, cocok untuk aktivitas sehari-hari.',
                'active'       => true,
            ],
            [
                'product_name' => 'Pashmina Kaos',
                'description'  => 'Pashmina berbahan kaos premium yang adem, lentur, dan nyaman dipakai sehari-hari.',
                'active'       => true,
            ],
        ];

        foreach ($products as $productData) {
            Product::updateOrCreate(
                ['product_name' => $productData['product_name']],
                $productData
            );
        }

        // ── Variants ──────────────────────────────────────────────────

        $voalPolos    = Product::where('product_name', 'Hijab Voal Polos Premium')->first();
        $voalMotif    = Product::where('product_name', 'Hijab Voal Motif')->first();
        $pashmina     = Product::where('product_name', 'Hijab Pashmina')->first();
        $bergoJersey  = Product::where('product_name', 'Hijab Bergo Jersey')->first();
        $pashminaKaos = Product::where('product_name', 'Pashmina Kaos')->first();

        $variants = [
            // Voal Polos (Non-printing)
            [
                'product_id'                           => $voalPolos->id,
                'variant_name'                         => 'Dusty Pink',
                'sku'                                  => 'HJB-VP-001',
                'base_selling_price'                   => 85000,
                'requires_printing'                    => false,
                'printing_cost_per_unit'               => 0,
                'direct_labor_cost_per_unit'           => 5000,
                'packaging_process_cost_per_good_unit' => 1000,
                'other_direct_cost_per_unit'           => 500,
                'active'                               => true,
            ],
            [
                'product_id'                           => $voalPolos->id,
                'variant_name'                         => 'Olive Green',
                'sku'                                  => 'HJB-VP-002',
                'base_selling_price'                   => 85000,
                'requires_printing'                    => false,
                'printing_cost_per_unit'               => 0,
                'direct_labor_cost_per_unit'           => 5000,
                'packaging_process_cost_per_good_unit' => 1000,
                'other_direct_cost_per_unit'           => 500,
                'active'                               => true,
            ],
            [
                'product_id'                           => $voalPolos->id,
                'variant_name'                         => 'Navy Blue',
                'sku'                                  => 'HJB-VP-003',
                'base_selling_price'                   => 85000,
                'requires_printing'                    => false,
                'printing_cost_per_unit'               => 0,
                'direct_labor_cost_per_unit'           => 5000,
                'packaging_process_cost_per_good_unit' => 1000,
                'other_direct_cost_per_unit'           => 500,
                'active'                               => true,
            ],
            [
                'product_id'                           => $voalPolos->id,
                'variant_name'                         => 'Cream',
                'sku'                                  => 'HJB-VP-004',
                'base_selling_price'                   => 85000,
                'requires_printing'                    => false,
                'printing_cost_per_unit'               => 0,
                'direct_labor_cost_per_unit'           => 5000,
                'packaging_process_cost_per_good_unit' => 1000,
                'other_direct_cost_per_unit'           => 500,
                'active'                               => true,
            ],
            // Voal Motif (With Printing)
            [
                'product_id'                           => $voalMotif->id,
                'variant_name'                         => 'Motif Bunga',
                'sku'                                  => 'HJB-VM-001',
                'base_selling_price'                   => 95000,
                'requires_printing'                    => true,
                'printing_cost_per_unit'               => 3500,
                'direct_labor_cost_per_unit'           => 5000,
                'packaging_process_cost_per_good_unit' => 1000,
                'other_direct_cost_per_unit'           => 500,
                'active'                               => true,
            ],
            [
                'product_id'                           => $voalMotif->id,
                'variant_name'                         => 'Motif Geometri',
                'sku'                                  => 'HJB-VM-002',
                'base_selling_price'                   => 95000,
                'requires_printing'                    => true,
                'printing_cost_per_unit'               => 3500,
                'direct_labor_cost_per_unit'           => 5000,
                'packaging_process_cost_per_good_unit' => 1000,
                'other_direct_cost_per_unit'           => 500,
                'active'                               => true,
            ],
            // Pashmina (Non-printing)
            [
                'product_id'                           => $pashmina->id,
                'variant_name'                         => 'Mauve',
                'sku'                                  => 'HJB-PS-001',
                'base_selling_price'                   => 75000,
                'requires_printing'                    => false,
                'printing_cost_per_unit'               => 0,
                'direct_labor_cost_per_unit'           => 4500,
                'packaging_process_cost_per_good_unit' => 1000,
                'other_direct_cost_per_unit'           => 500,
                'active'                               => true,
            ],
            [
                'product_id'                           => $pashmina->id,
                'variant_name'                         => 'Sage Green',
                'sku'                                  => 'HJB-PS-002',
                'base_selling_price'                   => 75000,
                'requires_printing'                    => false,
                'printing_cost_per_unit'               => 0,
                'direct_labor_cost_per_unit'           => 4500,
                'packaging_process_cost_per_good_unit' => 1000,
                'other_direct_cost_per_unit'           => 500,
                'active'                               => true,
            ],
            // Bergo Jersey (Non-printing)
            [
                'product_id'                           => $bergoJersey->id,
                'variant_name'                         => 'Hitam',
                'sku'                                  => 'HJB-BJ-001',
                'base_selling_price'                   => 65000,
                'requires_printing'                    => false,
                'printing_cost_per_unit'               => 0,
                'direct_labor_cost_per_unit'           => 4000,
                'packaging_process_cost_per_good_unit' => 1000,
                'other_direct_cost_per_unit'           => 500,
                'active'                               => true,
            ],
            [
                'product_id'                           => $bergoJersey->id,
                'variant_name'                         => 'Abu-abu',
                'sku'                                  => 'HJB-BJ-002',
                'base_selling_price'                   => 65000,
                'requires_printing'                    => false,
                'printing_cost_per_unit'               => 0,
                'direct_labor_cost_per_unit'           => 4000,
                'packaging_process_cost_per_good_unit' => 1000,
                'other_direct_cost_per_unit'           => 500,
                'active'                               => true,
            ],
            // Pashmina Kaos (Non-printing)
            [
                'product_id'                           => $pashminaKaos->id,
                'variant_name'                         => 'Hitam',
                'sku'                                  => 'PSH-KS-001',
                'base_selling_price'                   => 50000,
                'requires_printing'                    => false,
                'printing_cost_per_unit'               => 0,
                'direct_labor_cost_per_unit'           => 0,
                'packaging_process_cost_per_good_unit' => 0,
                'other_direct_cost_per_unit'           => 0,
                'active'                               => true,
            ],
            [
                'product_id'                           => $pashminaKaos->id,
                'variant_name'                         => 'Mocca',
                'sku'                                  => 'PSH-KS-002',
                'base_selling_price'                   => 50000,
                'requires_printing'                    => false,
                'printing_cost_per_unit'               => 0,
                'direct_labor_cost_per_unit'           => 0,
                'packaging_process_cost_per_good_unit' => 0,
                'other_direct_cost_per_unit'           => 0,
                'active'                               => true,
            ],
            [
                'product_id'                           => $pashminaKaos->id,
                'variant_name'                         => 'Cream',
                'sku'                                  => 'PSH-KS-003',
                'base_selling_price'                   => 50000,
                'requires_printing'                    => false,
                'printing_cost_per_unit'               => 0,
                'direct_labor_cost_per_unit'           => 0,
                'packaging_process_cost_per_good_unit' => 0,
                'other_direct_cost_per_unit'           => 0,
                'active'                               => true,
            ],
        ];

        foreach ($variants as $variantData) {
            ProductVariant::updateOrCreate(
                ['sku' => $variantData['sku']],
                $variantData
            );
        }

        // ── BOM Items ─────────────────────────────────────────────────
        // CATATAN BUKAN BUSINESS RULE FINAL:
        // Nilai BOM pada seeder merupakan data development contoh, bukan formula produksi final perusahaan.
        // Formula produksi sesungguhnya ditentukan oleh user admin via menu BOM per variant.

        $kainVoal    = Material::where('material_name', 'Kain Voal Premium')->first();
        $kainMotif   = Material::where('material_name', 'Kain Voal Motif')->first();
        $kainPashmina = Material::where('material_name', 'Kain Pashmina')->first();
        $kainJersey  = Material::where('material_name', 'Kain Jersey')->first();
        $benang      = Material::where('material_name', 'Benang Jahit')->first();
        $label       = Material::where('material_name', 'Label Brand Hijab')->first();
        $polybag     = Material::where('material_name', 'Polybag 25x35')->first();
        $hangtag     = Material::where('material_name', 'Hangtag')->first();
        $stiker      = Material::where('material_name', 'Stiker Barcode')->first();

        // BOM untuk Voal Polos variants (1 pcs)
        $voalPolosVariants = ProductVariant::where('product_id', $voalPolos->id)->get();
        foreach ($voalPolosVariants as $variant) {
            $bomItems = [
                ['variant_id' => $variant->id, 'material_id' => $kainVoal->id,  'qty_per_unit' => 1.29],
                ['variant_id' => $variant->id, 'material_id' => $benang->id,    'qty_per_unit' => 0.02],
                ['variant_id' => $variant->id, 'material_id' => $label->id,     'qty_per_unit' => 1.00],
                ['variant_id' => $variant->id, 'material_id' => $polybag->id,   'qty_per_unit' => 1.00],
                ['variant_id' => $variant->id, 'material_id' => $hangtag->id,   'qty_per_unit' => 1.00],
                ['variant_id' => $variant->id, 'material_id' => $stiker->id,    'qty_per_unit' => 1.00],
            ];
            foreach ($bomItems as $item) {
                BomItem::updateOrCreate(
                    ['variant_id' => $item['variant_id'], 'material_id' => $item['material_id']],
                    $item
                );
            }
        }

        // BOM untuk Voal Motif variants (kain motif + printing)
        $voalMotifVariants = ProductVariant::where('product_id', $voalMotif->id)->get();
        foreach ($voalMotifVariants as $variant) {
            $bomItems = [
                ['variant_id' => $variant->id, 'material_id' => $kainMotif->id, 'qty_per_unit' => 1.29],
                ['variant_id' => $variant->id, 'material_id' => $benang->id,    'qty_per_unit' => 0.02],
                ['variant_id' => $variant->id, 'material_id' => $label->id,     'qty_per_unit' => 1.00],
                ['variant_id' => $variant->id, 'material_id' => $polybag->id,   'qty_per_unit' => 1.00],
                ['variant_id' => $variant->id, 'material_id' => $hangtag->id,   'qty_per_unit' => 1.00],
                ['variant_id' => $variant->id, 'material_id' => $stiker->id,    'qty_per_unit' => 1.00],
            ];
            foreach ($bomItems as $item) {
                BomItem::updateOrCreate(
                    ['variant_id' => $item['variant_id'], 'material_id' => $item['material_id']],
                    $item
                );
            }
        }

        // BOM untuk Pashmina
        $pashminaVariants = ProductVariant::where('product_id', $pashmina->id)->get();
        foreach ($pashminaVariants as $variant) {
            $bomItems = [
                ['variant_id' => $variant->id, 'material_id' => $kainPashmina->id, 'qty_per_unit' => 1.80],
                ['variant_id' => $variant->id, 'material_id' => $benang->id,       'qty_per_unit' => 0.03],
                ['variant_id' => $variant->id, 'material_id' => $label->id,        'qty_per_unit' => 1.00],
                ['variant_id' => $variant->id, 'material_id' => $polybag->id,      'qty_per_unit' => 1.00],
                ['variant_id' => $variant->id, 'material_id' => $hangtag->id,      'qty_per_unit' => 1.00],
                ['variant_id' => $variant->id, 'material_id' => $stiker->id,       'qty_per_unit' => 1.00],
            ];
            foreach ($bomItems as $item) {
                BomItem::updateOrCreate(
                    ['variant_id' => $item['variant_id'], 'material_id' => $item['material_id']],
                    $item
                );
            }
        }

        // BOM untuk Bergo Jersey
        $bergoVariants = ProductVariant::where('product_id', $bergoJersey->id)->get();
        foreach ($bergoVariants as $variant) {
            $bomItems = [
                ['variant_id' => $variant->id, 'material_id' => $kainJersey->id, 'qty_per_unit' => 0.75],
                ['variant_id' => $variant->id, 'material_id' => $benang->id,     'qty_per_unit' => 0.04],
                ['variant_id' => $variant->id, 'material_id' => $label->id,      'qty_per_unit' => 1.00],
                ['variant_id' => $variant->id, 'material_id' => $polybag->id,    'qty_per_unit' => 1.00],
                ['variant_id' => $variant->id, 'material_id' => $hangtag->id,    'qty_per_unit' => 1.00],
                ['variant_id' => $variant->id, 'material_id' => $stiker->id,     'qty_per_unit' => 1.00],
            ];
            foreach ($bomItems as $item) {
                BomItem::updateOrCreate(
                    ['variant_id' => $item['variant_id'], 'material_id' => $item['material_id']],
                    $item
                );
            }
        }

        $this->command->info('✅ ProductSeeder: products, variants, and BOM items seeded.');
    }
}
