<?php

namespace Tests\Feature\Roles;

use App\Models\Bed;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Day-to-day actions must be reachable by the roles that perform them, not
 * only by administrators.
 */
class RoleWorkflowAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function actingAsRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        $this->actingAs($user, 'sanctum');

        return $user;
    }

    public function test_doctor_and_nurse_can_transfer_a_patient_to_another_bed(): void
    {
        foreach ([Role::DOCTOR, Role::NURSE] as $role) {
            $actor = $this->actingAsRole($role);
            $doctor = $role === Role::DOCTOR ? $actor : User::factory()->create();
            $patient = Patient::factory()->create();
            $bedA = Bed::factory()->create(['status' => 'available']);
            $bedB = Bed::factory()->create(['status' => 'available']);

            $admissionId = $this->postJson('/api/v1/admissions', [
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'bed_id' => $bedA->id,
            ])->json('data.id');

            // Nurses cannot admit; admit as a doctor first when acting as nurse.
            if (! $admissionId) {
                $this->actingAsRole(Role::DOCTOR);
                $admissionId = $this->postJson('/api/v1/admissions', [
                    'patient_id' => $patient->id,
                    'doctor_id' => $doctor->id,
                    'bed_id' => $bedA->id,
                ])->assertCreated()->json('data.id');
                $this->actingAs($actor, 'sanctum');
            }

            $this->patchJson("/api/v1/admissions/{$admissionId}/transfer-bed", ['bed_id' => $bedB->id])->assertOk();
            $this->assertDatabaseHas('beds', ['id' => $bedB->id, 'status' => 'occupied']);
        }
    }

    public function test_doctor_and_ot_staff_can_view_wards_that_the_ipd_pages_list(): void
    {
        foreach ([Role::DOCTOR, Role::NURSE, Role::OT_STAFF] as $role) {
            $this->actingAsRole($role);

            $this->getJson('/api/v1/wards')->assertOk();
            $this->getJson('/api/v1/rooms')->assertOk();
        }
    }

    public function test_receptionist_still_cannot_transfer_beds(): void
    {
        $admission = \App\Models\Admission::factory()->create();
        $bed = Bed::factory()->create(['status' => 'available']);

        $this->actingAsRole(Role::RECEPTIONIST);

        $this->patchJson("/api/v1/admissions/{$admission->id}/transfer-bed", ['bed_id' => $bed->id])->assertForbidden();
    }

    public function test_pharmacist_can_generate_a_bill_for_a_sale_they_dispensed(): void
    {
        $patient = Patient::factory()->create();
        $medicine = Medicine::factory()->create();
        MedicineBatch::factory()->create(['medicine_id' => $medicine->id, 'quantity' => 50]);

        $this->actingAsRole(Role::PHARMACIST);

        $saleId = $this->postJson('/api/v1/pharmacy-sales', [
            'patient_id' => $patient->id,
            'items' => [['medicine_id' => $medicine->id, 'quantity' => 2]],
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/v1/pharmacy-sales/{$saleId}/bill")->assertCreated();
    }

    public function test_user_without_billing_or_dispense_permission_cannot_bill_a_sale(): void
    {
        $patient = Patient::factory()->create();
        $medicine = Medicine::factory()->create();
        MedicineBatch::factory()->create(['medicine_id' => $medicine->id, 'quantity' => 50]);

        $this->actingAsRole(Role::PHARMACIST);
        $saleId = $this->postJson('/api/v1/pharmacy-sales', [
            'patient_id' => $patient->id,
            'items' => [['medicine_id' => $medicine->id, 'quantity' => 1]],
        ])->json('data.id');

        $this->actingAsRole(Role::LAB_TECHNICIAN);
        $this->postJson("/api/v1/pharmacy-sales/{$saleId}/bill")->assertForbidden();
    }
}
