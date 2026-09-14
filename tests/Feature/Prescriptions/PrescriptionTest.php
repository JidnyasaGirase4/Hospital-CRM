<?php

namespace Tests\Feature\Prescriptions;

use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrescriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_doctor_can_write_a_prescription_with_multiple_items(): void
    {
        $doctor = User::factory()->create();
        $doctor->assignRole(Role::DOCTOR);
        $this->actingAs($doctor, 'sanctum');

        $patient = Patient::factory()->create();
        $medicineA = Medicine::factory()->create();
        $medicineB = Medicine::factory()->create();

        $response = $this->postJson('/api/v1/prescriptions', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'items' => [
                ['medicine_id' => $medicineA->id, 'dosage' => '500mg', 'frequency' => '1-0-1', 'duration' => '5 days', 'quantity' => 10],
                ['medicine_id' => $medicineB->id, 'dosage' => '250mg', 'frequency' => '1-1-1', 'duration' => '3 days', 'quantity' => 9],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonCount(2, 'data.items');

        $this->assertDatabaseCount('prescription_items', 2);
    }

    public function test_prescription_requires_at_least_one_item(): void
    {
        $doctor = User::factory()->create();
        $doctor->assignRole(Role::DOCTOR);
        $this->actingAs($doctor, 'sanctum');

        $this->postJson('/api/v1/prescriptions', [
            'patient_id' => Patient::factory()->create()->id,
            'doctor_id' => $doctor->id,
            'items' => [],
        ])->assertStatus(422)->assertJsonValidationErrors(['items']);
    }
}
