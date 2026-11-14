<?php

namespace App\Services\Cards;

use App\Services\Cards\Effects\CardEffect;
use App\Services\Cards\Effects\CoachAffinityBoostEffect;
use App\Services\Cards\Effects\InjuryReduceEffect;
use App\Services\Cards\Effects\MalusEffect;
use App\Services\Cards\Effects\MoraleBoostEffect;
use App\Services\Cards\Effects\PreMatchCardEffect;
use App\Services\Cards\Effects\RevenueGambleEffect;
use App\Services\Cards\Effects\RevenuePerformanceEffect;
use App\Services\Cards\Effects\SponsorChallengeEffect;
use App\Services\Cards\Effects\StaminaBoostEffect;
use App\Services\Cards\Effects\TeamMoraleBoostEffect;
use Illuminate\Contracts\Container\Container;

/**
 * Associe le `effect_type` d'une carte (colonne bonus_cards.effect_type) à la classe qui
 * applique son effet. Ajouter un type de carte = écrire un CardEffect et l'inscrire ici.
 */
class CardEffectRegistry
{
    /** Effets portés par une classe dédiée (cf. Effects/). @var array<string, class-string<CardEffect>> */
    private const EFFECTS = [
        'stamina_boost'           => StaminaBoostEffect::class,
        'injury_reduce'           => InjuryReduceEffect::class,
        'injury_cure'             => InjuryReduceEffect::class,
        'revenue_gamble'          => RevenueGambleEffect::class,
        'revenue_challenge'       => SponsorChallengeEffect::class,
        'morale_boost'            => MoraleBoostEffect::class,
        'coach_affinity_boost'    => CoachAffinityBoostEffect::class,
        'team_morale_boost'       => TeamMoraleBoostEffect::class,
        'revenue_performance'     => RevenuePerformanceEffect::class,
        // pre_match : juste marquer comme activée, MatchSimulator la lira
        'stat_boost'              => PreMatchCardEffect::class,
        'stat_boost_and_stamina'  => PreMatchCardEffect::class,
    ];

    /** Malus et bouclier : effet → méthode de MalusCardEffects. @var array<string, string> */
    private const MALUS_METHODS = [
        'malus_shield'              => 'applyMalusShield',
        'opponent_stamina_drain'    => 'applyOpponentStaminaDrain',
        'opponent_bench_starter'    => 'applyOpponentBenchStarter',
        'opponent_morale_drain'     => 'applyOpponentMoraleDrain',
        'opponent_affinity_drain'   => 'applyOpponentAffinityDrain',
        'opponent_budget_drain'     => 'applyOpponentBudgetDrain',
        'opponent_team_stat_debuff' => 'applyOpponentTeamStatDebuff',
        'opponent_key_stat_debuff'  => 'applyOpponentKeyStatDebuff',
    ];

    public function __construct(private Container $container)
    {
    }

    /** L'effet à appliquer pour ce type de carte, ou null si le type est inconnu. */
    public function for(string $effectType): ?CardEffect
    {
        if (isset(self::EFFECTS[$effectType])) {
            return $this->container->make(self::EFFECTS[$effectType]);
        }

        if (isset(self::MALUS_METHODS[$effectType])) {
            return new MalusEffect($this->container->make(MalusCardEffects::class), self::MALUS_METHODS[$effectType]);
        }

        return null;
    }
}
