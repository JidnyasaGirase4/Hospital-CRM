<?php

namespace App\Policies;

use App\Models\Diagnosis;
use App\Models\User;

class DiagnosisPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('diagnoses.view');
    }

    public function view(User $user, Diagnosis $diagnosis): bool
    {
        return $user->hasPermission('diagnoses.view') || $user->id === $diagnosis->doctor_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('diagnoses.create');
    }

    public function update(User $user, Diagnosis $diagnosis): bool
    {
        return $user->hasPermission('diagnoses.update') || $user->id === $diagnosis->doctor_id;
    }

    public function delete(User $user, Diagnosis $diagnosis): bool
    {
        return $user->hasPermission('diagnoses.delete');
    }
}
