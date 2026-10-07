<?php

namespace App\Policies;

use App\Models\SalonVisit;
use App\Models\User;

class SalonVisitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('sales.view');
    }

    public function view(User $user, SalonVisit $visit): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user) && $user->hasPermission('sales.manage');
    }

    public function recordPayment(User $user, SalonVisit $visit): bool
    {
        return $this->create($user);
    }
}
