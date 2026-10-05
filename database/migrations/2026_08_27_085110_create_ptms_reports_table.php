<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ptms_reports', function (Blueprint $table) {
            $table->id();

            $table->string('report_number', 50)->unique();

            $table->foreignId('article_id')
                ->constrained('articles')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('process_version_id')
                ->constrained('process_versions')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('operator_id')
                ->constrained('operators')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('factory_id')
                ->constrained('factories')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('department_id')
                ->constrained('departments')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('line_id')
                ->constrained('production_lines')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('machine_name', 150)->nullable();

            $table->string('feed_type', 100)->nullable();

            $table->decimal('rpm', 10, 2)->nullable();

            $table->decimal('stitch_per_cm', 10, 2)->nullable();

            $table->decimal('seam_width', 10, 2)->nullable();

            $table->decimal('machine_delay_percent', 10, 2)
                ->default(0);

            $table->decimal('contingency_percent', 10, 2)
                ->default(0);

            $table->decimal('ra_percent', 10, 2)
                ->default(0);

            $table->decimal('machining_tmu', 10, 2)
                ->default(0);

            $table->decimal('handling_tmu', 10, 2)
                ->default(0);

            $table->decimal('bundle_tmu', 10, 2)
                ->default(0);

            $table->decimal('total_tmu', 10, 2)
                ->default(0);

            $table->decimal('bms', 10, 2)
                ->default(0);

            $table->decimal('smv', 10, 2)
                ->default(0);

            $table->enum('status', ['draft', 'final', 'archived'])
                ->default('draft');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ptms_reports');
    }
};