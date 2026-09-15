<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'po_number' => $this->po_number,
            'supplier' => $this->whenLoaded('supplier', fn () => [
                'id' => $this->supplier->id,
                'name' => $this->supplier->name,
            ]),
            'order_date' => $this->order_date?->toDateString(),
            'status' => $this->status,
            'total_amount' => $this->total_amount,
            'items' => PurchaseOrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
