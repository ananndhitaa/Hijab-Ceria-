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
        if (Schema::hasTable('products') && ! Schema::hasColumn('products', 'is_published')) {
            Schema::table('products', function (Blueprint $table) {
                $table->boolean('is_published')->default(false)->after('active');
                $table->text('public_description')->nullable()->after('is_published');
                $table->decimal('public_price', 18, 2)->nullable()->after('public_description');
                $table->string('featured_image', 255)->nullable()->after('public_price');
                $table->string('public_category', 100)->nullable()->after('featured_image');

                $table->index(['outlet_id', 'is_published'], 'idx_products_outlet_published');
                $table->index('is_published');
                $table->index('public_category');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'is_published')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex('idx_products_outlet_published');
                $table->dropIndex(['is_published']);
                $table->dropIndex(['public_category']);

                $table->dropColumn([
                    'is_published',
                    'public_description',
                    'public_price',
                    'featured_image',
                    'public_category',
                ]);
            });
        }
    }
};
