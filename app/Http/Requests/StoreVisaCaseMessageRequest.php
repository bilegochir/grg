<?php

namespace App\Http\Requests;

use App\Enums\ApplicantNotificationEvent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVisaCaseMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'direction' => ['required', Rule::in(['outbound', 'inbound'])],
            'sender_type' => ['required', Rule::in(['staff', 'applicant', 'system'])],
            'channel' => ['required', Rule::in(['email', 'sms', 'portal'])],
            'notification_event' => ['nullable', Rule::enum(ApplicantNotificationEvent::class)],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:5000', 'required_without:notification_event'],
            'send_notification' => ['sometimes', 'boolean'],
        ];
    }
}
