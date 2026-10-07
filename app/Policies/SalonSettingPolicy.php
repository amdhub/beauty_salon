<?php

namespace App\Policies;

use App\Models\User;

class SalonSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('settings.manage');
    }

    public function update(User $user): bool
    {
        return $user->hasPermission('settings.manage');
    }
}
