<?php

namespace App\Policies;

use App\Models\Prescription;
use App\Models\User;

class PrescriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('prescriptions.view');
    }

    public function view(User $user, Prescription $prescription): bool
    {
        return $user->hasPermission('prescriptions.view') || $user->id === $prescription->doctor_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('prescriptions.create');
    }

    public function update(User $user, Prescription $prescription): bool
    {
        return $user->hasPermission('prescriptions.update') || $user->id === $prescription->doctor_id;
    }

    public function delete(User $user, Prescription $prescription): bool
    {
        return $user->hasPermission('prescriptions.delete');
    }
}
