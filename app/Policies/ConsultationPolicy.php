<?php

namespace App\Policies;

use App\Models\Consultation;
use App\Models\User;

class ConsultationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('consultations.view');
    }

    public function view(User $user, Consultation $consultation): bool
    {
        return $user->hasPermission('consultations.view') || $user->id === $consultation->doctor_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('consultations.create');
    }

    public function update(User $user, Consultation $consultation): bool
    {
        return $user->hasPermission('consultations.update') || $user->id === $consultation->doctor_id;
    }

    public function delete(User $user, Consultation $consultation): bool
    {
        return $user->hasPermission('consultations.delete');
    }
}
