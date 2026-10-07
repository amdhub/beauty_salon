<?php

namespace App\Policies;

use App\Models\ServiceVariant;
use App\Models\User;

class ServiceVariantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('catalog.view');
    }

    public function view(User $user, ServiceVariant $record): bool
    {
        return $user->hasPermission('catalog.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('catalog.manage');
    }

    public function update(User $user, ServiceVariant $record): bool
    {
        return $user->hasPermission('catalog.manage');
    }

    public function delete(User $user, ServiceVariant $record): bool
    {
        return $user->hasPermission('catalog.manage');
    }
}
