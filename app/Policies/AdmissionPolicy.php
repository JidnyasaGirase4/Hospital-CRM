<?php

namespace App\Policies;

use App\Models\Admission;
use App\Models\User;

class AdmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('ipd.view');
    }

    public function view(User $user, Admission $admission): bool
    {
        return $user->hasPermission('ipd.view') || $user->id === $admission->doctor_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('ipd.admit');
    }

    public function discharge(User $user, Admission $admission): bool
    {
        return $user->hasPermission('ipd.discharge');
    }

    public function update(User $user, Admission $admission): bool
    {
        return $user->hasPermission('ipd.update') || $user->id === $admission->doctor_id;
    }
}
