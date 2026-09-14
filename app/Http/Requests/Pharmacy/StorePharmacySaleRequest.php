<?php

namespace App\Http\Requests\Pharmacy;

use App\Models\PharmacySale;
use Illuminate\Foundation\Http\FormRequest;

class StorePharmacySaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', PharmacySale::class);
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['nullable', 'exists:patients,id'],
            'prescription_id' => ['nullable', 'exists:prescriptions,id'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.medicine_id' => ['required', 'exists:medicines,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.prescription_item_id' => ['nullable', 'exists:prescription_items,id'],
        ];
    }
}
