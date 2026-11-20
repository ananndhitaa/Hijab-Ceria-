<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('payment_type'); // DP, PELUNASAN, LAINNYA
            $table->decimal('amount', 18, 2);
            $table->date('payment_date');
            $table->string('payment_method')->nullable(); // Transfer, Cash, dll
            $table->string('reference_no')->nullable();
            $table->string('status')->default('PENDING'); // PENDING, CONFIRMED, CANCELLED
            $table->text('notes')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index('invoice_id');
            $table->index('payment_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
