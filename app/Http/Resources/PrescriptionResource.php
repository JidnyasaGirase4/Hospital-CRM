<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrescriptionResource extends JsonResource
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
            'consultation_id' => $this->consultation_id,
            'status' => $this->status,
            'notes' => $this->notes,
            'items' => PrescriptionItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
        ];
    }
}
