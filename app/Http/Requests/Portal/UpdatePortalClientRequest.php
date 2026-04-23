<?php

namespace App\Http\Requests\Portal;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePortalClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'passport_number' => ['nullable', 'string', 'max:255'],
            'passport_expiry_date' => ['nullable', 'date'],
            'marital_status' => ['nullable', Rule::in(Client::MARITAL_STATUSES)],
            'occupation' => ['nullable', 'string', 'max:255'],
            'current_address' => ['nullable', 'string', 'max:2000'],
            'nationality' => ['nullable', 'string', 'max:255'],
            'family_members' => ['nullable', 'array'],
            'family_members.*.relationship' => ['nullable', 'string', 'max:255'],
            'family_members.*.full_name' => ['nullable', 'string', 'max:255'],
            'family_members.*.date_of_birth' => ['nullable', 'date'],
            'family_members.*.nationality' => ['nullable', 'string', 'max:255'],
            'family_members.*.occupation' => ['nullable', 'string', 'max:255'],
            'family_members.*.is_accompanying' => ['nullable', 'boolean'],
            'education_history' => ['nullable', 'array'],
            'education_history.*.institution' => ['nullable', 'string', 'max:255'],
            'education_history.*.qualification' => ['nullable', 'string', 'max:255'],
            'education_history.*.field_of_study' => ['nullable', 'string', 'max:255'],
            'education_history.*.country' => ['nullable', 'string', 'max:255'],
            'education_history.*.start_date' => ['nullable', 'date'],
            'education_history.*.end_date' => ['nullable', 'date'],
            'education_history.*.is_current' => ['nullable', 'boolean'],
            'work_experiences' => ['nullable', 'array'],
            'work_experiences.*.employer' => ['nullable', 'string', 'max:255'],
            'work_experiences.*.job_title' => ['nullable', 'string', 'max:255'],
            'work_experiences.*.country' => ['nullable', 'string', 'max:255'],
            'work_experiences.*.start_date' => ['nullable', 'date'],
            'work_experiences.*.end_date' => ['nullable', 'date'],
            'work_experiences.*.is_current' => ['nullable', 'boolean'],
            'work_experiences.*.summary' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
