<?php

namespace App\Http\Requests\Emergency;

use App\Models\EmergencyVisit;
use Illuminate\Foundation\Http\FormRequest;

class RegisterEmergencyVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', EmergencyVisit::class);
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:patients,id'],
            'chief_complaint' => ['nullable', 'string'],
        ];
    }
}
