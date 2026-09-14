<?php

namespace App\Policies;

use App\Models\PharmacyPurchase;
use App\Models\User;

class PharmacyPurchasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('pharmacy.view');
    }

    public function view(User $user, PharmacyPurchase $purchase): bool
    {
        return $user->hasPermission('pharmacy.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('pharmacy.purchase');
    }
}
