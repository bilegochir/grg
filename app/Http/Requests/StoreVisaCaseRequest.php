<?php

namespace App\Http\Requests;

use App\Enums\VisaCasePriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVisaCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'applicant_id' => ['required', 'integer', 'exists:applicants,id'],
            'visa_type_id' => ['required', 'integer', 'exists:visa_types,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'priority' => ['required', Rule::enum(VisaCasePriority::class)],
            'expected_submission_at' => ['nullable', 'date'],
            'expected_decision_at' => ['nullable', 'date', 'after_or_equal:expected_submission_at'],
            'internal_note' => ['nullable', 'string', 'max:5000'],
            'client_note' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
