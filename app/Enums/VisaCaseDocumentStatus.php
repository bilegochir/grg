<?php

namespace App\Enums;

enum VisaCaseDocumentStatus: string
{
    case Pending = 'pending';
    case Uploaded = 'uploaded';
    case Verified = 'verified';
    case Rejected = 'rejected';

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            self::cases(),
        );
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Uploaded => 'Uploaded',
            self::Verified => 'Verified',
            self::Rejected => 'Rejected',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Uploaded => 'blue',
            self::Verified => 'emerald',
            self::Rejected => 'rose',
        };
    }
}
