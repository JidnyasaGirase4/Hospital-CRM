<?php

namespace App\Policies;

use App\Models\Bill;
use App\Models\User;

class BillPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('billing.view');
    }

    public function view(User $user, Bill $bill): bool
    {
        return $user->hasPermission('billing.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('billing.create');
    }

    public function update(User $user, Bill $bill): bool
    {
        return $user->hasPermission('billing.update');
    }

    public function delete(User $user, Bill $bill): bool
    {
        return $user->hasPermission('billing.delete');
    }
}
