<?php

namespace App\Policies;

use App\Models\LabOrder;
use App\Models\User;

class LabOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('laboratory.view');
    }

    public function view(User $user, LabOrder $labOrder): bool
    {
        return $user->hasPermission('laboratory.view') || $user->id === $labOrder->doctor_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('laboratory.create');
    }

    public function collectSample(User $user): bool
    {
        return $user->hasPermission('laboratory.collect-sample');
    }

    public function process(User $user): bool
    {
        return $user->hasPermission('laboratory.process');
    }

    public function recordResults(User $user): bool
    {
        return $user->hasPermission('laboratory.update');
    }

    public function approveResults(User $user): bool
    {
        return $user->hasPermission('laboratory.approve-result');
    }
}
