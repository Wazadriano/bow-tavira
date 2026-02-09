<?php

namespace App\Enums;

enum RAGStatus: string
{
    case BLUE = 'Blue';
    case GREEN = 'Green';
    case AMBER = 'Amber';
    case RED = 'Red';

    public function label(): string
    {
        return match ($this) {
            self::BLUE => 'Bleu (Terminé)',
            self::GREEN => 'Vert (OK)',
            self::AMBER => 'Orange (Attention)',
            self::RED => 'Rouge (Critique)',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::BLUE => '#3498db',
            self::GREEN => '#27ae60',
            self::AMBER => '#f39c12',
            self::RED => '#e74c3c',
        };
    }

    public function priority(): int
    {
        return match ($this) {
            self::RED => 4,
            self::AMBER => 3,
            self::GREEN => 2,
            self::BLUE => 1,
        };
    }
}
