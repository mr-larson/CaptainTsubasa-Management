<?php

namespace App\Http\Requests\Training;

use App\Models\GameSaves\GameSave;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTrainingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $gameSave = $this->route('gameSave');

        return $gameSave instanceof GameSave
            && (bool) $this->user()?->can('update', $gameSave);
    }

    public function rules(): array
    {
        $allowedStats = config('training.allowed_stats', []);

        return [
            'season' => ['required', 'integer', 'min:1'],
            'week'   => ['required', 'integer', 'min:1'],

            // trainings : array de 1 à max_trainings_per_week
            'trainings'              => ['required', 'array', 'min:1', 'max:' . config('training.max_trainings_per_week', 3)],
            'trainings.*.player_id'  => ['required', 'integer'],
            'trainings.*.stat'       => ['required', 'string', Rule::in($allowedStats)],
        ];
    }

    public function messages(): array
    {
        return [
            'trainings.max' => 'Tu ne peux pas faire plus de ' . config('training.max_trainings_per_week') . ' entraînements par semaine.',
        ];
    }
}
