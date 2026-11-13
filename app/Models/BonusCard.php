<?php

namespace App\Models;

use App\Models\GameSaves\GameBonusCard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Carte bonus ou malus du catalogue (référence, partagée par toutes les parties) :
 * nom, famille, rareté, cible, moment d'exécution et effet. Les exemplaires achetés
 * par les équipes sont des GameBonusCard. Le catalogue est semé par BonusCardSeeder.
 */
class BonusCard extends Model
{
    public const SHOP_PRICE = 200;

    protected $fillable = [
        'name', 'description', 'kind', 'tier', 'target', 'execution_phase',
        'effect_type', 'effect_value', 'cost', 'base_weight', 'icon',
    ];

    protected $casts = [
        'effect_value' => 'array',
        'target'       => \App\Enums\BonusCardTarget::class,
    ];

    /**
     * Points d'endurance retirés par un malus de fatigue d'équipe selon la rareté
     * (or 20, argent 15, bronze 10).
     */
    public static function teamStaminaDrainAmount(string $tier): int
    {
        return match ($tier) {
            'gold' => 20,
            'silver' => 15,
            default => 10,
        };
    }

    /** Phrase affichée pour un malus de fatigue d'équipe, avec le nombre de points de sa rareté. */
    public static function teamStaminaDrainDescription(string $tier): string
    {
        return 'Retire ' . self::teamStaminaDrainAmount($tier)
            . ' points d’endurance aux titulaires de l’équipe ciblée.';
    }

    /** Exemplaires de cette carte achetés dans les parties. */
    public function gameBonusCards(): HasMany
    {
        return $this->hasMany(GameBonusCard::class);
    }
}
