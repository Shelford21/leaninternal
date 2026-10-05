<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SparePartSeeder extends Seeder
{
    public function run(): void
    {
        $parts = [
            'Tidak ada',
            'Protective Lens 30x5mm',
            'Ceramic Ring D32',
            'Laser Nozzle Single 1.5mm',
            'High Temp Dust Filter',
        ];

        foreach ($parts as $name) {
            DB::table('spare_parts')->updateOrInsert(
                ['spare_part' => $name],
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
