<?php

namespace Tests\Feature\Insurance;

use App\Models\InsuranceCompany;
use App\Models\InsurancePolicy;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsuranceClaimTest extends TestCase
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

    public function test_claim_can_be_submitted_approved_and_settled_creating_a_payment(): void
    {
        $this->actingAsBillingStaff();
        $patient = Patient::factory()->create();
        $company = InsuranceCompany::factory()->create();

        $policyId = $this->postJson('/api/v1/insurance-policies', [
            'patient_id' => $patient->id,
            'insurance_company_id' => $company->id,
            'policy_number' => 'POL-100',
        ])->assertCreated()->json('data.id');

        $billId = $this->postJson('/api/v1/bills', [
            'patient_id' => $patient->id,
            'type' => 'ipd',
            'items' => [['description' => 'Room charges', 'quantity' => 1, 'unit_price' => 20000]],
        ])->json('data.id');

        $claimId = $this->postJson('/api/v1/insurance-claims', [
            'insurance_policy_id' => $policyId,
            'bill_id' => $billId,
            'requested_amount' => 20000,
        ])->assertCreated()->assertJsonPath('data.status', 'submitted')->json('data.id');

        $this->patchJson("/api/v1/insurance-claims/{$claimId}/approve", ['approved_amount' => 18000])
            ->assertOk()->assertJsonPath('data.status', 'approved');

        $this->patchJson("/api/v1/insurance-claims/{$claimId}/settle", ['settled_amount' => 18000])
            ->assertOk()->assertJsonPath('data.status', 'settled');

        $this->assertDatabaseHas('payments', ['bill_id' => $billId, 'amount' => 18000, 'method' => 'insurance']);
        $this->assertDatabaseHas('bills', ['id' => $billId, 'paid_amount' => 18000, 'status' => 'partially-paid']);
    }

    public function test_claim_can_be_rejected(): void
    {
        $this->actingAsBillingStaff();
        $policy = InsurancePolicy::factory()->create();

        $claimId = $this->postJson('/api/v1/insurance-claims', [
            'insurance_policy_id' => $policy->id,
            'requested_amount' => 5000,
        ])->json('data.id');

        $this->patchJson("/api/v1/insurance-claims/{$claimId}/reject", ['rejection_reason' => 'Policy lapsed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.rejection_reason', 'Policy lapsed');
    }

    public function test_cannot_settle_a_claim_that_has_not_been_approved(): void
    {
        $this->actingAsBillingStaff();
        $policy = InsurancePolicy::factory()->create();

        $claimId = $this->postJson('/api/v1/insurance-claims', [
            'insurance_policy_id' => $policy->id,
            'requested_amount' => 5000,
        ])->json('data.id');

        $this->patchJson("/api/v1/insurance-claims/{$claimId}/settle", ['settled_amount' => 5000])
            ->assertStatus(422);
    }

    public function test_receptionist_cannot_approve_claims(): void
    {
        $receptionist = User::factory()->create();
        $receptionist->assignRole(Role::RECEPTIONIST);
        $this->actingAs($receptionist, 'sanctum');

        $policy = InsurancePolicy::factory()->create();

        $this->actingAsBillingStaff();
        $claimId = $this->postJson('/api/v1/insurance-claims', [
            'insurance_policy_id' => $policy->id,
            'requested_amount' => 5000,
        ])->json('data.id');

        $receptionist = User::where('id', $receptionist->id)->first();
        $this->actingAs($receptionist, 'sanctum');

        $this->patchJson("/api/v1/insurance-claims/{$claimId}/approve", ['approved_amount' => 5000])
            ->assertStatus(403);
    }
}
