<?php

namespace Tests\Feature\Users;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::SUPER_ADMIN);
        $this->actingAs($admin, 'sanctum');

        return $admin;
    }

    public function test_admin_can_create_a_user_with_roles(): void
    {
        $this->actingAsAdmin();

        $doctorRole = Role::where('slug', Role::DOCTOR)->firstOrFail();

        $response = $this->postJson('/api/v1/users', [
            'name' => 'Dr. Jane Doe',
            'email' => 'jane.doe@example.com',
            'password' => 'password123',
            'role_ids' => [$doctorRole->id],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.email', 'jane.doe@example.com')
            ->assertJsonPath('data.roles.0.slug', Role::DOCTOR);

        $this->assertDatabaseHas('users', ['email' => 'jane.doe@example.com']);
    }

    public function test_creating_a_user_requires_valid_data(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/v1/users', ['email' => 'not-an-email'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_user_without_permission_cannot_create_users(): void
    {
        $nurse = User::factory()->create();
        $nurse->assignRole(Role::NURSE);
        $this->actingAs($nurse, 'sanctum');

        $this->postJson('/api/v1/users', [
            'name' => 'Someone',
            'email' => 'someone@example.com',
            'password' => 'password123',
        ])->assertStatus(403);
    }

    public function test_user_can_view_and_update_own_profile_without_special_permission(): void
    {
        $nurse = User::factory()->create(['name' => 'Old Name']);
        $nurse->assignRole(Role::NURSE);
        $this->actingAs($nurse, 'sanctum');

        $this->getJson("/api/v1/users/{$nurse->id}")->assertOk();

        $this->putJson("/api/v1/users/{$nurse->id}", ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name');
    }

    public function test_self_service_update_cannot_reactivate_or_change_own_active_status(): void
    {
        $nurse = User::factory()->create(['is_active' => true]);
        $nurse->assignRole(Role::NURSE);
        $this->actingAs($nurse, 'sanctum');

        // A user without users.update can't toggle their own is_active via
        // the self-service update path - the field is silently dropped
        // rather than applied, so a deactivated user can never restore
        // their own access even if a stale token were still valid.
        $this->putJson("/api/v1/users/{$nurse->id}", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('users', ['id' => $nurse->id, 'is_active' => true]);
    }

    public function test_user_cannot_view_another_users_profile_without_permission(): void
    {
        $nurse = User::factory()->create();
        $nurse->assignRole(Role::NURSE);
        $this->actingAs($nurse, 'sanctum');

        $other = User::factory()->create();

        $this->getJson("/api/v1/users/{$other->id}")->assertStatus(403);
    }

    public function test_admin_can_deactivate_a_user_and_revoke_tokens(): void
    {
        $this->actingAsAdmin();

        $target = User::factory()->create();
        $target->createToken('test');

        $this->patchJson("/api/v1/users/{$target->id}/deactivate")
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        // See AuthTest::test_user_can_logout_and_token_is_revoked() for why
        // this is asserted against the database rather than a second
        // authenticated request within the same test.
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $target->id,
            'tokenable_type' => User::class,
        ]);
    }

    public function test_deactivating_via_the_generic_update_endpoint_also_revokes_tokens(): void
    {
        $this->actingAsAdmin();

        $target = User::factory()->create();
        $target->createToken('test');

        $this->putJson("/api/v1/users/{$target->id}", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $target->id,
            'tokenable_type' => User::class,
        ]);
    }

    public function test_admin_can_sync_roles_for_a_user(): void
    {
        $this->actingAsAdmin();

        $target = User::factory()->create();
        $pharmacistRole = Role::where('slug', Role::PHARMACIST)->firstOrFail();

        $this->postJson("/api/v1/users/{$target->id}/roles", [
            'role_ids' => [$pharmacistRole->id],
        ])->assertOk()->assertJsonPath('data.roles.0.slug', Role::PHARMACIST);
    }
}
