<?php

namespace App\Policies;

use App\Models\Medicine;
use App\Models\User;

class MedicinePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('pharmacy.view');
    }

    public function view(User $user, Medicine $medicine): bool
    {
        return $user->hasPermission('pharmacy.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('pharmacy.create');
    }

    public function update(User $user, Medicine $medicine): bool
    {
        return $user->hasPermission('pharmacy.update');
    }

    public function delete(User $user, Medicine $medicine): bool
    {
        return $user->hasPermission('pharmacy.delete');
    }
}
