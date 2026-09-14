<?php

namespace App\Http\Requests\Diagnoses;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDiagnosisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('diagnosis'));
    }

    public function rules(): array
    {
        return [
            'diagnosis_code' => ['nullable', 'string', 'max:50'],
            'diagnosis_name' => ['sometimes', 'required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
