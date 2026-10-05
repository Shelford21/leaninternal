<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-lg text-slate-800 leading-tight">
            Credentials
        </h2>
    </x-slot>

    <div class="py-8 px-4 sm:px-6 lg:px-8">

        <div class="max-w-7xl mx-auto">

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/60 p-6">

                {{-- Page Header --}}
                <div class="flex items-center justify-between mb-6">

                    <div>
                        <h1 class="text-xl font-bold text-slate-800">
                            Credentials
                        </h1>

                        <p class="text-sm text-slate-500 mt-1">
                            Manage system user accounts.
                        </p>
                    </div>

                    <button type="button"
                        onclick="document.getElementById('addCredentialModal').classList.remove('hidden')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-xl hover:bg-indigo-700 transition-colors shadow-sm shadow-indigo-200">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Credential
                    </button>

                </div>


                {{-- Success Message --}}
                @if (session('success'))

                    <div class="mb-6 p-4 bg-emerald-50 text-emerald-700 rounded-xl border border-emerald-200/60 text-sm">
                        {{ session('success') }}
                    </div>

                @endif

                @if (session('error'))

                    <div class="mb-6 p-4 bg-red-50 text-red-700 rounded-xl border border-red-200/60 text-sm">
                        {{ session('error') }}
                    </div>

                @endif


                {{-- Credentials Table --}}
                <div class="overflow-x-auto rounded-xl border border-slate-200/60">

                    <table class="min-w-full">

                        <thead class="bg-slate-50/80">

                            <tr>

                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    Username
                                </th>

                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    Password
                                </th>

                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    Description
                                </th>

                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    Role
                                </th>

                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    Actions
                                </th>

                            </tr>

                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            @forelse ($users as $user)

                                <tr class="hover:bg-slate-50/50 transition-colors">

                                    <td class="px-4 py-3 text-sm text-slate-700 font-medium">
                                        {{ $user->username }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-slate-400">
                                        ••••••••
                                    </td>

                                    <td class="px-4 py-3 text-sm text-slate-500">
                                        {{ $user->description ?? '-' }}
                                    </td>

                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                {{ $user->role->role_name === 'developer' ? 'bg-purple-100 text-purple-700' : '' }}
                                                {{ $user->role->role_name === 'admin' ? 'bg-blue-100 text-blue-700' : '' }}
                                                {{ $user->role->role_name === 'viewer' ? 'bg-slate-100 text-slate-700' : '' }}">
                                            {{ ucfirst($user->role->role_name) }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3">

                                        <button type="button" onclick="openEditCredentialModal(
                                                {{ $user->id }},
                                                @js($user->name),
                                                @js($user->username),
                                                {{ $user->role_id }},
                                                @js($user->description)
                                            )"
                                            class="text-indigo-600 hover:text-indigo-800 text-sm font-medium mr-3 transition-colors">
                                            Edit
                                        </button>

                                        <form method="POST" action="{{ route('credentials.destroy', $user) }}"
                                            class="inline"
                                            onsubmit="return confirm('Are you sure you want to delete this credential?');">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                class="text-red-500 hover:text-red-700 text-sm font-medium transition-colors">
                                                Delete
                                            </button>
                                        </form>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="5" class="px-4 py-12 text-center text-slate-400 text-sm">
                                        No credentials found.
                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>


    {{-- Add Credential Modal --}}
    <div id="addCredentialModal" class="hidden fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="addCredentialModalTitle" role="dialog" aria-modal="true">

        {{-- Background Overlay --}}
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
            onclick="document.getElementById('addCredentialModal').classList.add('hidden')"></div>


        {{-- Modal --}}
        <div class="flex min-h-full items-center justify-center p-4">

            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg">

                {{-- Modal Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">

                    <h2 id="addCredentialModalTitle" class="text-lg font-semibold text-slate-800">
                        Add Credential
                    </h2>

                    <button type="button"
                        onclick="document.getElementById('addCredentialModal').classList.add('hidden')"
                        class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                </div>


                {{-- Modal Body --}}
                <form method="POST" action="{{ route('credentials.store') }}">

                    @csrf

                    <div class="px-6 py-5 space-y-4">

                        {{-- Name --}}
                        <div>

                            <label for="name" class="block text-sm font-medium text-slate-700 mb-1">
                                Name
                            </label>

                            <input id="name" name="name" type="text" required
                                class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">

                        </div>


                        {{-- Username --}}
                        <div>

                            <label for="username" class="block text-sm font-medium text-slate-700 mb-1">
                                Username
                            </label>

                            <input id="username" name="username" type="text" required
                                class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">

                        </div>


                        {{-- Password --}}
                        <div>

                            <label for="password" class="block text-sm font-medium text-slate-700 mb-1">
                                Password
                            </label>

                            <input id="password" name="password" type="password" required
                                class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">

                        </div>


                        {{-- Role --}}
                        <div>

                            <label for="role_id" class="block text-sm font-medium text-slate-700 mb-1">
                                Role
                            </label>

                            <select id="role_id" name="role_id" required
                                class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">

                                <option value="">
                                    Select role
                                </option>

                                @foreach ($roles as $role)

                                    <option value="{{ $role->id }}">
                                        {{ ucfirst($role->role_name) }}
                                    </option>

                                @endforeach

                            </select>

                        </div>


                        {{-- Description --}}
                        <div>

                            <label for="description" class="block text-sm font-medium text-slate-700 mb-1">
                                Description
                            </label>

                            <textarea id="description" name="description" rows="3"
                                class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"></textarea>

                        </div>

                    </div>


                    {{-- Modal Footer --}}
                    <div
                        class="flex items-center justify-end gap-3 px-6 py-4 bg-slate-50 border-t border-slate-100 rounded-b-2xl">

                        <button type="button"
                            onclick="document.getElementById('addCredentialModal').classList.add('hidden')"
                            class="px-4 py-2.5 bg-white text-slate-700 text-sm font-medium rounded-xl border border-slate-200 hover:bg-slate-50 transition-colors">
                            Cancel
                        </button>

                        <button type="submit"
                            class="px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-xl hover:bg-indigo-700 transition-colors shadow-sm shadow-indigo-200">
                            Save Credential
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>


    {{-- Edit Credential Modal --}}
    <div id="editCredentialModal" class="hidden fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="editCredentialModalTitle" role="dialog" aria-modal="true">

        {{-- Background Overlay --}}
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" onclick="closeEditCredentialModal()"></div>


        {{-- Modal --}}
        <div class="flex min-h-full items-center justify-center p-4">

            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg">

                {{-- Modal Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">

                    <h2 id="editCredentialModalTitle" class="text-lg font-semibold text-slate-800">
                        Edit Credential
                    </h2>

                    <button type="button" onclick="closeEditCredentialModal()"
                        class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                </div>


                {{-- Modal Form --}}
                <form id="editCredentialForm" method="POST">

                    @csrf
                    @method('PUT')

                    <div class="px-6 py-5 space-y-4">

                        {{-- Name --}}
                        <div>

                            <label for="edit_name" class="block text-sm font-medium text-slate-700 mb-1">
                                Name
                            </label>

                            <input id="edit_name" name="name" type="text" required
                                class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">

                        </div>


                        {{-- Username --}}
                        <div>

                            <label for="edit_username" class="block text-sm font-medium text-slate-700 mb-1">
                                Username
                            </label>

                            <input id="edit_username" name="username" type="text" required
                                class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">

                        </div>


                        {{-- Password --}}
                        <div>

                            <label for="edit_password" class="block text-sm font-medium text-slate-700 mb-1">
                                New Password
                            </label>

                            <input id="edit_password" name="password" type="password"
                                class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">

                            <p class="text-xs text-slate-400 mt-1">
                                Leave blank to keep the existing password.
                            </p>

                        </div>


                        {{-- Role --}}
                        <div>

                            <label for="edit_role_id" class="block text-sm font-medium text-slate-700 mb-1">
                                Role
                            </label>

                            <select id="edit_role_id" name="role_id" required
                                class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">

                                @foreach ($roles as $role)

                                    <option value="{{ $role->id }}">
                                        {{ ucfirst($role->role_name) }}
                                    </option>

                                @endforeach

                            </select>

                        </div>


                        {{-- Description --}}
                        <div>

                            <label for="edit_description" class="block text-sm font-medium text-slate-700 mb-1">
                                Description
                            </label>

                            <textarea id="edit_description" name="description" rows="3"
                                class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"></textarea>

                        </div>

                    </div>


                    {{-- Modal Footer --}}
                    <div
                        class="flex items-center justify-end gap-3 px-6 py-4 bg-slate-50 border-t border-slate-100 rounded-b-2xl">

                        <button type="button" onclick="closeEditCredentialModal()"
                            class="px-4 py-2.5 bg-white text-slate-700 text-sm font-medium rounded-xl border border-slate-200 hover:bg-slate-50 transition-colors">
                            Cancel
                        </button>

                        <button type="submit"
                            class="px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-xl hover:bg-indigo-700 transition-colors shadow-sm shadow-indigo-200">
                            Save Changes
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>



    <script>

        function openEditCredentialModal(
            id,
            name,
            username,
            roleId,
            description
        ) {
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_password').value = '';
            document.getElementById('edit_role_id').value = roleId;
            document.getElementById('edit_description').value = description ?? '';

            document.getElementById('editCredentialForm').action =
                `/credentials/${id}`;

            document
                .getElementById('editCredentialModal')
                .classList
                .remove('hidden');
        }


        function closeEditCredentialModal() {

            document
                .getElementById('editCredentialModal')
                .classList
                .add('hidden');

        }

    </script>


</x-app-layout>