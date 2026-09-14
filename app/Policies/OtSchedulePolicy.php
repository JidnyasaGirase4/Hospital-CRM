<?php

namespace App\Policies;

use App\Models\OtSchedule;
use App\Models\User;

class OtSchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('ot.view');
    }

    public function view(User $user, OtSchedule $otSchedule): bool
    {
        return $user->hasPermission('ot.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('ot.create');
    }

    public function update(User $user, OtSchedule $otSchedule): bool
    {
        return $user->hasPermission('ot.update');
    }
}
