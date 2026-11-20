<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->string('batch_no')->unique();
            $table->decimal('target_qty', 18, 4);
            $table->string('status')->default('PENDING');
            $table->date('planned_start_date')->nullable();
            $table->date('planned_end_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('order_item_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_batches');
    }
};
