<?php

namespace App\Enums;

enum PlayerPosition: string
{
    case Goalkeeper = 'Goalkeeper';
    case Defender   = 'Defender';
    case Midfielder = 'Midfielder';
    case Forward    = 'Forward';

    /**
     * Liste des valeurs brutes (pour Rule::in, Inertia, etc.).
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Labels lisibles (pour affichage dans les selects).
     */
    public static function labels(): array
    {
        return [
            self::Goalkeeper->value => 'Gardien',
            self::Defender->value   => 'Défenseur',
            self::Midfielder->value => 'Milieu',
            self::Forward->value    => 'Attaquant',
        ];
    }
}
