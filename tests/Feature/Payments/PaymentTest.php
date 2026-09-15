<?php

namespace Tests\Feature\Payments;

use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
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

    private function createBill(int $patientId, float $total): int
    {
        return $this->postJson('/api/v1/bills', [
            'patient_id' => $patientId,
            'type' => 'opd',
            'items' => [['description' => 'Charge', 'quantity' => 1, 'unit_price' => $total]],
        ])->json('data.id');
    }

    public function test_partial_payment_marks_bill_partially_paid(): void
    {
        $this->actingAsBillingStaff();
        $patient = Patient::factory()->create();
        $billId = $this->createBill($patient->id, 1000);

        $this->postJson('/api/v1/payments', [
            'bill_id' => $billId,
            'patient_id' => $patient->id,
            'amount' => 400,
            'method' => 'cash',
        ])->assertCreated();

        $this->assertDatabaseHas('bills', ['id' => $billId, 'status' => 'partially-paid', 'paid_amount' => 400]);
    }

    public function test_full_payment_marks_bill_paid(): void
    {
        $this->actingAsBillingStaff();
        $patient = Patient::factory()->create();
        $billId = $this->createBill($patient->id, 1000);

        $this->postJson('/api/v1/payments', [
            'bill_id' => $billId,
            'patient_id' => $patient->id,
            'amount' => 1000,
            'method' => 'card',
        ])->assertCreated();

        $this->assertDatabaseHas('bills', ['id' => $billId, 'status' => 'paid', 'paid_amount' => 1000]);
    }

    public function test_payment_cannot_exceed_outstanding_balance(): void
    {
        $this->actingAsBillingStaff();
        $patient = Patient::factory()->create();
        $billId = $this->createBill($patient->id, 500);

        $response = $this->postJson('/api/v1/payments', [
            'bill_id' => $billId,
            'patient_id' => $patient->id,
            'amount' => 600,
            'method' => 'cash',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['amount']);
        $this->assertDatabaseHas('bills', ['id' => $billId, 'paid_amount' => 0]);
    }

    public function test_two_partial_payments_that_together_cover_the_bill_mark_it_paid(): void
    {
        $this->actingAsBillingStaff();
        $patient = Patient::factory()->create();
        $billId = $this->createBill($patient->id, 1000);

        $this->postJson('/api/v1/payments', ['bill_id' => $billId, 'patient_id' => $patient->id, 'amount' => 600, 'method' => 'cash'])->assertCreated();
        $this->postJson('/api/v1/payments', ['bill_id' => $billId, 'patient_id' => $patient->id, 'amount' => 400, 'method' => 'upi'])->assertCreated();

        $this->assertDatabaseHas('bills', ['id' => $billId, 'status' => 'paid', 'paid_amount' => 1000]);
    }

    public function test_advance_payment_without_a_bill_is_recorded_as_advance(): void
    {
        $this->actingAsBillingStaff();
        $patient = Patient::factory()->create();

        $response = $this->postJson('/api/v1/payments', [
            'patient_id' => $patient->id,
            'amount' => 5000,
            'method' => 'cash',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.purpose', 'advance')
            ->assertJsonPath('data.bill_id', null);
    }

    public function test_refund_cannot_exceed_the_amount_paid(): void
    {
        $this->actingAsBillingStaff();
        $patient = Patient::factory()->create();
        $billId = $this->createBill($patient->id, 1000);

        $paymentId = $this->postJson('/api/v1/payments', [
            'bill_id' => $billId,
            'patient_id' => $patient->id,
            'amount' => 500,
            'method' => 'cash',
        ])->json('data.id');

        $this->postJson("/api/v1/payments/{$paymentId}/refund", ['amount' => 600])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_refund_reduces_bill_paid_amount_and_status(): void
    {
        $this->actingAsBillingStaff();
        $patient = Patient::factory()->create();
        $billId = $this->createBill($patient->id, 1000);

        $paymentId = $this->postJson('/api/v1/payments', [
            'bill_id' => $billId,
            'patient_id' => $patient->id,
            'amount' => 1000,
            'method' => 'cash',
        ])->json('data.id');

        $this->assertDatabaseHas('bills', ['id' => $billId, 'status' => 'paid']);

        $this->postJson("/api/v1/payments/{$paymentId}/refund", [
            'amount' => 300,
            'reason' => 'Overcharged',
        ])->assertCreated();

        $this->assertDatabaseHas('bills', ['id' => $billId, 'status' => 'partially-paid', 'paid_amount' => 700]);
    }

    public function test_pharmacist_cannot_record_payments(): void
    {
        $pharmacist = User::factory()->create();
        $pharmacist->assignRole(Role::PHARMACIST);
        $this->actingAs($pharmacist, 'sanctum');

        $this->postJson('/api/v1/payments', [
            'patient_id' => Patient::factory()->create()->id,
            'amount' => 100,
            'method' => 'cash',
        ])->assertStatus(403);
    }
}
