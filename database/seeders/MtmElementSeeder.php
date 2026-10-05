<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MtmElementSeeder extends Seeder
{
    public function run(): void
    {
        $elements = [
            [
                'element_name' => 'Foot or Short Leg Motion',
                'description' => 'Gerakan telapak kaki/kaki dengan jangkauan dekat',
                'code' => 'F',
                'tmu' => 9,
                'seconds' => 0.30,
            ],
            [
                'element_name' => 'Pace or Step to Move Body',
                'description' => 'Memindahkan badan',
                'code' => 'P',
                'tmu' => 18,
                'seconds' => 0.60,
            ],
            [
                'element_name' => 'Bend (and Arise)',
                'description' => 'Membungkuk (kemudian bangkit)',
                'code' => 'B',
                'tmu' => 61,
                'seconds' => 2.20,
            ],
            [
                'element_name' => 'Bend Down',
                'description' => 'Membungkuk',
                'code' => 'BD',
                'tmu' => 29,
                'seconds' => 1.00,
            ],
            [
                'element_name' => 'Arise from Bending',
                'description' => 'Bangkit dari membungkuk',
                'code' => 'AB',
                'tmu' => 32,
                'seconds' => 1.20,
            ],
            [
                'element_name' => 'Sit',
                'description' => 'Duduk',
                'code' => 'SIT',
                'tmu' => 35,
                'seconds' => 1.30,
            ],
            [
                'element_name' => 'Stand',
                'description' => 'Berdiri',
                'code' => 'SIT',
                'tmu' => 44,
                'seconds' => 1.60,
            ],
            [
                'element_name' => 'Eye Action (Simple Binary Check)',
                'description' => 'Gerakan mata (pemeriksaan ya/tidak dengan mudah)',
                'code' => 'E',
                'tmu' => 7,
                'seconds' => 0.30,
            ],
            [
                'element_name' => 'Crank',
                'description' => 'Engkol',
                'code' => 'C',
                'tmu' => 15,
                'seconds' => 0.50,
            ],
            [
                'element_name' => 'Regrasp',
                'description' => 'Menggenggam kembali',
                'code' => 'R',
                'tmu' => 6,
                'seconds' => 0.20,
            ],
            [
                'element_name' => 'Apply Gesture',
                'description' => 'Membuat gerakan tubuh/gestur',
                'code' => 'A',
                'tmu' => 14,
                'seconds' => 0.50,
            ],
        ];

        foreach ($elements as $element) {
            DB::table('mtm_elements')->updateOrInsert(
                [
                    'element_name' => $element['element_name'],
                    'code' => $element['code'],
                ],
                [
                    'description' => $element['description'],
                    'tmu' => $element['tmu'],
                    'seconds' => $element['seconds'],
                    'status' => 'active',
                    'updated_at' => now(),
                ]
            );
        }
    }
}