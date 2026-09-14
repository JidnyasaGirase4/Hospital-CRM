<?php

namespace App\Http\Requests\Beds;

use App\Models\Ward;
use Illuminate\Foundation\Http\FormRequest;

class StoreWardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Ward::class);
    }

    public function rules(): array
    {
        return [
            'department_id' => ['nullable', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:255'],
            'floor' => ['nullable', 'string', 'max:50'],
            'ward_type' => ['nullable', 'string', 'max:100'],
        ];
    }
}
