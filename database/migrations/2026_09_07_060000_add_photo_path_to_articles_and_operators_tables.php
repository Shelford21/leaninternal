<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('articles', 'photo_path')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->string('photo_path', 255)->nullable()->after('description');
            });
        }

        if (!Schema::hasColumn('operators', 'photo_path')) {
            Schema::table('operators', function (Blueprint $table) {
                $table->string('photo_path', 255)->nullable()->after('operator_name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('articles', 'photo_path')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->dropColumn('photo_path');
            });
        }

        if (Schema::hasColumn('operators', 'photo_path')) {
            Schema::table('operators', function (Blueprint $table) {
                $table->dropColumn('photo_path');
            });
        }
    }
};