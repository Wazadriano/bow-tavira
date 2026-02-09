<?php

namespace App\Enums;

enum CurrentStatus: string
{
    case NOT_STARTED = 'Not Started';
    case IN_PROGRESS = 'In Progress';
    case COMPLETED = 'Completed';
    case ON_HOLD = 'On Hold';

    public function label(): string
    {
        return match ($this) {
            self::NOT_STARTED => 'Non démarré',
            self::IN_PROGRESS => 'En cours',
            self::COMPLETED => 'Terminé',
            self::ON_HOLD => 'En attente',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NOT_STARTED => 'gray',
            self::IN_PROGRESS => 'blue',
            self::COMPLETED => 'green',
            self::ON_HOLD => 'amber',
        };
    }
}
