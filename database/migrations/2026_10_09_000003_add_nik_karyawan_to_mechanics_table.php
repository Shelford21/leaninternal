<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('mechanics', 'nik_karyawan')) {
            Schema::table('mechanics', function (Blueprint $table) {
                $table->string('nik_karyawan', 50)->nullable()->unique()->after('id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('mechanics', 'nik_karyawan')) {
            Schema::table('mechanics', function (Blueprint $table) {
                $table->dropUnique(['nik_karyawan']);
                $table->dropColumn('nik_karyawan');
            });
        }
    }
};