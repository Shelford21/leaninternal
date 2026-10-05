<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GenderSeeder extends Seeder
{
    public function run(): void
    {
        $genders = [
            ['gender' => 'L', 'description' => 'Laki - Laki', 'status' => 'active'],
            ['gender' => 'P', 'description' => 'Perempuan', 'status' => 'active'],
        ];

        foreach ($genders as $gender) {
            DB::table('genders')->updateOrInsert(
                ['gender' => $gender['gender']],
                [
                    'description' => $gender['description'],
                    'status' => $gender['status'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
