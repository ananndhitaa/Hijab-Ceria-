<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Harden business_incomes.income_no uniqueness to be tenant-scoped:
     * - Drops global UNIQUE(income_no).
     * - Adds stored generated column tenant_income_scope = COALESCE(outlet_id, 0).
     * - Creates UNIQUE(tenant_income_scope, income_no).
     * - Preserves legacy NULL uniqueness while permitting distinct tenants to maintain independent counters.
     */
    public function up(): void
    {
        if (Schema::hasTable('business_incomes') && Schema::hasColumn('business_incomes', 'outlet_id')) {
            Schema::table('business_incomes', function (Blueprint $table) {
                // Drop global unique index
                $table->dropUnique('business_incomes_income_no_unique');

                // Stored generated column for deterministic MariaDB & SQLite indexing
                $table->unsignedBigInteger('tenant_income_scope')
                    ->storedAs('COALESCE(outlet_id, 0)')
                    ->after('outlet_id');

                $table->unique(['tenant_income_scope', 'income_no'], 'business_incomes_tenant_income_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('business_incomes') && Schema::hasColumn('business_incomes', 'tenant_income_scope')) {
            Schema::table('business_incomes', function (Blueprint $table) {
                $table->dropUnique('business_incomes_tenant_income_unique');
                $table->dropColumn('tenant_income_scope');
                $table->unique('income_no', 'business_incomes_income_no_unique');
            });
        }
    }
};
