<?php

namespace Tests\Feature\Laboratory;

use App\Models\LabTest;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The lab workflow named in the spec: Doctor Order -> Lab Order -> Sample
 * Collection -> Processing -> Result -> Approval -> Report.
 */
class LabWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_full_lab_workflow_from_order_to_approved_result(): void
    {
        $doctor = User::factory()->create();
        $doctor->assignRole(Role::DOCTOR);

        $labTech = User::factory()->create();
        $labTech->assignRole(Role::LAB_TECHNICIAN);

        $patient = Patient::factory()->create();
        $test = LabTest::factory()->create(['name' => 'Blood Glucose']);

        // 1. Doctor orders the test.
        $this->actingAs($doctor, 'sanctum');
        $orderResponse = $this->postJson('/api/v1/lab-orders', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'test_ids' => [$test->id],
        ])->assertCreated()->assertJsonPath('data.status', 'ordered');

        $orderId = $orderResponse->json('data.id');
        $itemId = $orderResponse->json('data.items.0.id');

        // 2. Lab tech collects the sample.
        $this->actingAs($labTech, 'sanctum');
        $this->patchJson("/api/v1/lab-order-items/{$itemId}/collect-sample")
            ->assertOk()->assertJsonPath('data.status', 'sample-collected');

        $this->assertDatabaseHas('lab_orders', ['id' => $orderId, 'status' => 'sample-collected']);

        // 3. Processing begins.
        $this->patchJson("/api/v1/lab-order-items/{$itemId}/start-processing")
            ->assertOk()->assertJsonPath('data.status', 'processing');

        // 4. Result is recorded.
        $this->postJson("/api/v1/lab-order-items/{$itemId}/results", [
            'results' => [
                ['parameter_name' => 'Glucose', 'result_value' => '95', 'unit' => 'mg/dL', 'reference_range' => '70-100', 'flag' => 'normal'],
            ],
        ])->assertOk()->assertJsonPath('data.status', 'resulted');

        // 5. Result is approved -> order completes.
        $this->patchJson("/api/v1/lab-order-items/{$itemId}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.results.0.is_approved', true);

        $this->assertDatabaseHas('lab_orders', ['id' => $orderId, 'status' => 'completed']);
    }

    public function test_cannot_skip_workflow_stages(): void
    {
        $labTech = User::factory()->create();
        $labTech->assignRole(Role::LAB_TECHNICIAN);
        $this->actingAs($labTech, 'sanctum');

        $doctor = User::factory()->create();
        $patient = Patient::factory()->create();
        $test = LabTest::factory()->create();

        $itemId = $this->postJson('/api/v1/lab-orders', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'test_ids' => [$test->id],
        ])->json('data.items.0.id');

        // Cannot go straight to processing without collecting a sample first.
        $this->patchJson("/api/v1/lab-order-items/{$itemId}/start-processing")
            ->assertStatus(422);
    }

    public function test_receptionist_cannot_order_lab_tests(): void
    {
        $receptionist = User::factory()->create();
        $receptionist->assignRole(Role::RECEPTIONIST);
        $this->actingAs($receptionist, 'sanctum');

        $this->postJson('/api/v1/lab-orders', [
            'patient_id' => Patient::factory()->create()->id,
            'doctor_id' => $receptionist->id,
            'test_ids' => [LabTest::factory()->create()->id],
        ])->assertStatus(403);
    }
}
