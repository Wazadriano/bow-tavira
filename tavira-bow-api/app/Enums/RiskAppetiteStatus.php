<?php

namespace App\Enums;

enum RiskAppetiteStatus: string
{
    case WITHIN = 'Within';
    case APPROACHING = 'Approaching';
    case EXCEEDED = 'Exceeded';

    public function label(): string
    {
        return match ($this) {
            self::WITHIN => 'Within Appetite',
            self::APPROACHING => 'Approaching Threshold',
            self::EXCEEDED => 'Exceeded Appetite',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::WITHIN => 'green',
            self::APPROACHING => 'amber',
            self::EXCEEDED => 'red',
        };
    }
}
