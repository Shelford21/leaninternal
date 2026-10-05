<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-lg text-slate-800 dark:text-slate-200 leading-tight">
            System / Speed Test
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

            <div
                class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-2">Database Speed Test</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
                    Test database connection and query performance. This runs a simple query and measures response time.
                </p>

                <form action="{{ route('system.speed-test.execute') }}" method="POST" class="space-y-4">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-indigo-500 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Run Speed Test
                    </button>
                </form>
            </div>

            {{-- Results --}}
            @if (session('speedResults'))
                @php $r = session('speedResults'); @endphp
                <div
                    class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm p-6">
                    <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-100 mb-4">Results</h4>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                        <div class="rounded-xl bg-slate-50 dark:bg-slate-700/50 p-4 text-center">
                            <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ $r['db_time'] }}ms</div>
                            <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">DB Connection</div>
                        </div>
                        <div class="rounded-xl bg-slate-50 dark:bg-slate-700/50 p-4 text-center">
                            <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ $r['query_time'] }}ms
                            </div>
                            <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Query Time</div>
                        </div>
                        <div class="rounded-xl bg-slate-50 dark:bg-slate-700/50 p-4 text-center">
                            <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ $r['total_time'] }}ms
                            </div>
                            <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Total Time</div>
                        </div>
                    </div>

                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-700">
                            <span class="text-slate-500 dark:text-slate-400">Database</span>
                            <span class="font-medium text-slate-900 dark:text-slate-100">{{ $r['database'] }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-700">
                            <span class="text-slate-500 dark:text-slate-400">Driver</span>
                            <span class="font-medium text-slate-900 dark:text-slate-100">{{ $r['driver'] }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-700">
                            <span class="text-slate-500 dark:text-slate-400">Laravel Version</span>
                            <span class="font-medium text-slate-900 dark:text-slate-100">{{ $r['laravel_version'] }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-700">
                            <span class="text-slate-500 dark:text-slate-400">PHP Version</span>
                            <span class="font-medium text-slate-900 dark:text-slate-100">{{ $r['php_version'] }}</span>
                        </div>
                        <div class="flex justify-between py-2">
                            <span class="text-slate-500 dark:text-slate-400">Server Time</span>
                            <span class="font-medium text-slate-900 dark:text-slate-100">{{ $r['server_time'] }}</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>