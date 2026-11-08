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
            self::Routine => __('statuses.priority.routine'),
            self::Elevated => __('statuses.priority.elevated'),
            self::Urgent => __('statuses.priority.urgent'),
            self::Critical => __('statuses.priority.critical'),
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
