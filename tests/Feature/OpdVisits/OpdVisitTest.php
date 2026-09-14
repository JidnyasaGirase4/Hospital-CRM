<?php

namespace Tests\Feature\OpdVisits;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpdVisitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function actingAsDoctor(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::DOCTOR);
        $this->actingAs($user, 'sanctum');

        return $user;
    }

    public function test_creating_an_opd_visit_for_a_scheduled_appointment_checks_the_patient_in(): void
    {
        $doctor = $this->actingAsDoctor();
        $patient = Patient::factory()->create();
        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'status' => 'scheduled',
        ]);

        $response = $this->postJson('/api/v1/opd-visits', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_id' => $appointment->id,
            'symptoms' => 'Fever and cough',
            'vitals' => ['pulse' => 78, 'temperature_c' => 37.5],
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'checked-in']);
    }

    public function test_opd_visit_stores_structured_vitals(): void
    {
        $doctor = $this->actingAsDoctor();
        $patient = Patient::factory()->create();

        $response = $this->postJson('/api/v1/opd-visits', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'vitals' => ['bp_systolic' => 120, 'bp_diastolic' => 80],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.vitals.bp_systolic', 120)
            ->assertJsonPath('data.vitals.bp_diastolic', 80);
    }

    public function test_opd_visit_can_be_closed(): void
    {
        $doctor = $this->actingAsDoctor();
        $patient = Patient::factory()->create();

        $visitId = $this->postJson('/api/v1/opd-visits', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
        ])->json('data.id');

        $this->patchJson("/api/v1/opd-visits/{$visitId}/close")
            ->assertOk()
            ->assertJsonPath('data.status', 'closed');
    }
}
