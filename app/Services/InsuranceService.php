<?php

namespace App\Services;

use App\Models\InsuranceClaim;
use App\Models\InsurancePolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InsuranceService
{
    public function __construct(
        private readonly SequenceGeneratorService $sequences,
        private readonly PaymentService $paymentService
    ) {}

    public function createPolicy(array $data): InsurancePolicy
    {
        return InsurancePolicy::create($data);
    }

    public function submitClaim(array $data): InsuranceClaim
    {
        return InsuranceClaim::create([
            'claim_number' => $this->sequences->next('insurance_claim', 'CLM'),
            'insurance_policy_id' => $data['insurance_policy_id'],
            'bill_id' => $data['bill_id'] ?? null,
            'status' => 'submitted',
            'requested_amount' => $data['requested_amount'],
            'submitted_at' => now(),
            'created_by' => request()->user()?->id,
        ]);
    }

    public function approve(InsuranceClaim $claim, float $approvedAmount): InsuranceClaim
    {
        $this->assertStatus($claim, 'submitted');

        $claim->update(['status' => 'approved', 'approved_amount' => $approvedAmount]);

        return $claim->fresh();
    }

    public function reject(InsuranceClaim $claim, string $reason): InsuranceClaim
    {
        $this->assertStatus($claim, 'submitted');

        $claim->update(['status' => 'rejected', 'rejection_reason' => $reason]);

        return $claim->fresh();
    }

    public function settle(InsuranceClaim $claim, float $settledAmount): InsuranceClaim
    {
        if ($claim->status !== 'approved') {
            throw ValidationException::withMessages([
                'status' => ["Cannot settle a claim with status '{$claim->status}'; it must be approved first."],
            ]);
        }

        return DB::transaction(function () use ($claim, $settledAmount) {
            $claim->update(['status' => 'settled', 'settled_at' => now()]);

            if ($claim->bill_id) {
                $this->paymentService->recordPayment([
                    'bill_id' => $claim->bill_id,
                    'patient_id' => $claim->policy->patient_id,
                    'amount' => $settledAmount,
                    'method' => 'insurance',
                    'reference_number' => $claim->claim_number,
                ]);
            }

            return $claim->fresh();
        });
    }

    private function assertStatus(InsuranceClaim $claim, string $expected): void
    {
        if ($claim->status !== $expected) {
            throw ValidationException::withMessages([
                'status' => ["Cannot act on a claim with status '{$claim->status}'; expected '{$expected}'."],
            ]);
        }
    }
}
