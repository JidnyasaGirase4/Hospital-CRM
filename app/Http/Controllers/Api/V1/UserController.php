<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\SyncRolesRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private readonly UserService $userService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->with(['roles', 'department'])
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $term = "%{$request->string('search')}%";
                $q->where('name', 'like', $term)->orWhere('email', 'like', $term);
            }))
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->integer('department_id')))
            ->when($request->filled('role_id'), fn ($q) => $q->whereHas('roles', fn ($q) => $q->where('roles.id', $request->integer('role_id'))))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($users, UserResource::class, 'Users retrieved successfully');
    }

    public function store(StoreUserRequest $request)
    {
        $user = $this->userService->create($request->validated());

        return $this->success(new UserResource($user), 'User created successfully', 201);
    }

    public function show(User $user)
    {
        $this->authorize('view', $user);

        return $this->success(new UserResource($user->load(['roles', 'department'])));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $user = $this->userService->update($user, $request->validated());

        return $this->success(new UserResource($user), 'User updated successfully');
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        $user->delete();

        return $this->success(null, 'User deleted successfully');
    }

    public function syncRoles(SyncRolesRequest $request, User $user)
    {
        $user = $this->userService->syncRoles($user, $request->validated()['role_ids']);

        return $this->success(new UserResource($user), 'Roles synced successfully');
    }

    public function activate(User $user)
    {
        $this->authorize('update', $user);

        return $this->success(new UserResource($this->userService->setActive($user, true)), 'User activated');
    }

    public function deactivate(User $user)
    {
        $this->authorize('update', $user);

        return $this->success(new UserResource($this->userService->setActive($user, false)), 'User deactivated');
    }
}
