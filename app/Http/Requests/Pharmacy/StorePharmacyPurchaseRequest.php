<?php

namespace App\Http\Requests\Pharmacy;

use App\Models\PharmacyPurchase;
use Illuminate\Foundation\Http\FormRequest;

class StorePharmacyPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', PharmacyPurchase::class);
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'purchase_date' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.medicine_id' => ['required', 'exists:medicines,id'],
            'items.*.batch_number' => ['required', 'string', 'max:100'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.selling_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.mrp' => ['nullable', 'numeric', 'min:0'],
            'items.*.expiry_date' => ['required', 'date', 'after:today'],
        ];
    }
}
