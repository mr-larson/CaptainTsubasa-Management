<?php

namespace App\Models\GameSaves\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Restreint un modèle de jeu (matchs, contrats, blessures, sanctions) à la
 * saison COURANTE de sa sauvegarde. Les saisons passées restent en base, mais
 * tout le code existant, indexé sur la semaine, continue de ne voir que la
 * saison en cours. Pour lire l'historique : Model::allSeasons().
 */
class CurrentSeasonScope implements Scope
{
    /**
     * Ajoute à la requête la condition « la ligne est de la saison courante de sa
     * sauvegarde » (sous-requête sur game_saves.season).
     */
    public function apply(Builder $builder, Model $model): void
    {
        $table = $model->getTable();

        $builder->whereRaw("`{$table}`.`season` = (select `season` from `game_saves` where `game_saves`.`id` = `{$table}`.`game_save_id`)");
    }
}
