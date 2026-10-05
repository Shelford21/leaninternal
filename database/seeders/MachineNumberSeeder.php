<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MachineNumberSeeder extends Seeder
{
    public function run(): void
    {
        $numbers = [
            '1',
            '2',
            '3',
            '4',
            '5',
            '6',
            '7',
            '8',
            '9',
            '10',
            '11',
            '12',
            '13',
            '14',
            '15',
            '16',
            '17',
            '18',
            '19',
            '20',
            '21',
            '1a',
            '1b',
            '2a',
            '2b',
            '3a',
            '3b',
            '4a',
            '4b',
            '5a',
            '5b',
            '6a',
            '6b',
            '7a',
            '7b',
            '8a',
            '8b',
            '9a',
            '9b',
            '10a',
            '10b',
            '11a',
            '11b',
            '12a',
            '12b',
            '13a',
            '13b',
            '14a',
            '14b',
            '15a',
            '15b',
        ];

        foreach ($numbers as $num) {
            DB::table('machine_numbers')->updateOrInsert(
                ['machine_number' => $num],
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
