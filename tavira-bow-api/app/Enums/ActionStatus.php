<?php

namespace App\Enums;

enum ActionStatus: string
{
    case OPEN = 'Open';
    case IN_PROGRESS = 'In Progress';
    case COMPLETED = 'Completed';
    case OVERDUE = 'Overdue';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Open',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::OVERDUE => 'Overdue',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::OPEN => 'gray',
            self::IN_PROGRESS => 'blue',
            self::COMPLETED => 'green',
            self::OVERDUE => 'red',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::OPEN, self::IN_PROGRESS, self::OVERDUE]);
    }
}
