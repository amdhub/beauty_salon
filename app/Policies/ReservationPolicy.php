<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;

class ReservationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('reservations.view');
    }

    public function view(User $user, Reservation $reservation): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Reservation $reservation): bool
    {
        return $this->viewAny($user) && $user->hasPermission('reservations.manage');
    }
}
