<?php

namespace App\Enums;

enum AssignmentType: string
{
    case OWNER = 'owner';
    case MEMBER = 'member';

    public function label(): string
    {
        return match ($this) {
            self::OWNER => 'Propriétaire',
            self::MEMBER => 'Membre',
        };
    }

    public function isOwner(): bool
    {
        return $this === self::OWNER;
    }
}
