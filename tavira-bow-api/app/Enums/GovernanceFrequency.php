<?php

namespace App\Enums;

enum GovernanceFrequency: string
{
    case MONTHLY = 'Monthly';
    case QUARTERLY = 'Quarterly';
    case BIANNUALLY = 'Biannually';
    case ANNUALLY = 'Annually';
    case AD_HOC = 'Ad Hoc';

    public function label(): string
    {
        return match ($this) {
            self::MONTHLY => 'Monthly',
            self::QUARTERLY => 'Quarterly',
            self::BIANNUALLY => 'Biannually',
            self::ANNUALLY => 'Annually',
            self::AD_HOC => 'Ad Hoc',
        };
    }

    public function days(): ?int
    {
        return match ($this) {
            self::MONTHLY => 30,
            self::QUARTERLY => 90,
            self::BIANNUALLY => 180,
            self::ANNUALLY => 365,
            self::AD_HOC => null,
        };
    }
}
