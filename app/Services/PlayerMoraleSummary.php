<?php

namespace App\Services;

use App\Models\GameSaves\GameDeclaration;
use App\Models\GameSaves\GamePlayerMoraleLog;
use App\Models\GameSaves\GamePromise;
use App\Models\GameSaves\GameSave;
use Illuminate\Support\Collection;

/**
 * Carte « Moral » d'un joueur : derniers changements de moral, promesses
 * (en cours + dernière évaluée) et dernière déclaration publique, indexés par
 * game_player_id. Utilisé par la page de jeu (équipe contrôlée) et par
 * l'endpoint à la demande de l'onglet « Autres équipes ».
 */
class PlayerMoraleSummary
{
    public function moraleLogs(GameSave $gameSave, Collection|array $playerIds): Collection
    {
        return GamePlayerMoraleLog::where('game_save_id', $gameSave->id)
            ->whereIn('game_player_id', $playerIds)
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->groupBy('game_player_id')
            ->map(fn($logs) => $logs->take(6)->map(fn($l) => [
                'source' => $l->source,
                'value'  => $l->value,
                'label'  => $l->label,
                'week'   => $l->week,
                'season' => $l->season,
            ])->values());
    }

    public function promises(GameSave $gameSave, Collection|array $playerIds): Collection
    {
        $promises = GamePromise::where('game_save_id', $gameSave->id)
            ->whereIn('game_player_id', $playerIds)
            ->orderByDesc('id')
            ->limit(100)
            ->get();
        $starterProgress = app(PromiseService::class)->starterProgress($gameSave, $promises, (int) ($gameSave->week ?? 1));

        return $promises
            ->groupBy('game_player_id')
            ->map(fn($promises) => $promises->take(2)->map(fn($p) => [
                'id'             => $p->id,
                'type'           => $p->type,
                'start_week'     => $p->start_week,
                'due_week'       => $p->due_week,
                'target_matches' => $p->target_matches,
                'played_matches' => $starterProgress[$p->id] ?? $p->played_matches,
                'target_turns'   => $p->target_turns,
                'played_turns'   => $p->played_turns,
                'status'         => $p->status,
            ])->values());
    }

    public function declarations(GameSave $gameSave, Collection|array $playerIds): Collection
    {
        return GameDeclaration::where('game_save_id', $gameSave->id)
            ->whereIn('game_player_id', $playerIds)
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->groupBy('game_player_id')
            ->map(fn($declarations) => $declarations->take(1)->map(fn($d) => [
                'type'           => $d->type,
                'deserved'       => $d->deserved,
                'outcome'        => $d->outcome,
                'affinity_delta' => $d->affinity_delta,
                'morale_delta'   => $d->morale_delta,
                'week'           => $d->week,
                'season'         => $d->season,
            ])->values());
    }
}
