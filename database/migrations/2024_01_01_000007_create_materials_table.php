<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units');
            $table->string('material_name');
            $table->decimal('min_stock', 18, 4)->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('unit_id');
            $table->index('active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
