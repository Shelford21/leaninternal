<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SewingStopFactorSeeder extends Seeder
{
    public function run(): void
    {
        $factors = [
            [
                'factor_name' => 'Stop Long a Seam or Run Off',
                'description' => 'Stop Long a Seam or Run Off',
                'tolerance' => 'Greater than 1 cm',
                'factor_value' => 0.00,
                'code' => 'A',
            ],
            [
                'factor_name' => 'Stop for a Non-Visible Backtack',
                'description' => 'Stop for a Non-Visible Backtack',
                'tolerance' => 'Within 1 cm',
                'factor_value' => 9.00,
                'code' => 'B',
            ],
            [
                'factor_name' => 'Stop to Change Direction or Form Visible Backtack',
                'description' => 'Stop to Change Direction (Needle Pivot) or To Form Visible Backtack',
                'tolerance' => 'Within 1/2 cm',
                'factor_value' => 21.00,
                'code' => 'C',
            ],
        ];

        foreach ($factors as $factor) {
            DB::table('sewing_stop_factors')->updateOrInsert(
                [
                    'factor_name' => $factor['factor_name'],
                    'code' => $factor['code'],
                ],
                [
                    'description' => $factor['description'],
                    'tolerance' => $factor['tolerance'],
                    'factor_value' => $factor['factor_value'],
                    'status' => 'active',
                    'updated_at' => now(),
                ]
            );
        }
    }
}