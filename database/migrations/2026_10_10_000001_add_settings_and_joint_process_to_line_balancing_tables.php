<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add configurable settings and update_date to reports table
        Schema::table('line_balancing_reports', function (Blueprint $table) {
            $table->decimal('working_hours_per_day', 5, 2)->default(8)->after('output_actual');
            $table->decimal('allowance_percent', 5, 2)->default(15)->after('working_hours_per_day');
            $table->date('update_date')->nullable()->after('allowance_percent');
        });

        // Add joint_process to rows table
        Schema::table('line_balancing_report_rows', function (Blueprint $table) {
            $table->string('joint_process', 150)->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('line_balancing_reports', function (Blueprint $table) {
            $table->dropColumn(['working_hours_per_day', 'allowance_percent', 'update_date']);
        });

        Schema::table('line_balancing_report_rows', function (Blueprint $table) {
            $table->dropColumn('joint_process');
        });
    }
};