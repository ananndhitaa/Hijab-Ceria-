<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('production_batches')->cascadeOnDelete();
            $table->decimal('actual_qty', 18, 4);
            $table->date('result_date');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('batch_id');
            $table->index('result_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_results');
    }
};
