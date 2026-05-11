<?php

namespace App\Enums;

enum VisaCasePriority: string
{
    case Normal = 'normal';
    case Urgent = 'urgent';
    case Vip = 'vip';

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $priority): array => [
                'value' => $priority->value,
                'label' => $priority->label(),
            ],
            self::cases(),
        );
    }

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::Urgent => 'Urgent',
            self::Vip => 'VIP',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Normal => 'slate',
            self::Urgent => 'amber',
            self::Vip => 'violet',
        };
    }
}
