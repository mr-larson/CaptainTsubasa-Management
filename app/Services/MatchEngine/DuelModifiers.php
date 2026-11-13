<?php

namespace App\Services\MatchEngine;

use App\Helpers\FormationHelper;
use App\Services\MoraleService;

/**
 * Calcul de la force d'un joueur dans un duel du simulateur de match : stat utile
 * à l'action, bonus ou malus de poste (joueur placé hors de son poste), moral,
 * fatigue (stamina) et multiplicateurs de contexte (zone du terrain, vitesse,
 * dernier rempart). Ce sont des formules pures, miroir du moteur de match côté
 * client (resources/js/Pages/Match/engine/*) : aucun tirage aléatoire ici.
 *
 * L'instance garde seulement le rôle assigné à chaque joueur pour le match en
 * cours (assignRoles) : en créer une par simulation.
 */
class DuelModifiers
{
    /** Nombre de zones du terrain : index 0 (son camp) à 4 (près du but adverse). */
    public const MAX_ZONE_INDEX = 4;

    /** Endurance de référence d'un joueur dont la stat stamina est inconnue. */
    public const ENDURANCE_DEFAULT = 100;

    /** Points ajoutés à la défense : la défense « attendue » (interception, tacle, gardien) compte comme un bon contre. */
    public const GOOD_COUNTER_BONUS = 2;

    /** Points ajoutés au score de l'équipe qui joue à domicile, qu'elle attaque ou défende. */
    public const HOME_BONUS = 0.5;

    // Duel contextuel (miroir de DUEL_CONTEXT côté client) : chaque stat apporte
    // un bonus multiplicatif borné selon le contexte du duel (zone, type d'action).
    // Réglage « subtil » : au plus ~10-15 % de bascule à stat maximale.
    private const ATTACK_ZONE_MAX                 = 0.15; // `attack`  : boost offensif vers le but adverse
    private const DEFENSE_ZONE_MAX                = 0.15; // `defense` : boost défensif en défendant bas
    private const SPEED_DUEL_WEIGHT               = 0.10; // `speed`   : dribble (att.) / tackle + intercept (déf.)
    private const BLOCK_EDGE_MAX                  = 0.12; // `block`   : dernier rempart anti-tir, scalé par la profondeur

    // Miroir de POSITION_BONUS / SECONDARY_POSITION_BONUS_FACTOR / OFF_POSITION_MALUS (constants.js)
    private const POSITION_BONUS = [
        'GK' => ['gk' => 0.03],
        'DF' => ['defend' => 0.03, 'tackle' => 0.02, 'block' => 0.03],
        'MF' => ['pass' => 0.03, 'dribble' => 0.02],
        'FW' => ['shot' => 0.04, 'attack' => 0.03],
    ];
    private const SECONDARY_POSITION_BONUS_FACTOR = 0.5;
    private const OFF_POSITION_MALUS              = 0.08;

    /** Rôle (GK/DF/MF/FW) assigné par slot de lineup, indexé par id de joueur. */
    private array $assignedRoles = [];

    /**
     * Construit la carte joueur → rôle assigné (GK/DF/MF/FW) depuis les slots
     * de lineup sauvegardés. Les joueurs sans slot connu joueront à leur
     * poste naturel (aucun bonus/malus de repositionnement).
     */
    public function assignRoles(array $teams, array $lineups): void
    {
        $this->assignedRoles = [];

        foreach ($teams as $team) {
            $slots = $lineups[$team->id]['slots'] ?? null;
            if (!is_array($slots)) continue;

            foreach ($slots as $slot => $playerId) {
                if (!$playerId) continue;
                $role = FormationHelper::slotRole($team->formation ?? null, (int) $slot);
                if ($role !== null) {
                    $this->assignedRoles[(string) $playerId] = $role;
                }
            }
        }
    }

