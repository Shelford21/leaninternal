<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $developerRole = Role::where('role_name', 'developer')->firstOrFail();
        $adminRole = Role::where('role_name', 'admin')->firstOrFail();
        $viewerRole = Role::where('role_name', 'viewer')->firstOrFail();

        $users = [
            [
                'name' => 'Developer',
                'username' => 'developer',
                'password' => '#devadmin',
                'role_id' => $developerRole->id,
            ],
            [
                'name' => 'Yuliyana',
                'username' => 'yuliyana',
                'password' => 'yuliyana1234',
                'role_id' => $adminRole->id,
            ],
            [
                'name' => 'Kanitha',
                'username' => 'kanitha',
                'password' => 'kanitha1234',
                'role_id' => $adminRole->id,
            ],
            [
                'name' => 'Satrio',
                'username' => 'satrio',
                'password' => 'satrio1234',
                'role_id' => $adminRole->id,
            ],
            [
                'name' => 'Hermawan',
                'username' => 'hermawan',
                'password' => 'hermawan1234',
                'role_id' => $adminRole->id,
            ],
            [
                'name' => 'Ilham',
                'username' => 'ilham',
                'password' => 'ilham1234',
                'role_id' => $adminRole->id,
            ],
            [
                'name' => 'Allief',
                'username' => 'allief',
                'password' => 'allief1234',
                'role_id' => $adminRole->id,
            ],
            [
                'name' => 'Normal User',
                'username' => 'normal',
                'password' => '1234',
                'role_id' => $viewerRole->id,
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['username' => $user['username']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make($user['password']),
                    'role_id' => $user['role_id'],
                    'employee_number' => null,
                ]
            );
        }
    }
}