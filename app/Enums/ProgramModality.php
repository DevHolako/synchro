<?php

namespace App\Enums;

enum ProgramModality: string
{
    case TempsAmenage = 'temps_amenage';
    case FormationInitiale = 'formation_initiale';

    /**
     * Get human-friendly label.
     */
    public function label(): string
    {
        return match ($this) {
            self::TempsAmenage => 'Temps Aménagé',
            self::FormationInitiale => 'Formation Initiale',
        };
    }

    /**
     * Check if this modality is executive (weekend / evening).
     */
    public function isExecutive(): bool
    {
        return $this === self::TempsAmenage;
    }

    /**
     * Check if this modality is standard daytime.
     */
    public function isStandard(): bool
    {
        return $this === self::FormationInitiale;
    }
}
