<?php

namespace App\Services\Matches\Engine;

use App\Contracts\Randomizer;
use Illuminate\Support\Collection;

/**
 * Résolution d'une action « Centre » (milieux et attaquants) ou « Passe longue »
 * (défenseurs) dans le simulateur : plusieurs vérifications enchaînées (le
 * centre trouve-t-il un partenaire, la tête, le tir, l'arrêt du gardien…), mise à
 * jour des bilans de joueurs et d'équipes, du déroulé, de la stamina et de l'état
 * du ballon (zone, possession). Miroir du moteur de match côté client.
 *
 * Reçoit les mêmes tableaux d'état que la boucle principale de MatchSimulator, par
 * référence, et les modifie directement.
 */
class CrossResolver
{
    /**
     * Reçoit le générateur aléatoire et les briques de calcul partagées avec le simulateur
     * (les MÊMES DuelModifiers, qui portent les rôles assignés du match en cours).
     */
    public function __construct(
        private Randomizer $random,
        private DuelModifiers $mods,
        private MatchRoster $roster,
        private ActionChooser $chooser,
    ) {
    }

    /** Jet de dé à 20 faces (via le générateur injecté, rejouable avec une graine). */
    private function d20(): int
    {
        return $this->random->int(1, 20);
    }

    /**
     * Résout une action "Centre" (MDF/MOF/ATT) ou "Passe longue" (DEF),
     * met à jour les stats joueurs/équipes, le déroulé du match, la stamina
     * et l'état du ballon (zone, possession), miroir du moteur client.
     */
    public function resolve(
        string $action,
        Collection $attPlayers,
        Collection $defPlayers,
        $defGK,
        array &$stamina,
        array $staminaMax,
        array &$playerStats,
        array &$teamStats,
        array &$events,
        array &$goalEvents,
        string &$side,
        string $defSide,
        int &$zone,
        bool &$frontOfKeeper,
        int $turn,
        bool $isHomeAttacking
    ): void {
        $factorOf  = fn($p) => $this->mods->staminaFactorOf($p, $stamina, $staminaMax);
        $homeBonus = $isHomeAttacking ? DuelModifiers::HOME_BONUS : 0.0;
        $awayBonus = $isHomeAttacking ? 0.0 : DuelModifiers::HOME_BONUS;

        if ($action === 'long_pass') {
            $attacker = $this->roster->pickPlayerForAction($attPlayers, 'pass');

            // Cible : meilleur allié de champ par (pass + attack)
            $target = $this->roster->fieldPlayers($attPlayers)
                ->filter(fn($p) => $p->id !== ($attacker->id ?? null))
                ->sortByDesc(fn($p) => ($p->pass ?? 0) + ($p->attack ?? 0))
                ->first() ?? $attacker;

            // Défenseur : meilleur intercepteur adverse (hors gardien)
            $defender = $this->roster->fieldPlayers($defPlayers)->sortByDesc(fn($p) => $p->intercept ?? 0)->first();

            $attackScore  = ($attacker->pass ?? 0) * $this->mods->positionMultiplier($attacker, 'pass') * $this->mods->attackZoneMultiplier($attacker, $zone) * $factorOf($attacker) + $homeBonus + $this->d20();
            $defenseScore = ($defender->intercept ?? 0) * $this->mods->positionMultiplier($defender, 'defend') * $this->mods->defenseContextMultiplier($defender, 'intercept', 'defenseField', $zone) * $factorOf($defender) + DuelModifiers::GOOD_COUNTER_BONUS + $awayBonus + $this->d20();

            $success = $attackScore > $defenseScore;

            if ($attacker) {
                $pid = (string) $attacker->id;
                $playerStats[$pid] = $playerStats[$pid] ?? MatchStats::emptyPlayerStats();
                $playerStats[$pid]['offense']['long_pass']['attempts']++;
                if ($success) $playerStats[$pid]['offense']['long_pass']['success']++;
                $success ? $playerStats[$pid]['duelsWon']++ : $playerStats[$pid]['duelsLost']++;
            }
            if ($defender) {
                $pid = (string) $defender->id;
                $playerStats[$pid] = $playerStats[$pid] ?? MatchStats::emptyPlayerStats();
                $playerStats[$pid]['defense']['intercept']['attempts']++;
                if (!$success) $playerStats[$pid]['defense']['intercept']['success']++;
                $success ? $playerStats[$pid]['duelsLost']++ : $playerStats[$pid]['duelsWon']++;
            }

            $teamStats[$side]['passes']++;
            if ($success) {
                $teamStats[$side]['duelsWon']++;
                $teamStats[$defSide]['duelsLost']++;
            } else {
                $teamStats[$side]['duelsLost']++;
                $teamStats[$defSide]['duelsWon']++;
            }

            $this->mods->applyStaminaCost($stamina, $attacker, 'long_pass', 'attack');
            $this->mods->applyStaminaCost($stamina, $defender, 'intercept', 'defenseField');

            $events[] = [
                'turn'       => $turn,
                'actionType' => 'long_pass',
                'team'       => $side === 'home' ? 'internal' : 'external',
                'result'     => $success ? 'attack' : 'defense',
                'text'       => $success
                    ? "Passe longue de {$attacker?->lastname} trouve {$target?->lastname}"
                    : "Passe longue de {$attacker?->lastname} interceptée par {$defender?->lastname}",
                'details'    => ['Zone ' . ($zone + 1)],
                'diceTag'    => null,
                'zone'            => $zone,
                'lane'            => null,
                'front_of_keeper' => false,
                'action'          => 'long_pass',
                'def_action'      => 'intercept',
                'attacker'        => MatchStats::eventPlayerRef($attacker),
                'defender'        => MatchStats::eventPlayerRef($defender),
                'receiver'        => $success ? MatchStats::eventPlayerRef($target) : null,
            ];

            if ($success) {
                $zone = 2; // le MOF reçoit en zone 3 (1-indexée)
            } else {
                $side = $defSide;
                $zone = DuelModifiers::MAX_ZONE_INDEX - $zone;
            }
            $frontOfKeeper = false;
            return;
        }

        // ----- Centre -----
        $crosser = $this->roster->pickPlayerForAction($attPlayers, 'pass');
        $crosserScore = (($crosser->pass ?? 0) * 0.7 + ($crosser->attack ?? 0) * 0.3)
            * $this->mods->positionMultiplier($crosser, 'pass')
            * $this->mods->attackZoneMultiplier($crosser, $zone);

        $success = true;
        $defenderForLog = null;

        // MDF (zoneIndex 1 -> rôle MDF) : 1er check vs intercept du MOF adverse
        if ($this->chooser->getPlayerZone($zone) === 2) {
            $interceptDef = $this->roster->fieldPlayers($defPlayers)->sortByDesc(fn($p) => $p->intercept ?? 0)->first();
            $defenderForLog = $interceptDef;
            $attackScore  = $crosserScore * $factorOf($crosser) + $homeBonus + $this->d20();
            $defenseScore = ($interceptDef->intercept ?? 0) * $this->mods->positionMultiplier($interceptDef, 'defend') * $this->mods->defenseContextMultiplier($interceptDef, 'intercept', 'defenseField', $zone) * $factorOf($interceptDef) + $awayBonus + $this->d20();
            $success = $attackScore > $defenseScore;

            if ($interceptDef) {
                $pid = (string) $interceptDef->id;
                $playerStats[$pid] = $playerStats[$pid] ?? MatchStats::emptyPlayerStats();
                $playerStats[$pid]['defense']['intercept']['attempts']++;
                if (!$success) $playerStats[$pid]['defense']['intercept']['success']++;
            }
            $this->mods->applyStaminaCost($stamina, $interceptDef, 'intercept', 'defenseField');
        }

        // 2e check (ou seul check pour MOF/ATT) : heading du DEF adverse
        $headingDef = null;
        if ($success) {
            $headingDef = $this->roster->fieldPlayers($defPlayers)->sortByDesc(fn($p) => (($p->heading ?? 0) * 0.8) + (($p->speed ?? 0) * 0.2))->first();
            $defenderForLog = $headingDef ?? $defenderForLog;
            $headingScore = ((($headingDef->heading ?? 0) * 0.8) + (($headingDef->speed ?? 0) * 0.2))
                * $this->mods->positionMultiplier($headingDef, 'defend')
                * $this->mods->defenseContextMultiplier($headingDef, 'heading', 'defenseField', $zone);

            $attackScore  = $crosserScore * $factorOf($crosser) + $homeBonus + $this->d20();
            $defenseScore = $headingScore * $factorOf($headingDef) + $awayBonus + $this->d20();
            $success = $attackScore > $defenseScore;

            if ($headingDef) {
                $pid = (string) $headingDef->id;
                $playerStats[$pid] = $playerStats[$pid] ?? MatchStats::emptyPlayerStats();
                $playerStats[$pid]['defense']['heading']['attempts']++;
                if (!$success) $playerStats[$pid]['defense']['heading']['success']++;
            }
            $this->mods->applyStaminaCost($stamina, $headingDef, 'heading', 'defenseField');
        }

        if ($crosser) {
            $pid = (string) $crosser->id;
            $playerStats[$pid] = $playerStats[$pid] ?? MatchStats::emptyPlayerStats();
            $playerStats[$pid]['offense']['cross']['attempts']++;
            if ($success) $playerStats[$pid]['offense']['cross']['success']++;
            $success ? $playerStats[$pid]['duelsWon']++ : $playerStats[$pid]['duelsLost']++;
        }
        $this->mods->applyStaminaCost($stamina, $crosser, 'cross', 'attack');

        $teamStats[$side]['passes']++;
        if ($success) {
            $teamStats[$side]['duelsWon']++;
            $teamStats[$defSide]['duelsLost']++;
        } else {
            $teamStats[$side]['duelsLost']++;
            $teamStats[$defSide]['duelsWon']++;
        }

        if (!$success) {
            $events[] = [
                'turn'       => $turn,
                'actionType' => 'cross',
                'team'       => $side === 'home' ? 'internal' : 'external',
                'result'     => 'defense',
                'text'       => "Centre de {$crosser?->lastname} dégagé par {$defenderForLog?->lastname}",
                'details'    => ['Zone ' . ($zone + 1)],
                'diceTag'    => null,
                'zone'            => $zone,
                'lane'            => null,
                'front_of_keeper' => false,
                'action'          => 'cross',
                'def_action'      => 'heading',
                'attacker'        => MatchStats::eventPlayerRef($crosser),
                'defender'        => MatchStats::eventPlayerRef($defenderForLog),
            ];
            $side = $defSide;
            $zone = DuelModifiers::MAX_ZONE_INDEX - $zone;
            $frontOfKeeper = false;
            return;
        }

        // Centre réussi : l'ATT receveur (joueur de champ) tire automatiquement avec bonus
        $receiver = $this->roster->fieldPlayers($attPlayers)
            ->sortByDesc(fn($p) => ($p->attack ?? 0) + ($p->shot ?? 0))
            ->first() ?? $crosser;

        $crosserZone = $this->chooser->getPlayerZone($zone);
        $receiverIsForward = (string) ($receiver->position ?? '') === 'Forward';
        $crosserIsForward  = (string) ($crosser->position ?? '') === 'Forward';
        $shotBonus = ($crosserZone <= 2)
            ? 1.15
            : (($crosserIsForward && $receiverIsForward) ? 1.10 : 1.0);

        // Reprise dans la surface : `attack` à son plein effet (finition au but).
        $attackScore  = ($receiver->shot ?? 0) * $shotBonus * $this->mods->positionMultiplier($receiver, 'shot') * $this->mods->attackZoneMultiplier($receiver, DuelModifiers::MAX_ZONE_INDEX) * $factorOf($receiver) + $homeBonus + $this->d20();
        $defenseScore = ($defGK->hand_save ?? 0) * 0.9 * $this->mods->positionMultiplier($defGK, 'gk') * $factorOf($defGK) + $awayBonus + $this->d20();

        $isGoal = $attackScore > $defenseScore;

        if ($receiver) {
            $pid = (string) $receiver->id;
            $playerStats[$pid] = $playerStats[$pid] ?? MatchStats::emptyPlayerStats();
            $playerStats[$pid]['offense']['shot']['attempts']++;
            if ($isGoal) {
                $playerStats[$pid]['offense']['shot']['success']++;
                $playerStats[$pid]['offense']['goals']++;
                $goalEvents[] = ['player_id' => $receiver->id, 'turn' => $turn];
            }
        }
        if ($defGK) {
            $pid = (string) $defGK->id;
            $playerStats[$pid] = $playerStats[$pid] ?? MatchStats::emptyPlayerStats();
            $playerStats[$pid]['defense']['hands']['attempts']++;
            if (!$isGoal) $playerStats[$pid]['defense']['hands']['success']++;
        }
        $this->mods->applyStaminaCost($stamina, $receiver, 'shot', 'attack');
        $this->mods->applyStaminaCost($stamina, $defGK, 'hands', 'defenseGK');

        if ($isGoal) {
            $teamStats[$side]['goals']++;
            $teamStats[$side]['shots']++;
            $teamStats[$side]['duelsWon']++;
            $teamStats[$defSide]['duelsLost']++;
        } else {
            $teamStats[$side]['shots']++;
            $teamStats[$side]['duelsLost']++;
            $teamStats[$defSide]['duelsWon']++;
        }

        $events[] = [
            'turn'       => $turn,
            'actionType' => $isGoal ? 'goal' : 'shot',
            'team'       => $side === 'home' ? 'internal' : 'external',
            'result'     => $isGoal ? 'attack' : 'defense',
            'text'       => $isGoal
                ? "⚽ BUT ! Centre de {$crosser?->lastname} repris par {$receiver?->lastname}"
                : "Centre de {$crosser?->lastname} repris par {$receiver?->lastname} — arrêté",
            'details'    => ['Zone ' . ($zone + 1)],
            'diceTag'    => null,
            'zone'            => $zone,
            'lane'            => null,
            'front_of_keeper' => true,
            'action'          => 'shot',
            'def_action'      => 'hands',
            'attacker'        => MatchStats::eventPlayerRef($receiver),
            'defender'        => MatchStats::eventPlayerRef($defGK),
            'assist'          => MatchStats::eventPlayerRef($crosser),
        ];

        // La balle repart de zéro côté défenseur, but ou non
        $side          = $defSide;
        $zone          = 0;
        $frontOfKeeper = false;
    }
}
