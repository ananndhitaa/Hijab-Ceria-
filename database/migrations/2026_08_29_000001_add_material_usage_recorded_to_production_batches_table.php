<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_batches', function (Blueprint $table) {
            $table->timestamp('material_usage_recorded_at')->nullable()->after('requires_printing');
            $table->foreignId('material_usage_recorded_by')->nullable()->after('material_usage_recorded_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('production_batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('material_usage_recorded_by');
            $table->dropColumn('material_usage_recorded_at');
        });
    }
};
