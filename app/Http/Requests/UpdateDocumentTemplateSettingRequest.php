<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentTemplateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'visa_type_id' => ['required', 'exists:visa_types,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'client_instructions' => ['nullable', 'string'],
            'agent_guidance' => ['nullable', 'string'],
            'sample_hint' => ['nullable', 'string', 'max:255'],
            'accepted_file_types' => ['nullable', 'array'],
            'accepted_file_types.*' => ['string', 'max:50'],
            'max_files' => ['required', 'integer', 'min:1', 'max:20'],
            'max_file_size_mb' => ['required', 'integer', 'min:1', 'max:100'],
            'due_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'is_repeatable' => ['sometimes', 'boolean'],
            'position' => ['required', 'integer', 'min:1'],
            'is_required' => ['sometimes', 'boolean'],
            'tracks_expiry' => ['sometimes', 'boolean'],
        ];
    }
}
