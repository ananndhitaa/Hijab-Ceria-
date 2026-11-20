<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('production_batches')->cascadeOnDelete();
            $table->string('cost_type'); // MATERIAL, PRINTING, DIRECT_LABOR, PACKAGING, OTHER_DIRECT
            $table->text('description')->nullable();
            $table->decimal('amount', 18, 2);
            $table->timestamp('recorded_at');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('batch_id');
            $table->index('cost_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_costs');
    }
};
