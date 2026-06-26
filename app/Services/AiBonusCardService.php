<?php

namespace App\Services;

use App\Models\BonusCard;
use App\Models\GameSaves\GameBonusCard;
use App\Models\GameSaves\GameInjury;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;

class AiBonusCardService
{
    // Budget minimum que l'IA garde en réserve pour les transferts
    private const TRANSFER_RESERVE = 500;

    public function __construct(
        private BonusCardActivationService $activationService
    ) {}

    /**
     * Point d'entrée appelé dans simulateWeek et finishMatch pour toutes les équipes IA.
     */
    public function processWeek(GameSave $gameSave): void
    {
        $controlledTeamIds = $gameSave->controlledGameTeamIds();
        $shop = $gameSave->state['shop'] ?? null;

        if (!$shop ||
            (int) ($shop['season'] ?? 0) !== (int) $gameSave->season ||
            (int) ($shop['week']   ?? 0) !== (int) $gameSave->week) {
            return; // Boutique pas encore générée
        }

        $teams = $gameSave->gameTeams()->get();

        foreach ($teams as $team) {
            if (in_array((int) $team->id, $controlledTeamIds, true)) continue; // Skip équipes joueurs

            $offers = $shop['offers_by_team'][(string) $team->id] ?? [];
            $this->processTeam($gameSave, $team, $offers);
        }
    }

    // ──────────────────────────────────────────────────────────
    //   LOGIQUE PAR ÉQUIPE IA
    // ──────────────────────────────────────────────────────────

    private function processTeam(GameSave $gameSave, GameTeam $team, array $offers): void
    {
        if (empty($offers)) return;

        $budget = $team->budget ?? 0;

        // Contexte de l'équipe pour prendre des décisions
        $context = $this->buildTeamContext($gameSave, $team);

        foreach ($offers as $offer) {
            $cost = (int) ($offer['cost'] ?? 0);

            // Ne jamais dépasser le budget - réserve
            if ($budget < $cost + self::TRANSFER_RESERVE) continue;

            // Évaluer si la carte vaut l'achat
            $score = $this->scoreOffer($offer, $context);

            // Seuil d'achat selon le tier : l'IA est plus sélective sur les cartes chères
            $threshold = match ($offer['tier'] ?? 'bronze') {
                'gold'   => 70,
                'silver' => 50,
                default  => 35,
            };

            if ($score < $threshold) continue;

            // Acheter
            $card = GameBonusCard::create([
                'game_save_id'     => $gameSave->id,
                'bonus_card_id'    => $offer['bonus_card_id'],
                'game_team_id'     => $team->id,
                'tier'             => $offer['tier'],
                'cost_paid'        => $cost,
                'status'           => 'available',
                'purchased_season' => $gameSave->season,
                'purchased_week'   => $gameSave->week,
            ]);

            $team->budget = $budget - $cost;
            $team->save();
            $budget = $team->budget;

            // Activer immédiatement si immediate et utile
            if (($offer['execution_phase'] ?? '') === 'immediate') {
                $this->activateIfUseful($gameSave, $card, $offer, $context);
            }
            // Les pre_match sont activées si un match est imminent (géré ailleurs)
        }

        // Activer les cartes pre_match en inventaire si match à venir
        $this->activatePreMatchCards($gameSave, $team, $context);
    }

    // ──────────────────────────────────────────────────────────
    //   SCORING D'UNE OFFRE (0-100)
    // ──────────────────────────────────────────────────────────

