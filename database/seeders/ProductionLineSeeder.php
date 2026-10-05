<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductionLineSeeder extends Seeder
{
    public function run(): void
    {
        // Default production lines: A1-A10, B1-B10, C1-C10
        $lines = [];
        foreach (['A', 'B', 'C'] as $prefix) {
            for ($i = 1; $i <= 10; $i++) {
                $lines[] = $prefix . $i;
            }
        }

        // Get first division as default
        $defaultDivId = DB::table('divisions')->where('status', 'active')->value('id');

        foreach ($lines as $lineName) {
            DB::table('production_lines')->updateOrInsert(
                ['line_name' => $lineName],
                [
                    'division_id' => $defaultDivId,
                    'description' => null,
                    'status' => 'active',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
