<?php

namespace App\Enums;

enum SupplierStatus: string
{
    case ACTIVE = 'Active';
    case EXITED = 'Exited';
    case PENDING = 'Pending';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::EXITED => 'Exited',
            self::PENDING => 'Pending',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'green',
            self::EXITED => 'gray',
            self::PENDING => 'amber',
        };
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }
}
