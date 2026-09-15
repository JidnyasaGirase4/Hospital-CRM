<?php

namespace App\Http\Requests\Billing;

use App\Models\Bill;
use Illuminate\Foundation\Http\FormRequest;

class StoreBillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Bill::class);
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:patients,id'],
            'type' => ['required', 'in:opd,ipd,pharmacy,laboratory,radiology,ot,other'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.category' => ['nullable', 'string', 'max:50'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
