<?php

namespace App\Enums;

/**
 * Phase d'une sauvegarde. Stockée en chaîne (colonne game_saves.phase) et
 * exposée telle quelle au front.
 */
enum GamePhase: string
{
    // Ligue
    case Draft            = 'draft';
    case Season           = 'season';
    case SeasonEnd        = 'season_end';
    case IntersaisonDraft = 'intersaison_draft';

    // Coupe du Monde
    case WorldCup = 'world_cup';
    case Group    = 'group';
    case Knockout = 'knockout';
    case Finished = 'finished';

    /** Draft initial ou d'intersaison : l'effectif se monte. */
    public function isDraft(): bool
    {
        return $this === self::Draft || $this === self::IntersaisonDraft;
    }

    /** Phases où des matchs se jouent (semaines simulables). */
    public function isMatchPhase(): bool
    {
        return in_array($this, self::matchPhases(), true);
    }

    /** @return list<self> */
    public static function matchPhases(): array
    {
        return [self::Season, self::WorldCup, self::Group, self::Knockout];
    }
}
