<?php

namespace App\Enums;

enum RiskTier: string
{
    case TIER_A = 'Tier A';  // Score >= 9 (High risk)
    case TIER_B = 'Tier B';  // Score 4-8 (Medium risk)
    case TIER_C = 'Tier C';  // Score < 4 (Low risk)

    public function label(): string
    {
        return match ($this) {
            self::TIER_A => 'Tier A - Risque Élevé',
            self::TIER_B => 'Tier B - Risque Moyen',
            self::TIER_C => 'Tier C - Risque Faible',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::TIER_A => 'red',
            self::TIER_B => 'amber',
            self::TIER_C => 'green',
        };
    }

    public static function fromScore(int $score): self
    {
        return match (true) {
            $score >= 9 => self::TIER_A,
            $score >= 4 => self::TIER_B,
            default => self::TIER_C,
        };
    }
}
