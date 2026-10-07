<?php

namespace App\Policies;

use App\Models\ServiceCategory;
use App\Models\User;

class ServiceCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('catalog.view');
    }

    public function view(User $user, ServiceCategory $record): bool
    {
        return $user->hasPermission('catalog.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('catalog.manage');
    }

    public function update(User $user, ServiceCategory $record): bool
    {
        return $user->hasPermission('catalog.manage');
    }

    public function delete(User $user, ServiceCategory $record): bool
    {
        return $user->hasPermission('catalog.manage');
    }
}
