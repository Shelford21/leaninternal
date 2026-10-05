<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class CredentialController extends Controller
{
    public function index()
    {
        $users = User::with('role')
            ->orderBy('username')
            ->get();

        $roles = Role::orderBy('role_name')->get();

        return view('credentials.index', compact('users', 'roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'password' => ['required', 'string', 'min:4'],
            'role_id' => ['required', 'exists:roles,id'],
            'description' => ['nullable', 'string'],
        ]);

        User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'role_id' => $validated['role_id'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('credentials.index')
            ->with('success', 'Credential created successfully.');
    }

    public function edit(User $user)
    {
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'role_id' => $user->role_id,
            'description' => $user->description,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'password' => ['nullable', 'string', 'min:4'],
            'role_id' => ['required', 'exists:roles,id'],
            'description' => ['nullable', 'string'],
        ]);

        $user->name = $validated['name'];
        $user->username = $validated['username'];
        $user->role_id = $validated['role_id'];
        $user->description = $validated['description'] ?? null;

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()
            ->route('credentials.index')
            ->with('success', 'Credential updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()
                ->route('credentials.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()
            ->route('credentials.index')
            ->with('success', 'Credential deleted successfully.');
    }
}