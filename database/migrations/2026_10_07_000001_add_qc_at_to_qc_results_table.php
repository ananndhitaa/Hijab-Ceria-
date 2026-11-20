<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive only. qc_at records the actual QC event timestamp (stored UTC, displayed WIB).
 * Nullable so historical QC rows (which only have the legacy qc_date) stay untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('qc_results', 'qc_at')) {
            Schema::table('qc_results', function (Blueprint $table) {
                $table->timestamp('qc_at')->nullable()->after('qc_date');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('qc_results', 'qc_at')) {
            Schema::table('qc_results', function (Blueprint $table) {
                $table->dropColumn('qc_at');
            });
        }
    }
};
