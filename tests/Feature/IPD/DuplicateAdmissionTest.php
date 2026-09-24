<?php

namespace Tests\Feature\IPD;

use App\Models\Admission;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A patient can only hold one active admission at a time, and an emergency
 * visit can only be admitted once.
 */
class DuplicateAdmissionTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->doctor = User::factory()->create();
        $this->doctor->assignRole(Role::DOCTOR);
        $this->actingAs($this->doctor, 'sanctum');
    }

    public function test_patient_with_an_active_admission_cannot_be_admitted_again(): void
    {
        $patient = Patient::factory()->create();

        $this->postJson('/api/v1/admissions', ['patient_id' => $patient->id, 'doctor_id' => $this->doctor->id])
            ->assertCreated();

        $this->postJson('/api/v1/admissions', ['patient_id' => $patient->id, 'doctor_id' => $this->doctor->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('patient_id');

        $this->assertSame(1, Admission::where('patient_id', $patient->id)->count());
    }

    public function test_patient_can_be_readmitted_after_discharge(): void
    {
        $patient = Patient::factory()->create();

        $id = $this->postJson('/api/v1/admissions', ['patient_id' => $patient->id, 'doctor_id' => $this->doctor->id])
            ->json('data.id');

        $this->patchJson("/api/v1/admissions/{$id}/discharge", ['discharge_summary' => 'ok'])->assertOk();

        $this->postJson('/api/v1/admissions', ['patient_id' => $patient->id, 'doctor_id' => $this->doctor->id])
            ->assertCreated();
    }

    public function test_emergency_visit_cannot_be_admitted_twice(): void
    {
        $patient = Patient::factory()->create();

        $visitId = $this->postJson('/api/v1/emergency-visits', ['patient_id' => $patient->id])->json('data.id');
        $this->patchJson("/api/v1/emergency-visits/{$visitId}/triage", ['triage_level' => 'urgent'])->assertOk();
        $this->patchJson("/api/v1/emergency-visits/{$visitId}/start-treatment")->assertOk();

        $this->patchJson("/api/v1/emergency-visits/{$visitId}/admit", ['doctor_id' => $this->doctor->id])->assertOk();
        $this->patchJson("/api/v1/emergency-visits/{$visitId}/admit", ['doctor_id' => $this->doctor->id])
            ->assertUnprocessable();

        $this->assertSame(1, Admission::where('patient_id', $patient->id)->count());
    }

    public function test_emergency_admit_without_any_doctor_is_a_validation_error_not_a_server_error(): void
    {
        $patient = Patient::factory()->create();

        $visitId = $this->postJson('/api/v1/emergency-visits', ['patient_id' => $patient->id])->json('data.id');

        $this->patchJson("/api/v1/emergency-visits/{$visitId}/admit", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('doctor_id');
    }
}
