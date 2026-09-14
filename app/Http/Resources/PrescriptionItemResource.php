<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrescriptionItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'medicine' => $this->whenLoaded('medicine', fn () => [
                'id' => $this->medicine->id,
                'name' => $this->medicine->name,
                'strength' => $this->medicine->strength,
            ]),
            'dosage' => $this->dosage,
            'frequency' => $this->frequency,
            'route' => $this->route,
            'duration' => $this->duration,
            'timing' => $this->timing,
            'instructions' => $this->instructions,
            'quantity' => $this->quantity,
            'dispensed_quantity' => $this->dispensed_quantity,
            'remaining_quantity' => $this->remainingQuantity(),
        ];
    }
}
