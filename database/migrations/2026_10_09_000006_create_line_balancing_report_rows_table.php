<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('line_balancing_report_rows', function (Blueprint $table) {
            $table->id();

            $table->foreignId('line_balancing_report_id')
                ->constrained('line_balancing_reports')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->integer('row_number')->default(1);

            $table->foreignId('machine_type_id')
                ->nullable()
                ->constrained('machine_types')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->string('process', 150)->nullable();

            $table->string('name', 150)->nullable();

            $table->integer('operator')->default(1);

            $table->decimal('cycle_time', 10, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('line_balancing_report_rows');
    }
};