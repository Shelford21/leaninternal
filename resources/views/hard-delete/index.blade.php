<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-lg text-slate-800 dark:text-slate-200 leading-tight">
            Management / Hard Delete
        </h2>
    </x-slot>

    <div class="py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto space-y-6">
            {{-- Warning Banner --}}
            <div class="rounded-xl border border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/20 px-5 py-4">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                    <div>
                        <h3 class="text-sm font-semibold text-red-800 dark:text-red-300">Danger Zone — Hard Delete</h3>
                        <p class="text-sm text-red-700 dark:text-red-400 mt-1">
                            This page permanently removes records from the database. This action <strong>cannot be
                                undone</strong>.
                            Only inactive (soft-deleted) records are shown below. This page is accessible only to the
                            Developer role.
                        </p>
                    </div>
                </div>
            </div>

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

            {{-- Data Masters Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($masters as $master)
                    <div
                        class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $master['label'] }}
                                </h3>
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                        {{ $master['count'] > 0 ? 'bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 ring-1 ring-inset ring-red-600/20' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400' }}">
                                    {{ $master['count'] }} inactive
                                </span>
                            </div>
                        </div>
                        <div class="px-5 py-4">
                            @if ($master['count'] > 0)
                                <form action="{{ route('hard-delete.destroy') }}" method="POST"
                                    onsubmit="return confirm('Are you sure you want to permanently delete ALL {{ $master['count'] }} inactive {{ strtolower($master['label']) }} records? This cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="master_key" value="{{ $master['key'] }}" />
                                    <button type="submit"
                                        class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-slate-800">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Delete {{ $master['count'] }} Record(s)
                                    </button>
                                </form>
                            @else
                                <p class="text-sm text-slate-500 dark:text-slate-400 text-center py-2">No inactive records to
                                    delete.</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>