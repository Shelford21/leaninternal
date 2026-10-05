<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            if (!Schema::hasColumn('articles', 'label_number_quty')) {
                $table->string('label_number_quty', 100)->nullable()->unique()->after('label_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            if (Schema::hasColumn('articles', 'label_number_quty')) {
                $table->dropUnique(['label_number_quty']);
                $table->dropColumn('label_number_quty');
            }
        });
    }
};
