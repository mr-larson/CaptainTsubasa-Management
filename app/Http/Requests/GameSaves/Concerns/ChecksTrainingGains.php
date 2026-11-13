<?php

namespace App\Http\Requests\GameSaves\Concerns;

use App\Support\GameConfig;
use Illuminate\Validation\Validator;

/**
 * Gain d'entraînement min ≤ max (sinon random_int lève une ValueError) : règle
 * croisée que les règles par champ de GameConfig::rules() ne peuvent pas porter.
 */
trait ChecksTrainingGains
{
    protected function checkTrainingGains(Validator $validator, array $config, string $errorKey, ?GameConfig $existing = null): void
    {
        if (! ($existing ?? new GameConfig)->hasValidTrainingGains($config)) {
            $validator->errors()->add($errorKey, 'Le gain max doit être supérieur ou égal au gain min.');
        }
    }
}
