<?php

namespace Tests\Feature\Billing;

use App\Models\Bed;
use App\Models\BedAllocation;
use App\Models\Patient;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The IPD golden path from the README's Phase 10 plan: admission -> bed
 * allocation -> charges -> discharge -> final bill -> payment.
 */
class IpdFinalBillTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_final_bill_aggregates_room_charges_and_can_be_paid(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::HOSPITAL_ADMIN);
        $this->actingAs($admin, 'sanctum');

        $doctor = User::factory()->create();
        $patient = Patient::factory()->create();
        $room = Room::factory()->create(['daily_rate' => 2000]);
        $bed = Bed::factory()->create(['room_id' => $room->id, 'status' => 'available']);

        $admissionId = $this->postJson('/api/v1/admissions', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'bed_id' => $bed->id,
        ])->json('data.id');

        // Backdate the allocation by exactly 2 days instead of using Carbon
        // time-travel, which is more deterministic for an hours-based diff.
        BedAllocation::where('admission_id', $admissionId)
            ->update(['allocated_at' => now()->subDays(2)]);

        $this->patchJson("/api/v1/admissions/{$admissionId}/discharge", [
            'discharge_summary' => 'Recovered.',
        ])->assertOk();

        $response = $this->postJson("/api/v1/admissions/{$admissionId}/final-bill");

        $response->assertCreated()
            ->assertJsonPath('data.type', 'ipd')
            ->assertJsonPath('data.status', 'unpaid');

        $billId = $response->json('data.id');
        $totalAmount = (float) $response->json('data.total_amount');

        // 2 days at 2000/day = 4000 in room charges (only source of charges in this scenario).
        $this->assertEquals(4000.0, $totalAmount);

        $this->postJson('/api/v1/payments', [
            'bill_id' => $billId,
            'patient_id' => $patient->id,
            'amount' => $totalAmount,
            'method' => 'card',
        ])->assertCreated();

        $this->assertDatabaseHas('bills', ['id' => $billId, 'status' => 'paid']);
    }

    public function test_final_bill_cannot_be_generated_before_discharge(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::HOSPITAL_ADMIN);
        $this->actingAs($admin, 'sanctum');

        $doctor = User::factory()->create();
        $patient = Patient::factory()->create();
        $bed = Bed::factory()->create(['status' => 'available']);

        $admissionId = $this->postJson('/api/v1/admissions', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'bed_id' => $bed->id,
        ])->json('data.id');

        $this->postJson("/api/v1/admissions/{$admissionId}/final-bill")->assertStatus(422);
    }
}
