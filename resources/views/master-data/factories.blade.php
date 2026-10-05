<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-lg text-slate-800 dark:text-slate-200 leading-tight">
            Data Masters / Factories
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

            {{-- Toolbar --}}
            <div class="flex flex-wrap items-center justify-between gap-3">
                <form method="GET" action="{{ route('master-data.factories') }}" id="factory-filter-form"
                    class="flex flex-1 flex-wrap items-center gap-2">
                    <div class="relative" id="factory-autocomplete-wrapper">
                        <input type="search" name="search" id="factory-search-input" value="{{ $search }}"
                            placeholder="Search factories..." autocomplete="off"
                            class="w-56 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-1.5 text-sm focus:border-indigo-500 focus:outline-none" />
                        <div id="factory-search-dropdown"
                            class="absolute left-0 top-full mt-1 z-50 w-full max-h-60 overflow-y-auto rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 shadow-lg hidden">
                        </div>
                    </div>
                    <select id="factory-filter-column" onchange="updateFactoryFilterValues()"
                        class="rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-1.5 text-sm text-slate-600 dark:text-slate-300 focus:border-indigo-500 focus:outline-none">
                        <option value="">Filter by...</option>
                        <option value="factory_name" @selected($filterColumn === 'factory_name')>Factory Name</option>
                    </select>
                    <select id="factory-filter-value" name="filter_value"
                        class="rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-1.5 text-sm text-slate-600 dark:text-slate-300 focus:border-indigo-500 focus:outline-none">
                        <option value="">All values</option>
                    </select>
                    <input type="hidden" name="filter_column" id="factory-filter-column-hidden"
                        value="{{ $filterColumn }}" />
                    <input type="hidden" name="sort" value="{{ $sort ?? 'factory_name' }}" />
                    <input type="hidden" name="direction" value="{{ $direction ?? 'asc' }}" />
                    <label
                        class="inline-flex items-center gap-1.5 text-sm text-slate-600 dark:text-slate-300 cursor-pointer">
                        <input type="checkbox" name="show_inactive" value="1" @checked($showInactive ?? false)
                            onchange="this.form.submit()"
                            class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500" />
                        Show Inactive
                    </label>
                    <a href="{{ route('master-data.factories') }}"
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
                    <a href="{{ route('master-data.factories.export') }}"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Export
                    </a>
                    @if (auth()->user()->role->role_name !== 'viewer')
                        <button type="button" onclick="document.getElementById('create-modal').classList.remove('hidden')"
                            class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500">
                            + New
                        </button>
                        <button type="button" id="factory-delete-btn" onclick="enterFactoryDeleteMode()"
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

            {{-- Bulk Delete Bar --}}
            <div id="factory-delete-bar"
                class="hidden flex items-center gap-3 rounded-xl border border-red-200 dark:border-red-700 bg-red-50 dark:bg-red-900/20 px-4 py-3">
                <span class="text-sm text-red-700 dark:text-red-300">Selected: <strong
                        id="factory-selected-count">0</strong></span>
                <button type="button" onclick="confirmFactoryBulkDelete()"
                    class="rounded-lg bg-red-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-red-500">Confirm
                    Delete</button>
                <button type="button" onclick="exitFactoryDeleteMode()"
                    class="rounded-lg border border-slate-300 dark:border-slate-600 px-4 py-1.5 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">Cancel</button>
            </div>

            <form id="factory-bulk-delete-form" action="{{ url('/master-data/factories/bulk-deactivate') }}"
                method="POST">
                @csrf @method('PATCH')

                {{-- Table --}}
                <div
                    class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm">
                    <table
                        class="min-w-full divide-y divide-slate-200 dark:divide-slate-700 text-left text-sm text-slate-700 dark:text-slate-300">
                        <thead class="bg-slate-50 dark:bg-slate-700/50">
                            @php
                                $currentSort = $sort ?? 'factory_name';
                                $currentDir = $direction ?? 'asc';
                            @endphp
                            <tr>
                                <th class="factory-delete-col hidden px-4 py-3 font-semibold w-10">
                                    <input type="checkbox" id="factory-select-all"
                                        onchange="toggleAllFactoryCheckboxes(this)"
                                        class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500" />
                                </th>
                                <th class="px-4 py-3 font-semibold">No</th>
                                <th class="px-4 py-3 font-semibold">
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'factory_name', 'direction' => $currentSort === 'factory_name' && $currentDir === 'asc' ? 'desc' : 'asc']) }}"
                                        class="hover:text-indigo-600 dark:hover:text-indigo-400">Factory Name
                                        @if($currentSort === 'factory_name')<span
                                        class="text-xs">{!! $currentDir === 'asc' ? '&#9650;' : '&#9660;' !!}</span>@endif</a>
                                </th>
                                <th class="px-4 py-3 font-semibold">
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'description', 'direction' => $currentSort === 'description' && $currentDir === 'asc' ? 'desc' : 'asc']) }}"
                                        class="hover:text-indigo-600 dark:hover:text-indigo-400">Descriptions
                                        @if($currentSort === 'description')<span
                                        class="text-xs">{!! $currentDir === 'asc' ? '&#9650;' : '&#9660;' !!}</span>@endif</a>
                                </th>
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
                            @forelse ($factories as $index => $factory)
                                <tr>
                                    <td class="factory-delete-col hidden px-4 py-3 w-10">
                                        <input type="checkbox" name="ids[]" value="{{ $factory->id }}"
                                            onchange="updateFactorySelectedCount()"
                                            class="factory-row-checkbox rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500" />
                                    </td>
                                    <td class="px-4 py-3">{{ $index + 1 }}</td>
                                    <td class="px-4 py-3 font-medium text-slate-900 dark:text-slate-100">
                                        {{ $factory->factory_name }}
                                    </td>
                                    <td class="px-4 py-3">{{ $factory->description ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        @if ($factory->status === 'active')
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
                                                onclick="openEdit({{ $factory->id }}, @js($factory->factory_name), @js($factory->description))">Edit</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">No
                                        factories found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>

    {{-- Create Modal --}}
    <div id="create-modal" class="fixed inset-0 z-50 hidden bg-slate-900/40">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="w-full max-w-lg rounded-2xl bg-white dark:bg-slate-800 p-6 shadow-xl">
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">New Factory</h3>
                    <button type="button" onclick="document.getElementById('create-modal').classList.add('hidden')"
                        class="text-slate-500 dark:text-slate-400">✕</button>
                </div>
                <form action="{{ route('master-data.factories.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="factory_name"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Factory
                            Name</label>
                        <input id="factory_name" name="factory_name" type="text" required
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2 focus:border-indigo-500 focus:outline-none"
                            style="text-transform:uppercase" />
                    </div>
                    <div>
                        <label for="description"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Descriptions</label>
                        <textarea id="description" name="description" rows="2"
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2 focus:border-indigo-500 focus:outline-none"
                            style="text-transform:uppercase"></textarea>
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
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Edit Factory</h3>
                    <button type="button" onclick="document.getElementById('edit-modal').classList.add('hidden')"
                        class="text-slate-500 dark:text-slate-400">✕</button>
                </div>
                <form id="edit-form" method="POST" class="space-y-4">
                    @csrf @method('PUT')
                    <div>
                        <label for="edit_factory_name"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Factory
                            Name</label>
                        <input id="edit_factory_name" name="factory_name" type="text" required
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2 focus:border-indigo-500 focus:outline-none"
                            style="text-transform:uppercase" />
                    </div>
                    <div>
                        <label for="edit_description"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Descriptions</label>
                        <textarea id="edit_description" name="description" rows="2"
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2 focus:border-indigo-500 focus:outline-none"
                            style="text-transform:uppercase"></textarea>
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
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Import Factories</h3>
                    <button type="button" onclick="document.getElementById('import-modal').classList.add('hidden')"
                        class="text-slate-500 dark:text-slate-400">✕</button>
                </div>
                <form action="{{ route('master-data.factories.import') }}" method="POST" enctype="multipart/form-data"
                    class="space-y-4">
                    @csrf
                    <div
                        class="rounded-lg bg-slate-50 dark:bg-slate-700/50 p-3 text-xs text-slate-600 dark:text-slate-400">
                        <p class="font-semibold mb-1">Expected Excel columns (row 1 = header):</p>
                        <p><code class="bg-slate-200 dark:bg-slate-600 px-1 rounded">factory name</code>, <code
                                class="bg-slate-200 dark:bg-slate-600 px-1 rounded">descriptions</code></p>
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
        const factoryFilterValues = @json($filterValues ?? []);
        function updateFactoryFilterValues() {
            const col = document.getElementById('factory-filter-column').value;
            document.getElementById('factory-filter-column-hidden').value = col;
            const valueSelect = document.getElementById('factory-filter-value');
            valueSelect.innerHTML = '<option value="">All values</option>';
            if (col && factoryFilterValues[col]) {
                factoryFilterValues[col].forEach(v => {
                    const opt = document.createElement('option');
                    opt.value = v;
                    opt.textContent = v;
                    valueSelect.appendChild(opt);
                });
            }
        }
        function openEdit(id, name, description) {
            document.getElementById('edit-form').action = `{{ url('/master-data/factories') }}/${id}`;
            document.getElementById('edit_factory_name').value = name || '';
            document.getElementById('edit_description').value = description || '';
            document.getElementById('edit-modal').classList.remove('hidden');
        }
        document.addEventListener('DOMContentLoaded', () => {
            updateFactoryFilterValues();
            const urlParams = new URLSearchParams(window.location.search);
            const filterValue = urlParams.get('filter_value');
            if (filterValue) {
                document.getElementById('factory-filter-value').value = filterValue;
            }
        });
        // Search on Enter keypress, instant filter/checkbox submit
        const factoryForm = document.getElementById('factory-filter-form');
        const factorySearchInput = factoryForm.querySelector('input[name="search"]');
        factorySearchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                factoryForm.submit();
            }
        });
        const factoryFilterValueSelect = document.getElementById('factory-filter-value');
        factoryFilterValueSelect.addEventListener('change', function () {
            factoryForm.submit();
        });

        // Autocomplete search
        const factoryAcDropdown = document.getElementById('factory-search-dropdown');
        let factoryAcTimer;
        factorySearchInput.addEventListener('input', function () {
            clearTimeout(factoryAcTimer);
            const q = this.value.trim();
            if (q.length < 1) { factoryAcDropdown.classList.add('hidden'); factoryAcDropdown.innerHTML = ''; return; }
            factoryAcTimer = setTimeout(() => {
                fetch('{{ url("master-data/factories/search") }}?q=' + encodeURIComponent(q))
                    .then(r => r.json()).then(results => {
                        if (!results.length) { factoryAcDropdown.classList.add('hidden'); factoryAcDropdown.innerHTML = ''; return; }
                        factoryAcDropdown.innerHTML = results.map(r =>
                            `<div class="ac-item cursor-pointer px-3 py-2 text-sm hover:bg-indigo-50 dark:hover:bg-slate-700" data-label="${r.label.replace(/"/g, '&quot;')}">
                                <div class="font-medium text-slate-800 dark:text-slate-200">${r.label}</div>
                                ${r.description ? `<div class="text-xs text-slate-500 dark:text-slate-400 truncate">${r.description}</div>` : ''}
                            </div>`
                        ).join('');
                        factoryAcDropdown.classList.remove('hidden');
                    });
            }, 300);
        });
        factoryAcDropdown.addEventListener('click', function (e) {
            const item = e.target.closest('.ac-item');
            if (!item) return;
            factorySearchInput.value = item.dataset.label;
            factoryAcDropdown.classList.add('hidden');
            factoryForm.submit();
        });
        document.addEventListener('click', function (e) {
            if (!document.getElementById('factory-autocomplete-wrapper').contains(e.target)) {
                factoryAcDropdown.classList.add('hidden');
            }
        });

        // --- Bulk Delete Functions ---
        function enterFactoryDeleteMode() {
            document.querySelectorAll('.factory-delete-col').forEach(c => c.classList.remove('hidden'));
            document.getElementById('factory-delete-btn').classList.add('hidden');
            document.getElementById('factory-delete-bar').classList.remove('hidden');
            document.getElementById('factory-select-all').checked = false;
            updateFactorySelectedCount();
        }
        function exitFactoryDeleteMode() {
            document.querySelectorAll('.factory-delete-col').forEach(c => c.classList.add('hidden'));
            document.getElementById('factory-delete-btn').classList.remove('hidden');
            document.getElementById('factory-delete-bar').classList.add('hidden');
            document.querySelectorAll('.factory-row-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('factory-select-all').checked = false;
        }
        function toggleAllFactoryCheckboxes(master) {
            document.querySelectorAll('.factory-row-checkbox').forEach(cb => cb.checked = master.checked);
            updateFactorySelectedCount();
        }
        function updateFactorySelectedCount() {
            const count = document.querySelectorAll('.factory-row-checkbox:checked').length;
            document.getElementById('factory-selected-count').textContent = count;
        }
        function confirmFactoryBulkDelete() {
            const checked = document.querySelectorAll('.factory-row-checkbox:checked');
            if (!checked.length) { alert('No records selected.'); return; }
            if (!confirm('Mark ' + checked.length + ' factory(ies) as inactive?')) return;
            const form = document.getElementById('factory-bulk-delete-form');
            form.querySelectorAll('input[name="ids[]"]').forEach(el => el.remove());
            checked.forEach(cb => { const input = document.createElement('input'); input.type = 'hidden'; input.name = 'ids[]'; input.value = cb.value; form.appendChild(input); });
            form.submit();
        }
    </script>
</x-app-layout>