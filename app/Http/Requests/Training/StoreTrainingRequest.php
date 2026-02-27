<?php

namespace App\Http\Requests\Training;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTrainingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La propriété de la GameSave est vérifiée dans le contrôleur,
        // ici on autorise et on laisse le contrôle au controller.
        return true;
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
