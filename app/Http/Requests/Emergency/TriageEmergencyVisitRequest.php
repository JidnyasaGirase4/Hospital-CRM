<?php

namespace App\Http\Requests\Emergency;

use Illuminate\Foundation\Http\FormRequest;

class TriageEmergencyVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('emergency_visit'));
    }

    public function rules(): array
    {
        return [
            'triage_level' => ['required', 'in:critical,urgent,semi-urgent,non-urgent'],
        ];
    }
}
