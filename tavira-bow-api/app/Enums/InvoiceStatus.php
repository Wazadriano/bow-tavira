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
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::PAID => 'Paid',
            self::CANCELLED => 'Cancelled',
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
