<?php

namespace App\Enums;

enum UpdateFrequency: string
{
    case ANNUALLY = 'Annually';
    case SEMI_ANNUALLY = 'Semi Annually';
    case QUARTERLY = 'Quarterly';
    case MONTHLY = 'Monthly';
    case WEEKLY = 'Weekly';

    public function label(): string
    {
        return match ($this) {
            self::ANNUALLY => 'Annually',
            self::SEMI_ANNUALLY => 'Semi-Annually',
            self::QUARTERLY => 'Quarterly',
            self::MONTHLY => 'Monthly',
            self::WEEKLY => 'Weekly',
        };
    }

    public function days(): int
    {
        return match ($this) {
            self::ANNUALLY => 365,
            self::SEMI_ANNUALLY => 180,
            self::QUARTERLY => 90,
            self::MONTHLY => 30,
            self::WEEKLY => 7,
        };
    }
}
