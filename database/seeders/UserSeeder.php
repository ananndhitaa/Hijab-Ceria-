<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\System\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name'     => 'Administrator',
                'email'    => 'admin@example.com',
                'password' => Hash::make('password'),
                'role'     => UserRole::ADMIN->value,
                'status'   => UserStatus::ACTIVE->value,
            ],
            [
                'name'     => 'Staff Gudang',
                'email'    => 'gudang@example.com',
                'password' => Hash::make('password'),
                'role'     => UserRole::GUDANG->value,
                'status'   => UserStatus::ACTIVE->value,
            ],
            [
                'name'     => 'Staff Produksi',
                'email'    => 'produksi@example.com',
                'password' => Hash::make('password'),
                'role'     => UserRole::PRODUKSI->value,
                'status'   => UserStatus::ACTIVE->value,
            ],
            [
                'name'     => 'Owner Toko',
                'email'    => 'owner@example.com',
                'password' => Hash::make('password'),
                'role'     => UserRole::OWNER->value,
                'status'   => UserStatus::ACTIVE->value,
            ],
            [
                'name'     => 'Customer Demo',
                'email'    => 'customer@example.com',
                'password' => Hash::make('password'),
                'role'     => UserRole::CUSTOMER->value,
                'status'   => UserStatus::ACTIVE->value,
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }

        $this->command->info('✅ UserSeeder: 5 bootstrap role accounts seeded cleanly (unlinked customer).');
    }
}
