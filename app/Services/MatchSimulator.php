<?php

namespace App\Services;

use App\Enums\TeamStyle;
use App\Helpers\FormationHelper;
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

    // Duel contextuel (miroir de DUEL_CONTEXT côté client) : chaque stat apporte
    // un bonus multiplicatif borné selon le contexte du duel (zone, type d'action).
    // Réglage « subtil » : au plus ~10-15 % de bascule à stat maximale.
    private const ATTACK_ZONE_MAX                 = 0.15; // `attack`  : boost offensif vers le but adverse
    private const DEFENSE_ZONE_MAX                = 0.15; // `defense` : boost défensif en défendant bas
    private const SPEED_DUEL_WEIGHT               = 0.10; // `speed`   : dribble (att.) / tackle + intercept (déf.)
    private const BLOCK_EDGE_MAX                  = 0.12; // `block`   : dernier rempart anti-tir, scalé par la profondeur
    // Usure passive par tour pour chaque titulaire (miroir client). Combinée au
    // `staminaMax = stat stamina`, rend la fatigue de fin de match visible.
    private const PASSIVE_STAMINA_DRAIN_PER_TURN  = 0.4;

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

    public function simulateOtherMatchesOfWeek(GameMatch $playedMatch): void
    {
        $others = GameMatch::query()
            ->where('game_save_id', $playedMatch->game_save_id)
            ->where('week', $playedMatch->week)
            ->where('status', 'scheduled')
            ->where('id', '!=', $playedMatch->id)
            ->with([
                'homeTeam' => fn($q) => $q->with(['contracts' => fn($cq) => $cq->activeAt($playedMatch->week)->with('gamePlayer')]),
                'awayTeam' => fn($q) => $q->with(['contracts' => fn($cq) => $cq->activeAt($playedMatch->week)->with('gamePlayer')]),
            ])
            ->get();

        foreach ($others as $m) {
            $this->simulateAndSave($m);
        }
    }

    public function simulateMatchesCollection(Collection $matches): void
    {
        foreach ($matches as $m) {
            if ($m->status !== 'scheduled') continue;

            $m->loadMissing([
                'homeTeam' => fn($q) => $q->with(['contracts' => fn($cq) => $cq->activeAt($m->week)->with('gamePlayer')]),
                'awayTeam' => fn($q) => $q->with(['contracts' => fn($cq) => $cq->activeAt($m->week)->with('gamePlayer')]),
            ]);

            $this->simulateAndSave($m);
        }
    }

    // ==========================
    //   SIMULATION PRINCIPALE
    // ==========================
    private function simulateAndSave(GameMatch $m): void
    {
        $gameSave = $m->gameSave ?? \App\Models\GameSaves\GameSave::find($m->game_save_id);
        $lineups  = $gameSave?->state['lineup'] ?? [];

        [$homeScore, $awayScore, $matchStats] = $this->simulateMatch(
            $m->homeTeam,
            $m->awayTeam,
            $lineups
        );

        $m->home_score  = $homeScore;
        $m->away_score  = $awayScore;
        $m->status      = 'played';
        $m->match_stats = $matchStats;
        $m->save();

        // Progression post-match pour les joueurs IA
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
    private function simulateMatch(GameTeam $home, GameTeam $away, array $lineups = []): array
    {
        $homePlayers = $this->getStarters($home);
        $awayPlayers = $this->getStarters($away);

        $this->buildAssignedRoles([$home, $away], $lineups);

        $homeGK = $this->getGoalkeeper($homePlayers);
        $awayGK = $this->getGoalkeeper($awayPlayers);

        $playerStats = [];
        $stamina     = [];
        $staminaMax  = [];
        foreach ($homePlayers->concat($awayPlayers) as $p) {
            // Endurance max = stat `stamina` (miroir du client) : un joueur peu
            // endurant atteint plus vite les paliers de fatigue. Repli sur 100.
            $max = (float) (($p->stamina ?? 0) > 0 ? $p->stamina : self::ENDURANCE_DEFAULT);
            $staminaMax[(string) $p->id] = $max;
            $stamina[(string) $p->id]    = $max;
        }

        $teamStats = [
            'home' => ['goals' => 0, 'shots' => 0, 'passes' => 0, 'dribbles' => 0, 'duelsWon' => 0, 'duelsLost' => 0],
            'away' => ['goals' => 0, 'shots' => 0, 'passes' => 0, 'dribbles' => 0, 'duelsWon' => 0, 'duelsLost' => 0],
        ];
        $goalEvents = [];
        $events     = [];
        $heroicUsed = []; // "Dépassement de soi" : une seule fois par joueur et par match

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

            // ----- Usure passive : chaque titulaire perd un peu d'endurance à
            //       chaque tour (en plus des coûts d'action). Placé en tête de
            //       boucle pour s'appliquer aussi aux tours centre/passe longue. -----
            if (self::PASSIVE_STAMINA_DRAIN_PER_TURN > 0) {
                foreach ($homePlayers->concat($awayPlayers) as $p) {
                    $pid = (string) ($p->id ?? 0);
                    if (($stamina[$pid] ?? 0) > 0) {
                        $stamina[$pid] = max(0.0, $stamina[$pid] - self::PASSIVE_STAMINA_DRAIN_PER_TURN);
                    }
                }
            }

            // ----- Choix de l'action offensive -----
            if ($frontOfKeeper || $zone >= self::MAX_ZONE_INDEX) {
                $action = $frontOfKeeper
                    ? 'shot'
                    : (random_int(1, 10) <= 6 ? 'shot' : 'dribble');
            } else {
                $action = $this->randomOffenseAction($attTeam->tactical_style, $zone, $attPlayers);
            }

            // ----- Centre / Passe longue : résolution dédiée (checks multiples) -----
            if ($action === 'cross' || $action === 'long_pass') {
                $this->resolveCrossOrLongPass(
                    $action, $attPlayers, $defPlayers, $defGK, $stamina, $staminaMax,
                    $playerStats, $teamStats, $events, $goalEvents,
                    $side, $defSide, $zone, $frontOfKeeper, $turn, $isHomeAttacking
                );
                continue;
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

            // ----- Calcul des scores de duel (RPS + contexte + stamina + domicile) -----
            // Contexte : `attack` (zone) + `speed` (dribble) côté attaque ;
            //            `defense` (zone) + `speed` (tackle/intercept) côté défense.
            $attackBase = $this->statForAction($attacker, $action, 'attack')
                * $this->attackContextMultiplier($attacker, $action, $zone)
                * $this->staminaFactorOf($attacker, $stamina, $staminaMax);

            if ($action === 'shot' && !$frontOfKeeper) {
                $zonesToGoal = self::MAX_ZONE_INDEX - $zone;
                $attackBase -= $zonesToGoal * self::SHOT_DISTANCE_PENALTY_PER_ZONE;
            }

            $defenseBase = $this->statForAction($defender, $defAction, $defCategory)
                * $this->defenseContextMultiplier($defender, $defAction, $defCategory, $zone)
                * $this->staminaFactorOf($defender, $stamina, $staminaMax);

            // `block` (dernier rempart) : le moteur serveur envoie tous les tirs au
            // gardien (pas de duel de contre dédié), alors le meilleur contreur de
            // champ se jette devant la frappe et renforce l'arrêt — d'autant plus
            // que le tir part de près du but (zone haute).
            if ($action === 'shot') {
                $blocker = $this->fieldPlayers($defPlayers)->sortByDesc(fn($p) => $p->block ?? 0)->first();
                if ($blocker) $defenseBase *= $this->blockEdgeMultiplier($blocker, $zone);
            }

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

            // La récupération est plafonnée à l'endurance MAX du joueur (stat stamina),
            // pas à 100 : sinon un joueur peu endurant — typiquement le gardien, seul
            // défenseur sur chaque tir — se constituerait une réserve au-dessus de son
            // plafond. Miroir du client (resolvers.js applyCritBoost).
            if ($attackCrit) {
                $aid = (string) ($attacker->id ?? 0);
                $stamina[$aid] = min($staminaMax[$aid] ?? self::ENDURANCE_DEFAULT, ($stamina[$aid] ?? self::ENDURANCE_DEFAULT) + self::CRIT_STAMINA_BOOST);
            }
            if ($defenseCrit) {
                $did = (string) ($defender->id ?? 0);
                $stamina[$did] = min($staminaMax[$did] ?? self::ENDURANCE_DEFAULT, ($stamina[$did] ?? self::ENDURANCE_DEFAULT) + self::CRIT_STAMINA_BOOST);
            }

            // ----- Moment héroïque : un joueur très satisfait peut relancer un duel perdu -----
            if (
                !$success && $attacker
                && (int) ($attacker->morale ?? MoraleService::NEUTRAL_MORALE) > MoraleService::HEROIC_MORALE_THRESHOLD
                && empty($heroicUsed[(string) $attacker->id])
                && random_int(1, 100) <= (int) round(MoraleService::HEROIC_CHANCE * 100)
            ) {
                $heroicUsed[(string) $attacker->id] = true;

                $attackRoll  = $this->d20();
                $defenseRoll = $this->d20();
                $attackScore  = $attackBase  + $attackRoll;
                $defenseScore = $defenseBase + $defenseRoll;
                $attackCrit  = $attackRoll  === 20;
                $defenseCrit = $defenseRoll === 20;

                if ($attackCrit && !$defenseCrit)      $success = true;
                elseif ($defenseCrit && !$attackCrit)  $success = false;
                else                                   $success = $attackScore > $defenseScore;

                $events[] = [
                    'turn'       => $turn,
                    'actionType' => 'heroic',
                    'team'       => $side,
                    'result'     => $success ? 'success' : 'fail',
                    'text'       => "🔥 Dépassement de soi — {$attacker->lastname}",
                    'details'    => [$success ? '✓ Le duel est renversé !' : '✗ Le duel reste perdu'],
                    'diceTag'    => round($attackScore, 1) . '-' . round($defenseScore, 1),
                    'zone'            => $zone,
                    'lane'            => null,
                    'front_of_keeper' => $frontOfKeeper,
                    'action'          => $action,
                    'def_action'      => $defAction,
                    'attacker'        => $this->eventPlayerRef($attacker),
                    'defender'        => $this->eventPlayerRef($defender),
                ];
            }

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

        // Temps de jeu (matchs simulés) : pas de remplacements ici, donc chaque
        // titulaire joue l'intégralité des tours, les remplaçants n'entrent pas.
        $starters = [
            'home' => $homePlayers->map(fn($p) => (string) $p->id)->values()->all(),
            'away' => $awayPlayers->map(fn($p) => (string) $p->id)->values()->all(),
        ];
        $playtime = [];
        foreach ($homePlayers->concat($awayPlayers) as $p) {
            $playtime[(string) $p->id] = self::MAX_TURNS;
        }

        $matchStats = [
            'teams'    => $teamStats,
            'players'  => $playerStats,
            'events'   => $events,
            'starters' => $starters,
            'playtime' => $playtime,
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
            // Données structurées pour le replay visuel (positions + acteurs).
            // lane = null : la simulation PHP n'a pas de notion de couloir.
            'zone'            => $zone,
            'lane'            => null,
            'front_of_keeper' => $frontOfKeeper,
            'action'          => $action,
            'def_action'      => $defAction,
            'attacker'        => $this->eventPlayerRef($attacker),
            'defender'        => $this->eventPlayerRef($defender),
        ];
    }

    /** Référence joueur embarquée dans les events du déroulé (replay). */
    private function eventPlayerRef($player): ?array
    {
        if (!$player || !($player->id ?? null)) return null;

        return [
            'id'     => (int) $player->id,
            'name'   => trim(($player->firstname ?? '') . ' ' . ($player->lastname ?? '')),
            'number' => $player->number ?? null,
        ];
    }

    // ==========================
    //   HELPERS DUEL / STAMINA
    // ==========================

    /**
     * Construit la carte joueur → rôle assigné (GK/DF/MF/FW) depuis les slots
     * de lineup sauvegardés. Les joueurs sans slot connu joueront à leur
     * poste naturel (aucun bonus/malus de repositionnement).
     */
    private function buildAssignedRoles(array $teams, array $lineups): void
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
    private function positionMultiplier($player, string $tag): float
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
    private function statForAction($player, string $action, string $category): float
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
    private function staminaFactorOf($player, array $stamina, array $staminaMax): float
    {
        $pid = (string) ($player->id ?? 0);
        return $this->staminaFactor(
            $stamina[$pid]    ?? self::ENDURANCE_DEFAULT,
            $staminaMax[$pid] ?? self::ENDURANCE_DEFAULT,
        );
    }

    // ==========================
    //   MULTIPLICATEURS CONTEXTUELS (rôle distinct par stat)
    // ==========================

    /** Facteur de zone normalisé 0..1 (0 = propre tiers, 1 = près du but visé). */
    private function zoneFactor(int $zone): float
    {
        if (self::MAX_ZONE_INDEX <= 0) return 0.0;
        return max(0.0, min(1.0, $zone / self::MAX_ZONE_INDEX));
    }

    /** `attack` : boost offensif croissant à mesure qu'on progresse vers le but adverse. */
    private function attackZoneMultiplier($player, int $zone): float
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
    private function blockEdgeMultiplier($player, int $zone): float
    {
        return 1.0 + ((float) ($player->block ?? 0) / 100) * self::BLOCK_EDGE_MAX * $this->zoneFactor($zone);
    }

    /** Multiplicateur offensif contextuel : `attack` (zone) + `speed` (dribble). */
    private function attackContextMultiplier($player, string $action, int $zone): float
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
    private function defenseContextMultiplier($player, string $defAction, string $defCategory, int $zone): float
    {
        if ($defCategory === 'defenseGK') return 1.0;

        $mult = $this->defenseZoneMultiplier($player, $zone);
        if ($defAction === 'tackle' || $defAction === 'intercept') {
            $mult *= $this->speedDuelMultiplier($player);
        }
        return $mult;
    }

    /** Coût en stamina d'une action, pondéré par la vitesse du joueur (miroir de `applyStaminaCost`). */
    private function applyStaminaCost(array &$stamina, $player, string $action, string $category): void
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
        $stamina[$pid] = max(0.0, ($stamina[$pid] ?? self::ENDURANCE_DEFAULT) - $baseCost * $categoryMultiplier * $speedReduction);
    }

    /** Le joueur est-il un gardien d'après son poste ? */
    private function isGoalkeeper($player): bool
    {
        return FormationHelper::roleFromPosition($player->position ?? null) === 'GK';
    }

    /**
     * Joueurs de champ (sans gardien), miroir du client qui exclut `.goalkeeper`
     * des sélections de défenseurs/receveurs (engine/field.js, pickWeightedPlayerInZone).
     * Repli sur la collection complète si elle ne contient que des gardiens.
     */
    private function fieldPlayers(Collection $players): Collection
    {
        $field = $players->reject(fn($p) => $this->isGoalkeeper($p));

        return $field->isNotEmpty() ? $field : $players;
    }

    /** Sélectionne un joueur de champ adapté à l'action (biaisé vers les profils pertinents, comme côté client). */
    private function pickPlayerForAction(Collection $players, string $action)
    {
        // Toutes les actions résolues ici sont des actions de champ : le gardien
        // ne doit jamais tacler/intercepter au milieu (ses arrêts passent par $defGK).
        $players = $this->fieldPlayers($players);

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
    private function getPlayerZone(int $zoneIndex): int
    {
        return min(4, $zoneIndex + 1);
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
    /**
     * Choisit l'action offensive en fonction du style tactique et de la zone
     * (rôle) du porteur de balle. Cross/Long pass ne sont disponibles que
     * pour certains rôles (cf. specs Centre / Passe longue).
     */
    private function randomOffenseAction(?string $tacticalStyle, int $zoneIndex, Collection $attPlayers): string
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
        $roll  = random_int(1, $total);
        $acc   = 0;
        foreach ($weights as $action => $weight) {
            $acc += $weight;
            if ($roll <= $acc) return $action;
        }

        return 'pass';
    }

    /**
     * Résout une action "Centre" (MDF/MOF/ATT) ou "Passe longue" (DEF),
     * met à jour les stats joueurs/équipes, le déroulé du match, la stamina
     * et l'état du ballon (zone, possession), miroir du moteur client.
     */
    private function resolveCrossOrLongPass(
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
        $factorOf  = fn($p) => $this->staminaFactorOf($p, $stamina, $staminaMax);
        $homeBonus = $isHomeAttacking ? self::HOME_BONUS : 0.0;
        $awayBonus = $isHomeAttacking ? 0.0 : self::HOME_BONUS;

        if ($action === 'long_pass') {
            $attacker = $this->pickPlayerForAction($attPlayers, 'pass');

            // Cible : meilleur allié de champ par (pass + attack)
            $target = $this->fieldPlayers($attPlayers)
                ->filter(fn($p) => $p->id !== ($attacker->id ?? null))
                ->sortByDesc(fn($p) => ($p->pass ?? 0) + ($p->attack ?? 0))
                ->first() ?? $attacker;

            // Défenseur : meilleur intercepteur adverse (hors gardien)
            $defender = $this->fieldPlayers($defPlayers)->sortByDesc(fn($p) => $p->intercept ?? 0)->first();

            $attackScore  = ($attacker->pass ?? 0) * $this->positionMultiplier($attacker, 'pass') * $this->attackZoneMultiplier($attacker, $zone) * $factorOf($attacker) + $homeBonus + $this->d20();
            $defenseScore = ($defender->intercept ?? 0) * $this->positionMultiplier($defender, 'defend') * $this->defenseContextMultiplier($defender, 'intercept', 'defenseField', $zone) * $factorOf($defender) + self::GOOD_COUNTER_BONUS + $awayBonus + $this->d20();

            $success = $attackScore > $defenseScore;

            if ($attacker) {
                $pid = (string) $attacker->id;
                $playerStats[$pid] = $playerStats[$pid] ?? $this->emptyPlayerStats();
                $playerStats[$pid]['offense']['long_pass']['attempts']++;
                if ($success) $playerStats[$pid]['offense']['long_pass']['success']++;
                $success ? $playerStats[$pid]['duelsWon']++ : $playerStats[$pid]['duelsLost']++;
            }
            if ($defender) {
                $pid = (string) $defender->id;
                $playerStats[$pid] = $playerStats[$pid] ?? $this->emptyPlayerStats();
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

            $this->applyStaminaCost($stamina, $attacker, 'long_pass', 'attack');
            $this->applyStaminaCost($stamina, $defender, 'intercept', 'defenseField');

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
                'attacker'        => $this->eventPlayerRef($attacker),
                'defender'        => $this->eventPlayerRef($defender),
                'receiver'        => $success ? $this->eventPlayerRef($target) : null,
            ];

            if ($success) {
                $zone = 2; // le MOF reçoit en zone 3 (1-indexée)
            } else {
                $side = $defSide;
                $zone = self::MAX_ZONE_INDEX - $zone;
            }
            $frontOfKeeper = false;
            return;
        }

        // ----- Centre -----
        $crosser = $this->pickPlayerForAction($attPlayers, 'pass');
        $crosserScore = (($crosser->pass ?? 0) * 0.7 + ($crosser->attack ?? 0) * 0.3)
            * $this->positionMultiplier($crosser, 'pass')
            * $this->attackZoneMultiplier($crosser, $zone);

        $success = true;
        $defenderForLog = null;

        // MDF (zoneIndex 1 -> rôle MDF) : 1er check vs intercept du MOF adverse
        if ($this->getPlayerZone($zone) === 2) {
            $interceptDef = $this->fieldPlayers($defPlayers)->sortByDesc(fn($p) => $p->intercept ?? 0)->first();
            $defenderForLog = $interceptDef;
            $attackScore  = $crosserScore * $factorOf($crosser) + $homeBonus + $this->d20();
            $defenseScore = ($interceptDef->intercept ?? 0) * $this->positionMultiplier($interceptDef, 'defend') * $this->defenseContextMultiplier($interceptDef, 'intercept', 'defenseField', $zone) * $factorOf($interceptDef) + self::GOOD_COUNTER_BONUS + $awayBonus + $this->d20();
            $success = $attackScore > $defenseScore;

            if ($interceptDef) {
                $pid = (string) $interceptDef->id;
                $playerStats[$pid] = $playerStats[$pid] ?? $this->emptyPlayerStats();
                $playerStats[$pid]['defense']['intercept']['attempts']++;
                if (!$success) $playerStats[$pid]['defense']['intercept']['success']++;
            }
            $this->applyStaminaCost($stamina, $interceptDef, 'intercept', 'defenseField');
        }

        // 2e check (ou seul check pour MOF/ATT) : heading du DEF adverse
        $headingDef = null;
        if ($success) {
            $headingDef = $this->fieldPlayers($defPlayers)->sortByDesc(fn($p) => (($p->heading ?? 0) * 0.8) + (($p->speed ?? 0) * 0.2))->first();
            $defenderForLog = $headingDef ?? $defenderForLog;
            $headingScore = ((($headingDef->heading ?? 0) * 0.8) + (($headingDef->speed ?? 0) * 0.2))
                * $this->positionMultiplier($headingDef, 'defend')
                * $this->defenseContextMultiplier($headingDef, 'heading', 'defenseField', $zone);

            $attackScore  = $crosserScore * $factorOf($crosser) + $homeBonus + $this->d20();
            $defenseScore = $headingScore * $factorOf($headingDef) + self::GOOD_COUNTER_BONUS + $awayBonus + $this->d20();
            $success = $attackScore > $defenseScore;

            if ($headingDef) {
                $pid = (string) $headingDef->id;
                $playerStats[$pid] = $playerStats[$pid] ?? $this->emptyPlayerStats();
                $playerStats[$pid]['defense']['heading']['attempts']++;
                if (!$success) $playerStats[$pid]['defense']['heading']['success']++;
            }
            $this->applyStaminaCost($stamina, $headingDef, 'heading', 'defenseField');
        }

        if ($crosser) {
            $pid = (string) $crosser->id;
            $playerStats[$pid] = $playerStats[$pid] ?? $this->emptyPlayerStats();
            $playerStats[$pid]['offense']['cross']['attempts']++;
            if ($success) $playerStats[$pid]['offense']['cross']['success']++;
            $success ? $playerStats[$pid]['duelsWon']++ : $playerStats[$pid]['duelsLost']++;
        }
        $this->applyStaminaCost($stamina, $crosser, 'cross', 'attack');

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
                'attacker'        => $this->eventPlayerRef($crosser),
                'defender'        => $this->eventPlayerRef($defenderForLog),
            ];
            $side = $defSide;
            $zone = self::MAX_ZONE_INDEX - $zone;
            $frontOfKeeper = false;
            return;
        }

        // Centre réussi : l'ATT receveur (joueur de champ) tire automatiquement avec bonus
        $receiver = $this->fieldPlayers($attPlayers)
            ->sortByDesc(fn($p) => ($p->attack ?? 0) + ($p->shot ?? 0))
            ->first() ?? $crosser;

        $crosserZone = $this->getPlayerZone($zone);
        $receiverIsForward = (string) ($receiver->position ?? '') === 'Forward';
        $crosserIsForward  = (string) ($crosser->position ?? '') === 'Forward';
        $shotBonus = ($crosserZone <= 2)
            ? 1.15
            : (($crosserIsForward && $receiverIsForward) ? 1.10 : 1.0);

        // Reprise dans la surface : `attack` à son plein effet (finition au but).
        $attackScore  = ($receiver->shot ?? 0) * $shotBonus * $this->positionMultiplier($receiver, 'shot') * $this->attackZoneMultiplier($receiver, self::MAX_ZONE_INDEX) * $factorOf($receiver) + $homeBonus + $this->d20();
        $defenseScore = ($defGK->hand_save ?? 0) * 0.9 * $this->positionMultiplier($defGK, 'gk') * $factorOf($defGK) + $awayBonus + $this->d20();

        $isGoal = $attackScore > $defenseScore;

        if ($receiver) {
            $pid = (string) $receiver->id;
            $playerStats[$pid] = $playerStats[$pid] ?? $this->emptyPlayerStats();
            $playerStats[$pid]['offense']['shot']['attempts']++;
            if ($isGoal) {
                $playerStats[$pid]['offense']['shot']['success']++;
                $playerStats[$pid]['offense']['goals']++;
                $goalEvents[] = ['player_id' => $receiver->id, 'turn' => $turn];
            }
        }
        if ($defGK) {
            $pid = (string) $defGK->id;
            $playerStats[$pid] = $playerStats[$pid] ?? $this->emptyPlayerStats();
            $playerStats[$pid]['defense']['hands']['attempts']++;
            if (!$isGoal) $playerStats[$pid]['defense']['hands']['success']++;
        }
        $this->applyStaminaCost($stamina, $receiver, 'shot', 'attack');
        $this->applyStaminaCost($stamina, $defGK, 'hands', 'defenseGK');

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
            'attacker'        => $this->eventPlayerRef($receiver),
            'defender'        => $this->eventPlayerRef($defGK),
            'assist'          => $this->eventPlayerRef($crosser),
        ];

        // La balle repart de zéro côté défenseur, but ou non
        $side          = $defSide;
        $zone          = 0;
        $frontOfKeeper = false;
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

        $home->goals_for     = ($home->goals_for     ?? 0) + $m->home_score;
        $home->goals_against = ($home->goals_against ?? 0) + $m->away_score;
        $away->goals_for     = ($away->goals_for     ?? 0) + $m->away_score;
        $away->goals_against = ($away->goals_against ?? 0) + $m->home_score;

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
                'pass'      => ['attempts' => 0, 'success' => 0],
                'shot'      => ['attempts' => 0, 'success' => 0],
                'dribble'   => ['attempts' => 0, 'success' => 0],
                'special'   => ['attempts' => 0, 'success' => 0],
                'cross'     => ['attempts' => 0, 'success' => 0],
                'long_pass' => ['attempts' => 0, 'success' => 0],
                'goals'     => 0,
            ],
            'defense' => [
                'intercept' => ['attempts' => 0, 'success' => 0],
                'tackle'    => ['attempts' => 0, 'success' => 0],
                'block'     => ['attempts' => 0, 'success' => 0],
                'heading'   => ['attempts' => 0, 'success' => 0],
                'hands'     => ['attempts' => 0, 'success' => 0],
                'punch'     => ['attempts' => 0, 'success' => 0],
                'gkSpecial' => ['attempts' => 0, 'success' => 0],
            ],
            'duelsWon'  => 0,
            'duelsLost' => 0,
        ];
    }
}
