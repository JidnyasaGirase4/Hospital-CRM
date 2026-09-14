<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicationAdministrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'medicine' => $this->whenLoaded('medicine', fn () => [
                'id' => $this->medicine->id,
                'name' => $this->medicine->name,
            ]),
            'dose_given' => $this->dose_given,
            'administered_by' => $this->whenLoaded('administeredBy', fn () => $this->administeredBy->only(['id', 'name'])),
            'administered_at' => $this->administered_at,
            'notes' => $this->notes,
        ];
    }
}
