<?php

namespace App\Enums;

enum GovernanceLocation: string
{
    case GLOBAL = 'Global';
    case UK = 'UK';
    case DUBAI = 'Dubai';
    case MONACO = 'Monaco';
    case FRANCE = 'France';
    case SINGAPORE = 'Singapore';
    case AUSTRALIA = 'Australia';

    public function label(): string
    {
        return match ($this) {
            self::GLOBAL => 'Global',
            self::UK => 'UK',
            self::DUBAI => 'Dubai',
            self::MONACO => 'Monaco',
            self::FRANCE => 'France',
            self::SINGAPORE => 'Singapore',
            self::AUSTRALIA => 'Australia',
        };
    }
}
