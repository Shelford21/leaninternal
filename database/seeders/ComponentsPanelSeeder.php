<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ComponentsPanelSeeder extends Seeder
{
    public function run(): void
    {
        $panels = [
            'Bantal',
            'Batang ekor',
            'Bias',
            'Body set',
            'Dada',
            'Dagu',
            'Ekor',
            'Guling',
            'Janggut',
            'Kaki',
            'Kening',
            'Muka',
            'Mulut',
            'Mulut atas',
            'Mulut Double',
            'Paha Luar',
            'Paha dalam',
            'Perut',
            'Perut, dagu',
            'Rambut',
            'Spot',
            'Sewing',
            'Tangan',
            'Telinga Dalam',
            'Telinga Luar',
            'Topi atas',
            'Topi Bawah',
            'Ujung ekor',
        ];

        foreach ($panels as $name) {
            DB::table('components_panels')->updateOrInsert(
                ['component_panel' => $name],
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
