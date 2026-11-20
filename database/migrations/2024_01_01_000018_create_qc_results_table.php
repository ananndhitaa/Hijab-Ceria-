<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // QC HANYA: GOOD dan REJECT — TIDAK ADA REWORK
        Schema::create('qc_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_result_id')
                  ->unique()
                  ->constrained('production_results')
                  ->cascadeOnDelete();
            $table->decimal('good_qty', 18, 4)->default(0);
            $table->decimal('reject_qty', 18, 4)->default(0);
            $table->date('qc_date');
            $table->text('notes')->nullable();
            $table->foreignId('qc_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('qc_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qc_results');
    }
};
