<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-lg text-slate-800 dark:text-slate-200 leading-tight">
            Data Masters / Mechanics
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
                <form method="GET" action="{{ route('master-data.mechanics') }}" id="mech-filter-form"
                    class="flex flex-1 flex-wrap items-center gap-2">
                    <div class="relative" id="mech-autocomplete-wrapper">
                        <input type="search" name="search" id="mech-search-input" value="{{ $search }}"
                            placeholder="Search mechanics..." autocomplete="off"
                            class="w-56 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-1.5 text-sm focus:border-indigo-500 focus:outline-none" />
                        <div id="mech-search-dropdown"
                            class="absolute left-0 top-full mt-1 z-50 w-full max-h-60 overflow-y-auto rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 shadow-lg hidden">
                        </div>
                    </div>
                    <select id="mech-filter-column" onchange="updateMechFilterValues()"
                        class="rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-1.5 text-sm text-slate-600 dark:text-slate-300 focus:border-indigo-500 focus:outline-none">
                        <option value="">Filter by...</option>
                        <option value="nik_karyawan" @selected($filterColumn === 'nik_karyawan')>NIK KARYAWAN</option>
                        <option value="mechanic" @selected($filterColumn === 'mechanic')>Mechanic</option>
                    </select>
                    <select id="mech-filter-value" name="filter_value"
                        class="rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-1.5 text-sm text-slate-600 dark:text-slate-300 focus:border-indigo-500 focus:outline-none">
                        <option value="">All values</option>
                    </select>
                    <input type="hidden" name="filter_column" id="mech-filter-column-hidden"
                        value="{{ $filterColumn }}" />
                    <input type="hidden" name="sort" value="{{ $sort ?? 'mechanic' }}" />
                    <input type="hidden" name="direction" value="{{ $direction ?? 'asc' }}" />
                    <label
                        class="inline-flex items-center gap-1.5 text-sm text-slate-600 dark:text-slate-300 cursor-pointer">
                        <input type="checkbox" name="show_inactive" value="1" @checked($showInactive ?? false)
                            onchange="this.form.submit()"
                            class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500" />
                        Show Inactive
                    </label>
                    <a href="{{ route('master-data.mechanics') }}"
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
                    <a href="{{ route('master-data.mechanics.export') }}"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Export
                    </a>
                    @if (auth()->user()->role->role_name !== 'viewer')
                        <button type="button" onclick="document.getElementById('mech-modal').classList.remove('hidden')"
                            class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white">+ New</button>
                        <button type="button" id="mech-delete-btn" onclick="enterMechDeleteMode()"
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
            <div id="mech-delete-bar"
                class="hidden flex items-center gap-3 rounded-xl border border-red-200 dark:border-red-700 bg-red-50 dark:bg-red-900/20 px-4 py-3">
                <span class="text-sm text-red-700 dark:text-red-300">Selected: <strong
                        id="mech-selected-count">0</strong></span>
                <button type="button" onclick="confirmMechBulkDelete()"
                    class="rounded-lg bg-red-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-red-500">Confirm
                    Delete</button>
                <button type="button" onclick="exitMechDeleteMode()"
                    class="rounded-lg border border-slate-300 dark:border-slate-600 px-4 py-1.5 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">Cancel</button>
            </div>

            <form id="mech-bulk-delete-form" action="{{ url('/master-data/mechanics/bulk-deactivate') }}" method="POST">
                @csrf @method('PATCH')

                {{-- Table --}}
                <div
                    class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm">
                    <table
                        class="min-w-full divide-y divide-slate-200 dark:divide-slate-700 text-left text-sm text-slate-700 dark:text-slate-300">
                        <thead class="bg-slate-50 dark:bg-slate-700/50">
                            @php
                                $currentSort = $sort ?? 'mechanic';
                                $currentDir = $direction ?? 'asc';
                            @endphp
                            <tr>
                                <th class="mech-delete-col hidden px-4 py-3 font-semibold w-10">
                                    <input type="checkbox" id="mech-select-all" onchange="toggleAllMechCheckboxes(this)"
                                        class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500" />
                                </th>
                                <th class="px-4 py-3 font-semibold">No</th>
                                <th class="px-4 py-3 font-semibold">
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'nik_karyawan', 'direction' => $currentSort === 'nik_karyawan' && $currentDir === 'asc' ? 'desc' : 'asc']) }}"
                                        class="hover:text-indigo-600 dark:hover:text-indigo-400">NIK KARYAWAN
                                        @if($currentSort === 'nik_karyawan')<span
                                        class="text-xs">{!! $currentDir === 'asc' ? '&#9650;' : '&#9660;' !!}</span>@endif</a>
                                </th>
                                <th class="px-4 py-3 font-semibold">
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'mechanic', 'direction' => $currentSort === 'mechanic' && $currentDir === 'asc' ? 'desc' : 'asc']) }}"
                                        class="hover:text-indigo-600 dark:hover:text-indigo-400">Mechanic
                                        @if($currentSort === 'mechanic')<span
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
                                <th class="px-4 py-3 font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            @forelse ($items as $index => $item)
                                <tr>
                                    <td class="mech-delete-col hidden px-4 py-3 w-10">
                                        <input type="checkbox" name="ids[]" value="{{ $item->id }}"
                                            onchange="updateMechSelectedCount()"
                                            class="mech-row-checkbox rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500" />
                                    </td>
                                    <td class="px-4 py-3">{{ $index + 1 }}</td>
                                    <td class="px-4 py-3">{{ $item->nik_karyawan ?? '—' }}</td>
                                    <td class="px-4 py-3 font-medium text-slate-900 dark:text-slate-100">
                                        {{ $item->mechanic }}
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
                                                onclick="openMechEdit({{ $item->id }}, @js($item->nik_karyawan), @js($item->mechanic), @js($item->description))"
                                                class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">Edit</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">No
                                        mechanics found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>

    {{-- New Modal --}}
    <div id="mech-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/40">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="w-full max-w-lg rounded-2xl bg-white dark:bg-slate-800 p-6 shadow-xl">
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">New Mechanic</h3>
                    <button type="button" onclick="document.getElementById('mech-modal').classList.add('hidden')"
                        class="text-slate-500 dark:text-slate-400">✕</button>
                </div>
                <form action="{{ route('master-data.mechanics.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">NIK
                            KARYAWAN</label>
                        <input name="nik_karyawan" maxlength="50"
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2"
                            placeholder="Optional" style="text-transform:uppercase" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Mechanic <span
                                class="text-red-500">*</span></label>
                        <input name="mechanic" required maxlength="200"
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2"
                            style="text-transform:uppercase" />
                    </div>
                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Descriptions</label>
                        <input name="description" maxlength="255"
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2"
                            placeholder="Optional" style="text-transform:uppercase" />
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="document.getElementById('mech-modal').classList.add('hidden')"
                            class="rounded-xl border border-slate-200 dark:border-slate-600 px-4 py-2 text-sm text-slate-600 dark:text-slate-300 dark:hover:bg-slate-700">Cancel</button>
                        <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm text-white">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div id="mech-edit-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/40">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="w-full max-w-lg rounded-2xl bg-white dark:bg-slate-800 p-6 shadow-xl">
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Edit Mechanic</h3>
                    <button type="button" onclick="document.getElementById('mech-edit-modal').classList.add('hidden')"
                        class="text-slate-500 dark:text-slate-400">✕</button>
                </div>
                <form id="mech-edit-form" method="POST" class="space-y-4">
                    @csrf @method('PUT')
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">NIK
                            KARYAWAN</label>
                        <input name="nik_karyawan" id="edit-nik-karyawan" maxlength="50"
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2"
                            placeholder="Optional" style="text-transform:uppercase" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Mechanic <span
                                class="text-red-500">*</span></label>
                        <input name="mechanic" id="edit-mechanic" required maxlength="200"
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2"
                            style="text-transform:uppercase" />
                    </div>
                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Descriptions</label>
                        <input name="description" id="edit-description" maxlength="255"
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2"
                            placeholder="Optional" style="text-transform:uppercase" />
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button"
                            onclick="document.getElementById('mech-edit-modal').classList.add('hidden')"
                            class="rounded-xl border border-slate-200 dark:border-slate-600 px-4 py-2 text-sm text-slate-600 dark:text-slate-300 dark:hover:bg-slate-700">Cancel</button>
                        <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm text-white">Save</button>
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
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Import Mechanics</h3>
                    <button type="button" onclick="document.getElementById('import-modal').classList.add('hidden')"
                        class="text-slate-500 dark:text-slate-400">✕</button>
                </div>
                <form action="{{ route('master-data.mechanics.import') }}" method="POST" enctype="multipart/form-data"
                    class="space-y-4">
                    @csrf
                    <div>
                        <label for="import-file"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Excel File
                            (.xlsx)</label>
                        <input id="import-file" name="file" type="file" accept=".xlsx,.xls,.csv" required
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2" />
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Columns: NIK KARYAWAN, Mechanic,
                            Descriptions</p>
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
        function openMechEdit(id, nikKaryawan, mechanic, description) {
            const form = document.getElementById('mech-edit-form');
            form.action = `{{ url('/master-data/mechanics') }}/${id}`;
            document.getElementById('edit-nik-karyawan').value = nikKaryawan || '';
            document.getElementById('edit-mechanic').value = mechanic || '';
            document.getElementById('edit-description').value = description || '';
            document.getElementById('mech-edit-modal').classList.remove('hidden');
        }

        const mechFilterValues = @json($filterValues);
        function updateMechFilterValues() {
            const column = document.getElementById('mech-filter-column').value;
            const valueSelect = document.getElementById('mech-filter-value');
            const hiddenColumn = document.getElementById('mech-filter-column-hidden');
            hiddenColumn.value = column;
            valueSelect.innerHTML = '<option value="">All values</option>';
            if (column && mechFilterValues[column]) {
                mechFilterValues[column].forEach(function (val) {
                    const opt = document.createElement('option');
                    opt.value = val;
                    opt.textContent = val;
                    if (String(val) === '{{ $filterValue }}') opt.selected = true;
                    valueSelect.appendChild(opt);
                });
            }
        }
        updateMechFilterValues();

        const mechForm = document.getElementById('mech-filter-form');
        const mechSearchInput = mechForm.querySelector('input[name="search"]');
        mechSearchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); mechForm.submit(); }
        });
        document.getElementById('mech-filter-value').addEventListener('change', function () { mechForm.submit(); });

        const mechAcDropdown = document.getElementById('mech-search-dropdown');
        let mechAcTimer;
        mechSearchInput.addEventListener('input', function () {
            clearTimeout(mechAcTimer);
            const q = this.value.trim();
            if (q.length < 1) { mechAcDropdown.classList.add('hidden'); mechAcDropdown.innerHTML = ''; return; }
            mechAcTimer = setTimeout(() => {
                fetch('{{ url("master-data/mechanics/search") }}?q=' + encodeURIComponent(q))
                    .then(r => r.json()).then(results => {
                        if (!results.length) { mechAcDropdown.classList.add('hidden'); mechAcDropdown.innerHTML = ''; return; }
                        mechAcDropdown.innerHTML = results.map(r =>
                            `<div class="ac-item cursor-pointer px-3 py-2 text-sm hover:bg-indigo-50 dark:hover:bg-slate-700" data-label="${r.label.replace(/"/g, '&quot;')}">
                                <div class="font-medium text-slate-800 dark:text-slate-200">${r.label}</div>
                                ${r.description ? `<div class="text-xs text-slate-500 dark:text-slate-400 truncate">${r.description}</div>` : ''}
                            </div>`
                        ).join('');
                        mechAcDropdown.classList.remove('hidden');
                    });
            }, 300);
        });
        mechAcDropdown.addEventListener('click', function (e) {
            const item = e.target.closest('.ac-item');
            if (!item) return;
            mechSearchInput.value = item.dataset.label;
            mechAcDropdown.classList.add('hidden');
            mechForm.submit();
        });
        document.addEventListener('click', function (e) {
            if (!document.getElementById('mech-autocomplete-wrapper').contains(e.target)) {
                mechAcDropdown.classList.add('hidden');
            }
        });

        // --- Bulk Delete Functions ---
        function enterMechDeleteMode() {
            document.querySelectorAll('.mech-delete-col').forEach(c => c.classList.remove('hidden'));
            document.getElementById('mech-delete-btn').classList.add('hidden');
            document.getElementById('mech-delete-bar').classList.remove('hidden');
            document.getElementById('mech-select-all').checked = false;
            updateMechSelectedCount();
        }
        function exitMechDeleteMode() {
            document.querySelectorAll('.mech-delete-col').forEach(c => c.classList.add('hidden'));
            document.getElementById('mech-delete-btn').classList.remove('hidden');
            document.getElementById('mech-delete-bar').classList.add('hidden');
            document.querySelectorAll('.mech-row-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('mech-select-all').checked = false;
        }
        function toggleAllMechCheckboxes(master) {
            document.querySelectorAll('.mech-row-checkbox').forEach(cb => cb.checked = master.checked);
            updateMechSelectedCount();
        }
        function updateMechSelectedCount() {
            const count = document.querySelectorAll('.mech-row-checkbox:checked').length;
            document.getElementById('mech-selected-count').textContent = count;
        }
        function confirmMechBulkDelete() {
            const checked = document.querySelectorAll('.mech-row-checkbox:checked');
            if (!checked.length) { alert('No records selected.'); return; }
            if (!confirm('Mark ' + checked.length + ' mechanic(s) as inactive?')) return;
            const form = document.getElementById('mech-bulk-delete-form');
            form.querySelectorAll('input[name="ids[]"]').forEach(el => el.remove());
            checked.forEach(cb => { const input = document.createElement('input'); input.type = 'hidden'; input.name = 'ids[]'; input.value = cb.value; form.appendChild(input); });
            form.submit();
        }
    </script>
</x-app-layout>