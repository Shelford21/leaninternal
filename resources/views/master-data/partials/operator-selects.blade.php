<div><label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Factory</label><select
        name="factory_id"
        class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2">
        <option value="">Select factory</option>@foreach ($factories as $factory)
        <option value="{{ $factory->id }}">{{ $factory->factory_name }}</option>@endforeach
    </select></div>
<div><label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Department</label><select
        name="department_id"
        class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2">
        <option value="">Select department</option>@foreach ($departments as $dept)
        <option value="{{ $dept->id }}">{{ $dept->department_name }}</option>@endforeach
    </select></div>
<div><label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Division</label><select
        name="division_id"
        class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2">
        <option value="">Select division</option>@foreach ($divisions as $div)
        <option value="{{ $div->id }}">{{ $div->division }}</option>@endforeach
    </select></div>
<div><label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Section</label><select
        name="section_id"
        class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2">
        <option value="">Select section</option>@foreach ($sections as $sec)
        <option value="{{ $sec->id }}">{{ $sec->section }}</option>@endforeach
    </select></div>
<div><label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Line</label><select name="line_id"
        class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2">
        <option value="">Select line</option>@foreach ($productionLines as $line)
        <option value="{{ $line->id }}">{{ $line->line_name }}</option>@endforeach
    </select></div>
<div><label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Gender</label><select
        name="gender" data-field="gender"
        class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2">
        <option value="">Select gender</option>@foreach ($genders as $gender)
        <option value="{{ $gender->gender }}">{{ $gender->gender }} - {{ $gender->description }}</option>@endforeach
    </select></div>
<div><label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Role</label><select name="role"
        data-field="role"
        class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2">
        <option value="">Select role</option>@foreach ($productionRoles as $role)
        <option value="{{ $role->production_role }}">{{ $role->production_role }}</option>@endforeach
    </select></div>
<div><label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Status PKWTT</label><select
        name="status_pkwtt_id"
        class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2">
        <option value="">Select status</option>@foreach ($statusPkwttList as $status)
        <option value="{{ $status->id }}">{{ $status->pkwtt }} - {{ $status->description }}</option>@endforeach
    </select></div>
<div><label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Educational Level</label><select
        name="educational_level_id"
        class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2">
        <option value="">Select level</option>@foreach ($educationalLevels as $level)
        <option value="{{ $level->id }}">{{ $level->level }} - {{ $level->description }}</option>@endforeach
    </select></div>