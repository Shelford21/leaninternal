<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-lg text-slate-800 dark:text-slate-200 leading-tight">
            System / Clear Cache
        </h2>
    </x-slot>

    <div class="py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto space-y-6">
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

            <div
                class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-2">Cache Management</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
                    Clear various Laravel caches. This is useful after deploying code changes or modifying configuration
                    files.
                </p>

                <form action="{{ route('system.clear-cache.execute') }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label
                            class="flex items-center gap-3 rounded-xl border border-slate-200 dark:border-slate-600 p-4 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                            <input type="checkbox" name="caches[]" value="config" checked
                                class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500" />
                            <div>
                                <div class="text-sm font-medium text-slate-900 dark:text-slate-100">Config</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">config:clear</div>
                            </div>
                        </label>
                        <label
                            class="flex items-center gap-3 rounded-xl border border-slate-200 dark:border-slate-600 p-4 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                            <input type="checkbox" name="caches[]" value="route" checked
                                class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500" />
                            <div>
                                <div class="text-sm font-medium text-slate-900 dark:text-slate-100">Routes</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">route:clear</div>
                            </div>
                        </label>
                        <label
                            class="flex items-center gap-3 rounded-xl border border-slate-200 dark:border-slate-600 p-4 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                            <input type="checkbox" name="caches[]" value="view" checked
                                class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500" />
                            <div>
                                <div class="text-sm font-medium text-slate-900 dark:text-slate-100">Views</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">view:clear</div>
                            </div>
                        </label>
                        <label
                            class="flex items-center gap-3 rounded-xl border border-slate-200 dark:border-slate-600 p-4 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                            <input type="checkbox" name="caches[]" value="cache"
                                class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500" />
                            <div>
                                <div class="text-sm font-medium text-slate-900 dark:text-slate-100">Application Cache
                                </div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">cache:clear</div>
                            </div>
                        </label>
                        <label
                            class="flex items-center gap-3 rounded-xl border border-slate-200 dark:border-slate-600 p-4 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                            <input type="checkbox" name="caches[]" value="compiled"
                                class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500" />
                            <div>
                                <div class="text-sm font-medium text-slate-900 dark:text-slate-100">Compiled Classes
                                </div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">clear-compiled</div>
                            </div>
                        </label>
                        <label
                            class="flex items-center gap-3 rounded-xl border border-slate-200 dark:border-slate-600 p-4 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                            <input type="checkbox" name="caches[]" value="event"
                                class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500" />
                            <div>
                                <div class="text-sm font-medium text-slate-900 dark:text-slate-100">Events</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">event:clear</div>
                            </div>
                        </label>
                    </div>

                    <div class="pt-4">
                        <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-6 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-red-500 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Clear Selected Caches
                        </button>
                    </div>
                </form>
            </div>

            {{-- Results --}}
            @if (session('results'))
                <div
                    class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm p-6">
                    <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-100 mb-3">Results</h4>
                    <div class="space-y-2">
                        @foreach (session('results') as $result)
                            <div class="flex items-center gap-2 text-sm">
                                @if ($result['success'])
                                    <span class="text-emerald-500">✓</span>
                                @else
                                    <span class="text-red-500">✕</span>
                                @endif
                                <span class="font-medium text-slate-700 dark:text-slate-300">{{ $result['command'] }}</span>
                                <span class="text-slate-500 dark:text-slate-400">— {{ $result['message'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>