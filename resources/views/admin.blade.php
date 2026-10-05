<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-lg text-slate-800 leading-tight">
            {{ __('Admin Panel') }}
        </h2>
    </x-slot>

    <div class="py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/60 p-6">
                <p class="text-slate-600">{{ __("Hi Admin") }}</p>
            </div>
        </div>
    </div>
</x-app-layout>