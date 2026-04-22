<?php

namespace App\Http\Requests;

use App\Enums\VisaRequirementStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVisaCaseRequirementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'is_completed' => ['sometimes', 'boolean'],
            'status' => ['sometimes', Rule::enum(VisaRequirementStatus::class)],
            'due_at' => ['sometimes', 'nullable', 'date'],
            'review_notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
