<?php

namespace App\Actions;

use App\Enums\ApplicantNotificationEvent;
use App\Models\Applicant;
use App\Models\BusinessSetting;
use App\Models\CommunicationTemplate;
use App\Models\User;
use App\Models\VisaCase;
use App\Models\VisaCaseMessage;
use App\Notifications\CaseCommunicationNotification;
use App\Support\CommunicationTemplateRenderer;

class DispatchApplicantCaseNotificationAction
{
    public function __construct(
        private readonly CommunicationTemplateRenderer $renderer,
        private readonly RecordActivityAction $recordActivity,
    ) {
    }

    /**
     * @param  array<int, string>  $channels
     * @return array<int, VisaCaseMessage>
     */
    public function execute(
        VisaCase $visaCase,
        ApplicantNotificationEvent $event,
        array $data = [],
        ?User $user = null,
        array $channels = ['email', 'sms'],
        ?string $customSubject = null,
        ?string $customBody = null,
        string $senderType = 'staff',
    ): array {
        $applicant = $visaCase->applicant;
        $locale = $applicant->notificationPreferences()['locale'] ?? BusinessSetting::current()->default_locale;
        $templateData = [
            'applicant_name' => $applicant->full_name,
            'case_reference' => $visaCase->reference_code,
            'country_name' => $visaCase->country->name,
            'visa_type' => $visaCase->visaType->name,
            'stage_name' => $visaCase->currentStage?->name ?? 'Updated',
            'agent_name' => $user?->name ?? 'Agency',
            'message_body' => $customBody ?? '',
        ] + $data;

        $messages = [];

        foreach ($channels as $channel) {
            if (! $applicant->wantsNotification($event, $channel)) {
                continue;
            }

            if ($channel === 'email' && blank($applicant->email)) {
                continue;
            }

            if ($channel === 'sms' && blank($applicant->phone)) {
                continue;
            }

            $template = $this->resolveTemplate($event, $channel, $locale);
            $subject = $customSubject
                ?: ($channel === 'email' ? $this->renderer->render($template['subject'], $templateData) : null);
            $body = $this->renderer->render($customBody ?: $template['body'], $templateData);

            $notification = new CaseCommunicationNotification(
                $channel === 'email' ? $subject : null,
                $channel === 'email' ? $body : null,
                $channel === 'sms' ? $body : null,
                BusinessSetting::current()->sms_sender,
            );

            $applicant->notify($notification);

            $messages[] = $visaCase->messages()->create([
                'sent_by_user_id' => $user?->id,
                'sender_type' => $senderType,
                'direction' => 'outbound',
                'channel' => $channel,
                'notification_event' => $event->value,
                'subject' => $subject,
                'body' => $body,
                'metadata' => [
                    'locale' => $locale,
                ],
                'sent_at' => now(),
            ]);
        }

        if ($messages !== []) {
            $this->recordActivity->execute(
                $visaCase,
                'visa_case.notification_sent',
                sprintf('%s sent to applicant.', $event->label()),
                $user,
                [
                    'event' => $event->value,
                    'channels' => array_values(array_map(fn (VisaCaseMessage $message) => $message->channel, $messages)),
                ],
            );
        }

        return $messages;
    }

    private function resolveTemplate(ApplicantNotificationEvent $event, string $channel, string $locale): array
    {
        $template = CommunicationTemplate::query()
            ->where('key', $event->templateKey())
            ->where('channel', $channel)
            ->where('locale', $locale)
            ->where('is_active', true)
            ->first();

        if ($template) {
            return [
                'subject' => $template->subject ?: $this->fallbackSubject($event),
                'body' => $template->body,
            ];
        }

        return [
            'subject' => $this->fallbackSubject($event),
            'body' => $this->fallbackBody($event),
        ];
    }

    private function fallbackSubject(ApplicantNotificationEvent $event): string
    {
        return match ($event) {
            ApplicantNotificationEvent::CaseStatusChanges => 'Your visa case has moved forward',
            ApplicantNotificationEvent::DocumentRequests => 'We need a document from you',
            ApplicantNotificationEvent::PaymentReminders => 'A payment reminder for your visa case',
            ApplicantNotificationEvent::AppointmentReminders => 'An appointment reminder for your visa case',
            ApplicantNotificationEvent::Messages => 'A new update from your visa team',
        };
    }

    private function fallbackBody(ApplicantNotificationEvent $event): string
    {
        return match ($event) {
            ApplicantNotificationEvent::CaseStatusChanges => "Hi {{applicant_name}}, your case {{case_reference}} is now at {{stage_name}} for your {{visa_type}} application to {{country_name}}.",
            ApplicantNotificationEvent::DocumentRequests => "Hi {{applicant_name}}, we need an additional document for case {{case_reference}}. {{message_body}}",
            ApplicantNotificationEvent::PaymentReminders => "Hi {{applicant_name}}, this is a reminder about an upcoming payment related to case {{case_reference}}. {{message_body}}",
            ApplicantNotificationEvent::AppointmentReminders => "Hi {{applicant_name}}, this is a reminder about your appointment for case {{case_reference}}. {{message_body}}",
            ApplicantNotificationEvent::Messages => "Hi {{applicant_name}}, your visa team sent an update for case {{case_reference}}. {{message_body}}",
        };
    }
}
