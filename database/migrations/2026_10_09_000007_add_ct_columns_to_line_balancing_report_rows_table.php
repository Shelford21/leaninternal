<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('line_balancing_report_rows', function (Blueprint $table) {
            $table->decimal('ct_1', 10, 2)->nullable()->after('operator');
            $table->decimal('ct_2', 10, 2)->nullable()->after('ct_1');
            $table->decimal('ct_3', 10, 2)->nullable()->after('ct_2');
            $table->decimal('ct_4', 10, 2)->nullable()->after('ct_3');
            $table->decimal('ct_5', 10, 2)->nullable()->after('ct_4');
        });

        // Migrate existing cycle_time data to ct_1
        DB::table('line_balancing_report_rows')
            ->whereNotNull('cycle_time')
            ->where('cycle_time', '>', 0)
            ->update(['ct_1' => DB::raw('cycle_time')]);

        Schema::table('line_balancing_report_rows', function (Blueprint $table) {
            $table->dropColumn('cycle_time');
        });

        Schema::table('line_balancing_reports', function (Blueprint $table) {
            $table->integer('output_actual')->nullable()->after('target_output_per_hour');
        });
    }

    public function down(): void
    {
        Schema::table('line_balancing_report_rows', function (Blueprint $table) {
            $table->decimal('cycle_time', 10, 2)->default(0)->after('operator');
            $table->dropColumn(['ct_1', 'ct_2', 'ct_3', 'ct_4', 'ct_5']);
        });

        Schema::table('line_balancing_reports', function (Blueprint $table) {
            $table->dropColumn('output_actual');
        });
    }
};