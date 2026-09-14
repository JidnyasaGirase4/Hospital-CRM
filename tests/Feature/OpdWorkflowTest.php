<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The golden-path OPD flow named in the README's Phase 10 test plan:
 * appointment -> check-in -> consultation -> (prescription -> billing ->
 * payment land in later phases). Exercised end-to-end here as each of
 * those pieces becomes available, rather than only unit-testing modules
 * in isolation.
 */
class OpdWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_full_opd_flow_from_appointment_to_completed_consultation(): void
    {
        $receptionist = User::factory()->create();
        $receptionist->assignRole(Role::RECEPTIONIST);

        $doctor = User::factory()->create();
        $doctor->assignRole(Role::DOCTOR);

        $patient = Patient::factory()->create();

        // 1. Receptionist books the appointment.
        $this->actingAs($receptionist, 'sanctum');
        $appointmentId = $this->postJson('/api/v1/appointments', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'scheduled_at' => now()->addHour()->toDateTimeString(),
        ])->assertCreated()->json('data.id');

        // 2. Receptionist checks the patient in.
        $this->patchJson("/api/v1/appointments/{$appointmentId}/check-in")
            ->assertOk()
            ->assertJsonPath('data.status', 'checked-in');

        // 3. Doctor records the OPD visit (vitals/symptoms).
        $this->actingAs($doctor, 'sanctum');
        $visitId = $this->postJson('/api/v1/opd-visits', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_id' => $appointmentId,
            'symptoms' => 'Sore throat',
            'vitals' => ['temperature_c' => 37.8],
        ])->assertCreated()->json('data.id');

        // 4. Doctor performs the consultation and completes it.
        $consultationId = $this->postJson('/api/v1/consultations', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'opd_visit_id' => $visitId,
            'appointment_id' => $appointmentId,
            'chief_complaint' => 'Sore throat for 3 days',
            'diagnosis' => 'Pharyngitis',
        ])->assertCreated()->json('data.id');

        $this->patchJson("/api/v1/consultations/{$consultationId}/complete")
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');

        // 5. Appointment is auto-completed as a side effect.
        $this->assertDatabaseHas('appointments', ['id' => $appointmentId, 'status' => 'completed']);

        // 6. Patient 360 now surfaces the appointment, OPD visit and consultation.
        $response = $this->getJson("/api/v1/patients/{$patient->id}/360")->assertOk();

        $response->assertJsonCount(1, 'data.appointments')
            ->assertJsonCount(1, 'data.opd_visits')
            ->assertJsonCount(1, 'data.consultations')
            ->assertJsonPath('data.consultations.0.diagnosis', 'Pharyngitis');
    }
}
