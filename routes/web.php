<?php

use App\Http\Controllers\CredentialController;
use App\Http\Controllers\ProfileController;
use App\Models\ActivityLog;
use App\Models\Article;
use App\Models\ComponentsPanel;
use App\Models\Department;
use App\Models\Destination;
use App\Models\Division;
use App\Models\Factory;
use App\Models\FailureMode;
use App\Models\Gender;
use App\Models\GsdCategory;
use App\Models\GsdElement;
use App\Models\LoginLog;
use App\Models\MachineNumber;
use App\Models\MachineType;
use App\Models\Mechanic;
use App\Models\EducationalLevel;
use App\Models\StatusPkwtt;
use App\Models\Operator;
use App\Models\Process;
use App\Models\ProductionLine;
use App\Models\ProductionRole;
use App\Models\Section;
use App\Models\Shift;
use App\Models\SkillGrading;
use App\Models\SparePart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

// Activity logging helper
function logActivity(string $activity, ?string $module = null): void
{
    $user = auth()->user();
    if ($user) {
        ActivityLog::create([
            'user_id' => $user->id,
            'username' => $user->username,
            'activity' => $activity,
            'module' => $module,
        ]);
    }
}

// Excel export formatting helper
function applyExcelFormatting(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet): void
{
    $sheet = $spreadsheet->getActiveSheet();
    $highestRow = $sheet->getHighestRow();
    $highestCol = $sheet->getHighestColumn();
    $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);

    $borderStyle = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
            ],
        ],
    ];
    $alignmentStyle = [
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
        ],
    ];

    $cellRange = 'A1:' . $highestCol . $highestRow;
    $sheet->getStyle($cellRange)->applyFromArray($borderStyle);
    $sheet->getStyle($cellRange)->applyFromArray($alignmentStyle);

    // Auto-width columns
    for ($col = 1; $col <= $highestColIndex; $col++) {
        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
        $sheet->getColumnDimension($colLetter)->setAutoSize(true);
    }
}

// Root URL redirect: authenticated -> home, guest -> login
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('home');
    }
    return redirect()->route('login');
});

Route::post('/language/{locale}', function (string $locale) {
    if (in_array($locale, ['en', 'id'])) {
        session(['locale' => $locale]);
    }
    return redirect()->back();
})->name('language.switch');

