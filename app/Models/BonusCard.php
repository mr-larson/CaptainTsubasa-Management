<?php

namespace App\Models;

use App\Models\GameSaves\GameBonusCard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BonusCard extends Model
{
    protected $fillable = [
        'name', 'description', 'kind', 'tier', 'target', 'execution_phase',
        'effect_type', 'effect_value', 'cost', 'base_weight', 'icon',
    ];

    protected $casts = [
        'effect_value' => 'array',
    ];

    public function gameBonusCards(): HasMany
    {
        return $this->hasMany(GameBonusCard::class);
    }
}
