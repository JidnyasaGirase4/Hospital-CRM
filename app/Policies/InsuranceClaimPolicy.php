<?php

namespace App\Policies;

use App\Models\InsuranceClaim;
use App\Models\User;

class InsuranceClaimPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('insurance.view');
    }

    public function view(User $user, InsuranceClaim $insuranceClaim): bool
    {
        return $user->hasPermission('insurance.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('insurance.create');
    }

    public function approve(User $user): bool
    {
        return $user->hasPermission('insurance.approve-claim');
    }
}
