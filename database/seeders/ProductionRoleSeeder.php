<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductionRoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['production_role' => 'Helper', 'description' => null],
            ['production_role' => 'Operator', 'description' => null],
            ['production_role' => 'Quality Control', 'description' => null],
            ['production_role' => 'Leader', 'description' => null],
            ['production_role' => 'Assistant Supervisor', 'description' => null],
            ['production_role' => 'Supervisor', 'description' => null],
            ['production_role' => 'Assistant Manager', 'description' => null],
            ['production_role' => 'Manager', 'description' => null],
            ['production_role' => 'ADM', 'description' => null],
            ['production_role' => 'ANGGOTA', 'description' => null],
            ['production_role' => 'CHECKER SEWING', 'description' => null],
            ['production_role' => 'DANRU', 'description' => null],
            ['production_role' => 'KASAT', 'description' => null],
        ];

        foreach ($roles as $role) {
            DB::table('production_roles')->updateOrInsert(
                ['production_role' => $role['production_role']],
                [
                    'description' => $role['description'],
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
