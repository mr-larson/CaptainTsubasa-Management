<?php

namespace App\Services;

use App\Models\GameSaves\GameDeclaration;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GamePlayerMoraleLog;
use App\Models\GameSaves\GameSave;

/**
 * Déclarations publiques du coach (féliciter / critiquer) — phase 3.
 * L'effet dépend du mérite : féliciter un joueur en forme paie,
 * critiquer un joueur en forme se retourne contre toi.
 */
class DeclarationService
{
    const COOLDOWN_WEEKS = 3;

    // Féliciter
    const PRAISE_DESERVED_AFFINITY   = 8;
    const PRAISE_DESERVED_MORALE     = 4;
    const PRAISE_UNDESERVED_AFFINITY = 4; // sonne creux : effet réduit, pas de moral
    private const PRAISE_MALUS_CHANCE = 25;
    private const PRAISE_MATCH_BOOST = ['attack' => 2, 'defense' => 2, 'speed' => 2];
    private const MATCH_MALUS = ['attack' => -2, 'defense' => -2, 'speed' => -2];

    // Critiquer un joueur en méforme : électrochoc ou mal vécu
    const CRITICIZE_PROUD_CHANCE     = 60;
    const CRITICIZE_PROUD_MORALE     = -3;
    const CRITICIZE_PROUD_AFFINITY   = -5;
    const CRITICIZE_BACKFIRE_AFFINITY = -10;
    const CRITICIZE_BACKFIRE_MORALE   = -5;

    // Critiquer un joueur en forme : toujours injuste
    const CRITICIZE_UNFAIR_AFFINITY = -15;
    const CRITICIZE_UNFAIR_MORALE   = -8;
    private const MATCH_BOOST = ['attack' => 3, 'defense' => 3, 'speed' => 3];

    /**
     * Prononce une déclaration. Retourne le GameDeclaration créé,
     * ou un message d'erreur (string) si impossible.
     */
    public function declare(GameSave $gameSave, GamePlayer $player, string $type, int $teamId): GameDeclaration|string
    {
        $week = (int) ($gameSave->week ?? 1);

        $recent = GameDeclaration::where('game_save_id', $gameSave->id)
            ->where('game_player_id', $player->id)
            ->where('season', (int) ($gameSave->season ?? 1))
            ->where('week', '>', $week - self::COOLDOWN_WEEKS)
            ->exists();

        if ($recent) {
            return 'Tu t\'es déjà exprimé publiquement sur ce joueur récemment.';
        }

        $duelBalance = $this->lastMatchDuelBalance($gameSave, $player, $teamId);
        $deserved = $duelBalance > 0;

        if ($type === 'praise') {
            $outcome       = 'well_received';
            $affinityDelta = $deserved ? self::PRAISE_DESERVED_AFFINITY : self::PRAISE_UNDESERVED_AFFINITY;
            $moraleDelta   = $deserved ? self::PRAISE_DESERVED_MORALE : 0;
            if ($deserved && $this->queueMatchModifier($gameSave, $player, $teamId, $week, self::PRAISE_MATCH_BOOST)) {
                $outcome = 'match_boost';
            } elseif ($duelBalance < 0 && $this->roll() <= self::PRAISE_MALUS_CHANCE
                && $this->queueMatchModifier($gameSave, $player, $teamId, $week, self::MATCH_MALUS)) {
                $outcome = 'match_malus';
                $affinityDelta = 0;
            }
            // deserved = en forme → la félicitation est méritée
            $isDeserved = $deserved;
        } else {
            // criticize : "mérité" = le joueur est en méforme
            $isDeserved = !$deserved;
            if ($isDeserved) {
                if ($this->roll() <= self::CRITICIZE_PROUD_CHANCE
                    && $this->queueMatchModifier($gameSave, $player, $teamId, $week, self::MATCH_BOOST)) {
                    $outcome       = 'match_boost';
                    $affinityDelta = self::CRITICIZE_PROUD_AFFINITY;
                    $moraleDelta   = self::CRITICIZE_PROUD_MORALE;
                } else {
                    $outcome       = 'backfired';
                    $affinityDelta = self::CRITICIZE_BACKFIRE_AFFINITY;
                    $moraleDelta   = self::CRITICIZE_BACKFIRE_MORALE;
                }
            } else {
                $outcome       = 'backfired';
                $affinityDelta = self::CRITICIZE_UNFAIR_AFFINITY;
                $moraleDelta   = self::CRITICIZE_UNFAIR_MORALE;
            }
        }

        $player->coach_affinity = max(PromiseService::AFFINITY_MIN, min(PromiseService::AFFINITY_MAX,
            (int) $player->coach_affinity + $affinityDelta
        ));
        $player->morale = max(0, min(100, (int) $player->morale + $moraleDelta));
        $player->save();

        $declaration = GameDeclaration::create([
            'game_save_id'   => $gameSave->id,
            'game_player_id' => $player->id,
            'type'           => $type,
            'deserved'       => $isDeserved,
            'outcome'        => $outcome,
            'affinity_delta' => $affinityDelta,
            'morale_delta'   => $moraleDelta,
            'week'           => $week,
            'season'         => (int) ($gameSave->season ?? 1),
        ]);

        if ($moraleDelta !== 0) {
            GamePlayerMoraleLog::create([
                'game_save_id'   => $gameSave->id,
                'game_player_id' => $player->id,
                'source'         => 'declaration',
                'value'          => $moraleDelta,
                'label'          => $type === 'praise'
                    ? 'Félicité publiquement par le coach'
                    : ($outcome === 'match_boost'
                        ? 'Critiqué publiquement — réaction d’orgueil, bonus au prochain match'
                        : 'Critiqué publiquement — mal vécu'),
                'week'           => $week,
                'season'         => (int) ($gameSave->season ?? 1),
            ]);
        }

        return $declaration;
    }

    protected function roll(): int
    {
        return random_int(1, 100);
    }

    private function queueMatchModifier(GameSave $gameSave, GamePlayer $player, int $teamId, int $week, array $modifier): bool
    {
        $matchId = GameMatch::where('game_save_id', $gameSave->id)
            ->where('status', 'scheduled')
            ->where('week', '>=', $week)
            ->where(fn ($q) => $q->where('home_team_id', $teamId)->orWhere('away_team_id', $teamId))
            ->orderBy('week')->orderBy('id')->value('id');
        if ($matchId === null) return false;

        $state = $gameSave->state ?? [];
        $state['declaration_match_boosts'][$matchId][$teamId][$player->id] = $modifier;
        $gameSave->state = $state;
        $gameSave->save();
        return true;
    }

    public function consumeMatchBoosts(GameSave $gameSave, int $matchId): void
    {
        $state = $gameSave->state ?? [];
        if (!isset($state['declaration_match_boosts'][$matchId])) return;

        unset($state['declaration_match_boosts'][$matchId]);
        $gameSave->state = $state;
        $gameSave->save();
    }

    private function lastMatchDuelBalance(GameSave $gameSave, GamePlayer $player, int $teamId): int
    {
        $match = GameMatch::where('game_save_id', $gameSave->id)
            ->where('status', 'played')
            ->where(fn($q) => $q->where('home_team_id', $teamId)->orWhere('away_team_id', $teamId))
            ->orderByDesc('week')
            ->first();

        $stats = $match?->match_stats['players'][$player->id] ?? [];

        return (int) ($stats['duelsWon'] ?? 0) - (int) ($stats['duelsLost'] ?? 0);
    }
}
