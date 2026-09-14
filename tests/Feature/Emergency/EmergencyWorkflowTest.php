<?php

namespace Tests\Feature\Emergency;

use App\Models\Bed;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Registration -> Triage -> Vitals -> Doctor -> Investigation -> Treatment
 * -> Admission / Discharge / Referral.
 */
class EmergencyWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function actingAsNurse(): User
    {
        $nurse = User::factory()->create();
        $nurse->assignRole(Role::NURSE);
        $this->actingAs($nurse, 'sanctum');

        return $nurse;
    }

    public function test_emergency_visit_can_be_registered_triaged_treated_and_admitted(): void
    {
        $this->actingAsNurse();
        $doctor = User::factory()->create();
        $doctor->assignRole(Role::DOCTOR);

        $patient = Patient::factory()->create();
        $bed = Bed::factory()->create(['status' => 'available']);

        $visitId = $this->postJson('/api/v1/emergency-visits', [
            'patient_id' => $patient->id,
            'chief_complaint' => 'Severe chest pain',
        ])->assertCreated()->assertJsonPath('data.status', 'registered')->json('data.id');

        $this->patchJson("/api/v1/emergency-visits/{$visitId}/triage", ['triage_level' => 'critical'])
            ->assertOk()->assertJsonPath('data.status', 'triaged');

        $this->patchJson("/api/v1/emergency-visits/{$visitId}/start-treatment")
            ->assertOk()->assertJsonPath('data.status', 'in-treatment');

        $this->patchJson("/api/v1/emergency-visits/{$visitId}/admit", [
            'doctor_id' => $doctor->id,
            'bed_id' => $bed->id,
        ])->assertOk()->assertJsonPath('data.status', 'admitted');

        $this->assertDatabaseHas('admissions', ['patient_id' => $patient->id]);
        $this->assertDatabaseHas('beds', ['id' => $bed->id, 'status' => 'occupied']);
    }

    public function test_emergency_visit_can_be_referred_instead_of_admitted(): void
    {
        $this->actingAsNurse();
        $patient = Patient::factory()->create();

        $visitId = $this->postJson('/api/v1/emergency-visits', ['patient_id' => $patient->id])
            ->json('data.id');

        $this->patchJson("/api/v1/emergency-visits/{$visitId}/refer", ['referred_to' => 'City General Hospital'])
            ->assertOk()
            ->assertJsonPath('data.status', 'referred')
            ->assertJsonPath('data.referred_to', 'City General Hospital');
    }

    public function test_cannot_act_on_an_already_closed_out_visit(): void
    {
        $this->actingAsNurse();
        $patient = Patient::factory()->create();

        $visitId = $this->postJson('/api/v1/emergency-visits', ['patient_id' => $patient->id])
            ->json('data.id');

        $this->patchJson("/api/v1/emergency-visits/{$visitId}/refer", ['referred_to' => 'Elsewhere'])->assertOk();

        $this->patchJson("/api/v1/emergency-visits/{$visitId}/discharge")->assertStatus(422);
    }
}
