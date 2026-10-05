<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('operators', 'nik_karyawan')) {
            Schema::table('operators', function (Blueprint $table) {
                $table->string('nik_karyawan', 50)->nullable()->unique()->after('employee_number');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('operators', 'nik_karyawan')) {
            Schema::table('operators', function (Blueprint $table) {
                $table->dropUnique(['nik_karyawan']);
                $table->dropColumn('nik_karyawan');
            });
        }
    }
};
