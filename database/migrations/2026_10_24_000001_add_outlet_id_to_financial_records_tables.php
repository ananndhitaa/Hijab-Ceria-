<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Target financial records tables receiving tenant outlet_id.
     *
     * @var array<int, string>
     */
    protected array $targetTables = [
        'opening_cash_balances',
        'business_incomes',
        'business_expenses',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add nullable outlet_id with ON DELETE RESTRICT to each target table
        foreach ($this->targetTables as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'outlet_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('outlet_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('outlets')
                        ->restrictOnDelete();
                });
            }
        }

        // 2. Add Composite Constraints and Indexes

        // opening_cash_balances: Unique per outlet & opening cut-off date
        // Allows different outlets to have the same cut-off date, but prevents duplicate cut-offs for the same outlet.
        // Standard SQL & SQLite permit multiple NULL outlet_id records for legacy unassigned data.
        if (Schema::hasTable('opening_cash_balances')) {
            Schema::table('opening_cash_balances', function (Blueprint $table) {
                $table->unique(['outlet_id', 'opening_date'], 'uq_opening_cash_balances_outlet_date');
            });
        }

        // business_incomes: Composite index for tenant date-range queries
        if (Schema::hasTable('business_incomes')) {
            Schema::table('business_incomes', function (Blueprint $table) {
                $table->index(['outlet_id', 'income_date'], 'idx_business_incomes_outlet_date');
            });
        }

        // business_expenses: Composite index for tenant date-range queries
        if (Schema::hasTable('business_expenses')) {
            Schema::table('business_expenses', function (Blueprint $table) {
                $table->index(['outlet_id', 'expense_date'], 'idx_business_expenses_outlet_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes first
        if (Schema::hasTable('opening_cash_balances')) {
            Schema::table('opening_cash_balances', function (Blueprint $table) {
                $table->dropUnique('uq_opening_cash_balances_outlet_date');
            });
        }

        if (Schema::hasTable('business_incomes')) {
            Schema::table('business_incomes', function (Blueprint $table) {
                $table->dropIndex('idx_business_incomes_outlet_date');
            });
        }

        if (Schema::hasTable('business_expenses')) {
            Schema::table('business_expenses', function (Blueprint $table) {
                $table->dropIndex('idx_business_expenses_outlet_date');
            });
        }

        // Drop foreign keys and columns in reverse order
        foreach (array_reverse($this->targetTables) as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'outlet_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['outlet_id']);
                    $table->dropColumn('outlet_id');
                });
            }
        }
    }
};
