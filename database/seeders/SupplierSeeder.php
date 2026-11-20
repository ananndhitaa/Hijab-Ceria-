<?php

namespace Database\Seeders;

use App\Models\Master\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'supplier_name' => 'PT. Tekstil Nusantara',
                'phone'         => '021-5588-9900',
                'address'       => 'Jl. Industri Tekstil No. 1, Bandung',
                'email'         => 'order@tekstilnusantara.co.id',
                'active'        => true,
            ],
            [
                'supplier_name' => 'CV. Kain Indah Makmur',
                'phone'         => '024-7755-3311',
                'address'       => 'Jl. Pasar Kain No. 88, Semarang',
                'email'         => 'sales@kainindah.com',
                'active'        => true,
            ],
            [
                'supplier_name' => 'UD. Benang Mas',
                'phone'         => '031-8844-2200',
                'address'       => 'Jl. Gembong No. 45, Surabaya',
                'email'         => null,
                'active'        => true,
            ],
            [
                'supplier_name' => 'PT. Packaging Prima',
                'phone'         => '021-7722-6600',
                'address'       => 'Jl. Industri Kemasan No. 12, Tangerang',
                'email'         => 'info@packagingprima.com',
                'active'        => true,
            ],
            [
                'supplier_name' => 'Toko Label Kreasi',
                'phone'         => '0812-9988-7755',
                'address'       => 'Jl. Pasar Pagi Blok D No. 5, Jakarta Barat',
                'email'         => null,
                'active'        => true,
            ],
        ];

        foreach ($suppliers as $data) {
            Supplier::updateOrCreate(
                ['supplier_name' => $data['supplier_name']],
                $data
            );
        }

        $this->command->info('✅ SupplierSeeder: ' . count($suppliers) . ' suppliers seeded.');
    }
}
