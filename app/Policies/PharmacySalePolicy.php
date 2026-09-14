<?php

namespace App\Policies;

use App\Models\PharmacySale;
use App\Models\User;

class PharmacySalePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('pharmacy.view');
    }

    public function view(User $user, PharmacySale $sale): bool
    {
        return $user->hasPermission('pharmacy.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('pharmacy.dispense');
    }

    public function return(User $user, PharmacySale $sale): bool
    {
        return $user->hasPermission('pharmacy.return');
    }
}
