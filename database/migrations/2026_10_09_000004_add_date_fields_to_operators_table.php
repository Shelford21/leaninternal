<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('operators', function (Blueprint $table) {
            if (!Schema::hasColumn('operators', 'start_date')) {
                $table->date('start_date')->nullable()->after('line_id');
            }
            if (!Schema::hasColumn('operators', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('start_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('operators', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'date_of_birth']);
        });
    }
};