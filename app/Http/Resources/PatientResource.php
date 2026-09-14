<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'mrn' => $this->mrn,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->fullName(),
            'dob' => $this->dob?->toDateString(),
            'gender' => $this->gender,
            'mobile' => $this->mobile,
            'email' => $this->email,
            'address' => [
                'line' => $this->address_line,
                'city' => $this->city,
                'state' => $this->state,
                'postal_code' => $this->postal_code,
                'country' => $this->country,
            ],
            'emergency_contact' => [
                'name' => $this->emergency_contact_name,
                'phone' => $this->emergency_contact_phone,
                'relation' => $this->emergency_contact_relation,
            ],
            'blood_group' => $this->blood_group,
            'allergies' => $this->allergies,
            'insurance' => [
                'provider' => $this->insurance_provider,
                'policy_number' => $this->insurance_policy_number,
            ],
            'is_active' => $this->is_active,
            'registered_by' => $this->whenLoaded('registeredBy', fn () => $this->registeredBy?->only(['id', 'name'])),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
