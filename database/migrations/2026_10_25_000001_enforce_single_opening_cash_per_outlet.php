<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Enforces that each tenant outlet can have at most ONE opening cash balance record.
     * Legacy records with outlet_id = null are permitted to coexist (standard nullable UNIQUE semantics).
     */
    public function up(): void
    {
        if (Schema::hasTable('opening_cash_balances') && Schema::hasColumn('opening_cash_balances', 'outlet_id')) {
            Schema::table('opening_cash_balances', function (Blueprint $table) {
                $table->unique('outlet_id', 'uq_opening_cash_balances_outlet_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('opening_cash_balances') && Schema::hasColumn('opening_cash_balances', 'outlet_id')) {
            Schema::table('opening_cash_balances', function (Blueprint $table) {
                $table->dropUnique('uq_opening_cash_balances_outlet_id');
            });
        }
    }
};
