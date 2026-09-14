<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['users.view']);
    }

    public function view(User $user, User $model): bool
    {
        return $user->id === $model->id || $user->hasPermission('users.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('users.create');
    }

    public function update(User $user, User $model): bool
    {
        return $user->id === $model->id || $user->hasPermission('users.update');
    }

    public function delete(User $user, User $model): bool
    {
        return $user->id !== $model->id && $user->hasPermission('users.delete');
    }

    public function manageRoles(User $user): bool
    {
        return $user->hasPermission('users.manage-roles');
    }
}
