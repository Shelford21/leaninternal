<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-lg text-slate-800 dark:text-slate-200 leading-tight">
            {{ __('master-data.data_masters') }} / {{ __('master-data.gsd_elements') }}
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
                <form method="GET" action="{{ route('master-data.gsd-elements') }}" id="gsde-filter-form"
                    class="flex flex-1 flex-wrap items-center gap-2">
                    <div class="relative" id="gsde-autocomplete-wrapper">
                        <input type="search" name="search" id="gsde-search-input" value="{{ $search }}"
                            placeholder="Search GSD elements..." autocomplete="off"
                            class="w-56 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-1.5 text-sm focus:border-indigo-500 focus:outline-none" />
                        <div id="gsde-search-dropdown"
                            class="absolute left-0 top-full mt-1 z-50 w-full max-h-60 overflow-y-auto rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 shadow-lg hidden">
                        </div>
                    </div>
                    <select id="gsde-filter-column" onchange="updateGsdeFilterValues()"
                        class="rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-1.5 text-sm text-slate-600 dark:text-slate-300 focus:border-indigo-500 focus:outline-none">
                        <option value="">Filter by...</option>
                        <option value="element_name" @selected($filterColumn === 'element_name')>Element Name</option>
                        <option value="code" @selected($filterColumn === 'code')>Code</option>
                        <option value="motion_sequence" @selected($filterColumn === 'motion_sequence')>Motion Sequence
                        </option>
                    </select>
                    <select id="gsde-filter-value" name="filter_value"
                        class="rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-1.5 text-sm text-slate-600 dark:text-slate-300 focus:border-indigo-500 focus:outline-none">
                        <option value="">All values</option>
                    </select>
                    <input type="hidden" name="filter_column" id="gsde-filter-column-hidden"
                        value="{{ $filterColumn }}" />
                    <input type="hidden" name="sort" value="{{ $sort ?? 'element_name' }}" />
                    <input type="hidden" name="direction" value="{{ $direction ?? 'asc' }}" />
                    <label
                        class="inline-flex items-center gap-1.5 text-sm text-slate-600 dark:text-slate-300 cursor-pointer">
                        <input type="checkbox" name="show_inactive" value="1" @checked($showInactive)
                            onchange="this.form.submit()"
                            class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500" />
                        Show inactive
                    </label>
                    <a href="{{ route('master-data.gsd-elements') }}"
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
                    <a href="{{ route('master-data.gsd-elements.export') }}"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Export
                    </a>
                    @if (auth()->user()->role->role_name !== 'viewer')
                        <button type="button"
                            onclick="document.getElementById('gsde-create-modal').classList.remove('hidden')"
                            class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500">
                            + New
                        </button>
                        <button type="button" id="gsde-delete-btn" onclick="enterGsdeDeleteMode()"
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

            <div id="gsde-delete-bar"
                class="hidden flex items-center justify-between rounded-xl border border-red-200 dark:border-red-700 bg-red-50 dark:bg-red-900/20 px-4 py-3">
                <span class="text-sm text-red-700 dark:text-red-300"><span id="gsde-selected-count">0</span> record(s)
                    selected</span>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="confirmGsdeBulkDelete()"
                        class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-500">Confirm
                        Delete</button>
                    <button type="button" onclick="exitGsdeDeleteMode()"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-300 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">Cancel
                        Delete</button>
                </div>
            </div>

            <form id="gsde-bulk-delete-form" action="{{ route('master-data.gsd-elements.bulk-deactivate') }}"
                method="POST">@csrf @method('PATCH')

                {{-- TABLE --}}
                <div
                    class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm">
                    <table
                        class="min-w-full divide-y divide-slate-200 dark:divide-slate-700 text-left text-sm text-slate-700 dark:text-slate-300">
                        <thead class="bg-slate-50 dark:bg-slate-700/50">
                            @php
                                $currentSort = $sort ?? 'element_name';
                                $currentDir = $direction ?? 'asc';
                            @endphp
                            <tr>
                                <th class="gsde-delete-col hidden px-4 py-3 font-semibold w-10">
                                    <input type="checkbox" id="gsde-select-all" onchange="toggleAllGsdeCheckboxes(this)"
                                        class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500" />
                                </th>
                                <th class="px-4 py-3 font-semibold">No</th>
                                <th class="px-4 py-3 font-semibold">
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'element_name', 'direction' => $currentSort === 'element_name' && $currentDir === 'asc' ? 'desc' : 'asc']) }}"
                                        class="hover:text-indigo-600 dark:hover:text-indigo-400">Element Name
                                        @if ($currentSort === 'element_name')<span
                                        class="text-xs">{!! $currentDir === 'asc' ? '&#9650;' : '&#9660;' !!}</span>@endif</a>
                                </th>
                                <th class="px-4 py-3 font-semibold">
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'description', 'direction' => $currentSort === 'description' && $currentDir === 'asc' ? 'desc' : 'asc']) }}"
                                        class="hover:text-indigo-600 dark:hover:text-indigo-400">Description
                                        @if ($currentSort === 'description')<span
                                        class="text-xs">{!! $currentDir === 'asc' ? '&#9650;' : '&#9660;' !!}</span>@endif</a>
                                </th>
                                <th class="px-4 py-3 font-semibold">
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'code', 'direction' => $currentSort === 'code' && $currentDir === 'asc' ? 'desc' : 'asc']) }}"
                                        class="hover:text-indigo-600 dark:hover:text-indigo-400">Code
                                        @if ($currentSort === 'code')<span
                                        class="text-xs">{!! $currentDir === 'asc' ? '&#9650;' : '&#9660;' !!}</span>@endif</a>
                                </th>
                                <th class="px-4 py-3 font-semibold">
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'tmu', 'direction' => $currentSort === 'tmu' && $currentDir === 'asc' ? 'desc' : 'asc']) }}"
                                        class="hover:text-indigo-600 dark:hover:text-indigo-400">TMU
                                        @if ($currentSort === 'tmu')<span
                                        class="text-xs">{!! $currentDir === 'asc' ? '&#9650;' : '&#9660;' !!}</span>@endif</a>
                                </th>
                                <th class="px-4 py-3 font-semibold">
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'seconds', 'direction' => $currentSort === 'seconds' && $currentDir === 'asc' ? 'desc' : 'asc']) }}"
                                        class="hover:text-indigo-600 dark:hover:text-indigo-400">Seconds
                                        @if ($currentSort === 'seconds')<span
                                        class="text-xs">{!! $currentDir === 'asc' ? '&#9650;' : '&#9660;' !!}</span>@endif</a>
                                </th>
                                <th class="px-4 py-3 font-semibold">
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'motion_sequence', 'direction' => $currentSort === 'motion_sequence' && $currentDir === 'asc' ? 'desc' : 'asc']) }}"
                                        class="hover:text-indigo-600 dark:hover:text-indigo-400">Motion Sequence
                                        @if ($currentSort === 'motion_sequence')<span
                                        class="text-xs">{!! $currentDir === 'asc' ? '&#9650;' : '&#9660;' !!}</span>@endif</a>
                                </th>
                                <th class="px-4 py-3 font-semibold">
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'status', 'direction' => $currentSort === 'status' && $currentDir === 'asc' ? 'desc' : 'asc']) }}"
                                        class="hover:text-indigo-600 dark:hover:text-indigo-400">Status
                                        @if ($currentSort === 'status')<span
                                        class="text-xs">{!! $currentDir === 'asc' ? '&#9650;' : '&#9660;' !!}</span>@endif</a>
                                </th>
                                <th class="px-4 py-3 font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            @forelse ($gsdElements as $index => $element)
                                <tr>
                                    <td class="gsde-delete-col hidden px-4 py-3 w-10">
                                        <input type="checkbox" name="ids[]" value="{{ $element->id }}"
                                            onchange="updateGsdeSelectedCount()"
                                            class="gsde-row-checkbox rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500" />
                                    </td>
                                    <td class="px-4 py-3">{{ $index + 1 }}</td>
                                    <td class="px-4 py-3 font-medium text-slate-900 dark:text-slate-100">
                                        {{ $element->element_name }}
                                    </td>
                                    <td class="px-4 py-3">{{ $element->description ?? '—' }}</td>
                                    <td class="px-4 py-3"><code
                                            class="rounded bg-slate-100 dark:bg-slate-700 px-1.5 py-0.5 text-xs">{{ $element->code }}</code>
                                    </td>
                                    <td class="px-4 py-3">{{ $element->tmu }}</td>
                                    <td class="px-4 py-3">{{ $element->seconds }}</td>
                                    <td class="px-4 py-3">{{ $element->motion_sequence ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        @if ($element->status === 'active')
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
                                                onclick="openGsdeEdit(
                                                                                {{ $element->id }},
                                                                                @js($element->element_name),
                                                                                @js($element->description),
                                                                                @js($element->code),
                                                                                {{ $element->tmu }},
                                                                                {{ $element->seconds }},
                                                                                @js($element->motion_sequence),
                                                                                {{ $element->gsd_category_id }}
                                                                            )">Edit</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-4 py-8 text-center text-slate-400 dark:text-slate-500">No GSD
                                        elements found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>

    {{-- CREATE MODAL --}}
    <div id="gsde-create-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/40"
        onclick="if(event.target===this)this.classList.add('hidden')">
        <div
            class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-w-lg p-6 space-y-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800 dark:text-slate-200">New GSD Element</h3>
                <button onclick="document.getElementById('gsde-create-modal').classList.add('hidden')"
                    class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">&times;</button>
            </div>
            <form method="POST" action="{{ route('master-data.gsd-elements.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Category *</label>
                    <select name="gsd_category_id" required
                        class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2">
                        <option value="">Select category</option>
                        @foreach ($gsdCategories as $category)
                            <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Element Name
                        *</label>
                    <input type="text" name="element_name" required maxlength="200"
                        class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2"
                        style="text-transform:uppercase" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Description</label>
                    <input type="text" name="description" maxlength="255"
                        class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2"
                        style="text-transform:uppercase" />
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Code *</label>
                        <input type="text" name="code" required maxlength="50"
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2"
                            style="text-transform:uppercase" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">TMU *</label>
                        <input type="number" name="tmu" required step="0.01" min="0"
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Seconds
                            *</label>
                        <input type="number" name="seconds" required step="0.01" min="0"
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2" />
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Motion
                        Sequence</label>
                    <input type="text" name="motion_sequence" maxlength="100"
                        class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2"
                        style="text-transform:uppercase" />
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('gsde-create-modal').classList.add('hidden')"
                        class="rounded-xl border border-slate-200 dark:border-slate-600 px-4 py-2 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">Cancel</button>
                    <button type="submit"
                        class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">Create</button>
                </div>
            </form>
        </div>
    </div>

    {{-- EDIT MODAL --}}
    <div id="gsde-edit-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/40"
        onclick="if(event.target===this)this.classList.add('hidden')">
        <div
            class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-w-lg p-6 space-y-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800 dark:text-slate-200">Edit GSD Element</h3>
                <button onclick="document.getElementById('gsde-edit-modal').classList.add('hidden')"
                    class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">&times;</button>
            </div>
            <form id="gsde-edit-form" method="POST" action="" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Category *</label>
                    <select name="gsd_category_id" id="gsde-edit-category" required
                        class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2">
                        <option value="">Select category</option>
                        @foreach ($gsdCategories as $category)
                            <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Element Name
                        *</label>
                    <input type="text" name="element_name" id="gsde-edit-name" required maxlength="200"
                        class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2"
                        style="text-transform:uppercase" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Description</label>
                    <input type="text" name="description" id="gsde-edit-description" maxlength="255"
                        class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2"
                        style="text-transform:uppercase" />
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Code *</label>
                        <input type="text" name="code" id="gsde-edit-code" required maxlength="50"
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2"
                            style="text-transform:uppercase" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">TMU *</label>
                        <input type="number" name="tmu" id="gsde-edit-tmu" required step="0.01" min="0"
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Seconds
                            *</label>
                        <input type="number" name="seconds" id="gsde-edit-seconds" required step="0.01" min="0"
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2" />
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Motion
                        Sequence</label>
                    <input type="text" name="motion_sequence" id="gsde-edit-motion" maxlength="100"
                        class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2"
                        style="text-transform:uppercase" />
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('gsde-edit-modal').classList.add('hidden')"
                        class="rounded-xl border border-slate-200 dark:border-slate-600 px-4 py-2 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">Cancel</button>
                    <button type="submit"
                        class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">Update</button>
                </div>
            </form>
        </div>
    </div>

    {{-- JS: Filter values + Edit modal population --}}
    <script>
        const filterValues = @json($filterValues);

        function updateGsdeFilterValues() {
            const col = document.getElementById('gsde-filter-column').value;
            const sel = document.getElementById('gsde-filter-value');
            sel.innerHTML = '<option value="">All values</option>';
            if (col && filterValues[col]) {
                filterValues[col].forEach(v => {
                    const o = document.createElement('option');
                    o.value = v; o.textContent = v;
                    @if ($filterValue !== '')
                        if (v === @js($filterValue)) o.selected = true;
                    @endif
                    sel.appendChild(o);
                });
            }
        }

        function openGsdeEdit(id, name, desc, code, tmu, seconds, motion, categoryId) {
            const form = document.getElementById('gsde-edit-form');
            form.action = '{{ url("master-data/gsd-elements") }}/' + id;
            document.getElementById('gsde-edit-name').value = name || '';
            document.getElementById('gsde-edit-description').value = desc || '';
            document.getElementById('gsde-edit-code').value = code || '';
            document.getElementById('gsde-edit-tmu').value = tmu || '';
            document.getElementById('gsde-edit-seconds').value = seconds || '';
            document.getElementById('gsde-edit-motion').value = motion || '';
            document.getElementById('gsde-edit-category').value = categoryId || '';
            document.getElementById('gsde-edit-modal').classList.remove('hidden');
        }

        // Init filter on page load
        updateGsdeFilterValues();

        // Search on Enter keypress, instant filter/checkbox submit
        const gsdeForm = document.getElementById('gsde-filter-form');
        const gsdeSearchInput = gsdeForm.querySelector('input[name="search"]');
        const gsdeFilterValueSelect = document.getElementById('gsde-filter-value');
        gsdeSearchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                gsdeForm.submit();
            }
        });
        gsdeFilterValueSelect.addEventListener('change', function () {
            gsdeForm.submit();
        });

        // Autocomplete search
        const gsdeAcDropdown = document.getElementById('gsde-search-dropdown');
        let gsdeAcTimer;
        gsdeSearchInput.addEventListener('input', function () {
            clearTimeout(gsdeAcTimer);
            const q = this.value.trim();
            if (q.length < 1) { gsdeAcDropdown.classList.add('hidden'); gsdeAcDropdown.innerHTML = ''; return; }
            gsdeAcTimer = setTimeout(() => {
                fetch('{{ url("master-data/gsd-elements/search") }}?q=' + encodeURIComponent(q))
                    .then(r => r.json()).then(results => {
                        if (!results.length) { gsdeAcDropdown.classList.add('hidden'); gsdeAcDropdown.innerHTML = ''; return; }
                        gsdeAcDropdown.innerHTML = results.map(r =>
                            `<div class="ac-item cursor-pointer px-3 py-2 text-sm hover:bg-indigo-50 dark:hover:bg-slate-700" data-label="${r.label.replace(/"/g, '&quot;')}">
                                <div class="font-medium text-slate-800 dark:text-slate-200">${r.label}</div>
                                ${r.description ? `<div class="text-xs text-slate-500 dark:text-slate-400 truncate">${r.description}</div>` : ''}
                            </div>`
                        ).join('');
                        gsdeAcDropdown.classList.remove('hidden');
                    });
            }, 300);
        });
        gsdeAcDropdown.addEventListener('click', function (e) {
            const item = e.target.closest('.ac-item');
            if (!item) return;
            gsdeSearchInput.value = item.dataset.label;
            gsdeAcDropdown.classList.add('hidden');
            gsdeForm.submit();
        });
        document.addEventListener('click', function (e) {
            if (!document.getElementById('gsde-autocomplete-wrapper').contains(e.target)) {
                gsdeAcDropdown.classList.add('hidden');
            }
        });

        // --- Bulk Delete Functions ---
        function enterGsdeDeleteMode() {
            document.querySelectorAll('.gsde-delete-col').forEach(c => c.classList.remove('hidden'));
            document.getElementById('gsde-delete-btn').classList.add('hidden');
            document.getElementById('gsde-delete-bar').classList.remove('hidden');
            document.getElementById('gsde-select-all').checked = false;
            updateGsdeSelectedCount();
        }
        function exitGsdeDeleteMode() {
            document.querySelectorAll('.gsde-delete-col').forEach(c => c.classList.add('hidden'));
            document.getElementById('gsde-delete-btn').classList.remove('hidden');
            document.getElementById('gsde-delete-bar').classList.add('hidden');
            document.querySelectorAll('.gsde-row-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('gsde-select-all').checked = false;
        }
        function toggleAllGsdeCheckboxes(master) {
            document.querySelectorAll('.gsde-row-checkbox').forEach(cb => cb.checked = master.checked);
            updateGsdeSelectedCount();
        }
        function updateGsdeSelectedCount() {
            const count = document.querySelectorAll('.gsde-row-checkbox:checked').length;
            document.getElementById('gsde-selected-count').textContent = count;
        }
        function confirmGsdeBulkDelete() {
            const checked = document.querySelectorAll('.gsde-row-checkbox:checked');
            if (!checked.length) { alert('No records selected.'); return; }
            if (!confirm('Mark ' + checked.length + ' GSD element(s) as inactive?')) return;
            const form = document.getElementById('gsde-bulk-delete-form');
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
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Import GSD Elements</h3>
                    <button type="button" onclick="document.getElementById('import-modal').classList.add('hidden')"
                        class="text-slate-500 dark:text-slate-400">✕</button>
                </div>
                <form action="{{ route('master-data.gsd-elements.import') }}" method="POST"
                    enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label for="import-file"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Excel File
                            (.xlsx)</label>
                        <input id="import-file" name="file" type="file" accept=".xlsx,.xls,.csv" required
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2" />
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Columns: Element Name, Code, TMU,
                            Seconds, Motion Sequence, Category, Descriptions</p>
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