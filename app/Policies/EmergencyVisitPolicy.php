<?php

namespace App\Policies;

use App\Models\EmergencyVisit;
use App\Models\User;

class EmergencyVisitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('emergency.view');
    }

    public function view(User $user, EmergencyVisit $emergencyVisit): bool
    {
        return $user->hasPermission('emergency.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('emergency.create');
    }

    public function update(User $user, EmergencyVisit $emergencyVisit): bool
    {
        return $user->hasPermission('emergency.update');
    }
}
