<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdmissionResource extends JsonResource
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
            'admission_date' => $this->admission_date,
            'discharge_date' => $this->discharge_date,
            'admission_type' => $this->admission_type,
            'reason' => $this->reason,
            'discharge_summary' => $this->discharge_summary,
            'status' => $this->status,
            'current_bed' => $this->whenLoaded('currentBedAllocation', fn () => $this->currentBedAllocation
                ? new BedAllocationResource($this->currentBedAllocation)
                : null),
        ];
    }
}
