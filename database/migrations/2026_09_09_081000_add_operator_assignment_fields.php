<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('operators', function (Blueprint $table) {
            if (!Schema::hasColumn('operators', 'gender')) {
                $table->string('gender', 30)->nullable()->after('operator_name');
            }
            if (!Schema::hasColumn('operators', 'role')) {
                $table->string('role', 100)->nullable()->after('gender');
            }
            if (!Schema::hasColumn('operators', 'factory_id')) {
                $table->foreignId('factory_id')->nullable()->after('photo_path')->constrained('factories')->nullOnDelete();
            }
            if (!Schema::hasColumn('operators', 'department_id')) {
                $table->foreignId('department_id')->nullable()->after('factory_id')->constrained('departments')->nullOnDelete();
            }
            if (!Schema::hasColumn('operators', 'line_id')) {
                $table->foreignId('line_id')->nullable()->after('department_id')->constrained('production_lines')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('operators', function (Blueprint $table) {
            foreach (['line_id', 'department_id', 'factory_id'] as $foreignKey) {
                if (Schema::hasColumn('operators', $foreignKey)) {
                    $table->dropConstrainedForeignId($foreignKey);
                }
            }
            $columns = array_filter(['gender', 'role'], fn($column) => Schema::hasColumn('operators', $column));
            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};