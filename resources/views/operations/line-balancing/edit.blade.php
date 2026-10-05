<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('operations.index') }}" class="text-teal-600 hover:text-teal-700">Lean Operations</a>
            <span class="text-slate-300">/</span>
            <a href="{{ route('operations.line.balancing.index') }}" class="text-teal-600 hover:text-teal-700">Line
                Balancing</a>
            <span class="text-slate-300">/</span>
            <h2 class="font-semibold text-lg text-slate-800 dark:text-slate-200 leading-tight">
                {{ $report->report_name }}</h2>
        </div>
    </x-slot>

    <div class="py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto space-y-6">
            @if (session('success'))
                <div
                    class="rounded-xl border border-emerald-200 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-900/30 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-300">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Report Header --}}
            <div
                class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm overflow-hidden">
                <div class="bg-slate-950 p-6 text-white">
                    <div class="flex items-start justify-between">
                        <div>
                            <div
                                class="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-emerald-300">
                                <span class="text-lg">⇄</span> Line Balancing Report
                            </div>
                            <h1 class="text-2xl font-semibold tracking-tight">{{ $report->report_name }}</h1>
                        </div>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('operations.line.balancing.export', $report->id) }}"
                                class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500"
                                title="Export to Excel">
                                📊 Export XLSX
                            </a>
                            @if ($report->article && $report->article->photo_path)
                                <img src="{{ asset('storage/' . $report->article->photo_path) }}"
                                    alt="{{ $report->article->article_name }}"
                                    class="h-16 w-16 rounded-xl object-cover ring-2 ring-white/20" />
                            @else
                                <div
                                    class="flex h-16 w-16 items-center justify-center rounded-xl bg-slate-800 ring-2 ring-white/20">
                                    <span class="text-2xl text-slate-500">📷</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Header Info Grid --}}
                <div class="grid grid-cols-2 gap-4 p-6 sm:grid-cols-5">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Factory</span>
                        <p class="mt-1 text-sm font-medium text-slate-900 dark:text-slate-100">
                            {{ $report->factory->factory_name ?? '—' }}</p>
                    </div>
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Line</span>
                        <p class="mt-1 text-sm font-medium text-slate-900 dark:text-slate-100">
                            {{ $report->productionLine->line_name ?? '—' }}</p>
                    </div>
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Article</span>
                        <p class="mt-1 text-sm font-medium text-slate-900 dark:text-slate-100">
                            {{ $report->article->article_name ?? '—' }}</p>
                    </div>
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Update Date</span>
                        <p class="mt-1 text-sm font-medium text-slate-900 dark:text-slate-100">
                            {{ $report->update_date ? $report->update_date->format('Y-m-d') : '—' }}</p>
                    </div>
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Created By</span>
                        <p class="mt-1 text-sm font-medium text-slate-900 dark:text-slate-100">
                            {{ $report->createdBy->name ?? '—' }}</p>
                    </div>
                </div>
            </div>

            {{-- Target Output & Settings --}}
            <div
                class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-6 shadow-sm">
                <form id="target-form" action="{{ route('operations.line.balancing.update', $report->id) }}"
                    method="POST">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label
                                class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">Target
                                Output / Hours (PPH)</label>
                            <input type="number" name="target_output_per_hour"
                                value="{{ $report->target_output_per_hour }}" min="0"
                                class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none" />
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">Output
                                Actual (PCS/Hour)</label>
                            <input type="number" name="output_actual"
                                value="{{ $report->output_actual }}" min="0"
                                class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500"
                                title="Working hours per day (default: 8)">Working Hours / Day</label>
                            <input type="number" name="working_hours_per_day"
                                value="{{ $report->working_hours_per_day }}" min="0" max="24" step="0.5"
                                class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500"
                                title="Allowance percentage (default: 15%)">Allowance (%)</label>
                            <input type="number" name="allowance_percent"
                                value="{{ $report->allowance_percent }}" min="0" max="100" step="0.5"
                                class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none" />
                        </div>
                    </div>
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">Update
                                Date</label>
                            <input type="date" name="update_date"
                                value="{{ $report->update_date ? $report->update_date->format('Y-m-d') : '' }}"
                                class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none" />
                        </div>
                        <div>
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Target Output /
                                Day</span>
                            <p id="target-per-day"
                                class="mt-1 text-lg font-semibold text-slate-900 dark:text-slate-100">
                                {{ $report->target_output_per_hour * $report->working_hours_per_day }}</p>
                        </div>
                        <div>
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Takt Time
                                (seconds)</span>
                            <p id="takt-time"
                                class="mt-1 text-lg font-semibold text-emerald-600 dark:text-emerald-400">
                                {{ $report->target_output_per_hour > 0 ? round(3600 / $report->target_output_per_hour, 2) : '—' }}
                            </p>
                        </div>
                        <div>
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">PPH
                                (Productivity)</span>
                            <p id="pph-display"
                                class="mt-1 text-lg font-semibold text-indigo-600 dark:text-indigo-400">
                                {{ $report->rows->sum('operator') > 0 ? round($report->target_output_per_hour / $report->rows->sum('operator'), 2) : '—' }}
                            </p>
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button type="submit"
                            class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">Update
                            Settings</button>
                    </div>
                </form>
            </div>

            {{-- Main Report Table --}}
            <div
                class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Operator / Process Table
                        </h2>
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="addRow()"
                                class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500">
                                + Add Row
                            </button>
                        </div>
                    </div>

                    {{-- Stopwatch --}}
                    <div
                        class="mb-6 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/50 p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Cycle Time
                                    Stopwatch</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    Start → Lap 5 times (fills CT 1–5) → Stop to apply. Each lap records one cycle
                                    time observation for the target row.
                                </p>
                            </div>
                            <div class="text-right">
                                <p id="stopwatch-display"
                                    class="text-3xl font-mono font-bold text-slate-900 dark:text-slate-100">
                                    00:00.00</p>
                                <p id="lap-display" class="text-sm text-slate-500 dark:text-slate-400"></p>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center gap-2 flex-wrap">
                            <button type="button" id="btn-start" onclick="stopwatchStart()"
                                class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500">Start</button>
                            <button type="button" id="btn-stop" onclick="stopwatchStop()" disabled
                                class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-500 disabled:opacity-50 disabled:cursor-not-allowed">Stop</button>
                            <button type="button" id="btn-lap" onclick="stopwatchLap()" disabled
                                class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-500 disabled:opacity-50 disabled:cursor-not-allowed">Lap</button>
                            <button type="button" id="btn-reset" onclick="stopwatchReset()"
                                class="rounded-lg bg-slate-600 px-4 py-2 text-sm font-medium text-white hover:bg-slate-500">Reset</button>
                            <select id="stopwatch-target-row"
                                class="ml-4 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm text-slate-600 dark:text-slate-300 focus:border-indigo-500 focus:outline-none">
                                <option value="">Select target row...</option>
                            </select>
                            <span id="lap-counter"
                                class="ml-2 text-xs font-semibold text-slate-500 dark:text-slate-400">0 / 5
                                laps</span>
                        </div>
                    </div>

                    {{-- Report Table --}}
                    <form id="rows-form"
                        action="{{ route('operations.line.balancing.saveRows', $report->id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="target_output_per_hour"
                            value="{{ $report->target_output_per_hour }}" />
                        <input type="hidden" name="output_actual" value="{{ $report->output_actual }}" />
                        <div class="overflow-x-auto" style="min-width: 100%;">
                            <table class="divide-y divide-slate-200 dark:divide-slate-700 text-left text-sm"
                                id="report-table" style="min-width: 1400px;">
                                <thead class="bg-slate-50 dark:bg-slate-700/50">
                                    <tr>
                                        <th
                                            class="px-3 py-3 font-semibold text-slate-600 dark:text-slate-300 w-10 text-center">
                                            No</th>
                                        <th class="px-3 py-3 font-semibold text-slate-600 dark:text-slate-300 w-32">Machine
                                        </th>
                                        <th class="px-3 py-3 font-semibold text-slate-600 dark:text-slate-300 w-36">Process
                                        </th>
                                        <th class="px-3 py-3 font-semibold text-slate-600 dark:text-slate-300 w-44">Employee (Name)
                                        </th>
                                        <th
                                            class="px-3 py-3 font-semibold text-slate-600 dark:text-slate-300 w-16 text-center">
                                            Opr</th>
                                        <th
                                            class="px-3 py-3 font-semibold text-slate-600 dark:text-slate-300 w-20 text-center bg-blue-50 dark:bg-blue-900/20">
                                            CT 1</th>
                                        <th
                                            class="px-3 py-3 font-semibold text-slate-600 dark:text-slate-300 w-20 text-center bg-blue-50 dark:bg-blue-900/20">
                                            CT 2</th>
                                        <th
                                            class="px-3 py-3 font-semibold text-slate-600 dark:text-slate-300 w-20 text-center bg-blue-50 dark:bg-blue-900/20">
                                            CT 3</th>
                                        <th
                                            class="px-3 py-3 font-semibold text-slate-600 dark:text-slate-300 w-20 text-center bg-blue-50 dark:bg-blue-900/20">
                                            CT 4</th>
                                        <th
                                            class="px-3 py-3 font-semibold text-slate-600 dark:text-slate-300 w-20 text-center bg-blue-50 dark:bg-blue-900/20">
                                            CT 5</th>
                                        <th
                                            class="px-3 py-3 font-semibold text-slate-600 dark:text-slate-300 w-24 text-center"
                                            title="Average CT = AVERAGE(non-blank CT observations)">Avg CT</th>
                                        <th
                                            class="px-3 py-3 font-semibold text-slate-600 dark:text-slate-300 w-24 text-center"
                                            title="Average CT × (1 + Allowance%)">Avg CT +Allow.</th>
                                        <th
                                            class="px-3 py-3 font-semibold text-slate-600 dark:text-slate-300 w-24 text-center"
                                            title="Average CT per Process = Average Cycle Time">Avg CT/Opr</th>
                                        <th
                                            class="px-3 py-3 font-semibold text-slate-600 dark:text-slate-300 w-24 text-center"
                                            title="Output/Hour = 3600 ÷ (Avg CT + Allowance)">Output/Hr</th>
                                        <th
                                            class="px-3 py-3 font-semibold text-slate-600 dark:text-slate-300 w-28 text-center"
                                            title="Output Process/Hour = Output/Hour">Output Proc/Hr</th>
                                        <th
                                            class="px-3 py-3 font-semibold text-slate-600 dark:text-slate-300 w-20 text-center"
                                            title="Request Operator = Avg CT/Process ÷ Takt Time">Req Opr</th>
                                        <th
                                            class="px-3 py-3 font-semibold text-slate-600 dark:text-slate-300 w-28 text-center"
                                            title="Potential Output = Output/Process/Hour × Working Hours/Day">Potential Output</th>
                                        <th class="px-3 py-3 w-12"></th>
                                    </tr>
                                </thead>
                                <tbody id="rows-body" class="divide-y divide-slate-200 dark:divide-slate-700">
                                    @foreach ($report->rows->sortBy('row_number') as $row)
                                        <tr data-row-id="{{ $row->id }}">
                                            <td class="px-3 py-2 text-center">
                                                <span
                                                    class="row-number text-sm font-medium text-slate-500">{{ $row->row_number }}</span>
                                                <input type="hidden" name="rows[{{ $loop->index }}][row_number]"
                                                    value="{{ $row->row_number }}" />
                                            </td>
                                            <td class="px-3 py-2">
                                                <select name="rows[{{ $loop->index }}][machine_type_id]"
                                                    class="row-machine w-full rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-2 py-1.5 text-xs focus:border-indigo-500 focus:outline-none">
                                                    <option value="">—</option>
                                                    @foreach ($machineTypes as $mt)
                                                        <option value="{{ $mt->id }}"
                                                            @selected($row->machine_type_id == $mt->id)>
                                                            {{ $mt->machine_type }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="px-3 py-2">
                                                <input type="text" name="rows[{{ $loop->index }}][process]"
                                                    value="{{ $row->process }}"
                                                    class="row-process w-full rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-2 py-1.5 text-xs focus:border-indigo-500 focus:outline-none" />
                                            </td>
                                            <td class="px-3 py-2">
                                                <div class="relative employee-ac-wrapper">
                                                    <input type="text"
                                                        class="employee-search-input row-employee w-full rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-2 py-1.5 text-xs focus:border-indigo-500 focus:outline-none"
                                                        value="{{ $row->employee?->operator_name ?? '' }}"
                                                        placeholder="Search employee..." autocomplete="off" />
                                                    <input type="hidden" name="rows[{{ $loop->index }}][employee_id]"
                                                        value="{{ $row->employee_id }}" class="employee-id-input" />
                                                    <input type="hidden" name="rows[{{ $loop->index }}][name]"
                                                        value="{{ $row->name }}" class="employee-name-input" />
                                                    <div class="employee-ac-dropdown absolute left-0 top-full mt-1 z-50 w-full max-h-48 overflow-y-auto rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 shadow-lg hidden">
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-3 py-2">
                                                <input type="number" name="rows[{{ $loop->index }}][operator]"
                                                    value="{{ $row->operator }}" min="1"
                                                    class="row-operator w-full rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-2 py-1.5 text-xs text-center focus:border-indigo-500 focus:outline-none" />
                                            </td>
                                            {{-- CT 1-5 columns --}}
                                            @foreach (['ct_1', 'ct_2', 'ct_3', 'ct_4', 'ct_5'] as $ctKey)
                                                <td class="px-3 py-2 bg-blue-50/50 dark:bg-blue-900/10">
                                                    <input type="number"
                                                        name="rows[{{ $loop->parent->index }}][{{ $ctKey }}]"
                                                        value="{{ $row->$ctKey }}" min="0" step="0.01"
                                                        class="row-ct w-full rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-2 py-1.5 text-xs text-center focus:border-indigo-500 focus:outline-none" />
                                                </td>
                                            @endforeach
                                            {{-- Calculated columns --}}
                                            <td
                                                class="px-3 py-2 text-xs text-center text-slate-600 dark:text-slate-300 calc-avg-ct">
                                                —</td>
                                            <td
                                                class="px-3 py-2 text-xs text-center text-slate-600 dark:text-slate-300 calc-avg-ct-allowance">
                                                —</td>
                                            <td
                                                class="px-3 py-2 text-xs text-center text-slate-600 dark:text-slate-300 calc-avg-ct-process">
                                                —</td>
                                            <td
                                                class="px-3 py-2 text-xs text-center text-slate-600 dark:text-slate-300 calc-output-hr">
                                                —</td>
                                            <td
                                                class="px-3 py-2 text-xs text-center text-slate-600 dark:text-slate-300 calc-output-process-hr">
                                                —</td>
                                            <td
                                                class="px-3 py-2 text-xs text-center text-slate-600 dark:text-slate-300 calc-req-operator">
                                                —</td>
                                            <td
                                                class="px-3 py-2 text-xs text-center text-slate-600 dark:text-slate-300 calc-potential-output">
                                                —</td>
                                            <td class="px-3 py-2 text-center">
                                                <button type="button" onclick="removeRow(this)"
                                                    class="text-red-400 hover:text-red-600 dark:hover:text-red-300">✕</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                    {{-- Total Row --}}
                                    <tr id="total-row" class="bg-slate-100 dark:bg-slate-700 font-semibold">
                                        <td class="px-3 py-2 text-center text-xs text-slate-700 dark:text-slate-200" colspan="3">TOTAL</td>
                                        <td class="px-3 py-2 text-xs text-center text-slate-700 dark:text-slate-200"></td>
                                        <td class="px-3 py-2 text-xs text-center text-slate-700 dark:text-slate-200" id="total-operator">—</td>
                                        <td class="px-3 py-2 text-xs text-center text-slate-700 dark:text-slate-200" colspan="5"></td>
                                        <td class="px-3 py-2 text-xs text-center text-slate-700 dark:text-slate-200" id="total-avg-ct-allowance">—</td>
                                        <td class="px-3 py-2 text-xs text-center text-slate-700 dark:text-slate-200"></td>
                                        <td class="px-3 py-2 text-xs text-center text-slate-700 dark:text-slate-200"></td>
                                        <td class="px-3 py-2 text-xs text-center text-slate-700 dark:text-slate-200" id="total-output-proc-hr">—</td>
                                        <td class="px-3 py-2 text-xs text-center text-slate-700 dark:text-slate-200" id="total-req-operator">—</td>
                                        <td class="px-3 py-2 text-xs text-center text-slate-700 dark:text-slate-200" id="total-potential-output">—</td>
                                        <td class="px-3 py-2"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 flex justify-end">
                            <button type="submit"
                                class="rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-indigo-500">Save
                                Report</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Summary Metrics --}}
            <div
                class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">Summary Metrics</h2>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                    <div class="rounded-xl bg-slate-50 dark:bg-slate-700/50 p-4">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Cycle
                            Time</span>
                        <p id="summary-total-ct" class="mt-1 text-xl font-bold text-slate-900 dark:text-slate-100">—
                        </p>
                    </div>
                    <div class="rounded-xl bg-slate-50 dark:bg-slate-700/50 p-4">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total
                            Manpower</span>
                        <p id="summary-total-manpower"
                            class="mt-1 text-xl font-bold text-slate-900 dark:text-slate-100">—</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 dark:bg-slate-700/50 p-4">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Average Standard
                            Time</span>
                        <p id="summary-avg-std-time"
                            class="mt-1 text-xl font-bold text-slate-900 dark:text-slate-100">—</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 dark:bg-slate-700/50 p-4">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Working
                            Time</span>
                        <p id="summary-working-time"
                            class="mt-1 text-xl font-bold text-slate-900 dark:text-slate-100">28,800 s</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 dark:bg-slate-700/50 p-4">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Target per
                            PCS</span>
                        <p id="summary-target-pcs"
                            class="mt-1 text-xl font-bold text-slate-900 dark:text-slate-100">—</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 dark:bg-slate-700/50 p-4">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Target Line /
                            Day</span>
                        <p id="summary-target-day"
                            class="mt-1 text-xl font-bold text-slate-900 dark:text-slate-100">—</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 dark:bg-slate-700/50 p-4">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Target Line /
                            Hour</span>
                        <p id="summary-target-hr"
                            class="mt-1 text-xl font-bold text-slate-900 dark:text-slate-100">—</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 dark:bg-slate-700/50 p-4">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Max Based on
                            CT</span>
                        <p id="summary-max-ct" class="mt-1 text-xl font-bold text-slate-900 dark:text-slate-100">—</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 dark:bg-slate-700/50 p-4">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Output Actual</span>
                        <p id="summary-output-actual"
                            class="mt-1 text-xl font-bold text-slate-900 dark:text-slate-100">
                            {{ $report->output_actual ?? '—' }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 dark:bg-slate-700/50 p-4">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Sub Total
                            Operator</span>
                        <p id="summary-sub-total-operator"
                            class="mt-1 text-xl font-bold text-emerald-600 dark:text-emerald-400">—</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 dark:bg-slate-700/50 p-4">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Productivity
                            (PPH)</span>
                        <p id="summary-productivity"
                            class="mt-1 text-xl font-bold text-indigo-600 dark:text-indigo-400">—</p>
                    </div>
                </div>
            </div>

            {{-- Chart --}}
            <div
                class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">Cycle Time Chart</h2>
                <div class="relative" style="height: 400px;">
                    <canvas id="lb-chart"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Chart.js CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script
        src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@3.0.1/dist/chartjs-plugin-annotation.min.js"></script>

    <script>
        const targetOutputPerHour = {{ $report->target_output_per_hour }};
        const outputActual = {{ $report->output_actual ?? 0 }};
        const allowancePercent = {{ $report->allowance_percent }};
        const allowanceMultiplier = {{ $report->allowance_multiplier }};
        const workingHoursPerDay = {{ $report->working_hours_per_day }};
        const WORKING_SECONDS = {{ $report->working_seconds_per_day }};
        let lbChart = null;

        // ─── Stopwatch State (5 laps for CT1–CT5) ───────────────────
        let swRunning = false;
        let swStartTime = 0;
        let swElapsed = 0;
        let swInterval = null;
        let swLaps = []; // Each lap timestamp (absolute ms since start)

        function stopwatchStart() {
            if (swRunning) return;
            // If we already have 5 laps, reset first
            if (swLaps.length >= 5) {
                stopwatchReset();
            }
            swRunning = true;
            swStartTime = Date.now() - swElapsed;
            swInterval = setInterval(updateStopwatch, 10);
            document.getElementById('btn-start').disabled = true;
            document.getElementById('btn-stop').disabled = false;
            document.getElementById('btn-lap').disabled = false;
        }

        function stopwatchStop() {
            if (!swRunning) return;
            swRunning = false;
            clearInterval(swInterval);
            swElapsed = Date.now() - swStartTime;
            document.getElementById('btn-start').disabled = false;
            document.getElementById('btn-stop').disabled = true;
            document.getElementById('btn-lap').disabled = true;

            // Apply laps to target row CT1–CT5
            const targetIdx = document.getElementById('stopwatch-target-row').value;
            if (targetIdx !== '' && swLaps.length > 0) {
                const tbody = document.getElementById('rows-body');
                const rows = tbody.querySelectorAll('tr');
                if (rows[targetIdx]) {
                    const ctInputs = rows[targetIdx].querySelectorAll('.row-ct');
                    // Calculate each lap duration
                    for (let i = 0; i < swLaps.length && i < 5; i++) {
                        const lapDuration = i === 0 ? swLaps[0] : swLaps[i] - swLaps[i - 1];
                        const seconds = (lapDuration / 1000).toFixed(2);
                        if (ctInputs[i]) {
                            ctInputs[i].value = seconds;
                        }
                    }
                    recalculate();
                }
            }
        }

        function stopwatchLap() {
            if (!swRunning) return;
            if (swLaps.length >= 5) return; // Max 5 laps

            const now = Date.now() - swStartTime;
            swLaps.push(now);
            const lapNum = swLaps.length;
            const lapStart = swLaps.length > 1 ? swLaps[swLaps.length - 2] : 0;
            const lapTime = formatTime(now - lapStart);

            // Update display
            const lapDisplay = document.getElementById('lap-display');
            const parts = [];
            for (let i = 0; i < swLaps.length; i++) {
                const start = i > 0 ? swLaps[i - 1] : 0;
                parts.push(`CT${i + 1}: ${(swLaps[i] - start) / 1000}s`);
            }
            lapDisplay.textContent = parts.join(' | ');
            document.getElementById('lap-counter').textContent = `${swLaps.length} / 5 laps`;

            // Auto-stop after 5 laps
            if (swLaps.length >= 5) {
                stopwatchStop();
            }
        }

        function stopwatchReset() {
            swRunning = false;
            clearInterval(swInterval);
            swElapsed = 0;
            swLaps = [];
            document.getElementById('stopwatch-display').textContent = '00:00.00';
            document.getElementById('lap-display').textContent = '';
            document.getElementById('lap-counter').textContent = '0 / 5 laps';
            document.getElementById('btn-start').disabled = false;
            document.getElementById('btn-stop').disabled = true;
            document.getElementById('btn-lap').disabled = true;
        }

        function updateStopwatch() {
            swElapsed = Date.now() - swStartTime;
            document.getElementById('stopwatch-display').textContent = formatTime(swElapsed);
        }

        function formatTime(ms) {
            const minutes = Math.floor(ms / 60000);
            const seconds = Math.floor((ms % 60000) / 1000);
            const hundredths = Math.floor((ms % 1000) / 10);
            return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}.${String(hundredths).padStart(2, '0')}`;
        }

        // ─── Row Management ──────────────────────────────────────────
        function addRow() {
            const tbody = document.getElementById('rows-body');
            const dataRows = tbody.querySelectorAll('tr:not(#total-row)');
            const index = dataRows.length;

            const machineOptions = `<option value="">—</option>` +
                @json($machineTypes).map(mt =>
                    `<option value="${mt.id}">${mt.machine_type}</option>`
                ).join('');

            const ctCells = [0, 1, 2, 3, 4].map(i => `
                <td class="px-3 py-2 bg-blue-50/50 dark:bg-blue-900/10">
                    <input type="number" name="rows[${index}][ct_${i + 1}]" value="" min="0" step="0.01"
                        class="row-ct w-full rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-2 py-1.5 text-xs text-center focus:border-indigo-500 focus:outline-none" />
                </td>
            `).join('');

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="px-3 py-2 text-center">
                    <span class="row-number text-sm font-medium text-slate-500">${index + 1}</span>
                    <input type="hidden" name="rows[${index}][row_number]" value="${index + 1}" />
                </td>
                <td class="px-3 py-2">
                    <select name="rows[${index}][machine_type_id]"
                        class="row-machine w-full rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-2 py-1.5 text-xs focus:border-indigo-500 focus:outline-none">
                        ${machineOptions}
                    </select>
                </td>
                <td class="px-3 py-2">
                    <input type="text" name="rows[${index}][process]"
                        class="row-process w-full rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-2 py-1.5 text-xs focus:border-indigo-500 focus:outline-none" />
                </td>
                <td class="px-3 py-2">
                    <div class="relative employee-ac-wrapper">
                        <input type="text"
                            class="employee-search-input row-employee w-full rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-2 py-1.5 text-xs focus:border-indigo-500 focus:outline-none"
                            value="" placeholder="Search employee..." autocomplete="off" />
                        <input type="hidden" name="rows[${index}][employee_id]" value="" class="employee-id-input" />
                        <input type="hidden" name="rows[${index}][name]" value="" class="employee-name-input" />
                        <div class="employee-ac-dropdown absolute left-0 top-full mt-1 z-50 w-full max-h-48 overflow-y-auto rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 shadow-lg hidden">
                        </div>
                    </div>
                </td>
                <td class="px-3 py-2">
                    <input type="number" name="rows[${index}][operator]" value="1" min="1"
                        class="row-operator w-full rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-2 py-1.5 text-xs text-center focus:border-indigo-500 focus:outline-none" />
                </td>
                ${ctCells}
                <td class="px-3 py-2 text-xs text-center text-slate-600 dark:text-slate-300 calc-avg-ct">—</td>
                <td class="px-3 py-2 text-xs text-center text-slate-600 dark:text-slate-300 calc-avg-ct-allowance">—</td>
                <td class="px-3 py-2 text-xs text-center text-slate-600 dark:text-slate-300 calc-avg-ct-process">—</td>
                <td class="px-3 py-2 text-xs text-center text-slate-600 dark:text-slate-300 calc-output-hr">—</td>
                <td class="px-3 py-2 text-xs text-center text-slate-600 dark:text-slate-300 calc-output-process-hr">—</td>
                <td class="px-3 py-2 text-xs text-center text-slate-600 dark:text-slate-300 calc-req-operator">—</td>
                <td class="px-3 py-2 text-xs text-center text-slate-600 dark:text-slate-300 calc-potential-output">—</td>
                <td class="px-3 py-2 text-center">
                    <button type="button" onclick="removeRow(this)"
                        class="text-red-400 hover:text-red-600 dark:hover:text-red-300">✕</button>
                </td>
            `;
            // Insert before total row
            const totalRow = document.getElementById('total-row');
            tbody.insertBefore(tr, totalRow);
            updateStopwatchTargetOptions();
            recalculate();
        }

        function removeRow(btn) {
            const tr = btn.closest('tr');
            tr.remove();
            renumberRows();
            updateStopwatchTargetOptions();
            recalculate();
        }

        function renumberRows() {
            const tbody = document.getElementById('rows-body');
            const rows = tbody.querySelectorAll('tr:not(#total-row)');
            rows.forEach((tr, i) => {
                tr.querySelector('.row-number').textContent = i + 1;
                // Update all input/select names
                tr.querySelectorAll('input[name], select[name]').forEach(input => {
                    input.name = input.name.replace(/rows\[\d+\]/, `rows[${i}]`);
                });
                // Update the hidden row_number value so it matches the new position
                const rowNumInput = tr.querySelector('input[name$="[row_number]"]');
                if (rowNumInput) rowNumInput.value = i + 1;
            });
        }

        function updateStopwatchTargetOptions() {
            const select = document.getElementById('stopwatch-target-row');
            const tbody = document.getElementById('rows-body');
            const rows = tbody.querySelectorAll('tr:not(#total-row)');
            const currentVal = select.value;
            select.innerHTML = '<option value="">Select target row...</option>';
            rows.forEach((tr, i) => {
                const process = tr.querySelector('.row-process')?.value || `Row ${i + 1}`;
                const opt = document.createElement('option');
                opt.value = i;
                opt.textContent = `Row ${i + 1} — ${process}`;
                select.appendChild(opt);
            });
            if (currentVal !== '' && select.querySelector(`option[value="${currentVal}"]`)) {
                select.value = currentVal;
            }
        }

        // ─── Calculation Engine ───────────────────────────────────────
        // Formulas per Line Balancing workbook specification:
        //   Average CT = AVERAGE(CT1..CT5) — only non-null/non-zero
        //   Average CT + Allowance = Average CT × (1 + allowancePercent/100)
        //   Average CT / Process = Average CT (per process, same as avg CT)
        //   Output / Hour = 3600 / (Average CT + Allowance)
        //   Output Process / Hour = Output / Hour
        //   Request Operator = Average CT / Takt Time
        //   Potential Output / Process = Output Process / Hour × Working Hours/Day
        //
        //   Summary:
        //   Total Cycle Time = SUM(Average CT + Allowance)
        //   Total Manpower = SUM(Operator)
        //   Average Standard Time = Total Cycle Time / Total Manpower
        //   Target Per PCS = ROUND(Working Seconds / Total Cycle Time, 0)
        //   Target Line / Day = Total Manpower × Target Per PCS
        //   Target Line / Hour = Target Line / Day / Working Hours/Day
        //   Maximum Based on CT = 3600 / MAX(Average CT + Allowance)
        //   Sub Total Operator = SUM(Operator)
        //   Productivity/PPH = Target Output/Hour / Sub Total Operator

        // ── Employee Autocomplete (delegated) ───────────────────────
        const EMP_SEARCH_URL = '{{ url("master-data/operators/search") }}';
        let empAcTimer = null;

        // Debounced search on input
        document.addEventListener('input', function (e) {
            if (!e.target.classList.contains('employee-search-input')) return;
            clearTimeout(empAcTimer);
            const input = e.target;
            const wrapper = input.closest('.employee-ac-wrapper');
            const dropdown = wrapper.querySelector('.employee-ac-dropdown');
            const q = input.value.trim();

            // Clear hidden fields when user types
            wrapper.querySelector('.employee-id-input').value = '';
            wrapper.querySelector('.employee-name-input').value = '';

            if (q.length < 1) {
                dropdown.classList.add('hidden');
                dropdown.innerHTML = '';
                return;
            }
            empAcTimer = setTimeout(() => {
                fetch(EMP_SEARCH_URL + '?q=' + encodeURIComponent(q))
                    .then(r => r.json()).then(results => {
                        if (!results.length) {
                            dropdown.classList.add('hidden');
                            dropdown.innerHTML = '';
                            return;
                        }
                        dropdown.innerHTML = results.map(r =>
                            `<div class="emp-ac-item cursor-pointer px-3 py-2 text-xs hover:bg-indigo-50 dark:hover:bg-slate-700"
                                  data-id="${r.id}" data-label="${r.label.replace(/"/g, '&quot;')}">
                                <div class="font-medium text-slate-800 dark:text-slate-200">${r.label}</div>
                                ${r.description ? `<div class="text-[10px] text-slate-500 dark:text-slate-400 truncate">${r.description}</div>` : ''}
                            </div>`
                        ).join('');
                        dropdown.classList.remove('hidden');
                    });
            }, 250);
        });

        // Click on dropdown item → select employee
        document.addEventListener('click', function (e) {
            const item = e.target.closest('.emp-ac-item');
            if (item) {
                const wrapper = item.closest('.employee-ac-wrapper');
                const input = wrapper.querySelector('.employee-search-input');
                const idInput = wrapper.querySelector('.employee-id-input');
                const nameInput = wrapper.querySelector('.employee-name-input');
                const dropdown = wrapper.querySelector('.employee-ac-dropdown');

                input.value = item.dataset.label;
                idInput.value = item.dataset.id;
                nameInput.value = item.dataset.label;
                dropdown.classList.add('hidden');
                dropdown.innerHTML = '';
                recalculate();
                return;
            }

            // Click outside any employee autocomplete → close all dropdowns
            document.querySelectorAll('.employee-ac-dropdown:not(.hidden)').forEach(dd => {
                if (!dd.closest('.employee-ac-wrapper').contains(e.target)) {
                    dd.classList.add('hidden');
                }
            });
        });

        function recalculate() {
            const tbody = document.getElementById('rows-body');
            const rows = tbody.querySelectorAll('tr:not(#total-row)');
            const taktTime = targetOutputPerHour > 0 ? 3600 / targetOutputPerHour : 0;

            let totalCycleTimeAllowance = 0; // sum of (avgCT * allowanceMultiplier) for summary
            let totalManpower = 0;
            let maxCtAllowance = 0;
            let totalOutputProcHr = 0;
            let totalReqOperator = 0;
            let totalPotentialOutput = 0;
            const chartData = [];

            rows.forEach((tr, i) => {
                // Read CT1–CT5
                const ctInputs = tr.querySelectorAll('.row-ct');
                const observations = [];
                ctInputs.forEach(input => {
                    const val = parseFloat(input.value) || 0;
                    if (val > 0) observations.push(val);
                });

                const operator = parseInt(tr.querySelector('.row-operator')?.value) || 1;

                // Average CT = AVERAGE of non-zero observations
                const avgCt = observations.length > 0
                    ? observations.reduce((a, b) => a + b, 0) / observations.length
                    : 0;

                // Average CT + Allowance
                const avgCtAllowance = avgCt * allowanceMultiplier;

                // Average CT / Process = Average CT (per workbook)
                const avgCtProcess = avgCt;

                // Output / Hour = 3600 / (Average CT + Allowance)
                const outputHr = avgCtAllowance > 0 ? 3600 / avgCtAllowance : 0;

                // Output Process / Hour = Output / Hour
                const outputProcessHr = outputHr;

                // Request Operator = Average CT / Takt Time
                const reqOperator = taktTime > 0 ? avgCtProcess / taktTime : 0;

                // Potential Output / Process = Output Process / Hour * Working Hours/Day
                const potentialOutput = outputProcessHr * workingHoursPerDay;

                // Update cells
                tr.querySelector('.calc-avg-ct').textContent = avgCt > 0 ? avgCt.toFixed(2) : '—';
                tr.querySelector('.calc-avg-ct-allowance').textContent = avgCtAllowance > 0 ? avgCtAllowance.toFixed(
                    2) : '—';
                tr.querySelector('.calc-avg-ct-process').textContent = avgCtProcess > 0 ? avgCtProcess.toFixed(2) :
                    '—';
                tr.querySelector('.calc-output-hr').textContent = outputHr > 0 ? outputHr.toFixed(1) : '—';
                tr.querySelector('.calc-output-process-hr').textContent = outputProcessHr > 0 ? outputProcessHr
                    .toFixed(1) : '—';
                tr.querySelector('.calc-req-operator').textContent = reqOperator > 0 ? reqOperator.toFixed(2) : '—';
                tr.querySelector('.calc-potential-output').textContent = potentialOutput > 0 ? potentialOutput.toFixed(
                    0) : '—';

                // Accumulate for summary
                totalCycleTimeAllowance += avgCtAllowance;
                totalManpower += operator;
                totalOutputProcHr += outputProcessHr;
                totalReqOperator += reqOperator;
                totalPotentialOutput += potentialOutput;
                if (avgCtAllowance > maxCtAllowance) maxCtAllowance = avgCtAllowance;

                chartData.push({
                    label: tr.querySelector('.row-process')?.value || `Row ${i + 1}`,
                    avgCt,
                    avgCtAllowance,
                });
            });

            // Update total row
            document.getElementById('total-operator').textContent = totalManpower || '—';
            document.getElementById('total-avg-ct-allowance').textContent = totalCycleTimeAllowance > 0 ? totalCycleTimeAllowance.toFixed(2) : '—';
            document.getElementById('total-output-proc-hr').textContent = totalOutputProcHr > 0 ? totalOutputProcHr.toFixed(1) : '—';
            document.getElementById('total-req-operator').textContent = totalReqOperator > 0 ? totalReqOperator.toFixed(2) : '—';
            document.getElementById('total-potential-output').textContent = totalPotentialOutput > 0 ? totalPotentialOutput.toFixed(0) : '—';

            // ─── Summary Calculations ───────────────────────────────
            const avgStdTime = totalManpower > 0 ? totalCycleTimeAllowance / totalManpower : 0;
            const targetPerPCS = totalCycleTimeAllowance > 0 ? Math.round(WORKING_SECONDS / totalCycleTimeAllowance) : 0;
            const targetLineDay = totalManpower * targetPerPCS;
            const targetLineHour = targetLineDay > 0 ? targetLineDay / workingHoursPerDay : 0;
            const maxBasedCT = maxCtAllowance > 0 ? 3600 / maxCtAllowance : 0;
            const subTotalOp = totalManpower;
            const productivity = subTotalOp > 0 ? targetOutputPerHour / subTotalOp : 0;

            document.getElementById('summary-total-ct').textContent = totalCycleTimeAllowance > 0 ?
                totalCycleTimeAllowance.toFixed(2) + ' s' : '—';
            document.getElementById('summary-total-manpower').textContent = totalManpower || '—';
            document.getElementById('summary-avg-std-time').textContent = avgStdTime > 0 ? avgStdTime.toFixed(2) +
                ' s' : '—';
            document.getElementById('summary-working-time').textContent = WORKING_SECONDS.toLocaleString() + ' s';
            document.getElementById('summary-target-pcs').textContent = targetPerPCS > 0 ? targetPerPCS.toLocaleString() :
                '—';
            document.getElementById('summary-target-day').textContent = targetLineDay > 0 ? targetLineDay.toLocaleString() :
                '—';
            document.getElementById('summary-target-hr').textContent = targetLineHour > 0 ? targetLineHour.toFixed(
                1) : '—';
            document.getElementById('summary-max-ct').textContent = maxBasedCT > 0 ? maxBasedCT.toFixed(1) : '—';
            document.getElementById('summary-output-actual').textContent = outputActual > 0 ? outputActual
                .toLocaleString() : '—';
            document.getElementById('summary-sub-total-operator').textContent = subTotalOp || '—';
            document.getElementById('summary-productivity').textContent = productivity > 0 ? productivity.toFixed(
                2) : '—';

            // Update target display
            document.getElementById('target-per-day').textContent = targetOutputPerHour > 0 ? (
                targetOutputPerHour * workingHoursPerDay).toLocaleString() : '—';
            document.getElementById('takt-time').textContent = taktTime > 0 ? taktTime.toFixed(2) : '—';

            // Update chart
            updateChart(chartData);
        }

        // ─── Chart ────────────────────────────────────────────────────
        function updateChart(data) {
            const ctx = document.getElementById('lb-chart').getContext('2d');

            if (lbChart) {
                lbChart.destroy();
            }

            if (data.length === 0) return;

            const taktTime = targetOutputPerHour > 0 ? 3600 / targetOutputPerHour : 0;

            lbChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.map(d => d.label),
                    datasets: [{
                            label: 'Average CT (s)',
                            data: data.map(d => d.avgCt),
                            backgroundColor: data.map(d =>
                                taktTime > 0 && d.avgCt > taktTime ?
                                'rgba(239, 68, 68, 0.7)' : 'rgba(16, 185, 129, 0.7)'
                            ),
                            borderColor: data.map(d =>
                                taktTime > 0 && d.avgCt > taktTime ?
                                'rgb(239, 68, 68)' : 'rgb(16, 185, 129)'
                            ),
                            borderWidth: 1,
                        },
                        {
                            label: `Avg CT +${allowancePercent}% (s)`,
                            data: data.map(d => d.avgCtAllowance),
                            backgroundColor: 'rgba(59, 130, 246, 0.5)',
                            borderColor: 'rgb(59, 130, 246)',
                            borderWidth: 1,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        annotation: taktTime > 0 ? {
                            annotations: {
                                taktLine: {
                                    type: 'line',
                                    yMin: taktTime,
                                    yMax: taktTime,
                                    borderColor: 'rgb(239, 68, 68)',
                                    borderWidth: 2,
                                    borderDash: [6, 6],
                                    label: {
                                        display: true,
                                        content: `Takt: ${taktTime.toFixed(1)}s`,
                                        position: 'end',
                                    },
                                },
                            },
                        } : {},
                        legend: {
                            position: 'top',
                        },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Seconds',
                            },
                        },
                    },
                },
            });
        }

        // ─── Initialize ──────────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', function() {
            updateStopwatchTargetOptions();
            recalculate();

            // Recalculate on any input change in the table
            document.getElementById('rows-body').addEventListener('input', function(e) {
                if (e.target.classList.contains('row-ct') || e.target.classList.contains('row-operator')) {
                    recalculate();
                }
                if (e.target.classList.contains('row-process')) {
                    updateStopwatchTargetOptions();
                }
            });
        });
    </script>
</x-app-layout>