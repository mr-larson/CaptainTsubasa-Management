<?php

namespace App\Models\GameSaves\Concerns;

use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\Scopes\CurrentSeasonScope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Ligne rattachée à une saison de la sauvegarde (colonne `season`).
 * - Les requêtes ne voient que la saison courante (CurrentSeasonScope).
 * - À la création, `season` reprend celle de la sauvegarde sauf si elle est donnée.
 * - Au changement de saison, on ne supprime plus : les anciennes lignes sortent
 *   simplement du filtre et forment l'historique (allSeasons / forSeason).
 */
trait ScopedToSaveSeason
{
    public static function bootScopedToSaveSeason(): void
    {
        static::addGlobalScope(new CurrentSeasonScope());

        static::creating(function ($model) {
            if ($model->season === null) {
                $model->season = (int) (GameSave::query()->whereKey($model->game_save_id)->value('season') ?: 1);
            }
        });
    }

    /** Toutes les saisons (historique : palmarès, statistiques de carrière). */
    public static function allSeasons(): Builder
    {
        return static::withoutGlobalScope(CurrentSeasonScope::class);
    }

    /** Une saison précise. */
    public static function forSeason(int $season): Builder
    {
        return static::allSeasons()->where((new static)->qualifyColumn('season'), $season);
    }
}
