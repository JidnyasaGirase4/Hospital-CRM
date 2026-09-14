<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmergencyVisitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient' => $this->whenLoaded('patient', fn () => [
                'id' => $this->patient->id,
                'mrn' => $this->patient->mrn,
                'name' => $this->patient->fullName(),
            ]),
            'doctor' => $this->whenLoaded('doctor', fn () => $this->doctor ? [
                'id' => $this->doctor->id,
                'name' => $this->doctor->name,
            ] : null),
            'triage_level' => $this->triage_level,
            'status' => $this->status,
            'registered_at' => $this->registered_at,
            'chief_complaint' => $this->chief_complaint,
            'treatment_notes' => $this->treatment_notes,
            'referred_to' => $this->referred_to,
            'admission_id' => $this->admission_id,
        ];
    }
}
