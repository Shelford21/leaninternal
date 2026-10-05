<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('line_balancing_report_rows', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_id')->nullable()->after('name');
            $table->foreign('employee_id')->references('id')->on('operators')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('line_balancing_report_rows', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropColumn('employee_id');
        });
    }
};