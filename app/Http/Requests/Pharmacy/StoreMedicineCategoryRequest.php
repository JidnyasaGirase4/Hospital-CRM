<?php

namespace App\Http\Requests\Pharmacy;

use App\Models\Medicine;
use Illuminate\Foundation\Http\FormRequest;

class StoreMedicineCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Medicine::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:medicine_categories,name'],
            'description' => ['nullable', 'string'],
        ];
    }
}
