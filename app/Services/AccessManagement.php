<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccessManagement
{
    public function saveUser(User $user, array $data): void
    {
        DB::transaction(function () use ($user, $data): void {
            $admin = Role::where('slug', 'administrator')->lockForUpdate()->firstOrFail();
            $roles = array_map('intval', $data['roles']);
            if ($user->exists && $user->roles()->whereKey($admin->id)->exists() && ! in_array($admin->id, $roles, true) && $admin->users()->count() <= 1) {
                throw ValidationException::withMessages(['roles' => 'Conservez au moins un administrateur.']);
            }
            unset($data['roles']);
            if (empty($data['password'])) {
                unset($data['password']);
            }
            $user->fill($data)->save();
            $user->roles()->sync($roles);
        });
    }

    public function saveRole(Role $role, array $data): void
    {
        DB::transaction(function () use ($role, $data): void {
            if ($role->exists && $role->slug === 'administrator') {
                throw ValidationException::withMessages(['role' => 'Le rôle administrateur est protégé. Créez un rôle personnalisé pour adapter les permissions.']);
            }
            $permissions = $data['permissions'] ?? [];
            unset($data['permissions']);
            $role->fill($data)->save();
            $role->permissions()->sync($permissions);
        });
    }
}
