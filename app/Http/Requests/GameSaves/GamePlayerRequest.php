<?php

namespace App\Http\Requests\GameSaves;

use App\Enums\PlayerPosition;
use App\Models\GameSaves\GameSave;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/** Requête de création ou d'édition d'un joueur au sein d'une sauvegarde. */
class GamePlayerRequest extends FormRequest
{
    /** Autorise la requête si l'utilisateur peut modifier la sauvegarde de la route (propriétaire ou admin). */
    public function authorize(): bool
    {
        $gameSave = $this->route('gameSave');

        return $gameSave instanceof GameSave
            && (bool) $this->user()?->can('update', $gameSave);
    }

    /** Identité, postes, coût, photo, statistiques directes (0 à 100) et techniques spéciales. */
    public function rules(): array
    {
        return [
            'firstname'    => ['required', 'string', 'max:255'],
            'lastname'     => ['required', 'string', 'max:255'],
            'position'     => ['required', new Enum(PlayerPosition::class)],
            'secondary_positions'   => ['nullable', 'array'],
            'secondary_positions.*' => [new Enum(PlayerPosition::class), 'different:position'],
            'description'  => ['nullable', 'string', 'max:1000'],
            'cost'         => ['required', 'integer', 'min:0'],

            // photo
            'photo'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_photo' => ['boolean'],

            // base player optionnel
            'base_player_id' => ['nullable', 'integer', 'exists:players,id'],

            // stats directes (GamePlayer)
            'speed'        => ['nullable', 'integer', 'min:0', 'max:100'],
            'stamina'      => ['nullable', 'integer', 'min:0', 'max:100'],
            'attack'       => ['nullable', 'integer', 'min:0', 'max:100'],
            'defense'      => ['nullable', 'integer', 'min:0', 'max:100'],
            'shot'         => ['nullable', 'integer', 'min:0', 'max:100'],
            'pass'         => ['nullable', 'integer', 'min:0', 'max:100'],
            'dribble'      => ['nullable', 'integer', 'min:0', 'max:100'],
            'block'        => ['nullable', 'integer', 'min:0', 'max:100'],
            'intercept'    => ['nullable', 'integer', 'min:0', 'max:100'],
            'tackle'       => ['nullable', 'integer', 'min:0', 'max:100'],
            'heading'      => ['nullable', 'integer', 'min:0', 'max:100'],
            'hand_save'    => ['nullable', 'integer', 'min:0', 'max:100'],
            'punch_save'   => ['nullable', 'integer', 'min:0', 'max:100'],

            // special moves
            'special_moves'              => ['nullable', 'array'],
            'special_moves.*.key'        => ['required_with:special_moves', 'string', 'max:255'],
            'special_moves.*.label'      => ['required_with:special_moves', 'string', 'max:255'],
            'special_moves.*.mode'       => ['required_with:special_moves', 'string', 'in:attack,defense'],
            'special_moves.*.base_action'=> ['required_with:special_moves', 'string', 'max:50'],
            'special_moves.*.description'=> ['nullable', 'string', 'max:1000'],
            'special_moves.*.short_label'=> ['nullable', 'string', 'max:50'],
            'special_moves.*.cooldown'   => ['nullable', 'integer', 'min:0', 'max:20'],
        ];
    }
}
