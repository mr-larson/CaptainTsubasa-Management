<?php

namespace App\Http\Resources;

use App\Models\GameSaves\GameSave;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;

/**
 * Ce qu'une page Inertia reçoit d'une sauvegarde : colonnes utiles et, dans
 * `state`, UNIQUEMENT les clés que le front lit. Liste blanche : une nouvelle
 * clé de state reste côté serveur tant qu'on ne l'ajoute pas ici (contrairement
 * à GameSave::toArray, qui n'écarte que GameState::SERVER_ONLY_KEYS).
 *
 * Volontairement PAS un JsonResource : resolve() ré-indexe les tableaux à clés
 * numériques, ce qui transformerait state.lineup (indexé par id d'équipe) en liste.
 */
final class GameSaveResource implements Arrayable
{
    /** Clés du state lues par resources/js (Play.vue et composables). */
    public const STATE_KEYS = [
        'lineup',                 // compositions par équipe (useTeam / useOtherTeam)
        'training',               // séances de la semaine (useTraining)
        'contract_departures',    // départs de joueurs en fin de contrat (Play.vue)
        'active_pre_match_cards', // cartes bonus armées pour le prochain match
        'pending_stat_debuffs',   // débuffs de stats subis au prochain match
    ];

    private const COLUMNS = [
        'id', 'team_id', 'controlled_game_team_id', 'period', 'season', 'week',
        'label', 'phase', 'game_mode', 'competition_type', 'created_at', 'updated_at',
    ];

    private function __construct(private GameSave $gameSave, private bool $withState)
    {
    }

    public static function make(GameSave $gameSave): self
    {
        return new self($gameSave, true);
    }

    /** Version sans state, pour les listes (une page n'y lit que les colonnes). */
    public static function summary(GameSave $gameSave): self
    {
        return new self($gameSave, false);
    }

    public function toArray(): array
    {
        $array = Arr::only($this->gameSave->toArray(), self::COLUMNS);

        if ($this->withState) {
            $state = $this->gameSave->state;
            $array['state'] = is_array($state) ? Arr::only($state, self::STATE_KEYS) : $state;
        }

        return $array;
    }
}
