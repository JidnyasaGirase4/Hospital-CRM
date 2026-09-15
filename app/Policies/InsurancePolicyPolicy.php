<?php

namespace App\Policies;

use App\Models\InsurancePolicy;
use App\Models\User;

class InsurancePolicyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('insurance.view');
    }

    public function view(User $user, InsurancePolicy $insurancePolicy): bool
    {
        return $user->hasPermission('insurance.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('insurance.create');
    }

    public function update(User $user, InsurancePolicy $insurancePolicy): bool
    {
        return $user->hasPermission('insurance.update');
    }
}
