<?php

namespace App\Services;

use App\Enums\TeamStyle;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GameTeam;
use Illuminate\Support\Collection;

class MatchSimulator
{
    // Miroir des constantes du moteur client (resources/js/Pages/Match/engine/constants.js)
    private const MAX_TURNS                       = 45;
    private const MAX_ZONE_INDEX                  = 4;   // 5 zones, index 0..4
    private const ENDURANCE_DEFAULT               = 100;
    private const CRIT_STAMINA_BOOST              = 10;
    private const GOOD_COUNTER_BONUS              = 2;
    private const HOME_BONUS                      = 0.5;
    private const SHOT_DISTANCE_PENALTY_PER_ZONE  = 1;

    public function simulateOtherMatchesOfWeek(GameMatch $playedMatch): void
    {
        $others = GameMatch::query()
            ->where('game_save_id', $playedMatch->game_save_id)
            ->where('week', $playedMatch->week)
            ->where('status', 'scheduled')
            ->where('id', '!=', $playedMatch->id)
            ->with(['homeTeam.contracts.gamePlayer', 'awayTeam.contracts.gamePlayer'])
            ->get();

        foreach ($others as $m) {
            $this->simulateAndSave($m);
        }
    }

    public function simulateMatchesCollection(Collection $matches): void
    {
        $matches->loadMissing(['homeTeam.contracts.gamePlayer', 'awayTeam.contracts.gamePlayer']);

        foreach ($matches as $m) {
            if ($m->status !== 'scheduled') continue;
            $this->simulateAndSave($m);
        }
    }

    // ==========================
    //   SIMULATION PRINCIPALE
    // ==========================
    private function simulateAndSave(GameMatch $m): void
    {
        [$homeScore, $awayScore, $matchStats] = $this->simulateMatch(
            $m->homeTeam,
            $m->awayTeam
        );

        $m->home_score  = $homeScore;
        $m->away_score  = $awayScore;
        $m->status      = 'played';
        $m->match_stats = $matchStats;
        $m->save();

        // Progression post-match pour les joueurs IA
        $gameSave = $m->gameSave ?? \App\Models\GameSaves\GameSave::find($m->game_save_id);
        if ($gameSave) {
            app(PostMatchProgressionService::class)->applyForMatch($gameSave, $m);
        }

        $this->applyResultToStandings($m);
    }

