<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('process_versions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('process_id')
                ->constrained('processes')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->unsignedInteger('version_number');

            $table->string('notes', 255)->nullable();

            $table->enum('status', ['draft', 'active', 'archived'])
                ->default('draft');

            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->timestamps();

            $table->unique(
                ['process_id', 'version_number'],
                'process_versions_process_version_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('process_versions');
    }
};