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
        Schema::create('gsd_elements', function (Blueprint $table) {
            $table->id();
            $table->foreignId("gsd_category_id")
                ->constrained("gsd_categories")
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('element_name', 200); #apsh dll
            $table->string('description', 255)
                ->nullable();
            $table->string('code', 50);
            $table->decimal('tmu', 10, 2);
            $table->decimal('seconds', 10, 2);
            $table->string('motion_sequence', 100)
                ->nullable();
            $table->enum('status', ['active', 'inactive'])
                ->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gsd_elements');
    }
};
