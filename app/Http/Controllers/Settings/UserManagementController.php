<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'is_owner', 'created_at']);

        return Inertia::render('settings/users', [
            'users' => $users,
            'isOwner' => $request->user()->is_owner,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->is_owner, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'locale' => $request->user()->locale,
        ]);

        return back()->with('success', __('User created.'));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->is_owner, 403);
        abort_if($user->is_owner, 403);

        $user->delete();

        return back()->with('success', __('User deleted.'));
    }
}
