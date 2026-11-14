<?php

namespace App\Services\Matches\Engine;

use App\Contracts\Randomizer;
use App\Enums\TeamStyle;
use Illuminate\Support\Collection;

/**
 * Choix de l'action offensive d'un tour du simulateur : tirage pondéré selon le
 * style tactique de l'équipe et la zone du terrain où se trouve le ballon (un
 * défenseur ne tire pas, un attaquant peut centrer s'il a un partenaire…).
 */
class ActionChooser
{
    /** Reçoit le générateur aléatoire (rejouable avec une graine en test). */
    public function __construct(private Randomizer $random)
    {
    }

    /**
     * Retourne la distribution d'actions offensives [pass_weight, dribble_weight, shot_weight]
     * sur 10 selon le style tactique d'une équipe.
     */
    /**
     * Distribution [pass, dribble, shot, cross] selon le style tactique.
     */
    private function offenseDistributionFor(?string $tacticalStyle): array
    {
        return match ($tacticalStyle) {
            TeamStyle::TACTICAL_OFFENSIVE  => [2, 2, 3, 3], // centre fréquent
            TeamStyle::TACTICAL_DEFENSIVE  => [4, 2, 2, 2], // centre rare
            TeamStyle::TACTICAL_POSSESSION => [5, 2, 1, 2], // préfère construire
            TeamStyle::TACTICAL_COUNTER    => [2, 1, 3, 4], // centre dominant
            TeamStyle::TACTICAL_BALANCED   => [3, 2, 3, 2], // usage normal
            default                        => [3, 2, 3, 2],
        };
    }

    /** Convertit l'index de zone du ballon (0..4) en zone "rôle" 1..4 (DEF..ATT). */
    public function getPlayerZone(int $zoneIndex): int
    {
        return min(4, $zoneIndex + 1);
    }

    /**
     * Répartition [interception, tacle, contre] des actions défensives selon le style tactique.
     * Pas encore utilisée par le simulateur, qui choisit le type de défense d'après l'action
     * attaquante : gardée pour un futur réglage du style défensif.
     */
    private function defenseDistributionFor(?string $tacticalStyle): array
    {
        return match ($tacticalStyle) {
            TeamStyle::TACTICAL_OFFENSIVE  => [3, 2, 1], // intercept >= tackle, peu de blocks
            TeamStyle::TACTICAL_DEFENSIVE  => [2, 2, 2], // équilibré avec bias blocks
            TeamStyle::TACTICAL_POSSESSION => [3, 2, 1], // intercept prioritaire (récup haute)
            TeamStyle::TACTICAL_COUNTER    => [1, 3, 2], // tackle ++ (récup basse pour repartir)
            TeamStyle::TACTICAL_BALANCED   => [2, 2, 2],
            default                        => [2, 2, 2],
        };
    }

    /**
     * Choisit l'action offensive en fonction du style tactique et de la zone
     * (rôle) du porteur de balle. Cross/Long pass ne sont disponibles que
     * pour certains rôles (cf. specs Centre / Passe longue).
     */
    public function randomOffenseAction(?string $tacticalStyle, int $zoneIndex, Collection $attPlayers): string
    {
        [$pass, $dribble, $shot, $cross] = $this->offenseDistributionFor($tacticalStyle);
        $zone = $this->getPlayerZone($zoneIndex);

        $weights = ['pass' => $pass, 'dribble' => $dribble];

        if ($zone === 1) {
            // DEF : pas de tir/centre, mais passe longue possible
            $weights['long_pass'] = $cross;
        } elseif ($zone === 2) {
            // MDF : centre possible, pas de tir
            $weights['cross'] = $cross;
        } elseif ($zone === 3) {
            // MOF : tir + centre
            $weights['shot']  = $shot;
            $weights['cross'] = $cross;
        } else {
            // ATT : tir, et centre si 2+ attaquants titulaires
            $weights['shot'] = $shot;
            $attCount = $attPlayers->filter(fn($p) => (string) ($p->position ?? '') === 'Forward')->count();
            if ($attCount >= 2) $weights['cross'] = $cross;
        }

        $total = array_sum($weights);
        $roll  = $this->random->int(1, $total);
        $acc   = 0;
        foreach ($weights as $action => $weight) {
            $acc += $weight;
            if ($roll <= $acc) return $action;
        }

        return 'pass';
    }
}
