<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('factories', 'status')) {
            Schema::table('factories', function (Blueprint $table) {
                $table->enum('status', ['active', 'inactive'])->default('active')->after('description');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('factories', 'status')) {
            Schema::table('factories', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
