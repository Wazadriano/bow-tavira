<?php

namespace App\Enums;

enum CurrentStatus: string
{
    case NOT_STARTED = 'Not Started';
    case IN_PROGRESS = 'In Progress';
    case COMPLETED = 'Completed';
    case ON_HOLD = 'On Hold';

    public function label(): string
    {
        return match ($this) {
            self::NOT_STARTED => 'Not Started',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::ON_HOLD => 'On Hold',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NOT_STARTED => 'gray',
            self::IN_PROGRESS => 'blue',
            self::COMPLETED => 'green',
            self::ON_HOLD => 'amber',
        };
    }
}
