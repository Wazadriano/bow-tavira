<?php

namespace App\Enums;

enum ImpactLevel: string
{
    case HIGH = 'High';
    case MEDIUM = 'Medium';
    case LOW = 'Low';

    public function label(): string
    {
        return match ($this) {
            self::HIGH => 'Élevé',
            self::MEDIUM => 'Moyen',
            self::LOW => 'Faible',
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

    public function color(): string
    {
        return match ($this) {
            self::HIGH => 'red',
            self::MEDIUM => 'amber',
            self::LOW => 'green',
        };
    }
}
