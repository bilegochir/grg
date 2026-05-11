<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConvertLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'country_of_residence' => ['nullable', 'string', 'max:100'],
            'passport_number' => ['nullable', 'string', 'max:100'],
            'passport_country' => ['nullable', 'string', 'max:100'],
            'passport_issued_at' => ['nullable', 'date'],
            'passport_expires_at' => ['nullable', 'date', 'after:passport_issued_at'],
            'travel_history' => ['nullable', 'array'],
            'travel_history.*.country' => ['required_with:travel_history', 'string', 'max:100'],
            'travel_history.*.purpose' => ['nullable', 'string', 'max:150'],
            'travel_history.*.year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
        ];
    }
}
