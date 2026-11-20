<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('product_variants')) {
            Schema::table('product_variants', function (Blueprint $table) {
                if (! Schema::hasColumn('product_variants', 'tenant_sku_scope')) {
                    $table->unsignedBigInteger('tenant_sku_scope')
                        ->storedAs('COALESCE(outlet_id, 0)')
                        ->after('outlet_id');
                }

                $table->dropUnique('product_variants_sku_unique');
                $table->unique(['tenant_sku_scope', 'sku'], 'product_variants_tenant_sku_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('product_variants')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->dropUnique('product_variants_tenant_sku_unique');

                if (Schema::hasColumn('product_variants', 'tenant_sku_scope')) {
                    $table->dropColumn('tenant_sku_scope');
                }

                $table->unique('sku', 'product_variants_sku_unique');
            });
        }
    }
};
