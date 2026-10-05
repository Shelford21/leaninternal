<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <span class="text-teal-600">Lean Operations</span>
            <span class="text-slate-300">/</span>
            <h2 class="font-semibold text-lg text-slate-800 leading-tight">
                {{ $module === 'overview' ? 'Operations Overview' : ucwords(str_replace('-', ' ', $module)) }}</h2>
        </div>
    </x-slot>

    @php
        $modules = [
            'cycle-time' => ['title' => 'Cycle Time Study', 'description' => 'Record observations and calculate average, range, and variation per operation.', 'accent' => 'cyan', 'symbol' => '◷'],
            'breakdown' => ['title' => 'Operational Breakdown', 'description' => 'Record hidden losses by activity and make the biggest causes visible.', 'accent' => 'amber', 'symbol' => '↯'],
            'line-balancing' => ['title' => 'Line Balancing', 'description' => 'Compare station cycle time with takt time and identify bottlenecks.', 'accent' => 'emerald', 'symbol' => '⇄'],
            'kaizen' => ['title' => 'Kaizen Board', 'description' => 'Create improvement proposals, assign owners, and move actions to completed.', 'accent' => 'rose', 'symbol' => '✦'],
            'skills' => ['title' => 'Skills & OSCP', 'description' => 'Review operator skill coverage and identify training gaps.', 'accent' => 'violet', 'symbol' => '◎'],
            'tpm' => ['title' => 'TPM Maintenance', 'description' => 'Track equipment readiness and preventive maintenance due dates.', 'accent' => 'orange', 'symbol' => '⚙'],
            'materials' => ['title' => 'Material Database', 'description' => 'Link material masters to the articles and operations that use them.', 'accent' => 'blue', 'symbol' => '▤'],
            'vsm' => ['title' => 'Value Stream Map', 'description' => 'Lay out the current process flow and prepare it for lead-time analysis.', 'accent' => 'teal', 'symbol' => '→'],
        ];
    @endphp

    <div class="min-h-[calc(100vh-7rem)] bg-slate-50 px-4 py-8 sm:px-6 lg:px-8"
        x-data="leanOperations('{{ $module }}')">
        <div class="mx-auto max-w-7xl space-y-6">
            <div class="relative overflow-hidden rounded-2xl bg-slate-950 p-6 text-white shadow-xl sm:p-8">
                <div class="relative z-10 max-w-3xl">
                    <div
                        class="mb-3 flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-teal-300">
                        <span class="text-lg">✦</span> Work plan module</div>
                    <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl" x-text="pageTitle"></h1>
                    <p class="mt-3 text-sm leading-6 text-slate-300" x-text="pageDescription"></p>
                </div>
                <div class="absolute -right-16 -top-28 h-80 w-80 rounded-full bg-teal-500/20 blur-3xl"></div>
            </div>

            @if ($module === 'overview')
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($modules as $key => $item)
                        <a href="{{ route('operations.' . str_replace('-', '.', $key)) }}"
                            class="group rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-teal-300 hover:shadow-md">
                            <div class="flex items-start justify-between"><span
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-xl text-slate-700 group-hover:bg-teal-50 group-hover:text-teal-700">{{ $item['symbol'] }}</span><span
                                    class="text-xs font-semibold uppercase tracking-wider text-slate-400">Open →</span></div>
                            <h2 class="mt-5 font-semibold text-slate-950">{{ $item['title'] }}</h2>
                            <p class="mt-2 text-sm leading-5 text-slate-500">{{ $item['description'] }}</p>
                        </a>
                    @endforeach
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Recommended workflow</p>
                    <div class="mt-5 grid gap-4 md:grid-cols-4">
                        <div><span class="text-sm font-semibold text-slate-900">01 Observe</span>
                            <p class="mt-1 text-xs text-slate-500">Capture cycle time and skill context.</p>
                        </div>
                        <div><span class="text-sm font-semibold text-slate-900">02 Diagnose</span>
                            <p class="mt-1 text-xs text-slate-500">Log breakdown and flow losses.</p>
                        </div>
                        <div><span class="text-sm font-semibold text-slate-900">03 Improve</span>
                            <p class="mt-1 text-xs text-slate-500">Balance work and create Kaizen.</p>
                        </div>
                        <div><span class="text-sm font-semibold text-slate-900">04 Sustain</span>
                            <p class="mt-1 text-xs text-slate-500">Maintain equipment and skills.</p>
                        </div>
                    </div>
                </div>
            @elseif ($module === 'cycle-time')
                <div class="grid gap-6 lg:grid-cols-[320px_1fr]">
                    <div class="rounded-xl border border-slate-200 bg-white p-5">
                        <h2 class="font-semibold text-slate-900">New observation set</h2>
                        <div class="mt-5 space-y-4"><label
                                class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Operation<input
                                    x-model="form.operation" class="mt-2 block w-full rounded-lg border-slate-200 text-sm"
                                    placeholder="Collar attach"></label><label
                                class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Seconds, comma
                                separated<input x-model="form.values"
                                    class="mt-2 block w-full rounded-lg border-slate-200 text-sm"
                                    placeholder="42, 44, 41, 43"></label><button @click="addCycle"
                                class="w-full rounded-lg bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">+
                                Add observations</button></div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="font-semibold text-slate-900">Observation register</h2>
                                <p class="mt-1 text-sm text-slate-500">Instant study statistics per operation.</p>
                            </div><span class="rounded-full bg-cyan-50 px-3 py-1 text-xs font-semibold text-cyan-700"
                                x-text="cycleCount + ' samples'"></span>
                        </div>
                        <div class="mt-5 overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="border-b border-slate-100 text-xs uppercase tracking-wider text-slate-400">
                                    <tr>
                                        <th class="px-3 py-3">Operation</th>
                                        <th class="px-3 py-3">Average</th>
                                        <th class="px-3 py-3">Range</th>
                                        <th class="px-3 py-3">Std dev</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody><template x-for="(row, index) in cycleRows" :key="row.id">
                                        <tr class="border-b border-slate-100">
                                            <td class="px-3 py-4 font-medium text-slate-900" x-text="row.operation"></td>
                                            <td class="px-3 py-4" x-text="average(row.values).toFixed(1) + ' s'"></td>
                                            <td class="px-3 py-4"
                                                x-text="Math.min(...row.values) + ' - ' + Math.max(...row.values) + ' s'">
                                            </td>
                                            <td class="px-3 py-4" x-text="stdDev(row.values).toFixed(1) + ' s'"></td>
                                            <td class="px-3 py-4 text-right"><button
                                                    @click="cycleRows.splice(index, 1); save()"
                                                    class="text-slate-400 hover:text-red-600">Remove</button></td>
                                        </tr>
                                    </template></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @elseif ($module === 'breakdown')
                <div class="grid gap-6 lg:grid-cols-[320px_1fr]">
                    <div class="rounded-xl border border-slate-200 bg-white p-5">
                        <h2 class="font-semibold text-slate-900">Record loss</h2>
                        <div class="mt-5 space-y-4"><label
                                class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Activity<input
                                    x-model="form.activity" class="mt-2 block w-full rounded-lg border-slate-200 text-sm"
                                    placeholder="Thread break correction"></label><label
                                class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Minutes<input
                                    x-model.number="form.minutes" type="number" min="1"
                                    class="mt-2 block w-full rounded-lg border-slate-200 text-sm"
                                    placeholder="12"></label><button @click="addBreakdown"
                                class="w-full rounded-lg bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-700">+
                                Record breakdown</button></div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="font-semibold text-slate-900">Loss register</h2>
                                <p class="mt-1 text-sm text-slate-500">Prioritize the activities consuming the most time.
                                </p>
                            </div><span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700"
                                x-text="breakdownTotal + ' minutes'"></span>
                        </div>
                        <div class="mt-5 space-y-3"><template x-for="(row, index) in breakdownRows" :key="row.id">
                                <div class="rounded-lg border border-slate-100 p-4">
                                    <div class="flex justify-between gap-4 text-sm"><span class="font-medium text-slate-900"
                                            x-text="row.activity"></span><span class="font-semibold text-amber-700"
                                            x-text="row.minutes + ' min'"></span></div>
                                    <div class="mt-3 h-2 rounded-full bg-slate-100">
                                        <div class="h-2 rounded-full bg-amber-500"
                                            :style="'width:' + Math.min(100, row.minutes / Math.max(breakdownTotal, 1) * 100) + '%'">
                                        </div>
                                    </div><button @click="breakdownRows.splice(index, 1); save()"
                                        class="mt-2 text-xs text-slate-400 hover:text-red-600">Remove</button>
                                </div>
                            </template></div>
                    </div>
                </div>
            @elseif ($module === 'line-balancing')
                <div class="grid gap-6 lg:grid-cols-[320px_1fr]">
                    <div class="rounded-xl border border-slate-200 bg-white p-5">
                        <h2 class="font-semibold text-slate-900">Balance inputs</h2>
                        <div class="mt-5 space-y-4"><label
                                class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Takt time
                                (seconds)<input x-model.number="form.takt" type="number" min="1"
                                    class="mt-2 block w-full rounded-lg border-slate-200 text-sm"></label><label
                                class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Station
                                times<input x-model="form.stations"
                                    class="mt-2 block w-full rounded-lg border-slate-200 text-sm"
                                    placeholder="42, 48, 39, 44"></label></div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="font-semibold text-slate-900">Station analysis</h2>
                                <p class="mt-1 text-sm text-slate-500">Stations over takt are flagged as bottlenecks.</p>
                            </div><span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700"
                                x-text="balanceEfficiency.toFixed(1) + '% efficiency'"></span>
                        </div>
                        <div class="mt-5 space-y-3"><template x-for="(time, index) in stationValues" :key="index">
                                <div class="flex items-center gap-4 rounded-lg border border-slate-100 p-4"><span
                                        class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold"
                                        :class="time > form.takt ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700'"
                                        x-text="index + 1"></span>
                                    <div class="flex-1">
                                        <div class="flex justify-between text-sm"><span class="font-medium">Station <span
                                                    x-text="index + 1"></span></span><span
                                                x-text="time + 's / ' + form.takt + 's'"></span></div>
                                        <div class="mt-2 h-2 rounded-full bg-slate-100">
                                            <div class="h-2 rounded-full"
                                                :class="time > form.takt ? 'bg-red-500' : 'bg-emerald-500'"
                                                :style="'width:' + Math.min(100, time / form.takt * 100) + '%'"></div>
                                        </div>
                                    </div><span class="text-xs font-semibold uppercase text-slate-400"
                                        x-text="time > form.takt ? 'Bottleneck' : 'On takt'"></span>
                                </div>
                            </template></div>
                    </div>
                </div>
            @elseif ($module === 'kaizen')
                <div class="grid gap-6 lg:grid-cols-[320px_1fr]">
                    <div class="rounded-xl border border-slate-200 bg-white p-5">
                        <h2 class="font-semibold text-slate-900">New proposal</h2>
                        <div class="mt-5 space-y-4"><label
                                class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Improvement<input
                                    x-model="form.title" class="mt-2 block w-full rounded-lg border-slate-200 text-sm"
                                    placeholder="Reposition thread rack"></label><label
                                class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Owner<input
                                    x-model="form.owner" class="mt-2 block w-full rounded-lg border-slate-200 text-sm"
                                    placeholder="Responsible person"></label><label
                                class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Expected seconds
                                saved<input x-model.number="form.impact" type="number" min="1"
                                    class="mt-2 block w-full rounded-lg border-slate-200 text-sm"></label><button
                                @click="addKaizen"
                                class="w-full rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-700">+
                                Propose kaizen</button></div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-5">
                        <h2 class="font-semibold text-slate-900">Improvement board</h2>
                        <div class="mt-5 space-y-3"><template x-for="(row, index) in kaizenRows" :key="row.id">
                                <div class="rounded-lg border border-slate-100 p-4">
                                    <div class="flex justify-between gap-4">
                                        <div>
                                            <p class="font-medium text-slate-900" x-text="row.title"></p>
                                            <p class="mt-1 text-xs text-slate-500"
                                                x-text="'Owner: ' + row.owner + ' · Gain: ' + row.impact + 's'"></p>
                                        </div><span class="h-fit rounded-full px-2 py-1 text-[11px] font-semibold"
                                            :class="row.status === 'Completed' ? 'bg-emerald-100 text-emerald-700' : row.status === 'In progress' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600'"
                                            x-text="row.status"></span>
                                    </div>
                                    <div class="mt-3 flex gap-3"><button @click="advanceKaizen(row); save()"
                                            class="text-xs font-semibold text-slate-700 hover:text-teal-700"
                                            x-text="row.status === 'Proposed' ? 'Start work' : row.status === 'In progress' ? 'Mark completed' : 'Completed'"></button><button
                                            @click="kaizenRows.splice(index, 1); save()"
                                            class="text-xs text-slate-400 hover:text-red-600">Remove</button></div>
                                </div>
                            </template></div>
                    </div>
                </div>
            @elseif ($module === 'skills')
                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <h2 class="font-semibold text-slate-900">Operator skill matrix</h2>
                    <p class="mt-1 text-sm text-slate-500">Click a rating to cycle from 1 to 5. Ratings of 3 or more count
                        as covered.</p>
                    <div class="mt-5 overflow-x-auto">
                        <table class="w-full min-w-[680px] text-left text-sm">
                            <thead class="border-b border-slate-100 text-xs uppercase tracking-wider text-slate-400">
                                <tr>
                                    <th class="px-3 py-3">Operator</th><template x-for="skill in skills" :key="skill">
                                        <th class="px-3 py-3" x-text="skill"></th>
                                    </template>
                                    <th class="px-3 py-3">Coverage</th>
                                </tr>
                            </thead>
                            <tbody><template x-for="(person, personIndex) in skillRows" :key="person.name">
                                    <tr class="border-b border-slate-100">
                                        <td class="px-3 py-4 font-medium text-slate-900" x-text="person.name"></td><template
                                            x-for="(rating, skillIndex) in person.ratings" :key="skillIndex">
                                            <td class="px-3 py-4"><button
                                                    @click="person.ratings[skillIndex] = rating === 5 ? 1 : rating + 1; save()"
                                                    class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold"
                                                    :class="rating >= 4 ? 'bg-emerald-100 text-emerald-700' : rating >= 3 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700'"
                                                    x-text="rating"></button></td>
                                        </template>
                                        <td class="px-3 py-4 font-semibold text-slate-700"
                                            x-text="Math.round(person.ratings.filter(r => r >= 3).length / skills.length * 100) + '%'">
                                        </td>
                                    </tr>
                                </template></tbody>
                        </table>
                    </div>
                </div>
            @elseif ($module === 'tpm')
                <div class="grid gap-6 lg:grid-cols-[320px_1fr]">
                    <div class="rounded-xl border border-slate-200 bg-white p-5">
                        <h2 class="font-semibold text-slate-900">Maintenance schedule</h2>
                        <div class="mt-5 space-y-4"><label
                                class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Equipment<input
                                    x-model="form.equipment" class="mt-2 block w-full rounded-lg border-slate-200 text-sm"
                                    placeholder="Juki DDL-9000B / M-04"></label><label
                                class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Next due<input
                                    x-model="form.nextDue" type="date"
                                    class="mt-2 block w-full rounded-lg border-slate-200 text-sm"></label><button
                                @click="addTpm"
                                class="w-full rounded-lg bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-orange-700">+
                                Add schedule</button></div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-5">
                        <h2 class="font-semibold text-slate-900">Equipment register</h2>
                        <div class="mt-5 space-y-3"><template x-for="(row, index) in tpmRows" :key="row.id">
                                <div class="flex items-center gap-4 rounded-lg border border-slate-100 p-4"><span
                                        class="rounded-lg bg-orange-50 p-2 text-orange-700">⚙</span>
                                    <div class="flex-1">
                                        <p class="font-medium text-slate-900" x-text="row.equipment"></p>
                                        <p class="mt-1 text-xs text-slate-500"
                                            x-text="'Preventive maintenance due ' + row.nextDue"></p>
                                    </div><span
                                        class="rounded-full bg-emerald-100 px-2 py-1 text-[11px] font-semibold text-emerald-700">Ready</span><button
                                        @click="tpmRows.splice(index, 1); save()"
                                        class="text-xs text-slate-400 hover:text-red-600">Remove</button>
                                </div>
                            </template></div>
                    </div>
                </div>
            @elseif ($module === 'materials')
                <div class="grid gap-6 lg:grid-cols-[320px_1fr]">
                    <div class="rounded-xl border border-slate-200 bg-white p-5">
                        <h2 class="font-semibold text-slate-900">Material master</h2>
                        <div class="mt-5 space-y-4"><label
                                class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Code<input
                                    x-model="form.code" class="mt-2 block w-full rounded-lg border-slate-200 text-sm"
                                    placeholder="MAT-025"></label><label
                                class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Material
                                name<input x-model="form.name" class="mt-2 block w-full rounded-lg border-slate-200 text-sm"
                                    placeholder="Cotton jersey 180 gsm"></label><label
                                class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Used by<input
                                    x-model="form.usage" class="mt-2 block w-full rounded-lg border-slate-200 text-sm"
                                    placeholder="Polo Shirt Basic"></label><button @click="addMaterial"
                                class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">+
                                Add material</button></div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-5">
                        <h2 class="font-semibold text-slate-900">Material register</h2>
                        <div class="mt-5 space-y-3"><template x-for="(row, index) in materialRows" :key="row.id">
                                <div class="flex items-center gap-4 rounded-lg border border-slate-100 p-4"><span
                                        class="rounded-lg bg-blue-50 px-2 py-1 text-xs font-bold text-blue-700"
                                        x-text="row.code"></span>
                                    <div class="flex-1">
                                        <p class="font-medium text-slate-900" x-text="row.name"></p>
                                        <p class="mt-1 text-xs text-slate-500" x-text="'Linked to ' + row.usage"></p>
                                    </div><button @click="materialRows.splice(index, 1); save()"
                                        class="text-xs text-slate-400 hover:text-red-600">Remove</button>
                                </div>
                            </template></div>
                    </div>
                </div>
            @elseif ($module === 'vsm')
                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <h2 class="font-semibold text-slate-900">Current state flow</h2><label
                        class="mt-5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Process
                        stages<input x-model="form.stages" class="mt-2 block w-full rounded-lg border-slate-200 text-sm"
                            placeholder="Cutting, Sewing, Inspection, Packing"></label>
                    <div class="mt-8 flex flex-wrap items-center gap-3"><template x-for="(stage, index) in stageValues"
                            :key="stage + index">
                            <div class="flex items-center gap-3">
                                <div class="min-w-[150px] rounded-xl border-2 border-teal-200 bg-teal-50/40 p-4">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-teal-600"
                                        x-text="'Stage ' + (index + 1)"></p>
                                    <p class="mt-2 font-semibold text-slate-900" x-text="stage"></p>
                                    <p class="mt-2 text-xs text-slate-500">Cycle time: define</p>
                                </div><span x-show="index < stageValues.length - 1" class="text-xl text-teal-500">→</span>
                            </div>
                        </template></div>
                </div>
            @endif
        </div>
    </div>

    <script>
        function leanOperations(module) {
            const defaults = {
                cycleRows: [{ id: 1, operation: 'Collar attach', values: [42, 44, 41, 43, 45] }, { id: 2, operation: 'Sleeve hem', values: [28, 31, 29, 30, 28] }],
                breakdownRows: [{ id: 1, activity: 'Thread break correction', minutes: 12 }, { id: 2, activity: 'Material replenishment', minutes: 8 }],
                kaizenRows: [{ id: 1, title: 'Reposition thread rack at station 04', owner: 'Siti Rahayu', impact: 18, status: 'In progress' }, { id: 2, title: 'Standardize collar bundle height', owner: 'Budi Santoso', impact: 9, status: 'Proposed' }],
                tpmRows: [{ id: 1, equipment: 'Juki DDL-9000B / M-04', nextDue: '2026-09-12' }, { id: 2, equipment: 'Brother S-7200C / S-11', nextDue: '2026-09-26' }],
                materialRows: [{ id: 1, code: 'MAT-001', name: 'Cotton jersey 180 gsm', usage: 'Polo Shirt Basic' }, { id: 2, code: 'MAT-014', name: 'Poly core-spun thread', usage: 'Collar attach' }]
            };
            const titles = { 'overview': ['Operations Overview', 'Select a focused page for each part of the Lean improvement loop.'], 'cycle-time': ['Cycle Time Study', 'Record observations and calculate average, range, and variation per operation.'], 'breakdown': ['Operational Breakdown', 'Record hidden losses by activity and make the biggest causes visible.'], 'line-balancing': ['Line Balancing', 'Compare station cycle time with takt time and identify bottlenecks.'], 'kaizen': ['Kaizen Board', 'Create improvement proposals, assign owners, and move actions to completed.'], 'skills': ['Skills & OSCP', 'Review operator skill coverage and identify training gaps.'], 'tpm': ['TPM Maintenance', 'Track equipment readiness and preventive maintenance due dates.'], 'materials': ['Material Database', 'Link material masters to the articles and operations that use them.'], 'vsm': ['Value Stream Map', 'Lay out the current process flow and prepare it for lead-time analysis.'] };
            const saved = JSON.parse(localStorage.getItem('lean-operations-' + module) || '{}');
            return {
                pageTitle: titles[module][0], pageDescription: titles[module][1], skills: ['Collar attach', 'Sleeve hem', 'Overlock', 'Final check'],
                cycleRows: saved.cycleRows || defaults.cycleRows, breakdownRows: saved.breakdownRows || defaults.breakdownRows, kaizenRows: saved.kaizenRows || defaults.kaizenRows, tpmRows: saved.tpmRows || defaults.tpmRows, materialRows: saved.materialRows || defaults.materialRows,
                skillRows: [{ name: 'Budi Santoso', ratings: [4, 3, 5, 2] }, { name: 'Siti Rahayu', ratings: [5, 4, 3, 4] }, { name: 'Agus Wijaya', ratings: [2, 3, 2, 1] }],
                form: { operation: '', values: '', activity: '', minutes: '', takt: 45, stations: '42, 48, 39, 44, 31', title: '', owner: '', impact: '', equipment: '', nextDue: '', code: '', name: '', usage: '', stages: 'Cutting, Sewing, Inspection, Packing' },
                average(values) { return values.reduce((sum, value) => sum + Number(value), 0) / values.length; },
                stdDev(values) { const avg = this.average(values); return Math.sqrt(this.average(values.map(value => (value - avg) ** 2))); },
                save() { localStorage.setItem('lean-operations-' + module, JSON.stringify({ cycleRows: this.cycleRows, breakdownRows: this.breakdownRows, kaizenRows: this.kaizenRows, tpmRows: this.tpmRows, materialRows: this.materialRows })); },
                addCycle() { const values = this.form.values.split(',').map(Number).filter(value => value > 0); if (!this.form.operation || !values.length) return; this.cycleRows.push({ id: Date.now(), operation: this.form.operation, values }); this.form.operation = ''; this.form.values = ''; this.save(); },
                addBreakdown() { if (!this.form.activity || !this.form.minutes) return; this.breakdownRows.push({ id: Date.now(), activity: this.form.activity, minutes: Number(this.form.minutes) }); this.form.activity = ''; this.form.minutes = ''; this.save(); },
                addKaizen() { if (!this.form.title || !this.form.owner || !this.form.impact) return; this.kaizenRows.push({ id: Date.now(), title: this.form.title, owner: this.form.owner, impact: Number(this.form.impact), status: 'Proposed' }); this.form.title = ''; this.form.owner = ''; this.form.impact = ''; this.save(); },
                advanceKaizen(row) { row.status = row.status === 'Proposed' ? 'In progress' : 'Completed'; },
                addTpm() { if (!this.form.equipment || !this.form.nextDue) return; this.tpmRows.push({ id: Date.now(), equipment: this.form.equipment, nextDue: this.form.nextDue }); this.form.equipment = ''; this.form.nextDue = ''; this.save(); },
                addMaterial() { if (!this.form.code || !this.form.name || !this.form.usage) return; this.materialRows.push({ id: Date.now(), code: this.form.code, name: this.form.name, usage: this.form.usage }); this.form.code = ''; this.form.name = ''; this.form.usage = ''; this.save(); },
                get cycleCount() { return this.cycleRows.reduce((sum, row) => sum + row.values.length, 0); }, get breakdownTotal() { return this.breakdownRows.reduce((sum, row) => sum + Number(row.minutes), 0); }, get stationValues() { return this.form.stations.split(',').map(Number).filter(value => value > 0); }, get balanceEfficiency() { return this.stationValues.length ? this.stationValues.reduce((sum, value) => sum + value, 0) / (this.stationValues.length * this.form.takt) * 100 : 0; }, get stageValues() { return this.form.stages.split(',').map(stage => stage.trim()).filter(Boolean); }
            };
        }
    </script>
</x-app-layout>