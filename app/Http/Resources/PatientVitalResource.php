<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientVitalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'admission_id' => $this->admission_id,
            'temperature_c' => $this->temperature_c,
            'pulse' => $this->pulse,
            'bp_systolic' => $this->bp_systolic,
            'bp_diastolic' => $this->bp_diastolic,
            'respiratory_rate' => $this->respiratory_rate,
            'spo2' => $this->spo2,
            'recorded_at' => $this->recorded_at,
        ];
    }
}