    /**
     * Simule un match complet et retourne [homeScore, awayScore, matchStats].
     * matchStats est au même format que celui produit par engine.js côté client.
     */
    /**
     * Moteur de simulation "complet" en 45 tours, miroir du moteur client
     * (resources/js/Pages/Match/engine/*) : progression par zones (0..4),
     * duels RPS (pierre-feuille-ciseaux) attaque/défense, gestion de la
     * stamina et bonus du domicile. Produit un `match_stats` strictement
     * compatible avec celui généré par `buildMatchStats` côté client
     * (mêmes clés consommées par PlayerStatsService / PostMatchProgressionService / TabCalendar).
     */
    private function simulateMatch(GameTeam $home, GameTeam $away): array
    {
        $homePlayers = $this->getStarters($home);
        $awayPlayers = $this->getStarters($away);

        $homeGK = $this->getGoalkeeper($homePlayers);
        $awayGK = $this->getGoalkeeper($awayPlayers);

        $playerStats = [];
        $stamina     = [];
        foreach ($homePlayers->concat($awayPlayers) as $p) {
            $stamina[(string) $p->id] = (float) self::ENDURANCE_DEFAULT;
        }

        $teamStats = [
            'home' => ['goals' => 0, 'shots' => 0, 'passes' => 0, 'dribbles' => 0, 'duelsWon' => 0, 'duelsLost' => 0],
            'away' => ['goals' => 0, 'shots' => 0, 'passes' => 0, 'dribbles' => 0, 'duelsWon' => 0, 'duelsLost' => 0],
        ];
        $goalEvents = [];
        $events     = [];

        // État du ballon : équipe possédant, index de zone (0..MAX_ZONE_INDEX), face au gardien ?
        $side          = random_int(0, 1) ? 'home' : 'away';
        $zone          = 0;
        $frontOfKeeper = false;

        for ($turn = 1; $turn <= self::MAX_TURNS; $turn++) {
            $isHomeAttacking = $side === 'home';
            $attTeam    = $isHomeAttacking ? $home       : $away;
            $defTeam    = $isHomeAttacking ? $away       : $home;
            $attPlayers = $isHomeAttacking ? $homePlayers: $awayPlayers;
            $defPlayers = $isHomeAttacking ? $awayPlayers: $homePlayers;
            $defGK      = $isHomeAttacking ? $awayGK     : $homeGK;
            $defSide    = $isHomeAttacking ? 'away'      : 'home';

            // ----- Choix de l'action offensive -----
            if ($frontOfKeeper || $zone >= self::MAX_ZONE_INDEX) {
                $action = $frontOfKeeper
                    ? 'shot'
                    : (random_int(1, 10) <= 6 ? 'shot' : 'dribble');
            } else {
                $action = $this->randomOffenseAction($attTeam->tactical_style);
            }

            $attacker = $this->pickPlayerForAction($attPlayers, $action);

            // ----- Choix du défenseur (miroir RPS du client) -----
            if ($action === 'shot') {
                $defender    = $defGK;
                $defAction   = random_int(0, 1) ? 'hands' : 'punch';
                $defCategory = 'defenseGK';
            } else {
                $defAction   = $action === 'pass' ? 'intercept' : 'tackle';
                $defender    = $this->pickPlayerForAction($defPlayers, $defAction);
                $defCategory = 'defenseField';
            }

            // ----- Calcul des scores de duel (RPS + stamina + bonus domicile) -----
            $attackBase = $this->statForAction($attacker, $action, 'attack')
                * $this->staminaFactor($stamina[(string) ($attacker->id ?? 0)] ?? self::ENDURANCE_DEFAULT);

            if ($action === 'shot' && !$frontOfKeeper) {
                $zonesToGoal = self::MAX_ZONE_INDEX - $zone;
                $attackBase -= $zonesToGoal * self::SHOT_DISTANCE_PENALTY_PER_ZONE;
            }

            $defenseBase = $this->statForAction($defender, $defAction, $defCategory)
                * $this->staminaFactor($stamina[(string) ($defender->id ?? 0)] ?? self::ENDURANCE_DEFAULT);

            // RPS : la défense "attendue" (intercept/tackle/gardien) compte comme bon contre
            $defenseBase += self::GOOD_COUNTER_BONUS;

            // Bonus du domicile : ajouté au score de l'équipe à domicile, qu'elle attaque ou défende
            if ($isHomeAttacking) $attackBase  += self::HOME_BONUS;
            else                  $defenseBase += self::HOME_BONUS;

            $attackRoll  = $this->d20();
            $defenseRoll = $this->d20();

            $attackScore  = $attackBase  + $attackRoll;
            $defenseScore = $defenseBase + $defenseRoll;

            // Critique naturel : un 20 force la victoire de ce côté et restaure de la stamina
            $attackCrit  = $attackRoll  === 20;
            $defenseCrit = $defenseRoll === 20;

            if ($attackCrit && !$defenseCrit)      $success = true;
            elseif ($defenseCrit && !$attackCrit)  $success = false;
            else                                   $success = $attackScore > $defenseScore;

            if ($attackCrit)  $stamina[(string) ($attacker->id ?? 0)] = min(self::ENDURANCE_DEFAULT, ($stamina[(string) ($attacker->id ?? 0)] ?? self::ENDURANCE_DEFAULT) + self::CRIT_STAMINA_BOOST);
            if ($defenseCrit) $stamina[(string) ($defender->id ?? 0)] = min(self::ENDURANCE_DEFAULT, ($stamina[(string) ($defender->id ?? 0)] ?? self::ENDURANCE_DEFAULT) + self::CRIT_STAMINA_BOOST);

            $isGoal = $success && $action === 'shot';

            // ----- Déroulé du match (action par action), miroir de `state.matchLog`
            //       côté moteur client : exploité par le résumé de match (TabCalendar). -----
            $events[] = $this->buildEventEntry($turn, $side, $action, $defAction, $success, $isGoal, $attacker, $defender, $zone, $frontOfKeeper);

            // ----- Enregistrement des stats joueurs -----
            if ($attacker) {
                $pid = (string) $attacker->id;
                $playerStats[$pid] = $playerStats[$pid] ?? $this->emptyPlayerStats();
                $playerStats[$pid]['offense'][$action]['attempts']++;
                if ($success) $playerStats[$pid]['offense'][$action]['success']++;
                if ($isGoal) {
                    $playerStats[$pid]['offense']['goals']++;
                    $goalEvents[] = ['player_id' => $attacker->id, 'turn' => $turn];
                }
                $success ? $playerStats[$pid]['duelsWon']++ : $playerStats[$pid]['duelsLost']++;
            }

            if ($defender) {
                $pid = (string) $defender->id;
                $playerStats[$pid] = $playerStats[$pid] ?? $this->emptyPlayerStats();
                $playerStats[$pid]['defense'][$defAction]['attempts']++;
                if (!$success) $playerStats[$pid]['defense'][$defAction]['success']++;
                $success ? $playerStats[$pid]['duelsLost']++ : $playerStats[$pid]['duelsWon']++;
            }

            // ----- Stats d'équipe (tentatives, indépendamment du résultat) -----
            if ($action === 'pass')         $teamStats[$side]['passes']++;
            elseif ($action === 'dribble')  $teamStats[$side]['dribbles']++;
            else                            $teamStats[$side]['shots']++;

            if ($success) {
                $teamStats[$side]['duelsWon']++;
                $teamStats[$defSide]['duelsLost']++;
                if ($isGoal) $teamStats[$side]['goals']++;
            } else {
                $teamStats[$side]['duelsLost']++;
                $teamStats[$defSide]['duelsWon']++;
            }

            // ----- Coût de stamina (action + déplacement de balle) -----
            $this->applyStaminaCost($stamina, $attacker, $action, 'attack');
            $this->applyStaminaCost($stamina, $defender, $defAction, $defCategory);

            // ----- Progression / changement de possession -----
            if ($action === 'shot') {
                // Le ballon repart de zéro côté défenseur, qu'il y ait but ou arrêt
                $side          = $defSide;
                $zone          = 0;
                $frontOfKeeper = false;
            } elseif ($success) {
                if ($action === 'pass') {
                    $zone = min(self::MAX_ZONE_INDEX, $zone + 1);
                } elseif ($action === 'dribble') {
                    if ($zone < self::MAX_ZONE_INDEX) $zone++;
                    else                              $frontOfKeeper = true;
                }
            } else {
                // Perte de balle : possession inversée, la zone est mise en miroir
                $side          = $defSide;
                $zone          = self::MAX_ZONE_INDEX - $zone;
                $frontOfKeeper = false;
            }
        }

        $matchStats = [
            'teams'   => $teamStats,
            'players' => $playerStats,
            'events'  => $events,
        ];

        return [$teamStats['home']['goals'], $teamStats['away']['goals'], $matchStats];
    }

