<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LabOrderResource extends JsonResource
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
            'status' => $this->status,
            'ordered_at' => $this->ordered_at,
            'notes' => $this->notes,
            'items' => LabOrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
