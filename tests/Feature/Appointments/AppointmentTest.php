<?php

namespace Tests\Feature\Appointments;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function actingAsReceptionist(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::RECEPTIONIST);
        $this->actingAs($user, 'sanctum');

        return $user;
    }

    public function test_receptionist_can_book_an_appointment(): void
    {
        $this->actingAsReceptionist();

        $patient = Patient::factory()->create();
        $doctor = User::factory()->create();
        $doctor->assignRole(Role::DOCTOR);

        $response = $this->postJson('/api/v1/appointments', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'scheduled_at' => now()->addDay()->toDateTimeString(),
            'type' => 'new',
        ]);

        $response->assertCreated()->assertJsonPath('data.status', 'scheduled');
    }

    public function test_cannot_double_book_same_doctor_at_the_same_time(): void
    {
        $this->actingAsReceptionist();

        $doctor = User::factory()->create();
        $slot = now()->addDay()->toDateTimeString();

        Appointment::factory()->create(['doctor_id' => $doctor->id, 'scheduled_at' => $slot]);

        $response = $this->postJson('/api/v1/appointments', [
            'patient_id' => Patient::factory()->create()->id,
            'doctor_id' => $doctor->id,
            'scheduled_at' => $slot,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['scheduled_at']);
    }

    public function test_appointment_can_be_checked_in(): void
    {
        $this->actingAsReceptionist();

        $appointment = Appointment::factory()->create(['status' => 'scheduled']);

        $this->patchJson("/api/v1/appointments/{$appointment->id}/check-in")
            ->assertOk()
            ->assertJsonPath('data.status', 'checked-in');
    }

    public function test_cannot_check_in_an_already_completed_appointment(): void
    {
        $this->actingAsReceptionist();

        $appointment = Appointment::factory()->create(['status' => 'completed']);

        $this->patchJson("/api/v1/appointments/{$appointment->id}/check-in")
            ->assertStatus(422);
    }

    public function test_appointment_can_be_cancelled_with_a_reason(): void
    {
        $this->actingAsReceptionist();

        $appointment = Appointment::factory()->create(['status' => 'scheduled']);

        $this->patchJson("/api/v1/appointments/{$appointment->id}/cancel", ['reason' => 'Patient unavailable'])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.cancellation_reason', 'Patient unavailable');
    }

    public function test_rescheduling_creates_a_new_appointment_linked_to_the_original(): void
    {
        $this->actingAsReceptionist();

        $original = Appointment::factory()->create(['status' => 'scheduled']);
        $newTime = now()->addDays(3)->toDateTimeString();

        $response = $this->patchJson("/api/v1/appointments/{$original->id}/reschedule", ['scheduled_at' => $newTime]);

        $response->assertOk()->assertJsonPath('data.rescheduled_from_id', $original->id);

        $this->assertDatabaseHas('appointments', ['id' => $original->id, 'status' => 'cancelled']);
    }

    public function test_receptionist_without_cancel_permission_cannot_cancel(): void
    {
        $pharmacist = User::factory()->create();
        $pharmacist->assignRole(Role::PHARMACIST);
        $this->actingAs($pharmacist, 'sanctum');

        $appointment = Appointment::factory()->create();

        $this->patchJson("/api/v1/appointments/{$appointment->id}/cancel")->assertStatus(403);
    }
}
