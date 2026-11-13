<?php

namespace App\Services;

use App\Contracts\Randomizer;
use App\Helpers\PlayerLuck;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameInjury;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSanction;
use App\Models\GameSaves\GameSave;
use Illuminate\Support\Facades\DB;

/**
 * Conséquences disciplinaires et physiques des événements de match : blessures (durée selon la gravité) et sanctions (cartons jaunes, rouges, suspensions).
 * Reçoit les événements envoyés par le match, ne retient que les joueurs sous contrat avec l'une des deux équipes et écrit les blessures et sanctions en base. Les réglages de la partie peuvent désactiver blessures sur faute et suspensions au 3e jaune.
 */
class FoulAndInjuryService
{

    /** Reçoit le générateur aléatoire (gravité et durée des blessures, durée des suspensions). */
    public function __construct(private Randomizer $random)
    {
    }
    // Probabilités de blessure selon la sévérité du crit
    private const INJURY_CHANCE_CRIT_FAIL  = 0.30;
    private const INJURY_CHANCE_FOUL       = 0.10; // 10% chance sur faute simple

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

    /**
     * Traite les événements d'un match (fautes, blessures, cartons) et crée les blessures et sanctions correspondantes.
     * Les événements viennent du client : tout identifiant de joueur qui n'est pas sous contrat avec une des deux équipes est ignoré.
     */
    public function processMatchEvents(GameSave $gameSave, GameMatch $match, array $actionEvents): void
    {
        if (empty($actionEvents)) return;

        $injuryEnabled     = (bool) $gameSave->getConfig('injury_on_foul', true);
        $suspensionEnabled = (bool) $gameSave->getConfig('suspension_on_3_yellows', true);
        $week = $match->week;

        // Les événements viennent du client : on n'accepte que des joueurs sous
        // contrat avec l'une des deux équipes du match (sinon on pourrait
        // blesser ou suspendre n'importe quel joueur de la save).
        $matchPlayerIds = GameContract::where('game_save_id', $gameSave->id)
            ->whereIn('game_team_id', [$match->home_team_id, $match->away_team_id])
            ->activeAt((int) $week)
            ->pluck('game_player_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $luckByPlayer = $injuryEnabled ? GamePlayer::whereIn('id', $matchPlayerIds)->pluck('luck', 'id')->all() : [];

        foreach ($actionEvents as $event) {
            if (!is_array($event)) continue;
            foreach (['player_id', 'fouler_player_id', 'victim_player_id'] as $key) {
                if (isset($event[$key]) && !in_array((int) $event[$key], $matchPlayerIds, true)) {
                    $event[$key] = null;
                }
            }

            $eventType = $event['type'] ?? null;

            if ($eventType === 'foul' && $injuryEnabled) {
                $this->processFoulEvent($gameSave, $match, $event, $week, $luckByPlayer[$event['victim_player_id'] ?? 0] ?? null);
            }

            if ($eventType === 'injury' && $injuryEnabled) {
                $this->processInjuryEvent($gameSave, $match, $event, $week);
            }

            if ($eventType === 'card') {
                $this->processCardEvent($gameSave, $match, $event, $week, $suspensionEnabled);
            }
        }
    }

    // ==========================
    //   TRAITEMENT FAUTE
    // ==========================

    /** Une faute peut blesser sa victime : 10 % de chance (30 % si faute critique), modulée par la chance du joueur. Gravité aléatoire pour une faute critique, légère sinon. */
    protected function processFoulEvent(GameSave $gameSave, GameMatch $match, array $event, int $week, ?int $victimLuck): void
    {
        $victimId   = isset($event['victim_player_id']) ? (int) $event['victim_player_id'] : null;
        $isCritFail = $event['is_crit_fail'] ?? false;

        // Blessure pour la victime seulement
        if ($victimId) {
            $chance = $isCritFail ? self::INJURY_CHANCE_CRIT_FAIL : self::INJURY_CHANCE_FOUL;
            if ($this->random->int(0, 9999) < $chance * PlayerLuck::incidentMultiplier($victimLuck) * 10000) {
                $severity = $isCritFail ? $this->rollSeverity(0.3, 0.5, 0.2) : 'light';
                $this->createInjury($gameSave, $match, $victimId, $severity, $week);
            }
        }
    }

    // ==========================
    //   TRAITEMENT BLESSURE DIRECTE
    // ==========================

    /** Enregistre une blessure directe (joueur et gravité fournis par l'événement). */
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

    /** Enregistre un carton direct : jaune (avec suspension au 3e si activée) ou rouge (seulement si les suspensions sont activées). */
    protected function processCardEvent(GameSave $gameSave, GameMatch $match, array $event, int $week, bool $suspensionEnabled = true): void
    {
        $playerId = isset($event['player_id']) ? (int) $event['player_id'] : null;
        $cardType = $event['card_type'] ?? 'yellow';

        if (!$playerId) return;

        if ($cardType === 'yellow') {
            $this->giveYellowCard($gameSave, $match, $playerId, $week, $suspensionEnabled);
        } elseif ($cardType === 'red') {
            if ($suspensionEnabled) {
                $this->giveRedCard($gameSave, $match, $playerId, $week);
            }
        }
    }

    // ==========================
    //   LOGIQUE CARTONS
    // ==========================

    /** Tirage d'un carton après une faute (rouge ou jaune selon la gravité). Attention : s'appuie sur des constantes (RED_CHANCE_CRIT, etc.) absentes de la classe et n'est appelée nulle part. */
    protected function maybeGiveCard(
        GameSave $gameSave,
        GameMatch $match,
        int $playerId,
        bool $isCritFail,
        int $week
    ): void {
        $playerId = (int) $playerId;
        $roll = $this->random->int(1, 100);

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

    /** Inscrit un carton jaune en base. Si les suspensions sont activées, le 3e jaune du cycle devient un 'double_yellow' avec 1 semaine de suspension. */
    protected function giveYellowCard(GameSave $gameSave, GameMatch $match, int $playerId, int $week, bool $suspensionEnabled = true): void
    {
        $playerId = (int) $playerId;
        DB::transaction(function () use ($gameSave, $match, $playerId, $week, $suspensionEnabled) {
            // Le 3e jaune est stocké en 'double_yellow' : il doit compter, sinon
            // le cumul reste bloqué à 2 et chaque jaune suivant suspend.
            $yellowCount = GameSanction::currentYellowCardCycle($gameSave)
                ->where('game_player_id', $playerId)
                ->count();

            $newCount = $yellowCount + 1;

            $isDoubleYellow = $suspensionEnabled && ($newCount % 3 === 0);
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

    /** Inscrit un carton rouge en base avec 2 à 3 semaines de suspension. */
    protected function giveRedCard(GameSave $gameSave, GameMatch $match, int $playerId, int $week): void
    {
        $playerId = (int) $playerId;
        DB::transaction(function () use ($gameSave, $match, $playerId, $week) {
            $weeksSuspended = $this->random->int(2, 3);

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

    /** Crée une blessure (si le joueur n'est pas déjà blessé) : durée tirée selon la gravité (légère 1 semaine, modérée 2-3, grave 4-6) et description tirée au hasard. */
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
        $weeksOut    = $this->random->int($minWeeks, $maxWeeks);
        $weekReturn  = $week + 1 + $weeksOut;
        $descriptions = self::INJURY_DESCRIPTIONS[$severity] ?? ['Blessure'];
        $description = $this->random->pick($descriptions);

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
        $roll = $this->random->int(1, 100) / 100;
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
