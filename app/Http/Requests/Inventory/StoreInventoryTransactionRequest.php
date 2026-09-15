<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('inventory_item'));
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:purchase,issue,return,adjustment,transfer'],
            'quantity' => ['required', 'integer', 'not_in:0'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
