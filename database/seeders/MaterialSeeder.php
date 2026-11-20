<?php

namespace Database\Seeders;

use App\Models\Inventory\MaterialStock;
use App\Models\Master\Material;
use App\Models\Master\Unit;
use Illuminate\Database\Seeder;

class MaterialSeeder extends Seeder
{
    public function run(): void
    {
        $pcsId  = Unit::where('symbol', 'pcs')->value('id');
        $kgId   = Unit::where('symbol', 'kg')->value('id');
        $mId    = Unit::where('symbol', 'm')->value('id');
        $rollId = Unit::where('symbol', 'roll')->value('id');
        $lbrId  = Unit::where('symbol', 'lbr')->value('id');

        $materials = [
            // Kain/Bahan utama
            [
                'unit_id'       => $mId,
                'material_name' => 'Kain Voal Premium',
                'min_stock'     => 50,
                'active'        => true,
                'initial_stock' => 200,
                'initial_cost'  => 35000,
            ],
            [
                'unit_id'       => $mId,
                'material_name' => 'Kain Voal Motif',
                'min_stock'     => 30,
                'active'        => true,
                'initial_stock' => 120,
                'initial_cost'  => 38000,
            ],
            [
                'unit_id'       => $mId,
                'material_name' => 'Kain Pashmina',
                'min_stock'     => 30,
                'active'        => true,
                'initial_stock' => 80,
                'initial_cost'  => 32000,
            ],
            [
                'unit_id'       => $mId,
                'material_name' => 'Kain Jersey',
                'min_stock'     => 20,
                'active'        => true,
                'initial_stock' => 60,
                'initial_cost'  => 28000,
            ],
            // Bahan pendukung
            [
                'unit_id'       => $rollId,
                'material_name' => 'Benang Jahit',
                'min_stock'     => 10,
                'active'        => true,
                'initial_stock' => 50,
                'initial_cost'  => 15000,
            ],
            [
                'unit_id'       => $pcsId,
                'material_name' => 'Label Brand Hijab',
                'min_stock'     => 500,
                'active'        => true,
                'initial_stock' => 3000,
                'initial_cost'  => 500,
            ],
            [
                'unit_id'       => $pcsId,
                'material_name' => 'Polybag 25x35',
                'min_stock'     => 500,
                'active'        => true,
                'initial_stock' => 2000,
                'initial_cost'  => 300,
            ],
            [
                'unit_id'       => $pcsId,
                'material_name' => 'Hangtag',
                'min_stock'     => 300,
                'active'        => true,
                'initial_stock' => 1500,
                'initial_cost'  => 800,
            ],
            [
                'unit_id'       => $pcsId,
                'material_name' => 'Stiker Barcode',
                'min_stock'     => 500,
                'active'        => true,
                'initial_stock' => 3000,
                'initial_cost'  => 200,
            ],
            [
                'unit_id'       => $lbrId,
                'material_name' => 'Kertas Tisu Bungkus',
                'min_stock'     => 200,
                'active'        => true,
                'initial_stock' => 1000,
                'initial_cost'  => 500,
            ],
        ];

        foreach ($materials as $data) {
            $initialStock = $data['initial_stock'];
            $initialCost  = $data['initial_cost'];
            unset($data['initial_stock'], $data['initial_cost']);

            $material = Material::updateOrCreate(
                ['material_name' => $data['material_name']],
                $data
            );

            // Buat / update stock record
            MaterialStock::updateOrCreate(
                ['material_id' => $material->id],
                [
                    'qty_on_hand'   => $initialStock,
                    'avg_unit_cost' => $initialCost,
                ]
            );
        }

        $this->command->info('✅ MaterialSeeder: ' . count($materials) . ' materials seeded with initial stock.');
    }
}
