<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('branches', 'slug')],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('branches', 'code')],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
