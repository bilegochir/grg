<?php

namespace App\Http\Requests;

use App\Enums\VisaCaseDocumentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVisaCaseDocumentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(VisaCaseDocumentStatus::class)],
            'expiry_date' => ['nullable', 'date'],
            'rejection_reason' => ['nullable', 'string', 'max:5000', 'required_if:status,rejected'],
        ];
    }
}
