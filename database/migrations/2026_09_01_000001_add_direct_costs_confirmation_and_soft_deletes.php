<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_batches', function (Blueprint $table) {
            $table->timestamp('direct_costs_confirmed_at')->nullable()->after('material_usage_recorded_by');
            $table->foreignId('direct_costs_confirmed_by')->nullable()->after('direct_costs_confirmed_at')->constrained('users')->nullOnDelete();
        });

        Schema::table('batch_costs', function (Blueprint $table) {
            $table->softDeletes()->after('recorded_by');
        });
    }

    public function down(): void
    {
        Schema::table('batch_costs', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('production_batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('direct_costs_confirmed_by');
            $table->dropColumn('direct_costs_confirmed_at');
        });
    }
};
