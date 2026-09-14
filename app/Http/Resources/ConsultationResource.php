<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsultationResource extends JsonResource
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
            'opd_visit_id' => $this->opd_visit_id,
            'appointment_id' => $this->appointment_id,
            'chief_complaint' => $this->chief_complaint,
            'symptoms' => $this->symptoms,
            'examination' => $this->examination,
            'diagnosis' => $this->diagnosis,
            'clinical_notes' => $this->clinical_notes,
            'follow_up_date' => $this->follow_up_date,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
