<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case MEMBER = 'member';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrateur',
            self::MEMBER => 'Membre',
        };
    }

    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }
}
