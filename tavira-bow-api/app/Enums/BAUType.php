<?php

namespace App\Enums;

enum BAUType: string
{
    case BAU = 'BAU';
    case NON_BAU = 'Non BAU';

    public function label(): string
    {
        return match ($this) {
            self::BAU => 'Business As Usual',
            self::NON_BAU => 'Transformation / Projet',
        };
    }

    public function isTransformative(): bool
    {
        return $this === self::NON_BAU;
    }
}
