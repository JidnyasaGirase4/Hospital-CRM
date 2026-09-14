<?php

namespace App\Models\Concerns;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

trait HasRolesAndPermissions
{
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasRole(string|array $slug): bool
    {
        $slugs = Arr::wrap($slug);

        return $this->cachedRoleSlugs()->intersect($slugs)->isNotEmpty();
    }

    public function hasAnyRole(array $slugs): bool
    {
        return $this->hasRole($slugs);
    }

    public function hasPermission(string $slug): bool
    {
        return $this->cachedPermissionSlugs()->contains($slug);
    }

    public function hasAnyPermission(array $slugs): bool
    {
        return $this->cachedPermissionSlugs()->intersect($slugs)->isNotEmpty();
    }

    public function assignRole(Role|string $role): void
    {
        $role = $role instanceof Role ? $role : Role::where('slug', $role)->firstOrFail();
        $this->roles()->syncWithoutDetaching([$role->id]);
        $this->forgetRoleCache();
    }

    public function syncRoles(array $roleIds): void
    {
        $this->roles()->sync($roleIds);
        $this->forgetRoleCache();
    }

    protected function cachedRoleSlugs()
    {
        return Cache::remember(
            "user:{$this->id}:role-slugs",
            now()->addMinutes(30),
            fn () => $this->roles()->pluck('slug')
        );
    }

    protected function cachedPermissionSlugs()
    {
        return Cache::remember(
            "user:{$this->id}:permission-slugs",
            now()->addMinutes(30),
            fn () => Permission::query()
                ->join('permission_role', 'permissions.id', '=', 'permission_role.permission_id')
                ->join('role_user', 'permission_role.role_id', '=', 'role_user.role_id')
                ->where('role_user.user_id', $this->id)
                ->distinct()
                ->pluck('permissions.slug')
        );
    }

    public function forgetRoleCache(): void
    {
        Cache::forget("user:{$this->id}:role-slugs");
        Cache::forget("user:{$this->id}:permission-slugs");
    }
}
