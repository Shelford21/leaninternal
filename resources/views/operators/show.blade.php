<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-lg text-slate-800 leading-tight">
            {{ __('Operator Profile') }}
        </h2>
    </x-slot>

    @php
        $latestReport = $operator->ptmsReports()->latest()->first();
    @endphp

    <div class="py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-6xl mx-auto space-y-6">
            {{-- Employee Search Bar --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="relative" id="profile-search-wrapper">
                    <div class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                        <input type="text" id="profile-search-input" placeholder="Search employee by name or NIK..."
                            autocomplete="off" value="{{ $operator->operator_name }}"
                            class="flex-1 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition" />
                        <a href="{{ route('master-data.operators') }}"
                            class="shrink-0 text-sm text-indigo-600 hover:text-indigo-800 font-medium">View All
                            Employees</a>
                    </div>
                    <div id="profile-search-dropdown"
                        class="absolute left-0 right-0 top-full mt-1 z-50 max-h-60 overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg hidden">
                    </div>
                </div>
            </div>

            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ session('error') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-5 md:flex-row md:items-center">
                    <div
                        class="flex h-20 w-20 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-indigo-500 to-cyan-400 text-xl font-bold text-white">
                        @if (!empty($operator->photo_path))
                            <img src="{{ asset('storage/' . ltrim($operator->photo_path, '/')) }}"
                                alt="{{ $operator->operator_name }}" class="h-full w-full object-cover" />
                        @else
                            {{ strtoupper(substr($operator->operator_name, 0, 2)) }}
                        @endif
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-2xl font-bold text-slate-900">{{ $operator->operator_name }}</h3>
                        <div class="text-sm text-slate-600">NIK: {{ $operator->nik_karyawan ?? '—' }}</div>
                        <div class="text-sm text-slate-600">Gender: {{ $operator->gender ?? '—' }}</div>
                        <div class="text-sm text-slate-600">Role: {{ $operator->role ?? '—' }}</div>
                        <div class="text-sm text-slate-600">Status PKWTT: {{ $operator->statusPkwtt?->pkwtt ?? '—' }}
                        </div>
                        <div class="text-sm text-slate-600">Educational Level:
                            {{ $operator->educationalLevel?->level ?? '—' }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Personal & Employment Details --}}
            <form action="{{ route('operators.update-details', $operator) }}" method="POST"
                class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                @csrf
                @method('PUT')
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-semibold text-slate-900">Personal & Employment Details</h3>
                    <button type="submit"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        Save Changes
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                        <label for="start_date" class="text-xs font-medium text-slate-500 uppercase tracking-wide">Start
                            Date</label>
                        <input type="date" id="start_date" name="start_date"
                            value="{{ $operator->start_date?->format('Y-m-d') ?? '' }}"
                            class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition" />
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                        <label for="date_of_birth"
                            class="text-xs font-medium text-slate-500 uppercase tracking-wide">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth"
                            value="{{ $operator->date_of_birth?->format('Y-m-d') ?? '' }}"
                            class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition" />
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                        <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Working Age</div>
                        <div class="mt-1 text-sm font-semibold text-slate-900">{{ $operator->working_age ?? '—' }}</div>
                        <div class="text-xs text-slate-400 mt-0.5">DOB → Start Date</div>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                        <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Age</div>
                        <div class="mt-1 text-sm font-semibold text-slate-900">{{ $operator->age ?? '—' }}</div>
                        <div class="text-xs text-slate-400 mt-0.5">DOB → Today</div>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                        <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Age (Year)</div>
                        <div class="mt-1 text-sm font-semibold text-slate-900">
                            {{ $operator->age_year !== null ? $operator->age_year . ' years' : '—' }}
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                        <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Years of Service</div>
                        <div class="mt-1 text-sm font-semibold text-slate-900">{{ $operator->years_of_service ?? '—' }}
                        </div>
                        <div class="text-xs text-slate-400 mt-0.5">Remaining until retirement (DOB + 59yr 20d)</div>
                    </div>
                </div>
            </form>

            {{-- Organization --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-xl font-semibold text-slate-900 mb-4">Organization</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                        <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Factory</div>
                        <div class="mt-1 text-sm font-semibold text-slate-900">
                            {{ $operator->factory?->factory_name ?? $latestReport?->factory?->factory_name ?? '—' }}
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                        <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Department</div>
                        <div class="mt-1 text-sm font-semibold text-slate-900">
                            {{ $operator->department?->department_name ?? $latestReport?->department?->department_name ?? '—' }}
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                        <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Division</div>
                        <div class="mt-1 text-sm font-semibold text-slate-900">
                            {{ $operator->division?->division ?? '—' }}
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                        <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Section</div>
                        <div class="mt-1 text-sm font-semibold text-slate-900">{{ $operator->section?->section ?? '—' }}
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                        <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Line</div>
                        <div class="mt-1 text-sm font-semibold text-slate-900">
                            {{ $operator->productionLine?->line_name ?? $latestReport?->productionLine?->line_name ?? '—' }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-xl font-semibold text-slate-900">History</h3>
                <div class="mt-5 overflow-hidden rounded-xl border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm text-slate-700">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Articles</th>
                                <th class="px-4 py-3 font-semibold">Process</th>
                                <th class="px-4 py-3 font-semibold">Process Version</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @forelse ($operator->ptmsReports as $report)
                                <tr>
                                    <td class="px-4 py-3">
                                        @if ($report->article)
                                            <button type="button" class="font-medium text-indigo-600 hover:text-indigo-800"
                                                onclick="const details = [
                                                                                                    'Article: {{ addslashes($report->article->article_name) }}',
                                                                                                    'Destination: {{ addslashes($report->article->destination ?? '') }}',
                                                                                                    'Label code: {{ addslashes($report->article->label_number ?? '') }}',
                                                                                                    'Label + Quty: {{ addslashes($report->article->label_number_quty ?? '') }}',
                                                                                                    'Description: {{ addslashes($report->article->description ?? '') }}'
                                                                                                ].join('\n'); alert(details);">
                                                {{ $report->article->article_name }}
                                            </button>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($report->processVersion?->process)
                                            <button type="button" class="font-medium text-indigo-600 hover:text-indigo-800"
                                                onclick="const details = [
                                                                                                    'Process: {{ addslashes($report->processVersion->process->process_name) }}',
                                                                                                    'Description: {{ addslashes($report->processVersion->process->description ?? '') }}',
                                                                                                    'GSD Elements: {{ addslashes($report->processVersion->gsdElements->map(fn($element) => $element->code . ' - ' . $element->element_name . ' (' . ($element->gsdCategory?->category_name ?? 'Uncategorized') . ')')->join(', ') ?: '—') }}'
                                                                                                ].join('\n'); alert(details);">
                                                {{ $report->processVersion->process->process_name }}
                                            </button>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ $report->processVersion ? 'V' . $report->processVersion->version_number : '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-8 text-center text-slate-500">No history records found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        const searchInput = document.getElementById('profile-search-input');
        const searchDropdown = document.getElementById('profile-search-dropdown');
        let searchTimer;

        searchInput.addEventListener('focus', function () {
            this.select();
        });

        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            const q = this.value.trim();
            if (q.length < 1) {
                searchDropdown.classList.add('hidden');
                searchDropdown.innerHTML = '';
                return;
            }
            searchTimer = setTimeout(() => {
                fetch('{{ url("master-data/operators/search") }}?q=' + encodeURIComponent(q))
                    .then(r => r.json())
                    .then(results => {
                        if (!results.length) {
                            searchDropdown.innerHTML = '<div class="px-3 py-2 text-sm text-slate-500">No employees found.</div>';
                            searchDropdown.classList.remove('hidden');
                            return;
                        }
                        searchDropdown.innerHTML = results.map(r => {
                            const isActive = r.id === {{ $operator->id }};
                            return `<a href="/operators/${r.id}"
                                class="block px-3 py-2 text-sm hover:bg-indigo-50 ${isActive ? 'bg-indigo-50 border-l-2 border-indigo-500' : ''}">
                                <div class="font-medium text-slate-800">${r.label}</div>
                                ${r.description ? `<div class="text-xs text-slate-500 truncate">${r.description}</div>` : ''}
                            </a>`;
                        }).join('');
                        searchDropdown.classList.remove('hidden');
                    });
            }, 250);
        });

        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const firstLink = searchDropdown.querySelector('a');
                if (firstLink) firstLink.click();
            }
        });

        document.addEventListener('click', function (e) {
            if (!document.getElementById('profile-search-wrapper').contains(e.target)) {
                searchDropdown.classList.add('hidden');
            }
        });
    </script>
</x-app-layout>