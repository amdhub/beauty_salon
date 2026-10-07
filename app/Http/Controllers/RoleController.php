<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Services\AccessManagement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Role::class);

        return view('roles.index', ['roles' => Role::with('permissions')->withCount('users')->orderBy('id')->paginate(15)]);
    }

    public function create(): View
    {
        Gate::authorize('create', Role::class);

        return view('roles.form', ['role' => new Role, 'permissions' => Permission::orderBy('id')->get()]);
    }

    public function store(RoleRequest $request, AccessManagement $access): RedirectResponse
    {
        $access->saveRole(new Role, $request->validated());

        return redirect()->route('roles.index')->with('success', 'Le rôle a été créé.');
    }

    public function edit(Role $role): View
    {
        Gate::authorize('update', $role);
        $role->load('permissions');

        return view('roles.form', ['role' => $role, 'permissions' => Permission::orderBy('id')->get()]);
    }

    public function update(RoleRequest $request, Role $role, AccessManagement $access): RedirectResponse
    {
        $access->saveRole($role, $request->validated());

        return redirect()->route('roles.index')->with('success','Les permissions ont été modifiées.');
    }
}
