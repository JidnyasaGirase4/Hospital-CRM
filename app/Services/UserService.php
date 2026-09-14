<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                ...$data,
                'password' => Hash::make($data['password']),
                'is_active' => $data['is_active'] ?? true,
            ]);

            if (! empty($data['role_ids'])) {
                $user->syncRoles($data['role_ids']);
            }

            return $user->load('roles', 'department');
        });
    }

    public function update(User $user, array $data): User
    {
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return $user->fresh(['roles', 'department']);
    }

    public function syncRoles(User $user, array $roleIds): User
    {
        $user->syncRoles($roleIds);

        return $user->fresh('roles');
    }

    public function setActive(User $user, bool $active): User
    {
        $user->update(['is_active' => $active]);

        if (! $active) {
            $user->tokens()->delete();
        }

        return $user;
    }
}
