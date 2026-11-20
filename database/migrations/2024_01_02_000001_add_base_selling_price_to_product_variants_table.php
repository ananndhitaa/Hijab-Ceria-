<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Post-audit corrective migration: Add base_selling_price to product_variants table.
     * This acts as the DEFAULT / MASTER SELLING PRICE.
     */
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->decimal('base_selling_price', 18, 2)->default(0)->after('sku');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('base_selling_price');
        });
    }
};
