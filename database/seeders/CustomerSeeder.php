<?php

namespace Database\Seeders;

use App\Models\Master\Customer;
use App\Models\System\User;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $demoUser = User::where('email', 'customer@example.com')->first();

        // Ensure unique constraint safety if demo user exists
        if ($demoUser) {
            Customer::where('user_id', $demoUser->id)
                ->where('customer_name', '!=', 'Hijab Fatima Store')
                ->update(['user_id' => null]);
        }

        $customers = [
            [
                'customer_name' => 'Hijab Fatima Store',
                'phone'         => '0812-3456-7890',
                'address'       => 'Jl. Sudirman No. 12, Jakarta Pusat',
                'email'         => 'fatima@hijabstore.com',
                'user_id'       => $demoUser?->id,
            ],
            ['customer_name' => 'Toko Aisyah',          'phone' => '0813-2233-4455', 'address' => 'Jl. Pasar Baru No. 45, Bandung',    'email' => null],
            ['customer_name' => 'Hijab Store Jkt',      'phone' => '0856-8900-1234', 'address' => 'Jl. Kebon Jeruk No. 8, Jakarta Barat', 'email' => null],
            ['customer_name' => 'Butik Zahra',          'phone' => '0821-5678-9012', 'address' => 'Jl. Malioboro No. 99, Yogyakarta',  'email' => 'zahra.butik@gmail.com'],
            ['customer_name' => 'Kharisma Hijab',       'phone' => '0857-1122-3344', 'address' => 'Jl. Pemuda No. 67, Semarang',      'email' => null],
            ['customer_name' => 'Hijab Corner',         'phone' => '0895-2345-6789', 'address' => 'Jl. Ahmad Yani No. 23, Surabaya',  'email' => null],
            ['customer_name' => 'Nayyara Hijab',        'phone' => '0888-4455-6677', 'address' => 'Jl. Raya Bogor No. 156, Depok',    'email' => 'nayyara@gmail.com'],
            ['customer_name' => 'Zahira Boutique',      'phone' => '0811-9988-7766', 'address' => 'Jl. Dago No. 78, Bandung',         'email' => null],
            ['customer_name' => 'Toko Nisa',            'phone' => '0812-7788-9900', 'address' => 'Jl. Veteran No. 33, Malang',       'email' => null],
            ['customer_name' => 'Hijab Premium Medan',  'phone' => '0821-3344-5566', 'address' => 'Jl. Gatot Subroto No. 44, Medan',  'email' => 'hijab.premium@gmail.com'],
        ];

        foreach ($customers as $data) {
            Customer::updateOrCreate(
                ['customer_name' => $data['customer_name']],
                $data
            );
        }

        $this->command->info('✅ CustomerSeeder: ' . count($customers) . ' customers seeded.');
    }
}
