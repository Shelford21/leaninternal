<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data
    x-init="if(localStorage.getItem('theme')==='dark'||(!localStorage.getItem('theme')&&window.matchMedia('(prefers-color-scheme:dark)').matches))$el.classList.add('dark')">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'LIMS') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap"
        rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        /* Sidebar scrollbar */
        .sidebar-scroll::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 4px;
        }

        .sidebar-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.25);
        }

        /* Smooth transitions */
        .sidebar-transition {
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .menu-text {
            transition: opacity 0.2s ease, max-width 0.3s ease;
        }

        .sidebar-collapsed .menu-text {
            opacity: 0;
            max-width: 0;
            overflow: hidden;
            white-space: nowrap;
        }

        .sidebar-collapsed .menu-label {
            display: none;
        }

        /* Active indicator */
        .nav-item-active {
            position: relative;
        }

        .nav-item-active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 60%;
            background: linear-gradient(180deg, #818cf8, #6366f1);
            border-radius: 0 4px 4px 0;
        }
    </style>
</head>

<body class="font-sans antialiased bg-slate-50 dark:bg-slate-900">

    <div class="flex min-h-screen" x-data="{ sidebarCollapsed: false, mobileOpen: false }">

        {{-- ==================== SIDEBAR ==================== --}}
        <aside :class="sidebarCollapsed ? 'w-[72px] sidebar-collapsed' : 'w-[260px]'" class="sidebar-transition hidden lg:flex flex-col fixed inset-y-0 left-0 z-40
                   bg-gradient-to-b from-slate-900 via-slate-900 to-slate-950">

            {{-- Sidebar Header --}}
            <div class="flex items-center h-16 px-4 border-b border-white/[0.06] flex-shrink-0">
                <div class="flex items-center gap-3 overflow-hidden">
                    {{-- Logo --}}
                    <div
                        class="flex-shrink-0 w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div class="menu-text">
                        <div class="text-sm font-bold text-white tracking-wide">LIMS</div>
                        <div class="text-[10px] text-slate-400 font-medium tracking-wider uppercase">Lean Management
                        </div>
                    </div>
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 overflow-y-auto sidebar-scroll py-4 px-3 space-y-1">

                {{-- Section: Main --}}
                <div class="mb-2">
                    <div class="menu-label px-3 mb-2">
                        <span class="text-[10px] font-semibold uppercase tracking-widest text-slate-500">Main</span>
                    </div>

                    <a href="{{ route('home') }}" title="Home" class="nav-item relative flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200
                        {{ request()->routeIs('home', 'developer', 'admin', 'viewer')
    ? 'bg-indigo-500/10 text-indigo-300 nav-item-active'
    : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.04]' }}">
                        <div class="flex-shrink-0 w-5 h-5 flex items-center justify-center">
                            <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                        </div>
                        <span class="menu-text">{{ __('master-data.dashboard') }}</span>
                    </a>
                </div>

                <div class="pt-3 mb-2">
                    <div class="menu-label px-3 mb-2">
                        <span class="text-[10px] font-semibold uppercase tracking-widest text-slate-500">Data
                            Masters</span>
                    </div>

                    @php
                        $dataMasterLinks = [
                            ['label' => 'Processes', 'route' => 'master-data.processes'],
                            ['label' => 'Employees', 'route' => 'master-data.operators'],
                            ['label' => 'Articles', 'route' => 'master-data.articles'],
                            ['label' => 'GSD Elements', 'route' => 'master-data.gsd-elements'],
                            ['label' => 'Factories', 'route' => 'master-data.factories'],
                            ['label' => 'Departments', 'route' => 'master-data.departments'],
                            ['label' => 'Destinations', 'route' => 'master-data.destinations'],
                            ['label' => 'Production Lines', 'route' => 'master-data.production-lines'],
                            ['label' => 'Skill Gradings', 'route' => 'master-data.skill-gradings'],
                            ['label' => 'Divisions', 'route' => 'master-data.divisions'],
                            ['label' => 'Sections', 'route' => 'master-data.sections'],
                            ['label' => 'Machine Types', 'route' => 'master-data.machine-types'],
                            ['label' => 'Components/Panels', 'route' => 'master-data.components-panels'],
                            ['label' => 'Machine Numbers', 'route' => 'master-data.machine-numbers'],
                            ['label' => 'Shifts', 'route' => 'master-data.shifts'],
                            ['label' => 'Failure Modes', 'route' => 'master-data.failure-modes'],
                            ['label' => 'Mechanics', 'route' => 'master-data.mechanics'],
                            ['label' => 'Spare Parts', 'route' => 'master-data.spare-parts'],
                            ['label' => 'Genders', 'route' => 'master-data.genders'],
                            ['label' => 'Production Roles', 'route' => 'master-data.production-roles'],
                            ['label' => 'Educational Level', 'route' => 'master-data.educational-levels'],
                            ['label' => 'Status PKWTT', 'route' => 'master-data.status-pkwtt'],
                        ];
                        $isDataMasterActive = collect($dataMasterLinks)->contains(fn($l) => request()->routeIs($l['route']));
                    @endphp

                    {{-- Data Masters Dropdown --}}
                    <div x-data="{ open: {{ $isDataMasterActive ? 'true' : 'false' }} }">
                        <button @click="open = !open" title="Data Masters"
                            class="w-full nav-item relative flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200
                                    {{ $isDataMasterActive ? 'bg-indigo-500/10 text-indigo-300 nav-item-active' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.04]' }}">
                            <div class="flex-shrink-0 w-5 h-5 flex items-center justify-center">
                                <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </div>
                            <span class="menu-text flex-1 text-left">Data Masters</span>
                            <svg class="w-4 h-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="open" x-transition class="mt-1 ml-4 space-y-0.5 border-l border-white/[0.06] pl-3">
                            @foreach ($dataMasterLinks as $link)
                                <a href="{{ route($link['route']) }}" title="{{ $link['label'] }}"
                                    class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200
                                                                           {{ request()->routeIs($link['route']) ? 'text-indigo-300 bg-indigo-500/10' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.04]' }}">
                                    {{ $link['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Section: Lean Operations --}}
                <div class="pt-3 mb-2">
                    <div class="menu-label px-3 mb-2">
                        <span class="text-[10px] font-semibold uppercase tracking-widest text-slate-500">Lean
                            Operations</span>
                    </div>
                    @php
                        $operationLinks = [
                            ['label' => 'Operational Breakdown', 'route' => 'operations.breakdown', 'icon' => '↯'],
                            ['label' => 'Line Balancing', 'route' => 'operations.line.balancing.index', 'icon' => '⇄'],
                            ['label' => 'Kaizen', 'route' => 'operations.kaizen', 'icon' => '✦'],
                            ['label' => 'Skills & OSCP', 'route' => 'operations.skills', 'icon' => '◎'],
                            ['label' => 'TPM', 'route' => 'operations.tpm', 'icon' => '⚙'],
                            ['label' => 'VSM', 'route' => 'operations.vsm', 'icon' => '→'],
                            ['label' => 'Employees Profile', 'route' => 'employee-profile', 'icon' => '👤'],
                        ];
                    @endphp
                    @foreach ($operationLinks as $link)
                        <a href="{{ route($link['route']) }}" title="{{ $link['label'] }}"
                            class="nav-item relative flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs($link['route']) ? 'bg-teal-500/10 text-teal-300 nav-item-active' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.04]' }}">
                            <span class="flex-shrink-0 w-5 text-center text-base">{{ $link['icon'] }}</span>
                            <span class="menu-text">{{ $link['label'] }}</span>
                        </a>
                    @endforeach
                </div>

                {{-- Section: Management --}}
                @if (in_array(auth()->user()->role->role_name, ['developer', 'admin']))
                            <div class="pt-3 mb-2">
                                <div class="menu-label px-3 mb-2">
                                    <span
                                        class="text-[10px] font-semibold uppercase tracking-widest text-slate-500">Management</span>
                                </div>

                                {{-- Credentials --}}
                                <a href="{{ route('credentials.index') }}" title="Credentials" class="nav-item relative flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200
                                                                                                                                                                                                {{ request()->routeIs('credentials.*')
                    ? 'bg-indigo-500/10 text-indigo-300 nav-item-active'
                    : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.04]' }}">
                                    <div class="flex-shrink-0 w-5 h-5 flex items-center justify-center">
                                        <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                        </svg>
                                    </div>
                                    <span class="menu-text">Credentials</span>
                                </a>

                                {{-- Clear Cache (Developer only) --}}
                                @if (auth()->user()->role->role_name === 'developer')
                                    <a href="{{ route('system.clear-cache') }}" title="Clear Cache"
                                        class="nav-item relative flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200
                                                                                                                                                                            {{ request()->routeIs('system.clear-cache') ? 'bg-indigo-500/10 text-indigo-300 nav-item-active' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.04]' }}">
                                        <div class="flex-shrink-0 w-5 h-5 flex items-center justify-center">
                                            <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </div>
                                        <span class="menu-text">Clear Cache</span>
                                    </a>

                                    <a href="{{ route('system.speed-test') }}" title="Speed Test"
                                        class="nav-item relative flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200
                                                                                                                                                                            {{ request()->routeIs('system.speed-test') ? 'bg-indigo-500/10 text-indigo-300 nav-item-active' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.04]' }}">
                                        <div class="flex-shrink-0 w-5 h-5 flex items-center justify-center">
                                            <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                        </div>
                                        <span class="menu-text">Speed Test</span>
                                    </a>

                                    <a href="{{ route('hard-delete.index') }}" title="Hard Delete"
                                        class="nav-item relative flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200
                                                                            {{ request()->routeIs('hard-delete.*') ? 'bg-red-500/10 text-red-300 nav-item-active' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.04]' }}">
                                        <div class="flex-shrink-0 w-5 h-5 flex items-center justify-center">
                                            <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </div>
                                        <span class="menu-text">Hard Delete</span>
                                    </a>
                                @endif
                            </div>
                @endif

                {{-- Section: System (Developer only) --}}
                @if (auth()->user()->role->role_name === 'developer')
                            <div class="pt-3 mb-2">
                                <div class="menu-label px-3 mb-2">
                                    <span class="text-[10px] font-semibold uppercase tracking-widest text-slate-500">System</span>
                                </div>

                                {{-- Logs Dropdown --}}
                                <div
                                    x-data="{ open: {{ request()->routeIs('system.login-logs', 'system.activity-logs') ? 'true' : 'false' }} }">
                                    <button @click="open = !open" title="Logs"
                                        class="w-full nav-item relative flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200
                                                                                                                                            {{ request()->routeIs('system.login-logs', 'system.activity-logs') ? 'bg-indigo-500/10 text-indigo-300 nav-item-active' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.04]' }}">
                                        <div class="flex-shrink-0 w-5 h-5 flex items-center justify-center">
                                            <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <span class="menu-text flex-1 text-left">Logs</span>
                                        <svg class="w-4 h-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                    <div x-show="open" x-transition class="mt-1 ml-4 space-y-0.5 border-l border-white/[0.06] pl-3">
                                        <a href="{{ route('system.login-logs') }}" title="Login Logs"
                                            class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200
                                                                                                                                                {{ request()->routeIs('system.login-logs') ? 'text-indigo-300 bg-indigo-500/10' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.04]' }}">
                                            Login Logs
                                        </a>
                                        <a href="{{ route('system.activity-logs') }}" title="Activity Logs"
                                            class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200
                                                                                                                                                {{ request()->routeIs('system.activity-logs') ? 'text-indigo-300 bg-indigo-500/10' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.04]' }}">
                                            Activity Logs
                                        </a>
                                    </div>
                                </div>

                                {{-- Settings --}}
                                <a href="{{ route('profile.edit') }}" title="Settings" class="nav-item relative flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200
                                                                                                                                                                                                {{ request()->routeIs('profile.*')
                    ? 'bg-indigo-500/10 text-indigo-300 nav-item-active'
                    : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.04]' }}">
                                    <div class="flex-shrink-0 w-5 h-5 flex items-center justify-center">
                                        <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    <span class="menu-text">Settings</span>
                                </a>
                            </div>
                @endif
            </nav>

            {{-- Collapse Toggle --}}
            <div class="px-3 py-2 border-t border-white/[0.06]">
                <button @click="sidebarCollapsed = !sidebarCollapsed"
                    class="flex items-center justify-center w-full gap-3 px-3 py-2 rounded-lg text-slate-500 hover:text-slate-300 hover:bg-white/[0.04] transition-all duration-200 text-sm">
                    <div class="flex-shrink-0 w-5 h-5 flex items-center justify-center">
                        <svg class="w-[18px] h-[18px] transition-transform duration-300"
                            :class="sidebarCollapsed ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                        </svg>
                    </div>
                    <span class="menu-text">Collapse</span>
                </button>
            </div>

            {{-- User Profile --}}
            <div class="flex-shrink-0 border-t border-white/[0.06] p-3">
                <a href="{{ route('profile.edit') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/[0.04] transition-all duration-200 group">
                    {{-- Avatar --}}
                    <div
                        class="flex-shrink-0 w-9 h-9 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white text-xs font-bold shadow-lg shadow-indigo-500/20">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </div>
                    <div class="menu-text flex-1 min-w-0">
                        <div class="text-sm font-semibold text-slate-200 truncate">{{ auth()->user()->name }}</div>
                        <div class="text-[11px] text-slate-500 font-medium">
                            {{ ucfirst(auth()->user()->role->role_name) }}
                        </div>
                    </div>
                    <div class="menu-text flex-shrink-0">
                        <svg class="w-4 h-4 text-slate-600 group-hover:text-slate-400 transition-colors" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 9l4-4 4 4m0 6l-4 4-4-4" />
                        </svg>
                    </div>
                </a>
            </div>
        </aside>

        {{-- ==================== MOBILE SIDEBAR OVERLAY ==================== --}}
        <div x-show="mobileOpen" x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" @click="mobileOpen = false"
            class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm lg:hidden"></div>

        {{-- ==================== MOBILE SIDEBAR ==================== --}}
        <aside x-show="mobileOpen" x-transition:enter="transition ease-in-out duration-300 transform"
            x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in-out duration-300 transform" x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full" class="fixed inset-y-0 left-0 z-50 w-[260px] flex flex-col lg:hidden
                      bg-gradient-to-b from-slate-900 via-slate-900 to-slate-950">

            {{-- Mobile Header --}}
            <div class="flex items-center justify-between h-16 px-4 border-b border-white/[0.06]">
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-white tracking-wide">LIMS</div>
                        <div class="text-[10px] text-slate-400 font-medium tracking-wider uppercase">Lean Management
                        </div>
                    </div>
                </div>
                <button @click="mobileOpen = false"
                    class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-white/[0.06]">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Mobile Nav (same links) --}}
            <nav class="flex-1 overflow-y-auto sidebar-scroll py-4 px-3 space-y-1">
                <div class="mb-2">
                    <div class="px-3 mb-2">
                        <span class="text-[10px] font-semibold uppercase tracking-widest text-slate-500">Main</span>
                    </div>
                    <a href="{{ route('home') }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200
                        {{ request()->routeIs('home', 'developer', 'admin', 'viewer') ? 'bg-indigo-500/10 text-indigo-300' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.04]' }}">
                        <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>Home</span>
                    </a>
                </div>

                <div class="pt-3 mb-2">
                    <div class="px-3 mb-2">
                        <span class="text-[10px] font-semibold uppercase tracking-widest text-slate-500">Data
                            Masters</span>
                    </div>
                    @php
                        $dataMasterLinks = [
                            ['label' => 'Processes', 'route' => 'master-data.processes'],
                            ['label' => 'Employees', 'route' => 'master-data.operators'],
                            ['label' => 'Articles', 'route' => 'master-data.articles'],
                            ['label' => 'GSD Elements', 'route' => 'master-data.gsd-elements'],
                            ['label' => 'Factories', 'route' => 'master-data.factories'],
                            ['label' => 'Departments', 'route' => 'master-data.departments'],
                            ['label' => 'Destinations', 'route' => 'master-data.destinations'],
                            ['label' => 'Production Lines', 'route' => 'master-data.production-lines'],
                            ['label' => 'Skill Gradings', 'route' => 'master-data.skill-gradings'],
                            ['label' => 'Divisions', 'route' => 'master-data.divisions'],
                            ['label' => 'Sections', 'route' => 'master-data.sections'],
                            ['label' => 'Machine Types', 'route' => 'master-data.machine-types'],
                            ['label' => 'Components/Panels', 'route' => 'master-data.components-panels'],
                            ['label' => 'Machine Numbers', 'route' => 'master-data.machine-numbers'],
                            ['label' => 'Shifts', 'route' => 'master-data.shifts'],
                            ['label' => 'Failure Modes', 'route' => 'master-data.failure-modes'],
                            ['label' => 'Mechanics', 'route' => 'master-data.mechanics'],
                            ['label' => 'Spare Parts', 'route' => 'master-data.spare-parts'],
                            ['label' => 'Genders', 'route' => 'master-data.genders'],
                            ['label' => 'Production Roles', 'route' => 'master-data.production-roles'],
                            ['label' => 'Educational Level', 'route' => 'master-data.educational-levels'],
                            ['label' => 'Status PKWTT', 'route' => 'master-data.status-pkwtt'],
                        ];
                        $isDataMasterActive = collect($dataMasterLinks)->contains(fn($l) => request()->routeIs($l['route']));
                    @endphp
                    <div x-data="{ open: {{ $isDataMasterActive ? 'true' : 'false' }} }">
                        <button @click="open = !open"
                            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200
                                    {{ $isDataMasterActive ? 'bg-indigo-500/10 text-indigo-300' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.04]' }}">
                            <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                            <span class="flex-1 text-left">Data Masters</span>
                            <svg class="w-4 h-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="open" x-transition class="mt-1 ml-4 space-y-0.5 border-l border-white/[0.06] pl-3">
                            @foreach ($dataMasterLinks as $link)
                                <a href="{{ route($link['route']) }}"
                                    class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200
                                                                           {{ request()->routeIs($link['route']) ? 'text-indigo-300 bg-indigo-500/10' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.04]' }}">
                                    {{ $link['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                @if (in_array(auth()->user()->role->role_name, ['developer', 'admin']))
                    <div class="pt-3 mb-2">
                        <div class="px-3 mb-2">
                            <span
                                class="text-[10px] font-semibold uppercase tracking-widest text-slate-500">Management</span>
                        </div>
                        <a href="{{ route('credentials.index') }}"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200
                                                                                {{ request()->routeIs('credentials.*') ? 'bg-indigo-500/10 text-indigo-300' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.04]' }}">
                            <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                            </svg>
                            <span>Credentials</span>
                        </a>
                        @if (auth()->user()->role->role_name === 'developer')
                            <a href="{{ route('system.clear-cache') }}"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200
                                                                                                    {{ request()->routeIs('system.clear-cache') ? 'bg-indigo-500/10 text-indigo-300' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.04]' }}">
                                <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                <span>Clear Cache</span>
                            </a>
                            <a href="{{ route('system.speed-test') }}"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200
                                                                                                    {{ request()->routeIs('system.speed-test') ? 'bg-indigo-500/10 text-indigo-300' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.04]' }}">
                                <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                <span>Speed Test</span>
                            </a>
                            <a href="{{ route('hard-delete.index') }}"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200
                                                    {{ request()->routeIs('hard-delete.*') ? 'bg-red-500/10 text-red-300' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.04]' }}">
                                <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                <span>Hard Delete</span>
                            </a>
                        @endif
                    </div>
                @endif

                <div class="pt-3 mb-2">
                    <div class="px-3 mb-2"><span
                            class="text-[10px] font-semibold uppercase tracking-widest text-slate-500">Lean
                            Operations</span></div>
                    @php
                        $mobileOperationLinks = [
                            ['label' => 'Operational Breakdown', 'route' => 'operations.breakdown', 'icon' => '↯'],
                            ['label' => 'Line Balancing', 'route' => 'operations.line.balancing.index', 'icon' => '⇄'],
                            ['label' => 'Kaizen', 'route' => 'operations.kaizen', 'icon' => '✦'],
                            ['label' => 'Skills & OSCP', 'route' => 'operations.skills', 'icon' => '◎'],
                            ['label' => 'TPM', 'route' => 'operations.tpm', 'icon' => '⚙'],
                            ['label' => 'VSM', 'route' => 'operations.vsm', 'icon' => '→'],
                            ['label' => 'Employees Profile', 'route' => 'employee-profile', 'icon' => '👤'],
                        ];
                    @endphp
                    @foreach ($mobileOperationLinks as $link)
                        <a href="{{ route($link['route']) }}"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs($link['route']) ? 'bg-teal-500/10 text-teal-300' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.04]' }}">
                            <span
                                class="w-[18px] text-center text-base">{{ $link['icon'] }}</span><span>{{ $link['label'] }}</span>
                        </a>
                    @endforeach
                </div>

                @if (auth()->user()->role->role_name === 'developer')
                    <div class="pt-3 mb-2">
                        <div class="px-3 mb-2">
                            <span class="text-[10px] font-semibold uppercase tracking-widest text-slate-500">System</span>
                        </div>
                        {{-- Logs Dropdown --}}
                        <div
                            x-data="{ open: {{ request()->routeIs('system.login-logs', 'system.activity-logs') ? 'true' : 'false' }} }">
                            <button @click="open = !open"
                                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200
                                                                    {{ request()->routeIs('system.login-logs', 'system.activity-logs') ? 'bg-indigo-500/10 text-indigo-300' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.04]' }}">
                                <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span class="flex-1 text-left">Logs</span>
                                <svg class="w-4 h-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="open" x-transition class="mt-1 ml-4 space-y-0.5 border-l border-white/[0.06] pl-3">
                                <a href="{{ route('system.login-logs') }}"
                                    class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200
                                                                        {{ request()->routeIs('system.login-logs') ? 'text-indigo-300 bg-indigo-500/10' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.04]' }}">
                                    Login Logs
                                </a>
                                <a href="{{ route('system.activity-logs') }}"
                                    class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200
                                                                        {{ request()->routeIs('system.activity-logs') ? 'text-indigo-300 bg-indigo-500/10' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.04]' }}">
                                    Activity Logs
                                </a>
                            </div>
                        </div>

                        <a href="{{ route('profile.edit') }}"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200
                                                                                {{ request()->routeIs('profile.*') ? 'bg-indigo-500/10 text-indigo-300' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.04]' }}">
                            <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>Settings</span>
                        </a>
                    </div>
                @endif
            </nav>

            {{-- Mobile Language Selector --}}
            <div class="flex-shrink-0 border-t border-white/[0.06] p-3">
                <div class="flex items-center gap-2 px-3">
                    <span class="text-[11px] text-slate-500 font-medium">🌐 {{ __('master-data.language') }}:</span>
                    <form method="POST" action="{{ route('language.switch', 'en') }}" class="inline">
                        @csrf
                        <button type="submit"
                            class="text-xs px-2 py-1 rounded {{ app()->getLocale() === 'en' ? 'bg-indigo-500/20 text-indigo-300 font-semibold' : 'text-slate-400 hover:text-slate-200' }}">
                            EN
                        </button>
                    </form>
                    <form method="POST" action="{{ route('language.switch', 'id') }}" class="inline">
                        @csrf
                        <button type="submit"
                            class="text-xs px-2 py-1 rounded {{ app()->getLocale() === 'id' ? 'bg-indigo-500/20 text-indigo-300 font-semibold' : 'text-slate-400 hover:text-slate-200' }}">
                            ID
                        </button>
                    </form>
                </div>
            </div>

            {{-- Mobile User --}}
            <div class="flex-shrink-0 border-t border-white/[0.06] p-3">
                <a href="{{ route('profile.edit') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/[0.04] transition-all duration-200">
                    <div
                        class="w-9 h-9 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white text-xs font-bold shadow-lg shadow-indigo-500/20">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold text-slate-200 truncate">{{ auth()->user()->name }}</div>
                        <div class="text-[11px] text-slate-500 font-medium">
                            {{ ucfirst(auth()->user()->role->role_name) }}
                        </div>
                    </div>
                </a>
            </div>
        </aside>

        {{-- ==================== MAIN CONTENT ==================== --}}
        <div class="flex-1 flex flex-col min-w-0" :class="sidebarCollapsed ? 'lg:ml-[72px]' : 'lg:ml-[260px]'"
            style="transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);">

            {{-- Top Bar --}}
            <header
                class="sticky top-0 z-30 bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl border-b border-slate-200/60 dark:border-slate-700/60">
                <div class="flex items-center justify-between h-16 px-4 sm:px-6">
                    <div class="flex items-center gap-3">
                        {{-- Mobile menu button --}}
                        <button @click="mobileOpen = true"
                            class="p-2 rounded-lg text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 lg:hidden transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>

                        {{-- Dark/Light toggle --}}
                        <button
                            @click="$root.classList.toggle('dark'); localStorage.setItem('theme', $root.classList.contains('dark') ? 'dark' : 'light')"
                            class="p-2 rounded-lg text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                            title="Toggle dark mode">
                            <svg x-show="!$root.classList.contains('dark')" class="w-5 h-5" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                            <svg x-show="$root.classList.contains('dark')" class="w-5 h-5" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </button>

                        {{-- Page Heading --}}
                        @if (isset($header))
                            {{ $header }}
                        @endif
                    </div>

                    {{-- Right side: User dropdown --}}
                    <div class="flex items-center gap-2" x-data="{ dropdownOpen: false }">
                        {{-- Language selector --}}
                        <div class="relative" x-data="{ langOpen: false }">
                            <button @click="langOpen = !langOpen"
                                class="p-2 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                                title="{{ __('master-data.language') }}">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                </svg>
                            </button>
                            <div x-show="langOpen" @click.away="langOpen = false"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                class="absolute right-0 mt-2 w-40 bg-white dark:bg-slate-800 rounded-xl shadow-xl shadow-slate-200/50 dark:shadow-slate-900/50 border border-slate-200/60 dark:border-slate-700/60 py-1 z-50">
                                <form method="POST" action="{{ route('language.switch', 'en') }}">
                                    @csrf
                                    <button type="submit"
                                        class="w-full text-left px-4 py-2 text-sm {{ app()->getLocale() === 'en' ? 'text-indigo-600 dark:text-indigo-400 font-semibold bg-indigo-50 dark:bg-indigo-900/20' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700' }}">
                                        🇺🇸 {{ __('master-data.english') }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('language.switch', 'id') }}">
                                    @csrf
                                    <button type="submit"
                                        class="w-full text-left px-4 py-2 text-sm {{ app()->getLocale() === 'id' ? 'text-indigo-600 dark:text-indigo-400 font-semibold bg-indigo-50 dark:bg-indigo-900/20' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700' }}">
                                        🇮🇩 {{ __('master-data.indonesian') }}
                                    </button>
                                </form>
                            </div>
                        </div>

                        {{-- Notifications placeholder --}}
                        <button
                            class="p-2 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors relative">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-indigo-500 rounded-full"></span>
                        </button>

                        {{-- User dropdown --}}
                        <div class="relative">
                            <button @click="dropdownOpen = !dropdownOpen"
                                class="flex items-center gap-2 p-1.5 pr-3 rounded-full hover:bg-slate-100 transition-colors">
                                <div
                                    class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white text-xs font-bold">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                                </div>
                                <span <span
                                    class="text-sm font-medium text-slate-700 dark:text-slate-300 hidden sm:block">{{ auth()->user()->name }}</span>
                                <svg class="w-4 h-4 text-slate-400 hidden sm:block" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            {{-- Dropdown menu --}}
                            <div x-show="dropdownOpen" @click.away="dropdownOpen = false"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95" <div
                                class="absolute right-0 mt-2 w-56 bg-white dark:bg-slate-800 rounded-xl shadow-xl shadow-slate-200/50 dark:shadow-slate-900/50 border border-slate-200/60 dark:border-slate-700/60 py-2 z-50">

                                <div class="px-4 py-2 border-b border-slate-100">
                                    <div class="text-sm font-semibold text-slate-800 dark:text-slate-200">
                                        {{ auth()->user()->name }}
                                    </div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ ucfirst(auth()->user()->role->role_name) }}
                                    </div>
                                </div>

                                <a href="{{ route('profile.edit') }}"
                                    class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-slate-100 transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    Profile
                                </a>

                                <div class="border-t border-slate-100 mt-1 pt-1">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit"
                                            class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                            </svg>
                                            Log Out
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Page Content --}}
            <main class="flex-1">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>

</html>