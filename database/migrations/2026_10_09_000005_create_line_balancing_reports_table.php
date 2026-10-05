<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('line_balancing_reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('factory_id')
                ->constrained('factories')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('article_id')
                ->constrained('articles')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('line_id')
                ->constrained('production_lines')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('report_name', 150);

            $table->integer('target_output_per_hour')->default(0);

            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('line_balancing_reports');
    }
};