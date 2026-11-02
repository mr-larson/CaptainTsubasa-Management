<?php

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Les seeders de données de référence (équipes, joueurs, contrats, liens)
 * vident leur table avant de la remplir : les ids sont régénérés, alors que
 * les saves existantes y pointent (game_teams.base_team_id,
 * game_players.base_player_id, game_saves.team_id). En production, on refuse
 * donc de ré-exécuter ces seeders sur une table déjà remplie : un
 * `db:seed --force` (pour BonusCardSeeder, par ex.) ne doit pas corrompre
 * toutes les parties en cours.
 *
 * Forçage volontaire : SEED_FORCE_REFERENCE_RESET=true.
 */
trait GuardsReferenceReseed
{
    protected function refusesProductionReseed(string $table): bool
    {
        if (! app()->environment('production')
            || filter_var(env('SEED_FORCE_REFERENCE_RESET', false), FILTER_VALIDATE_BOOL)
            || ! DB::table($table)->exists()) {
            return false;
        }

        $this->command?->warn(
            static::class." ignoré : la table `{$table}` est déjà remplie en production "
            .'(les saves existantes référencent ses ids). SEED_FORCE_REFERENCE_RESET=true pour forcer.'
        );

        return true;
    }
}
