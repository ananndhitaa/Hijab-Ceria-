<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bom_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials');
            $table->decimal('qty_per_unit', 18, 4);
            $table->timestamps();

            // Satu material tidak boleh duplikat dalam BOM satu variant
            $table->unique(['variant_id', 'material_id']);
            $table->index('variant_id');
            $table->index('material_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bom_items');
    }
};
