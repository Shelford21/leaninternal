<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-lg text-slate-800 dark:text-slate-200 leading-tight">
            {{ __('master-data.data_masters') }} / {{ __('master-data.articles') }}
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

            <div class="flex flex-wrap items-center justify-between gap-3">
                <form method="GET" action="{{ route('master-data.articles') }}" id="article-filter-form"
                    class="flex flex-1 flex-wrap items-center gap-2">
                    <div class="relative" id="article-autocomplete-wrapper">
                        <input type="search" name="search" id="article-search-input" value="{{ $search }}"
                            placeholder="Search articles..." autocomplete="off"
                            class="w-56 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-1.5 text-sm focus:border-indigo-500 focus:outline-none" />
                        <div id="article-search-dropdown"
                            class="absolute left-0 top-full mt-1 z-50 w-full max-h-60 overflow-y-auto rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 shadow-lg hidden">
                        </div>
                    </div>
                    <select id="article-filter-column" onchange="updateArticleFilterValues()"
                        class="rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-1.5 text-sm text-slate-600 dark:text-slate-300 focus:border-indigo-500 focus:outline-none">
                        <option value="">Filter by...</option>
                        <option value="article_name" @selected($filterColumn === 'article_name')>Nama Article</option>
                    </select>
                    <select id="article-filter-value" name="filter_value"
                        class="rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-1.5 text-sm text-slate-600 dark:text-slate-300 focus:border-indigo-500 focus:outline-none">
                        <option value="">All values</option>
                    </select>
                    <input type="hidden" name="filter_column" id="article-filter-column-hidden"
                        value="{{ $filterColumn }}" />
                    <input type="hidden" name="sort" value="{{ $sort ?? 'article_name' }}" />
                    <input type="hidden" name="direction" value="{{ $direction ?? 'asc' }}" />
                    <label
                        class="inline-flex items-center gap-1.5 text-sm text-slate-600 dark:text-slate-300 cursor-pointer">
                        <input type="checkbox" name="show_inactive" value="1" @checked($showInactive ?? false)
                            onchange="this.form.submit()"
                            class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500" />
                        Show Inactive
                    </label>
                    <a href="{{ route('master-data.articles') }}"
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
                    <a href="{{ route('master-data.articles.export') }}"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Export
                    </a>
                    @if (auth()->user()->role->role_name !== 'viewer')
                        <button type="button" onclick="document.getElementById('article-modal').classList.remove('hidden')"
                            class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500">+
                            New</button>
                        <button type="button" id="article-delete-btn" onclick="enterArticleDeleteMode()"
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

            {{-- Soft Delete Bar --}}
            <div id="article-delete-bar"
                class="hidden flex items-center justify-between rounded-xl border border-red-200 dark:border-red-700 bg-red-50 dark:bg-red-900/20 px-4 py-3">
                <span class="text-sm text-red-700 dark:text-red-300"><span id="article-selected-count">0</span>
                    record(s) selected</span>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="confirmArticleBulkDelete()"
                        class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-500">Confirm
                        Delete</button>
                    <button type="button" onclick="exitArticleDeleteMode()"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-300 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">Cancel</button>
                </div>
            </div>

            <form id="article-bulk-delete-form" action="{{ route('master-data.articles.bulk-deactivate') }}"
                method="POST">@csrf @method('PATCH')</form>

            <div
                class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm">
                <table
                    class="min-w-full divide-y divide-slate-200 dark:divide-slate-700 text-left text-sm text-slate-700 dark:text-slate-300">
                    <thead class="bg-slate-50 dark:bg-slate-700/50">
                        @php
                            $currentSort = $sort ?? 'article_name';
                            $currentDir = $direction ?? 'asc';
                        @endphp
                        <tr>
                            <th class="article-delete-col hidden px-4 py-3 font-semibold w-10">
                                <input type="checkbox" id="article-select-all"
                                    onchange="toggleAllArticleCheckboxes(this)"
                                    class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500" />
                            </th>
                            <th class="px-4 py-3 font-semibold">No</th>
                            <th class="px-4 py-3 font-semibold">Photo</th>
                            <th class="px-4 py-3 font-semibold">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'article_name', 'direction' => $currentSort === 'article_name' && $currentDir === 'asc' ? 'desc' : 'asc']) }}"
                                    class="hover:text-indigo-600 dark:hover:text-indigo-400">Nama Article
                                    @if($currentSort === 'article_name')<span
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
                        @forelse ($articles as $index => $article)
                            <tr>
                                <td class="article-delete-col hidden px-4 py-3 w-10">
                                    <input type="checkbox" name="ids[]" value="{{ $article->id }}"
                                        onchange="updateArticleSelectedCount()"
                                        class="article-row-checkbox rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500" />
                                </td>
                                <td class="px-4 py-3">{{ $index + 1 }}</td>
                                <td class="px-4 py-3">
                                    @if (!empty($article->photo_path))
                                        <img src="{{ asset('storage/' . ltrim($article->photo_path, '/')) }}"
                                            alt="{{ $article->article_name }}" class="h-10 w-10 rounded-full object-cover" />
                                    @else
                                        <div
                                            class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-200 text-xs font-semibold text-slate-600">
                                            {{ strtoupper(substr($article->article_name, 0, 2)) }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-medium text-slate-900 dark:text-slate-100">
                                    {{ $article->article_name }}
                                </td>
                                <td class="px-4 py-3">
                                    @if ($article->status === 'active')
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
                                            onclick="openArticleEdit({{ $article->id }}, @js($article->article_name), @js($article->photo_path ? asset('storage/' . ltrim($article->photo_path, '/')) : null))">Edit</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">No articles
                                    found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- New Article Modal --}}
    <div id="article-modal" class="fixed inset-0 z-50 hidden bg-slate-900/40">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="w-full max-w-lg rounded-2xl bg-white dark:bg-slate-800 p-6 shadow-xl">
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">New Article</h3>
                    <button type="button" onclick="document.getElementById('article-modal').classList.add('hidden')"
                        class="text-slate-500 dark:text-slate-400">✕</button>
                </div>
                <form action="{{ route('master-data.articles.store') }}" method="POST" enctype="multipart/form-data"
                    class="space-y-4">
                    @csrf
                    <div>
                        <label for="photo"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Photo</label>
                        <input id="photo" name="photo" type="file" accept=".jpg,.jpeg,.png"
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2" />
                    </div>
                    <div>
                        <label for="article_name"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Nama
                            Articles</label>
                        <input id="article_name" name="article_name" type="text" required
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2 focus:border-indigo-500 focus:outline-none"
                            style="text-transform:uppercase" />
                    </div>
                    <div>
                        <label for="description"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Description</label>
                        <input id="description" name="description" type="text"
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2 focus:border-indigo-500 focus:outline-none"
                            style="text-transform:uppercase" />
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" onclick="document.getElementById('article-modal').classList.add('hidden')"
                            class="rounded-xl border border-slate-200 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 dark:hover:bg-slate-700">Cancel</button>
                        <button type="submit"
                            class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit Article Modal --}}
    <div id="article-edit-modal" class="fixed inset-0 z-50 hidden bg-slate-900/40">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="w-full max-w-lg rounded-2xl bg-white dark:bg-slate-800 p-6 shadow-xl">
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Edit Article</h3>
                    <button type="button"
                        onclick="document.getElementById('article-edit-modal').classList.add('hidden')"
                        class="text-slate-500 dark:text-slate-400">✕</button>
                </div>
                <form id="article-edit-form" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf @method('PUT')
                    <div>
                        <div id="article-edit-preview" class="mb-2 hidden"><img src="" alt="Current photo"
                                class="h-16 w-16 rounded-lg object-cover"></div>
                        <label for="edit_photo"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Replace
                            Photo</label>
                        <input id="edit_photo" name="photo" type="file" accept=".jpg,.jpeg,.png"
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2" />
                    </div>
                    <div>
                        <label for="edit_article_name"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Nama
                            Articles</label>
                        <input id="edit_article_name" name="article_name" type="text" required
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2 focus:border-indigo-500 focus:outline-none"
                            style="text-transform:uppercase" />
                    </div>
                    <div>
                        <label for="edit_description"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Description</label>
                        <input id="edit_description" name="description" type="text"
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2 focus:border-indigo-500 focus:outline-none"
                            style="text-transform:uppercase" />
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button"
                            onclick="document.getElementById('article-edit-modal').classList.add('hidden')"
                            class="rounded-xl border border-slate-200 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 dark:hover:bg-slate-700">Cancel</button>
                        <button type="submit"
                            class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openArticleEdit(id, name, photoUrl) {
            document.getElementById('article-edit-form').action = `{{ url('/master-data/articles') }}/${id}`;
            document.getElementById('edit_article_name').value = name;
            const preview = document.getElementById('article-edit-preview');
            if (photoUrl) {
                preview.classList.remove('hidden');
                preview.querySelector('img').src = photoUrl;
            } else {
                preview.classList.add('hidden');
            }
            document.getElementById('article-edit-modal').classList.remove('hidden');
        }

        const articleFilterValues = @json($filterValues);
        function updateArticleFilterValues() {
            const column = document.getElementById('article-filter-column').value;
            const valueSelect = document.getElementById('article-filter-value');
            const hiddenColumn = document.getElementById('article-filter-column-hidden');
            hiddenColumn.value = column;
            valueSelect.innerHTML = '<option value="">All values</option>';
            if (column && articleFilterValues[column]) {
                articleFilterValues[column].forEach(function (val) {
                    const opt = document.createElement('option');
                    opt.value = val;
                    opt.textContent = val;
                    if (String(val) === '{{ $filterValue }}') opt.selected = true;
                    valueSelect.appendChild(opt);
                });
            }
        }
        updateArticleFilterValues();

        const articleForm = document.getElementById('article-filter-form');
        const articleSearchInput = articleForm.querySelector('input[name="search"]');
        articleSearchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); articleForm.submit(); }
        });
        document.getElementById('article-filter-value').addEventListener('change', function () { articleForm.submit(); });

        // Autocomplete
        const articleAcDropdown = document.getElementById('article-search-dropdown');
        let articleAcTimer;
        articleSearchInput.addEventListener('input', function () {
            clearTimeout(articleAcTimer);
            const q = this.value.trim();
            if (q.length < 1) { articleAcDropdown.classList.add('hidden'); articleAcDropdown.innerHTML = ''; return; }
            articleAcTimer = setTimeout(() => {
                fetch('{{ url("master-data/articles/search") }}?q=' + encodeURIComponent(q))
                    .then(r => r.json()).then(results => {
                        if (!results.length) { articleAcDropdown.classList.add('hidden'); articleAcDropdown.innerHTML = ''; return; }
                        articleAcDropdown.innerHTML = results.map(r =>
                            `<div class="ac-item cursor-pointer px-3 py-2 text-sm hover:bg-indigo-50 dark:hover:bg-slate-700" data-label="${r.label.replace(/"/g, '&quot;')}">
                                <div class="font-medium text-slate-800 dark:text-slate-200">${r.label}</div>
                                ${r.description ? `<div class="text-xs text-slate-500 dark:text-slate-400 truncate">${r.description}</div>` : ''}
                            </div>`
                        ).join('');
                        articleAcDropdown.classList.remove('hidden');
                    });
            }, 300);
        });
        articleAcDropdown.addEventListener('click', function (e) {
            const item = e.target.closest('.ac-item');
            if (!item) return;
            articleSearchInput.value = item.dataset.label;
            articleAcDropdown.classList.add('hidden');
            articleForm.submit();
        });
        document.addEventListener('click', function (e) {
            if (!document.getElementById('article-autocomplete-wrapper').contains(e.target)) {
                articleAcDropdown.classList.add('hidden');
            }
        });

        // --- Soft Delete ---
        function enterArticleDeleteMode() {
            document.querySelectorAll('.article-delete-col').forEach(c => c.classList.remove('hidden'));
            document.getElementById('article-delete-btn').classList.add('hidden');
            document.getElementById('article-delete-bar').classList.remove('hidden');
            document.getElementById('article-select-all').checked = false;
            updateArticleSelectedCount();
        }
        function exitArticleDeleteMode() {
            document.querySelectorAll('.article-delete-col').forEach(c => c.classList.add('hidden'));
            document.getElementById('article-delete-btn').classList.remove('hidden');
            document.getElementById('article-delete-bar').classList.add('hidden');
            document.querySelectorAll('.article-row-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('article-select-all').checked = false;
        }
        function toggleAllArticleCheckboxes(master) {
            document.querySelectorAll('.article-row-checkbox').forEach(cb => cb.checked = master.checked);
            updateArticleSelectedCount();
        }
        function updateArticleSelectedCount() {
            const count = document.querySelectorAll('.article-row-checkbox:checked').length;
            document.getElementById('article-selected-count').textContent = count;
        }
        function confirmArticleBulkDelete() {
            const checked = document.querySelectorAll('.article-row-checkbox:checked');
            if (!checked.length) { alert('No records selected.'); return; }
            if (!confirm('Mark ' + checked.length + ' article(s) as inactive?')) return;
            const form = document.getElementById('article-bulk-delete-form');
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
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Import Articles</h3>
                    <button type="button" onclick="document.getElementById('import-modal').classList.add('hidden')"
                        class="text-slate-500 dark:text-slate-400">✕</button>
                </div>
                <form action="{{ route('master-data.articles.import') }}" method="POST" enctype="multipart/form-data"
                    class="space-y-4">
                    @csrf
                    <div>
                        <label for="import-file"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Excel File
                            (.xlsx)</label>
                        <input id="import-file" name="file" type="file" accept=".xlsx,.xls,.csv" required
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2" />
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Columns: Article Name</p>
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