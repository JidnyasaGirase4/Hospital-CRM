<?php

namespace App\Policies;

use App\Models\Bed;
use App\Models\User;

class BedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('beds.view');
    }

    public function view(User $user, Bed $bed): bool
    {
        return $user->hasPermission('beds.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('beds.create');
    }

    public function update(User $user, Bed $bed): bool
    {
        return $user->hasPermission('beds.update');
    }

    public function allocate(User $user): bool
    {
        return $user->hasPermission('beds.allocate');
    }
}
