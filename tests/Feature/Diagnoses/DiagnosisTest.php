<?php

namespace Tests\Feature\Diagnoses;

use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiagnosisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_doctor_can_record_a_diagnosis(): void
    {
        $doctor = User::factory()->create();
        $doctor->assignRole(Role::DOCTOR);
        $this->actingAs($doctor, 'sanctum');

        $patient = Patient::factory()->create();

        $response = $this->postJson('/api/v1/diagnoses', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'diagnosis_name' => 'Hypertension',
            'diagnosis_code' => 'I10',
        ]);

        $response->assertCreated()->assertJsonPath('data.diagnosis_name', 'Hypertension');
    }

    public function test_nurse_cannot_record_a_diagnosis(): void
    {
        $nurse = User::factory()->create();
        $nurse->assignRole(Role::NURSE);
        $this->actingAs($nurse, 'sanctum');

        $this->postJson('/api/v1/diagnoses', [
            'patient_id' => Patient::factory()->create()->id,
            'doctor_id' => $nurse->id,
            'diagnosis_name' => 'Test',
        ])->assertStatus(403);
    }
}
