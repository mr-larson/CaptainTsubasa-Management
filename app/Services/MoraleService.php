<?php

namespace App\Services;

use App\Models\GameSaves\GameInjury;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GamePlayerMoraleLog;
use App\Models\GameSaves\GameSanction;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;

/**
 * Moral des joueurs — phase 1 : résultats, temps de jeu, salaire.
 * Appliqué chaque semaine après les matchs (mêmes flux que StaminaService).
 */
class MoraleService
{
    const NEUTRAL_MORALE = 60;
    const DECAY_RATE     = 0.05; // retour progressif vers le neutre chaque semaine

    // Résultats sportifs
    const WIN_DELTA          = 2;
    const LOSS_DELTA         = -2;
    const CAPTAIN_MULTIPLIER = 1.5;

    // Temps de jeu (attente selon is_starter)
    const PLAYED_STARTER_DELTA = 1;
    const PLAYED_BENCH_DELTA   = 2;
    const MISSED_STARTER_DELTA = -3;
    const MISSED_BENCH_DELTA   = -1;

    // Salaire vs valeur du joueur (adjusted_cost)
    const SALARY_LOW_RATIO   = 0.8;
    const SALARY_HIGH_RATIO  = 1.2;
    const SALARY_LOW_DELTA   = -1;
    const SALARY_HIGH_DELTA  = 1;

    // Effets en match — paliers alignés sur MORALE_FACTORS côté client (constants.js)
    const REVOLTED_MAX  = 20;
    const UNHAPPY_MAX   = 40;
    const NEUTRAL_MAX   = 60;
    const SATISFIED_MAX = 80;

    // Moment héroïque ("Dépassement de soi")
    const HEROIC_MORALE_THRESHOLD = 85;  // moral strictement supérieur
    const HEROIC_CHANCE           = 0.05;

    // Relation avec l'entraîneur (coach_affinity, -100 → +100)
    const AFFINITY_REFUSAL_THRESHOLD = -50;  // refuse de re-signer en dessous
    const AFFINITY_LOSS_THRESHOLD    = -30;  // les défaites pèsent plus lourd en dessous
    const AFFINITY_LOSS_MULTIPLIER   = 1.25;
    const AFFINITY_SALARY_TOLERANCE  = 50;   // au-dessus, tolère un salaire bas sans malus

    /**
     * Facteur appliqué aux bases d'attaque/défense en match
     * (miroir de RosterService.moraleFactor côté client).
     */
    public static function matchFactor(?int $morale): float
    {
        $m = $morale ?? self::NEUTRAL_MORALE;
        if ($m <= self::REVOLTED_MAX)  return 0.90;
        if ($m <= self::UNHAPPY_MAX)   return 0.95;
        if ($m <= self::NEUTRAL_MAX)   return 1.0;
        if ($m <= self::SATISFIED_MAX) return 1.02;
        return 1.05;
    }

    /** Facteur appliqué aux gains de progression post-match. */
    public static function xpFactor(?int $morale): float
    {
        $m = $morale ?? self::NEUTRAL_MORALE;
        if ($m <= self::REVOLTED_MAX)   return 0.8;
        if ($m <= self::UNHAPPY_MAX)    return 0.9;
        if ($m >  self::SATISFIED_MAX)  return 1.1;
        return 1.0;
    }

    /**
     * Un joueur refuse de re-signer avec son club actuel s'il est révolté
     * (moral) ou fâché contre le coach (affinité).
     */
    public static function refusesToSign(GamePlayer $player): bool
    {
        return ((int) ($player->morale ?? self::NEUTRAL_MORALE)) <= self::REVOLTED_MAX
            || ((int) ($player->coach_affinity ?? 0)) <= self::AFFINITY_REFUSAL_THRESHOLD;
    }

