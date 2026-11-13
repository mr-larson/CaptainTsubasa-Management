<?php

namespace App\Services;

use App\Enums\MoraleSource;
use App\Models\GameSaves\GameDeclaration;
use App\Models\GameSaves\GameMatch;
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
    /** Les 6 derniers événements de moral (parmi les 200 plus récents) par joueur. */
    public function moraleLogs(GameSave $gameSave, Collection|array $playerIds): Collection
    {
        return GamePlayerMoraleLog::where('game_save_id', $gameSave->id)
            ->whereIn('game_player_id', $playerIds)
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->groupBy('game_player_id')
            ->map(fn($logs) => $logs->take(6)->map(fn($l) => [
                'source' => $l->source->value,
                'value'  => $l->value,
                'label'  => $l->label,
                'week'   => $l->week,
                'season' => $l->season,
            ])->values());
    }

    /** Historique compact : moral/relation d’un côté, gains de statistiques de l’autre. */
    public function recentChanges(GameSave $gameSave, Collection|array $playerIds): Collection
    {
        $ids = collect($playerIds)->map(fn ($id) => (int) $id)->all();
        $rows = collect();
        foreach ($this->moraleLogs($gameSave, $ids) as $playerId => $logs) {
            foreach ($logs as $log) {
                $rows->push($log + ['player_id' => $playerId, 'category' => 'morale',
                    'metric' => match ($log['source']) {
                        MoraleSource::Promise->value => 'Moral / coach', MoraleSource::CoachAffinity->value => 'Relation coach', default => 'Moral',
                    }]);
            }
        }
        foreach (GameDeclaration::where('game_save_id', $gameSave->id)->whereIn('game_player_id', $ids)
            ->where('affinity_delta', '!=', 0)->orderByDesc('id')->limit(100)->get() as $declaration) {
            $rows->push([
                'player_id' => $declaration->game_player_id, 'category' => 'morale', 'metric' => 'Relation coach',
                'source' => MoraleSource::Declaration->value, 'source_label' => 'Presse', 'value' => $declaration->affinity_delta,
                'label' => $declaration->type === 'praise' ? 'Félicitations publiques' : 'Critique publique',
                'week' => $declaration->week, 'season' => $declaration->season,
            ]);
        }
        $gameState = $gameSave->gameState();
        $manualHistory = $gameState->trainingHistory() ?? [];
        // Les anciennes sauvegardes conservent au moins les séances de la semaine.
        if (!$manualHistory) {
            $training = $gameState->training() ?? [];
            $manualHistory = array_map(fn ($entry) => $entry + [
                'week' => $training['week'] ?? $gameSave->week,
                'season' => $training['season'] ?? $gameSave->season,
            ], $training['entries'] ?? []);
        }
        foreach (array_merge($manualHistory, $gameState->aiTrainingHistory()) as $entry) {
            if (!in_array((int) ($entry['player_id'] ?? 0), $ids, true) || (int) ($entry['gain'] ?? 0) === 0) continue;
            $rows->push([
                'player_id' => $entry['player_id'], 'category' => 'stats', 'source' => MoraleSource::Training->value,
                'source_label' => 'Entraînement', 'stat' => $entry['stat'], 'value' => $entry['gain'],
                'old' => $entry['old_stat'] ?? null, 'new' => $entry['new_stat'] ?? null,
                'week' => $entry['week'], 'season' => $entry['season'],
            ]);
        }
        // Ne charge que le rapport de progression, pas le déroulé complet des matchs.
        $matches = GameMatch::where('game_save_id', $gameSave->id)->where('status', 'played')
            ->orderByDesc('week')->orderByDesc('id')->limit(50)
            ->get(['id', 'week', 'match_stats->progression as progression']);
        foreach ($matches as $match) {
            $report = is_array($match->progression) ? $match->progression : json_decode($match->progression ?? '[]', true);
            foreach ($report ?? [] as $entry) {
                if (!in_array((int) ($entry['player_id'] ?? 0), $ids, true)) continue;
                foreach ($entry['gains'] ?? [] as $stat => $gain) {
                    if ((int) ($gain['gain'] ?? 0) === 0) continue;
                    $rows->push([
                        'player_id' => $entry['player_id'], 'category' => 'stats', 'source' => MoraleSource::Match->value,
                        'source_label' => 'Performance en match', 'stat' => $stat, 'value' => $gain['gain'],
                        'old' => $gain['old'] ?? null, 'new' => $gain['new'] ?? null,
                        'week' => $match->week, 'season' => $gameSave->season,
                    ]);
                }
            }
        }
        return $rows->sortByDesc(fn ($row) => (int) $row['season'] * 10000 + (int) $row['week'])
            ->groupBy('player_id')->map(fn ($logs) => collect(['morale', 'stats'])->flatMap(
                fn ($category) => $logs->where('category', $category)->take(6)
            )->values());
    }

    /** Les 2 dernières promesses par joueur, avec la progression des titularisations promises recalculée par PromiseService. */
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

    /** La dernière déclaration publique par joueur. */
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
