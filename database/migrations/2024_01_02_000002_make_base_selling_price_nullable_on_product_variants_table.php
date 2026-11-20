<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Corrective migration: Change base_selling_price on product_variants table to be NULLABLE.
     * NULL represents "master price not yet set by admin".
     */
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->decimal('base_selling_price', 18, 2)->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->decimal('base_selling_price', 18, 2)->default(0)->change();
        });
    }
};
