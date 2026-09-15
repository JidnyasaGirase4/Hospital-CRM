<?php

namespace Tests\Feature\Billing;

use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function actingAsBillingStaff(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::BILLING_STAFF);
        $this->actingAs($user, 'sanctum');

        return $user;
    }

    public function test_creating_a_bill_computes_totals_from_items(): void
    {
        $this->actingAsBillingStaff();
        $patient = Patient::factory()->create();

        $response = $this->postJson('/api/v1/bills', [
            'patient_id' => $patient->id,
            'type' => 'opd',
            'items' => [
                ['description' => 'Consultation fee', 'quantity' => 1, 'unit_price' => 500],
                ['description' => 'Dressing', 'quantity' => 2, 'unit_price' => 50, 'tax_amount' => 10],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.subtotal', '600.00')
            ->assertJsonPath('data.tax_total', '10.00')
            ->assertJsonPath('data.total_amount', '610.00')
            ->assertJsonPath('data.status', 'unpaid');
    }

    public function test_percentage_discount_is_computed_against_subtotal(): void
    {
        $this->actingAsBillingStaff();
        $patient = Patient::factory()->create();

        $billId = $this->postJson('/api/v1/bills', [
            'patient_id' => $patient->id,
            'type' => 'opd',
            'items' => [['description' => 'Consultation', 'quantity' => 1, 'unit_price' => 1000]],
        ])->json('data.id');

        $response = $this->postJson("/api/v1/bills/{$billId}/discounts", [
            'description' => 'Senior citizen discount',
            'type' => 'percentage',
            'value' => 10,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.discount_total', '100.00')
            ->assertJsonPath('data.total_amount', '900.00');
    }

    public function test_cannot_cancel_a_bill_that_has_payments(): void
    {
        $this->actingAsBillingStaff();
        $patient = Patient::factory()->create();

        $billId = $this->postJson('/api/v1/bills', [
            'patient_id' => $patient->id,
            'type' => 'opd',
            'items' => [['description' => 'Consultation', 'quantity' => 1, 'unit_price' => 500]],
        ])->json('data.id');

        $this->postJson('/api/v1/payments', [
            'bill_id' => $billId,
            'patient_id' => $patient->id,
            'amount' => 200,
            'method' => 'cash',
        ])->assertCreated();

        $this->patchJson("/api/v1/bills/{$billId}/cancel")->assertStatus(422);
    }

    public function test_receptionist_can_create_opd_bills(): void
    {
        $receptionist = User::factory()->create();
        $receptionist->assignRole(Role::RECEPTIONIST);
        $this->actingAs($receptionist, 'sanctum');

        $patient = Patient::factory()->create();

        $this->postJson('/api/v1/bills', [
            'patient_id' => $patient->id,
            'type' => 'opd',
            'items' => [['description' => 'Consultation', 'quantity' => 1, 'unit_price' => 500]],
        ])->assertCreated();
    }

    public function test_pharmacist_cannot_create_bills(): void
    {
        $pharmacist = User::factory()->create();
        $pharmacist->assignRole(Role::PHARMACIST);
        $this->actingAs($pharmacist, 'sanctum');

        $this->postJson('/api/v1/bills', [
            'patient_id' => Patient::factory()->create()->id,
            'type' => 'pharmacy',
            'items' => [['description' => 'Test', 'quantity' => 1, 'unit_price' => 10]],
        ])->assertStatus(403);
    }
}
