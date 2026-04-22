<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreVisaRequirementTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'region' => ['required', 'in:Europe,Australia,Asia'],
            'country_name' => ['required', 'string', 'max:255'],
            'visa_type' => ['required', 'string', 'max:255'],
            'visa_code' => ['nullable', 'string', 'max:50'],
            'requires_institution_name' => ['required', 'boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
            'source_url' => ['nullable', 'url', 'max:255'],
            'source_checked_at' => ['nullable', 'date'],
            'processing_time_summary' => ['nullable', 'string', 'max:255'],
            'fee_summary' => ['nullable', 'string', 'max:255'],
            'stay_summary' => ['nullable', 'string', 'max:255'],
            'requirements' => ['required', 'array', 'min:1'],
            'requirements.*.category' => ['nullable', 'string', 'max:100'],
            'requirements.*.label' => ['required', 'string', 'max:255'],
            'requirements.*.help_text' => ['nullable', 'string', 'max:500'],
            'requirements.*.is_required' => ['required', 'boolean'],
        ];
    }
}
