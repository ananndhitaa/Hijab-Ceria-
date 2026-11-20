<?php

use App\Models\Master\Customer;
use App\Models\System\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Guard for test environments where outlet_id: 1 is not yet created
        if (! \App\Models\System\Outlet::where('id', 1)->exists()) {
            return;
        }

        // 1. Fix typo in customer name if any
        Customer::where('id', 1)->update(['customer_name' => 'Toko Aisyah Hijab']);

        // 2. Toko Raisya Hijab (ID: 3, Customer SO-202611-0001)
        $raisyaUser = User::firstOrCreate(
            ['email' => 'customer@example.com'],
            [
                'name' => 'Raisya Putri (Toko Raisya Hijab)',
                'password' => Hash::make('password'),
                'role' => 'CUSTOMER',
                'status' => 'ACTIVE',
                'outlet_id' => 1,
            ]
        );
        $raisyaUser->update([
            'name' => 'Raisya Putri (Toko Raisya Hijab)',
            'role' => 'CUSTOMER',
            'status' => 'ACTIVE',
        ]);
        Customer::where('id', 3)->update([
            'user_id' => $raisyaUser->id,
            'email' => 'customer@example.com',
        ]);

        // Also create raisya@example.com
        User::firstOrCreate(
            ['email' => 'raisya@example.com'],
            [
                'name' => 'Raisya Putri (Toko Raisya Hijab)',
                'password' => Hash::make('password'),
                'role' => 'CUSTOMER',
                'status' => 'ACTIVE',
                'outlet_id' => 1,
            ]
        );

        // 3. Toko Aisyah Hijab (ID: 1, Customer SO-202609-0001)
        $aisyahUser = User::firstOrCreate(
            ['email' => 'aisyah@example.com'],
            [
                'name' => 'Aisyah Ramadhani (Toko Aisyah Hijab)',
                'password' => Hash::make('password'),
                'role' => 'CUSTOMER',
                'status' => 'ACTIVE',
                'outlet_id' => 1,
            ]
        );
        $aisyahUser->update([
            'name' => 'Aisyah Ramadhani (Toko Aisyah Hijab)',
            'role' => 'CUSTOMER',
            'status' => 'ACTIVE',
        ]);
        Customer::where('id', 1)->update([
            'user_id' => $aisyahUser->id,
            'email' => 'aisyah@example.com',
        ]);

        // 4. Toko Fatimah Hijab (ID: 2, Customer SO-202609-0002)
        $fatimahUser = User::firstOrCreate(
            ['email' => 'fatimah@example.com'],
            [
                'name' => 'Fatimah Zahra (Toko Fatimah Hijab)',
                'password' => Hash::make('password'),
                'role' => 'CUSTOMER',
                'status' => 'ACTIVE',
                'outlet_id' => 1,
            ]
        );
        $fatimahUser->update([
            'name' => 'Fatimah Zahra (Toko Fatimah Hijab)',
            'role' => 'CUSTOMER',
            'status' => 'ACTIVE',
        ]);
        Customer::where('id', 2)->update([
            'user_id' => $fatimahUser->id,
            'email' => 'fatimah@example.com',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Non-destructive rollback
    }
};
