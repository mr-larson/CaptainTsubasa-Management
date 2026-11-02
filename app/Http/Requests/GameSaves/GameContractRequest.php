<?php

namespace App\Http\Requests\GameSaves;

use App\Models\GameSaves\GameSave;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GameContractRequest extends FormRequest
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
            // Scopé à la save : sinon un contrat pourrait rattacher un joueur
            // à l'équipe d'une autre save (voire d'un autre utilisateur).
            'game_team_id' => [
                'required', 'integer',
                Rule::exists('game_teams', 'id')->where('game_save_id', $this->route('gameSave')?->id),
            ],
            'salary'       => ['required', 'integer', 'min:0'],
            'start_week'   => ['required', 'integer', 'min:1'],
            'end_week'     => ['nullable', 'integer', 'min:1', 'gte:start_week'],
            'is_starter'   => ['boolean'],
        ];
    }
}