    /**
     * Construit une entrée de "déroulé du match" (action par action), au format
     * miroir de `state.matchLog` côté moteur client (ui.js::pushLogEntry), pour
     * que le résumé de match (TabCalendar) puisse afficher le play-by-play aussi
     * bien pour les matchs simulés par l'IA que pour les matchs joués.
     *
     * Forme : { turn, actionType, team, result, text, details, diceTag }
     */
    private function buildEventEntry(
        int $turn, string $side, string $action, string $defAction,
        bool $success, bool $isGoal, $attacker, $defender, int $zone, bool $frontOfKeeper
    ): array {
        $actionLabels = [
            'pass' => 'Passe', 'dribble' => 'Dribble', 'shot' => 'Tir',
            'intercept' => 'Interception', 'tackle' => 'Tacle',
            'block' => 'Contre', 'hands' => 'Arrêt (mains)', 'punch' => 'Dégagement (poing)',
        ];

        $attackerName = $attacker?->lastname ?? 'Joueur';
        $defenderName = $defender?->lastname ?? 'Adversaire';
        $actionLabel  = $actionLabels[$action] ?? ucfirst($action);

        if ($isGoal) {
            $actionType = 'goal';
            $result     = 'attack';
            $text       = "⚽ BUT ! {$attackerName} marque";
        } elseif ($action === 'shot') {
            $actionType = 'shot';
            $result     = $success ? 'attack' : 'defense';
            $text       = $success
                ? "{$actionLabel} de {$attackerName} — cadré"
                : "{$actionLabel} de {$attackerName} — arrêté par {$defenderName}";
        } else {
            $actionType = $action; // 'pass' | 'dribble'
            $result     = $success ? 'attack' : 'defense';
            $text       = $success
                ? "{$actionLabel} réussi(e) par {$attackerName}"
                : "{$actionLabel} de {$attackerName} contré(e) par {$defenderName}";
        }

        $details = [
            "Zone " . ($zone + 1),
            "Défense : {$actionLabels[$defAction]} ({$defenderName})",
        ];
        if ($frontOfKeeper) $details[] = 'Face au gardien';

        return [
            'turn'       => $turn,
            'actionType' => $actionType,
            'team'       => $side === 'home' ? 'internal' : 'external',
            'result'     => $result,
            'text'       => $text,
            'details'    => $details,
            'diceTag'    => null,
        ];
    }