    public function applyAfterWeek(GameSave $gameSave, int $week): void
    {
        $matches = GameMatch::with(['homeTeam', 'awayTeam'])
            ->where('game_save_id', $gameSave->id)
            ->where('week', $week)
            ->where('status', 'played')
            ->get();

        if ($matches->isEmpty()) return;

        $season = (int) ($gameSave->season ?? 1);

        // Résultat + adversaire + joueurs ayant joué, indexés par équipe
        $teamContext = [];
        foreach ($matches as $match) {
            $playedIds = self::resolvePlayedPlayerIds($match);

            foreach ([['home', $match->homeTeam, $match->awayTeam, $match->home_score, $match->away_score],
                      ['away', $match->awayTeam, $match->homeTeam, $match->away_score, $match->home_score]] as [$side, $team, $opponent, $for, $against]) {
                if (!$team) continue;
                $teamContext[$team->id] = [
                    'result'    => $for <=> $against, // 1 victoire, 0 nul, -1 défaite
                    'opponent'  => $opponent?->name ?? 'Inconnu',
                    'playedIds' => $playedIds,
                ];
            }
        }

        $unavailableIds = $this->resolveUnavailablePlayerIds($gameSave, $week);

        $teams = GameTeam::where('game_save_id', $gameSave->id)
            ->with(['contracts' => fn($q) => $q->activeAt($week)->with('gamePlayer')])
            ->get();

        $logs = [];
        $now  = now();

        foreach ($teams as $team) {
            $context = $teamContext[$team->id] ?? null;

            foreach ($team->contracts as $contract) {
                $player = $contract->gamePlayer;
                if (!$player) continue;

                $events      = [];
                $unavailable = isset($unavailableIds[$player->id]);

                if ($context) {
                    // 1. Résultat du match (capitaine plus impacté)
                    $resultDelta = $context['result'] > 0 ? self::WIN_DELTA
                        : ($context['result'] < 0 ? self::LOSS_DELTA : 0);
                    if ($resultDelta !== 0 && $contract->is_captain) {
                        $resultDelta = (int) round($resultDelta * self::CAPTAIN_MULTIPLIER);
                    }
                    // Fâché contre le coach : les défaites pèsent plus lourd
                    if ($resultDelta < 0 && (int) ($player->coach_affinity ?? 0) <= self::AFFINITY_LOSS_THRESHOLD) {
                        $resultDelta = (int) round($resultDelta * self::AFFINITY_LOSS_MULTIPLIER);
                    }
                    if ($resultDelta !== 0) {
                        $events[] = [
                            'source' => 'result',
                            'value'  => $resultDelta,
                            'label'  => ($context['result'] > 0 ? 'Victoire contre ' : 'Défaite contre ') . $context['opponent'],
                        ];
                    }

                    // 2. Temps de jeu — gelé si blessé ou suspendu
                    if (!$unavailable) {
                        $hasPlayed = isset($context['playedIds'][(string) $player->id]);

                        if ($hasPlayed) {
                            $events[] = [
                                'source' => 'playing_time',
                                'value'  => $contract->is_starter ? self::PLAYED_STARTER_DELTA : self::PLAYED_BENCH_DELTA,
                                'label'  => 'A joué contre ' . $context['opponent'],
                            ];
                        } else {
                            $events[] = [
                                'source' => 'playing_time',
                                'value'  => $contract->is_starter ? self::MISSED_STARTER_DELTA : self::MISSED_BENCH_DELTA,
                                'label'  => $contract->is_starter ? 'Titulaire laissé de côté' : 'Resté sur le banc',
                            ];
                        }
                    }
                }

                // 3. Salaire vs valeur (adjusted_cost = référence salariale du jeu)
                $expectedSalary = max(1, (int) $player->adjusted_cost);
                $ratio          = ((int) $contract->salary) / $expectedSalary;

                // Une excellente relation avec le coach fait accepter un salaire bas
                $toleratesLowSalary = (int) ($player->coach_affinity ?? 0) >= self::AFFINITY_SALARY_TOLERANCE;

                if ($ratio < self::SALARY_LOW_RATIO && !$toleratesLowSalary) {
                    $events[] = ['source' => 'salary', 'value' => self::SALARY_LOW_DELTA, 'label' => 'Se sent sous-payé'];
                } elseif ($ratio > self::SALARY_HIGH_RATIO) {
                    $events[] = ['source' => 'salary', 'value' => self::SALARY_HIGH_DELTA, 'label' => 'Satisfait de son salaire'];
                }

                // 4. Retour progressif vers le neutre (non loggé)
                $morale = (int) ($player->morale ?? self::NEUTRAL_MORALE);
                $decay  = (int) round((self::NEUTRAL_MORALE - $morale) * self::DECAY_RATE);

                $newMorale = $morale + $decay + array_sum(array_column($events, 'value'));
                $newMorale = max(0, min(100, $newMorale));

                if ($newMorale !== $morale) {
                    $player->morale = $newMorale;
                    $player->save();
                }

                foreach ($events as $event) {
                    $logs[] = [
                        'game_save_id'   => $gameSave->id,
                        'game_player_id' => $player->id,
                        'source'         => $event['source'],
                        'value'          => $event['value'],
                        'label'          => $event['label'],
                        'week'           => $week,
                        'season'         => $season,
                        'created_at'     => $now,
                        'updated_at'     => $now,
                    ];
                }
            }
        }

        if (!empty($logs)) {
            GamePlayerMoraleLog::insert($logs);
        }
    }

    /**
     * IDs (clé = id en string) des joueurs ayant joué un match.
     * Source primaire : match_stats.players — fallback : titulaires (match simulé).
     * Public static : aussi utilisé par PromiseService pour évaluer le temps de jeu promis.
     */
    public static function resolvePlayedPlayerIds(GameMatch $match): array
    {
        if (!empty($match->match_stats['players'])) {
            return array_fill_keys(array_map('strval', array_keys($match->match_stats['players'])), true);
        }

        $teamIds = array_filter([$match->home_team_id, $match->away_team_id]);
        if (empty($teamIds)) return [];

        return \App\Models\GameSaves\GameContract::query()
            ->whereIn('game_team_id', $teamIds)
            ->where('is_starter', true)
            ->pluck('game_player_id')
            ->mapWithKeys(fn($id) => [(string) $id => true])
            ->all();
    }

    /**
     * Joueurs blessés ou suspendus cette semaine : leur attente de temps
     * de jeu est gelée (pas de malus pour absence).
     */
    private function resolveUnavailablePlayerIds(GameSave $gameSave, int $week): array
    {
        $injured = GameInjury::where('game_save_id', $gameSave->id)
            ->where('week_injured', '<=', $week)
            ->where('week_return', '>', $week)
            ->pluck('game_player_id');

        $suspended = GameSanction::where('game_save_id', $gameSave->id)
            ->where('weeks_suspended', '>', 0)
            ->where('week_match', '<=', $week)
            ->where('week_return', '>', $week)
            ->pluck('game_player_id');

        return $injured->merge($suspended)->mapWithKeys(fn($id) => [(int) $id => true])->all();
    }
}
