<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MachineTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['machine_type' => 'SN', 'description' => 'Single Needle'],
            ['machine_type' => 'ZG', 'description' => 'ZigZag'],
        ];

        foreach ($types as $type) {
            DB::table('machine_types')->updateOrInsert(
                ['machine_type' => $type['machine_type']],
                [
                    'description' => $type['description'],
                    'status' => 'active',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
