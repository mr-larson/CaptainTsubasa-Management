<?php

namespace App\Http\Requests\GameSaves;

use App\Enums\TeamStyle;
use App\Models\GameSaves\GameSave;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GameTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        $gameSave = $this->route('gameSave');

        return $gameSave instanceof GameSave
            && (bool) $this->user()?->can('update', $gameSave);
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'budget'      => ['required', 'integer', 'min:0'],

            'wins'        => ['nullable', 'integer', 'min:0'],
            'draws'       => ['nullable', 'integer', 'min:0'],
            'losses'      => ['nullable', 'integer', 'min:0'],

            // Gestion du logo
            'logo'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_logo' => ['boolean'],

            // Pas de règle `logo_path` : le chemin est géré uniquement par le
            // contrôleur (upload / suppression). L'accepter du client écrasait
            // le nouveau logo par l'ancien chemin, déjà supprimé.

            'tactical_style'        => ['nullable', Rule::in(TeamStyle::TACTICAL_STYLES)],
            'management_philosophy' => ['nullable', Rule::in(TeamStyle::PHILOSOPHIES)],
        ];
    }
}
