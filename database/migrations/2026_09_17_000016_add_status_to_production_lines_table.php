<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('production_lines', function (Blueprint $table) {
            $table->enum('status', ['active', 'inactive'])->default('active')->after('description');
        });

        // Set existing records to active
        DB::table('production_lines')->whereNull('status')->update(['status' => 'active']);
    }

    public function down(): void
    {
        Schema::table('production_lines', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
