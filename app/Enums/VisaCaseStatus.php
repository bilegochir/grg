<?php

namespace App\Enums;

enum VisaCaseStatus: string
{
    case Intake = 'intake';
    case DocumentsPending = 'documents_pending';
    case ReadyToFile = 'ready_to_file';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Intake => 'Intake',
            self::DocumentsPending => 'Documents pending',
            self::ReadyToFile => 'Ready to file',
            self::Submitted => 'Submitted',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Closed => 'Closed',
        };
    }

    /**
     * @return list<self>
     */
    public static function active(): array
    {
        return [
            self::Intake,
            self::DocumentsPending,
            self::ReadyToFile,
            self::Submitted,
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $status): array => ['value' => $status->value, 'label' => $status->label()],
            self::cases(),
        );
    }
}
