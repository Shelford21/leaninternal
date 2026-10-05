<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('process_versions', function (Blueprint $table) {
            if (!Schema::hasColumn('process_versions', 'gsd_category_id')) {
                $table->foreignId('gsd_category_id')
                    ->nullable()
                    ->after('created_by')
                    ->constrained('gsd_categories')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('process_versions', 'gsd_element_id')) {
                $table->foreignId('gsd_element_id')
                    ->nullable()
                    ->after('gsd_category_id')
                    ->constrained('gsd_elements')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('process_versions', function (Blueprint $table) {
            if (Schema::hasColumn('process_versions', 'gsd_element_id')) {
                $table->dropConstrainedForeignId('gsd_element_id');
            }

            if (Schema::hasColumn('process_versions', 'gsd_category_id')) {
                $table->dropConstrainedForeignId('gsd_category_id');
            }
        });
    }
};