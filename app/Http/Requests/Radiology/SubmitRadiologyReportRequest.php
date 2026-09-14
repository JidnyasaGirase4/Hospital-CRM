<?php

namespace App\Http\Requests\Radiology;

use Illuminate\Foundation\Http\FormRequest;

class SubmitRadiologyReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('radiology_order'));
    }

    public function rules(): array
    {
        return [
            'findings' => ['nullable', 'string'],
            'impression' => ['nullable', 'string'],
        ];
    }
}
