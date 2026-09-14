<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PermissionResource;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Role::class);

        $permissions = Permission::query()
            ->when($request->filled('module'), fn ($q) => $q->where('module', $request->string('module')))
            ->orderBy('module')
            ->get();

        return $this->success(PermissionResource::collection($permissions), 'Permissions retrieved successfully');
    }

    public function show(Permission $permission)
    {
        $this->authorize('viewAny', Role::class);

        return $this->success(new PermissionResource($permission));
    }
}
