<?php

use App\Services\Master\ProductCodeGenerator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('code', 20)->nullable()->after('product_name');
        });

        // Safe, targeted backfill for existing products
        $products = DB::table('products')->get();
        $generator = new ProductCodeGenerator();

        foreach ($products as $product) {
            $code = null;

            // Check if product already has variants with HJB-XX- SKU convention
            $variant = DB::table('product_variants')
                ->where('product_id', $product->id)
                ->whereNotNull('sku')
                ->first();

            if ($variant && preg_match('/^HJB-([A-Z0-9]+)-/i', $variant->sku, $matches)) {
                $code = strtoupper($matches[1]);
            }

            if (!$code) {
                $code = $generator->generate($product->product_name, $product->id);
            }

            DB::table('products')->where('id', $product->id)->update(['code' => $code]);
        }

        Schema::table('products', function (Blueprint $table) {
            $table->unique('code');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });
    }
};