Route::middleware('auth')->group(function () {
    Route::get('/home', function () {
        $stats = [
            'articles' => Article::count(),
            'operators' => Operator::count(),
            'processes' => Process::count(),
            'process_versions' => \App\Models\ProcessVersion::count(),
            'gsd_categories' => \App\Models\GsdCategory::count(),
            'gsd_elements' => \App\Models\GsdElement::count(),
        ];

        return view('home', compact('stats'));
    })->name('home');

    Route::get('/dashboard', function () {
        return redirect()->route('home');
    })->name('dashboard');

    Route::get('/credentials', [CredentialController::class, 'index'])
        ->middleware('role:developer,admin')
        ->name('credentials.index');

    Route::post('/credentials', [CredentialController::class, 'store'])
        ->middleware('role:developer,admin')
        ->name('credentials.store');

    Route::get('/credentials/{user}/edit', [CredentialController::class, 'edit'])
        ->middleware('role:developer,admin')
        ->name('credentials.edit');

    Route::put('/credentials/{user}', [CredentialController::class, 'update'])
        ->middleware('role:developer,admin')
        ->name('credentials.update');

    Route::delete('/credentials/{user}', [CredentialController::class, 'destroy'])
        ->middleware('role:developer,admin')
        ->name('credentials.destroy');

    // Hard Delete - Centralized page (Developer only)
    Route::get('/hard-delete', function () {
        $masters = [
            ['key' => 'operators', 'label' => 'Operators', 'model' => \App\Models\Operator::class],
            ['key' => 'processes', 'label' => 'Processes', 'model' => \App\Models\Process::class],
            ['key' => 'destinations', 'label' => 'Destinations', 'model' => \App\Models\Destination::class],
            ['key' => 'articles', 'label' => 'Articles', 'model' => \App\Models\Article::class],
            ['key' => 'gsd_elements', 'label' => 'GSD Elements', 'model' => \App\Models\GsdElement::class],
            ['key' => 'factories', 'label' => 'Factories', 'model' => \App\Models\Factory::class],
            ['key' => 'departments', 'label' => 'Departments', 'model' => \App\Models\Department::class],
            ['key' => 'divisions', 'label' => 'Divisions', 'model' => \App\Models\Division::class],
            ['key' => 'sections', 'label' => 'Sections', 'model' => \App\Models\Section::class],
            ['key' => 'production_lines', 'label' => 'Production Lines', 'model' => \App\Models\ProductionLine::class],
            ['key' => 'mechanics', 'label' => 'Mechanics', 'model' => \App\Models\Mechanic::class],
            ['key' => 'failure_modes', 'label' => 'Failure Modes', 'model' => \App\Models\FailureMode::class],
            ['key' => 'spare_parts', 'label' => 'Spare Parts', 'model' => \App\Models\SparePart::class],
            ['key' => 'machine_types', 'label' => 'Machine Types', 'model' => \App\Models\MachineType::class],
            ['key' => 'machine_numbers', 'label' => 'Machine Numbers', 'model' => \App\Models\MachineNumber::class],
            ['key' => 'skill_gradings', 'label' => 'Skill Gradings', 'model' => \App\Models\SkillGrading::class],
            ['key' => 'components_panels', 'label' => 'Components Panels', 'model' => \App\Models\ComponentsPanel::class],
            ['key' => 'shifts', 'label' => 'Shifts', 'model' => \App\Models\Shift::class],
            ['key' => 'genders', 'label' => 'Genders', 'model' => \App\Models\Gender::class],
            ['key' => 'production_roles', 'label' => 'Production Roles', 'model' => \App\Models\ProductionRole::class],
            ['key' => 'educational_levels', 'label' => 'Educational Levels', 'model' => \App\Models\EducationalLevel::class],
            ['key' => 'status_pkwtt', 'label' => 'Status PKWTT', 'model' => \App\Models\StatusPkwtt::class],
        ];

        foreach ($masters as &$master) {
            $master['count'] = $master['model']::where('status', 'inactive')->count();
        }

        return view('hard-delete.index', compact('masters'));
    })->middleware('role:developer')->name('hard-delete.index');

    Route::delete('/hard-delete', function (Request $request) {
        $request->validate(['master_key' => 'required|string']);

        $map = [
            'operators' => \App\Models\Operator::class,
            'processes' => \App\Models\Process::class,
            'destinations' => \App\Models\Destination::class,
            'articles' => \App\Models\Article::class,
            'gsd_elements' => \App\Models\GsdElement::class,
            'factories' => \App\Models\Factory::class,
            'departments' => \App\Models\Department::class,
            'divisions' => \App\Models\Division::class,
            'sections' => \App\Models\Section::class,
            'production_lines' => \App\Models\ProductionLine::class,
            'mechanics' => \App\Models\Mechanic::class,
            'failure_modes' => \App\Models\FailureMode::class,
            'spare_parts' => \App\Models\SparePart::class,
            'machine_types' => \App\Models\MachineType::class,
            'machine_numbers' => \App\Models\MachineNumber::class,
            'skill_gradings' => \App\Models\SkillGrading::class,
            'components_panels' => \App\Models\ComponentsPanel::class,
            'shifts' => \App\Models\Shift::class,
            'genders' => \App\Models\Gender::class,
            'production_roles' => \App\Models\ProductionRole::class,
            'educational_levels' => \App\Models\EducationalLevel::class,
            'status_pkwtt' => \App\Models\StatusPkwtt::class,
        ];

        $key = $request->input('master_key');
        if (!isset($map[$key])) {
            return back()->with('error', 'Invalid data master.');
        }

        $count = $map[$key]::where('status', 'inactive')->count();
        if ($count === 0) {
            return back()->with('error', 'No inactive records to delete.');
        }

        try {
            $deleted = $map[$key]::where('status', 'inactive')->delete();
            logActivity("Hard deleted {$deleted} inactive record(s) from " . str_replace('_', ' ', $key), 'Hard Delete');
            return back()->with('success', "Permanently deleted {$deleted} inactive record(s) from " . str_replace('_', ' ', $key) . ".");
        } catch (\Illuminate\Database\QueryException $e) {
            $label = str_replace('_', ' ', $key);
            if ($e->getCode() == 23000) {
                return back()->with('error', "Cannot delete inactive {$label} — some records are still referenced by other data. Remove dependent records first.");
            }
            return back()->with('error', "Failed to delete inactive {$label}: " . $e->getMessage());
        } catch (\Exception $e) {
            return back()->with('error', "Failed to delete: " . $e->getMessage());
        }
    })->middleware('role:developer')->name('hard-delete.destroy');

    Route::get('/developer', function () {
        return view('developer');
    })->middleware('role:developer')->name('developer');

    Route::get('/admin', function () {
        return view('admin');
    })->middleware('role:developer,admin')->name('admin');

    Route::get('/viewer', function () {
        return view('viewer');
    })->middleware('role:developer,admin,viewer')->name('viewer');

    // ==================== DEVELOPER-ONLY: LOGS ====================
    Route::middleware('role:developer')->prefix('system/logs')->name('system.')->group(function () {
        Route::get('/login-logs', function (Request $request) {
            $logs = LoginLog::orderByDesc('created_at')->paginate(50);
            return view('system.login-logs', compact('logs'));
        })->name('login-logs');

        Route::delete('/login-logs/clear', function () {
            LoginLog::query()->delete();
            logActivity('Cleared all login logs', 'Login Logs');
            return redirect()->route('system.login-logs')->with('success', 'Login logs cleared.');
        })->name('login-logs.clear');

        Route::get('/activity-logs', function (Request $request) {
            $logs = ActivityLog::orderByDesc('created_at')->paginate(50);
            return view('system.activity-logs', compact('logs'));
        })->name('activity-logs');

        Route::delete('/activity-logs/clear', function () {
            ActivityLog::query()->delete();
            logActivity('Cleared all activity logs', 'Activity Logs');
            return redirect()->route('system.activity-logs')->with('success', 'Activity logs cleared.');
        })->name('activity-logs.clear');
    });

    // ==================== DEVELOPER-ONLY: CLEAR CACHE ====================
    Route::middleware('role:developer')->get('/system/clear-cache', function () {
        $cachePath = storage_path('framework/cache/data');
        $cacheSize = 0;
        if (is_dir($cachePath)) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($cachePath, \RecursiveDirectoryIterator::SKIP_DOTS));
            foreach ($files as $file) {
                $cacheSize += $file->getSize();
            }
        }
        $cacheSizeFormatted = $cacheSize > 1048576
            ? round($cacheSize / 1048576, 2) . ' MB'
            : ($cacheSize > 1024
                ? round($cacheSize / 1024, 2) . ' KB'
                : $cacheSize . ' bytes');
        return view('system.clear-cache', compact('cacheSizeFormatted'));
    })->name('system.clear-cache');

    Route::middleware('role:developer')->post('/system/clear-cache', function () {
        try {
            \Artisan::call('cache:clear');
            \Artisan::call('config:clear');
            \Artisan::call('route:clear');
            \Artisan::call('view:clear');
            return redirect()->route('system.clear-cache')->with('success', 'Application cache cleared successfully.');
        } catch (\Exception $e) {
            return redirect()->route('system.clear-cache')->with('error', 'Failed to clear cache: ' . $e->getMessage());
        }
    })->name('system.clear-cache.execute');

    // ==================== DEVELOPER-ONLY: SPEED TEST ====================
    Route::middleware('role:developer')->get('/system/speed-test', function () {
        return view('system.speed-test');
    })->name('system.speed-test');

    Route::middleware('role:developer')->post('/system/speed-test', function () {
        // DB connection test
        $dbStart = microtime(true);
        try {
            \DB::connection()->getPdo();
            $dbTime = round((microtime(true) - $dbStart) * 1000, 2);
        } catch (\Exception $e) {
            $dbTime = -1;
        }

        // Query test
        $queryStart = microtime(true);
        \DB::table('processes')->count();
        $queryTime = round((microtime(true) - $queryStart) * 1000, 2);

        $totalTime = round($dbTime + $queryTime, 2);

        $results = [
            'db_time' => $dbTime,
            'query_time' => $queryTime,
            'total_time' => $totalTime,
            'database' => \Config::get('database.connections.mysql.database'),
            'driver' => \Config::get('database.default'),
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'server_time' => now()->format('Y-m-d H:i:s'),
        ];

        return redirect()->route('system.speed-test')->with('speedResults', $results);
    })->name('system.speed-test.execute');

    Route::middleware('role:developer,admin,viewer')->prefix('master-data')->name('master-data.')->group(function () {
        Route::get('/processes', function (Request $request) {
            $search = trim((string) $request->query('search', ''));
            $filterColumn = (string) $request->query('filter_column', '');
            $filterValue = (string) $request->query('filter_value', '');
            $showInactive = $request->boolean('show_inactive');
            $sort = $request->query('sort', 'process_name');
            $direction = $request->query('direction', 'asc');

            $sortableColumns = ['process_name', 'status'];
            if (!in_array($sort, $sortableColumns))
                $sort = 'process_name';
            if (!in_array($direction, ['asc', 'desc']))
                $direction = 'asc';

            $processes = Process::with(['versions.gsdElements', 'versions.gsdElement'])
                ->when(!$showInactive, fn($q) => $q->where('status', 'active'))
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('process_name', 'like', "%{$search}%")
                            ->orWhereHas('versions', function ($q) use ($search) {
                                $q->where('version_number', 'like', "%{$search}%")
                                    ->orWhereHas('gsdElements', function ($q) use ($search) {
                                        $q->where('code', 'like', "%{$search}%")
                                            ->orWhere('element_name', 'like', "%{$search}%");
                                    });
                            });
                    });
                })
                ->when($filterColumn === 'process_name' && $filterValue !== '', fn($q) => $q->where('process_name', $filterValue))
                ->when($filterColumn === 'version_number' && $filterValue !== '', fn($q) => $q->whereHas('versions', fn($q2) => $q2->where('version_number', $filterValue)))
                ->when($filterColumn === 'gsd_code' && $filterValue !== '', fn($q) => $q->whereHas('versions.gsdElements', fn($q2) => $q2->where('code', $filterValue)))
                ->orderBy($sort, $direction)
                ->get();

            $filterValues = [
                'process_name' => Process::whereNotNull('process_name')->distinct()->orderBy('process_name')->pluck('process_name')->toArray(),
                'version_number' => \App\Models\ProcessVersion::whereNotNull('version_number')->distinct()->orderBy('version_number')->pluck('version_number')->toArray(),
                'gsd_code' => GsdElement::whereNotNull('code')->distinct()->orderBy('code')->pluck('code')->toArray(),
            ];

            $gsdCategories = GsdCategory::where('status', 'active')->orderBy('category_name')->get();
            $gsdElements = GsdElement::with('gsdCategory')
                ->where('status', 'active')
                ->orderBy('element_name')
                ->get();

            $totalCount = $processes->count();

            return view('master-data.processes', compact('processes', 'gsdCategories', 'gsdElements', 'search', 'filterColumn', 'filterValue', 'filterValues', 'showInactive', 'sort', 'direction', 'totalCount'));
        })->name('processes');

        Route::post('/processes', function (Request $request) {
            $data = $request->validate([
                'process_name' => ['required', 'string', 'max:200'],
                'version_number' => ['nullable', 'integer', 'min:1'],
                'gsd_element_ids' => ['nullable', 'array'],
                'gsd_element_ids.*' => ['integer', 'distinct', 'exists:gsd_elements,id'],
            ]);

            $process = Process::create([
                'process_name' => $data['process_name'],
                'description' => null,
                'status' => 'active',
            ]);

            $version = $process->versions()->create([
                'version_number' => $data['version_number'] ?? 1,
                'notes' => null,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            $elementIds = array_values(array_unique($data['gsd_element_ids'] ?? []));
            $version->gsdElements()->sync($elementIds);

            logActivity('Created process: ' . $data['process_name'], 'Processes');
            return redirect()->route('master-data.processes')->with('success', 'Process created successfully.');
        })->name('processes.store');

        Route::put('/processes/{process}', function (Request $request, Process $process) {
            $data = $request->validate([
                'process_name' => ['required', 'string', 'max:200'],
                'version_number' => ['required', 'integer', 'min:1'],
                'gsd_element_ids' => ['nullable', 'array'],
                'gsd_element_ids.*' => ['integer', 'distinct', 'exists:gsd_elements,id'],
            ]);

            $process->update([
                'process_name' => $data['process_name'],
            ]);

            $version = $process->versions()->orderByDesc('version_number')->first();
            if (!$version) {
                $version = $process->versions()->create([
                    'version_number' => $data['version_number'],
                    'status' => 'draft',
                    'created_by' => auth()->id(),
                ]);
            } else {
                $version->update(['version_number' => $data['version_number']]);
            }
            $version->gsdElements()->sync(array_values(array_unique($data['gsd_element_ids'] ?? [])));

            logActivity('Updated process: ' . $data['process_name'], 'Processes');
            return redirect()->route('master-data.processes')->with('success', 'Process updated successfully.');
        })->name('processes.update');

        Route::patch('/processes/{process}/deactivate', function (Process $process) {
            if ($process->versions()->whereHas('ptmsReports')->exists()) {
                return back()->with('error', 'This process cannot be deactivated because it is used by historical reports.');
            }
            $process->update(['status' => 'inactive']);
            logActivity('Deactivated process: ' . $process->process_name, 'Processes');
            return redirect()->route('master-data.processes')->with('success', 'Process deactivated successfully.');
        })->name('processes.destroy');

        // Process Bulk Delete (soft)
        Route::patch('/processes/bulk-deactivate', function (Request $request) {
            $ids = $request->input('ids', []);
            if (empty($ids)) {
                return redirect()->route('master-data.processes')->with('error', 'No records selected.');
            }
            $count = Process::whereIn('id', $ids)->update(['status' => 'inactive', 'updated_at' => now()]);
            logActivity('Bulk deactivated ' . $count . ' processes', 'Processes');
            return redirect()->route('master-data.processes')->with('success', $count . ' record(s) marked as inactive.');
        })->name('processes.bulk-deactivate');

        // Process Hard Delete
        Route::delete('/processes/hard-delete', function (Request $request) {
            $ids = $request->input('ids', []);
            if (empty($ids)) {
                return redirect()->route('master-data.processes')->with('error', 'No records selected.');
            }
            $toDelete = Process::whereIn('id', $ids)->where('status', 'inactive')->pluck('id')->toArray();
            $skipped = count($ids) - count($toDelete);
            $deleted = 0;
            foreach ($toDelete as $id) {
                $proc = Process::find($id);
                if ($proc && !$proc->ptmsReports()->exists()) {
                    $proc->delete();
                    $deleted++;
                }
            }
            $msg = $deleted . ' process(es) permanently deleted.';
            if ($skipped > 0) {
                $msg .= ' ' . $skipped . ' skipped (active or have dependencies).';
            }
            logActivity('Hard deleted ' . $deleted . ' processes', 'Processes');
            return redirect()->route('master-data.processes')->with('success', $msg);
        })->name('processes.hard-delete');

        // Process Import
        Route::post('/processes/import', function (Request $request) {
            $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv']]);
            $path = $request->file('file')->store('temp');
            $fullPath = storage_path('app/' . $path);
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
            $rows = $spreadsheet->getActiveSheet()->toArray();
            $header = array_map('strtolower', array_map('trim', $rows[0]));
            $nameIdx = array_search('process name', $header);
            $versionIdx = array_search('version', $header);
            $imported = 0;
            for ($i = 1; $i < count($rows); $i++) {
                $name = trim($rows[$i][$nameIdx] ?? '');
                if ($name === '')
                    continue;
                $process = Process::firstOrCreate(
                    ['process_name' => $name],
                    ['description' => null, 'status' => 'active']
                );
                $versionNum = intval(trim($rows[$i][$versionIdx] ?? '1')) ?: 1;
                $process->versions()->firstOrCreate(
                    ['version_number' => $versionNum],
                    ['notes' => null, 'status' => 'draft', 'created_by' => auth()->id()]
                );
                $imported++;
            }
            @unlink($fullPath);
            logActivity("Imported {$imported} processes from Excel", 'Processes');
            return redirect()->route('master-data.processes')->with('success', "Imported {$imported} processes.");
        })->name('processes.import');

        // Process Export
        Route::get('/processes/export', function () {
            $processes = Process::with(['versions.gsdElements'])->where('status', 'active')->orderBy('process_name')->get();
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValue('A1', 'No')->setCellValue('B1', 'Process Name')->setCellValue('C1', 'Version')->setCellValue('D1', 'GSD Codes');
            $row = 2;
            foreach ($processes as $i => $process) {
                foreach ($process->versions as $version) {
                    $gsdCodes = $version->gsdElements->pluck('code')->join(', ');
                    $sheet->setCellValue("A{$row}", $row - 1)->setCellValue("B{$row}", $process->process_name)->setCellValue("C{$row}", $version->version_number)->setCellValue("D{$row}", $gsdCodes);
                    $row++;
                }
            }
            applyExcelFormatting($spreadsheet);
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $tempFile = tempnam(sys_get_temp_dir(), 'export') . '.xlsx';
            $writer->save($tempFile);
            logActivity('Exported processes to Excel', 'Processes');
            return response()->download($tempFile, 'processes.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
        })->name('processes.export');

        Route::get('/operators', function (Request $request) {
            $search = trim((string) $request->query('search', ''));
            $filterColumn = (string) $request->query('filter_column', '');
            $filterValue = (string) $request->query('filter_value', '');
            $showInactive = $request->boolean('show_inactive');
            $sort = $request->query('sort', 'operator_name');
            $direction = $request->query('direction', 'asc');

            // Additional filter params
            $dateFrom = $request->query('date_from', '');
            $dateTo = $request->query('date_to', '');

            $sortableColumns = ['operator_name', 'nik_karyawan', 'gender', 'role', 'start_date', 'date_of_birth', 'status', 'status_pkwtt', 'educational_level', 'factory', 'department', 'division', 'section', 'line'];
            if (!in_array($sort, $sortableColumns))
                $sort = 'operator_name';
            if (!in_array($direction, ['asc', 'desc']))
                $direction = 'asc';

            // Map relationship sort names to actual columns
            $sortMap = [
                'status_pkwtt' => ['status_pkwtt', 'pkwtt'],
                'educational_level' => ['educational_levels', 'level'],
                'factory' => ['factories', 'factory_name'],
                'department' => ['departments', 'department_name'],
                'division' => ['divisions', 'division'],
                'section' => ['sections', 'section'],
                'line' => ['production_lines', 'line_name'],
            ];

            $operators = Operator::with(['statusPkwtt', 'educationalLevel', 'factory', 'department', 'division', 'section', 'productionLine'])
                ->when(!$showInactive, fn($q) => $q->where('status', 'active'))
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('operator_name', 'like', "%{$search}%")
                            ->orWhere('nik_karyawan', 'like', "%{$search}%")
                            ->orWhere('gender', 'like', "%{$search}%")
                            ->orWhere('role', 'like', "%{$search}%")
                            ->orWhereHas('statusPkwtt', fn($q2) => $q2->where('pkwtt', 'like', "%{$search}%"))
                            ->orWhereHas('educationalLevel', fn($q2) => $q2->where('level', 'like', "%{$search}%"))
                            ->orWhereHas('factory', fn($q2) => $q2->where('factory_name', 'like', "%{$search}%"))
                            ->orWhereHas('department', fn($q2) => $q2->where('department_name', 'like', "%{$search}%"))
                            ->orWhereHas('division', fn($q2) => $q2->where('division', 'like', "%{$search}%"))
                            ->orWhereHas('section', fn($q2) => $q2->where('section', 'like', "%{$search}%"))
                            ->orWhereHas('productionLine', fn($q2) => $q2->where('line_name', 'like', "%{$search}%"));
                    });
                })
                ->when($filterColumn === 'operator_name' && $filterValue !== '', fn($q) => $q->where('operator_name', $filterValue))
                ->when($filterColumn === 'nik_karyawan' && $filterValue !== '', fn($q) => $q->where('nik_karyawan', $filterValue))
                ->when($filterColumn === 'gender' && $filterValue !== '', fn($q) => $q->where('gender', $filterValue))
                ->when($filterColumn === 'role' && $filterValue !== '', fn($q) => $q->where('role', $filterValue))
                ->when($filterColumn === 'status_pkwtt' && $filterValue !== '', fn($q) => $q->whereHas('statusPkwtt', fn($q2) => $q2->where('pkwtt', $filterValue)))
                ->when($filterColumn === 'educational_level' && $filterValue !== '', fn($q) => $q->whereHas('educationalLevel', fn($q2) => $q2->where('level', $filterValue)))
                ->when($filterColumn === 'factory' && $filterValue !== '', fn($q) => $q->whereHas('factory', fn($q2) => $q2->where('factory_name', $filterValue)))
                ->when($filterColumn === 'department' && $filterValue !== '', fn($q) => $q->whereHas('department', fn($q2) => $q2->where('department_name', $filterValue)))
                ->when($filterColumn === 'division' && $filterValue !== '', fn($q) => $q->whereHas('division', fn($q2) => $q2->where('division', $filterValue)))
                ->when($filterColumn === 'section' && $filterValue !== '', fn($q) => $q->whereHas('section', fn($q2) => $q2->where('section', $filterValue)))
                ->when($filterColumn === 'line' && $filterValue !== '', fn($q) => $q->whereHas('productionLine', fn($q2) => $q2->where('line_name', $filterValue)))
                // Date range filters
                ->when($filterColumn === 'start_date' && $dateFrom !== '', fn($q) => $q->where('start_date', '>=', $dateFrom))
                ->when($filterColumn === 'start_date' && $dateTo !== '', fn($q) => $q->where('start_date', '<=', $dateTo))
                ->when($filterColumn === 'date_of_birth' && $dateFrom !== '', fn($q) => $q->where('date_of_birth', '>=', $dateFrom))
                ->when($filterColumn === 'date_of_birth' && $dateTo !== '', fn($q) => $q->where('date_of_birth', '<=', $dateTo))
                ->when(isset($sortMap[$sort]), function ($q) use ($sort, $direction, $sortMap) {
                    [$table, $column] = $sortMap[$sort];
                    $foreignKey = $sort === 'line' ? 'line_id' : "{$sort}_id";
                    $q->leftJoin($table, "operators.{$foreignKey}", '=', "{$table}.id")
                        ->orderBy("{$table}.{$column}", $direction)
                        ->select('operators.*');
                }, fn($q) => $q->orderBy($sort, $direction))
                ->get();

            $totalCount = $operators->count();

            $filterValues = [
                'operator_name' => Operator::whereNotNull('operator_name')->distinct()->orderBy('operator_name')->pluck('operator_name')->toArray(),
                'nik_karyawan' => Operator::whereNotNull('nik_karyawan')->distinct()->orderBy('nik_karyawan')->pluck('nik_karyawan')->toArray(),
                'gender' => Operator::whereNotNull('gender')->distinct()->orderBy('gender')->pluck('gender')->toArray(),
                'role' => Operator::whereNotNull('role')->distinct()->orderBy('role')->pluck('role')->toArray(),
                'status_pkwtt' => \App\Models\StatusPkwtt::where('status', 'active')->orderBy('pkwtt')->pluck('pkwtt')->toArray(),
                'educational_level' => \App\Models\EducationalLevel::where('status', 'active')->orderBy('level')->pluck('level')->toArray(),
                'factory' => \App\Models\Factory::where('status', 'active')->orderBy('factory_name')->pluck('factory_name')->toArray(),
                'department' => \App\Models\Department::where('status', 'active')->orderBy('department_name')->pluck('department_name')->toArray(),
                'division' => \App\Models\Division::where('status', 'active')->orderBy('division')->pluck('division')->toArray(),
                'section' => \App\Models\Section::where('status', 'active')->orderBy('section')->pluck('section')->toArray(),
                'line' => \App\Models\ProductionLine::where('status', 'active')->orderBy('line_name')->pluck('line_name')->toArray(),
            ];

            $genders = Gender::where('status', 'active')->orderBy('gender')->get();
            $productionRoles = ProductionRole::where('status', 'active')->orderBy('production_role')->get();
            $statusPkwttList = \App\Models\StatusPkwtt::where('status', 'active')->orderBy('pkwtt')->get();
            $educationalLevels = \App\Models\EducationalLevel::where('status', 'active')->orderBy('level')->get();
            $factories = \App\Models\Factory::where('status', 'active')->orderBy('factory_name')->get();
            $departments = \App\Models\Department::where('status', 'active')->orderBy('department_name')->get();
            $divisions = \App\Models\Division::where('status', 'active')->orderBy('division')->get();
            $sections = \App\Models\Section::where('status', 'active')->orderBy('section')->get();
            $productionLines = \App\Models\ProductionLine::where('status', 'active')->orderBy('line_name')->get();
            return view('master-data.operators', compact('operators', 'genders', 'productionRoles', 'statusPkwttList', 'educationalLevels', 'factories', 'departments', 'divisions', 'sections', 'productionLines', 'search', 'filterColumn', 'filterValue', 'filterValues', 'showInactive', 'sort', 'direction', 'totalCount', 'dateFrom', 'dateTo'));
        })->name('operators');

        Route::post('/operators', function (Request $request) {
            if (auth()->user()->role->role_name === 'viewer') {
                abort(403, 'Unauthorized. Viewer role is read-only.');
            }
            $data = $request->validate([
                'operator_name' => ['required', 'string', 'max:100'],
                'nik_karyawan' => ['nullable', 'string', 'max:50', 'unique:operators,nik_karyawan'],
                'gender' => ['nullable', 'string', 'max:30'],
                'role' => ['nullable', 'string', 'max:100'],
                'photo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
                'status_pkwtt_id' => ['nullable', 'exists:status_pkwtt,id'],
                'educational_level_id' => ['nullable', 'exists:educational_levels,id'],
                'start_date' => ['nullable', 'date'],
                'date_of_birth' => ['nullable', 'date'],
                'factory_id' => ['nullable', 'exists:factories,id'],
                'department_id' => ['nullable', 'exists:departments,id'],
                'division_id' => ['nullable', 'exists:divisions,id'],
                'section_id' => ['nullable', 'exists:sections,id'],
                'line_id' => ['nullable', 'exists:production_lines,id'],
            ]);
            $data['employee_number'] = 'OP-' . strtoupper(Str::random(12));
            $data['photo_path'] = $request->hasFile('photo')
                ? $request->file('photo')->store('operators', 'public')
                : null;
            unset($data['photo']);
            $data['operator_name'] = strtoupper($data['operator_name']);
            if (!empty($data['nik_karyawan']))
                $data['nik_karyawan'] = strtoupper($data['nik_karyawan']);
            if (!empty($data['gender']))
                $data['gender'] = strtoupper($data['gender']);
            if (!empty($data['role']))
                $data['role'] = strtoupper($data['role']);
            Operator::create($data + ['status' => 'active']);
            logActivity('Created employee: ' . $data['operator_name'], 'Employees');
            return redirect()->route('master-data.operators')->with('success', 'Employee created successfully.');
        })->name('operators.store');

        Route::put('/operators/{operator}', function (Request $request, Operator $operator) {
            if (auth()->user()->role->role_name === 'viewer') {
                abort(403, 'Unauthorized. Viewer role is read-only.');
            }
            $data = $request->validate([
                'operator_name' => ['required', 'string', 'max:100'],
                'nik_karyawan' => ['nullable', 'string', 'max:50', 'unique:operators,nik_karyawan,' . $operator->id],
                'gender' => ['nullable', 'string', 'max:30'],
                'role' => ['nullable', 'string', 'max:100'],
                'photo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
                'status_pkwtt_id' => ['nullable', 'exists:status_pkwtt,id'],
                'educational_level_id' => ['nullable', 'exists:educational_levels,id'],
                'start_date' => ['nullable', 'date'],
                'date_of_birth' => ['nullable', 'date'],
                'factory_id' => ['nullable', 'exists:factories,id'],
                'department_id' => ['nullable', 'exists:departments,id'],
                'division_id' => ['nullable', 'exists:divisions,id'],
                'section_id' => ['nullable', 'exists:sections,id'],
                'line_id' => ['nullable', 'exists:production_lines,id'],
            ]);
            if ($request->hasFile('photo')) {
                $oldPhoto = $operator->photo_path;
                $data['photo_path'] = $request->file('photo')->store('operators', 'public');
                if ($oldPhoto && Storage::disk('public')->exists($oldPhoto)) {
                    Storage::disk('public')->delete($oldPhoto);
                }
            }
            unset($data['photo']);
            $data['operator_name'] = strtoupper($data['operator_name']);
            if (!empty($data['nik_karyawan']))
                $data['nik_karyawan'] = strtoupper($data['nik_karyawan']);
            if (!empty($data['gender']))
                $data['gender'] = strtoupper($data['gender']);
            if (!empty($data['role']))
                $data['role'] = strtoupper($data['role']);
            $operator->update($data);
            logActivity('Updated employee: ' . $data['operator_name'], 'Employees');
            return redirect()->route('master-data.operators')->with('success', 'Employee updated successfully.');
        })->name('operators.update');

        Route::patch('/operators/{operator}/deactivate', function (Operator $operator) {
            if (auth()->user()->role->role_name === 'viewer') {
                abort(403, 'Unauthorized. Viewer role is read-only.');
            }
            if ($operator->ptmsReports()->exists()) {
                return back()->with('error', 'This employee cannot be deactivated because historical reports reference the record.');
            }
            $operator->update(['status' => 'inactive']);
            logActivity('Deactivated employee: ' . $operator->operator_name, 'Employees');
            return redirect()->route('master-data.operators')->with('success', 'Employee deactivated successfully.');
        })->name('operators.destroy');

        // Employee Bulk Delete (soft)
        Route::patch('/operators/bulk-deactivate', function (Request $request) {
            if (auth()->user()->role->role_name === 'viewer') {
                abort(403, 'Unauthorized. Viewer role is read-only.');
            }
            $ids = $request->input('ids', []);
            if (empty($ids)) {
                return redirect()->route('master-data.operators')->with('error', 'No records selected.');
            }
            $protectedIds = \App\Models\PtmsReport::whereIn('operator_id', $ids)->pluck('operator_id')->unique()->toArray();
            $deactivatableIds = array_diff($ids, $protectedIds);
            $count = Operator::whereIn('id', $deactivatableIds)->update(['status' => 'inactive', 'updated_at' => now()]);
            $msg = $count . ' employee(s) marked as inactive.';
            if (count($protectedIds) > 0) {
                $msg .= ' ' . count($protectedIds) . ' skipped (have historical reports).';
            }
            logActivity('Bulk deactivated ' . $count . ' employees', 'Employees');
            return redirect()->route('master-data.operators')->with('success', $msg);
        })->name('operators.bulk-deactivate');

        // Employee Hard Delete
        Route::delete('/operators/hard-delete', function (Request $request) {
            if (auth()->user()->role->role_name !== 'developer') {
                abort(403, 'Only developers can perform hard deletes.');
            }
            $ids = $request->input('ids', []);
            if (empty($ids)) {
                return redirect()->route('master-data.operators')->with('error', 'No records selected.');
            }
            // Only allow deleting inactive records
            $toDelete = Operator::whereIn('id', $ids)->where('status', 'inactive')->pluck('id')->toArray();
            $skipped = count($ids) - count($toDelete);
            $deleted = 0;
            foreach ($toDelete as $id) {
                $op = Operator::find($id);
                if ($op && !$op->ptmsReports()->exists()) {
                    if ($op->photo_path && Storage::disk('public')->exists($op->photo_path)) {
                        Storage::disk('public')->delete($op->photo_path);
                    }
                    $op->delete();
                    $deleted++;
                }
            }
            $msg = $deleted . ' employee(s) permanently deleted.';
            if ($skipped > 0) {
                $msg .= ' ' . $skipped . ' skipped (active or have dependencies).';
            }
            logActivity('Hard deleted ' . $deleted . ' employees', 'Employees');
            return redirect()->route('master-data.operators')->with('success', $msg);
        })->name('operators.hard-delete');

        // Employee Import
        Route::post('/operators/import', function (Request $request) {
            if (auth()->user()->role->role_name === 'viewer') {
                abort(403, 'Unauthorized. Viewer role is read-only.');
            }
            $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv']]);
            $path = $request->file('file')->store('temp');
            $fullPath = storage_path('app/' . $path);
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
            $rows = $spreadsheet->getActiveSheet()->toArray();
            $header = array_map('strtolower', array_map('trim', $rows[0]));
            $nameIdx = array_search('operator name', $header);
            $nikIdx = array_search('nik karyawan', $header);
            $genderIdx = array_search('gender', $header);
            $roleIdx = array_search('role', $header);
            $statusPkwttIdx = array_search('status pkwtt', $header);
            $eduLevelIdx = array_search('educational level', $header);
            $startDateIdx = array_search('start date', $header);
            $dobIdx = array_search('date of birth', $header);
            $factoryIdx = array_search('factory', $header);
            $departmentIdx = array_search('department', $header);
            $divisionIdx = array_search('division', $header);
            $sectionIdx = array_search('section', $header);
            $lineIdx = array_search('line', $header);
            $imported = 0;
            for ($i = 1; $i < count($rows); $i++) {
                $name = trim($rows[$i][$nameIdx] ?? '');
                if ($name === '')
                    continue;
                $statusPkwttId = null;
                if ($statusPkwttIdx !== false) {
                    $pkwttName = trim($rows[$i][$statusPkwttIdx] ?? '');
                    if ($pkwttName !== '') {
                        $pkwtt = \App\Models\StatusPkwtt::where('pkwtt', $pkwttName)->where('status', 'active')->first();
                        $statusPkwttId = $pkwtt?->id;
                    }
                }
                $eduLevelId = null;
                if ($eduLevelIdx !== false) {
                    $eduName = trim($rows[$i][$eduLevelIdx] ?? '');
                    if ($eduName !== '') {
                        $edu = \App\Models\EducationalLevel::where('level', $eduName)->where('status', 'active')->first();
                        $eduLevelId = $edu?->id;
                    }
                }
                $startDate = null;
                if ($startDateIdx !== false) {
                    $sd = trim($rows[$i][$startDateIdx] ?? '');
                    if ($sd !== '') {
                        try {
                            $startDate = \Carbon\Carbon::parse($sd)->format('Y-m-d');
                        } catch (\Exception $e) {
                        }
                    }
                }
                $dateOfBirth = null;
                if ($dobIdx !== false) {
                    $dob = trim($rows[$i][$dobIdx] ?? '');
                    if ($dob !== '') {
                        try {
                            $dateOfBirth = \Carbon\Carbon::parse($dob)->format('Y-m-d');
                        } catch (\Exception $e) {
                        }
                    }
                }
                // Resolve org IDs
                $factoryId = null;
                if ($factoryIdx !== false) {
                    $factoryName = trim($rows[$i][$factoryIdx] ?? '');
                    if ($factoryName !== '') {
                        $factory = \App\Models\Factory::where('factory_name', $factoryName)->where('status', 'active')->first();
                        $factoryId = $factory?->id;
                    }
                }
                $departmentId = null;
                if ($departmentIdx !== false) {
                    $deptName = trim($rows[$i][$departmentIdx] ?? '');
                    if ($deptName !== '') {
                        $dept = \App\Models\Department::where('department_name', $deptName)->where('status', 'active')->first();
                        $departmentId = $dept?->id;
                    }
                }
                $divisionId = null;
                if ($divisionIdx !== false) {
                    $divName = trim($rows[$i][$divisionIdx] ?? '');
                    if ($divName !== '') {
                        $div = \App\Models\Division::where('division', $divName)->where('status', 'active')->first();
                        $divisionId = $div?->id;
                    }
                }
                $sectionId = null;
                if ($sectionIdx !== false) {
                    $secName = trim($rows[$i][$sectionIdx] ?? '');
                    if ($secName !== '') {
                        $sec = \App\Models\Section::where('section', $secName)->where('status', 'active')->first();
                        $sectionId = $sec?->id;
                    }
                }
                $lineId = null;
                if ($lineIdx !== false) {
                    $lineName = trim($rows[$i][$lineIdx] ?? '');
                    if ($lineName !== '') {
                        $line = \App\Models\ProductionLine::where('line_name', $lineName)->where('status', 'active')->first();
                        $lineId = $line?->id;
                    }
                }
                Operator::updateOrInsert(
                    ['operator_name' => strtoupper($name)],
                    [
                        'nik_karyawan' => strtoupper(trim($rows[$i][$nikIdx] ?? '')),
                        'gender' => strtoupper(trim($rows[$i][$genderIdx] ?? '')),
                        'role' => strtoupper(trim($rows[$i][$roleIdx] ?? '')),
                        'status_pkwtt_id' => $statusPkwttId,
                        'educational_level_id' => $eduLevelId,
                        'start_date' => $startDate,
                        'date_of_birth' => $dateOfBirth,
                        'factory_id' => $factoryId,
                        'department_id' => $departmentId,
                        'division_id' => $divisionId,
                        'section_id' => $sectionId,
                        'line_id' => $lineId,
                        'employee_number' => 'OP-' . strtoupper(\Illuminate\Support\Str::random(12)),
                        'status' => 'active',
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
                $imported++;
            }
            @unlink($fullPath);
            logActivity("Imported {$imported} employees from Excel", 'Employees');
            return redirect()->route('master-data.operators')->with('success', "Imported {$imported} employees.");
        })->name('operators.import');

        // Employee Export
        Route::get('/operators/export', function () {
            $operators = Operator::with(['statusPkwtt', 'educationalLevel', 'factory', 'department', 'division', 'section', 'productionLine'])->where('status', 'active')->orderBy('operator_name')->get();
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValue('A1', 'No')->setCellValue('B1', 'Employee Name')->setCellValue('C1', 'NIK Karyawan')->setCellValue('D1', 'Gender')->setCellValue('E1', 'Role')->setCellValue('F1', 'Factory')->setCellValue('G1', 'Department')->setCellValue('H1', 'Division')->setCellValue('I1', 'Section')->setCellValue('J1', 'Line')->setCellValue('K1', 'Status PKWTT')->setCellValue('L1', 'Educational Level')->setCellValue('M1', 'Start Date')->setCellValue('N1', 'Date of Birth');
            foreach ($operators as $i => $op) {
                $row = $i + 2;
                $sheet->setCellValue("A{$row}", $i + 1)->setCellValue("B{$row}", $op->operator_name)->setCellValue("C{$row}", $op->nik_karyawan)->setCellValue("D{$row}", $op->gender)->setCellValue("E{$row}", $op->role)->setCellValue("F{$row}", $op->factory?->factory_name)->setCellValue("G{$row}", $op->department?->department_name)->setCellValue("H{$row}", $op->division?->division)->setCellValue("I{$row}", $op->section?->section)->setCellValue("J{$row}", $op->productionLine?->line_name)->setCellValue("K{$row}", $op->statusPkwtt?->pkwtt)->setCellValue("L{$row}", $op->educationalLevel?->level)->setCellValue("M{$row}", $op->start_date?->format('Y-m-d'))->setCellValue("N{$row}", $op->date_of_birth?->format('Y-m-d'));
            }
            applyExcelFormatting($spreadsheet);
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $tempFile = tempnam(sys_get_temp_dir(), 'export') . '.xlsx';
            $writer->save($tempFile);
            logActivity('Exported employees to Excel', 'Employees');
            return response()->download($tempFile, 'employees.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
        })->name('operators.export');

        // ==================== DESTINATIONS ====================
        Route::get('/destinations', function (Request $request) {
            $search = trim((string) $request->query('search', ''));
            $filterColumn = (string) $request->query('filter_column', '');
            $filterValue = (string) $request->query('filter_value', '');
            $showInactive = $request->boolean('show_inactive');
            $sort = $request->query('sort', 'destination');
            $direction = $request->query('direction', 'asc');

            $sortableColumns = ['destination', 'description', 'status'];
            if (!in_array($sort, $sortableColumns))
                $sort = 'destination';
            if (!in_array($direction, ['asc', 'desc']))
                $direction = 'asc';

            $destinations = Destination::when(!$showInactive, fn($q) => $q->where('status', 'active'))
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('destination', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
                })
                ->when($filterColumn === 'destination' && $filterValue !== '', fn($q) => $q->where('destination', $filterValue))
                ->orderBy($sort, $direction)
                ->get();

            $totalCount = $destinations->count();

            $filterValues = [
                'destination' => Destination::whereNotNull('destination')->distinct()->orderBy('destination')->pluck('destination')->toArray(),
            ];

            return view('master-data.destinations', compact('destinations', 'search', 'filterColumn', 'filterValue', 'filterValues', 'showInactive', 'sort', 'direction', 'totalCount'));
        })->name('destinations');

        Route::post('/destinations', function (Request $request) {
            $data = $request->validate([
                'destination' => ['required', 'string', 'max:100'],
                'description' => ['nullable', 'string', 'max:255'],
            ]);
            Destination::create($data + ['status' => 'active']);
            logActivity('Created destination: ' . $data['destination'], 'Destinations');
            return redirect()->route('master-data.destinations')->with('success', 'Destination created successfully.');
        })->name('destinations.store');

        Route::put('/destinations/{destination}', function (Request $request, Destination $destination) {
            $data = $request->validate([
                'destination' => ['required', 'string', 'max:100'],
                'description' => ['nullable', 'string', 'max:255'],
            ]);
            $destination->update($data);
            logActivity('Updated destination: ' . $data['destination'], 'Destinations');
            return redirect()->route('master-data.destinations')->with('success', 'Destination updated successfully.');
        })->name('destinations.update');

        Route::patch('/destinations/{destination}/deactivate', function (Destination $destination) {
            $destination->update(['status' => 'inactive']);
            logActivity('Deactivated destination: ' . $destination->destination, 'Destinations');
            return redirect()->route('master-data.destinations')->with('success', 'Destination marked as inactive.');
        })->name('destinations.destroy');

        // Destination Bulk Delete (soft)
        Route::patch('/destinations/bulk-deactivate', function (Request $request) {
            $ids = $request->input('ids', []);
            if (empty($ids)) {
                return redirect()->route('master-data.destinations')->with('error', 'No records selected.');
            }
            $count = Destination::whereIn('id', $ids)->update(['status' => 'inactive', 'updated_at' => now()]);
            logActivity('Bulk deactivated ' . $count . ' destinations', 'Destinations');
            return redirect()->route('master-data.destinations')->with('success', $count . ' record(s) marked as inactive.');
        })->name('destinations.bulk-deactivate');

        // Destination Hard Delete
        Route::delete('/destinations/hard-delete', function (Request $request) {
            $ids = $request->input('ids', []);
            if (empty($ids)) {
                return redirect()->route('master-data.destinations')->with('error', 'No records selected.');
            }
            $toDelete = Destination::whereIn('id', $ids)->where('status', 'inactive')->pluck('id')->toArray();
            $skipped = count($ids) - count($toDelete);
            $deleted = 0;
            foreach ($toDelete as $id) {
                $dest = Destination::find($id);
                if ($dest) {
                    $dest->delete();
                    $deleted++;
                }
            }
            $msg = $deleted . ' destination(s) permanently deleted.';
            if ($skipped > 0) {
                $msg .= ' ' . $skipped . ' skipped (active records cannot be hard deleted).';
            }
            logActivity('Hard deleted ' . $deleted . ' destinations', 'Destinations');
            return redirect()->route('master-data.destinations')->with('success', $msg);
        })->name('destinations.hard-delete');

        // Destination Import
        Route::post('/destinations/import', function (Request $request) {
            $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv']]);
            $path = $request->file('file')->store('temp');
            $fullPath = storage_path('app/' . $path);
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
            $rows = $spreadsheet->getActiveSheet()->toArray();
            $header = array_map('strtolower', array_map('trim', $rows[0]));
            $nameIdx = array_search('destination', $header);
            $descIdx = array_search('descriptions', $header);
            $imported = 0;
            for ($i = 1; $i < count($rows); $i++) {
                $name = trim($rows[$i][$nameIdx] ?? '');
                if ($name === '')
                    continue;
                Destination::updateOrInsert(
                    ['destination' => $name],
                    ['description' => trim($rows[$i][$descIdx] ?? ''), 'status' => 'active', 'updated_at' => now(), 'created_at' => now()]
                );
                $imported++;
            }
            @unlink($fullPath);
            logActivity("Imported {$imported} destinations from Excel", 'Destinations');
            return redirect()->route('master-data.destinations')->with('success', "Imported {$imported} destinations.");
        })->name('destinations.import');

        // Destination Export
        Route::get('/destinations/export', function () {
            $destinations = Destination::where('status', 'active')->orderBy('destination')->get();
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValue('A1', 'No')->setCellValue('B1', 'Destination')->setCellValue('C1', 'Descriptions');
            foreach ($destinations as $i => $d) {
                $row = $i + 2;
                $sheet->setCellValue("A{$row}", $i + 1)->setCellValue("B{$row}", $d->destination)->setCellValue("C{$row}", $d->description);
            }
            applyExcelFormatting($spreadsheet);
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $tempFile = tempnam(sys_get_temp_dir(), 'export') . '.xlsx';
            $writer->save($tempFile);
            logActivity('Exported destinations to Excel', 'Destinations');
            return response()->download($tempFile, 'destinations.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
        })->name('destinations.export');

        // Destination Autocomplete
        Route::get('/destinations/search', function (Request $request) {
            $q = trim((string) $request->query('q', ''));
            if ($q === '')
                return response()->json([]);
            return response()->json(
                Destination::where('status', 'active')
                    ->where(function ($query) use ($q) {
                        $query->where('destination', 'like', "%{$q}%")
                            ->orWhere('description', 'like', "%{$q}%");
                    })
                    ->orderBy('destination')
                    ->limit(10)
                    ->get()
                    ->map(fn($d) => [
                        'id' => $d->id,
                        'label' => $d->destination,
                        'description' => $d->description ?? '',
                    ])
            );
        })->name('destinations.search');

        Route::get('/articles', function (Request $request) {
            $search = trim((string) $request->query('search', ''));
            $filterColumn = (string) $request->query('filter_column', '');
            $filterValue = (string) $request->query('filter_value', '');
            $showInactive = $request->boolean('show_inactive');
            $sort = $request->query('sort', 'article_name');
            $direction = $request->query('direction', 'asc');

            $sortableColumns = ['article_name', 'status'];
            if (!in_array($sort, $sortableColumns))
                $sort = 'article_name';
            if (!in_array($direction, ['asc', 'desc']))
                $direction = 'asc';

            $articles = Article::when(!$showInactive, fn($q) => $q->where('status', 'active'))
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('article_name', 'like', "%{$search}%");
                    });
                })
                ->when($filterColumn === 'article_name' && $filterValue !== '', fn($q) => $q->where('article_name', $filterValue))
                ->orderBy($sort, $direction)
                ->get();

            $totalCount = $articles->count();

            $filterValues = [
                'article_name' => Article::whereNotNull('article_name')->distinct()->orderBy('article_name')->pluck('article_name')->toArray(),
            ];

            return view('master-data.articles', compact('articles', 'search', 'filterColumn', 'filterValue', 'filterValues', 'showInactive', 'sort', 'direction', 'totalCount'));
        })->name('articles');

        Route::post('/articles', function (Request $request) {
            $data = $request->validate([
                'article_name' => ['required', 'string', 'max:150'],
                'description' => ['nullable', 'string', 'max:255'],
                'photo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            ]);

            if ($request->hasFile('photo')) {
                $data['photo_path'] = $request->file('photo')->store('articles', 'public');
            }
            unset($data['photo']);

            $labelNumber = 'LBL-' . strtoupper(Str::random(10));

            Article::create([
                'article_name' => $data['article_name'],
                'destination' => '',
                'label_number' => $labelNumber,
                'label_number_quty' => $labelNumber . '17596',
                'description' => $data['description'] ?? null,
                'photo_path' => $data['photo_path'] ?? null,
                'status' => 'active',
            ]);

            logActivity('Created article: ' . $data['article_name'], 'Articles');
            return redirect()->route('master-data.articles')->with('success', 'Article created successfully.');
        })->name('articles.store');

        Route::put('/articles/{article}', function (Request $request, Article $article) {
            $data = $request->validate([
                'article_name' => ['required', 'string', 'max:150'],
                'description' => ['nullable', 'string', 'max:255'],
                'photo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            ]);

            if ($request->hasFile('photo')) {
                $oldPhoto = $article->photo_path;
                $data['photo_path'] = $request->file('photo')->store('articles', 'public');
                if ($oldPhoto && Storage::disk('public')->exists($oldPhoto)) {
                    Storage::disk('public')->delete($oldPhoto);
                }
            }

            $article->update([
                'article_name' => $data['article_name'],
                'description' => $data['description'] ?? null,
                'photo_path' => $data['photo_path'] ?? $article->photo_path,
            ]);

            logActivity('Updated article: ' . $data['article_name'], 'Articles');
            return redirect()->route('master-data.articles')->with('success', 'Article updated successfully.');
        })->name('articles.update');

        Route::patch('/articles/{article}/deactivate', function (Article $article) {
            if ($article->ptmsReports()->exists()) {
                return back()->with('error', 'This article cannot be deactivated because historical reports reference the record.');
            }
            $article->update(['status' => 'inactive']);
            logActivity('Deactivated article: ' . $article->article_name, 'Articles');
            return redirect()->route('master-data.articles')->with('success', 'Article deactivated successfully.');
        })->name('articles.destroy');

        // Article Bulk Delete (soft)
        Route::patch('/articles/bulk-deactivate', function (Request $request) {
            $ids = $request->input('ids', []);
            if (empty($ids)) {
                return redirect()->route('master-data.articles')->with('error', 'No records selected.');
            }
            $count = Article::whereIn('id', $ids)->update(['status' => 'inactive', 'updated_at' => now()]);
            logActivity('Bulk deactivated ' . $count . ' articles', 'Articles');
            return redirect()->route('master-data.articles')->with('success', $count . ' record(s) marked as inactive.');
        })->name('articles.bulk-deactivate');

        // Article Hard Delete
        Route::delete('/articles/hard-delete', function (Request $request) {
            $ids = $request->input('ids', []);
            if (empty($ids)) {
                return redirect()->route('master-data.articles')->with('error', 'No records selected.');
            }
            $toDelete = Article::whereIn('id', $ids)->where('status', 'inactive')->pluck('id')->toArray();
            $skipped = count($ids) - count($toDelete);
            $deleted = 0;
            foreach ($toDelete as $id) {
                $article = Article::find($id);
                if ($article && !$article->ptmsReports()->exists()) {
                    if ($article->photo_path && Storage::disk('public')->exists($article->photo_path)) {
                        Storage::disk('public')->delete($article->photo_path);
                    }
                    $article->delete();
                    $deleted++;
                }
            }
            $msg = $deleted . ' article(s) permanently deleted.';
            if ($skipped > 0) {
                $msg .= ' ' . $skipped . ' skipped (active or have dependencies).';
            }
            logActivity('Hard deleted ' . $deleted . ' articles', 'Articles');
            return redirect()->route('master-data.articles')->with('success', $msg);
        })->name('articles.hard-delete');

        // Article Import
        Route::post('/articles/import', function (Request $request) {
            $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv']]);
            $path = $request->file('file')->store('temp');
            $fullPath = storage_path('app/' . $path);
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
            $rows = $spreadsheet->getActiveSheet()->toArray();
            $header = array_map('strtolower', array_map('trim', $rows[0]));
            $nameIdx = array_search('article name', $header);
            $labelIdx = array_search('label number', $header);
            $destIdx = array_search('destination', $header);
            $descIdx = array_search('description', $header);
            $imported = 0;
            for ($i = 1; $i < count($rows); $i++) {
                $name = trim($rows[$i][$nameIdx] ?? '');
                if ($name === '')
                    continue;
                $labelNumber = ($labelIdx !== false) ? trim($rows[$i][$labelIdx] ?? '') : '';
                if ($labelNumber === '') {
                    $labelNumber = 'LBL-' . strtoupper(Str::random(10));
                }
                $destination = ($destIdx !== false) ? trim($rows[$i][$destIdx] ?? '') : '';
                $description = ($descIdx !== false) ? trim($rows[$i][$descIdx] ?? '') : null;
                $existing = Article::where('article_name', $name)->first();
                if ($existing) {
                    $existing->update([
                        'destination' => $destination !== '' ? $destination : $existing->destination,
                        'description' => $description ?? $existing->description,
                        'status' => 'active',
                    ]);
                } else {
                    Article::create([
                        'article_name' => $name,
                        'label_number' => $labelNumber,
                        'label_number_quty' => $labelNumber . '17596',
                        'destination' => $destination,
                        'description' => $description,
                        'status' => 'active',
                    ]);
                }
                $imported++;
            }
            @unlink($fullPath);
            logActivity("Imported {$imported} articles from Excel", 'Articles');
            return redirect()->route('master-data.articles')->with('success', "Imported {$imported} articles.");
        })->name('articles.import');

        // Article Export
        Route::get('/articles/export', function () {
            $articles = Article::where('status', 'active')->orderBy('article_name')->get();
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValue('A1', 'No')->setCellValue('B1', 'Article Name');
            foreach ($articles as $i => $a) {
                $row = $i + 2;
                $sheet->setCellValue("A{$row}", $i + 1)->setCellValue("B{$row}", $a->article_name);
            }
            applyExcelFormatting($spreadsheet);
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $tempFile = tempnam(sys_get_temp_dir(), 'export') . '.xlsx';
            $writer->save($tempFile);
            logActivity('Exported articles to Excel', 'Articles');
            return response()->download($tempFile, 'articles.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
        })->name('articles.export');

        // ==================== GSD ELEMENTS ====================

        Route::get('/gsd-elements', function (Request $request) {
            $search = trim((string) $request->query('search', ''));
            $filterColumn = (string) $request->query('filter_column', '');
            $filterValue = (string) $request->query('filter_value', '');
            $showInactive = $request->boolean('show_inactive');
            $sort = $request->query('sort', 'element_name');
            $direction = $request->query('direction', 'asc');

            // Validate sort column and direction
            $sortableColumns = ['element_name', 'description', 'code', 'tmu', 'seconds', 'motion_sequence', 'status'];
            if (!in_array($sort, $sortableColumns)) {
                $sort = 'element_name';
            }
            if (!in_array($direction, ['asc', 'desc'])) {
                $direction = 'asc';
            }

            $gsdElements = GsdElement::with('gsdCategory')
                ->when(!$showInactive, fn($q) => $q->where('gsd_elements.status', 'active'))
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('element_name', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%")
                            ->orWhere('motion_sequence', 'like', "%{$search}%");
                    });
                })
                ->when($filterColumn === 'element_name' && $filterValue !== '', fn($q) => $q->where('element_name', $filterValue))
                ->when($filterColumn === 'code' && $filterValue !== '', fn($q) => $q->where('code', $filterValue))
                ->when($filterColumn === 'motion_sequence' && $filterValue !== '', fn($q) => $q->where('motion_sequence', $filterValue))
                ->orderBy($sort, $direction)
                ->get();

            $filterValues = [
                'element_name' => GsdElement::distinct()->whereNotNull('element_name')->orderBy('element_name')->pluck('element_name')->toArray(),
                'code' => GsdElement::distinct()->whereNotNull('code')->orderBy('code')->pluck('code')->toArray(),
                'motion_sequence' => GsdElement::distinct()->whereNotNull('motion_sequence')->orderBy('motion_sequence')->pluck('motion_sequence')->toArray(),
            ];

            $gsdCategories = GsdCategory::where('status', 'active')->orderBy('category_name')->get();

            $totalCount = $gsdElements->count();

            return view('master-data.gsd-elements', compact('gsdElements', 'gsdCategories', 'search', 'filterColumn', 'filterValue', 'filterValues', 'showInactive', 'sort', 'direction', 'totalCount'));
        })->name('gsd-elements');

        Route::post('/gsd-elements', function (Request $request) {
            $data = $request->validate([
                'element_name' => ['required', 'string', 'max:200'],
                'description' => ['nullable', 'string', 'max:255'],
                'code' => ['required', 'string', 'max:50'],
                'tmu' => ['required', 'numeric', 'min:0'],
                'seconds' => ['required', 'numeric', 'min:0'],
                'motion_sequence' => ['nullable', 'string', 'max:100'],
                'gsd_category_id' => ['required', 'exists:gsd_categories,id'],
            ]);

            GsdElement::create($data + ['status' => 'active']);
            logActivity('Created GSD element: ' . $data['element_name'], 'GSD Elements');
            return redirect()->route('master-data.gsd-elements')->with('success', 'GSD Element created successfully.');
        })->name('gsd-elements.store');

        Route::put('/gsd-elements/{gsdElement}', function (Request $request, GsdElement $gsdElement) {
            $data = $request->validate([
                'element_name' => ['required', 'string', 'max:200'],
                'description' => ['nullable', 'string', 'max:255'],
                'code' => ['required', 'string', 'max:50'],
                'tmu' => ['required', 'numeric', 'min:0'],
                'seconds' => ['required', 'numeric', 'min:0'],
                'motion_sequence' => ['nullable', 'string', 'max:100'],
                'gsd_category_id' => ['required', 'exists:gsd_categories,id'],
            ]);

            $gsdElement->update($data);
            logActivity('Updated GSD element: ' . $data['element_name'], 'GSD Elements');
            return redirect()->route('master-data.gsd-elements')->with('success', 'GSD Element updated successfully.');
        })->name('gsd-elements.update');

        Route::patch('/gsd-elements/{gsdElement}/deactivate', function (GsdElement $gsdElement) {
            $gsdElement->update(['status' => 'inactive']);
            logActivity('Deactivated GSD element: ' . $gsdElement->element_name, 'GSD Elements');
            return redirect()->route('master-data.gsd-elements')->with('success', 'GSD Element marked as inactive.');
        })->name('gsd-elements.destroy');

        // GSD Element Bulk Delete (soft)
        Route::patch('/gsd-elements/bulk-deactivate', function (Request $request) {
            $ids = $request->input('ids', []);
            if (empty($ids)) {
                return redirect()->route('master-data.gsd-elements')->with('error', 'No records selected.');
            }
            $count = GsdElement::whereIn('id', $ids)->update(['status' => 'inactive', 'updated_at' => now()]);
            logActivity('Bulk deactivated ' . $count . ' GSD elements', 'GSD Elements');
            return redirect()->route('master-data.gsd-elements')->with('success', $count . ' record(s) marked as inactive.');
        })->name('gsd-elements.bulk-deactivate');

        // GSD Element Hard Delete
        Route::delete('/gsd-elements/hard-delete', function (Request $request) {
            $ids = $request->input('ids', []);
            if (empty($ids)) {
                return redirect()->route('master-data.gsd-elements')->with('error', 'No records selected.');
            }
            $toDelete = GsdElement::whereIn('id', $ids)->where('status', 'inactive')->pluck('id')->toArray();
            $skipped = count($ids) - count($toDelete);
            $deleted = 0;
            foreach ($toDelete as $id) {
                $el = GsdElement::find($id);
                if ($el) {
                    $el->delete();
                    $deleted++;
                }
            }
            $msg = $deleted . ' GSD element(s) permanently deleted.';
            if ($skipped > 0) {
                $msg .= ' ' . $skipped . ' skipped (active records cannot be hard deleted).';
            }
            logActivity('Hard deleted ' . $deleted . ' GSD elements', 'GSD Elements');
            return redirect()->route('master-data.gsd-elements')->with('success', $msg);
        })->name('gsd-elements.hard-delete');

        // GSD Element Import
        Route::post('/gsd-elements/import', function (Request $request) {
            $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv']]);
            $path = $request->file('file')->store('temp');
            $fullPath = storage_path('app/' . $path);
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
            $rows = $spreadsheet->getActiveSheet()->toArray();
            $header = array_map('strtolower', array_map('trim', $rows[0]));
            $nameIdx = array_search('element name', $header);
            $codeIdx = array_search('code', $header);
            $tmuIdx = array_search('tmu', $header);
            $secIdx = array_search('seconds', $header);
            $motionIdx = array_search('motion sequence', $header);
            $descIdx = array_search('descriptions', $header);
            $catIdx = array_search('category', $header);
            $imported = 0;
            for ($i = 1; $i < count($rows); $i++) {
                $name = trim($rows[$i][$nameIdx] ?? '');
                if ($name === '')
                    continue;
                $catName = trim($rows[$i][$catIdx] ?? '');
                $categoryId = null;
                if ($catName !== '') {
                    $cat = GsdCategory::firstOrCreate(['category_name' => $catName], ['status' => 'active']);
                    $categoryId = $cat->id;
                }
                GsdElement::updateOrInsert(
                    ['element_name' => $name],
                    [
                        'code' => trim($rows[$i][$codeIdx] ?? ''),
                        'tmu' => is_numeric(trim($rows[$i][$tmuIdx] ?? '')) ? floatval($rows[$i][$tmuIdx]) : 0,
                        'seconds' => is_numeric(trim($rows[$i][$secIdx] ?? '')) ? floatval($rows[$i][$secIdx]) : 0,
                        'motion_sequence' => trim($rows[$i][$motionIdx] ?? ''),
                        'description' => trim($rows[$i][$descIdx] ?? ''),
                        'gsd_category_id' => $categoryId,
                        'status' => 'active',
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
                $imported++;
            }
            @unlink($fullPath);
            logActivity("Imported {$imported} GSD elements from Excel", 'GSD Elements');
            return redirect()->route('master-data.gsd-elements')->with('success', "Imported {$imported} GSD elements.");
        })->name('gsd-elements.import');

        // GSD Element Export
        Route::get('/gsd-elements/export', function () {
            $gsdElements = GsdElement::with('gsdCategory')->where('status', 'active')->orderBy('element_name')->get();
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValue('A1', 'No')->setCellValue('B1', 'Element Name')->setCellValue('C1', 'Code')->setCellValue('D1', 'TMU')->setCellValue('E1', 'Seconds')->setCellValue('F1', 'Motion Sequence')->setCellValue('G1', 'Category')->setCellValue('H1', 'Descriptions');
            foreach ($gsdElements as $i => $el) {
                $row = $i + 2;
                $sheet->setCellValue("A{$row}", $i + 1)->setCellValue("B{$row}", $el->element_name)->setCellValue("C{$row}", $el->code)->setCellValue("D{$row}", $el->tmu)->setCellValue("E{$row}", $el->seconds)->setCellValue("F{$row}", $el->motion_sequence)->setCellValue("G{$row}", $el->gsdCategory?->category_name)->setCellValue("H{$row}", $el->description);
            }
            applyExcelFormatting($spreadsheet);
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $tempFile = tempnam(sys_get_temp_dir(), 'export') . '.xlsx';
            $writer->save($tempFile);
            logActivity('Exported GSD elements to Excel', 'GSD Elements');
            return response()->download($tempFile, 'gsd-elements.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
        })->name('gsd-elements.export');

        // ==================== FACTORIES ====================
        Route::get('/factories', function (Request $request) {
            $search = trim((string) $request->query('search', ''));
            $filterColumn = (string) $request->query('filter_column', '');
            $filterValue = (string) $request->query('filter_value', '');
            $showInactive = $request->boolean('show_inactive');
            $sort = $request->query('sort', 'factory_name');
            $direction = $request->query('direction', 'asc');

            $sortableColumns = ['factory_name', 'description', 'status'];
            if (!in_array($sort, $sortableColumns))
                $sort = 'factory_name';
            if (!in_array($direction, ['asc', 'desc']))
                $direction = 'asc';

            $factories = Factory::when(!$showInactive, fn($q) => $q->where('status', 'active'))
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('factory_name', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
                })
                ->when($filterColumn === 'factory_name' && $filterValue !== '', fn($q) => $q->where('factory_name', $filterValue))
                ->orderBy($sort, $direction)
                ->get();

            $totalCount = $factories->count();

            $filterValues = [
                'factory_name' => Factory::whereNotNull('factory_name')->distinct()->orderBy('factory_name')->pluck('factory_name')->toArray(),
            ];

            return view('master-data.factories', compact('factories', 'search', 'filterColumn', 'filterValue', 'filterValues', 'showInactive', 'sort', 'direction', 'totalCount'));
        })->name('factories');

        Route::post('/factories', function (Request $request) {
            $data = $request->validate([
                'factory_name' => ['required', 'string', 'max:100'],
                'description' => ['nullable', 'string', 'max:255'],
            ]);
            Factory::create($data + ['status' => 'active']);
            logActivity('Created factory: ' . $data['factory_name'], 'Factories');
            return redirect()->route('master-data.factories')->with('success', 'Factory created successfully.');
        })->name('factories.store');

        Route::put('/factories/{factory}', function (Request $request, Factory $factory) {
            $data = $request->validate([
                'factory_name' => ['required', 'string', 'max:100'],
                'description' => ['nullable', 'string', 'max:255'],
            ]);
            $factory->update($data);
            logActivity('Updated factory: ' . $data['factory_name'], 'Factories');
            return redirect()->route('master-data.factories')->with('success', 'Factory updated successfully.');
        })->name('factories.update');

        Route::patch('/factories/{factory}/deactivate', function (Factory $factory) {
            $factory->update(['status' => 'inactive']);
            logActivity('Deactivated factory: ' . $factory->factory_name, 'Factories');
            return redirect()->route('master-data.factories')->with('success', 'Factory marked as inactive.');
        })->name('factories.destroy');

        // Factory Bulk Delete (soft)
        Route::patch('/factories/bulk-deactivate', function (Request $request) {
            $ids = $request->input('ids', []);
            if (empty($ids)) {
                return redirect()->route('master-data.factories')->with('error', 'No records selected.');
            }
            $count = Factory::whereIn('id', $ids)->update(['status' => 'inactive', 'updated_at' => now()]);
            logActivity('Bulk deactivated ' . $count . ' factories', 'Factories');
            return redirect()->route('master-data.factories')->with('success', $count . ' record(s) marked as inactive.');
        })->name('factories.bulk-deactivate');

        // Factory Hard Delete
        Route::delete('/factories/hard-delete', function (Request $request) {
            $ids = $request->input('ids', []);
            if (empty($ids)) {
                return redirect()->route('master-data.factories')->with('error', 'No records selected.');
            }
            $toDelete = Factory::whereIn('id', $ids)->where('status', 'inactive')->pluck('id')->toArray();
            $skipped = count($ids) - count($toDelete);
            $deleted = 0;
            foreach ($toDelete as $id) {
                $factory = Factory::find($id);
                if ($factory && !$factory->departments()->exists()) {
                    $factory->delete();
                    $deleted++;
                }
            }
            $msg = $deleted . ' factory/factories permanently deleted.';
            if ($skipped > 0) {
                $msg .= ' ' . $skipped . ' skipped (active or have dependencies).';
            }
            logActivity('Hard deleted ' . $deleted . ' factories', 'Factories');
            return redirect()->route('master-data.factories')->with('success', $msg);
        })->name('factories.hard-delete');

        Route::post('/factories/import', function (Request $request) {
            $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv']]);
            $path = $request->file('file')->store('temp');
            $fullPath = storage_path('app/' . $path);
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
            $rows = $spreadsheet->getActiveSheet()->toArray();
            $header = array_map('strtolower', array_map('trim', $rows[0]));
            $nameIdx = array_search('factory name', $header);
            $descIdx = array_search('descriptions', $header);
            $imported = 0;
            for ($i = 1; $i < count($rows); $i++) {
                $name = trim($rows[$i][$nameIdx] ?? '');
                if ($name === '')
                    continue;
                Factory::updateOrInsert(
                    ['factory_name' => $name],
                    ['description' => trim($rows[$i][$descIdx] ?? ''), 'status' => 'active', 'updated_at' => now(), 'created_at' => now()]
                );
                $imported++;
            }
            @unlink($fullPath);
            logActivity("Imported {$imported} factories from Excel", 'Factories');
            return redirect()->route('master-data.factories')->with('success', "Imported {$imported} factories.");
        })->name('factories.import');

        Route::get('/factories/export', function () {
            $factories = Factory::where('status', 'active')->orderBy('factory_name')->get();
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValue('A1', 'No')->setCellValue('B1', 'Factory Name')->setCellValue('C1', 'Descriptions');
            foreach ($factories as $i => $f) {
                $row = $i + 2;
                $sheet->setCellValue("A{$row}", $i + 1)->setCellValue("B{$row}", $f->factory_name)->setCellValue("C{$row}", $f->description);
            }
            applyExcelFormatting($spreadsheet);
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $tempFile = tempnam(sys_get_temp_dir(), 'export') . '.xlsx';
            $writer->save($tempFile);
            logActivity('Exported factories to Excel', 'Factories');
            return response()->download($tempFile, 'factories.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
        })->name('factories.export');

        // ==================== DEPARTMENTS ====================
        Route::get('/departments', function (Request $request) {
            $search = trim((string) $request->query('search', ''));
            $filterColumn = (string) $request->query('filter_column', '');
            $filterValue = (string) $request->query('filter_value', '');
            $showInactive = $request->boolean('show_inactive');
            $sort = $request->query('sort', 'department_name');
            $direction = $request->query('direction', 'asc');

            $sortableColumns = ['department_name', 'desription', 'status'];
            if (!in_array($sort, $sortableColumns))
                $sort = 'department_name';
            if (!in_array($direction, ['asc', 'desc']))
                $direction = 'asc';

            $departments = Department::with('factory')
                ->when(!$showInactive, fn($q) => $q->where('status', 'active'))
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('department_name', 'like', "%{$search}%")
                            ->orWhere('desription', 'like', "%{$search}%")
                            ->orWhereHas('factory', fn($q2) => $q2->where('factory_name', 'like', "%{$search}%"));
                    });
                })
                ->when($filterColumn === 'department_name' && $filterValue !== '', fn($q) => $q->where('department_name', $filterValue))
                ->when($filterColumn === 'factory_name' && $filterValue !== '', fn($q) => $q->whereHas('factory', fn($q2) => $q2->where('factory_name', $filterValue)))
                ->orderBy($sort, $direction)
                ->get();

            $totalCount = $departments->count();

            $filterValues = [
                'department_name' => Department::whereNotNull('department_name')->distinct()->orderBy('department_name')->pluck('department_name')->toArray(),
                'factory_name' => Factory::whereNotNull('factory_name')->distinct()->orderBy('factory_name')->pluck('factory_name')->toArray(),
            ];

            $factories = Factory::where('status', 'active')->orderBy('factory_name')->get();
            return view('master-data.departments', compact('departments', 'factories', 'search', 'filterColumn', 'filterValue', 'filterValues', 'showInactive', 'sort', 'direction', 'totalCount'));
        })->name('departments');

        Route::post('/departments', function (Request $request) {
            $data = $request->validate([
                'factory_id' => ['required', 'exists:factories,id'],
                'department_name' => ['required', 'string', 'max:100'],
                'desription' => ['nullable', 'string', 'max:255'],
            ]);
            Department::create($data + ['status' => 'active']);
            logActivity('Created department: ' . $data['department_name'], 'Departments');
            return redirect()->route('master-data.departments')->with('success', 'Department created successfully.');
        })->name('departments.store');

        Route::put('/departments/{department}', function (Request $request, Department $department) {
            $data = $request->validate([
                'factory_id' => ['required', 'exists:factories,id'],
                'department_name' => ['required', 'string', 'max:100'],
                'desription' => ['nullable', 'string', 'max:255'],
            ]);
            $department->update($data);
            logActivity('Updated department: ' . $data['department_name'], 'Departments');
            return redirect()->route('master-data.departments')->with('success', 'Department updated successfully.');
        })->name('departments.update');

        Route::patch('/departments/{department}/deactivate', function (Department $department) {
            $department->update(['status' => 'inactive']);
            logActivity('Deactivated department: ' . $department->department_name, 'Departments');
            return redirect()->route('master-data.departments')->with('success', 'Department marked as inactive.');
        })->name('departments.destroy');

        // Department Bulk Delete (soft)
        Route::patch('/departments/bulk-deactivate', function (Request $request) {
            $ids = $request->input('ids', []);
            if (empty($ids)) {
                return redirect()->route('master-data.departments')->with('error', 'No records selected.');
            }
            $count = Department::whereIn('id', $ids)->update(['status' => 'inactive', 'updated_at' => now()]);
            logActivity('Bulk deactivated ' . $count . ' departments', 'Departments');
            return redirect()->route('master-data.departments')->with('success', $count . ' record(s) marked as inactive.');
        })->name('departments.bulk-deactivate');

        // Department Hard Delete
        Route::delete('/departments/hard-delete', function (Request $request) {
            $ids = $request->input('ids', []);
            if (empty($ids)) {
                return redirect()->route('master-data.departments')->with('error', 'No records selected.');
            }
            $toDelete = Department::whereIn('id', $ids)->where('status', 'inactive')->pluck('id')->toArray();
            $skipped = count($ids) - count($toDelete);
            $deleted = 0;
            foreach ($toDelete as $id) {
                $dept = Department::find($id);
                if ($dept && !$dept->operators()->exists() && !$dept->ptmsReports()->exists()) {
                    $dept->delete();
                    $deleted++;
                }
            }
            $msg = $deleted . ' department(s) permanently deleted.';
            if ($skipped > 0) {
                $msg .= ' ' . $skipped . ' skipped (active or have dependencies).';
            }
            logActivity('Hard deleted ' . $deleted . ' departments', 'Departments');
            return redirect()->route('master-data.departments')->with('success', $msg);
        })->name('departments.hard-delete');

        Route::post('/departments/import', function (Request $request) {
            $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv']]);
            $path = $request->file('file')->store('temp');
            $fullPath = storage_path('app/' . $path);
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
            $rows = $spreadsheet->getActiveSheet()->toArray();
            $header = array_map('strtolower', array_map('trim', $rows[0]));
            $nameIdx = array_search('department name', $header);
            $descIdx = array_search('descriptions', $header);
            $factoryIdx = array_search('factory', $header);
            $imported = 0;
            for ($i = 1; $i < count($rows); $i++) {
                $name = trim($rows[$i][$nameIdx] ?? '');
                if ($name === '')
                    continue;
                $factoryId = null;
                if ($factoryIdx !== false) {
                    $factoryName = trim($rows[$i][$factoryIdx] ?? '');
                    if ($factoryName !== '') {
                        $factory = Factory::where('factory_name', $factoryName)->where('status', 'active')->first();
                        $factoryId = $factory?->id;
                    }
                }
                Department::updateOrInsert(
                    ['department_name' => $name],
                    ['factory_id' => $factoryId, 'desription' => trim($rows[$i][$descIdx] ?? ''), 'status' => 'active', 'updated_at' => now(), 'created_at' => now()]
                );
                $imported++;
            }
            @unlink($fullPath);
            logActivity("Imported {$imported} departments from Excel", 'Departments');
            return redirect()->route('master-data.departments')->with('success', "Imported {$imported} departments.");
        })->name('departments.import');

        Route::get('/departments/export', function () {
            $departments = Department::where('status', 'active')->orderBy('department_name')->get();
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValue('A1', 'No')->setCellValue('B1', 'Department Name')->setCellValue('C1', 'Descriptions');
            foreach ($departments as $i => $d) {
                $row = $i + 2;
                $sheet->setCellValue("A{$row}", $i + 1)->setCellValue("B{$row}", $d->department_name)->setCellValue("C{$row}", $d->desription);
            }
            applyExcelFormatting($spreadsheet);
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $tempFile = tempnam(sys_get_temp_dir(), 'export') . '.xlsx';
            $writer->save($tempFile);
            logActivity('Exported departments to Excel', 'Departments');
            return response()->download($tempFile, 'departments.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
        })->name('departments.export');

        // ==================== GENERIC DATA MASTER HELPER ====================
        // Each data master follows the same pattern: CRUD + soft-delete + import + export
        $simpleMasters = [
            ['slug' => 'skill-gradings', 'model' => SkillGrading::class, 'name' => 'skill_grade', 'label' => 'Skill Gradings', 'importCols' => ['skill grade', 'descriptions']],
            ['slug' => 'divisions', 'model' => Division::class, 'name' => 'division', 'label' => 'Divisions', 'importCols' => ['division', 'descriptions']],
            ['slug' => 'sections', 'model' => Section::class, 'name' => 'section', 'label' => 'Sections', 'importCols' => ['section', 'descriptions']],
            ['slug' => 'machine-types', 'model' => MachineType::class, 'name' => 'machine_type', 'label' => 'Machine Types', 'importCols' => ['machine type', 'descriptions']],
            ['slug' => 'components-panels', 'model' => ComponentsPanel::class, 'name' => 'component_panel', 'label' => 'Components/Panels', 'importCols' => ['component/panel', 'descriptions']],
            ['slug' => 'machine-numbers', 'model' => MachineNumber::class, 'name' => 'machine_number', 'label' => 'Machine Numbers', 'importCols' => ['machine number', 'descriptions']],
            ['slug' => 'shifts', 'model' => Shift::class, 'name' => 'shift', 'label' => 'Shifts', 'importCols' => ['shift', 'descriptions']],
            ['slug' => 'failure-modes', 'model' => FailureMode::class, 'name' => 'failure_mode', 'label' => 'Failure Modes / Kerusakan', 'importCols' => ['failure mode / kerusakan', 'descriptions']],
            ['slug' => 'spare-parts', 'model' => SparePart::class, 'name' => 'spare_part', 'label' => 'Spare Parts', 'importCols' => ['spare part', 'descriptions']],
            ['slug' => 'genders', 'model' => Gender::class, 'name' => 'gender', 'label' => 'Genders', 'importCols' => ['gender', 'descriptions']],
            ['slug' => 'production-roles', 'model' => ProductionRole::class, 'name' => 'production_role', 'label' => 'Production Roles', 'importCols' => ['production role', 'descriptions']],
            ['slug' => 'educational-levels', 'model' => EducationalLevel::class, 'name' => 'level', 'label' => 'Educational Level', 'importCols' => ['level', 'descriptions']],
            ['slug' => 'status-pkwtt', 'model' => StatusPkwtt::class, 'name' => 'pkwtt', 'label' => 'Status PKWTT', 'importCols' => ['pkwtt', 'descriptions']],
        ];

        foreach ($simpleMasters as $master) {
            $slug = $master['slug'];
            $modelClass = $master['model'];
            $nameField = $master['name'];
            $label = $master['label'];

            // List
            Route::get('/' . $slug, function (Request $request) use ($modelClass, $nameField) {
                $search = trim((string) $request->query('search', ''));
                $filterColumn = (string) $request->query('filter_column', '');
                $filterValue = (string) $request->query('filter_value', '');
                $showInactive = $request->boolean('show_inactive');
                $sort = $request->query('sort', $nameField);
                $direction = $request->query('direction', 'asc');

                // Validate sort column and direction
                $sortableColumns = [$nameField, 'description', 'status'];
                if (!in_array($sort, $sortableColumns)) {
                    $sort = $nameField;
                }
                if (!in_array($direction, ['asc', 'desc'])) {
                    $direction = 'asc';
                }

                $items = $modelClass::when(!$showInactive, fn($q) => $q->where('status', 'active'))
                    ->when($search !== '', function ($query) use ($search, $nameField) {
                        $query->where(function ($q) use ($search, $nameField) {
                            $q->where($nameField, 'like', "%{$search}%")
                                ->orWhere('description', 'like', "%{$search}%");
                        });
                    })
                    ->when($filterColumn === $nameField && $filterValue !== '', fn($q) => $q->where($nameField, $filterValue))
                    ->orderBy($sort, $direction)
                    ->get();

                $totalCount = $items->count();

                $filterValues = [
                    $nameField => $modelClass::whereNotNull($nameField)->distinct()->orderBy($nameField)->pluck($nameField)->toArray(),
                ];

                return view('master-data.simple-master', compact('items', 'nameField', 'search', 'filterColumn', 'filterValue', 'filterValues', 'showInactive', 'sort', 'direction', 'totalCount'));
            })->name($slug);

            // Store
            Route::post('/' . $slug, function (Request $request) use ($modelClass, $nameField, $slug) {
                $data = $request->validate([
                    $nameField => ['required', 'string', 'max:200'],
                    'description' => ['nullable', 'string', 'max:255'],
                ]);
                $modelClass::create($data + ['status' => 'active']);
                logActivity('Created ' . $slug . ': ' . $data[$nameField], ucwords(str_replace('_', ' ', $nameField)));
                return redirect()->route('master-data.' . $slug)->with('success', $nameField . ' created successfully.');
            })->name($slug . '.store');

            // Update
            Route::put('/' . $slug . '/{item}', function (Request $request, $item) use ($modelClass, $nameField, $slug) {
                $model = $modelClass::findOrFail($item);
                $data = $request->validate([
                    $nameField => ['required', 'string', 'max:200'],
                    'description' => ['nullable', 'string', 'max:255'],
                ]);
                $model->update($data);
                logActivity('Updated ' . $slug . ': ' . $data[$nameField], ucwords(str_replace('_', ' ', $nameField)));
                return redirect()->route('master-data.' . $slug)->with('success', $nameField . ' updated successfully.');
            })->name($slug . '.update');

            // Delete (soft)
            Route::patch('/' . $slug . '/{item}/deactivate', function ($item) use ($modelClass, $nameField, $slug) {
                $model = $modelClass::findOrFail($item);
                $model->update(['status' => 'inactive']);
                logActivity('Deactivated ' . $slug . ': ' . $model->{$nameField}, ucwords(str_replace('_', ' ', $nameField)));
                return redirect()->route('master-data.' . $slug)->with('success', 'Record marked as inactive.');
            })->name($slug . '.destroy');

            // Bulk Delete (soft)
            Route::patch('/' . $slug . '/bulk-deactivate', function (Request $request) use ($modelClass, $nameField, $slug) {
                $ids = $request->input('ids', []);
                if (empty($ids)) {
                    return redirect()->route('master-data.' . $slug)->with('error', 'No records selected.');
                }
                $count = $modelClass::whereIn('id', $ids)->update(['status' => 'inactive', 'updated_at' => now()]);
                logActivity('Bulk deactivated ' . $count . ' ' . ucwords(str_replace('_', ' ', $nameField)), ucwords(str_replace('_', ' ', $nameField)));
                return redirect()->route('master-data.' . $slug)->with('success', $count . ' record(s) marked as inactive.');
            })->name($slug . '.bulk-deactivate');

            // Hard Delete
            Route::delete('/' . $slug . '/hard-delete', function (Request $request) use ($modelClass, $nameField, $slug) {
                $ids = $request->input('ids', []);
                if (empty($ids)) {
                    return redirect()->route('master-data.' . $slug)->with('error', 'No records selected.');
                }
                $toDelete = $modelClass::whereIn('id', $ids)->where('status', 'inactive')->pluck('id')->toArray();
                $skipped = count($ids) - count($toDelete);
                $deleted = 0;
                foreach ($toDelete as $id) {
                    $model = $modelClass::find($id);
                    if ($model) {
                        $model->delete();
                        $deleted++;
                    }
                }
                $msg = $deleted . ' record(s) permanently deleted.';
                if ($skipped > 0) {
                    $msg .= ' ' . $skipped . ' skipped (active records cannot be hard deleted).';
                }
                logActivity('Hard deleted ' . $deleted . ' ' . ucwords(str_replace('_', ' ', $nameField)), ucwords(str_replace('_', ' ', $nameField)));
                return redirect()->route('master-data.' . $slug)->with('success', $msg);
            })->name($slug . '.hard-delete');

            // Import
            Route::post('/' . $slug . '/import', function (Request $request) use ($modelClass, $nameField, $slug) {
                $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv']]);
                $path = $request->file('file')->store('temp');
                $fullPath = storage_path('app/' . $path);
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
                $rows = $spreadsheet->getActiveSheet()->toArray();
                $header = array_map('strtolower', array_map('trim', $rows[0]));
                $nameIdx = array_search(str_replace('_', ' ', $nameField), $header);
                $descIdx = array_search('descriptions', $header);
                $imported = 0;
                for ($i = 1; $i < count($rows); $i++) {
                    $name = trim($rows[$i][$nameIdx] ?? '');
                    if ($name === '')
                        continue;
                    $modelClass::updateOrInsert(
                        [$nameField => $name],
                        ['description' => trim($rows[$i][$descIdx] ?? ''), 'status' => 'active', 'updated_at' => now(), 'created_at' => now()]
                    );
                    $imported++;
                }
                @unlink($fullPath);
                logActivity("Imported {$imported} " . ucwords(str_replace('_', ' ', $nameField)) . " from Excel", ucwords(str_replace('_', ' ', $nameField)));
                return redirect()->route('master-data.' . $slug)->with('success', "Imported {$imported} records.");
            })->name($slug . '.import');

            // Export
            Route::get('/' . $slug . '/export', function () use ($modelClass, $nameField, $slug) {
                $items = $modelClass::where('status', 'active')->orderBy($nameField)->get();
                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                $displayName = ucwords(str_replace('_', ' ', $nameField));
                $sheet->setCellValue('A1', 'No')->setCellValue('B1', $displayName)->setCellValue('C1', 'Descriptions');
                foreach ($items as $i => $item) {
                    $row = $i + 2;
                    $sheet->setCellValue("A{$row}", $i + 1)->setCellValue("B{$row}", $item->{$nameField})->setCellValue("C{$row}", $item->description);
                }
                applyExcelFormatting($spreadsheet);
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                $tempFile = tempnam(sys_get_temp_dir(), 'export') . '.xlsx';
                $writer->save($tempFile);
                return response()->download($tempFile, $slug . '.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
            })->name($slug . '.export');

            // Autocomplete search (JSON)
            Route::get('/' . $slug . '/search', function (Request $request) use ($modelClass, $nameField) {
                $q = trim((string) $request->query('q', ''));
                if ($q === '')
                    return response()->json([]);
                $results = $modelClass::where('status', 'active')
                    ->where(function ($query) use ($q, $nameField) {
                        $query->where($nameField, 'like', "%{$q}%")
                            ->orWhere('description', 'like', "%{$q}%");
                    })
                    ->orderBy($nameField)
                    ->limit(10)
                    ->get()
                    ->map(fn($item) => [
                        'id' => $item->id,
                        'label' => $item->{$nameField},
                        'description' => $item->description ?? '',
                    ]);
                return response()->json($results);
            })->name($slug . '.search');
        }

        // ==================== MECHANICS (custom routes with NIK KARYAWAN) ====================
        Route::get('/mechanics', function (Request $request) {
            $search = trim((string) $request->query('search', ''));
            $filterColumn = (string) $request->query('filter_column', '');
            $filterValue = (string) $request->query('filter_value', '');
            $showInactive = $request->boolean('show_inactive');
            $sort = $request->query('sort', 'mechanic');
            $direction = $request->query('direction', 'asc');

            $sortableColumns = ['nik_karyawan', 'mechanic', 'description', 'status'];
            if (!in_array($sort, $sortableColumns))
                $sort = 'mechanic';
            if (!in_array($direction, ['asc', 'desc']))
                $direction = 'asc';

            $items = Mechanic::when(!$showInactive, fn($q) => $q->where('status', 'active'))
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('nik_karyawan', 'like', "%{$search}%")
                            ->orWhere('mechanic', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
                })
                ->when($filterColumn === 'nik_karyawan' && $filterValue !== '', fn($q) => $q->where('nik_karyawan', $filterValue))
                ->when($filterColumn === 'mechanic' && $filterValue !== '', fn($q) => $q->where('mechanic', $filterValue))
                ->orderBy($sort, $direction)
                ->get();

            $filterValues = [
                'nik_karyawan' => Mechanic::whereNotNull('nik_karyawan')->distinct()->orderBy('nik_karyawan')->pluck('nik_karyawan')->toArray(),
                'mechanic' => Mechanic::whereNotNull('mechanic')->distinct()->orderBy('mechanic')->pluck('mechanic')->toArray(),
            ];

            $totalCount = $items->count();

            return view('master-data.mechanics', compact('items', 'search', 'filterColumn', 'filterValue', 'filterValues', 'showInactive', 'sort', 'direction', 'totalCount'));
        })->name('mechanics');

        Route::post('/mechanics', function (Request $request) {
            $data = $request->validate([
                'nik_karyawan' => ['nullable', 'string', 'max:50', 'unique:mechanics,nik_karyawan'],
                'mechanic' => ['required', 'string', 'max:200'],
                'description' => ['nullable', 'string', 'max:255'],
            ]);
            Mechanic::create($data + ['status' => 'active']);
            logActivity('Created mechanic: ' . $data['mechanic'], 'Mechanics');
            return redirect()->route('master-data.mechanics')->with('success', 'Mechanic created successfully.');
        })->name('mechanics.store');

        Route::put('/mechanics/{item}', function (Request $request, $item) {
            $model = Mechanic::findOrFail($item);
            $data = $request->validate([
                'nik_karyawan' => ['nullable', 'string', 'max:50', 'unique:mechanics,nik_karyawan,' . $model->id],
                'mechanic' => ['required', 'string', 'max:200'],
                'description' => ['nullable', 'string', 'max:255'],
            ]);
            $model->update($data);
            logActivity('Updated mechanic: ' . $data['mechanic'], 'Mechanics');
            return redirect()->route('master-data.mechanics')->with('success', 'Mechanic updated successfully.');
        })->name('mechanics.update');

        Route::patch('/mechanics/{item}/deactivate', function ($item) {
            $model = Mechanic::findOrFail($item);
            $model->update(['status' => 'inactive']);
            logActivity('Deactivated mechanic: ' . $model->mechanic, 'Mechanics');
            return redirect()->route('master-data.mechanics')->with('success', 'Record marked as inactive.');
        })->name('mechanics.destroy');

        // Mechanic Bulk Delete (soft)
        Route::patch('/mechanics/bulk-deactivate', function (Request $request) {
            $ids = $request->input('ids', []);
            if (empty($ids)) {
                return redirect()->route('master-data.mechanics')->with('error', 'No records selected.');
            }
            $count = Mechanic::whereIn('id', $ids)->update(['status' => 'inactive', 'updated_at' => now()]);
            logActivity('Bulk deactivated ' . $count . ' mechanics', 'Mechanics');
            return redirect()->route('master-data.mechanics')->with('success', $count . ' record(s) marked as inactive.');
        })->name('mechanics.bulk-deactivate');

        // Mechanic Hard Delete
        Route::delete('/mechanics/hard-delete', function (Request $request) {
            $ids = $request->input('ids', []);
            if (empty($ids)) {
                return redirect()->route('master-data.mechanics')->with('error', 'No records selected.');
            }
            $toDelete = Mechanic::whereIn('id', $ids)->where('status', 'inactive')->pluck('id')->toArray();
            $skipped = count($ids) - count($toDelete);
            $deleted = 0;
            foreach ($toDelete as $id) {
                $m = Mechanic::find($id);
                if ($m && !$m->ptmsReports()->exists()) {
                    $m->delete();
                    $deleted++;
                }
            }
            $msg = $deleted . ' mechanic(s) permanently deleted.';
            if ($skipped > 0) {
                $msg .= ' ' . $skipped . ' skipped (active or have dependencies).';
            }
            logActivity('Hard deleted ' . $deleted . ' mechanics', 'Mechanics');
            return redirect()->route('master-data.mechanics')->with('success', $msg);
        })->name('mechanics.hard-delete');

        Route::post('/mechanics/import', function (Request $request) {
            $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv']]);
            $path = $request->file('file')->store('temp');
            $fullPath = storage_path('app/' . $path);
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
            $rows = $spreadsheet->getActiveSheet()->toArray();
            $header = array_map('strtolower', array_map('trim', $rows[0]));
            $nikIdx = array_search('nik karyawan', $header);
            $nameIdx = array_search('mechanic', $header);
            $descIdx = array_search('descriptions', $header);
            $imported = 0;
            for ($i = 1; $i < count($rows); $i++) {
                $name = trim($rows[$i][$nameIdx] ?? '');
                if ($name === '')
                    continue;
                $nik = $nikIdx !== false ? trim($rows[$i][$nikIdx] ?? '') : null;
                Mechanic::updateOrInsert(
                    ['mechanic' => $name],
                    [
                        'nik_karyawan' => $nik !== '' ? $nik : null,
                        'description' => trim($rows[$i][$descIdx] ?? ''),
                        'status' => 'active',
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
                $imported++;
            }
            @unlink($fullPath);
            logActivity("Imported {$imported} mechanics from Excel", 'Mechanics');
            return redirect()->route('master-data.mechanics')->with('success', "Imported {$imported} mechanics.");
        })->name('mechanics.import');

        Route::get('/mechanics/export', function () {
            $items = Mechanic::where('status', 'active')->orderBy('mechanic')->get();
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValue('A1', 'No')->setCellValue('B1', 'NIK KARYAWAN')->setCellValue('C1', 'Mechanic')->setCellValue('D1', 'Descriptions');
            foreach ($items as $i => $item) {
                $row = $i + 2;
                $sheet->setCellValue("A{$row}", $i + 1)->setCellValue("B{$row}", $item->nik_karyawan)->setCellValue("C{$row}", $item->mechanic)->setCellValue("D{$row}", $item->description);
            }
            applyExcelFormatting($spreadsheet);
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $tempFile = tempnam(sys_get_temp_dir(), 'export') . '.xlsx';
            $writer->save($tempFile);
            logActivity('Exported mechanics to Excel', 'Mechanics');
            return response()->download($tempFile, 'mechanics.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
        })->name('mechanics.export');

        Route::get('/mechanics/search', function (Request $request) {
            $q = trim((string) $request->query('q', ''));
            if ($q === '')
                return response()->json([]);
            return response()->json(
                Mechanic::where('status', 'active')
                    ->where(function ($query) use ($q) {
                        $query->where('nik_karyawan', 'like', "%{$q}%")
                            ->orWhere('mechanic', 'like', "%{$q}%");
                    })
                    ->orderBy('mechanic')->limit(10)->get()
                    ->map(fn($m) => ['id' => $m->id, 'label' => $m->mechanic, 'description' => $m->nik_karyawan ?? ''])
            );
        })->name('mechanics.search');

        // ==================== PRODUCTION LINES ====================
        Route::get('/production-lines', function (Request $request) {
            $search = trim((string) $request->query('search', ''));
            $filterColumn = (string) $request->query('filter_column', '');
            $filterValue = (string) $request->query('filter_value', '');
            $showInactive = $request->boolean('show_inactive');
            $sort = $request->query('sort', 'line_name');
            $direction = $request->query('direction', 'asc');

            $sortableColumns = ['line_name', 'description', 'status'];
            if (!in_array($sort, $sortableColumns))
                $sort = 'line_name';
            if (!in_array($direction, ['asc', 'desc']))
                $direction = 'asc';

            $productionLines = ProductionLine::when(!$showInactive, fn($q) => $q->where('status', 'active'))
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('line_name', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
                })
                ->when($filterColumn === 'line_name' && $filterValue !== '', fn($q) => $q->where('line_name', $filterValue))
                ->orderBy($sort, $direction)
                ->get();

            $totalCount = $productionLines->count();

            $filterValues = [
                'line_name' => ProductionLine::whereNotNull('line_name')->distinct()->orderBy('line_name')->pluck('line_name')->toArray(),
            ];

            return view('master-data.production-lines', compact('productionLines', 'search', 'filterColumn', 'filterValue', 'filterValues', 'showInactive', 'sort', 'direction', 'totalCount'));
        })->name('production-lines');

        Route::post('/production-lines', function (Request $request) {
            $data = $request->validate([
                'line_name' => ['required', 'string', 'max:100'],
                'description' => ['nullable', 'string', 'max:255'],
            ]);
            ProductionLine::create([...$data, 'status' => 'active']);
            logActivity('Created production line: ' . $data['line_name'], 'Production Lines');
            return redirect()->route('master-data.production-lines')->with('success', 'Production line created successfully.');
        })->name('production-lines.store');

        Route::put('/production-lines/{productionLine}', function (Request $request, ProductionLine $productionLine) {
            $data = $request->validate([
                'line_name' => ['required', 'string', 'max:100'],
                'description' => ['nullable', 'string', 'max:255'],
            ]);
            $productionLine->update($data);
            logActivity('Updated production line: ' . $data['line_name'], 'Production Lines');
            return redirect()->route('master-data.production-lines')->with('success', 'Production line updated successfully.');
        })->name('production-lines.update');

        Route::patch('/production-lines/{productionLine}/deactivate', function (ProductionLine $productionLine) {
            if ($productionLine->ptmsReports()->exists()) {
                return back()->with('error', 'This production line cannot be deactivated because historical reports reference the record.');
            }
            $productionLine->update(['status' => 'inactive']);
            logActivity('Deactivated production line: ' . $productionLine->line_name, 'Production Lines');
            return redirect()->route('master-data.production-lines')->with('success', 'Production line deactivated successfully.');
        })->name('production-lines.destroy');

        // Production Line Bulk Delete (soft)
        Route::patch('/production-lines/bulk-deactivate', function (Request $request) {
            $ids = $request->input('ids', []);
            if (empty($ids)) {
                return redirect()->route('master-data.production-lines')->with('error', 'No records selected.');
            }
            $count = ProductionLine::whereIn('id', $ids)->update(['status' => 'inactive', 'updated_at' => now()]);
            logActivity('Bulk deactivated ' . $count . ' production lines', 'Production Lines');
            return redirect()->route('master-data.production-lines')->with('success', $count . ' record(s) marked as inactive.');
        })->name('production-lines.bulk-deactivate');

        // Production Line Hard Delete
        Route::delete('/production-lines/hard-delete', function (Request $request) {
            $ids = $request->input('ids', []);
            if (empty($ids)) {
                return redirect()->route('master-data.production-lines')->with('error', 'No records selected.');
            }
            $toDelete = ProductionLine::whereIn('id', $ids)->where('status', 'inactive')->pluck('id')->toArray();
            $skipped = count($ids) - count($toDelete);
            $deleted = 0;
            foreach ($toDelete as $id) {
                $pl = ProductionLine::find($id);
                if ($pl && !$pl->ptmsReports()->exists()) {
                    $pl->delete();
                    $deleted++;
                }
            }
            $msg = $deleted . ' production line(s) permanently deleted.';
            if ($skipped > 0) {
                $msg .= ' ' . $skipped . ' skipped (active or have dependencies).';
            }
            logActivity('Hard deleted ' . $deleted . ' production lines', 'Production Lines');
            return redirect()->route('master-data.production-lines')->with('success', $msg);
        })->name('production-lines.hard-delete');

        Route::post('/production-lines/import', function (Request $request) {
            $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv']]);
            $path = $request->file('file')->store('temp');
            $fullPath = storage_path('app/' . $path);
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
            $rows = $spreadsheet->getActiveSheet()->toArray();
            $header = array_map('strtolower', array_map('trim', $rows[0]));
            $nameIdx = array_search('line name', $header);
            $descIdx = array_search('descriptions', $header);
            $imported = 0;
            for ($i = 1; $i < count($rows); $i++) {
                $name = trim($rows[$i][$nameIdx] ?? '');
                if ($name === '')
                    continue;
                ProductionLine::updateOrInsert(
                    ['line_name' => $name],
                    [
                        'description' => trim($rows[$i][$descIdx] ?? ''),
                        'status' => 'active',
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
                $imported++;
            }
            @unlink($fullPath);
            logActivity("Imported {$imported} production lines from Excel", 'Production Lines');
            return redirect()->route('master-data.production-lines')->with('success', "Imported {$imported} production lines.");
        })->name('production-lines.import');

        Route::get('/production-lines/export', function () {
            $productionLines = ProductionLine::orderBy('line_name')->get();
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValue('A1', 'No')->setCellValue('B1', 'Line Name')->setCellValue('C1', 'Descriptions');
            foreach ($productionLines as $i => $pl) {
                $row = $i + 2;
                $sheet->setCellValue("A{$row}", $i + 1)->setCellValue("B{$row}", $pl->line_name)->setCellValue("C{$row}", $pl->description);
            }
            applyExcelFormatting($spreadsheet);
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $tempFile = tempnam(sys_get_temp_dir(), 'export') . '.xlsx';
            $writer->save($tempFile);
            logActivity('Exported production lines to Excel', 'Production Lines');
            return response()->download($tempFile, 'production-lines.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
        })->name('production-lines.export');

        // ==================== AUTOCOMPLETE SEARCH ENDPOINTS ====================
        Route::get('/processes/search', function (Request $request) {
            $q = trim((string) $request->query('q', ''));
            if ($q === '')
                return response()->json([]);
            return response()->json(
                Process::where('status', 'active')
                    ->where('process_name', 'like', "%{$q}%")
                    ->orderBy('process_name')->limit(10)->get()
                    ->map(fn($p) => ['id' => $p->id, 'label' => $p->process_name, 'description' => $p->description ?? ''])
            );
        })->name('processes.search');

        Route::get('/operators/search', function (Request $request) {
            $q = trim((string) $request->query('q', ''));
            if ($q === '')
                return response()->json([]);
            return response()->json(
                Operator::where('status', 'active')
                    ->where(function ($query) use ($q) {
                        $query->where('operator_name', 'like', "%{$q}%")
                            ->orWhere('nik_karyawan', 'like', "%{$q}%");
                    })
                    ->orderBy('operator_name')->limit(10)->get()
                    ->map(fn($o) => ['id' => $o->id, 'label' => $o->operator_name, 'description' => $o->nik_karyawan ?? ''])
            );
        })->name('operators.search');

        Route::get('/articles/search', function (Request $request) {
            $q = trim((string) $request->query('q', ''));
            if ($q === '')
                return response()->json([]);
            return response()->json(
                Article::where('status', 'active')
                    ->where(function ($query) use ($q) {
                        $query->where('article_name', 'like', "%{$q}%")
                            ->orWhere('destination', 'like', "%{$q}%");
                    })
                    ->orderBy('article_name')->limit(10)->get()
                    ->map(fn($a) => ['id' => $a->id, 'label' => $a->article_name, 'description' => $a->destination ?? ''])
            );
        })->name('articles.search');

        Route::get('/gsd-elements/search', function (Request $request) {
            $q = trim((string) $request->query('q', ''));
            if ($q === '')
                return response()->json([]);
            return response()->json(
                GsdElement::where('status', 'active')
                    ->where(function ($query) use ($q) {
                        $query->where('element_name', 'like', "%{$q}%")
                            ->orWhere('code', 'like', "%{$q}%")
                            ->orWhere('motion_sequence', 'like', "%{$q}%");
                    })
                    ->orderBy('element_name')->limit(10)->get()
                    ->map(fn($e) => ['id' => $e->id, 'label' => $e->element_name, 'description' => ($e->code ?? '') . ($e->motion_sequence ? ' — ' . $e->motion_sequence : '')])
            );
        })->name('gsd-elements.search');

        Route::get('/factories/search', function (Request $request) {
            $q = trim((string) $request->query('q', ''));
            if ($q === '')
                return response()->json([]);
            return response()->json(
                Factory::where('status', 'active')
                    ->where('factory_name', 'like', "%{$q}%")
                    ->orderBy('factory_name')->limit(10)->get()
                    ->map(fn($f) => ['id' => $f->id, 'label' => $f->factory_name, 'description' => $f->description ?? ''])
            );
        })->name('factories.search');

        Route::get('/departments/search', function (Request $request) {
            $q = trim((string) $request->query('q', ''));
            if ($q === '')
                return response()->json([]);
            return response()->json(
                Department::where('status', 'active')
                    ->where('department_name', 'like', "%{$q}%")
                    ->orderBy('department_name')->limit(10)->get()
                    ->map(fn($d) => ['id' => $d->id, 'label' => $d->department_name, 'description' => $d->desription ?? ''])
            );
        })->name('departments.search');

        Route::get('/production-lines/search', function (Request $request) {
            $q = trim((string) $request->query('q', ''));
            if ($q === '')
                return response()->json([]);
            return response()->json(
                ProductionLine::where('status', 'active')
                    ->where('line_name', 'like', "%{$q}%")
                    ->orderBy('line_name')->limit(10)->get()
                    ->map(fn($pl) => ['id' => $pl->id, 'label' => $pl->line_name, 'description' => $pl->description ?? ''])
            );
        })->name('production-lines.search');
    });

    Route::get('/operators/{operator}', function (Operator $operator) {
        $operator->load([
            'ptmsReports.article',
            'ptmsReports.processVersion.process',
            'ptmsReports.processVersion.gsdElements.gsdCategory',
            'ptmsReports.factory',
            'ptmsReports.department',
            'ptmsReports.productionLine',
        ]);

        return view('operators.show', compact('operator'));
    })->name('operators.show');

    // Update employee personal details from profile page
    Route::put('/operators/{operator}/update-details', function (Request $request, Operator $operator) {
        if (auth()->user()->role->role_name === 'viewer') {
            abort(403, 'Unauthorized. Viewer role is read-only.');
        }
        $data = $request->validate([
            'start_date' => ['nullable', 'date'],
            'date_of_birth' => ['nullable', 'date'],
        ]);
        $operator->update($data);
        logActivity('Updated employee details: ' . $operator->operator_name, 'Employees');
        return redirect()->route('operators.show', $operator)->with('success', 'Employee details updated successfully.');
    })->name('operators.update-details');

    // Employee Profile (Lean Operations) - redirects to first active operator's profile
    Route::get('/operations/employee-profile', function () {
        $operator = Operator::where('status', 'active')->orderBy('operator_name')->first();
        if ($operator) {
            return redirect()->route('operators.show', $operator);
        }
        return redirect()->route('master-data.operators')->with('error', 'No active employees found. Please add an employee first.');
    })->name('employee-profile');

    Route::middleware('role:developer,admin,viewer')->prefix('operations')->name('operations.')->group(function () {
        Route::get('/', function () {
            return view('operations.module', ['module' => 'overview']);
        })->name('index');

        foreach (['cycle-time', 'breakdown', 'kaizen', 'skills', 'tpm', 'materials', 'vsm'] as $module) {
            Route::get('/' . $module, function () use ($module) {
                return view('operations.module', ['module' => $module]);
            })->name(str_replace('-', '.', $module));
        }

        // Line Balancing - dedicated routes
        Route::prefix('line-balancing')->name('line.balancing.')->group(function () {
            Route::get('/', [App\Http\Controllers\LineBalancingController::class, 'index'])->name('index');
            Route::post('/', [App\Http\Controllers\LineBalancingController::class, 'store'])->name('store');
            Route::get('/{id}', [App\Http\Controllers\LineBalancingController::class, 'edit'])->name('edit');
            Route::put('/{id}', [App\Http\Controllers\LineBalancingController::class, 'update'])->name('update');
            Route::post('/{id}/rows', [App\Http\Controllers\LineBalancingController::class, 'saveRows'])->name('saveRows');
            Route::patch('/{id}/deactivate', [App\Http\Controllers\LineBalancingController::class, 'deactivate'])->name('deactivate');
            Route::patch('/bulk-deactivate', [App\Http\Controllers\LineBalancingController::class, 'bulkDeactivate'])->name('bulkDeactivate');
            Route::get('/{id}/export', [App\Http\Controllers\LineBalancingController::class, 'export'])->name('export');
        });

        // AJAX: get production lines for a factory
        Route::get('/line-balancing/lines-by-factory/{factoryId}', [App\Http\Controllers\LineBalancingController::class, 'getLinesByFactory'])->name('line.balancing.linesByFactory');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
