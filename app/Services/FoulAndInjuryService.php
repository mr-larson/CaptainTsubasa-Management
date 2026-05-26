<?php

namespace App\Services;

use App\Models\GameSaves\GameInjury;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSanction;
use App\Models\GameSaves\GameSave;
use Illuminate\Support\Facades\DB;

class FoulAndInjuryService
{
    // Probabilités de blessure selon la sévérité du crit
    private const INJURY_CHANCE_CRIT_FAIL  = 0.40; // 40% chance sur critFail
    private const INJURY_CHANCE_FOUL       = 0.10; // 10% chance sur faute simple

    // Probabilités carton
    private const YELLOW_CHANCE_FOUL       = 0.25; // 25% sur faute simple
    private const YELLOW_CHANCE_CRIT       = 0.60; // 60% sur critFail défenseur
    private const RED_CHANCE_CRIT          = 0.15; // 15% carton rouge direct sur critFail défenseur

    // Durées blessures (en semaines)
    private const INJURY_WEEKS = [
        'light'    => [1, 1],
        'moderate' => [2, 3],
        'severe'   => [4, 6],
    ];

    private const INJURY_DESCRIPTIONS = [
        'light'    => ['Cheville foulée', 'Contusion', 'Coup reçu', 'Crampe musculaire'],
        'moderate' => ['Entorse', 'Déchirure musculaire légère', 'Côte fêlée', 'Genou touché'],
        'severe'   => ['Fracture', 'Rupture ligamentaire', 'Déchirure grave', 'Blessure sérieuse'],
    ];

    public function processMatchEvents(GameSave $gameSave, GameMatch $match, array $actionEvents): void
    {
        if (empty($actionEvents)) return;

        $week = $match->week;

        foreach ($actionEvents as $event) {
            $eventType = $event['type'] ?? null;

            // Événement faute
            if ($eventType === 'foul') {
                $this->processFoulEvent($gameSave, $match, $event, $week);
            }

            // Événement blessure
            if ($eventType === 'injury') {
                $this->processInjuryEvent($gameSave, $match, $event, $week);
            }

            // Événement carton (déjà décidé côté JS)
            if ($eventType === 'card') {
                $this->processCardEvent($gameSave, $match, $event, $week);
            }
        }
    }

    // ==========================
    //   TRAITEMENT FAUTE
    // ==========================

    protected function processFoulEvent(GameSave $gameSave, GameMatch $match, array $event, int $week): void
    {
        $foulerId   = isset($event['fouler_player_id']) ? (int) $event['fouler_player_id'] : null;
        $victimId   = isset($event['victim_player_id']) ? (int) $event['victim_player_id'] : null;
        $isCritFail = $event['is_crit_fail'] ?? false;

        // Blessure pour la victime seulement
        if ($victimId) {
            $chance = $isCritFail ? self::INJURY_CHANCE_CRIT_FAIL : self::INJURY_CHANCE_FOUL;
            if (mt_rand(1, 100) <= ($chance * 100)) {
                $severity = $isCritFail ? $this->rollSeverity(0.3, 0.5, 0.2) : 'light';
                $this->createInjury($gameSave, $match, $victimId, $severity, $week);
            }
        }
    }

    // ==========================
    //   TRAITEMENT BLESSURE DIRECTE
    // ==========================

    protected function processInjuryEvent(GameSave $gameSave, GameMatch $match, array $event, int $week): void
    {
        $playerId = isset($event['player_id']) ? (int) $event['player_id'] : null;
        $severity = $event['severity']  ?? 'light';

        if (!$playerId) return;

        $this->createInjury($gameSave, $match, $playerId, $severity, $week);
    }

    // ==========================
    //   TRAITEMENT CARTON DIRECT
    // ==========================

    protected function processCardEvent(GameSave $gameSave, GameMatch $match, array $event, int $week): void
    {
        $playerId = isset($event['player_id']) ? (int) $event['player_id'] : null;
        $cardType = $event['card_type'] ?? 'yellow'; // 'yellow' | 'red'

        if (!$playerId) return;

        if ($cardType === 'yellow') {
            $this->giveYellowCard($gameSave, $match, $playerId, $week);
        } elseif ($cardType === 'red') {
            $this->giveRedCard($gameSave, $match, $playerId, $week);
        }
    }

    // ==========================
    //   LOGIQUE CARTONS
    // ==========================

    protected function maybeGiveCard(
        GameSave $gameSave,
        GameMatch $match,
        int $playerId,
        bool $isCritFail,
        int $week
    ): void {
        $playerId = (int) $playerId;
        $roll = mt_rand(1, 100);

        if ($isCritFail) {
            if ($roll <= (self::RED_CHANCE_CRIT * 100)) {
                $this->giveRedCard($gameSave, $match, $playerId, $week);
            } elseif ($roll <= ((self::RED_CHANCE_CRIT + self::YELLOW_CHANCE_CRIT) * 100)) {
                $this->giveYellowCard($gameSave, $match, $playerId, $week);
            }
        } else {
            if ($roll <= (self::YELLOW_CHANCE_FOUL * 100)) {
                $this->giveYellowCard($gameSave, $match, $playerId, $week);
            }
        }
    }

