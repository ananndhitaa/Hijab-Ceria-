<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🚀 Memulai proses seeding clean-slate baseline NAYARA ERP...');
        $this->command->newLine();

        $this->call([
            UserSeeder::class,
            UnitSeeder::class,
        ]);

        $this->command->newLine();
        $this->command->info('✅ Seeding clean-slate baseline selesai! Database siap digunakan.');
        $this->command->newLine();
        $this->command->table(
            ['Role', 'Email', 'Password', 'Catatan'],
            [
                ['Admin',    'admin@example.com',    'password', 'Bootstrap Employee'],
                ['Gudang',   'gudang@example.com',   'password', 'Bootstrap Employee'],
                ['Produksi', 'produksi@example.com', 'password', 'Bootstrap Employee'],
                ['Owner',    'owner@example.com',    'password', 'Bootstrap Employee'],
                ['Customer', 'customer@example.com', 'password', 'Bootstrap Login (Unlinked)'],
            ]
        );
    }
}
