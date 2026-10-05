<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $defaultFactoryId = DB::table('factories')->where('status', 'active')->value('id')
            ?? DB::table('factories')->value('id');

        $departments = [
            'Business Development',
            'HRD',
            'Production',
            'Logistics',
            'PPIC',
            'Purchasing',
            'IT',
            'Accounting',
            'LEAN & IE',
            'Sustainability',
        ];

        // Ensure all new departments exist first (so we have a valid FK target)
        foreach ($departments as $name) {
            DB::table('departments')->updateOrInsert(
                ['department_name' => $name],
                [
                    'factory_id' => $defaultFactoryId,
                    'desription' => null,
                    'status' => 'active',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        // Get the first new department ID as fallback for FK references
        $fallbackDeptId = DB::table('departments')->where('department_name', 'Production')->value('id');

        // Get IDs of old departments that will be removed
        $oldDeptIds = DB::table('departments')
            ->whereNotIn('department_name', $departments)
            ->pluck('id');

        if ($oldDeptIds->isNotEmpty() && $fallbackDeptId) {
            // Reassign ptms_reports referencing old departments
            DB::table('ptms_reports')
                ->whereIn('department_id', $oldDeptIds)
                ->update(['department_id' => $fallbackDeptId]);

            // Reassign operators referencing old departments (if column exists)
            if (Schema::hasColumn('operators', 'department_id')) {
                DB::table('operators')
                    ->whereIn('department_id', $oldDeptIds)
                    ->update(['department_id' => $fallbackDeptId]);
            }
        }

        // Now remove rows not in the new list
        DB::table('departments')->whereNotIn('department_name', $departments)->delete();
    }
}