<?php

namespace Tests\Feature\IPD;

use App\Models\Bed;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The spec's explicit rule: "Do not allow two active patients to occupy
 * the same bed." Covered at both the service layer and the raw database
 * constraint (see bed_allocations migration).
 */
class BedAllocationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::HOSPITAL_ADMIN);
        $this->actingAs($admin, 'sanctum');

        return $admin;
    }

    public function test_admitting_a_patient_allocates_an_available_bed_and_marks_it_occupied(): void
    {
        $this->actingAsAdmin();

        $doctor = User::factory()->create();
        $bed = Bed::factory()->create(['status' => 'available']);

        $response = $this->postJson('/api/v1/admissions', [
            'patient_id' => Patient::factory()->create()->id,
            'doctor_id' => $doctor->id,
            'bed_id' => $bed->id,
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('beds', ['id' => $bed->id, 'status' => 'occupied']);
        $this->assertDatabaseHas('bed_allocations', ['bed_id' => $bed->id, 'released_at' => null]);
    }

    public function test_cannot_admit_a_second_patient_into_an_already_occupied_bed(): void
    {
        $this->actingAsAdmin();

        $doctor = User::factory()->create();
        $bed = Bed::factory()->create(['status' => 'available']);

        $this->postJson('/api/v1/admissions', [
            'patient_id' => Patient::factory()->create()->id,
            'doctor_id' => $doctor->id,
            'bed_id' => $bed->id,
        ])->assertCreated();

        $response = $this->postJson('/api/v1/admissions', [
            'patient_id' => Patient::factory()->create()->id,
            'doctor_id' => $doctor->id,
            'bed_id' => $bed->id,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['bed_id']);

        // Rolled back: only one active allocation exists for the bed.
        $this->assertDatabaseCount('bed_allocations', 1);
    }

    public function test_discharge_releases_the_bed_for_cleaning_not_immediate_reuse(): void
    {
        $this->actingAsAdmin();

        $doctor = User::factory()->create();
        $bed = Bed::factory()->create(['status' => 'available']);

        $admissionId = $this->postJson('/api/v1/admissions', [
            'patient_id' => Patient::factory()->create()->id,
            'doctor_id' => $doctor->id,
            'bed_id' => $bed->id,
        ])->json('data.id');

        $this->patchJson("/api/v1/admissions/{$admissionId}/discharge", [
            'discharge_summary' => 'Recovered well.',
        ])->assertOk()->assertJsonPath('data.status', 'discharged');

        $this->assertDatabaseHas('beds', ['id' => $bed->id, 'status' => 'cleaning']);
        $this->assertDatabaseMissing('bed_allocations', ['bed_id' => $bed->id, 'released_at' => null]);

        // A different patient can now be admitted into the bed only after it's marked cleaned.
        $this->patchJson("/api/v1/beds/{$bed->id}/mark-cleaned")
            ->assertOk()->assertJsonPath('data.status', 'available');
    }

    public function test_cannot_mark_a_bed_cleaned_unless_it_is_awaiting_cleaning(): void
    {
        $this->actingAsAdmin();

        $bed = Bed::factory()->create(['status' => 'available']);

        $this->patchJson("/api/v1/beds/{$bed->id}/mark-cleaned")->assertStatus(422);
    }

    public function test_transferring_beds_releases_the_old_bed_and_occupies_the_new_one(): void
    {
        $this->actingAsAdmin();

        $doctor = User::factory()->create();
        $oldBed = Bed::factory()->create(['status' => 'available']);
        $newBed = Bed::factory()->create(['status' => 'available']);

        $admissionId = $this->postJson('/api/v1/admissions', [
            'patient_id' => Patient::factory()->create()->id,
            'doctor_id' => $doctor->id,
            'bed_id' => $oldBed->id,
        ])->json('data.id');

        $this->patchJson("/api/v1/admissions/{$admissionId}/transfer-bed", ['bed_id' => $newBed->id])
            ->assertOk()
            ->assertJsonPath('data.current_bed.bed.id', $newBed->id);

        $this->assertDatabaseHas('beds', ['id' => $oldBed->id, 'status' => 'cleaning']);
        $this->assertDatabaseHas('beds', ['id' => $newBed->id, 'status' => 'occupied']);
    }
}
