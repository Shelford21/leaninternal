<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StatusPkwttSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = ['TETAP', 'KONTRAK'];

        foreach ($statuses as $status) {
            DB::table('status_pkwtt')->updateOrInsert(
                ['pkwtt' => $status],
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