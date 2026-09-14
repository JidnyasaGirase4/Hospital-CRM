<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NursingNoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'admission_id' => $this->admission_id,
            'nurse' => $this->whenLoaded('nurse', fn () => [
                'id' => $this->nurse->id,
                'name' => $this->nurse->name,
            ]),
            'type' => $this->type,
            'note' => $this->note,
            'shift' => $this->shift,
            'recorded_at' => $this->recorded_at,
        ];
    }
}
