<?php

namespace App\Http\Requests\Pharmacy;

use App\Models\Medicine;
use Illuminate\Foundation\Http\FormRequest;

class StoreMedicineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Medicine::class);
    }

    public function rules(): array
    {
        return [
            'medicine_category_id' => ['nullable', 'exists:medicine_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'generic_name' => ['nullable', 'string', 'max:255'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'form' => ['nullable', 'string', 'max:100'],
            'strength' => ['nullable', 'string', 'max:100'],
            'unit' => ['nullable', 'string', 'max:50'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],
            'allow_negative_stock' => ['boolean'],
        ];
    }
}
