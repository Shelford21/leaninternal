<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            GsdCategorySeeder::class,
            GsdElementSeeder::class,
            MtmElementSeeder::class,
            SewingFactorSeeder::class,
            SewingStopFactorSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            SkillGradingSeeder::class,
            DivisionSeeder::class,
            SectionSeeder::class,
            MachineTypeSeeder::class,
            ComponentsPanelSeeder::class,
            MachineNumberSeeder::class,
            ShiftSeeder::class,
            FailureModeSeeder::class,
            MechanicSeeder::class,
            SparePartSeeder::class,
            ProductionLineSeeder::class,
            GenderSeeder::class,
            ProductionRoleSeeder::class,
            EducationalLevelSeeder::class,
            StatusPkwttSeeder::class,
            DepartmentSeeder::class,
            DestinationSeeder::class,
            LineBalancingSeeder::class,
        ]);
    }
}
