<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SewingFactorSeeder extends Seeder
{
    public function run(): void
    {
        $factors = [
            [
                'factor_name' => 'Nil',
                'description' => 'A straight burst on a single ply',
                'factor_value' => 1.00,
                'code' => 'N',
            ],
            [
                'factor_name' => 'Low',
                'description' => 'A straight, non-visible seam (ie, not having an applicable affect on the final appearance of the product)',
                'factor_value' => 1.10,
                'code' => 'L',
            ],
            [
                'factor_name' => 'Medium',
                'description' => 'A straight visible seam or a curved non-visible seam',
                'factor_value' => 1.20,
                'code' => 'M',
            ],
            [
                'factor_name' => 'High',
                'description' => 'A curved visible seam or a seam worked in confined space',
                'factor_value' => 1.40,
                'code' => 'H',
            ],
        ];

        foreach ($factors as $factor) {
            DB::table('sewing_factors')->updateOrInsert(
                [
                    'factor_name' => $factor['factor_name'],
                    'code' => $factor['code'],
                ],
                [
                    'description' => $factor['description'],
                    'factor_value' => $factor['factor_value'],
                    'status' => 'active',
                    'updated_at' => now(),
                ]
            );
        }
    }
}