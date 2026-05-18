<?php

namespace App\Support;

use App\Actions\DispatchApplicantCaseNotificationAction;
use App\Enums\ApplicantNotificationEvent;
use App\Models\Invoice;
use App\Models\VisaCase;
use App\Models\VisaCaseAppointment;
use App\Models\VisaCaseDocument;
use App\Models\VisaCaseMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ApplicantPortalReminderService
{
    public function __construct(
        private readonly DispatchApplicantCaseNotificationAction $dispatchNotification,
    ) {
    }

    public function sendDueReminders(): array
    {
        $counts = [
            'documents' => 0,
            'messages' => 0,
            'appointments' => 0,
            'invoices' => 0,
        ];

        $cases = VisaCase::query()
            ->with([
                'applicant',
                'country',
                'visaType',
                'currentStage',
                'documents.latestVersion',
                'messages',
                'appointments',
                'invoices',
            ])
            ->whereNull('closed_at')
            ->get();

        foreach ($cases as $case) {
            if ($this->shouldSendDocumentReminder($case)) {
                $pendingDocuments = $case->documents
                    ->filter(fn (VisaCaseDocument $document) => in_array($document->status->value, ['pending', 'rejected'], true))
                    ->pluck('name')
                    ->take(3)
                    ->implode(', ');

                $this->dispatchNotification->execute(
                    $case,
                    ApplicantNotificationEvent::DocumentRequests,
                    data: [
                        'message_body' => "Please upload or correct the requested documents for your case. Still needed: {$pendingDocuments}.",
                    ],
                    channels: ['email', 'sms'],
                    senderType: 'system',
                );

                $counts['documents']++;
            }

            if ($this->shouldSendUnreadMessageReminder($case)) {
                $this->dispatchNotification->execute(
                    $case,
                    ApplicantNotificationEvent::Messages,
                    data: [
                        'message_body' => 'You have a message from your visa team waiting in the portal. Please open your case to read the latest update.',
                    ],
                    channels: ['email', 'sms'],
                    senderType: 'system',
                );

                $counts['messages']++;
            }

            if ($appointment = $this->appointmentNeedingReminder($case)) {
                $this->dispatchNotification->execute(
                    $case,
                    ApplicantNotificationEvent::AppointmentReminders,
                    data: [
                        'message_body' => sprintf(
                            '%s on %s%s%s',
                            $appointment->title,
                            $appointment->starts_at->toDayDateTimeString(),
                            $appointment->location ? " at {$appointment->location}" : '',
                            $appointment->meeting_link ? ". Join here: {$appointment->meeting_link}" : '',
                        ),
                    ],
                    channels: ['email', 'sms'],
                    senderType: 'system',
                );

                $appointment->forceFill(['reminder_sent_at' => now()])->save();
                $counts['appointments']++;
            }

            if ($invoice = $this->invoiceNeedingReminder($case)) {
                $this->dispatchNotification->execute(
                    $case,
                    ApplicantNotificationEvent::PaymentReminders,
                    data: [
                        'message_body' => sprintf(
                            'Invoice %s has %s %s still due%s.',
                            $invoice->number,
                            $invoice->currency,
                            number_format((float) $invoice->balance_due, 2, '.', ''),
                            $invoice->due_at ? " by {$invoice->due_at->toDateString()}" : '',
                        ),
                    ],
                    channels: ['email', 'sms'],
                    senderType: 'system',
                );

                $invoice->forceFill(['reminder_sent_at' => now()])->save();
                $counts['invoices']++;
            }
        }

        return $counts;
    }

    private function shouldSendDocumentReminder(VisaCase $case): bool
    {
        $documents = $case->documents->filter(fn (VisaCaseDocument $document) => in_array($document->status->value, ['pending', 'rejected'], true));

        if ($documents->isEmpty()) {
            return false;
        }

        $latestDocumentActionAt = $documents
            ->map(fn (VisaCaseDocument $document) => $this->documentRelevantAt($document))
            ->filter()
            ->sortDesc()
            ->first();

        if (! $latestDocumentActionAt || $latestDocumentActionAt->greaterThan(now()->subDay())) {
            return false;
        }

        return ! $this->hasRecentSystemReminder(
            $case->messages,
            ApplicantNotificationEvent::DocumentRequests,
            $latestDocumentActionAt,
            now()->subDays(3),
        );
    }

    private function shouldSendUnreadMessageReminder(VisaCase $case): bool
    {
        $portalSeenAt = $case->applicant->portal_last_seen_at;

        $latestUnreadMessageAt = $case->messages
            ->filter(fn (VisaCaseMessage $message) => $message->direction === 'outbound')
            ->filter(fn (VisaCaseMessage $message) => $message->sender_type !== 'system')
            ->map(fn (VisaCaseMessage $message) => $message->sent_at ?? $message->created_at)
            ->filter(fn ($sentAt) => $sentAt !== null && ($portalSeenAt === null || $sentAt->greaterThan($portalSeenAt)))
            ->sortDesc()
            ->first();

        if (! $latestUnreadMessageAt || $latestUnreadMessageAt->greaterThan(now()->subDay())) {
            return false;
        }

        return ! $this->hasRecentSystemReminder(
            $case->messages,
            ApplicantNotificationEvent::Messages,
            $latestUnreadMessageAt,
            now()->subDays(2),
        );
    }

    private function appointmentNeedingReminder(VisaCase $case): ?VisaCaseAppointment
    {
        return $case->appointments
            ->filter(fn (VisaCaseAppointment $appointment) => $appointment->status === 'scheduled')
            ->filter(fn (VisaCaseAppointment $appointment) => $appointment->starts_at && $appointment->starts_at->between(now(), now()->addDay()))
            ->first(fn (VisaCaseAppointment $appointment) => $appointment->reminder_sent_at === null);
    }

    private function invoiceNeedingReminder(VisaCase $case): ?Invoice
    {
        return $case->invoices
            ->filter(fn (Invoice $invoice) => (float) $invoice->balance_due > 0)
            ->filter(fn (Invoice $invoice) => $invoice->status === 'overdue' || ($invoice->due_at && $invoice->due_at->lessThanOrEqualTo(now()->addDays(2))))
            ->first(function (Invoice $invoice): bool {
                if ($invoice->reminder_sent_at === null) {
                    return true;
                }

                return $invoice->status === 'overdue' && $invoice->reminder_sent_at->lessThan(now()->subDays(3));
            });
    }

    private function hasRecentSystemReminder(
        Collection $messages,
        ApplicantNotificationEvent $event,
        Carbon $relevantAt,
        Carbon $windowStart,
    ): bool {
        return $messages
            ->filter(fn (VisaCaseMessage $message) => $message->sender_type === 'system')
            ->filter(fn (VisaCaseMessage $message) => $message->notification_event === $event->value)
            ->map(fn (VisaCaseMessage $message) => $message->sent_at ?? $message->created_at)
            ->contains(fn ($sentAt) => $sentAt !== null && $sentAt->greaterThan($relevantAt) && $sentAt->greaterThan($windowStart));
    }

    private function documentRelevantAt(VisaCaseDocument $document): ?Carbon
    {
        return collect([
            $document->rejected_at,
            $document->latestVersion?->created_at,
            $document->created_at,
        ])->filter()->sortDesc()->first();
    }
}
