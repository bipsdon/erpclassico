<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->isPipelineManager(), 403);

        $users = User::orderBy('role')->orderBy('name')->get();

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        abort_unless(auth()->user()->isPipelineManager(), 403);

        return view('users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isPipelineManager(), 403);
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'email', 'max:150', 'unique:users,email'],
            'role'     => ['required', 'in:pipeline_manager,designer,printing_manager,sewing_manager'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'role'      => $data['role'],
            'password'  => Hash::make($data['password']),
            'is_active' => true,
        ]);

        return redirect()->route('users.index')
            ->with('success', "User {$data['name']} created successfully.");
    }

    public function edit(User $user): View
    {
        abort_unless(auth()->user()->isPipelineManager(), 403);

        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless(auth()->user()->isPipelineManager(), 403);
        $rules = [
            'name'  => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', "unique:users,email,{$user->id}"],
            'role'  => ['required', 'in:pipeline_manager,designer,printing_manager,sewing_manager'],
        ];

        if ($request->filled('password')) {
            $rules['password'] = ['confirmed', Password::min(8)];
        }

        $data = $request->validate($rules);

        $user->update([
            'name'  => $data['name'],
            'email' => $data['email'],
            'role'  => $data['role'],
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($data['password'])]);
        }

        return redirect()->route('users.index')
            ->with('success', "User {$user->name} updated.");
    }

    public function toggleActive(User $user): RedirectResponse
    {
        abort_unless(auth()->user()->isPipelineManager(), 403);
        abort_if($user->id === auth()->id(), 422, 'You cannot deactivate your own account.');

        $user->update(['is_active' => ! $user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "User {$user->name} has been {$status}.");
    }
}
