<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-lg text-slate-800 leading-tight">
            {{ __('Home') }}
        </h2>
    </x-slot>

    <div class="py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto space-y-6">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                @php
                    $cards = [
                        ['label' => 'Articles', 'value' => $stats['articles'] ?? 0, 'accent' => 'indigo'],
                        ['label' => 'Employees', 'value' => $stats['operators'] ?? 0, 'accent' => 'cyan'],
                        ['label' => 'Processes', 'value' => $stats['processes'] ?? 0, 'accent' => 'emerald'],
                        ['label' => 'Process Versions', 'value' => $stats['process_versions'] ?? 0, 'accent' => 'amber'],
                        ['label' => 'GSD Elements', 'value' => $stats['gsd_elements'] ?? 0, 'accent' => 'violet'],
                    ];
                @endphp

                @foreach ($cards as $card)
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-slate-500">{{ $card['label'] }}</p>
                            <span class="inline-flex h-2.5 w-2.5 rounded-full bg-{{ $card['accent'] }}-500"></span>
                        </div>
                        <p class="mt-4 text-3xl font-bold text-slate-900">{{ $card['value'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="grid gap-6 lg:grid-cols-[1.5fr_1fr]">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="text-lg font-semibold text-slate-900">Operational Snapshot</h3>
                        <span
                            class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700">Live</span>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="rounded-xl bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-[0.18em] text-slate-500">GSD Categories</p>
                            <p class="mt-3 text-2xl font-bold text-slate-900">{{ $stats['gsd_categories'] ?? 0 }}</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Master Data</p>
                            <p class="mt-3 text-2xl font-bold text-slate-900">
                                {{ ($stats['articles'] ?? 0) + ($stats['operators'] ?? 0) + ($stats['processes'] ?? 0) }}
                            </p>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Role</p>
                            <p class="mt-3 text-2xl font-bold text-slate-900">
                                {{ ucfirst(auth()->user()->role->role_name) }}
                            </p>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Access</p>
                            <p class="mt-3 text-2xl font-bold text-slate-900">
                                {{ auth()->user()->role->role_name === 'viewer' ? 'Read Only' : 'Operational' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">Data Masters</h3>
                    <p class="mt-1 text-sm text-slate-500">Quick Actions</p>
                    <div class="mt-5 space-y-3">
                        <a href="{{ route('master-data.processes') }}"
                            class="block rounded-xl border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm font-medium text-indigo-700 hover:bg-indigo-100">Process</a>
                        <a href="{{ route('master-data.operators') }}"
                            class="block rounded-xl border border-cyan-100 bg-cyan-50 px-4 py-3 text-sm font-medium text-cyan-700 hover:bg-cyan-100">Employees</a>
                        <a href="{{ route('master-data.articles') }}"
                            class="block rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 hover:bg-emerald-100">Articles</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>