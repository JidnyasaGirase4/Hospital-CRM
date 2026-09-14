<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OpdVisitResource extends JsonResource
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
            'doctor' => $this->whenLoaded('doctor', fn () => [
                'id' => $this->doctor->id,
                'name' => $this->doctor->name,
            ]),
            'appointment_id' => $this->appointment_id,
            'visit_date' => $this->visit_date,
            'symptoms' => $this->symptoms,
            'vitals' => $this->vitals,
            'diagnosis' => $this->diagnosis,
            'notes' => $this->notes,
            'follow_up_date' => $this->follow_up_date,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
