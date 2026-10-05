<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sewing_stop_factors', function (Blueprint $table) {
            $table->id();

            $table->string('factor_name', 150);

            $table->string('description', 255)
                ->nullable();

            $table->string('tolerance', 100)->nullable();

            $table->decimal('factor_value', 10, 2);

            $table->string('code', 50);

            $table->enum('status', ['active', 'inactive'])
                ->default('active');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sewing_stop_factors');
    }
};