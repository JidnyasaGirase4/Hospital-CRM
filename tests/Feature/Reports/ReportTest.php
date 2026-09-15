<?php

namespace Tests\Feature\Reports;

use App\Models\Bed;
use App\Models\Bill;
use App\Models\Patient;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function actingAsAccountant(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::ACCOUNTANT);
        $this->actingAs($user, 'sanctum');

        return $user;
    }

    public function test_accountant_can_view_revenue_report(): void
    {
        $this->actingAsAccountant();
        $patient = Patient::factory()->create();

        Bill::factory()->create(['patient_id' => $patient->id, 'type' => 'opd', 'total_amount' => 1000, 'paid_amount' => 1000]);
        Bill::factory()->create(['patient_id' => $patient->id, 'type' => 'pharmacy', 'total_amount' => 500, 'paid_amount' => 200]);

        $response = $this->getJson('/api/v1/reports/revenue')->assertOk();

        $this->assertEquals('1500.00', $response->json('data.total_billed'));
    }

    public function test_accountant_can_view_bed_occupancy_report(): void
    {
        $this->actingAsAccountant();

        $room = Room::factory()->create();
        Bed::factory()->create(['room_id' => $room->id, 'bed_number' => 'A', 'status' => 'occupied']);
        Bed::factory()->create(['room_id' => $room->id, 'bed_number' => 'B', 'status' => 'available']);

        $response = $this->getJson('/api/v1/reports/bed-occupancy')->assertOk();

        $response->assertJsonPath('data.total_beds', 2)
            ->assertJsonPath('data.occupied_beds', 1);

        $this->assertEquals(50.0, (float) $response->json('data.occupancy_rate'));
    }

    public function test_receptionist_cannot_view_reports(): void
    {
        $receptionist = User::factory()->create();
        $receptionist->assignRole(Role::RECEPTIONIST);
        $this->actingAs($receptionist, 'sanctum');

        $this->getJson('/api/v1/reports/revenue')->assertStatus(403);
    }
}
