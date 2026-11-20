<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add requires_printing to product_variants and production_batches tables.
     *
     * - product_variants.requires_printing: Master production route configuration (NULL = unconfigured, TRUE = with printing, FALSE = without printing).
     * - production_batches.requires_printing: Immutable transaction execution route snapshot copied upon batch creation.
     */
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->boolean('requires_printing')->nullable()->default(null)->after('base_selling_price');
        });

        Schema::table('production_batches', function (Blueprint $table) {
            $table->boolean('requires_printing')->nullable()->default(null)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('production_batches', function (Blueprint $table) {
            $table->dropColumn('requires_printing');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('requires_printing');
        });
    }
};
