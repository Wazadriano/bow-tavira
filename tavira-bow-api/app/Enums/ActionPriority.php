<?php

namespace App\Enums;

enum ActionPriority: string
{
    case HIGH = 'High';
    case MEDIUM = 'Medium';
    case LOW = 'Low';

    public function label(): string
    {
        return match ($this) {
            self::HIGH => 'High',
            self::MEDIUM => 'Medium',
            self::LOW => 'Low',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::HIGH => 'red',
            self::MEDIUM => 'amber',
            self::LOW => 'green',
        };
    }

    public function value(): int
    {
        return match ($this) {
            self::HIGH => 3,
            self::MEDIUM => 2,
            self::LOW => 1,
        };
    }
}
