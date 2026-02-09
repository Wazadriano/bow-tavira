<?php

namespace App\Enums;

enum SupplierLocation: string
{
    case LONDON = 'London';
    case MONACO = 'Monaco';
    case DUBAI = 'Dubai';
    case AUSTRALIA = 'Australia';
    case GLOBAL = 'Global';
    case SINGAPORE = 'Singapore';
    case FRANCE = 'France';

    public function label(): string
    {
        return match ($this) {
            self::LONDON => 'Londres',
            self::MONACO => 'Monaco',
            self::DUBAI => 'Dubaï',
            self::AUSTRALIA => 'Australie',
            self::GLOBAL => 'Global',
            self::SINGAPORE => 'Singapour',
            self::FRANCE => 'France',
        };
    }
}