    private function scoreOffer(array $offer, array $context): int
    {
        $effectType = $offer['effect_type'] ?? '';
        $score = 0;

        switch ($effectType) {
            case 'stamina_boost':
                // Plus la stamina moyenne est basse, plus c'est utile
                $avgStamina = $context['avg_stamina'] ?? 100;
                $score = (int) ((100 - $avgStamina) * 0.8);
                break;

            case 'injury_reduce':
            case 'injury_cure':
                // Utile uniquement si des joueurs sont blessés
                $injuredCount = $context['injured_count'] ?? 0;
                $score = $injuredCount > 0 ? min(90, 40 + $injuredCount * 20) : 0;
                break;

            case 'revenue_gamble':
                // Pari publicitaire : plus le budget est bas, plus on tente
                $budget = $context['budget'] ?? 1000;
                $score = $budget < 500 ? 75 : ($budget < 1000 ? 45 : 15);
                break;

            case 'revenue_challenge':
                // Défi sponsor : intéressant quand le budget est tendu (pari sur la perf)
                $budget = $context['budget'] ?? 1000;
                $score = $budget < 700 ? 50 : ($budget < 1200 ? 30 : 10);
                break;

            case 'morale_boost':
                // Utile si un joueur a un moral bas (< neutre)
                $worst = $context['worst_morale'] ?? 60;
                $score = $worst < 50 ? min(85, (int) ((60 - $worst) * 2)) : 0;
                break;

            case 'coach_affinity_boost':
                // Utile si un joueur est fâché avec le coach (relation négative)
                $worst = $context['worst_affinity'] ?? 0;
                $score = $worst < 0 ? min(85, (int) (abs($worst) * 1.5)) : 0;
                break;

            case 'stat_boost':
            case 'stat_boost_and_stamina':
                // Toujours utile avant un match
                $score = 60;
                // Bonus si l'équipe est en difficulté (peu de victoires)
                if (($context['wins'] ?? 0) < ($context['losses'] ?? 0)) {
                    $score += 20;
                }
                break;

            case 'opponent_stamina_drain':
            case 'opponent_bench_starter':
            case 'opponent_morale_drain':
            case 'opponent_affinity_drain':
            case 'opponent_budget_drain':
            case 'opponent_team_stat_debuff':
            case 'opponent_key_stat_debuff':
                // Malus offensif. Carte "prochain adversaire" : utile seulement
                // si l'IA a un match. Carte à cible choisie (target=team) : l'IA
                // peut saboter le leader à tout moment.
                $base = match ($effectType) {
                    'opponent_bench_starter'   => 58,
                    'opponent_team_stat_debuff'=> 52,
                    'opponent_key_stat_debuff' => 50,
                    'opponent_affinity_drain'  => 48,
                    'opponent_budget_drain'    => 45,
                    default                    => 52,
                };
                $isTeamTargeted = ($offer['target'] ?? 'opponent') === 'team';
                $score = $isTeamTargeted
                    ? $base
                    : (($context['has_match_this_week'] ?? false) ? $base : 0);
                if ($score && ($context['wins'] ?? 0) < ($context['losses'] ?? 0)) {
                    $score += 10; // une équipe en difficulté est plus agressive
                }
                break;

            case 'team_morale_boost':
                // +moral collectif : utile si un joueur a le moral bas.
                $worst = $context['worst_morale'] ?? 60;
                $score = $worst < 50 ? 65 : ($worst < 60 ? 35 : 15);
                break;

            case 'revenue_performance':
                // Revenu lié à la perf : intéressant quand le budget est tendu.
                $budget = $context['budget'] ?? 1000;
                $score = $budget < 700 ? 50 : ($budget < 1200 ? 30 : 12);
                break;

            case 'malus_shield':
                // Défensif : l'IA n'en abuse pas (réservé surtout au joueur).
                $score = 25;
                break;
        }

        // Bonus selon le tier
        $score += match ($offer['tier'] ?? 'bronze') {
            'gold'   => 15,
            'silver' => 8,
            default  => 0,
        };

        return min(100, $score);
    }

    // ──────────────────────────────────────────────────────────
    //   ACTIVATION
    // ──────────────────────────────────────────────────────────

