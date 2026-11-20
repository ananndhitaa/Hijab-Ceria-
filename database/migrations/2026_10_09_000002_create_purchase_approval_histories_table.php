<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_approval_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->string('action'); // SUBMITTED, RESUBMITTED, APPROVED, REVISION_REQUESTED, REJECTED, INVALIDATED
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['purchase_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_approval_histories');
    }
};
