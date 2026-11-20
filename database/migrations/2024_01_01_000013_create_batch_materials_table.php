<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('production_batches')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials');
            $table->decimal('required_qty', 18, 4)->default(0);
            $table->decimal('stock_at_calc', 18, 4)->default(0);
            $table->decimal('shortage_qty', 18, 4)->default(0);
            $table->decimal('allocated_qty', 18, 4)->default(0);
            $table->decimal('used_qty', 18, 4)->default(0);
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            // UNIQUE constraint: 1 material 1 kali per batch
            $table->unique(['batch_id', 'material_id']);
            $table->index('batch_id');
            $table->index('material_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_materials');
    }
};
