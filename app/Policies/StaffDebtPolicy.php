<?php

namespace App\Policies;

use App\Models\StaffDebt;
use App\Models\User;

class StaffDebtPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('staff_debts.view');
    }

    public function view(User $user, StaffDebt $debt): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user) && $user->hasPermission('staff_debts.manage');
    }

    public function repay(User $user, StaffDebt $debt): bool
    {
        return $this->create($user);
    }
}
