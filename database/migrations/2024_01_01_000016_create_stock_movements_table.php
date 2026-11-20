<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materials');
            $table->string('movement_type'); // IN, OUT, ADJUSTMENT
            $table->decimal('qty', 18, 4);
            $table->decimal('unit_cost_snapshot', 18, 4)->default(0);
            $table->decimal('total_cost_snapshot', 18, 2)->default(0);
            $table->decimal('qty_before', 18, 4)->default(0);
            $table->decimal('qty_after', 18, 4)->default(0);
            $table->string('reference_type')->nullable(); // e.g. 'purchase_items', 'batch_materials'
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->date('movement_date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('material_id');
            $table->index('movement_type');
            $table->index('movement_date');
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
