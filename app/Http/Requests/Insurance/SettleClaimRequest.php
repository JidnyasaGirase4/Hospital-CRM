<?php

namespace App\Http\Requests\Insurance;

use App\Models\InsuranceClaim;
use Illuminate\Foundation\Http\FormRequest;

class SettleClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('approve', InsuranceClaim::class);
    }

    public function rules(): array
    {
        return [
            'settled_amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
