<?php

namespace App\Http\Requests\Emergency;

use Illuminate\Foundation\Http\FormRequest;

class ReferEmergencyVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('emergency_visit'));
    }

    public function rules(): array
    {
        return [
            'referred_to' => ['required', 'string', 'max:255'],
        ];
    }
}
