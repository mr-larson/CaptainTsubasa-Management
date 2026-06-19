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

            case 'revenue_boost':
                // Plus le budget est bas, plus c'est urgent
                $budget = $context['budget'] ?? 1000;
                $score = $budget < 500 ? 80 : ($budget < 1000 ? 50 : 20);
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
            } elseif ($effectType === 'revenue_boost') {
                // Toujours activer les boosts financiers
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

        // Match cette semaine
        $hasMatchThisWeek = $gameSave->matches()
            ->where('week', $gameSave->week)
            ->where('status', 'scheduled')
            ->where(fn ($q) => $q
                ->where('home_team_id', $team->id)
                ->orWhere('away_team_id', $team->id)
            )->exists();

        return [
            'avg_stamina'            => $avgStamina,
            'injured_count'          => $injuries->count(),
            'best_injured_player_id' => $bestInjuredId,
            'budget'                 => $team->budget ?? 0,
            'wins'                   => $team->wins ?? 0,
            'losses'                 => $team->losses ?? 0,
            'has_match_this_week'    => $hasMatchThisWeek,
        ];
    }
}
