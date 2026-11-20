<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->decimal('printing_cost_per_unit', 18, 2)->nullable()->after('requires_printing');
            $table->decimal('direct_labor_cost_per_unit', 18, 2)->nullable()->after('printing_cost_per_unit');
            $table->decimal('packaging_process_cost_per_good_unit', 18, 2)->nullable()->after('direct_labor_cost_per_unit');
            $table->decimal('other_direct_cost_per_unit', 18, 2)->nullable()->after('packaging_process_cost_per_good_unit');
        });

        Schema::table('production_batches', function (Blueprint $table) {
            $table->decimal('printing_cost_per_unit_snapshot', 18, 2)->nullable()->after('requires_printing');
            $table->decimal('direct_labor_cost_per_unit_snapshot', 18, 2)->nullable()->after('printing_cost_per_unit_snapshot');
            $table->decimal('packaging_process_cost_per_good_unit_snapshot', 18, 2)->nullable()->after('direct_labor_cost_per_unit_snapshot');
            $table->decimal('other_direct_cost_per_unit_snapshot', 18, 2)->nullable()->after('packaging_process_cost_per_good_unit_snapshot');

            if (Schema::hasColumn('production_batches', 'direct_costs_confirmed_by')) {
                $table->dropConstrainedForeignId('direct_costs_confirmed_by');
            }
            if (Schema::hasColumn('production_batches', 'direct_costs_confirmed_at')) {
                $table->dropColumn('direct_costs_confirmed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('production_batches', function (Blueprint $table) {
            $table->timestamp('direct_costs_confirmed_at')->nullable()->after('material_usage_recorded_by');
            $table->foreignId('direct_costs_confirmed_by')->nullable()->after('direct_costs_confirmed_at')->constrained('users')->nullOnDelete();

            $table->dropColumn([
                'printing_cost_per_unit_snapshot',
                'direct_labor_cost_per_unit_snapshot',
                'packaging_process_cost_per_good_unit_snapshot',
                'other_direct_cost_per_unit_snapshot',
            ]);
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn([
                'printing_cost_per_unit',
                'direct_labor_cost_per_unit',
                'packaging_process_cost_per_good_unit',
                'other_direct_cost_per_unit',
            ]);
        });
    }
};
