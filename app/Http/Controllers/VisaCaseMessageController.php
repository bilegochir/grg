<?php

namespace App\Http\Controllers;

use App\Actions\DispatchApplicantCaseNotificationAction;
use App\Actions\RecordActivityAction;
use App\Enums\ApplicantNotificationEvent;
use App\Http\Requests\StoreVisaCaseMessageRequest;
use App\Models\VisaCase;
use Illuminate\Http\RedirectResponse;

class VisaCaseMessageController extends Controller
{
    public function store(
        StoreVisaCaseMessageRequest $request,
        VisaCase $case,
        DispatchApplicantCaseNotificationAction $dispatchNotification,
        RecordActivityAction $recordActivity,
    ): RedirectResponse {
        $this->workspace()->assertCaseAccess($request->user(), $case);

        $direction = $request->string('direction')->toString();
        $senderType = $request->string('sender_type')->toString();
        $channel = $request->string('channel')->toString();
        $sendNotification = $request->boolean('send_notification') && $direction === 'outbound';

        if ($sendNotification) {
            $dispatchNotification->execute(
                $case->loadMissing(['applicant', 'country', 'visaType', 'currentStage']),
                $request->enum('notification_event', ApplicantNotificationEvent::class) ?? ApplicantNotificationEvent::Messages,
                user: $request->user(),
                channels: [$channel],
                customSubject: $request->string('subject')->toString() ?: null,
                customBody: $request->string('body')->toString() ?: null,
                senderType: $senderType,
            );

            return back()->with('success', 'Notification sent and added to the case thread.');
        }

        $message = $case->messages()->create([
            'sent_by_user_id' => $request->user()?->id,
            'sender_type' => $senderType,
            'direction' => $direction,
            'channel' => $channel,
            'notification_event' => $request->enum('notification_event', ApplicantNotificationEvent::class)?->value,
            'subject' => $request->string('subject')->toString() ?: null,
            'body' => $request->string('body')->toString(),
            'metadata' => [],
            'sent_at' => now(),
        ]);

        $recordActivity->execute(
            $case,
            'visa_case.message_logged',
            $direction === 'inbound' ? 'Applicant communication logged.' : 'Case communication added.',
            $request->user(),
            [
                'message_id' => $message->id,
                'channel' => $channel,
            ],
        );

        return back()->with('success', 'Case communication added.');
    }
}
