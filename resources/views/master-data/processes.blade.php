<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-lg text-slate-800 dark:text-slate-200 leading-tight">
            {{ __('master-data.data_masters') }} / {{ __('master-data.processes') }}
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

            <div class="flex flex-wrap items-center justify-between gap-3">
                <form method="GET" action="{{ route('master-data.processes') }}" id="process-filter-form"
                    class="flex flex-1 flex-wrap items-center gap-2">
                    <div class="relative" id="process-autocomplete-wrapper">
                        <input type="search" name="search" id="process-search-input" value="{{ $search }}"
                            placeholder="Search processes..." autocomplete="off"
                            class="w-56 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-1.5 text-sm focus:border-indigo-500 focus:outline-none" />
                        <div id="process-search-dropdown"
                            class="absolute left-0 top-full mt-1 z-50 w-full max-h-60 overflow-y-auto rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 shadow-lg hidden">
                        </div>
                    </div>
                    <select id="process-filter-column" onchange="updateProcessFilterValues()"
                        class="rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-1.5 text-sm text-slate-600 dark:text-slate-300 focus:border-indigo-500 focus:outline-none">
                        <option value="">Filter by...</option>
                        <option value="process_name" @selected($filterColumn === 'process_name')>Process</option>
                        <option value="version_number" @selected($filterColumn === 'version_number')>Version</option>
                        <option value="gsd_code" @selected($filterColumn === 'gsd_code')>GSD Code</option>
                    </select>
                    <select id="process-filter-value" name="filter_value"
                        class="rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-1.5 text-sm text-slate-600 dark:text-slate-300 focus:border-indigo-500 focus:outline-none">
                        <option value="">All values</option>
                    </select>
                    <input type="hidden" name="filter_column" id="process-filter-column-hidden"
                        value="{{ $filterColumn }}" />
                    <input type="hidden" name="sort" value="{{ $sort ?? 'process_name' }}" />
                    <input type="hidden" name="direction" value="{{ $direction ?? 'asc' }}" />
                    <label
                        class="inline-flex items-center gap-1.5 text-sm text-slate-600 dark:text-slate-300 cursor-pointer">
                        <input type="checkbox" name="show_inactive" value="1" @checked($showInactive ?? false)
                            onchange="this.form.submit()"
                            class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500" />
                        Show Inactive
                    </label>
                    <a href="{{ route('master-data.processes') }}"
                        class="rounded-lg border border-slate-200 dark:border-slate-600 px-3 py-1.5 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">Clear</a>
                </form>
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
                    <a href="{{ route('master-data.processes.export') }}"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Export
                    </a>
                    @if (auth()->user()->role->role_name !== 'viewer')
                        <button type="button" onclick="document.getElementById('process-modal').classList.remove('hidden')"
                            class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500">
                            + New
                        </button>
                        <button type="button" id="process-delete-btn" onclick="enterProcessDeleteMode()"
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

            <div id="process-delete-bar"
                class="hidden flex items-center justify-between rounded-xl border border-red-200 dark:border-red-700 bg-red-50 dark:bg-red-900/20 px-4 py-3">
                <span class="text-sm text-red-700 dark:text-red-300"><span id="process-selected-count">0</span>
                    record(s) selected</span>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="confirmProcessBulkDelete()"
                        class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-500">Confirm
                        Delete</button>
                    <button type="button" onclick="exitProcessDeleteMode()"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-300 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">Cancel
                        Delete</button>
                </div>
            </div>

            <form id="process-bulk-delete-form" action="{{ route('master-data.processes.bulk-deactivate') }}"
                method="POST">@csrf @method('PATCH')
                <div
                    class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm">
                    <table
                        class="min-w-full divide-y divide-slate-200 dark:divide-slate-700 text-left text-sm text-slate-700 dark:text-slate-300">
                        <thead class="bg-slate-50 dark:bg-slate-700/50">
                            @php
                                $currentSort = $sort ?? 'process_name';
                                $currentDir = $direction ?? 'asc';
                            @endphp
                            <tr>
                                <th class="process-delete-col hidden px-4 py-3 font-semibold w-10">
                                    <input type="checkbox" id="process-select-all"
                                        onchange="toggleAllProcessCheckboxes(this)"
                                        class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500" />
                                </th>
                                <th class="px-4 py-3 font-semibold">No</th>
                                <th class="px-4 py-3 font-semibold">
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'process_name', 'direction' => $currentSort === 'process_name' && $currentDir === 'asc' ? 'desc' : 'asc']) }}"
                                        class="hover:text-indigo-600 dark:hover:text-indigo-400">Process
                                        @if($currentSort === 'process_name')<span
                                        class="text-xs">{!! $currentDir === 'asc' ? '&#9650;' : '&#9660;' !!}</span>@endif</a>
                                </th>
                                <th class="px-4 py-3 font-semibold">Process Version</th>
                                <th class="px-4 py-3 font-semibold">GSD Elements</th>
                                <th class="px-4 py-3 font-semibold">
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'status', 'direction' => $currentSort === 'status' && $currentDir === 'asc' ? 'desc' : 'asc']) }}"
                                        class="hover:text-indigo-600 dark:hover:text-indigo-400">Status
                                        @if($currentSort === 'status')<span
                                        class="text-xs">{!! $currentDir === 'asc' ? '&#9650;' : '&#9660;' !!}</span>@endif</a>
                                </th>
                                <th class="px-4 py-3 font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            @forelse ($processes as $index => $process)
                            <tr>
                                <td class="process-delete-col hidden px-4 py-3 w-10">
                                    <input type="checkbox" name="ids[]" value="{{ $process->id }}"
                                        onchange="updateProcessSelectedCount()"
                                        class="process-row-checkbox rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500" />
                                </td>
                                <td class="px-4 py-3">{{ $index + 1 }}</td>
                                <td class="px-4 py-3 font-medium text-slate-900 dark:text-slate-100">
                                    {{ $process->process_name }}
                                </td>
                                <td class="px-4 py-3">
                                    @if ($process->versions->isNotEmpty())
                                        @foreach ($process->versions as $version)
                                            <span
                                                class="mr-2 inline-flex rounded-full bg-slate-100 dark:bg-slate-700 px-2 py-1 text-xs font-medium text-slate-700 dark:text-slate-300"
                                                title="{{ $version->gsdElement?->element_name ?? 'No GSD element' }}">V{{ $version->version_number }}</span>
                                        @endforeach
                                    @else
                                        <span class="text-slate-500 dark:text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @php($elements = $process->versions->flatMap(fn($version) => $version->gsdElements)->unique('id'))
                                    {{ $elements->pluck('code')->join(' - ') ?: '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    @if ($process->status === 'active')
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
                                            onclick="openProcessEdit({{ $process->id }}, @js($process->process_name), @js($process->versions->sortByDesc('version_number')->first()?->version_number ?? 1), @js($process->versions->sortByDesc('version_number')->first()?->gsdElements->pluck('id')->values() ?? []))">Edit</button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">No
                                    process
                                    data found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>

    <div id="process-modal" class="fixed inset-0 z-50 hidden bg-slate-900/40">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="w-full max-w-md rounded-2xl bg-white dark:bg-slate-800 p-6 shadow-xl">
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">New Process</h3>
                    <button type="button" onclick="document.getElementById('process-modal').classList.add('hidden')"
                        class="text-slate-500 dark:text-slate-400">✕</button>
                </div>

                <form action="{{ route('master-data.processes.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="process_name"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Process</label>
                        <input id="process_name" name="process_name" type="text" required
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2 focus:border-indigo-500 focus:outline-none"
                            style="text-transform:uppercase" />
                    </div>
                    <div>
                        <label for="version_number"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Process
                            Version</label>
                        <input id="version_number" name="version_number" type="number" min="1" value="1"
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2 focus:border-indigo-500 focus:outline-none" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">GSD
                            Elements</label>
                        <div id="new-gsd-elements" class="space-y-2"></div>
                        <button type="button" onclick="addGsdSelect('new-gsd-elements')"
                            class="mt-2 text-sm font-medium text-indigo-600 hover:text-indigo-800">+ Add GSD
                            Element</button>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" onclick="document.getElementById('process-modal').classList.add('hidden')"
                            class="rounded-xl border border-slate-200 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 dark:hover:bg-slate-700">Cancel</button>
                        <button type="submit"
                            class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="process-edit-modal" class="fixed inset-0 z-50 hidden bg-slate-900/40">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="w-full max-w-md rounded-2xl bg-white dark:bg-slate-800 p-6 shadow-xl">
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Edit Process</h3>
                    <button type="button"
                        onclick="document.getElementById('process-edit-modal').classList.add('hidden')"
                        class="text-slate-500 dark:text-slate-400">✕</button>
                </div>
                <form id="process-edit-form" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="edit_process_name"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Process</label>
                        <input id="edit_process_name" name="process_name" type="text" required
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2 focus:border-indigo-500 focus:outline-none"
                            style="text-transform:uppercase" />
                    </div>
                    <div>
                        <label for="edit_version_number"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Process
                            Version</label>
                        <input id="edit_version_number" name="version_number" type="number" min="1" required
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2 focus:border-indigo-500 focus:outline-none" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">GSD
                            Elements</label>
                        <div id="edit-gsd-elements" class="space-y-2"></div>
                        <button type="button" onclick="addGsdSelect('edit-gsd-elements')"
                            class="mt-2 text-sm font-medium text-indigo-600 hover:text-indigo-800">+ Add GSD
                            Element</button>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button"
                            onclick="document.getElementById('process-edit-modal').classList.add('hidden')"
                            class="rounded-xl border border-slate-200 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 dark:hover:bg-slate-700">Cancel</button>
                        <button type="submit"
                            class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const gsdOptions = @json($gsdElements->map(fn($element) => ['id' => $element->id, 'label' => $element->code . ' — ' . $element->element_name])->values());

        function addGsdSelect(containerId, selectedId = '') {
            const container = document.getElementById(containerId);
            const row = document.createElement('div');
            row.className = 'flex gap-2';
            row.innerHTML = `<select name="gsd_element_ids[]" required class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2 focus:border-indigo-500 focus:outline-none"><option value="">Select element</option>${gsdOptions.map(option => `<option value="${option.id}" ${String(option.id) === String(selectedId) ? 'selected' : ''}>${option.label}</option>`).join('')}</select><button type="button" class="rounded-xl border border-red-200 dark:border-red-600 px-3 text-sm text-red-600 dark:text-red-400" onclick="this.parentElement.remove()">Remove</button>`;
            container.appendChild(row);
        }

        function openProcessEdit(id, name, version, elementIds) {
            document.getElementById('process-edit-form').action = `{{ url('/master-data/processes') }}/${id}`;
            document.getElementById('edit_process_name').value = name;
            document.getElementById('edit_version_number').value = version;
            document.getElementById('edit-gsd-elements').innerHTML = '';
            elementIds.forEach(elementId => addGsdSelect('edit-gsd-elements', elementId));
            document.getElementById('process-edit-modal').classList.remove('hidden');
        }

        const processFilterValues = @json($filterValues);
        function updateProcessFilterValues() {
            const column = document.getElementById('process-filter-column').value;
            const valueSelect = document.getElementById('process-filter-value');
            const hiddenColumn = document.getElementById('process-filter-column-hidden');
            hiddenColumn.value = column;
            valueSelect.innerHTML = '<option value="">All values</option>';
            if (column && processFilterValues[column]) {
                processFilterValues[column].forEach(function (val) {
                    const opt = document.createElement('option');
                    opt.value = val;
                    opt.textContent = val;
                    if (String(val) === '{{ $filterValue }}') opt.selected = true;
                    valueSelect.appendChild(opt);
                });
            }
        }
        updateProcessFilterValues();

        // Search on Enter keypress, instant filter/checkbox submit
        const processForm = document.getElementById('process-filter-form');
        const processSearchInput = processForm.querySelector('input[name="search"]');
        processSearchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                processForm.submit();
            }
        });
        const processFilterValueSelect = document.getElementById('process-filter-value');
        processFilterValueSelect.addEventListener('change', function () {
            processForm.submit();
        });

        // Autocomplete search
        const processAcDropdown = document.getElementById('process-search-dropdown');
        let processAcTimer;
        processSearchInput.addEventListener('input', function () {
            clearTimeout(processAcTimer);
            const q = this.value.trim();
            if (q.length < 1) { processAcDropdown.classList.add('hidden'); processAcDropdown.innerHTML = ''; return; }
            processAcTimer = setTimeout(() => {
                fetch('{{ url("master-data/processes/search") }}?q=' + encodeURIComponent(q))
                    .then(r => r.json()).then(results => {
                        if (!results.length) { processAcDropdown.classList.add('hidden'); processAcDropdown.innerHTML = ''; return; }
                        processAcDropdown.innerHTML = results.map(r =>
                            `<div class="ac-item cursor-pointer px-3 py-2 text-sm hover:bg-indigo-50 dark:hover:bg-slate-700" data-label="${r.label.replace(/"/g, '&quot;')}">
                                <div class="font-medium text-slate-800 dark:text-slate-200">${r.label}</div>
                                ${r.description ? `<div class="text-xs text-slate-500 dark:text-slate-400 truncate">${r.description}</div>` : ''}
                            </div>`
                        ).join('');
                        processAcDropdown.classList.remove('hidden');
                    });
            }, 300);
        });
        processAcDropdown.addEventListener('click', function (e) {
            const item = e.target.closest('.ac-item');
            if (!item) return;
            processSearchInput.value = item.dataset.label;
            processAcDropdown.classList.add('hidden');
            processForm.submit();
        });
        document.addEventListener('click', function (e) {
            if (!document.getElementById('process-autocomplete-wrapper').contains(e.target)) {
                processAcDropdown.classList.add('hidden');
            }
        });

        // --- Bulk Delete Functions ---
        function enterProcessDeleteMode() {
            document.querySelectorAll('.process-delete-col').forEach(c => c.classList.remove('hidden'));
            document.getElementById('process-delete-btn').classList.add('hidden');
            document.getElementById('process-delete-bar').classList.remove('hidden');
            document.getElementById('process-select-all').checked = false;
            updateProcessSelectedCount();
        }
        function exitProcessDeleteMode() {
            document.querySelectorAll('.process-delete-col').forEach(c => c.classList.add('hidden'));
            document.getElementById('process-delete-btn').classList.remove('hidden');
            document.getElementById('process-delete-bar').classList.add('hidden');
            document.querySelectorAll('.process-row-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('process-select-all').checked = false;
        }
        function toggleAllProcessCheckboxes(master) {
            document.querySelectorAll('.process-row-checkbox').forEach(cb => cb.checked = master.checked);
            updateProcessSelectedCount();
        }
        function updateProcessSelectedCount() {
            const count = document.querySelectorAll('.process-row-checkbox:checked').length;
            document.getElementById('process-selected-count').textContent = count;
        }
        function confirmProcessBulkDelete() {
            const checked = document.querySelectorAll('.process-row-checkbox:checked');
            if (!checked.length) { alert('No records selected.'); return; }
            if (!confirm('Mark ' + checked.length + ' process(es) as inactive?')) return;
            const form = document.getElementById('process-bulk-delete-form');
            form.querySelectorAll('input[name="ids[]"]').forEach(el => el.remove());
            checked.forEach(cb => { const input = document.createElement('input'); input.type = 'hidden'; input.name = 'ids[]'; input.value = cb.value; form.appendChild(input); });
            form.submit();
        }
    </script>

    {{-- Import Modal --}}
    <div id="import-modal" class="fixed inset-0 z-50 hidden bg-slate-900/40">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="w-full max-w-lg rounded-2xl bg-white dark:bg-slate-800 p-6 shadow-xl">
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Import Processes</h3>
                    <button type="button" onclick="document.getElementById('import-modal').classList.add('hidden')"
                        class="text-slate-500 dark:text-slate-400">✕</button>
                </div>
                <form action="{{ route('master-data.processes.import') }}" method="POST" enctype="multipart/form-data"
                    class="space-y-4">
                    @csrf
                    <div>
                        <label for="import-file"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Excel File
                            (.xlsx)</label>
                        <input id="import-file" name="file" type="file" accept=".xlsx,.xls,.csv" required
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2" />
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Columns: Process Name, Version</p>
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
</x-app-layout>