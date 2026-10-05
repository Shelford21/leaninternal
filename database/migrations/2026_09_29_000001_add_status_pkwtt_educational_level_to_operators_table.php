<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('operators', function (Blueprint $table) {
            if (!Schema::hasColumn('operators', 'status_pkwtt_id')) {
                $table->foreignId('status_pkwtt_id')->nullable()->after('status')->constrained('status_pkwtt')->nullOnDelete();
            }
            if (!Schema::hasColumn('operators', 'educational_level_id')) {
                $table->foreignId('educational_level_id')->nullable()->after('status_pkwtt_id')->constrained('educational_levels')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('operators', function (Blueprint $table) {
            $table->dropForeign(['status_pkwtt_id']);
            $table->dropForeign(['educational_level_id']);
            $table->dropColumn(['status_pkwtt_id', 'educational_level_id']);
        });
    }
};