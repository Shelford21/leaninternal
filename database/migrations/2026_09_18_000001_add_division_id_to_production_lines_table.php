<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $hasDivisionId = Schema::hasColumn('production_lines', 'division_id');

        if (!$hasDivisionId) {
            // Fresh run: add column
            Schema::table('production_lines', function (Blueprint $table) {
                $table->foreignId('division_id')
                    ->nullable()
                    ->after('department_id')
                    ->constrained('divisions')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
            });

            // Migrate data
            $this->migrateDepartmentToDivision();
        }

        // Drop the FK if it exists (from nullable version), then make NOT NULL
        try {
            Schema::table('production_lines', function (Blueprint $table) {
                $table->dropForeign(['division_id']);
            });
        } catch (\Exception $e) {
            // FK may not exist, ignore
        }

        Schema::table('production_lines', function (Blueprint $table) {
            $table->foreignId('division_id')->nullable(false)->change();
        });

        Schema::table('production_lines', function (Blueprint $table) {
            $table->foreign('division_id')
                ->references('id')->on('divisions')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        // Drop the old department_id foreign key and column
        if (Schema::hasColumn('production_lines', 'department_id')) {
            try {
                Schema::table('production_lines', function (Blueprint $table) {
                    $table->dropForeign(['department_id']);
                });
            } catch (\Exception $e) {
                // FK may not exist
            }
            Schema::table('production_lines', function (Blueprint $table) {
                $table->dropColumn('department_id');
            });
        }
    }

    private function migrateDepartmentToDivision(): void
    {
        $divisionMap = DB::table('divisions')->pluck('id', 'division');
        $deptDivisionMap = [
            'Sewing' => 'Cutting Press',
            'Finishing' => 'Finishing Lines',
            'Cutting Laser' => 'Cutting Press',
            'Cutting Gerber' => 'Cutting Press',
            'Embroidery' => 'Reverse Skin',
            'Warehouse' => 'Stuffing',
            'Packing' => 'Finishing Lines',
            'Finished Goods' => 'Finishing Lines',
        ];

        $lines = DB::table('production_lines')->get();
        foreach ($lines as $line) {
            $dept = DB::table('departments')->where('id', $line->department_id)->first();
            $divId = null;
            if ($dept && isset($deptDivisionMap[$dept->department_name]) && isset($divisionMap[$deptDivisionMap[$dept->department_name]])) {
                $divId = $divisionMap[$deptDivisionMap[$dept->department_name]];
            }
            if (!$divId) {
                // Fallback: use first active division
                $divId = DB::table('divisions')->where('status', 'active')->value('id');
            }
            DB::table('production_lines')
                ->where('id', $line->id)
                ->update(['division_id' => $divId]);
        }
    }

    public function down(): void
    {
        Schema::table('production_lines', function (Blueprint $table) {
            $table->foreignId('department_id')
                ->nullable()
                ->after('id')
                ->constrained('departments')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });

        Schema::table('production_lines', function (Blueprint $table) {
            $table->dropForeign(['division_id']);
            $table->dropColumn('division_id');
        });
    }
};
