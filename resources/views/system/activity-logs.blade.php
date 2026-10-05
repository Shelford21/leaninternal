<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-lg text-slate-800 dark:text-slate-200 leading-tight">
            System / Activity Logs
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

            <div class="flex items-center justify-between">
                <p class="text-sm text-slate-500 dark:text-slate-400">Showing {{ $logs->total() }} activity log entries.
                </p>
                <form action="{{ route('system.activity-logs.clear') }}" method="POST"
                    onsubmit="return confirm('Are you sure you want to clear ALL activity logs? This action cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl border border-red-200 dark:border-red-700 px-4 py-2 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Clear All Logs
                    </button>
                </form>
            </div>

            <div
                class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm">
                <table
                    class="min-w-full divide-y divide-slate-200 dark:divide-slate-700 text-left text-sm text-slate-700 dark:text-slate-300">
                    <thead class="bg-slate-50 dark:bg-slate-700/50">
                        <tr>
                            <th class="px-4 py-3 font-semibold">No</th>
                            <th class="px-4 py-3 font-semibold">Username</th>
                            <th class="px-4 py-3 font-semibold">Activity</th>
                            <th class="px-4 py-3 font-semibold">Module</th>
                            <th class="px-4 py-3 font-semibold">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        @forelse ($logs as $index => $log)
                            <tr>
                                <td class="px-4 py-3">{{ $index + 1 }}</td>
                                <td class="px-4 py-3 font-medium text-slate-900 dark:text-slate-100">{{ $log->username }}
                                </td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-400">{{ $log->activity }}</td>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $log->module ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400">
                                    {{ $log->created_at->format('Y-m-d H:i:s') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">No activity
                                    logs found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>