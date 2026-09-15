<?php

namespace App\Http\Requests\Documents;

use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Document::class);
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'in:prescription,lab-report,radiology-report,discharge-summary,insurance,consent,other'],
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ];
    }
}
