<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                ...$data,
                'password' => Hash::make($data['password']),
                'is_active' => $data['is_active'] ?? true,
            ]);

            $this->auditLog->log('user-created', $user, ['email' => $user->email]);

            if (! empty($data['role_ids'])) {
                $user->syncRoles($data['role_ids']);
                $this->auditLog->log('roles-synced', $user, ['role_ids' => $data['role_ids']]);
            }

            return $user->load('roles', 'department');
        });
    }

    public function update(User $user, array $data): User
    {
        $passwordChanged = ! empty($data['password']);

        if ($passwordChanged) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $wasActive = $user->is_active;
        $user->update($data);

        // Never log the hashed password itself - only that it changed.
        $this->auditLog->log('user-updated', $user, [
            'changed_fields' => array_keys(array_diff_key($data, array_flip(['password']))),
            'password_changed' => $passwordChanged,
        ]);

        // Deactivation must always revoke tokens, whether it comes through
        // this generic update endpoint or the dedicated setActive() one -
        // otherwise a deactivated user's existing token keeps working.
        if ($wasActive && ! $user->is_active) {
            $user->tokens()->delete();
        }

        return $user->fresh(['roles', 'department']);
    }

    public function syncRoles(User $user, array $roleIds): User
    {
        $user->syncRoles($roleIds);

        $this->auditLog->log('roles-synced', $user, ['role_ids' => $roleIds]);

        return $user->fresh('roles');
    }

    public function setActive(User $user, bool $active): User
    {
        $user->update(['is_active' => $active]);
        $this->auditLog->log($active ? 'user-activated' : 'user-deactivated', $user);

        if (! $active) {
            $user->tokens()->delete();
        }

        return $user;
    }
}
