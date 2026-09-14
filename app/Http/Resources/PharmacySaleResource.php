<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PharmacySaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'patient' => $this->whenLoaded('patient', fn () => $this->patient ? [
                'id' => $this->patient->id,
                'mrn' => $this->patient->mrn,
                'name' => $this->patient->fullName(),
            ] : null),
            'prescription_id' => $this->prescription_id,
            'total_amount' => $this->total_amount,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'net_amount' => $this->net_amount,
            'payment_status' => $this->payment_status,
            'status' => $this->status,
            'items' => PharmacySaleItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
        ];
    }
}
