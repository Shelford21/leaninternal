<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $factoryIds = [];

        foreach (['Factory 1', 'Factory 2'] as $factoryName) {
            $factory = DB::table('factories')->where('factory_name', $factoryName)->first();
            $factoryIds[$factoryName] = $factory?->id ?? DB::table('factories')->insertGetId([
                'factory_name' => $factoryName,
                'description' => 'Operator master-data factory choice',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $departmentNames = [
            'Warehouse',
            'Cutting Laser',
            'Embroidery',
            'Cutting Gerber',
            'Sewing',
            'Finishing',
            'Packing',
            'Finished Goods',
        ];

        $sewingDepartmentId = null;
        foreach ($departmentNames as $departmentName) {
            $department = DB::table('departments')
                ->where('factory_id', $factoryIds['Factory 1'])
                ->where('department_name', $departmentName)
                ->first();
            $departmentId = $department?->id ?? DB::table('departments')->insertGetId([
                'factory_id' => $factoryIds['Factory 1'],
                'department_name' => $departmentName,
                'desription' => 'Operator master-data department choice',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($departmentName === 'Sewing') {
                $sewingDepartmentId = $departmentId;
            }
        }

        foreach (['A', 'B', 'C'] as $prefix) {
            for ($number = 1; $number <= 10; $number++) {
                if (!DB::table('production_lines')->where('department_id', $sewingDepartmentId)->where('line_name', $prefix . $number)->exists()) {
                    DB::table('production_lines')->insert([
                        'department_id' => $sewingDepartmentId,
                        'line_name' => $prefix . $number,
                        'description' => 'Operator master-data line choice',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        $lineIds = DB::table('production_lines')
            ->where('description', 'Operator master-data line choice')
            ->pluck('id');

        DB::table('production_lines')->whereIn('id', $lineIds)->delete();
        DB::table('departments')->where('desription', 'Operator master-data department choice')->delete();
        DB::table('departments')->whereIn('department_name', [
            'Warehouse',
            'Cutting Laser',
            'Embroidery',
            'Cutting Gerber',
            'Sewing',
            'Finishing',
            'Packing',
            'Finished Goods',
        ])->where('desription', 'Operator master-data department choice')->delete();
        DB::table('factories')->where('description', 'Operator master-data factory choice')->delete();
    }
};