<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->date('expense_date');
            $table->string('expense_type'); // MARKETING, OPERATING, INTEREST, TAX, DEPRECIATION, AMORTIZATION
            $table->string('campaign_name')->nullable(); // Untuk marketing spend
            $table->text('description');
            $table->decimal('amount', 18, 2);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('order_id');
            $table->index('expense_date');
            $table->index('expense_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_expenses');
    }
};
