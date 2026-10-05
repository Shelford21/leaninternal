<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('operations.index') }}" class="text-teal-600 hover:text-teal-700">Lean Operations</a>
            <span class="text-slate-300">/</span>
            <h2 class="font-semibold text-lg text-slate-800 dark:text-slate-200 leading-tight">Line Balancing</h2>
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
            @if (session('error'))
                <div
                    class="rounded-xl border border-red-200 dark:border-red-700 bg-red-50 dark:bg-red-900/30 px-4 py-3 text-sm text-red-700 dark:text-red-300">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Header banner --}}
            <div class="relative overflow-hidden rounded-2xl bg-slate-950 p-6 text-white shadow-xl sm:p-8">
                <div class="relative z-10 max-w-3xl">
                    <div
                        class="mb-3 flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-emerald-300">
                        <span class="text-lg">⇄</span> Work plan module
                    </div>
                    <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">Line Balancing</h1>
                    <p class="mt-3 text-sm leading-6 text-slate-300">Compare station cycle time with takt time and
                        identify bottlenecks.</p>
                </div>
                <div class="absolute -right-16 -top-28 h-80 w-80 rounded-full bg-emerald-500/20 blur-3xl"></div>
            </div>

            {{-- Toolbar --}}
            <div class="flex flex-wrap items-center justify-between gap-3">
                <form method="GET" action="{{ route('operations.line.balancing.index') }}" id="lb-filter-form"
                    class="flex flex-1 flex-wrap items-center gap-2">
                    <div class="relative" id="lb-autocomplete-wrapper">
                        <input type="search" name="search" id="lb-search-input" value="{{ $search }}"
                            placeholder="Search reports..." autocomplete="off"
                            class="w-56 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-1.5 text-sm focus:border-indigo-500 focus:outline-none" />
                        <div id="lb-search-dropdown"
                            class="absolute left-0 top-full mt-1 z-50 w-full max-h-60 overflow-y-auto rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 shadow-lg hidden">
                        </div>
                    </div>
                    <select id="lb-filter-column" onchange="updateLbFilterValues()"
                        class="rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-1.5 text-sm text-slate-600 dark:text-slate-300 focus:border-indigo-500 focus:outline-none">
                        <option value="">Filter by...</option>
                        <option value="factory" @selected($filterColumn === 'factory')>Factory</option>
                        <option value="article" @selected($filterColumn === 'article')>Article</option>
                        <option value="report_name" @selected($filterColumn === 'report_name')>Report Name</option>
                        <option value="created_by" @selected($filterColumn === 'created_by')>Created By</option>
                        <option value="status" @selected($filterColumn === 'status')>Status</option>
                    </select>
                    <select id="lb-filter-value" name="filter_value"
                        class="rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-1.5 text-sm text-slate-600 dark:text-slate-300 focus:border-indigo-500 focus:outline-none">
                        <option value="">All values</option>
                    </select>
                    <input type="hidden" name="filter_column" id="lb-filter-column-hidden"
                        value="{{ $filterColumn }}" />
                    <input type="hidden" name="sort" value="{{ $sort }}" />
                    <input type="hidden" name="direction" value="{{ $direction }}" />
                    <label
                        class="inline-flex items-center gap-1.5 text-sm text-slate-600 dark:text-slate-300 cursor-pointer">
                        <input type="checkbox" name="show_inactive" value="1" @checked($showInactive)
                            onchange="this.form.submit()"
                            class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500" />
                        Show Inactive
                    </label>
                    <a href="{{ route('operations.line.balancing.index') }}"
                        class="rounded-lg border border-slate-200 dark:border-slate-600 px-3 py-1.5 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">Clear</a>
                </form>
                <div class="flex items-center gap-2">
                    {{-- New --}}
                    <button type="button" onclick="document.getElementById('create-modal').classList.remove('hidden')"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500">
                        + New
                    </button>
                    {{-- Delete --}}
                    <button type="button" id="lb-delete-btn" onclick="enterDeleteMode()"
                        class="inline-flex items-center gap-2 rounded-xl border border-red-300 dark:border-red-600 px-4 py-2 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Delete
                    </button>
                </div>
            </div>

            {{-- Bulk delete action bar --}}
            <div id="lb-delete-bar"
                class="hidden flex items-center justify-between rounded-xl border border-red-200 dark:border-red-700 bg-red-50 dark:bg-red-900/20 px-4 py-3">
                <span class="text-sm text-red-700 dark:text-red-300">
                    <span id="lb-selected-count">0</span> record(s) selected
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
                <form id="lb-bulk-delete-form" action="{{ route('operations.line.balancing.bulkDeactivate') }}"
                    method="POST">
                    @csrf
                    @method('PATCH')
                    <table
                        class="min-w-full divide-y divide-slate-200 dark:divide-slate-700 text-left text-sm text-slate-700 dark:text-slate-300">
                        <thead class="bg-slate-50 dark:bg-slate-700/50">
                            <tr>
                                <th class="lb-delete-col hidden px-4 py-3 font-semibold w-10">
                                    <input type="checkbox" id="lb-select-all" onchange="toggleAllCheckboxes(this)"
                                        class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500" />
                                </th>
                                <th class="px-4 py-3 font-semibold">No</th>
                                <th class="px-4 py-3 font-semibold">
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'factory', 'direction' => ($sort === 'factory' && $direction === 'asc') ? 'desc' : 'asc']) }}"
                                        class="hover:text-indigo-600 dark:hover:text-indigo-400">
                                        Factory
                                        @if ($sort === 'factory')
                                            <span
                                                class="text-xs">{!! $direction === 'asc' ? '&#9650;' : '&#9660;' !!}</span>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-4 py-3 font-semibold">
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'article', 'direction' => ($sort === 'article' && $direction === 'asc') ? 'desc' : 'asc']) }}"
                                        class="hover:text-indigo-600 dark:hover:text-indigo-400">
                                        Article
                                        @if ($sort === 'article')
                                            <span
                                                class="text-xs">{!! $direction === 'asc' ? '&#9650;' : '&#9660;' !!}</span>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-4 py-3 font-semibold">LB Report Name</th>
                                <th class="px-4 py-3 font-semibold">
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'direction' => ($sort === 'created_at' && $direction === 'asc') ? 'desc' : 'asc']) }}"
                                        class="hover:text-indigo-600 dark:hover:text-indigo-400">
                                        Created Date
                                        @if ($sort === 'created_at')
                                            <span
                                                class="text-xs">{!! $direction === 'asc' ? '&#9650;' : '&#9660;' !!}</span>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-4 py-3 font-semibold">
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'updated_at', 'direction' => ($sort === 'updated_at' && $direction === 'asc') ? 'desc' : 'asc']) }}"
                                        class="hover:text-indigo-600 dark:hover:text-indigo-400">
                                        Edited Date
                                        @if ($sort === 'updated_at')
                                            <span
                                                class="text-xs">{!! $direction === 'asc' ? '&#9650;' : '&#9660;' !!}</span>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-4 py-3 font-semibold">
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'created_by', 'direction' => ($sort === 'created_by' && $direction === 'asc') ? 'desc' : 'asc']) }}"
                                        class="hover:text-indigo-600 dark:hover:text-indigo-400">
                                        Created By
                                        @if ($sort === 'created_by')
                                            <span
                                                class="text-xs">{!! $direction === 'asc' ? '&#9650;' : '&#9660;' !!}</span>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-4 py-3 font-semibold">
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'status', 'direction' => ($sort === 'status' && $direction === 'asc') ? 'desc' : 'asc']) }}"
                                        class="hover:text-indigo-600 dark:hover:text-indigo-400">
                                        Status
                                        @if ($sort === 'status')
                                            <span
                                                class="text-xs">{!! $direction === 'asc' ? '&#9650;' : '&#9660;' !!}</span>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-4 py-3 font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            @forelse ($reports as $index => $report)
                                <tr>
                                    <td class="lb-delete-col hidden px-4 py-3 w-10">
                                        <input type="checkbox" name="ids[]" value="{{ $report->id }}"
                                            onchange="updateSelectedCount()"
                                            class="lb-row-checkbox rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500" />
                                    </td>
                                    <td class="px-4 py-3">{{ $reports->firstItem() + $index }}</td>
                                    <td class="px-4 py-3 font-medium text-slate-900 dark:text-slate-100">
                                        {{ $report->factory->factory_name ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3">{{ $report->article->article_name ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('operations.line.balancing.edit', $report->id) }}"
                                            class="font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 hover:underline">
                                            {{ $report->report_name }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-slate-500 dark:text-slate-400">
                                        {{ $report->created_at->format('d M Y H:i') }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-500 dark:text-slate-400">
                                        {{ $report->updated_at->format('d M Y H:i') }}
                                    </td>
                                    <td class="px-4 py-3">{{ $report->createdBy->name ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        @if ($report->status === 'active')
                                            <span
                                                class="inline-flex items-center rounded-full bg-emerald-50 dark:bg-emerald-900/30 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:text-emerald-300 ring-1 ring-inset ring-emerald-600/20">Active</span>
                                        @else
                                            <span
                                                class="inline-flex items-center rounded-full bg-red-50 dark:bg-red-900/30 px-2 py-0.5 text-xs font-medium text-red-700 dark:text-red-300 ring-1 ring-inset ring-red-600/20">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('operations.line.balancing.edit', $report->id) }}"
                                                class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">Edit</a>
                                            <a href="{{ route('operations.line.balancing.export', $report->id) }}"
                                                class="text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300"
                                                title="Export to Excel">📊</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">
                                        No reports found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </form>
            </div>

            {{-- Pagination --}}
            <div class="flex justify-end">
                {{ $reports->links() }}
            </div>
        </div>
    </div>

    {{-- Create Modal --}}
    <div id="create-modal" class="fixed inset-0 z-50 hidden bg-slate-900/40">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="w-full max-w-lg rounded-2xl bg-white dark:bg-slate-800 p-6 shadow-xl">
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">New Line Balancing Report</h3>
                    <button type="button" onclick="document.getElementById('create-modal').classList.add('hidden')"
                        class="text-slate-500 dark:text-slate-400">✕</button>
                </div>
                <form action="{{ route('operations.line.balancing.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="create_factory"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Factory</label>
                        <select id="create_factory" name="factory_id" required
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2 focus:border-indigo-500 focus:outline-none">
                            <option value="">Select Factory</option>
                            @foreach ($filterOptions['factory'] as $factoryName)
                                @php $f = \App\Models\Factory::where('factory_name', $factoryName)->first(); @endphp
                                @if ($f)
                                    <option value="{{ $f->id }}">{{ $f->factory_name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="create_article"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Article</label>
                        <select id="create_article" name="article_id" required
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2 focus:border-indigo-500 focus:outline-none">
                            <option value="">Select Article</option>
                            @php $articles = \App\Models\Article::where('status', 'active')->orderBy('article_name')->get(); @endphp
                            @foreach ($articles as $article)
                                <option value="{{ $article->id }}">{{ $article->article_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="create_line"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Production
                            Line</label>
                        <select id="create_line" name="line_id" required
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2 focus:border-indigo-500 focus:outline-none">
                            <option value="">Select Line</option>
                            @php $lines = \App\Models\ProductionLine::where('status', 'active')->orderBy('line_name')->get(); @endphp
                            @foreach ($lines as $line)
                                <option value="{{ $line->id }}">{{ $line->line_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="create_report_name"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">LB Report
                            Name</label>
                        <input id="create_report_name" name="report_name" type="text" required maxlength="150"
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2 focus:border-indigo-500 focus:outline-none"
                            placeholder="e.g. LB Quty 1 C4 2026-01" />
                    </div>
                    @if ($errors->any())
                        <div class="rounded-lg bg-red-50 dark:bg-red-900/30 p-3 text-sm text-red-700 dark:text-red-300">
                            <ul class="list-disc pl-4">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" onclick="document.getElementById('create-modal').classList.add('hidden')"
                            class="rounded-xl border border-slate-200 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 dark:hover:bg-slate-700">Cancel</button>
                        <button type="submit"
                            class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">Create
                            Report</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Filter options data
        const filterOptions = @js($filterOptions);

        function updateLbFilterValues() {
            const column = document.getElementById('lb-filter-column').value;
            const valueSelect = document.getElementById('lb-filter-value');
            const hiddenInput = document.getElementById('lb-filter-column-hidden');

            hiddenInput.value = column;
            valueSelect.innerHTML = '<option value="">All values</option>';

            if (column && filterOptions[column]) {
                filterOptions[column].forEach(val => {
                    const opt = document.createElement('option');
                    opt.value = val;
                    opt.textContent = val;
                    valueSelect.appendChild(opt);
                });
            }
        }

        // Auto-submit on filter value change
        document.getElementById('lb-filter-value').addEventListener('change', function () {
            document.getElementById('lb-filter-form').submit();
        });

        // Auto-submit search on Enter
        document.getElementById('lb-search-input').addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('lb-filter-form').submit();
            }
        });

        // Initialize filter values on page load
        updateLbFilterValues();

        // Bulk delete functions
        function enterDeleteMode() {
            document.getElementById('lb-delete-bar').classList.remove('hidden');
            document.querySelectorAll('.lb-delete-col').forEach(el => el.classList.remove('hidden'));
            document.getElementById('lb-delete-btn').classList.add('hidden');
        }

        function exitDeleteMode() {
            document.getElementById('lb-delete-bar').classList.add('hidden');
            document.querySelectorAll('.lb-delete-col').forEach(el => el.classList.add('hidden'));
            document.getElementById('lb-delete-btn').classList.remove('hidden');
            document.querySelectorAll('.lb-row-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('lb-select-all').checked = false;
            updateSelectedCount();
        }

        function toggleAllCheckboxes(master) {
            document.querySelectorAll('.lb-row-checkbox').forEach(cb => cb.checked = master.checked);
            updateSelectedCount();
        }

        function updateSelectedCount() {
            const count = document.querySelectorAll('.lb-row-checkbox:checked').length;
            document.getElementById('lb-selected-count').textContent = count;
        }

        function confirmBulkDelete() {
            const checked = document.querySelectorAll('.lb-row-checkbox:checked');
            if (checked.length === 0) {
                alert('Please select at least one report to delete.');
                return;
            }
            if (confirm('Are you sure you want to deactivate ' + checked.length + ' report(s)?')) {
                document.getElementById('lb-bulk-delete-form').submit();
            }
        }
    </script>
</x-app-layout>