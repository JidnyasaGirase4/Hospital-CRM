<?php

namespace Tests\Feature\Lookups;

use App\Models\Bed;
use App\Models\Medicine;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Form pick-lists must work for the roles that use those forms, even though
 * those roles lack users.view / pharmacy.view / beds.view.
 */
class LookupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function actingAsRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        $this->actingAs($user, 'sanctum');

        return $user;
    }

    public function test_receptionist_can_look_up_staff_without_users_view(): void
    {
        $doctor = User::factory()->create(['name' => 'Dr Lookup Target']);
        $doctor->assignRole(Role::DOCTOR);
        User::factory()->create(['name' => 'Inactive Person', 'is_active' => false]);

        $this->actingAsRole(Role::RECEPTIONIST);

        $this->getJson('/api/v1/users')->assertForbidden();

        $response = $this->getJson('/api/v1/lookups/staff?search=Lookup')->assertOk();
        $response->assertJsonPath('data.0.name', 'Dr Lookup Target');
        $response->assertJsonMissingPath('data.0.email');

        $this->assertCount(0, $this->getJson('/api/v1/lookups/staff?search=Inactive')->json('data'));
    }

    public function test_staff_lookup_can_filter_by_role(): void
    {
        $doctor = User::factory()->create(['name' => 'Dr Filter']);
        $doctor->assignRole(Role::DOCTOR);
        $nurse = User::factory()->create(['name' => 'Nurse Filter']);
        $nurse->assignRole(Role::NURSE);

        $this->actingAsRole(Role::RECEPTIONIST);

        $names = collect($this->getJson('/api/v1/lookups/staff?role=doctor')->assertOk()->json('data'))->pluck('name');

        $this->assertTrue($names->contains('Dr Filter'));
        $this->assertFalse($names->contains('Nurse Filter'));
    }

    public function test_doctor_and_nurse_can_look_up_active_medicines(): void
    {
        Medicine::factory()->create(['name' => 'Lookup Paracetamol', 'is_active' => true]);
        Medicine::factory()->create(['name' => 'Lookup Retired', 'is_active' => false]);

        foreach ([Role::DOCTOR, Role::NURSE] as $role) {
            $this->actingAsRole($role);

            $this->getJson('/api/v1/medicines')->assertForbidden();

            $names = collect($this->getJson('/api/v1/lookups/medicines?search=Lookup')->assertOk()->json('data'))->pluck('name');
            $this->assertTrue($names->contains('Lookup Paracetamol'));
            $this->assertFalse($names->contains('Lookup Retired'));
        }
    }

    public function test_doctor_can_look_up_available_beds_only(): void
    {
        $free = Bed::factory()->create(['status' => 'available']);
        Bed::factory()->create(['status' => 'occupied']);

        $this->actingAsRole(Role::DOCTOR);

        $ids = collect($this->getJson('/api/v1/lookups/beds')->assertOk()->json('data'))->pluck('id');

        $this->assertSame([$free->id], $ids->all());
    }

    public function test_lookups_require_authentication(): void
    {
        $this->getJson('/api/v1/lookups/staff')->assertUnauthorized();
        $this->getJson('/api/v1/lookups/medicines')->assertUnauthorized();
        $this->getJson('/api/v1/lookups/beds')->assertUnauthorized();
    }
}
