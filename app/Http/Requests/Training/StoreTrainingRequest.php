<?php

namespace App\Http\Requests\Training;

use App\Models\GameSaves\GameSave;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Requête d'entraînement hebdomadaire : liste de joueurs avec la statistique à travailler. */
class StoreTrainingRequest extends FormRequest
{
    /** Autorise la requête si l'utilisateur peut modifier la sauvegarde de la route (propriétaire ou admin). */
    public function authorize(): bool
    {
        $gameSave = $this->route('gameSave');

        return $gameSave instanceof GameSave
            && (bool) $this->user()?->can('update', $gameSave);
    }

    /** Quota hebdo de la save (réglable 1-10 dans Gestion), pas seulement la config globale. */
    private function maxPerWeek(): int
    {
        $gameSave = $this->route('gameSave');
        $default  = (int) config('training.max_trainings_per_week', 3);

        return $gameSave instanceof GameSave
            ? (int) $gameSave->getConfig('training_max_per_week', $default)
            : $default;
    }

    /**
     * Saison, semaine et liste d'entraînements (de 1 au quota hebdomadaire), chacun avec joueur et statistique autorisée.
     */
    public function rules(): array
    {
        $allowedStats = config('training.allowed_stats', []);

        return [
            'season' => ['required', 'integer', 'min:1'],
            'week'   => ['required', 'integer', 'min:1'],

            // trainings : array de 1 à max_trainings_per_week
            'trainings'              => ['required', 'array', 'min:1', 'max:' . $this->maxPerWeek()],
            'trainings.*.player_id'  => ['required', 'integer', Rule::exists('game_players', 'id')->where('game_save_id', $this->route('gameSave')?->id)],
            'trainings.*.stat'       => ['required', 'string', Rule::in($allowedStats)],
        ];
    }

    /** Message d'erreur personnalisé quand le quota hebdomadaire est dépassé. */
    public function messages(): array
    {
        return [
            'trainings.max' => 'Tu ne peux pas faire plus de ' . $this->maxPerWeek() . ' entraînements par semaine.',
        ];
    }
}
