<?php

namespace Database\Seeders;

use App\Models\Master\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['unit_name' => 'Pieces',  'symbol' => 'pcs'],
            ['unit_name' => 'Kilogram', 'symbol' => 'kg'],
            ['unit_name' => 'Meter',   'symbol' => 'm'],
            ['unit_name' => 'Roll',    'symbol' => 'roll'],
            ['unit_name' => 'Lembar',  'symbol' => 'lbr'],
            ['unit_name' => 'Lusin',   'symbol' => 'lsn'],
            ['unit_name' => 'Gram',    'symbol' => 'gr'],
            ['unit_name' => 'Liter',   'symbol' => 'ltr'],
            ['unit_name' => 'Set',     'symbol' => 'set'],
        ];

        foreach ($units as $unit) {
            Unit::updateOrCreate(['symbol' => $unit['symbol']], $unit);
        }

        $this->command->info('✅ UnitSeeder: ' . count($units) . ' units seeded.');
    }
}
