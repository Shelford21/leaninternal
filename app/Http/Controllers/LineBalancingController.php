<?php

namespace App\Http\Controllers;

use App\Models\LineBalancingReport;
use App\Models\LineBalancingReportRow;
use App\Models\Factory;
use App\Models\Article;
use App\Models\ProductionLine;
use App\Models\MachineType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LineBalancingController extends Controller
{
    /**
     * Display the Line Balancing list page.
     */
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $filterColumn = $request->input('filter_column', '');
        $filterValue = $request->input('filter_value', '');
        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');
        $showInactive = $request->has('show_inactive');

        $query = LineBalancingReport::with(['factory', 'article', 'productionLine', 'createdBy']);

        // Status filter
        if ($showInactive) {
            $query->whereIn('status', ['active', 'inactive']);
        } else {
            $query->where('status', 'active');
        }

        // Search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('report_name', 'like', "%{$search}%")
                    ->orWhereHas('factory', function ($q2) use ($search) {
                        $q2->where('factory_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('article', function ($q2) use ($search) {
                        $q2->where('article_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('productionLine', function ($q2) use ($search) {
                        $q2->where('line_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('createdBy', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter
        if ($filterColumn && $filterValue) {
            switch ($filterColumn) {
                case 'factory':
                    $query->whereHas('factory', function ($q) use ($filterValue) {
                        $q->where('factory_name', $filterValue);
                    });
                    break;
                case 'article':
                    $query->whereHas('article', function ($q) use ($filterValue) {
                        $q->where('article_name', $filterValue);
                    });
                    break;
                case 'report_name':
                    $query->where('report_name', $filterValue);
                    break;
                case 'created_by':
                    $query->whereHas('createdBy', function ($q) use ($filterValue) {
                        $q->where('name', $filterValue);
                    });
                    break;
                case 'status':
                    $query->where('status', $filterValue);
                    break;
            }
        }

        // Sort
        $sortable = ['report_name', 'created_at', 'updated_at', 'status'];
        if (in_array($sort, $sortable)) {
            $query->orderBy($sort, $direction);
        } elseif ($sort === 'factory') {
            $query->join('factories', 'line_balancing_reports.factory_id', '=', 'factories.id')
                ->orderBy('factories.factory_name', $direction)
                ->select('line_balancing_reports.*');
        } elseif ($sort === 'article') {
            $query->join('articles', 'line_balancing_reports.article_id', '=', 'articles.id')
                ->orderBy('articles.article_name', $direction)
                ->select('line_balancing_reports.*');
        } elseif ($sort === 'created_by') {
            $query->join('users', 'line_balancing_reports.created_by', '=', 'users.id')
                ->orderBy('users.name', $direction)
                ->select('line_balancing_reports.*');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $reports = $query->paginate(15)->withQueryString();

        // Filter options
        $factories = Factory::where('status', 'active')->orderBy('factory_name')->pluck('factory_name');
        $articles = Article::where('status', 'active')->orderBy('article_name')->pluck('article_name');
        $reportNames = LineBalancingReport::where('status', 'active')->orderBy('report_name')->pluck('report_name');
        $creators = \App\Models\User::orderBy('name')->pluck('name');

        $filterOptions = [
            'factory' => $factories,
            'article' => $articles,
            'report_name' => $reportNames,
            'created_by' => $creators,
            'status' => collect(['active', 'inactive']),
        ];

        return view('operations.line-balancing.index', compact(
            'reports',
            'search',
            'filterColumn',
            'filterValue',
            'sort',
            'direction',
            'showInactive',
            'filterOptions'
        ));
    }

    /**
     * Store a new Line Balancing report.
     */
    public function store(Request $request)
    {
        $request->validate([
            'factory_id' => 'required|exists:factories,id',
            'article_id' => 'required|exists:articles,id',
            'line_id' => 'required|exists:production_lines,id',
            'report_name' => 'required|string|max:150',
            'working_hours_per_day' => 'nullable|numeric|min:0|max:24',
            'allowance_percent' => 'nullable|numeric|min:0|max:100',
            'update_date' => 'nullable|date',
        ]);

        $report = LineBalancingReport::create([
            'factory_id' => $request->factory_id,
            'article_id' => $request->article_id,
            'line_id' => $request->line_id,
            'report_name' => $request->report_name,
            'target_output_per_hour' => 0,
            'working_hours_per_day' => $request->working_hours_per_day ?? 8,
            'allowance_percent' => $request->allowance_percent ?? 15,
            'update_date' => $request->update_date,
            'status' => 'active',
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('operations.line.balancing.edit', $report->id)
            ->with('success', 'Line Balancing report created successfully.');
    }

    /**
     * Display the report editor.
     */
    public function edit($id)
    {
        $report = LineBalancingReport::with(['factory', 'article', 'productionLine', 'createdBy', 'rows.machineType', 'rows.employee'])
            ->findOrFail($id);

        $machineTypes = MachineType::where('status', 'active')->orderBy('machine_type')->get();
        $employees = \App\Models\Operator::where('status', 'active')->orderBy('operator_name')->get(['id', 'operator_name', 'employee_number']);

        return view('operations.line-balancing.edit', compact('report', 'machineTypes', 'employees'));
    }

    /**
     * Update the report header (target_output_per_hour).
     */
    public function update(Request $request, $id)
    {
        $report = LineBalancingReport::findOrFail($id);

        $request->validate([
            'target_output_per_hour' => 'required|integer|min:0',
            'output_actual' => 'nullable|integer|min:0',
            'working_hours_per_day' => 'nullable|numeric|min:0|max:24',
            'allowance_percent' => 'nullable|numeric|min:0|max:100',
            'update_date' => 'nullable|date',
        ]);

        $report->update([
            'target_output_per_hour' => $request->target_output_per_hour,
            'output_actual' => $request->output_actual ?: null,
            'working_hours_per_day' => $request->working_hours_per_day ?? $report->working_hours_per_day,
            'allowance_percent' => $request->allowance_percent ?? $report->allowance_percent,
            'update_date' => $request->update_date ?? $report->update_date,
        ]);

        return redirect()->route('operations.line.balancing.edit', $report->id)
            ->with('success', 'Report updated successfully.');
    }

    /**
     * Save all report rows (bulk update).
     */
    public function saveRows(Request $request, $id)
    {
        $report = LineBalancingReport::findOrFail($id);

        $request->validate([
            'rows' => 'required|array|min:1',
            'rows.*.row_number' => 'required|integer|min:1',
            'rows.*.machine_type_id' => 'nullable|exists:machine_types,id',
            'rows.*.process' => 'nullable|string|max:150',
            'rows.*.name' => 'nullable|string|max:150',
            'rows.*.employee_id' => 'nullable|exists:operators,id',
            'rows.*.joint_process' => 'nullable|string|max:150',
            'rows.*.operator' => 'required|integer|min:1',
            'rows.*.ct_1' => 'nullable|numeric|min:0',
            'rows.*.ct_2' => 'nullable|numeric|min:0',
            'rows.*.ct_3' => 'nullable|numeric|min:0',
            'rows.*.ct_4' => 'nullable|numeric|min:0',
            'rows.*.ct_5' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($report, $request) {
            // Delete existing rows
            $report->rows()->delete();

            // Insert new rows
            foreach ($request->rows as $rowData) {
                $employee = $rowData['employee_id'] ? \App\Models\Operator::find($rowData['employee_id']) : null;
                $report->rows()->create([
                    'row_number' => $rowData['row_number'],
                    'machine_type_id' => $rowData['machine_type_id'] ?? null,
                    'process' => $rowData['process'] ?? null,
                    'name' => $employee ? $employee->operator_name : ($rowData['name'] ?? null),
                    'employee_id' => $rowData['employee_id'] ?? null,
                    'joint_process' => $rowData['joint_process'] ?? null,
                    'operator' => $rowData['operator'],
                    'ct_1' => $rowData['ct_1'] ?? null,
                    'ct_2' => $rowData['ct_2'] ?? null,
                    'ct_3' => $rowData['ct_3'] ?? null,
                    'ct_4' => $rowData['ct_4'] ?? null,
                    'ct_5' => $rowData['ct_5'] ?? null,
                ]);
            }

            // Update target_output_per_hour and output_actual if provided
            if ($request->has('target_output_per_hour')) {
                $report->update([
                    'target_output_per_hour' => $request->target_output_per_hour,
                ]);
            }
            if ($request->has('output_actual')) {
                $report->update([
                    'output_actual' => $request->output_actual ?: null,
                ]);
            }
        });

        return redirect()->route('operations.line.balancing.edit', $report->id)
            ->with('success', 'Report rows saved successfully.');
    }

    /**
     * Soft delete (deactivate) a single report.
     */
    public function deactivate($id)
    {
        $report = LineBalancingReport::findOrFail($id);
        $report->update(['status' => 'inactive']);

        return redirect()->route('operations.line.balancing.index')
            ->with('success', 'Report deactivated successfully.');
    }

    /**
     * Bulk soft delete (deactivate) multiple reports.
     */
    public function bulkDeactivate(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:line_balancing_reports,id',
        ]);

        LineBalancingReport::whereIn('id', $request->ids)
            ->update(['status' => 'inactive']);

        return redirect()->route('operations.line.balancing.index')
            ->with('success', count($request->ids) . ' report(s) deactivated successfully.');
    }

    /**
     * Get production lines for a given factory (AJAX).
     */
    public function getLinesByFactory($factoryId)
    {
        // ProductionLine is linked to Division, not Factory.
        // We return all active production lines for now.
        $lines = ProductionLine::where('status', 'active')
            ->orderBy('line_name')
            ->get(['id', 'line_name']);

        return response()->json($lines);
    }

    /**
     * Export Line Balancing report as XLSX.
     * Reproduces the "LB Livlig" template layout with dynamic data.
     */
    public function export($id)
    {
        $report = LineBalancingReport::with(['factory', 'article', 'productionLine', 'rows.machineType', 'rows.employee', 'createdBy'])->findOrFail($id);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('LB Livlig');

        $A = \PhpOffice\PhpSpreadsheet\Style\Alignment::class;
        $B = \PhpOffice\PhpSpreadsheet\Style\Border::class;
        $F = \PhpOffice\PhpSpreadsheet\Style\Fill::class;

        // ════════════════════════════════════════════════════════
        // COLUMN WIDTHS  (A–V, spec-exact)
        // ════════════════════════════════════════════════════════
        foreach ([
            'A' => 2.5703125,
            'B' => 5.85546875,
            'C' => 12.7109375,
            'D' => 76.42578125,
            'E' => 19.85546875,
            'F' => 14.140625,
            'G' => 13.42578125,
            'H' => 7.7109375,
            'I' => 13,
            'J' => 13,
            'K' => 13,
            'L' => 13,
            'M' => 14.28515625,
            'N' => 13,
            'O' => 14,
            'P' => 19.28515625,
            'Q' => 13.5703125,
            'R' => 13,
            'S' => 16.5703125,
            'T' => 22.28515625,
            'U' => 13,
            'V' => 14.28515625,
        ] as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }
        $sheet->getColumnDimension('F')->setVisible(false);
        $sheet->getColumnDimension('S')->setVisible(false);

        // ════════════════════════════════════════════════════════
        // ROW HEIGHTS  (spec-exact)
        // ════════════════════════════════════════════════════════
        foreach ([
            2 => 23.25,
            3 => 43.15,
            4 => 31.5,
            5 => 21,
            6 => 21.75,
            7 => 22.15,
            8 => 27.6,
            9 => 36.75,
            10 => 24,
            11 => 25.15,
            12 => 25.15,
            13 => 25.15,
            14 => 25.15,
            15 => 25.15,
            16 => 25.15,
            17 => 28.5,
            18 => 25.15,
            19 => 28.5,
            20 => 25.15,
            21 => 25.15,
            22 => 25.15,
            23 => 25.15,
            24 => 27,
            25 => 25.15,
            26 => 25.15,
            27 => 30,
            28 => 26.25,
            29 => 29.25,
            30 => 29.25,
            31 => 25.15,
            32 => 31.5,
            33 => 18.75,
        ] as $r => $h) {
            $sheet->getRowDimension($r)->setRowHeight($h);
        }
        foreach ([35, 36, 37, 38, 39, 40, 41, 42, 43] as $r) {
            $sheet->getRowDimension($r)->setRowHeight(25.15);
        }
        $sheet->getRowDimension(44)->setRowHeight(3.75);

        // ════════════════════════════════════════════════════════
        // REUSABLE STYLE HELPERS
        // ════════════════════════════════════════════════════════
        $thinBorder = ['borders' => ['allBorders' => ['borderStyle' => $B::BORDER_THIN]]];
        $centerWrap = ['alignment' => ['horizontal' => $A::HORIZONTAL_CENTER, 'vertical' => $A::VERTICAL_CENTER, 'wrapText' => true]];

        $applyStyle = function ($range, array ...$styles) use ($sheet) {
            $merged = [];
            foreach ($styles as $s) {
                $merged = array_replace_recursive($merged, $s);
            }
            $sheet->getStyle($range)->applyFromArray($merged);
        };

        $cellFont = function ($name = 'Arial', $size = 12, $bold = false, $italic = false, $color = null) {
            $f = ['name' => $name, 'size' => $size, 'bold' => $bold, 'italic' => $italic];
            if ($color)
                $f['color'] = ['argb' => $color];
            return ['font' => $f];
        };

        $cellFill = function ($color) use ($F) {
            return ['fill' => ['fillType' => $F::FILL_SOLID, 'color' => ['argb' => $color]]];
        };

        $cellAlign = function ($h = 'center', $v = 'center', $wrap = true) use ($A) {
            $map = ['center' => $A::HORIZONTAL_CENTER, 'left' => $A::HORIZONTAL_LEFT, 'right' => $A::HORIZONTAL_RIGHT];
            return ['alignment' => ['horizontal' => $map[$h], 'vertical' => $v === 'center' ? $A::VERTICAL_CENTER : $A::VERTICAL_TOP, 'wrapText' => $wrap]];
        };

        // ════════════════════════════════════════════════════════
        // TOP REPORT AREA  (Rows 2–6)
        // ════════════════════════════════════════════════════════

        // Row 2 — Title
        $sheet->mergeCells('B2:Q2');
        $sheet->setCellValue('B2', 'LINE BALANCING SEWING');
        $applyStyle('B2', $cellFont('Arial', 18, true, false, 'FFFFFFFF'), $cellAlign(), $cellFill('FF4472C4'));

        // Row 3
        $sheet->setCellValue('D3', $report->report_name);
        $applyStyle('D3', $cellFont('Arial', 14, true), $cellAlign('left'));

        $sheet->mergeCells('E3:G3');
        $sheet->setCellValue('E3', 'Line');
        $applyStyle('E3:G3', $cellFont('Arial', 11, true), $cellAlign(), $thinBorder, $cellFill('FFD9E2F3'));

        $sheet->mergeCells('H3:K3');
        $sheet->setCellValue('H3', $report->productionLine->line_name ?? '-');
        $applyStyle('H3:K3', $cellFont('Arial', 11), $cellAlign(), $thinBorder);

        $sheet->mergeCells('L3:O3');
        $sheet->setCellValue('L3', 'PPH');
        $applyStyle('L3:O3', $cellFont('Arial', 11, true), $cellAlign(), $thinBorder, $cellFill('FFD9E2F3'));

        $sheet->setCellValue('P3', 'Target Output /Hours');
        $applyStyle('P3', $cellFont('Arial', 10, true), $cellAlign(), $thinBorder, $cellFill('FFD9E2F3'));

        $sheet->setCellValue('Q3', $report->target_output_per_hour ?: 0);
        $applyStyle('Q3', $cellFont('Arial', 12, true), $cellAlign(), $thinBorder, $cellFill('FFFFF2CC'));
        $sheet->getStyle('Q3')->getNumberFormat()->setFormatCode('0 " Pcs "');

        // Row 4
        $sheet->setCellValue('D4', $report->factory->factory_name ?? '-');
        $applyStyle('D4', $cellFont('Arial', 12, true), $cellAlign('left'));

        $sheet->mergeCells('E4:G4');
        $sheet->setCellValue('E4', 'Article');
        $applyStyle('E4:G4', $cellFont('Arial', 11, true), $cellAlign(), $thinBorder, $cellFill('FFD9E2F3'));

        $sheet->mergeCells('H4:K4');
        $sheet->setCellValue('H4', $report->article->article_name ?? '-');
        $applyStyle('H4:K4', $cellFont('Arial', 11), $cellAlign(), $thinBorder);

        // PPH/Productivity = Q3/H6
        $sheet->mergeCells('L4:O6');
        $sheet->setCellValue('L4', '=Q3/H6');
        $applyStyle('L4:O6', $cellFont('Arial', 14, true), $cellAlign(), $thinBorder, $cellFill('FFB4C6E7'));
        $sheet->getStyle('L4')->getNumberFormat()->setFormatCode('0.00');

        $sheet->setCellValue('P4', 'Target Output / Day');
        $applyStyle('P4', $cellFont('Arial', 10, true), $cellAlign(), $thinBorder, $cellFill('FFD9E2F3'));

        $workHrs = $report->working_hours_per_day ?? 8;
        $sheet->setCellValue('Q4', "=Q3*{$workHrs}");
        $applyStyle('Q4', $cellFont('Arial', 12, true), $cellAlign(), $thinBorder, $cellFill('FFFFF2CC'));
        $sheet->getStyle('Q4')->getNumberFormat()->setFormatCode('0');

        // Row 5
        $sheet->setCellValue('D5', 'CYCLE TIME ANALYZE');
        $applyStyle('D5', $cellFont('Arial', 12, true, false, 'FF1F4E79'), $cellAlign('left'));

        $sheet->mergeCells('E5:G5');
        $sheet->setCellValue('E5', 'Update');
        $applyStyle('E5:G5', $cellFont('Arial', 11, true), $cellAlign(), $thinBorder, $cellFill('FFD9E2F3'));

        $sheet->mergeCells('H5:K5');
        if ($report->update_date) {
            $h5Date = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(
                new \DateTime($report->update_date->format('Y-m-d'), new \DateTimeZone('UTC'))
            );
            $sheet->setCellValue('H5', $h5Date);
            $sheet->getStyle('H5')->getNumberFormat()->setFormatCode('d mmmm yyyy');
        } else {
            $sheet->setCellValue('H5', '-');
        }
        $applyStyle('H5:K5', $cellFont('Arial', 11), $cellAlign(), $thinBorder);

        $sheet->setCellValue('P5', 'Time 1 Hours');
        $applyStyle('P5', $cellFont('Arial', 10, true), $cellAlign(), $thinBorder, $cellFill('FFD9E2F3'));

        $sheet->setCellValue('Q5', 3600);
        $applyStyle('Q5', $cellFont('Arial', 12, true), $cellAlign(), $thinBorder, $cellFill('FFFFF2CC'));
        $sheet->getStyle('Q5')->getNumberFormat()->setFormatCode('0');

        // Row 6
        $sheet->mergeCells('E6:G6');
        $applyStyle('E6:G6', $thinBorder);
        $sheet->mergeCells('H6:K6');
        // H6 = G32 (Total Manpower) — formula, set after data rows

        $sheet->setCellValue('P6', 'Takt Time');
        $applyStyle('P6', $cellFont('Arial', 10, true), $cellAlign(), $thinBorder, $cellFill('FFD9E2F3'));
        $sheet->setCellValue('Q6', '=Q5/Q3');
        $applyStyle('Q6', $cellFont('Arial', 12, true), $cellAlign(), $thinBorder, $cellFill('FFFFF2CC'));
        $sheet->getStyle('Q6')->getNumberFormat()->setFormatCode('0.00');

        // ════════════════════════════════════════════════════════
        // MAIN TABLE HEADERS  (Rows 7–10)
        // ════════════════════════════════════════════════════════
        $hdrFill = $cellFill('FF4472C4');
        $hdrFont = $cellFont('Arial', 12, true, false, 'FFFFFFFF');
        $hdrStyle = array_merge($hdrFont, $centerWrap, $thinBorder, $hdrFill);
        $subFill = $cellFill('FF2F5597');
        $subFont = $cellFont('Arial', 10, true, false, 'FFFFFFFF');
        $subStyle = array_merge($subFont, $centerWrap, $thinBorder, $subFill);
        $sub2Fill = $cellFill('FF5B9BD5');
        $sub2Style = array_merge($cellFont('Arial', 11, true, false, 'FFFFFFFF'), $centerWrap, $thinBorder, $sub2Fill);

        $row7Merge = [
            'B7:B10',
            'C7:C10',
            'D7:D10',
            'E7:E10',
            'F7:F10',
            'G7:G9',
            'H7:L9',
            'M7:M9',
            'N7:N9',
            'O7:O9',
            'P7:P9',
            'Q7:Q9',
            'R7:R10',
            'S7:S9',
            'T7:T9',
        ];
        foreach ($row7Merge as $m)
            $sheet->mergeCells($m);

        $sheet->setCellValue('B7', 'No');
        $applyStyle('B7:B10', $hdrStyle);
        $sheet->setCellValue('C7', 'Machine');
        $applyStyle('C7:C10', $hdrStyle);
        $sheet->setCellValue('D7', 'Process');
        $applyStyle('D7:D10', $hdrStyle);
        $sheet->setCellValue('E7', 'Name');
        $applyStyle('E7:E10', $hdrStyle);
        $sheet->setCellValue('F7', 'Joint process');
        $applyStyle('F7:F10', $hdrStyle);

        $sheet->setCellValue('G7', 'Operator');
        $applyStyle('G7:G9', $hdrStyle);
        $sheet->setCellValue('G10', '=SUM(G11)');
        $applyStyle('G10', $cellFont('Arial', 11, true), $cellAlign(), $thinBorder, $cellFill('FFD6DCE4'));
        $sheet->getStyle('G10')->getNumberFormat()->setFormatCode('0');

        $sheet->setCellValue('H7', 'Cycle Time');
        $applyStyle('H7:L9', $hdrStyle);
        foreach (['H' => 1, 'I' => 2, 'J' => 3, 'K' => 4, 'L' => 5] as $col => $n) {
            $sheet->setCellValue($col . '10', $n);
        }
        $applyStyle('H10:L10', $subStyle);

        $sheet->setCellValue('M7', 'Average Cycle Time');
        $applyStyle('M7:M9', $sub2Style);
        $sheet->setCellValue('N7', 'Average Cycle Time + Allowance 15 %');
        $applyStyle('N7:N9', $subStyle);
        $sheet->setCellValue('O7', 'Average CT / Process');
        $applyStyle('O7:O9', $sub2Style);
        $sheet->setCellValue('P7', 'Output / Hours');
        $applyStyle('P7:P9', $sub2Style);
        $sheet->setCellValue('Q7', 'Output Process / Hours');
        $applyStyle('Q7:Q9', $sub2Style);
        $sheet->setCellValue('R7', 'Request Operator');
        $applyStyle('R7:R10', $hdrStyle);
        $sheet->setCellValue('S7', 'Average Output/ Hour');
        $applyStyle('S7:S9', $hdrStyle);
        $sheet->setCellValue('T7', 'Potential Output/Process');
        $applyStyle('T7:T9', $hdrStyle);

        // Helper row 10 (M–T)
        $applyStyle('M10:T10', $cellFont('Arial', 10, true), $cellAlign(), $thinBorder, $cellFill('FFD6DCE4'));
        $sheet->setCellValue('N10', '=MAX(N11)');
        $sheet->getStyle('N10')->getNumberFormat()->setFormatCode('0.00');
        $sheet->setCellValue('O10', '=MAX(O11)');
        $sheet->getStyle('O10')->getNumberFormat()->setFormatCode('0.00');
        $sheet->setCellValue('P10', '=MAX(P11)');
        $sheet->getStyle('P10')->getNumberFormat()->setFormatCode('0');
        $sheet->setCellValue('Q10', '=MIN(Q11)');
        $sheet->getStyle('Q10')->getNumberFormat()->setFormatCode('0');
        $sheet->setCellValue('T10', '=MIN(T11)');
        $sheet->getStyle('T10')->getNumberFormat()->setFormatCode('0');

        // ════════════════════════════════════════════════════════
        // DATA ROWS  (Rows 11–31 or more)
        // ════════════════════════════════════════════════════════
        $dataFont = $cellFont('Arial', 12);
        $dataFontNarrow = $cellFont('Arial Narrow', 11);
        $dataStyle = array_merge($dataFont, $centerWrap, $thinBorder);
        $dataStyleCalc = array_merge($dataFontNarrow, $centerWrap, $thinBorder);
        $processFill = $cellFill('FFDCE6F1');
        $outputFill = $cellFill('FFE2EFDA');

        $sortedRows = $report->rows->sortBy('row_number')->values();
        foreach ($sortedRows as $i => $row) {
            $r = 11 + $i;

            // Set row height for data rows
            $sheet->getRowDimension($r)->setRowHeight(25.15);

            // B = No
            $sheet->setCellValue("B{$r}", $row->row_number);
            $applyStyle("B{$r}", $dataStyle);

            // C = Machine
            $sheet->setCellValue("C{$r}", $row->machineType->machine_type ?? '-');
            $applyStyle("C{$r}", $dataStyle);

            // D = Process (left-aligned, wrapped)
            $sheet->setCellValue("D{$r}", $row->process ?? '-');
            $applyStyle("D{$r}", $dataFont, $cellAlign('left', 'center', true), $thinBorder);

            // E = Name
            $sheet->setCellValue("E{$r}", $row->name ?? '-');
            $applyStyle("E{$r}", $dataStyle);

            // F = Joint process (hidden)
            $sheet->setCellValue("F{$r}", $row->joint_process ?? '');
            $applyStyle("F{$r}", $dataStyle);

            // G = Operator
            $sheet->setCellValue("G{$r}", $row->operator);
            $applyStyle("G{$r}", $dataStyle);
            $sheet->getStyle("G{$r}")->getNumberFormat()->setFormatCode('0');

            // H–L = Cycle Time observations
            $ctVals = ['H' => $row->ct_1, 'I' => $row->ct_2, 'J' => $row->ct_3, 'K' => $row->ct_4, 'L' => $row->ct_5];
            foreach ($ctVals as $col => $val) {
                if ($val !== null && $val !== '') {
                    $sheet->setCellValue("{$col}{$r}", $val);
                }
                $applyStyle("{$col}{$r}", $dataFontNarrow, $cellAlign(), $thinBorder);
                $sheet->getStyle("{$col}{$r}")->getNumberFormat()->setFormatCode('0.00');
            }

            // M = Average Cycle Time
            $sheet->setCellValue("M{$r}", "=AVERAGE(H{$r}:L{$r})");
            $applyStyle("M{$r}", $dataStyleCalc, $processFill);
            $sheet->getStyle("M{$r}")->getNumberFormat()->setFormatCode('0.00');

            // N = Avg CT + 15%
            $sheet->setCellValue("N{$r}", "=M{$r}*1.15");
            $applyStyle("N{$r}", $dataStyleCalc, $processFill);
            $sheet->getStyle("N{$r}")->getNumberFormat()->setFormatCode('0.00');

            // O = Avg CT / Process
            $sheet->setCellValue("O{$r}", "=M{$r}");
            $applyStyle("O{$r}", $dataStyleCalc, $processFill);
            $sheet->getStyle("O{$r}")->getNumberFormat()->setFormatCode('0.00');

            // P = Output / Hours
            $sheet->setCellValue("P{$r}", "=3600/N{$r}");
            $applyStyle("P{$r}", $dataStyleCalc, $outputFill);
            $sheet->getStyle("P{$r}")->getNumberFormat()->setFormatCode('0');

            // Q = Output Process / Hours
            $sheet->setCellValue("Q{$r}", "=P{$r}");
            $applyStyle("Q{$r}", $dataStyleCalc, $outputFill);
            $sheet->getStyle("Q{$r}")->getNumberFormat()->setFormatCode('0');

            // R = Request Operator
            $sheet->setCellValue("R{$r}", "=O{$r}/\$Q\$6");
            $applyStyle("R{$r}", $dataStyleCalc, $thinBorder);
            $sheet->getStyle("R{$r}")->getNumberFormat()->setFormatCode('0.00');

            // S = Average Output/Hour (hidden)
            $applyStyle("S{$r}", $cellFont('Arial', 16, true), $cellAlign(), $thinBorder);
            $sheet->getStyle("S{$r}")->getNumberFormat()->setFormatCode('0');

            // T = Potential Output / Process
            $sheet->setCellValue("T{$r}", "=Q{$r}*8");
            $applyStyle("T{$r}", $dataStyleCalc, $thinBorder);
            $sheet->getStyle("T{$r}")->getNumberFormat()->setFormatCode('0');
        }

        // ════════════════════════════════════════════════════════
        // TOTAL ROW  (Row 32)
        // ════════════════════════════════════════════════════════
        $totalStyle = array_merge($cellFont('Arial', 12, true), $centerWrap, $thinBorder, $cellFill('FFD6DCE4'));
        $lastDataRow = 10 + $sortedRows->count();
        $totalRow = $lastDataRow + 1;  // row right after last data

        // Merge E–T for total label
        $sheet->mergeCells("E{$totalRow}:T{$totalRow}");
        $sheet->setCellValue("E{$totalRow}", 'Total');
        $applyStyle("E{$totalRow}:T{$totalRow}", $totalStyle);

        // Total formulas
        $sheet->setCellValue("G{$totalRow}", "=SUM(G11:G{$lastDataRow})");
        $sheet->getStyle("G{$totalRow}")->getNumberFormat()->setFormatCode('0');
        $sheet->setCellValue("M{$totalRow}", "=SUM(M11:M{$lastDataRow})");
        $sheet->getStyle("M{$totalRow}")->getNumberFormat()->setFormatCode('0.00');
        $sheet->setCellValue("N{$totalRow}", "=SUM(N11:N{$lastDataRow})");
        $sheet->getStyle("N{$totalRow}")->getNumberFormat()->setFormatCode('0.00');
        $sheet->setCellValue("O{$totalRow}", "=MAX(O11:O{$lastDataRow})");
        $sheet->getStyle("O{$totalRow}")->getNumberFormat()->setFormatCode('0.00');
        $sheet->setCellValue("P{$totalRow}", "=MAX(P11:P{$lastDataRow})");
        $sheet->getStyle("P{$totalRow}")->getNumberFormat()->setFormatCode('0');
        $sheet->setCellValue("Q{$totalRow}", "=MIN(Q11:Q{$lastDataRow})");
        $sheet->getStyle("Q{$totalRow}")->getNumberFormat()->setFormatCode('0');
        $sheet->setCellValue("R{$totalRow}", "=SUM(R11:R{$lastDataRow})");
        $sheet->getStyle("R{$totalRow}")->getNumberFormat()->setFormatCode('0.00');
        $sheet->setCellValue("T{$totalRow}", "=MIN(T11:T{$lastDataRow})");
        $sheet->getStyle("T{$totalRow}")->getNumberFormat()->setFormatCode('0');

        // Set total row height
        $sheet->getRowDimension($totalRow)->setRowHeight(31.5);

        // H6:K6 = Total Manpower (=G32 in spec, but row is dynamic)
        $sheet->setCellValue("H6", "=G{$totalRow}");
        $applyStyle('H6:K6', $cellFont('Arial', 11, true), $cellAlign(), $thinBorder, $cellFill('FFD9E2F3'));
        $sheet->getStyle('H6')->getNumberFormat()->setFormatCode('0.00');

        // ════════════════════════════════════════════════════════
        // SUMMARY KPI SECTION  (Rows 35–43)
        // ════════════════════════════════════════════════════════
        $labelStyle = array_merge($cellFont('Arial', 12, true, false, 'FFFFFFFF'), $centerWrap, $thinBorder, $cellFill('FF4472C4'));
        $valueStyle = array_merge($cellFont('Arial', 13, true), $cellAlign(), $thinBorder, $cellFill('FFFFF2CC'));
        $unitStyle = array_merge($cellFont('Arial', 10), $cellAlign(), $thinBorder, $cellFill('FFD6DCE4'));

        $summaryRows = [
            35 => ['TOTAL CYCLE TIME', "=N{$totalRow}", 'SEC'],
            36 => ['TOTAL MANPOWER', "=G{$totalRow}", 'PERSON'],
            37 => ['AVERAGE STANDARD TIME', "=R35/R36", 'SEC / PERSON'],
            38 => ['TOTAL WORKING TIME', 28800, 'SEC / DAY / PERSON'],
            39 => ['TARGET PER PCS', '=ROUND(R38/R35,0)', 'PCS / DAY / PERSON'],
            40 => ['TARGET LINE PER DAY', '=R36*R39', 'PCS/ DAY'],
            41 => ['TARGET LINE PER HOUR', '=R40/8', 'PCS/HOUR'],
            42 => ['MAXIMUM BASE ON CYLE TIME', "=3600/N10", 'PCS/HOUR'],
            43 => ['OUTPUT ACTUAL', $report->output_actual ?: 0, 'PCS/HOUR'],
        ];

        foreach ($summaryRows as $r => [$label, $value, $unit]) {
            $sheet->mergeCells("M{$r}:Q{$r}");
            $sheet->setCellValue("M{$r}", $label);
            $applyStyle("M{$r}:Q{$r}", $labelStyle);

            $sheet->setCellValue("R{$r}", $value);
            $applyStyle("R{$r}", $valueStyle);
            $sheet->getStyle("R{$r}")->getNumberFormat()->setFormatCode('0.00');

            $sheet->mergeCells("S{$r}:T{$r}");
            $sheet->setCellValue("S{$r}", $unit);
            $applyStyle("S{$r}:T{$r}", $unitStyle);
        }

        // Specific R-column formats
        $sheet->getStyle('R36')->getNumberFormat()->setFormatCode('0');
        $sheet->getStyle('R38')->getNumberFormat()->setFormatCode('0');
        $sheet->getStyle('R39')->getNumberFormat()->setFormatCode('0');
        $sheet->getStyle('R40')->getNumberFormat()->setFormatCode('0');
        $sheet->getStyle('R43')->getNumberFormat()->setFormatCode('0');

        // Extra unit sub-labels in T column
        foreach ([37 => 'SEC', 38 => 'SEC', 39 => 'PCS'] as $rr => $u) {
            $sheet->setCellValue("T{$rr}", $u);
            $applyStyle("T{$rr}", $unitStyle);
        }

        // S11 merge (hidden column placeholder)
        $sheet->mergeCells('S11');

        // ════════════════════════════════════════════════════════
        // YAMAZUMI BAR CHART
        // ════════════════════════════════════════════════════════
        $chartDataRows = $sortedRows->count();
        if ($chartDataRows > 0) {
            $dataSeries = new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues(
                \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues::DATASERIES_TYPE_NUMBER,
                "'LB Livlig'!\$P\$11:\$P\${$lastDataRow}",
                null,
                $chartDataRows
            );
            $catSeries = new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues(
                \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues::DATASERIES_TYPE_STRING,
                "'LB Livlig'!\$B\$11:\$B\${$lastDataRow}",
                null,
                $chartDataRows
            );
            $series = new \PhpOffice\PhpSpreadsheet\Chart\DataSeries(
                \PhpOffice\PhpSpreadsheet\Chart\DataSeries::TYPE_BARCHART,
                \PhpOffice\PhpSpreadsheet\Chart\DataSeries::GROUPING_CLUSTERED,
                [0],
                [],
                [$catSeries],
                [$dataSeries]
            );
            $series->setPlotDirection(\PhpOffice\PhpSpreadsheet\Chart\DataSeries::DIRECTION_COL);
            $plotArea = new \PhpOffice\PhpSpreadsheet\Chart\PlotArea(null, [$series]);
            $legend = new \PhpOffice\PhpSpreadsheet\Chart\Legend(
                \PhpOffice\PhpSpreadsheet\Chart\Legend::POSITION_BOTTOM,
                null,
                false
            );
            $chart = new \PhpOffice\PhpSpreadsheet\Chart\Chart(
                'YamazumiChart',
                new \PhpOffice\PhpSpreadsheet\Chart\Title('Yamazumi Chart Before'),
                $legend,
                $plotArea
            );
            $chartRow = $totalRow + 14;
            $chart->setTopLeftPosition("B{$chartRow}");
            $chart->setBottomRightPosition('T' . ($chartRow + 22));
            $sheet->addChart($chart);
        }

        // ════════════════════════════════════════════════════════
        // OUTPUT
        // ════════════════════════════════════════════════════════
        $filename = 'LineBalancing_' . preg_replace('/[^A-Za-z0-9_]/', '_', $report->report_name) . '_' . now()->format('Ymd_His') . '.xlsx';

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->setIncludeCharts(true);
        $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}