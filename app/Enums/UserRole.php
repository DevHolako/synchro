<?php

namespace App\Enums;

enum UserRole: string
{
    case Administrator = 'administrator';
    case Coordinator = 'coordinator';
    case Teacher = 'teacher';
    case Student = 'student';

    /**
     * Get human-friendly label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrator',
            self::Coordinator => 'Coordinator',
            self::Teacher => 'Teacher',
            self::Student => 'Student',
        };
    }

    /**
     * Check if the role can manage referentials.
     */
    public function canManageReferentials(): bool
    {
        return in_array($this, [self::Administrator, self::Coordinator], true);
    }
}
