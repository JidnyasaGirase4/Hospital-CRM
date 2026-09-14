<?php

namespace Tests\Feature\Patients;

use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Patient360Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_doctor_can_view_patient_360(): void
    {
        $doctor = User::factory()->create();
        $doctor->assignRole(Role::DOCTOR);
        $this->actingAs($doctor, 'sanctum');

        $patient = Patient::factory()->create();

        $response = $this->getJson("/api/v1/patients/{$patient->id}/360");

        $response->assertOk()
            ->assertJsonPath('data.profile.mrn', $patient->mrn)
            ->assertJsonPath('data.profile.id', $patient->id);
    }

    public function test_user_without_view_360_permission_is_forbidden(): void
    {
        $pharmacist = User::factory()->create();
        $pharmacist->assignRole(Role::PHARMACIST);
        $this->actingAs($pharmacist, 'sanctum');

        $patient = Patient::factory()->create();

        $this->getJson("/api/v1/patients/{$patient->id}/360")->assertStatus(403);
    }

    public function test_patient_360_returns_404_for_unknown_patient(): void
    {
        $doctor = User::factory()->create();
        $doctor->assignRole(Role::DOCTOR);
        $this->actingAs($doctor, 'sanctum');

        $this->getJson('/api/v1/patients/999999/360')->assertStatus(404);
    }
}