    /**
     * Multiplicateur de poste, miroir de RosterService::positionBonusMultiplier :
     * - poste principal  → bonus plein du rôle occupé
     * - poste secondaire → bonus réduit
     * - hors poste       → malus global
     */
    public function positionMultiplier($player, string $tag): float
    {
        if (!$player || !($player->id ?? null)) return 1.0;

        $natural = FormationHelper::roleFromPosition($player->position ?? null);
        $role    = $this->assignedRoles[(string) $player->id] ?? $natural;

        if ($role === null || $natural === null) return 1.0;

        $factor = 1.0;
        if ($role !== $natural) {
            $secondary = array_filter(array_map(
                fn($p) => FormationHelper::roleFromPosition(is_string($p) ? $p : null),
                (array) ($player->secondary_positions ?? [])
            ));
            if (!in_array($role, $secondary, true)) {
                return 1.0 - self::OFF_POSITION_MALUS;
            }
            $factor = self::SECONDARY_POSITION_BONUS_FACTOR;
        }

        $bonus = self::POSITION_BONUS[$role][$tag] ?? 0.0;
        return 1.0 + $bonus * $factor;
    }

    /** Tag de bonus de poste correspondant à une action (miroir des appels client). */
    private function positionTagForAction(string $action, string $category): string
    {
        return match ($category) {
            'attack' => match ($action) {
                'shot'    => 'shot',
                'pass'    => 'pass',
                'dribble' => 'dribble',
                default   => 'attack',
            },
            'defenseField' => $action === 'block' ? 'block' : 'defend',
            'defenseGK'    => 'gk',
            default        => 'attack',
        };
    }

    /** Retourne la statistique pertinente du joueur pour une action donnée (mêmes clés que STATS côté client). */
    public function statForAction($player, string $action, string $category): float
    {
        if (!$player) return 20.0;

        return $this->positionMultiplier($player, $this->positionTagForAction($action, $category))
            * MoraleService::matchFactor($player->morale ?? null)
            * match ($category) {
            'attack' => match ($action) {
                'shot'    => (float) ($player->shot    ?? 0),
                'pass'    => (float) ($player->pass    ?? 0),
                'dribble' => (float) ($player->dribble ?? 0),
                'special' => (float) ($player->attack  ?? 0),
                default   => (float) ($player->attack  ?? 0),
            },
            'defenseField' => match ($action) {
                'intercept' => (float) ($player->intercept ?? 0),
                'tackle'    => (float) ($player->tackle    ?? 0),
                'block'     => (float) ($player->block     ?? 0),
                'heading'   => (float) ($player->heading   ?? 0),
                default     => (float) ($player->defense   ?? 0),
            },
            'defenseGK' => match ($action) {
                'hands' => (float) ($player->hand_save  ?? 0),
                'punch' => (float) ($player->punch_save ?? 0),
                default => (float) ($player->hand_save  ?? 0),
            },
            default => 20.0,
        };
    }

    /**
     * Facteur multiplicatif appliqué à la base d'attaque/défense selon le ratio
     * de stamina restante (relatif à l'endurance max du joueur) — paliers durcis,
     * identiques à `STAMINA_FACTORS` côté client.
     */
    private function staminaFactor(float $stamina, float $max = self::ENDURANCE_DEFAULT): float
    {
        $ratio = $max > 0 ? $stamina / $max : 0.0;

        if ($ratio <= 0)    return 0.35; // EXHAUSTED
        if ($ratio < 0.25)  return 0.50; // CRIT
        if ($ratio < 0.50)  return 0.68; // LOW
        if ($ratio < 0.75)  return 0.85; // MID
        return 1.0;                      // HIGH
    }

    /** Facteur de stamina d'un joueur en lisant les maps d'état (valeur + max). */
    public function staminaFactorOf($player, array $stamina, array $staminaMax): float
    {
        $pid = (string) ($player->id ?? 0);
        return $this->staminaFactor(
            $stamina[$pid]    ?? self::ENDURANCE_DEFAULT,
            $staminaMax[$pid] ?? self::ENDURANCE_DEFAULT,
        );
    }

