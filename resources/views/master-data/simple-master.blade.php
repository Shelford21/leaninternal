@php
    // Determine master identity from the current route
    $currentRoute = request()->route()->getName(); // e.g. 'master-data.skill-gradings'
    $slug = str_replace('master-data.', '', $currentRoute);
    $routePrefix = 'master-data.' . $slug;

    // Map slug to display name
    $displayNames = [
        'skill-gradings' => 'Skill Gradings',
        'divisions' => 'Divisions',
        'sections' => 'Sections',
        'machine-types' => 'Machine Types',
        'components-panels' => 'Components/Panels',
        'machine-numbers' => 'Machine Numbers',
        'shifts' => 'Shifts',
        'failure-modes' => 'Failure Modes / Kerusakan',
        'mechanics' => 'Mechanics',
        'spare-parts' => 'Spare Parts',
        'factories' => 'Factories',
        'departments' => 'Departments',
        'genders' => 'Genders',
        'production-roles' => 'Production Roles',
        'educational-levels' => 'Educational Level',
        'status-pkwtt' => 'Status PKWTT',
    ];
    $displayName = $displayNames[$slug] ?? ucwords(str_replace('-', ' ', $slug));

    // Map slug to the primary name field
    $nameFields = [
        'skill-gradings' => 'skill_grade',
        'divisions' => 'division',
        'sections' => 'section',
        'machine-types' => 'machine_type',
        'components-panels' => 'component_panel',
        'machine-numbers' => 'machine_number',
        'shifts' => 'shift',
        'failure-modes' => 'failure_mode',
        'mechanics' => 'mechanic',
        'spare-parts' => 'spare_part',
        'genders' => 'gender',
        'production-roles' => 'production_role',
        'educational-levels' => 'level',
        'status-pkwtt' => 'pkwtt',
    ];
    $nameField = $nameFields[$slug] ?? 'name';
    $displayFieldName = ucwords(str_replace('_', ' ', $nameField));
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-lg text-slate-800 dark:text-slate-200 leading-tight">
            Data Masters / {{ $displayName }}
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

            {{-- Toolbar --}}
            <div class="flex flex-wrap items-center justify-between gap-3">
                <form method="GET" action="{{ route($routePrefix) }}" id="simple-filter-form"
                    class="flex flex-1 flex-wrap items-center gap-2">
                    <div class="relative" id="simple-autocomplete-wrapper">
                        <input type="search" name="search" id="simple-search-input" value="{{ $search }}"
                            placeholder="Search {{ strtolower($displayName) }}..." autocomplete="off"
                            class="w-56 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-1.5 text-sm focus:border-indigo-500 focus:outline-none uppercase"
                            style="text-transform:uppercase" />
                        <div id="simple-search-dropdown"
                            class="absolute left-0 top-full mt-1 z-50 w-full max-h-60 overflow-y-auto rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 shadow-lg hidden">
                        </div>
                    </div>
                    <select id="simple-filter-column" onchange="updateSimpleFilterValues()"
                        class="rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-1.5 text-sm text-slate-600 dark:text-slate-300 focus:border-indigo-500 focus:outline-none">
                        <option value="">Filter by...</option>
                        <option value="{{ $nameField }}" @selected($filterColumn === $nameField)>{{ $displayFieldName }}
                        </option>
                    </select>
                    <select id="simple-filter-value" name="filter_value"
                        class="rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-1.5 text-sm text-slate-600 dark:text-slate-300 focus:border-indigo-500 focus:outline-none">
                        <option value="">All values</option>
                    </select>
                    <input type="hidden" name="filter_column" id="simple-filter-column-hidden"
                        value="{{ $filterColumn }}" />
                    <input type="hidden" name="sort" value="{{ $sort ?? $nameField }}" />
                    <input type="hidden" name="direction" value="{{ $direction ?? 'asc' }}" />
                    <label
                        class="inline-flex items-center gap-1.5 text-sm text-slate-600 dark:text-slate-300 cursor-pointer">
                        <input type="checkbox" name="show_inactive" value="1" @checked($showInactive ?? false)
                            onchange="this.form.submit()"
                            class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500" />
                        Show Inactive
                    </label>
                    <a href="{{ route($routePrefix) }}"
                        class="rounded-lg border border-slate-200 dark:border-slate-600 px-3 py-1.5 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">Clear</a>
                </form>
                <div class="flex items-center gap-2">
                    @if (auth()->user()->role->role_name !== 'viewer')
                        {{-- Import --}}
                        <button type="button" onclick="document.getElementById('import-modal').classList.remove('hidden')"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            Import
                        </button>
                        {{-- Export --}}
                    @endif
                    <a href="{{ route($routePrefix . '.export') }}"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Export
                    </a>
                    @if (auth()->user()->role->role_name !== 'viewer')
                        {{-- New --}}
                        <button type="button" onclick="document.getElementById('create-modal').classList.remove('hidden')"
                            class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500">
                            + New
                        </button>
                        {{-- Delete --}}
                        <button type="button" id="simple-delete-btn" onclick="enterDeleteMode()"
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

            {{-- Bulk delete action bar --}}
            <div id="simple-delete-bar"
                class="hidden flex items-center justify-between rounded-xl border border-red-200 dark:border-red-700 bg-red-50 dark:bg-red-900/20 px-4 py-3">
                <span class="text-sm text-red-700 dark:text-red-300">
                    <span id="simple-selected-count">0</span> record(s) selected
                </span>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="confirmBulkDelete()"
                        class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-500">
                        Confirm Delete
                    </button>
                    <button type="button" onclick="exitDeleteMode()"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-300 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">
                        Cancel Delete
                    </button>
                </div>
            </div>



            {{-- Table --}}
            <div
                class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm">
                <form id="simple-bulk-delete-form" action="{{ url('/master-data/' . $slug . '/bulk-deactivate') }}"
                    method="POST">
                    @csrf
                    @method('PATCH')
                    <table
                        class="min-w-full divide-y divide-slate-200 dark:divide-slate-700 text-left text-sm text-slate-700 dark:text-slate-300">
                        <thead class="bg-slate-50 dark:bg-slate-700/50">
                            <tr>
                                <th class="simple-delete-col hidden px-4 py-3 font-semibold w-10">
                                    <input type="checkbox" id="simple-select-all" onchange="toggleAllCheckboxes(this)"
                                        class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500" />
                                </th>
                                <th class="px-4 py-3 font-semibold">No</th>
                                <th class="px-4 py-3 font-semibold">
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => $nameField, 'direction' => (($sort ?? $nameField) === $nameField && ($direction ?? 'asc') === 'asc') ? 'desc' : 'asc']) }}"
                                        class="hover:text-indigo-600 dark:hover:text-indigo-400">
                                        {{ $displayFieldName }}
                                        @if (($sort ?? $nameField) === $nameField)
                                            <span
                                                class="text-xs">{!! ($direction ?? 'asc') === 'asc' ? '&#9650;' : '&#9660;' !!}</span>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-4 py-3 font-semibold">
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'description', 'direction' => (($sort ?? $nameField) === 'description' && ($direction ?? 'asc') === 'asc') ? 'desc' : 'asc']) }}"
                                        class="hover:text-indigo-600 dark:hover:text-indigo-400">
                                        Descriptions
                                        @if (($sort ?? $nameField) === 'description')
                                            <span
                                                class="text-xs">{!! ($direction ?? 'asc') === 'asc' ? '&#9650;' : '&#9660;' !!}</span>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-4 py-3 font-semibold">
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'status', 'direction' => (($sort ?? $nameField) === 'status' && ($direction ?? 'asc') === 'asc') ? 'desc' : 'asc']) }}"
                                        class="hover:text-indigo-600 dark:hover:text-indigo-400">
                                        Status
                                        @if (($sort ?? $nameField) === 'status')
                                            <span
                                                class="text-xs">{!! ($direction ?? 'asc') === 'asc' ? '&#9650;' : '&#9660;' !!}</span>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-4 py-3 font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            @forelse ($items as $index => $item)
                                <tr>
                                    <td class="simple-delete-col hidden px-4 py-3 w-10">
                                        <input type="checkbox" name="ids[]" value="{{ $item->id }}"
                                            onchange="updateSelectedCount()"
                                            class="simple-row-checkbox rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500" />
                                    </td>
                                    <td class="px-4 py-3">{{ $index + 1 }}</td>
                                    <td class="px-4 py-3 font-medium text-slate-900 dark:text-slate-100">
                                        {{ $item->{$nameField} }}
                                    </td>
                                    <td class="px-4 py-3">{{ $item->description ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        @if ($item->status === 'active')
                                            <span
                                                class="inline-flex items-center rounded-full bg-emerald-50 dark:bg-emerald-900/30 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:text-emerald-300 ring-1 ring-inset ring-emerald-600/20">Active</span>
                                        @else
                                            <span
                                                class="inline-flex items-center rounded-full bg-red-50 dark:bg-red-900/30 px-2 py-0.5 text-xs font-medium text-red-700 dark:text-red-300 ring-1 ring-inset ring-red-600/20">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <button type="button"
                                                class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                                                onclick="openEdit({{ $item->id }}, @js($item->{$nameField}), @js($item->description))">Edit</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">No
                                        records
                                        found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </form>
            </div>
        </div>
    </div>

    {{-- Create Modal --}}
    <div id="create-modal" class="fixed inset-0 z-50 hidden bg-slate-900/40">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="w-full max-w-lg rounded-2xl bg-white dark:bg-slate-800 p-6 shadow-xl">
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">New {{ $displayName }}</h3>
                    <button type="button" onclick="document.getElementById('create-modal').classList.add('hidden')"
                        class="text-slate-500 dark:text-slate-400">✕</button>
                </div>
                <form action="{{ route($routePrefix . '.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="create_name"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">{{ $displayFieldName }}</label>
                        <input id="create_name" name="{{ $nameField }}" type="text" required
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2 focus:border-indigo-500 focus:outline-none uppercase"
                            style="text-transform:uppercase" />
                    </div>
                    <div>
                        <label for="create_description"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Descriptions</label>
                        <textarea id="create_description" name="description" rows="2"
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2 focus:border-indigo-500 focus:outline-none"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" onclick="document.getElementById('create-modal').classList.add('hidden')"
                            class="rounded-xl border border-slate-200 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 dark:hover:bg-slate-700">Cancel</button>
                        <button type="submit"
                            class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div id="edit-modal" class="fixed inset-0 z-50 hidden bg-slate-900/40">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="w-full max-w-lg rounded-2xl bg-white dark:bg-slate-800 p-6 shadow-xl">
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Edit {{ $displayName }}</h3>
                    <button type="button" onclick="document.getElementById('edit-modal').classList.add('hidden')"
                        class="text-slate-500 dark:text-slate-400">✕</button>
                </div>
                <form id="edit-form" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="edit_name"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">{{ $displayFieldName }}</label>
                        <input id="edit_name" name="{{ $nameField }}" type="text" required
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2 focus:border-indigo-500 focus:outline-none uppercase"
                            style="text-transform:uppercase" />
                    </div>
                    <div>
                        <label for="edit_description"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Descriptions</label>
                        <textarea id="edit_description" name="description" rows="2"
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2 focus:border-indigo-500 focus:outline-none"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" onclick="document.getElementById('edit-modal').classList.add('hidden')"
                            class="rounded-xl border border-slate-200 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 dark:hover:bg-slate-700">Cancel</button>
                        <button type="submit"
                            class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">Save</button>
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
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Import {{ $displayName }}</h3>
                    <button type="button" onclick="document.getElementById('import-modal').classList.add('hidden')"
                        class="text-slate-500 dark:text-slate-400">✕</button>
                </div>
                <form action="{{ route($routePrefix . '.import') }}" method="POST" enctype="multipart/form-data"
                    class="space-y-4">
                    @csrf
                    <div
                        class="rounded-lg bg-slate-50 dark:bg-slate-700/50 p-3 text-xs text-slate-600 dark:text-slate-400">
                        <p class="font-semibold mb-1">Expected Excel columns (row 1 = header):</p>
                        <p><code
                                class="bg-slate-200 dark:bg-slate-600 px-1 rounded">{{ strtolower($displayFieldName) }}</code>,
                            <code class="bg-slate-200 dark:bg-slate-600 px-1 rounded">descriptions</code>
                        </p>
                    </div>
                    <div>
                        <label for="import_file"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Excel File (.xlsx,
                            .xls, .csv)</label>
                        <input id="import_file" name="file" type="file" accept=".xlsx,.xls,.csv" required
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2" />
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
        const simpleFilterValues = @json($filterValues ?? []);

        // Soft delete functions
        function enterDeleteMode() {
            document.querySelectorAll('.simple-delete-col').forEach(el => el.classList.remove('hidden'));
            document.getElementById('simple-delete-bar').classList.remove('hidden');
            document.getElementById('simple-delete-btn').classList.add('hidden');
            document.getElementById('simple-select-all').checked = false;
            updateSelectedCount();
        }
        function exitDeleteMode() {
            document.querySelectorAll('.simple-delete-col').forEach(el => el.classList.add('hidden'));
            document.getElementById('simple-delete-bar').classList.add('hidden');
            document.getElementById('simple-delete-btn').classList.remove('hidden');
            document.querySelectorAll('.simple-row-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('simple-select-all').checked = false;
        }
        function toggleAllCheckboxes(master) {
            document.querySelectorAll('.simple-row-checkbox').forEach(cb => cb.checked = master.checked);
            updateSelectedCount();
        }
        function updateSelectedCount() {
            const count = document.querySelectorAll('.simple-row-checkbox:checked').length;
            document.getElementById('simple-selected-count').textContent = count;
        }
        function confirmBulkDelete() {
            const checked = document.querySelectorAll('.simple-row-checkbox:checked');
            if (checked.length === 0) { alert('Please select at least one record to delete.'); return; }
            if (!confirm('Are you sure you want to mark ' + checked.length + ' record(s) as inactive?')) return;
            const form = document.getElementById('simple-bulk-delete-form');
            form.querySelectorAll('input[name="ids[]"]').forEach(el => el.remove());
            checked.forEach(cb => { const input = document.createElement('input'); input.type = 'hidden'; input.name = 'ids[]'; input.value = cb.value; form.appendChild(input); });
            form.submit();
        }

        function updateSimpleFilterValues() {
            const col = document.getElementById('simple-filter-column').value;
            document.getElementById('simple-filter-column-hidden').value = col;
            const valueSelect = document.getElementById('simple-filter-value');
            valueSelect.innerHTML = '<option value="">All values</option>';
            if (col && simpleFilterValues[col]) {
                simpleFilterValues[col].forEach(v => {
                    const opt = document.createElement('option');
                    opt.value = v;
                    opt.textContent = v;
                    valueSelect.appendChild(opt);
                });
            }
        }
        function openEdit(id, name, description) {
            document.getElementById('edit-form').action = `{{ url('/master-data/' . $slug) }}/${id}`;
            document.getElementById('edit_name').value = name || '';
            document.getElementById('edit_description').value = description || '';
            document.getElementById('edit-modal').classList.remove('hidden');
        }
        document.addEventListener('DOMContentLoaded', () => {
            updateSimpleFilterValues();
            const urlParams = new URLSearchParams(window.location.search);
            const filterValue = urlParams.get('filter_value');
            if (filterValue) {
                document.getElementById('simple-filter-value').value = filterValue;
            }

            // Search on Enter keypress, instant filter/checkbox submit
            const form = document.getElementById('simple-filter-form');
            const searchInput = form.querySelector('input[name="search"]');
            const filterValueSelect = document.getElementById('simple-filter-value');
            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    form.submit();
                }
            });
            filterValueSelect.addEventListener('change', function () {
                form.submit();
            });
            document.getElementById('simple-filter-column').addEventListener('change', function () {
                updateSimpleFilterValues();
            });

            // Autocomplete search
            const acDropdown = document.getElementById('simple-search-dropdown');
            let acTimer;
            searchInput.addEventListener('input', function () {
                clearTimeout(acTimer);
                const q = this.value.trim();
                if (q.length < 1) { acDropdown.classList.add('hidden'); acDropdown.innerHTML = ''; return; }
                acTimer = setTimeout(() => {
                    fetch('{{ url("master-data/" . $slug . "/search") }}?q=' + encodeURIComponent(q))
                        .then(r => r.json()).then(results => {
                            if (!results.length) { acDropdown.classList.add('hidden'); acDropdown.innerHTML = ''; return; }
                            acDropdown.innerHTML = results.map(r =>
                                `<div class="ac-item cursor-pointer px-3 py-2 text-sm hover:bg-indigo-50 dark:hover:bg-slate-700" data-label="${r.label.replace(/"/g, '&quot;')}">
                                    <div class="font-medium text-slate-800 dark:text-slate-200">${r.label}</div>
                                    ${r.description ? `<div class="text-xs text-slate-500 dark:text-slate-400 truncate">${r.description}</div>` : ''}
                                </div>`
                            ).join('');
                            acDropdown.classList.remove('hidden');
                        });
                }, 300);
            });
            acDropdown.addEventListener('click', function (e) {
                const item = e.target.closest('.ac-item');
                if (!item) return;
                searchInput.value = item.dataset.label;
                acDropdown.classList.add('hidden');
                form.submit();
            });
            document.addEventListener('click', function (e) {
                if (!document.getElementById('simple-autocomplete-wrapper').contains(e.target)) {
                    acDropdown.classList.add('hidden');
                }
            });
        });
    </script>
</x-app-layout>