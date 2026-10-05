<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\LoginLog;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();
        $user = Auth::user();

        if (in_array($user->role->role_name, ['developer', 'admin', 'viewer'], true)) {
            LoginLog::create([
                'user_id' => $user->id,
                'username' => $user->username,
                'activity' => 'Login',
            ]);
            return redirect()->route('home');
        }

        Auth::logout();
        abort(403, 'Invalid user role.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user) {
            LoginLog::create([
                'user_id' => $user->id,
                'username' => $user->username,
                'activity' => 'Logout',
            ]);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
