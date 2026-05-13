<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVisaTypeSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_country_id' => ['required', 'exists:target_countries,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'official_subclass' => ['nullable', 'string', 'max:50'],
            'slug' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'submission_sla_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'decision_sla_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'validity_months' => ['nullable', 'integer', 'min:0', 'max:240'],
            'stay_duration_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'entry_type' => ['nullable', 'string', 'max:50'],
            'service_scope' => ['nullable', 'string', 'max:100'],
            'priority_support' => ['sometimes', 'boolean'],
            'dependants_allowed' => ['sometimes', 'boolean'],
            'biometrics_required' => ['sometimes', 'boolean'],
            'interview_required' => ['sometimes', 'boolean'],
            'medical_required' => ['sometimes', 'boolean'],
            'police_clearance_required' => ['sometimes', 'boolean'],
            'financial_proof_required' => ['sometimes', 'boolean'],
            'checklist_intro' => ['nullable', 'string', 'max:4000'],
            'portal_guidance' => ['nullable', 'string', 'max:4000'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'official_reference_url' => ['nullable', 'url', 'max:2048'],
            'official_summary' => ['nullable', 'string', 'max:4000'],
            'official_requirements' => ['nullable', 'array', 'max:30'],
            'official_requirements.*' => ['string', 'max:500'],
            'official_last_reviewed_at' => ['nullable', 'date'],
            'policy_effective_date' => ['nullable', 'date'],
            'official_change_notes' => ['nullable', 'string', 'max:4000'],
        ];
    }
}
