<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\AccessManagement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', User::class);

        return view('users.index', ['users' => User::with('roles')->orderBy('name')->orderBy('id')->paginate(15)]);
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('users.form', ['user' => new User, 'roles' => Role::orderBy('name')->get()]);
    }

    public function store(UserRequest $request, AccessManagement $access): RedirectResponse
    {
        $access->saveUser(new User, $request->validated());

        return redirect()->route('users.index')->with('success', 'Le compte a été créé.');
    }

    public function edit(User $user): View
    {
        Gate::authorize('update', $user);
        $user->load('roles');

        return view('users.form', ['user' => $user, 'roles' => Role::orderBy('name')->get()]);
    }

    public function update(UserRequest $request, User $user, AccessManagement $access): RedirectResponse
    {
        $access->saveUser($user, $request->validated());

        return redirect()->route('users.index')->with('success','Le compte a été modifié.');
    }
}
