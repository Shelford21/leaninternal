<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EducationalLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = ['D1', 'D3', 'MA', 'MTS', 'S1', 'SD', 'SLTA', 'SLTP', 'SMA', 'SMK', 'SMKN', 'SMP', 'STM'];

        foreach ($levels as $level) {
            DB::table('educational_levels')->updateOrInsert(
                ['level' => $level],
                [
                    'description' => null,
                    'status' => 'active',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}