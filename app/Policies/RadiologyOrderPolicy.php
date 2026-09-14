<?php

namespace App\Policies;

use App\Models\RadiologyOrder;
use App\Models\User;

class RadiologyOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('radiology.view');
    }

    public function view(User $user, RadiologyOrder $radiologyOrder): bool
    {
        return $user->hasPermission('radiology.view') || $user->id === $radiologyOrder->doctor_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('radiology.create');
    }

    public function update(User $user, RadiologyOrder $radiologyOrder): bool
    {
        return $user->hasPermission('radiology.update');
    }
}
