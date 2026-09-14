<?php

namespace App\Policies;

use App\Models\PatientVital;
use App\Models\User;

class PatientVitalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('nursing.view');
    }

    public function view(User $user, PatientVital $patientVital): bool
    {
        return $user->hasPermission('nursing.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('nursing.create');
    }
}
