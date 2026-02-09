<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case PENDING = 'Pending';
    case APPROVED = 'Approved';
    case PAID = 'Paid';
    case CANCELLED = 'Cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::APPROVED => 'Approuvée',
            self::PAID => 'Payée',
            self::CANCELLED => 'Annulée',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'amber',
            self::APPROVED => 'blue',
            self::PAID => 'green',
            self::CANCELLED => 'gray',
        };
    }
}
