<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('roles')->updateOrInsert(
            ['role_name' => 'developer'],
            [
                'description' => 'Full system access, including technical/system functions',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        DB::table('roles')->updateOrInsert(
            ['role_name' => 'admin'],
            [
                'description' => 'Full application/business feature access',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        DB::table('roles')->updateOrInsert(
            ['role_name' => 'viewer'],
            [
                'description' => 'View and download/export only',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}