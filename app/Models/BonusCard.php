<?php

namespace App\Models;

use App\Models\GameSaves\GameBonusCard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BonusCard extends Model
{
    public const SHOP_PRICE = 200;

    protected $fillable = [
        'name', 'description', 'kind', 'tier', 'target', 'execution_phase',
        'effect_type', 'effect_value', 'cost', 'base_weight', 'icon',
    ];

    protected $casts = [
        'effect_value' => 'array',
    ];

    public static function teamStaminaDrainAmount(string $tier): int
    {
        return match ($tier) {
            'gold' => 20,
            'silver' => 15,
            default => 10,
        };
    }

    public static function teamStaminaDrainDescription(string $tier): string
    {
        return 'Retire ' . self::teamStaminaDrainAmount($tier)
            . ' points d’endurance aux titulaires de l’équipe ciblée.';
    }

    public function gameBonusCards(): HasMany
    {
        return $this->hasMany(GameBonusCard::class);
    }
}
