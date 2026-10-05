<x-guest-layout>
    <!-- Session Status -->
    @if (session('status'))
        <div class="mb-4 rounded-lg bg-emerald-500/10 border border-emerald-500/20 px-4 py-3 text-sm text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Username -->
        <div>
            <label for="username" class="block text-sm font-medium text-indigo-200 mb-1.5">Username</label>
            <input id="username" type="text" name="username" value="{{ old('username') }}" required autofocus
                autocomplete="username" style="color-scheme: light; color: #000;"
                class="w-full rounded-xl bg-white border border-slate-300 text-slate-900 placeholder-slate-400 px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none transition" />
            @error('username')
                <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-indigo-200 mb-1.5">Password</label>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                style="color-scheme: light; color: #000;"
                class="w-full rounded-xl bg-white border border-slate-300 text-slate-900 placeholder-slate-400 px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none transition" />
            @error('password')
                <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center gap-2 cursor-pointer">
                <input id="remember_me" type="checkbox" name="remember"
                    class="rounded border-white/20 bg-white/5 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-0">
                <span class="text-sm text-indigo-300">Remember me</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm text-indigo-400 hover:text-indigo-300 transition" href="{{ route('password.request') }}">
                    Forgot password?
                </a>
            @endif
        </div>

        <!-- Login Button -->
        <button type="submit"
            class="w-full rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-500/25 hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-slate-900 transition">
            Log in
        </button>
    </form>
</x-guest-layout>