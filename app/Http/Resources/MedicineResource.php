<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category' => $this->whenLoaded('category', fn () => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ] : null),
            'name' => $this->name,
            'generic_name' => $this->generic_name,
            'manufacturer' => $this->manufacturer,
            'form' => $this->form,
            'strength' => $this->strength,
            'unit' => $this->unit,
            'reorder_level' => $this->reorder_level,
            'allow_negative_stock' => $this->allow_negative_stock,
            'is_active' => $this->is_active,
            'stock_on_hand' => $this->when($this->relationLoaded('batches'), fn () => $this->stockOnHand()),
            'batches' => MedicineBatchResource::collection($this->whenLoaded('batches')),
        ];
    }
}
