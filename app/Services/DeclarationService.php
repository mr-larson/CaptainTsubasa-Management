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

    // Critiquer un joueur en méforme : électrochoc ou mal vécu
    const CRITICIZE_PROUD_CHANCE     = 0.6;
    const CRITICIZE_PROUD_MORALE     = 5;
    const CRITICIZE_PROUD_AFFINITY   = -5;
    const CRITICIZE_BACKFIRE_AFFINITY = -10;
    const CRITICIZE_BACKFIRE_MORALE   = -5;

    // Critiquer un joueur en forme : toujours injuste
    const CRITICIZE_UNFAIR_AFFINITY = -15;
    const CRITICIZE_UNFAIR_MORALE   = -8;

    /**
     * Prononce une déclaration. Retourne le GameDeclaration créé,
     * ou un message d'erreur (string) si impossible.
     */
    public function declare(GameSave $gameSave, GamePlayer $player, string $type, int $teamId): GameDeclaration|string
    {
        $week = (int) ($gameSave->week ?? 1);

        $recent = GameDeclaration::where('game_save_id', $gameSave->id)
            ->where('game_player_id', $player->id)
            ->where('week', '>', $week - self::COOLDOWN_WEEKS)
            ->exists();

        if ($recent) {
            return 'Tu t\'es déjà exprimé publiquement sur ce joueur récemment.';
        }

        $deserved = $this->isInForm($gameSave, $player, $teamId);

        if ($type === 'praise') {
            $outcome       = 'well_received';
            $affinityDelta = $deserved ? self::PRAISE_DESERVED_AFFINITY : self::PRAISE_UNDESERVED_AFFINITY;
            $moraleDelta   = $deserved ? self::PRAISE_DESERVED_MORALE : 0;
            // deserved = en forme → la félicitation est méritée
            $isDeserved = $deserved;
        } else {
            // criticize : "mérité" = le joueur est en méforme
            $isDeserved = !$deserved;
            if ($isDeserved) {
                if (mt_rand() / mt_getrandmax() < self::CRITICIZE_PROUD_CHANCE) {
                    $outcome       = 'proud_reaction';
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
                    : ($outcome === 'proud_reaction'
                        ? 'Critiqué publiquement — réaction d\'orgueil'
                        : 'Critiqué publiquement — mal vécu'),
                'week'           => $week,
                'season'         => (int) ($gameSave->season ?? 1),
            ]);
        }

        return $declaration;
    }

    /**
     * Le joueur est-il en forme ? Référence : son dernier match d'équipe joué
     * (a participé ET duels gagnés > duels perdus).
     */
    private function isInForm(GameSave $gameSave, GamePlayer $player, int $teamId): bool
    {
        $match = GameMatch::where('game_save_id', $gameSave->id)
            ->where('status', 'played')
            ->where(fn($q) => $q->where('home_team_id', $teamId)->orWhere('away_team_id', $teamId))
            ->orderByDesc('week')
            ->first();

        $stats = $match?->match_stats['players'][(string) $player->id]
            ?? $match?->match_stats['players'][$player->id]
            ?? null;

        if (!$stats) return false; // n'a pas joué (ou aucun match) → pas en forme

        return (int) ($stats['duelsWon'] ?? 0) > (int) ($stats['duelsLost'] ?? 0);
    }
}
