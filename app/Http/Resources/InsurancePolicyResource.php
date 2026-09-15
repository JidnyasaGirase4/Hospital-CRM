<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InsurancePolicyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'insurance_company' => $this->whenLoaded('insuranceCompany', fn () => [
                'id' => $this->insuranceCompany->id,
                'name' => $this->insuranceCompany->name,
            ]),
            'policy_number' => $this->policy_number,
            'valid_from' => $this->valid_from,
            'valid_till' => $this->valid_till,
            'coverage_amount' => $this->coverage_amount,
        ];
    }
}
