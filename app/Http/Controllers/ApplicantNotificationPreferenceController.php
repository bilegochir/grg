<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateApplicantNotificationPreferencesRequest;
use App\Models\Applicant;
use Illuminate\Http\RedirectResponse;

class ApplicantNotificationPreferenceController extends Controller
{
    public function update(
        UpdateApplicantNotificationPreferencesRequest $request,
        Applicant $applicant,
    ): RedirectResponse {
        $this->workspace()->assertApplicantAccess($request->user(), $applicant);

        $applicant->update([
            'notification_preferences' => [
                'email_enabled' => $request->boolean('email_enabled'),
                'sms_enabled' => $request->boolean('sms_enabled'),
                'locale' => $request->string('locale')->toString(),
                'events' => [
                    'case_status_changes' => $request->boolean('events.case_status_changes'),
                    'document_requests' => $request->boolean('events.document_requests'),
                    'payment_reminders' => $request->boolean('events.payment_reminders'),
                    'appointment_reminders' => $request->boolean('events.appointment_reminders'),
                    'messages' => $request->boolean('events.messages'),
                ],
            ],
        ]);

        return back()->with('success', 'Notification preferences updated.');
    }
}
