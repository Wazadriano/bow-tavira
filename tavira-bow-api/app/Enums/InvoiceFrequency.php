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
            self::MONTHLY => 'Monthly',
            self::QUARTERLY => 'Quarterly',
            self::ANNUALLY => 'Annually',
            self::ONE_TIME => 'One Time',
            self::AS_NEEDED => 'As Needed',
        };
    }
}
