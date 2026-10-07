<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('catalog.view');
    }

    public function view(User $user, Service $record): bool
    {
        return $user->hasPermission('catalog.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('catalog.manage');
    }

    public function update(User $user, Service $record): bool
    {
        return $user->hasPermission('catalog.manage');
    }

    public function delete(User $user, Service $record): bool
    {
        return $user->hasPermission('catalog.manage');
    }
}
