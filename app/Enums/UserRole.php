<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case CaseManager = 'case_manager';
    case Staff = 'staff';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::CaseManager => 'Case manager',
            self::Staff => 'Staff',
            self::Viewer => 'Viewer',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $role): array => [
                'value' => $role->value,
                'label' => $role->label(),
            ],
            self::cases(),
        );
    }
}
