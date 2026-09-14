<?php

namespace Tests\Feature\Nursing;

use App\Models\Admission;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NursingTest extends TestCase
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

    public function test_nurse_can_record_a_note_vitals_and_medication_administration(): void
    {
        $nurse = $this->actingAsNurse();
        $admission = Admission::factory()->create();
        $medicine = Medicine::factory()->create();

        $this->postJson('/api/v1/nursing-notes', [
            'patient_id' => $admission->patient_id,
            'admission_id' => $admission->id,
            'type' => 'shift-handover',
            'note' => 'Patient stable, vitals normal.',
            'shift' => 'night',
        ])->assertCreated()->assertJsonPath('data.nurse.id', $nurse->id);

        $this->postJson('/api/v1/patient-vitals', [
            'patient_id' => $admission->patient_id,
            'admission_id' => $admission->id,
            'temperature_c' => 37.2,
            'pulse' => 72,
            'bp_systolic' => 118,
            'bp_diastolic' => 76,
            'spo2' => 98,
        ])->assertCreated()->assertJsonPath('data.pulse', 72);

        $this->postJson('/api/v1/medication-administrations', [
            'patient_id' => $admission->patient_id,
            'admission_id' => $admission->id,
            'medicine_id' => $medicine->id,
            'dose_given' => '500mg',
        ])->assertCreated();

        $this->assertDatabaseCount('nursing_notes', 1);
        $this->assertDatabaseCount('patient_vitals', 1);
        $this->assertDatabaseCount('medication_administrations', 1);
    }

    public function test_receptionist_cannot_record_nursing_data(): void
    {
        $receptionist = User::factory()->create();
        $receptionist->assignRole(Role::RECEPTIONIST);
        $this->actingAs($receptionist, 'sanctum');

        $patient = Patient::factory()->create();

        $this->postJson('/api/v1/nursing-notes', [
            'patient_id' => $patient->id,
            'note' => 'Should be blocked.',
        ])->assertStatus(403);
    }
}
