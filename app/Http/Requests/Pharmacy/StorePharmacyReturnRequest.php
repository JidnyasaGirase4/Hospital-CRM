<?php

namespace App\Http\Requests\Pharmacy;

use Illuminate\Foundation\Http\FormRequest;

class StorePharmacyReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('return', $this->route('sale'));
    }

    public function rules(): array
    {
        return [
            'pharmacy_sale_item_id' => ['required', 'exists:pharmacy_sale_items,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
