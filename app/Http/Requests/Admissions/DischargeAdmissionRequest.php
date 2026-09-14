<?php

namespace App\Http\Requests\Admissions;

use Illuminate\Foundation\Http\FormRequest;

class DischargeAdmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('discharge', $this->route('admission'));
    }

    public function rules(): array
    {
        return [
            'discharge_summary' => ['nullable', 'string'],
        ];
    }
}
