<?php

namespace App\Http\Requests\Laboratory;

use App\Models\LabOrder;
use Illuminate\Foundation\Http\FormRequest;

class RecordLabResultsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('recordResults', LabOrder::class);
    }

    public function rules(): array
    {
        return [
            'results' => ['required', 'array', 'min:1'],
            'results.*.parameter_name' => ['required', 'string', 'max:255'],
            'results.*.result_value' => ['required', 'string', 'max:255'],
            'results.*.unit' => ['nullable', 'string', 'max:50'],
            'results.*.reference_range' => ['nullable', 'string', 'max:255'],
            'results.*.flag' => ['nullable', 'in:normal,low,high,critical'],
        ];
    }
}
