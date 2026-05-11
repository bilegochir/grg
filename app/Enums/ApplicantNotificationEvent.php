<?php

namespace App\Enums;

enum ApplicantNotificationEvent: string
{
    case CaseStatusChanges = 'case_status_changes';
    case DocumentRequests = 'document_requests';
    case PaymentReminders = 'payment_reminders';
    case AppointmentReminders = 'appointment_reminders';
    case Messages = 'messages';

    public static function options(): array
    {
        return array_map(
            fn (self $event): array => [
                'value' => $event->value,
                'label' => $event->label(),
            ],
            self::cases(),
        );
    }

    public function label(): string
    {
        return match ($this) {
            self::CaseStatusChanges => 'Case status changes',
            self::DocumentRequests => 'Document requests',
            self::PaymentReminders => 'Payment reminders',
            self::AppointmentReminders => 'Appointment reminders',
            self::Messages => 'General case messages',
        };
    }

    public function templateKey(): string
    {
        return match ($this) {
            self::CaseStatusChanges => 'case-status-change',
            self::DocumentRequests => 'document-request',
            self::PaymentReminders => 'payment-reminder',
            self::AppointmentReminders => 'appointment-reminder',
            self::Messages => 'case-message',
        };
    }
}
