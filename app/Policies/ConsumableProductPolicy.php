<?php

namespace App\Policies;

use App\Models\ConsumableProduct;
use App\Models\User;

class ConsumableProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('inventory.view');
    }

    public function view(User $user, ConsumableProduct $product): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user) && $user->hasPermission('inventory.manage');
    }

    public function update(User $user, ConsumableProduct $product): bool
    {
        return $this->create($user);
    }

    public function supply(User $user, ConsumableProduct $product): bool
    {
        return $this->create($user);
    }

    public function consume(User $user, ConsumableProduct $product): bool
    {
        return $this->viewAny($user) && $user->hasPermission('inventory.consume');
    }
}
