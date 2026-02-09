<?php

namespace App\Enums;

enum InvoiceFrequency: string
{
    case MONTHLY = 'Monthly';
    case QUARTERLY = 'Quarterly';
    case ANNUALLY = 'Annually';
    case ONE_TIME = 'One Time';
    case AS_NEEDED = 'As Needed';

    public function label(): string
    {
        return match ($this) {
            self::MONTHLY => 'Mensuel',
            self::QUARTERLY => 'Trimestriel',
            self::ANNUALLY => 'Annuel',
            self::ONE_TIME => 'Unique',
            self::AS_NEEDED => 'Sur demande',
        };
    }
}
