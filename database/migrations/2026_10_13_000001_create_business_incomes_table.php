<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_incomes', function (Blueprint $table) {
            $table->id();
            $table->string('income_no', 50)->unique();
            $table->date('income_date');
            $table->string('category', 100);
            $table->decimal('amount', 18, 2);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('income_date');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_incomes');
    }
};
