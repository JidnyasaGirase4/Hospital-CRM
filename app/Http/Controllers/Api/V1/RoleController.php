<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\StoreRoleRequest;
use App\Http\Requests\Roles\SyncPermissionsRequest;
use App\Http\Requests\Roles\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Role::class);

        $roles = Role::query()
            ->with('permissions')
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($roles, RoleResource::class, 'Roles retrieved successfully');
    }

    public function store(StoreRoleRequest $request)
    {
        $role = Role::create($request->safe()->except('permission_ids'));

        if ($request->filled('permission_ids')) {
            $role->permissions()->sync($request->validated()['permission_ids']);
        }

        return $this->success(new RoleResource($role->load('permissions')), 'Role created successfully', 201);
    }

    public function show(Role $role)
    {
        $this->authorize('view', $role);

        return $this->success(new RoleResource($role->load('permissions')));
    }

    public function update(UpdateRoleRequest $request, Role $role)
    {
        $role->update($request->validated());

        return $this->success(new RoleResource($role->fresh('permissions')), 'Role updated successfully');
    }

    public function destroy(Role $role)
    {
        $this->authorize('delete', $role);

        $role->delete();

        return $this->success(null, 'Role deleted successfully');
    }

    public function syncPermissions(SyncPermissionsRequest $request, Role $role)
    {
        $role->permissions()->sync($request->validated()['permission_ids']);

        $this->auditLog->log('permissions-synced', $role, ['permission_ids' => $request->validated()['permission_ids']]);

        $role->users->each->forgetRoleCache();

        return $this->success(new RoleResource($role->fresh('permissions')), 'Permissions synced successfully');
    }
}
