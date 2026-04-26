<?php

namespace App\Http\Requests\Settings;

use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskStatusTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->agency_id !== null && $this->user()->canManageWorkflowSettings();
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'templates' => ['required', 'array', 'size:3'],
            'templates.*.status_key' => ['required', 'distinct', Rule::enum(TaskStatus::class)],
            'templates.*.label' => ['required', 'string', 'max:80'],
        ];
    }
}
