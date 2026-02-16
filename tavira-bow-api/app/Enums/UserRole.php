<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case MEMBER = 'member';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrator',
            self::MEMBER => 'Member',
        };
    }

    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }
}
