<?php

namespace App\Http\Requests;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'source' => ['required', Rule::enum(LeadSource::class)],
            'status' => ['nullable', Rule::enum(LeadStatus::class)],
            'country_of_citizenship' => ['nullable', 'string', 'max:100'],
            'interested_visa_type' => ['nullable', 'string', 'max:150'],
            'education_history' => ['nullable', 'array'],
            'education_history.*.institution' => ['nullable', 'string', 'max:150'],
            'education_history.*.degree' => ['nullable', 'string', 'max:150'],
            'education_history.*.field_of_study' => ['nullable', 'string', 'max:150'],
            'education_history.*.start_date' => ['nullable', 'date'],
            'education_history.*.end_date' => ['nullable', 'date'],
            'education_history.*.notes' => ['nullable', 'string', 'max:1000'],
            'work_experience' => ['nullable', 'array'],
            'work_experience.*.company' => ['nullable', 'string', 'max:150'],
            'work_experience.*.title' => ['nullable', 'string', 'max:150'],
            'work_experience.*.location' => ['nullable', 'string', 'max:150'],
            'work_experience.*.start_date' => ['nullable', 'date'],
            'work_experience.*.end_date' => ['nullable', 'date'],
            'work_experience.*.is_current' => ['nullable', 'boolean'],
            'work_experience.*.notes' => ['nullable', 'string', 'max:1000'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'note' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