    protected function giveYellowCard(GameSave $gameSave, GameMatch $match, int $playerId, int $week): void
    {
        $playerId = (int) $playerId;
        DB::transaction(function () use ($gameSave, $match, $playerId, $week) {
            // Compter les cartons jaunes existants cette saison
            $yellowCount = GameSanction::where('game_save_id', $gameSave->id)
                ->where('game_player_id', $playerId)
                ->where('type', 'yellow')
                ->count();

            $newCount = $yellowCount + 1;

            // 3e carton jaune = suspension 1 semaine (double_yellow)
            $isDoubleYellow = ($newCount % 3 === 0);
            $type           = $isDoubleYellow ? 'double_yellow' : 'yellow';
            $weeksSuspended = $isDoubleYellow ? 1 : 0;
            $weekReturn     = $week + 1 + $weeksSuspended;

            GameSanction::create([
                'game_save_id'      => $gameSave->id,
                'game_player_id'    => $playerId,
                'game_match_id'     => $match->id,
                'type'              => $type,
                'weeks_suspended'   => $weeksSuspended,
                'week_match'        => $week,
                'week_return'       => $weekReturn,
                'yellow_card_count' => $newCount,
            ]);
        });
    }

    protected function giveRedCard(GameSave $gameSave, GameMatch $match, int $playerId, int $week): void
    {
        $playerId = (int) $playerId;
        DB::transaction(function () use ($gameSave, $match, $playerId, $week) {
            $weeksSuspended = rand(2, 3);

            GameSanction::create([
                'game_save_id'      => $gameSave->id,
                'game_player_id'    => $playerId,
                'game_match_id'     => $match->id,
                'type'              => 'red',
                'weeks_suspended'   => $weeksSuspended,
                'week_match'        => $week,
                'week_return'       => $week + 1 + $weeksSuspended,
                'yellow_card_count' => 0,
            ]);
        });
    }

    // ==========================
    //   LOGIQUE BLESSURES
    // ==========================

    protected function createInjury(
        GameSave $gameSave,
        GameMatch $match,
        int $playerId,
        string $severity,
        int $week
    ): void {
        // Vérifier que le joueur n'est pas déjà blessé
        $alreadyInjured = GameInjury::where('game_save_id', $gameSave->id)
            ->where('game_player_id', $playerId)
            ->where('week_return', '>', $week)
            ->exists();

        if ($alreadyInjured) return;

        [$minWeeks, $maxWeeks] = self::INJURY_WEEKS[$severity] ?? [1, 1];
        $weeksOut    = rand($minWeeks, $maxWeeks);
        $weekReturn  = $week + 1 + $weeksOut;
        $descriptions = self::INJURY_DESCRIPTIONS[$severity] ?? ['Blessure'];
        $description = $descriptions[array_rand($descriptions)];

        DB::transaction(function () use ($gameSave, $match, $playerId, $severity, $weeksOut, $weekReturn, $week, $description) {
            GameInjury::create([
                'game_save_id'   => $gameSave->id,
                'game_player_id' => $playerId,
                'game_match_id'  => $match->id,
                'severity'       => $severity,
                'weeks_out'      => $weeksOut,
                'week_injured'   => $week,
                'week_return'    => $weekReturn,
                'description'    => $description,
            ]);
        });
    }

    // ==========================
    //   HELPERS
    // ==========================

    /**
     * Tire une sévérité de blessure selon des probabilités.
     */
    protected function rollSeverity(float $lightChance, float $moderateChance, float $severeChance): string
    {
        $roll = mt_rand(1, 100) / 100;
        if ($roll <= $lightChance)                             return 'light';
        if ($roll <= $lightChance + $moderateChance)          return 'moderate';
        return 'severe';
    }

    // ==========================
    //   QUERY HELPERS (pour useDashboard)
    // ==========================

    /**
     * Retourne les blessures actives pour une save à une semaine donnée.
     */
    public static function activeInjuries(int $gameSaveId, int $currentWeek): \Illuminate\Support\Collection
    {
        return GameInjury::where('game_save_id', $gameSaveId)
            ->where('week_return', '>', $currentWeek)
            ->get();
    }

    /**
     * Retourne les suspensions actives pour une save à une semaine donnée.
     */
    public static function activeSuspensions(int $gameSaveId, int $currentWeek): \Illuminate\Support\Collection
    {
        return GameSanction::where('game_save_id', $gameSaveId)
            ->where('week_return', '>', $currentWeek)
            ->where('weeks_suspended', '>', 0)
            ->get();
    }

    /**
     * Retourne les cartons jaunes actifs (pas encore purgés) pour une save.
     */
    public static function activeYellowCards(int $gameSaveId, int $currentWeek): \Illuminate\Support\Collection
    {
        return GameSanction::where('game_save_id', $gameSaveId)
            ->where('type', 'yellow')
            ->where('week_return', '>', $currentWeek)
            ->get();
    }
}
