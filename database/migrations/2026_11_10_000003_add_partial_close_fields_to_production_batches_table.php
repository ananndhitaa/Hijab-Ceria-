<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('production_batches', function (Blueprint $table) {
            $table->text('close_reason')->nullable()->after('notes');
            $table->foreignId('closed_by_user_id')->nullable()->after('close_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable()->after('closed_by_user_id');
            $table->decimal('shortfall_qty', 18, 4)->default(0)->after('closed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_batches', function (Blueprint $table) {
            $table->dropForeign(['closed_by_user_id']);
            $table->dropColumn(['close_reason', 'closed_by_user_id', 'closed_at', 'shortfall_qty']);
        });
    }
};

