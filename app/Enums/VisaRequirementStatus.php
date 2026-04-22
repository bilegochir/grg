<?php

namespace App\Enums;

enum VisaRequirementStatus: string
{
    case Pending = 'pending';
    case Requested = 'requested';
    case Received = 'received';
    case Verified = 'verified';
    case Waived = 'waived';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Requested => 'Requested',
            self::Received => 'Received',
            self::Verified => 'Verified',
            self::Waived => 'Waived',
        };
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
