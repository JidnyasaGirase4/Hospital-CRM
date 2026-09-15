<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InsuranceClaimResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'claim_number' => $this->claim_number,
            'policy' => $this->whenLoaded('policy', fn () => [
                'id' => $this->policy->id,
                'policy_number' => $this->policy->policy_number,
            ]),
            'bill_id' => $this->bill_id,
            'status' => $this->status,
            'requested_amount' => $this->requested_amount,
            'approved_amount' => $this->approved_amount,
            'rejected_amount' => $this->rejected_amount,
            'rejection_reason' => $this->rejection_reason,
            'submitted_at' => $this->submitted_at,
            'settled_at' => $this->settled_at,
        ];
    }
}
