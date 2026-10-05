<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SectionSeeder extends Seeder
{
    public function run(): void
    {
        $sections = [
            'Computer Stitching',
            'Cutting Gerber',
            'Cutting Laser',
            'Embroidery',
            'Stuffing',
            'Reverse Skin',
            'Finishing Line',
            'Finished Goods',
        ];

        // Remove rows not in the new list
        DB::table('sections')->whereNotIn('section', $sections)->delete();

        foreach ($sections as $name) {
            DB::table('sections')->updateOrInsert(
                ['section' => $name],
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
