<?php

namespace Tests\Feature\Roles;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_super_admin_bypasses_all_permission_checks(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::SUPER_ADMIN);
        $this->actingAs($admin, 'sanctum');

        $this->getJson('/api/v1/roles')->assertOk();
        $this->getJson('/api/v1/permissions')->assertOk();
    }

    public function test_system_role_cannot_be_deleted(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::SUPER_ADMIN);
        $this->actingAs($admin, 'sanctum');

        $doctorRole = Role::where('slug', Role::DOCTOR)->firstOrFail();

        $this->deleteJson("/api/v1/roles/{$doctorRole->id}")->assertStatus(403);
        $this->assertDatabaseHas('roles', ['id' => $doctorRole->id]);
    }

    public function test_admin_can_create_a_custom_role_with_permissions(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::SUPER_ADMIN);
        $this->actingAs($admin, 'sanctum');

        $permissionIds = Permission::where('module', 'patients')->pluck('id')->all();

        $response = $this->postJson('/api/v1/roles', [
            'name' => 'Ward Clerk',
            'slug' => 'ward-clerk',
            'permission_ids' => $permissionIds,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.slug', 'ward-clerk')
            ->assertJsonCount(count($permissionIds), 'data.permissions');
    }

    public function test_role_permission_changes_immediately_affect_authorization(): void
    {
        $customRole = Role::create(['name' => 'Limited', 'slug' => 'limited']);

        $user = User::factory()->create();
        $user->assignRole($customRole);
        $this->actingAs($user, 'sanctum');

        $this->getJson('/api/v1/users')->assertStatus(403);

        $viewUsersPermission = Permission::where('slug', 'users.view')->firstOrFail();
        $customRole->permissions()->sync([$viewUsersPermission->id]);
        $user->forgetRoleCache();

        $this->getJson('/api/v1/users')->assertOk();
    }
}
