<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RadiologyOrderResource extends JsonResource
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
            'test' => $this->whenLoaded('test', fn () => [
                'id' => $this->test->id,
                'name' => $this->test->name,
                'modality' => $this->test->modality,
            ]),
            'status' => $this->status,
            'ordered_at' => $this->ordered_at,
            'notes' => $this->notes,
            'report' => new RadiologyReportResource($this->whenLoaded('report')),
        ];
    }
}
