<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FailureModeSeeder extends Seeder
{
    public function run(): void
    {
        $modes = [
            'Lensa Pelindung Terbakar/Rusak',
            'Keramik Ring / Nozzle',
            'Laser Source',
            'Cooling / Chiller',
            'Gas Supply',
            'Exhaust / Blower',
            'Motor / Axis',
            'Sensor / Limit Switch',
            'Electrical / Panel',
            'Software / CNC',
            'Machine Breakdown',
            'Setup',
            'Lainnya',
        ];

        foreach ($modes as $name) {
            DB::table('failure_modes')->updateOrInsert(
                ['failure_mode' => $name],
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
