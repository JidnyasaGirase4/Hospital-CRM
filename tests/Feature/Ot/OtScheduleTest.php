<?php

namespace Tests\Feature\Ot;

use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function actingAsOtStaff(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::OT_STAFF);
        $this->actingAs($user, 'sanctum');

        return $user;
    }

    public function test_surgery_can_be_scheduled_started_and_completed(): void
    {
        $this->actingAsOtStaff();
        $surgeon = User::factory()->create();
        $patient = Patient::factory()->create();

        $scheduleId = $this->postJson('/api/v1/ot-schedules', [
            'patient_id' => $patient->id,
            'surgeon_id' => $surgeon->id,
            'procedure_name' => 'Appendectomy',
            'scheduled_at' => now()->addDay()->toDateTimeString(),
        ])->assertCreated()->assertJsonPath('data.status', 'scheduled')->json('data.id');

        $this->patchJson("/api/v1/ot-schedules/{$scheduleId}/start")
            ->assertOk()->assertJsonPath('data.status', 'in-progress');

        $this->patchJson("/api/v1/ot-schedules/{$scheduleId}/complete", ['notes' => 'Procedure successful.'])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.notes', 'Procedure successful.');
    }

    public function test_cannot_complete_a_surgery_that_has_not_started(): void
    {
        $this->actingAsOtStaff();
        $surgeon = User::factory()->create();
        $patient = Patient::factory()->create();

        $scheduleId = $this->postJson('/api/v1/ot-schedules', [
            'patient_id' => $patient->id,
            'surgeon_id' => $surgeon->id,
            'procedure_name' => 'Appendectomy',
            'scheduled_at' => now()->addDay()->toDateTimeString(),
        ])->json('data.id');

        $this->patchJson("/api/v1/ot-schedules/{$scheduleId}/complete")->assertStatus(422);
    }

    public function test_receptionist_cannot_schedule_surgery(): void
    {
        $receptionist = User::factory()->create();
        $receptionist->assignRole(Role::RECEPTIONIST);
        $this->actingAs($receptionist, 'sanctum');

        $this->postJson('/api/v1/ot-schedules', [
            'patient_id' => Patient::factory()->create()->id,
            'surgeon_id' => $receptionist->id,
            'procedure_name' => 'Test',
            'scheduled_at' => now()->addDay()->toDateTimeString(),
        ])->assertStatus(403);
    }
}
