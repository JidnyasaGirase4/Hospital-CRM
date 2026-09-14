<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Ward;

class WardPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('beds.view');
    }

    public function view(User $user, Ward $ward): bool
    {
        return $user->hasPermission('beds.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('beds.create');
    }

    public function update(User $user, Ward $ward): bool
    {
        return $user->hasPermission('beds.update');
    }
}
