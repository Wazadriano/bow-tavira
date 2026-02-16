<?php

namespace App\Enums;

enum ControlImplementationStatus: string
{
    case PLANNED = 'Planned';
    case IN_PROGRESS = 'In Progress';
    case IMPLEMENTED = 'Implemented';

    public function label(): string
    {
        return match ($this) {
            self::PLANNED => 'Planned',
            self::IN_PROGRESS => 'In Progress',
            self::IMPLEMENTED => 'Implemented',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PLANNED => 'gray',
            self::IN_PROGRESS => 'blue',
            self::IMPLEMENTED => 'green',
        };
    }

    public function isEffective(): bool
    {
        return $this === self::IMPLEMENTED;
    }
}
