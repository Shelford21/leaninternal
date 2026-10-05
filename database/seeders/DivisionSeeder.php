<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DivisionSeeder extends Seeder
{
    public function run(): void
    {
        $divisions = ['Warehouse', 'Cutting', 'Sewing', 'Finshing', 'Packing'];

        // Ensure all new divisions exist first (so we have a valid FK target)
        foreach ($divisions as $name) {
            DB::table('divisions')->updateOrInsert(
                ['division' => $name],
                [
                    'description' => null,
                    'status' => 'active',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        // Get the first new division ID as fallback for FK references
        $fallbackDivisionId = DB::table('divisions')->where('division', 'Cutting')->value('id');

        // Update production_lines referencing old divisions to point to the fallback
        $oldDivisionIds = DB::table('divisions')
            ->whereNotIn('division', $divisions)
            ->pluck('id');

        if ($oldDivisionIds->isNotEmpty() && $fallbackDivisionId) {
            DB::table('production_lines')
                ->whereIn('division_id', $oldDivisionIds)
                ->update(['division_id' => $fallbackDivisionId]);
        }

        // Now remove rows not in the new list
        DB::table('divisions')->whereNotIn('division', $divisions)->delete();
    }
}
