<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('payments')
            ->whereIn('payment_method', [
                'Transfer BCA',
                'Transfer Mandiri',
                'Transfer BNI',
                'Transfer BRI',
                'Transfer',
                'TRANSFER_BCA',
                'TRANSFER',
            ])
            ->update(['payment_method' => 'Transfer Bank']);

        DB::table('payments')
            ->whereIn('payment_method', [
                'Cash',
                'CASH',
            ])
            ->update(['payment_method' => 'Tunai']);
    }

    public function down(): void
    {
        // Irreversible cosmetic normalization
    }
};
