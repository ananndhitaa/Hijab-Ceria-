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
        Schema::create('bom_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_template_id')->constrained('bom_templates')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials')->restrictOnDelete();
            $table->decimal('qty_per_unit', 18, 4);
            $table->timestamps();

            $table->unique(['bom_template_id', 'material_id']);
            $table->index('material_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bom_template_items');
    }
};
