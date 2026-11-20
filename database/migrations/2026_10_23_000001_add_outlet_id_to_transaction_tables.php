<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Target transaction root tables receiving outlet_id.
     *
     * @var array<int, string>
     */
    protected array $targetTables = [
        'orders',
        'purchases',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
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
