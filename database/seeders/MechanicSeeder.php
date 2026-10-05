<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MechanicSeeder extends Seeder
{
    public function run(): void
    {
        $mechanics = [
            'Dani',
            'Dede',
            'Dede K',
            'Didi Muglis',
            'Eno',
            'Idin',
            'Iwan',
            'Mulyadi',
            'Ohim',
            'Opik',
            'Pepen',
            'Rudi',
            'Yuyud',
        ];

        foreach ($mechanics as $name) {
            DB::table('mechanics')->updateOrInsert(
                ['mechanic' => $name],
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
