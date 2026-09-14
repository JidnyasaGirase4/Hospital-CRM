<?php

namespace App\Http\Requests\OpdVisits;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOpdVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('opd_visit'));
    }

    public function rules(): array
    {
        return [
            'symptoms' => ['nullable', 'string'],
            'vitals' => ['nullable', 'array'],
            'diagnosis' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'follow_up_date' => ['nullable', 'date'],
        ];
    }
}
