<?php

namespace Tests\Feature\Radiology;

use App\Models\Patient;
use App\Models\RadiologyTest;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RadiologyWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_full_radiology_workflow_from_order_to_approved_report(): void
    {
        $doctor = User::factory()->create();
        $doctor->assignRole(Role::DOCTOR);

        $radiologyStaff = User::factory()->create();
        $radiologyStaff->assignRole(Role::RADIOLOGY_STAFF);

        $patient = Patient::factory()->create();
        $test = RadiologyTest::factory()->create(['modality' => 'X-Ray']);

        $this->actingAs($doctor, 'sanctum');
        $orderId = $this->postJson('/api/v1/radiology-orders', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'radiology_test_id' => $test->id,
        ])->assertCreated()->assertJsonPath('data.status', 'ordered')->json('data.id');

        $this->actingAs($radiologyStaff, 'sanctum');

        $this->patchJson("/api/v1/radiology-orders/{$orderId}/schedule")
            ->assertOk()->assertJsonPath('data.status', 'scheduled');

        $this->postJson("/api/v1/radiology-orders/{$orderId}/report", [
            'findings' => 'No acute abnormality.',
            'impression' => 'Normal chest X-ray.',
        ])->assertCreated();

        $this->patchJson("/api/v1/radiology-orders/{$orderId}/report/approve")
            ->assertOk()
            ->assertJsonPath('data.is_approved', true);

        $this->assertDatabaseHas('radiology_orders', ['id' => $orderId, 'status' => 'completed']);
    }

    public function test_pharmacist_cannot_order_radiology(): void
    {
        $pharmacist = User::factory()->create();
        $pharmacist->assignRole(Role::PHARMACIST);
        $this->actingAs($pharmacist, 'sanctum');

        $this->postJson('/api/v1/radiology-orders', [
            'patient_id' => Patient::factory()->create()->id,
            'doctor_id' => $pharmacist->id,
            'radiology_test_id' => RadiologyTest::factory()->create()->id,
        ])->assertStatus(403);
    }
}
