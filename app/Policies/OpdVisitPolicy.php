<?php

namespace App\Policies;

use App\Models\OpdVisit;
use App\Models\User;

class OpdVisitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('opd.view');
    }

    public function view(User $user, OpdVisit $opdVisit): bool
    {
        return $user->hasPermission('opd.view') || $user->id === $opdVisit->doctor_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('opd.create');
    }

    public function update(User $user, OpdVisit $opdVisit): bool
    {
        return $user->hasPermission('opd.update');
    }

    public function delete(User $user, OpdVisit $opdVisit): bool
    {
        return $user->hasPermission('opd.delete');
    }
}
