<?php

namespace App\Policies;

use App\Models\MedicationAdministration;
use App\Models\User;

class MedicationAdministrationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('nursing.view');
    }

    public function view(User $user, MedicationAdministration $medicationAdministration): bool
    {
        return $user->hasPermission('nursing.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('nursing.create');
    }
}
