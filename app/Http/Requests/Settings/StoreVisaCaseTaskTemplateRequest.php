<?php

namespace App\Http\Requests\Settings;

use App\Enums\TaskPriority;
use App\Enums\VisaCaseStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVisaCaseTaskTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->agency_id !== null;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'templates' => ['required', 'array', 'min:1'],
            'templates.*.status' => ['required', 'distinct', Rule::enum(VisaCaseStatus::class)],
            'templates.*.tasks' => ['present', 'array'],
            'templates.*.tasks.*.title' => ['required', 'string', 'max:255'],
            'templates.*.tasks.*.description' => ['nullable', 'string', 'max:5000'],
            'templates.*.tasks.*.priority' => ['required', Rule::enum(TaskPriority::class)],
            'templates.*.tasks.*.due_in_days' => ['nullable', 'integer', 'min:0', 'max:365'],
        ];
    }
}
