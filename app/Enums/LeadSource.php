<?php

namespace App\Enums;

enum LeadSource: string
{
    case Website = 'website';
    case Referral = 'referral';
    case WalkIn = 'walk_in';
    case Social = 'social';

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $source): array => [
                'value' => $source->value,
                'label' => $source->label(),
            ],
            self::cases(),
        );
    }

    public function label(): string
    {
        return match ($this) {
            self::Website => 'Website',
            self::Referral => 'Referral',
            self::WalkIn => 'Walk-in',
            self::Social => 'Social',
        };
    }
}
