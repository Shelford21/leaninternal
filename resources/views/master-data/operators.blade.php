<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-lg text-slate-800 dark:text-slate-200 leading-tight">
            {{ __('master-data.data_masters') }} / Employees
        </h2>
    </x-slot>

    <div class="py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto space-y-6">
            @if (session('success'))
                <div
                    class="rounded-xl border border-emerald-200 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-900/30 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-300">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div
                    class="rounded-xl border border-red-200 dark:border-red-700 bg-red-50 dark:bg-red-900/30 px-4 py-3 text-sm text-red-700 dark:text-red-300">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Row Count --}}
            <div class="text-sm text-slate-600 dark:text-slate-400">
                Total Records: <span class="font-semibold text-slate-900 dark:text-slate-100">{{ $totalCount }}</span>
            </div>

            {{-- Filters --}}
            <div class="flex flex-wrap items-end justify-between gap-3">
                <form method="GET" action="{{ route('master-data.operators') }}" id="operator-filter-form"
                    class="flex flex-1 flex-wrap items-end gap-2">
                    <div class="relative" id="operator-autocomplete-wrapper">
                        <input type="search" name="search" id="operator-search-input" value="{{ $search }}"
                            placeholder="Search employees..." autocomplete="off" style="text-transform:uppercase"
                            class="w-56 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-1.5 text-sm focus:border-indigo-500 focus:outline-none" />
                        <div id="operator-search-dropdown"
                            class="absolute left-0 top-full mt-1 z-50 w-full max-h-60 overflow-y-auto rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 shadow-lg hidden">
                        </div>
                    </div>

                    {{-- Simple select filters --}}
                    <select id="operator-filter-column" onchange="updateOperatorFilterValues()"
                        class="rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-1.5 text-sm text-slate-600 dark:text-slate-300 focus:border-indigo-500 focus:outline-none">
                        <option value="">Filter by...</option>
                        <option value="operator_name" @selected($filterColumn === 'operator_name')>Nama</option>
                        <option value="gender" @selected($filterColumn === 'gender')>Gender</option>
                        <option value="role" @selected($filterColumn === 'role')>Role</option>
                        <option value="status_pkwtt" @selected($filterColumn === 'status_pkwtt')>Status PKWTT</option>
                        <option value="educational_level" @selected($filterColumn === 'educational_level')>Educational
                            Level</option>
                        <option value="factory" @selected($filterColumn === 'factory')>Factory</option>
                        <option value="department" @selected($filterColumn === 'department')>Department</option>
                        <option value="division" @selected($filterColumn === 'division')>Division</option>
                        <option value="section" @selected($filterColumn === 'section')>Section</option>
                        <option value="line" @selected($filterColumn === 'line')>Line</option>
                        <option value="start_date" @selected($filterColumn === 'start_date')>Start Date</option>
                        <option value="date_of_birth" @selected($filterColumn === 'date_of_birth')>Date of Birth</option>
                    </select>

                    {{-- Dynamic value dropdown for simple filters --}}
                    <select id="operator-filter-value" name="filter_value"
                        class="rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-1.5 text-sm text-slate-600 dark:text-slate-300 focus:border-indigo-500 focus:outline-none">
                        <option value="">All values</option>
                    </select>

                    {{-- Date range inputs (hidden by default) --}}
                    <div id="operator-date-range-filters" class="hidden flex items-center gap-2">
                        <input type="date" name="date_from" id="operator-date-from" value="{{ $dateFrom ?? '' }}"
                            class="rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-1.5 text-sm focus:border-indigo-500 focus:outline-none" />
                        <span class="text-sm text-slate-500">to</span>
                        <input type="date" name="date_to" id="operator-date-to" value="{{ $dateTo ?? '' }}"
                            class="rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-1.5 text-sm focus:border-indigo-500 focus:outline-none" />
                    </div>

                    <input type="hidden" name="filter_column" id="operator-filter-column-hidden"
                        value="{{ $filterColumn }}" />
                    <input type="hidden" name="sort" value="{{ $sort ?? 'operator_name' }}" />
                    <input type="hidden" name="direction" value="{{ $direction ?? 'asc' }}" />

                    <label
                        class="inline-flex items-center gap-1.5 text-sm text-slate-600 dark:text-slate-300 cursor-pointer">
                        <input type="checkbox" name="show_inactive" value="1" @checked($showInactive ?? false)
                            onchange="this.form.submit()"
                            class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500" />
                        Show Inactive
                    </label>
                    <a href="{{ route('master-data.operators') }}"
                        class="rounded-lg border border-slate-200 dark:border-slate-600 px-3 py-1.5 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">Clear</a>
                </form>

                {{-- Action buttons --}}
                <div class="flex items-center gap-2">
                    @if (auth()->user()->role->role_name !== 'viewer')
                        <button type="button" onclick="document.getElementById('import-modal').classList.remove('hidden')"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            Import
                        </button>
                    @endif
                    <a href="{{ route('master-data.operators.export') }}"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Export
                    </a>
                    @if (auth()->user()->role->role_name !== 'viewer')
                        <button type="button" onclick="document.getElementById('operator-modal').classList.remove('hidden')"
                            class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white">+ New</button>
                        <button type="button" id="operator-delete-btn" onclick="enterOperatorDeleteMode()"
                            class="inline-flex items-center gap-2 rounded-xl border border-red-300 dark:border-red-600 px-4 py-2 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Delete
                        </button>
                    @endif
                </div>
            </div>

            {{-- Delete action bar --}}
            <div id="operator-delete-bar"
                class="hidden flex items-center justify-between rounded-xl border border-red-200 dark:border-red-700 bg-red-50 dark:bg-red-900/20 px-4 py-3">
                <span class="text-sm text-red-700 dark:text-red-300">
                    <span id="operator-selected-count">0</span> record(s) selected
                </span>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="confirmOperatorBulkDelete()"
                        class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-500">Confirm
                        Delete</button>
                    <button type="button" onclick="exitOperatorDeleteMode()"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-300 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">Cancel</button>
                </div>
            </div>

            {{-- Soft delete form --}}
            <form id="operator-bulk-delete-form" action="{{ route('master-data.operators.bulk-deactivate') }}"
                method="POST">@csrf @method('PATCH')</form>

            {{-- Table --}}
            <div
                class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm">
                <table
                    class="min-w-[1200px] w-full divide-y divide-slate-200 dark:divide-slate-700 text-left text-sm text-slate-700 dark:text-slate-300">
                    <thead class="bg-slate-50 dark:bg-slate-700/50">
                        @php
                            $currentSort = $sort ?? 'operator_name';
                            $currentDir = $direction ?? 'asc';
                        @endphp
                        <tr>
                            <th class="operator-delete-col hidden px-4 py-3 font-semibold w-10">
                                <input type="checkbox" id="operator-select-all"
                                    onchange="toggleAllOperatorCheckboxes(this)"
                                    class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500" />
                            </th>
                            <th class="px-4 py-3 font-semibold">No</th>
                            <th class="px-4 py-3 font-semibold">Photo</th>
                            <th class="px-4 py-3 font-semibold">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'operator_name', 'direction' => $currentSort === 'operator_name' && $currentDir === 'asc' ? 'desc' : 'asc']) }}"
                                    class="hover:text-indigo-600 dark:hover:text-indigo-400">Nama
                                    @if($currentSort === 'operator_name')<span
                                    class="text-xs">{!! $currentDir === 'asc' ? '&#9650;' : '&#9660;' !!}</span>@endif</a>
                            </th>
                            <th class="px-4 py-3 font-semibold">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'nik_karyawan', 'direction' => $currentSort === 'nik_karyawan' && $currentDir === 'asc' ? 'desc' : 'asc']) }}"
                                    class="hover:text-indigo-600 dark:hover:text-indigo-400">NIK Karyawan
                                    @if($currentSort === 'nik_karyawan')<span
                                    class="text-xs">{!! $currentDir === 'asc' ? '&#9650;' : '&#9660;' !!}</span>@endif</a>
                            </th>
                            <th class="px-4 py-3 font-semibold">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'gender', 'direction' => $currentSort === 'gender' && $currentDir === 'asc' ? 'desc' : 'asc']) }}"
                                    class="hover:text-indigo-600 dark:hover:text-indigo-400">Gender
                                    @if($currentSort === 'gender')<span
                                    class="text-xs">{!! $currentDir === 'asc' ? '&#9650;' : '&#9660;' !!}</span>@endif</a>
                            </th>
                            <th class="px-4 py-3 font-semibold">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'role', 'direction' => $currentSort === 'role' && $currentDir === 'asc' ? 'desc' : 'asc']) }}"
                                    class="hover:text-indigo-600 dark:hover:text-indigo-400">Role
                                    @if($currentSort === 'role')<span
                                    class="text-xs">{!! $currentDir === 'asc' ? '&#9650;' : '&#9660;' !!}</span>@endif</a>
                            </th>
                            <th class="px-4 py-3 font-semibold">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'factory', 'direction' => $currentSort === 'factory' && $currentDir === 'asc' ? 'desc' : 'asc']) }}"
                                    class="hover:text-indigo-600 dark:hover:text-indigo-400">Factory
                                    @if($currentSort === 'factory')<span
                                    class="text-xs">{!! $currentDir === 'asc' ? '&#9650;' : '&#9660;' !!}</span>@endif</a>
                            </th>
                            <th class="px-4 py-3 font-semibold">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'department', 'direction' => $currentSort === 'department' && $currentDir === 'asc' ? 'desc' : 'asc']) }}"
                                    class="hover:text-indigo-600 dark:hover:text-indigo-400">Department
                                    @if($currentSort === 'department')<span
                                    class="text-xs">{!! $currentDir === 'asc' ? '&#9650;' : '&#9660;' !!}</span>@endif</a>
                            </th>
                            <th class="px-4 py-3 font-semibold">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'division', 'direction' => $currentSort === 'division' && $currentDir === 'asc' ? 'desc' : 'asc']) }}"
                                    class="hover:text-indigo-600 dark:hover:text-indigo-400">Division
                                    @if($currentSort === 'division')<span
                                    class="text-xs">{!! $currentDir === 'asc' ? '&#9650;' : '&#9660;' !!}</span>@endif</a>
                            </th>
                            <th class="px-4 py-3 font-semibold">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'section', 'direction' => $currentSort === 'section' && $currentDir === 'asc' ? 'desc' : 'asc']) }}"
                                    class="hover:text-indigo-600 dark:hover:text-indigo-400">Section
                                    @if($currentSort === 'section')<span
                                    class="text-xs">{!! $currentDir === 'asc' ? '&#9650;' : '&#9660;' !!}</span>@endif</a>
                            </th>
                            <th class="px-4 py-3 font-semibold">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'line', 'direction' => $currentSort === 'line' && $currentDir === 'asc' ? 'desc' : 'asc']) }}"
                                    class="hover:text-indigo-600 dark:hover:text-indigo-400">Line
                                    @if($currentSort === 'line')<span
                                    class="text-xs">{!! $currentDir === 'asc' ? '&#9650;' : '&#9660;' !!}</span>@endif</a>
                            </th>
                            <th class="px-4 py-3 font-semibold">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'status_pkwtt', 'direction' => $currentSort === 'status_pkwtt' && $currentDir === 'asc' ? 'desc' : 'asc']) }}"
                                    class="hover:text-indigo-600 dark:hover:text-indigo-400">Status PKWTT
                                    @if($currentSort === 'status_pkwtt')<span
                                    class="text-xs">{!! $currentDir === 'asc' ? '&#9650;' : '&#9660;' !!}</span>@endif</a>
                            </th>
                            <th class="px-4 py-3 font-semibold">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'educational_level', 'direction' => $currentSort === 'educational_level' && $currentDir === 'asc' ? 'desc' : 'asc']) }}"
                                    class="hover:text-indigo-600 dark:hover:text-indigo-400">Educational Level
                                    @if($currentSort === 'educational_level')<span
                                    class="text-xs">{!! $currentDir === 'asc' ? '&#9650;' : '&#9660;' !!}</span>@endif</a>
                            </th>
                            <th class="px-4 py-3 font-semibold">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'start_date', 'direction' => $currentSort === 'start_date' && $currentDir === 'asc' ? 'desc' : 'asc']) }}"
                                    class="hover:text-indigo-600 dark:hover:text-indigo-400">Start Date
                                    @if($currentSort === 'start_date')<span
                                    class="text-xs">{!! $currentDir === 'asc' ? '&#9650;' : '&#9660;' !!}</span>@endif</a>
                            </th>
                            <th class="px-4 py-3 font-semibold">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'date_of_birth', 'direction' => $currentSort === 'date_of_birth' && $currentDir === 'asc' ? 'desc' : 'asc']) }}"
                                    class="hover:text-indigo-600 dark:hover:text-indigo-400">Date of Birth
                                    @if($currentSort === 'date_of_birth')<span
                                    class="text-xs">{!! $currentDir === 'asc' ? '&#9650;' : '&#9660;' !!}</span>@endif</a>
                            </th>
                            <th class="px-4 py-3 font-semibold">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'status', 'direction' => $currentSort === 'status' && $currentDir === 'asc' ? 'desc' : 'asc']) }}"
                                    class="hover:text-indigo-600 dark:hover:text-indigo-400">Status
                                    @if($currentSort === 'status')<span
                                    class="text-xs">{!! $currentDir === 'asc' ? '&#9650;' : '&#9660;' !!}</span>@endif</a>
                            </th>
                            <th class="px-4 py-3 font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        @forelse ($operators as $index => $operator)
                            <tr>
                                <td class="operator-delete-col hidden px-4 py-3 w-10">
                                    <input type="checkbox" name="ids[]" value="{{ $operator->id }}"
                                        onchange="updateOperatorSelectedCount()"
                                        class="operator-row-checkbox rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500" />
                                </td>
                                <td class="px-4 py-3">{{ $index + 1 }}</td>
                                <td class="px-4 py-3">
                                    @if ($operator->photo_path)
                                        <img src="{{ asset('storage/' . ltrim($operator->photo_path, '/')) }}"
                                            alt="{{ $operator->operator_name }}" class="h-10 w-10 rounded-full object-cover" />
                                    @else
                                        <div
                                            class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-200 text-xs font-semibold text-slate-600">
                                            {{ strtoupper(substr($operator->operator_name, 0, 2)) }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-medium text-slate-900 dark:text-slate-100">
                                    {{ $operator->operator_name }}
                                </td>
                                <td class="px-4 py-3">{{ $operator->nik_karyawan ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $operator->gender ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $operator->role ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $operator->factory?->factory_name ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $operator->department?->department_name ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $operator->division?->division ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $operator->section?->section ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $operator->productionLine?->line_name ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $operator->statusPkwtt?->pkwtt ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $operator->educationalLevel?->level ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $operator->start_date?->format('Y-m-d') ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $operator->date_of_birth?->format('Y-m-d') ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    @if ($operator->status === 'active')
                                        <span
                                            class="inline-flex items-center rounded-full bg-emerald-50 dark:bg-emerald-900/30 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:text-emerald-300 ring-1 ring-inset ring-emerald-600/20">Active</span>
                                    @else
                                        <span
                                            class="inline-flex items-center rounded-full bg-red-50 dark:bg-red-900/30 px-2 py-0.5 text-xs font-medium text-red-700 dark:text-red-300 ring-1 ring-inset ring-red-600/20">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('operators.show', $operator) }}"
                                            class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">Details</a>
                                        @if (auth()->user()->role->role_name !== 'viewer')
                                            <button type="button"
                                                onclick="openOperatorEdit({{ $operator->id }}, @js($operator->operator_name), @js($operator->nik_karyawan), @js($operator->gender), @js($operator->role), @js($operator->photo_path), {{ $operator->status_pkwtt_id ?? 'null' }}, {{ $operator->educational_level_id ?? 'null' }}, @js($operator->start_date?->format('Y-m-d')), @js($operator->date_of_birth?->format('Y-m-d')), {{ $operator->factory_id ?? 'null' }}, {{ $operator->department_id ?? 'null' }}, {{ $operator->division_id ?? 'null' }}, {{ $operator->section_id ?? 'null' }}, {{ $operator->line_id ?? 'null' }})"
                                                class="text-indigo-600 hover:text-indigo-800">Edit</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="19" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">No
                                    employees found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @php
        $operatorFields = '<div><label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Nama</label><input name="operator_name" data-field="operator_name" required style="text-transform:uppercase" class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2"></div><div><label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">NIK Karyawan</label><input name="nik_karyawan" data-field="nik_karyawan" maxlength="50" style="text-transform:uppercase" class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2" placeholder="Optional"></div><div><label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Photo</label><input name="photo" type="file" accept=".jpg,.jpeg,.png" class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2"></div><div><label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Start Date</label><input name="start_date" data-field="start_date" type="date" class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2"></div><div><label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Date of Birth</label><input name="date_of_birth" data-field="date_of_birth" type="date" class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2"></div>';
    @endphp

    {{-- New Employee Modal --}}
    <div id="operator-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/40">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="w-full max-w-2xl rounded-2xl bg-white dark:bg-slate-800 p-6 shadow-xl">
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">New Employee</h3>
                    <button type="button" onclick="document.getElementById('operator-modal').classList.add('hidden')"
                        class="text-slate-500 dark:text-slate-400">✕</button>
                </div>
                <form action="{{ route('master-data.operators.store') }}" method="POST" enctype="multipart/form-data"
                    class="space-y-4">@csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {!! $operatorFields !!}
                        @include('master-data.partials.operator-selects')
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button"
                            onclick="document.getElementById('operator-modal').classList.add('hidden')"
                            class="rounded-xl border border-slate-200 dark:border-slate-600 px-4 py-2 text-sm text-slate-600 dark:text-slate-300 dark:hover:bg-slate-700">Cancel</button>
                        <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm text-white">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit Employee Modal --}}
    <div id="operator-edit-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/40">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="w-full max-w-2xl rounded-2xl bg-white dark:bg-slate-800 p-6 shadow-xl">
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Edit Employee</h3>
                    <button type="button"
                        onclick="document.getElementById('operator-edit-modal').classList.add('hidden')"
                        class="text-slate-500 dark:text-slate-400">✕</button>
                </div>
                <form id="operator-edit-form" method="POST" enctype="multipart/form-data" class="space-y-4">@csrf
                    @method('PUT')
                    <div id="operator-edit-preview" class="hidden"><img src="" alt="Current photo"
                            class="h-16 w-16 rounded-full object-cover"></div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {!! $operatorFields !!}
                        @include('master-data.partials.operator-selects')
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button"
                            onclick="document.getElementById('operator-edit-modal').classList.add('hidden')"
                            class="rounded-xl border border-slate-200 dark:border-slate-600 px-4 py-2 text-sm text-slate-600 dark:text-slate-300 dark:hover:bg-slate-700">Cancel</button>
                        <button class="rounded-xl bg-indigo-600 px-4 py-2 text-white">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Import Modal --}}
    <div id="import-modal" class="fixed inset-0 z-50 hidden bg-slate-900/40">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="w-full max-w-lg rounded-2xl bg-white dark:bg-slate-800 p-6 shadow-xl">
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Import Employees</h3>
                    <button type="button" onclick="document.getElementById('import-modal').classList.add('hidden')"
                        class="text-slate-500 dark:text-slate-400">✕</button>
                </div>
                <form action="{{ route('master-data.operators.import') }}" method="POST" enctype="multipart/form-data"
                    class="space-y-4">
                    @csrf
                    <div>
                        <label for="import-file"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Excel File
                            (.xlsx)</label>
                        <input id="import-file" name="file" type="file" accept=".xlsx,.xls,.csv" required
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2" />
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Columns: Employee Name, NIK Karyawan,
                            Gender, Role, Status PKWTT, Educational Level, Factory, Department, Division, Section, Line,
                            Start Date, Date of Birth</p>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" onclick="document.getElementById('import-modal').classList.add('hidden')"
                            class="rounded-xl border border-slate-200 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 dark:hover:bg-slate-700">Cancel</button>
                        <button type="submit"
                            class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // --- Soft Delete Mode ---
        function enterOperatorDeleteMode() {
            document.querySelectorAll('.operator-delete-col').forEach(el => el.classList.remove('hidden'));
            document.getElementById('operator-delete-bar').classList.remove('hidden');
            document.getElementById('operator-delete-btn').classList.add('hidden');
            updateOperatorSelectedCount();
        }
        function exitOperatorDeleteMode() {
            document.querySelectorAll('.operator-delete-col').forEach(el => el.classList.add('hidden'));
            document.getElementById('operator-delete-bar').classList.add('hidden');
            document.getElementById('operator-delete-btn').classList.remove('hidden');
            document.querySelectorAll('.operator-row-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('operator-select-all').checked = false;
            updateOperatorSelectedCount();
        }
        function toggleAllOperatorCheckboxes(master) {
            document.querySelectorAll('.operator-row-checkbox').forEach(cb => cb.checked = master.checked);
            updateOperatorSelectedCount();
        }
        function updateOperatorSelectedCount() {
            const count = document.querySelectorAll('.operator-row-checkbox:checked').length;
            document.getElementById('operator-selected-count').textContent = count;
        }
        function confirmOperatorBulkDelete() {
            const checked = document.querySelectorAll('.operator-row-checkbox:checked');
            if (checked.length === 0) { alert('Please select at least one record.'); return; }
            if (!confirm('Are you sure you want to mark ' + checked.length + ' employee(s) as inactive?')) return;
            const form = document.getElementById('operator-bulk-delete-form');
            form.querySelectorAll('input[name="ids[]"]').forEach(el => el.remove());
            checked.forEach(cb => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = cb.value;
                form.appendChild(input);
            });
            form.submit();
        }

        // --- Edit Modal ---
        function openOperatorEdit(id, name, nikKaryawan, gender, role, photoPath, statusPkwttId, educationalLevelId, startDate, dateOfBirth, factoryId, departmentId, divisionId, sectionId, lineId) {
            const form = document.getElementById('operator-edit-form');
            form.action = `{{ url('/master-data/operators') }}/${id}`;
            const values = { operator_name: name, nik_karyawan: nikKaryawan, gender, role, start_date: startDate, date_of_birth: dateOfBirth };
            Object.entries(values).forEach(([field, value]) => { const el = form.querySelector(`[data-field="${field}"]`); if (el) el.value = value || ''; });
            form.querySelector('[name="status_pkwtt_id"]').value = statusPkwttId || '';
            form.querySelector('[name="educational_level_id"]').value = educationalLevelId || '';
            form.querySelector('[name="factory_id"]').value = factoryId || '';
            form.querySelector('[name="department_id"]').value = departmentId || '';
            form.querySelector('[name="division_id"]').value = divisionId || '';
            form.querySelector('[name="section_id"]').value = sectionId || '';
            form.querySelector('[name="line_id"]').value = lineId || '';
            const preview = document.getElementById('operator-edit-preview');
            if (photoPath) {
                preview.classList.remove('hidden');
                preview.querySelector('img').src = photoPath.startsWith('http') ? photoPath : `{{ asset('storage') }}/${photoPath}`;
            } else {
                preview.classList.add('hidden');
            }
            document.getElementById('operator-edit-modal').classList.remove('hidden');
        }

        // --- Filter Logic ---
        const operatorFilterValues = @json($filterValues);
        const dateFilterColumns = ['start_date', 'date_of_birth'];

        function updateOperatorFilterValues() {
            const column = document.getElementById('operator-filter-column').value;
            const valueSelect = document.getElementById('operator-filter-value');
            const hiddenColumn = document.getElementById('operator-filter-column-hidden');
            const dateRangeDiv = document.getElementById('operator-date-range-filters');

            hiddenColumn.value = column;
            valueSelect.innerHTML = '<option value="">All values</option>';

            dateRangeDiv.classList.add('hidden');
            valueSelect.style.display = '';

            if (dateFilterColumns.includes(column)) {
                valueSelect.style.display = 'none';
                dateRangeDiv.classList.remove('hidden');
            } else if (column && operatorFilterValues[column]) {
                operatorFilterValues[column].forEach(function (val) {
                    const opt = document.createElement('option');
                    opt.value = val;
                    opt.textContent = val;
                    if (String(val) === '{{ $filterValue }}') opt.selected = true;
                    valueSelect.appendChild(opt);
                });
            }
        }
        updateOperatorFilterValues();

        const operatorForm = document.getElementById('operator-filter-form');
        const operatorSearchInput = operatorForm.querySelector('input[name="search"]');
        operatorSearchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); operatorForm.submit(); }
        });
        document.getElementById('operator-filter-value').addEventListener('change', function () { operatorForm.submit(); });

        const operatorAcDropdown = document.getElementById('operator-search-dropdown');
        let operatorAcTimer;
        operatorSearchInput.addEventListener('input', function () {
            clearTimeout(operatorAcTimer);
            const q = this.value.trim();
            if (q.length < 1) { operatorAcDropdown.classList.add('hidden'); operatorAcDropdown.innerHTML = ''; return; }
            operatorAcTimer = setTimeout(() => {
                fetch('{{ url("master-data/operators/search") }}?q=' + encodeURIComponent(q))
                    .then(r => r.json()).then(results => {
                        if (!results.length) { operatorAcDropdown.classList.add('hidden'); operatorAcDropdown.innerHTML = ''; return; }
                        operatorAcDropdown.innerHTML = results.map(r =>
                            `<div class="ac-item cursor-pointer px-3 py-2 text-sm hover:bg-indigo-50 dark:hover:bg-slate-700" data-label="${r.label.replace(/"/g, '&quot;')}">
                                <div class="font-medium text-slate-800 dark:text-slate-200">${r.label}</div>
                                ${r.description ? `<div class="text-xs text-slate-500 dark:text-slate-400 truncate">${r.description}</div>` : ''}
                            </div>`
                        ).join('');
                        operatorAcDropdown.classList.remove('hidden');
                    });
            }, 300);
        });
        operatorAcDropdown.addEventListener('click', function (e) {
            const item = e.target.closest('.ac-item');
            if (!item) return;
            operatorSearchInput.value = item.dataset.label;
            operatorAcDropdown.classList.add('hidden');
            operatorForm.submit();
        });
        document.addEventListener('click', function (e) {
            if (!document.getElementById('operator-autocomplete-wrapper').contains(e.target)) {
                operatorAcDropdown.classList.add('hidden');
            }
        });
    </script>
</x-app-layout>