    // ==========================
    //   HELPERS DUEL / STAMINA
    // ==========================

    /** Retourne la statistique pertinente du joueur pour une action donnée (mêmes clés que STATS côté client). */
    private function statForAction($player, string $action, string $category): float
    {
        if (!$player) return 20.0;

        return match ($category) {
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
     * de stamina restante — paliers identiques à `STAMINA_FACTORS` côté client.
     */
    private function staminaFactor(float $stamina): float
    {
        $ratio = $stamina / self::ENDURANCE_DEFAULT;

        if ($ratio <= 0)    return 0.40; // EXHAUSTED
        if ($ratio < 0.25)  return 0.55; // CRIT
        if ($ratio < 0.50)  return 0.72; // LOW
        if ($ratio < 0.75)  return 0.88; // MID
        return 1.0;                      // HIGH
    }

    /** Coût en stamina d'une action, pondéré par la vitesse du joueur (miroir de `applyStaminaCost`). */
    private function applyStaminaCost(array &$stamina, $player, string $action, string $category): void
    {
        if (!$player) return;

        $costs = match ($category) {
            'attack'       => ['shot' => 10, 'pass' => 6, 'dribble' => 4, 'special' => 15],
            'defenseField' => ['intercept' => 3, 'tackle' => 3, 'block' => 5, 'field-special' => 10],
            'defenseGK'    => ['hands' => 5, 'punch' => 3, 'gk-special' => 10],
            default        => [],
        };
        $baseCost = $costs[$action] ?? 5;

        $categoryMultiplier = $category === 'defenseGK' ? 0.6 : 1.0;
        $speedReduction     = 1 - (((float) ($player->speed ?? 0)) / 100) * 0.1;

        $pid = (string) $player->id;
        $stamina[$pid] = max(0.0, ($stamina[$pid] ?? self::ENDURANCE_DEFAULT) - $baseCost * $categoryMultiplier * $speedReduction);
    }

    /** Sélectionne un joueur de champ adapté à l'action (biaisé vers les profils pertinents, comme côté client). */
    private function pickPlayerForAction(Collection $players, string $action)
    {
        if ($players->isEmpty()) return null;

        $statKey = match ($action) {
            'shot', 'special'     => 'shot',
            'pass'                => 'pass',
            'dribble'             => 'dribble',
            'intercept'           => 'intercept',
            'tackle'              => 'tackle',
            'block'               => 'block',
            default               => null,
        };

        if ($statKey === null) {
            return $players->random();
        }

        $candidates = $players->filter(fn($p) => ($p->{$statKey} ?? 0) > 30);
        $pool       = $candidates->isNotEmpty() ? $candidates : $players;

        return $pool->random();
    }

    // ==========================
    //   HELPERS JOUEURS
    // ==========================

    private function getStarters(GameTeam $team): Collection
    {
        return $team->contracts
            ->filter(fn($c) => $c->is_starter && $c->gamePlayer)
            ->map(fn($c) => $c->gamePlayer)
            ->values()
            ->take(11);
    }

    private function getGoalkeeper(Collection $players)
    {
        // Le gardien = joueur avec le plus de hand_save + punch_save
        return $players->sortByDesc(fn($p) => ($p->hand_save ?? 0) + ($p->punch_save ?? 0))->first();
    }
    /**
     * Retourne la distribution d'actions offensives [pass_weight, dribble_weight, shot_weight]
     * sur 10 selon le style tactique d'une équipe.
     */
    private function offenseDistributionFor(?string $tacticalStyle): array
    {
        return match ($tacticalStyle) {
            TeamStyle::TACTICAL_OFFENSIVE  => [3, 3, 4], // tirs +++
            TeamStyle::TACTICAL_DEFENSIVE  => [5, 3, 2], // passes sécurisées, peu de tirs
            TeamStyle::TACTICAL_POSSESSION => [6, 2, 2], // passes ++++
            TeamStyle::TACTICAL_COUNTER    => [3, 2, 5], // tirs rapides quand la balle arrive
            TeamStyle::TACTICAL_BALANCED   => [4, 3, 3], // distribution actuelle
            default                        => [4, 3, 3],
        };
    }

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
    private function randomOffenseAction(?string $tacticalStyle = null): string
    {
        [$pass, $dribble, $shot] = $this->offenseDistributionFor($tacticalStyle);
        $total = $pass + $dribble + $shot;
        $roll  = random_int(1, $total);
        if ($roll <= $pass)               return 'pass';
        if ($roll <= $pass + $dribble)    return 'dribble';
        return 'shot';
    }

    private function d20(): int
    {
        return random_int(1, 20);
    }

    // ==========================
    //   STANDINGS
    // ==========================

    private function applyResultToStandings(GameMatch $m): void
    {
        if ($m->status !== 'played') return;
        if ($m->home_score === null || $m->away_score === null) return;

        $home = $m->relationLoaded('homeTeam') ? $m->homeTeam : $m->homeTeam()->first();
        $away = $m->relationLoaded('awayTeam') ? $m->awayTeam : $m->awayTeam()->first();

        if (!$home || !$away) return;

        if ($m->home_score > $m->away_score) {
            $home->wins   = ($home->wins   ?? 0) + 1;
            $away->losses = ($away->losses ?? 0) + 1;
        } elseif ($m->home_score < $m->away_score) {
            $away->wins   = ($away->wins   ?? 0) + 1;
            $home->losses = ($home->losses ?? 0) + 1;
        } else {
            $home->draws  = ($home->draws  ?? 0) + 1;
            $away->draws  = ($away->draws  ?? 0) + 1;
        }

        $home->save();
        $away->save();
    }

    // ==========================
    //   STATS VIDES
    // ==========================

    private function emptyPlayerStats(): array
    {
        return [
            'offense' => [
                'pass'    => ['attempts' => 0, 'success' => 0],
                'shot'    => ['attempts' => 0, 'success' => 0],
                'dribble' => ['attempts' => 0, 'success' => 0],
                'special' => ['attempts' => 0, 'success' => 0],
                'goals'   => 0,
            ],
            'defense' => [
                'intercept' => ['attempts' => 0, 'success' => 0],
                'tackle'    => ['attempts' => 0, 'success' => 0],
                'block'     => ['attempts' => 0, 'success' => 0],
                'hands'     => ['attempts' => 0, 'success' => 0],
                'punch'     => ['attempts' => 0, 'success' => 0],
                'gkSpecial' => ['attempts' => 0, 'success' => 0],
            ],
            'duelsWon'  => 0,
            'duelsLost' => 0,
        ];
    }
}