    /** Facteur de zone normalisé 0..1 (0 = propre tiers, 1 = près du but visé). */
    private function zoneFactor(int $zone): float
    {
        if (self::MAX_ZONE_INDEX <= 0) return 0.0;
        return max(0.0, min(1.0, $zone / self::MAX_ZONE_INDEX));
    }

    /** `attack` : boost offensif croissant à mesure qu'on progresse vers le but adverse. */
    public function attackZoneMultiplier($player, int $zone): float
    {
        return 1.0 + ((float) ($player->attack ?? 0) / 100) * self::ATTACK_ZONE_MAX * $this->zoneFactor($zone);
    }

    /** `defense` : boost défensif croissant à mesure que le ballon menace (zone haute). */
    private function defenseZoneMultiplier($player, int $zone): float
    {
        return 1.0 + ((float) ($player->defense ?? 0) / 100) * self::DEFENSE_ZONE_MAX * $this->zoneFactor($zone);
    }

    /** `speed` : avantage de poursuite — dribble (attaquant), tackle/intercept (défenseur). */
    private function speedDuelMultiplier($player): float
    {
        return 1.0 + ((float) ($player->speed ?? 0) / 100) * self::SPEED_DUEL_WEIGHT;
    }

    /** `block` : dernier rempart anti-tir, d'autant plus fort que la défense est basse. */
    public function blockEdgeMultiplier($player, int $zone): float
    {
        return 1.0 + ((float) ($player->block ?? 0) / 100) * self::BLOCK_EDGE_MAX * $this->zoneFactor($zone);
    }

    /** Multiplicateur offensif contextuel : `attack` (zone) + `speed` (dribble). */
    public function attackContextMultiplier($player, string $action, int $zone): float
    {
        $mult = $this->attackZoneMultiplier($player, $zone);
        if ($action === 'dribble') $mult *= $this->speedDuelMultiplier($player);
        return $mult;
    }

    /**
     * Multiplicateur défensif contextuel : `defense` (zone) + `speed`
     * (tackle/intercept). Le gardien (defenseGK) n'en bénéficie pas — son arrêt
     * peut être renforcé séparément par le `block` d'un coéquipier (cf. tir).
     */
    public function defenseContextMultiplier($player, string $defAction, string $defCategory, int $zone): float
    {
        if ($defCategory === 'defenseGK') return 1.0;

        $mult = $this->defenseZoneMultiplier($player, $zone);
        if ($defAction === 'tackle' || $defAction === 'intercept') {
            $mult *= $this->speedDuelMultiplier($player);
        }
        return $mult;
    }

    /** Coût en stamina d'une action, pondéré par la vitesse du joueur (miroir de `applyStaminaCost`). */
    public function applyStaminaCost(array &$stamina, $player, string $action, string $category, bool $critical = false): void
    {
        if (!$player) return;

        $costs = match ($category) {
            'attack'       => ['shot' => 10, 'pass' => 6, 'dribble' => 4, 'special' => 15, 'cross' => 18, 'long_pass' => 15],
            'defenseField' => ['intercept' => 3, 'tackle' => 3, 'block' => 5, 'heading' => 5, 'field-special' => 10],
            'defenseGK'    => ['hands' => 5, 'punch' => 3, 'gk-special' => 10],
            default        => [],
        };
        $baseCost = $costs[$action] ?? 5;

        $categoryMultiplier = $category === 'defenseGK' ? 0.6 : 1.0;
        $speedReduction     = 1 - (((float) ($player->speed ?? 0)) / 100) * 0.1;

        $pid = (string) $player->id;
        $stamina[$pid] = max(0.0, ($stamina[$pid] ?? self::ENDURANCE_DEFAULT) - $baseCost * $categoryMultiplier * $speedReduction * ($critical ? 0.5 : 1.0));
    }
}
