<?php

namespace App\Enums;

enum AppetiteStatus: string
{
    case OK = 'OK';
    case OUTSIDE = 'Outside';

    public function label(): string
    {
        return match ($this) {
            self::OK => 'Within Appetite',
            self::OUTSIDE => 'Outside Appetite',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::OK => 'green',
            self::OUTSIDE => 'red',
        };
    }

    public function isWithinAppetite(): bool
    {
        return $this === self::OK;
    }
}
