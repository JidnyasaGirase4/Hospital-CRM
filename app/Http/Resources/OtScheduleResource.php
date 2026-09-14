<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OtScheduleResource extends JsonResource
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
            'surgeon' => $this->whenLoaded('surgeon', fn () => [
                'id' => $this->surgeon->id,
                'name' => $this->surgeon->name,
            ]),
            'procedure_name' => $this->procedure_name,
            'ot_room' => $this->ot_room,
            'scheduled_at' => $this->scheduled_at,
            'status' => $this->status,
            'notes' => $this->notes,
        ];
    }
}
