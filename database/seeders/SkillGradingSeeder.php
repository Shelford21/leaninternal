<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SkillGradingSeeder extends Seeder
{
    public function run(): void
    {
        $gradings = [
            ['skill_grade' => 'S', 'description' => 'Superior — Highest skill level, capable of all operations and training others'],
            ['skill_grade' => 'A', 'description' => 'Advanced — High skill level, capable of complex operations with minimal supervision'],
            ['skill_grade' => 'B', 'description' => 'Basic — Standard skill level, capable of regular operations with some supervision'],
            ['skill_grade' => 'C', 'description' => 'Clerical — Entry level, requires close supervision and training support'],
        ];

        foreach ($gradings as $grading) {
            DB::table('skill_gradings')->updateOrInsert(
                ['skill_grade' => $grading['skill_grade']],
                [
                    'description' => $grading['description'],
                    'status' => 'active',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
