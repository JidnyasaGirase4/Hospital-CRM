<?php

namespace Tests\Feature\Consultations;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsultationTest extends TestCase
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

    public function test_doctor_can_create_a_consultation(): void
    {
        $doctor = $this->actingAsDoctor();
        $patient = Patient::factory()->create();

        $response = $this->postJson('/api/v1/consultations', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'chief_complaint' => 'Chest pain',
            'examination' => 'BP normal, chest clear',
            'diagnosis' => 'Musculoskeletal pain',
        ]);

        $response->assertCreated()->assertJsonPath('data.status', 'in-progress');
    }

    public function test_completing_a_consultation_linked_to_an_appointment_also_completes_the_appointment(): void
    {
        $doctor = $this->actingAsDoctor();
        $patient = Patient::factory()->create();
        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'status' => 'checked-in',
        ]);

        $consultationId = $this->postJson('/api/v1/consultations', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_id' => $appointment->id,
            'chief_complaint' => 'Follow-up',
        ])->json('data.id');

        $this->patchJson("/api/v1/consultations/{$consultationId}/complete")
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'completed']);
    }

    public function test_pharmacist_cannot_create_consultations(): void
    {
        $pharmacist = User::factory()->create();
        $pharmacist->assignRole(Role::PHARMACIST);
        $this->actingAs($pharmacist, 'sanctum');

        $this->postJson('/api/v1/consultations', [
            'patient_id' => Patient::factory()->create()->id,
            'doctor_id' => $pharmacist->id,
        ])->assertStatus(403);
    }
}