    private function activateIfUseful(GameSave $gameSave, GameBonusCard $card, array $offer, array $context): void
    {
        $effectType = $offer['effect_type'] ?? '';

        try {
            if ($effectType === 'stamina_boost') {
                // Activer si stamina moyenne < 70%
                if (($context['avg_stamina'] ?? 100) < 70) {
                    $this->activationService->activate($card, $gameSave);
                }
            } elseif (in_array($effectType, ['injury_reduce', 'injury_cure'])) {
                // Activer sur le joueur le plus important blessé
                $targetId = $context['best_injured_player_id'] ?? null;
                if ($targetId) {
                    $this->activationService->activate($card, $gameSave, $targetId);
                }
            } elseif ($effectType === 'revenue_gamble') {
                // Tenter le pari publicitaire (résolution immédiate)
                $this->activationService->activate($card, $gameSave);
            } elseif ($effectType === 'revenue_challenge') {
                // Souscrire le défi sponsor (résolu au prochain match)
                $this->activationService->activate($card, $gameSave);
            } elseif ($effectType === 'morale_boost') {
                // Activer sur le joueur au moral le plus bas
                $targetId = $context['worst_morale_player_id'] ?? null;
                if ($targetId && ($context['worst_morale'] ?? 60) < 50) {
                    $this->activationService->activate($card, $gameSave, $targetId);
                }
            } elseif ($effectType === 'coach_affinity_boost') {
                // Activer sur le joueur à la pire relation avec le coach
                $targetId = $context['worst_affinity_player_id'] ?? null;
                if ($targetId && ($context['worst_affinity'] ?? 0) < 0) {
                    $this->activationService->activate($card, $gameSave, $targetId);
                }
            } elseif (in_array($effectType, [
                'opponent_stamina_drain', 'opponent_bench_starter',
                'opponent_morale_drain', 'opponent_affinity_drain', 'opponent_budget_drain',
                'opponent_team_stat_debuff', 'opponent_key_stat_debuff',
            ], true)) {
                // Malus prochain adversaire : si match à venir. Malus à cible
                // choisie (target=team) : sans cible explicite, le service tape
                // le leader du classement (qui peut être le joueur humain).
                $isTeamTargeted = ($offer['target'] ?? 'opponent') === 'team';
                if ($isTeamTargeted || ($context['has_match_this_week'] ?? false)) {
                    $this->activationService->activate($card, $gameSave);
                }
            } elseif ($effectType === 'team_morale_boost') {
                // +moral collectif : activer si un joueur a le moral bas.
                if (($context['worst_morale'] ?? 60) < 55) {
                    $this->activationService->activate($card, $gameSave);
                }
            } elseif ($effectType === 'revenue_performance' || $effectType === 'malus_shield') {
                // Revenu (argent immédiat) et bouclier défensif : activer direct.
                $this->activationService->activate($card, $gameSave);
            }
        } catch (\Throwable) {
            // Silencieux — l'IA ne plante pas si la carte échoue
        }
    }

    private function activatePreMatchCards(GameSave $gameSave, GameTeam $team, array $context): void
    {
        $pendingCards = GameBonusCard::query()
            ->where('game_save_id', $gameSave->id)
            ->where('game_team_id', $team->id)
            ->where('status', 'available')
            ->whereHas('bonusCard', fn ($q) => $q->where('execution_phase', 'pre_match'))
            ->with('bonusCard')
            ->get();

        foreach ($pendingCards as $card) {
            // Activer si match cette semaine
            if ($context['has_match_this_week'] ?? false) {
                try {
                    $this->activationService->activate($card, $gameSave);
                } catch (\Throwable) {}
            }
        }
    }

    // ──────────────────────────────────────────────────────────
    //   CONTEXTE ÉQUIPE
    // ──────────────────────────────────────────────────────────

    private function buildTeamContext(GameSave $gameSave, GameTeam $team): array
    {
        // Stamina moyenne
        $players = GamePlayer::query()
            ->where('game_save_id', $gameSave->id)
            ->whereHas('contracts', fn ($q) => $q->where('game_team_id', $team->id))
            ->get();

        $avgStamina = $players->isNotEmpty()
            ? (int) $players->avg('stamina')
            : 100;

        // Blessures actives
        $injuries = GameInjury::query()
            ->where('game_save_id', $gameSave->id)
            ->whereIn('game_player_id', $players->pluck('id'))
            ->where('week_return', '>', $gameSave->week)
            ->orderByDesc('week_return')
            ->get();

        // Joueur blessé le plus impactant = celui dont le retour est le plus loin
        $bestInjuredId = $injuries->first()?->game_player_id;

        // Joueur au moral le plus bas / à la pire relation avec le coach
        $worstMoralePlayer   = $players->sortBy('morale')->first();
        $worstAffinityPlayer = $players->sortBy('coach_affinity')->first();

        // Match cette semaine
        $hasMatchThisWeek = $gameSave->matches()
            ->where('week', $gameSave->week)
            ->where('status', 'scheduled')
            ->where(fn ($q) => $q
                ->where('home_team_id', $team->id)
                ->orWhere('away_team_id', $team->id)
            )->exists();

        return [
            'avg_stamina'               => $avgStamina,
            'injured_count'             => $injuries->count(),
            'best_injured_player_id'    => $bestInjuredId,
            'worst_morale'              => (int) ($worstMoralePlayer->morale ?? 60),
            'worst_morale_player_id'    => $worstMoralePlayer?->id,
            'worst_affinity'            => (int) ($worstAffinityPlayer->coach_affinity ?? 0),
            'worst_affinity_player_id'  => $worstAffinityPlayer?->id,
            'budget'                    => $team->budget ?? 0,
            'wins'                      => $team->wins ?? 0,
            'losses'                    => $team->losses ?? 0,
            'has_match_this_week'       => $hasMatchThisWeek,
        ];
    }
}
