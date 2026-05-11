<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApplicantNotificationPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email_enabled' => ['sometimes', 'boolean'],
            'sms_enabled' => ['sometimes', 'boolean'],
            'locale' => ['required', 'string', 'max:10'],
            'events.case_status_changes' => ['sometimes', 'boolean'],
            'events.document_requests' => ['sometimes', 'boolean'],
            'events.payment_reminders' => ['sometimes', 'boolean'],
            'events.appointment_reminders' => ['sometimes', 'boolean'],
            'events.messages' => ['sometimes', 'boolean'],
        ];
    }
}
