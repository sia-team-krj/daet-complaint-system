<?php

namespace App\Enums;

enum ComplaintPriority: string
{
    case Routine = 'routine';
    case Elevated = 'elevated';
    case Urgent = 'urgent';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Routine => 'Routine',
            self::Elevated => 'Elevated',
            self::Urgent => 'Urgent',
            self::Critical => 'Critical',
        };
    }

    public function rank(): int
    {
        return match ($this) {
            self::Routine => 1,
            self::Elevated => 2,
            self::Urgent => 3,
            self::Critical => 4,
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Routine => 'priority-routine',
            self::Elevated => 'priority-elevated',
            self::Urgent => 'priority-urgent',
            self::Critical => 'priority-critical',
        };
    }
}
