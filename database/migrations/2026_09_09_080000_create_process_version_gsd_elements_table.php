<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('process_version_gsd_elements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_version_id')->constrained('process_versions')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('gsd_element_id')->constrained('gsd_elements')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();
            $table->unique(['process_version_id', 'gsd_element_id'], 'process_version_gsd_elements_unique');
        });

        DB::table('process_versions')
            ->whereNotNull('gsd_element_id')
            ->select(['id', 'gsd_element_id'])
            ->orderBy('id')
            ->each(function ($version) {
                DB::table('process_version_gsd_elements')->insert([
                    'process_version_id' => $version->id,
                    'gsd_element_id' => $version->gsd_element_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('process_version_gsd_elements');
    }
};