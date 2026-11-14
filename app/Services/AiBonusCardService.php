<?php

namespace App\Services;

use App\Models\BonusCard;
use App\Models\GameSaves\GameBonusCard;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameInjury;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use Illuminate\Support\Collection;

/**
 * Achat et activation des cartes bonus par les équipes IA.
 * Chaque semaine, une équipe IA regarde les offres de sa boutique, achète celles dont le score dépasse un seuil selon le palier (bronze, argent, or) en gardant une réserve pour les transferts, puis active les cartes utiles (immédiates, ou avant match quand un match est prévu).
 */
class AiBonusCardService
{
    // Budget minimum que l'IA garde en réserve pour les transferts
    private const TRANSFER_RESERVE = 500;

    /** Effets de carte qui modifient les joueurs d'UNE AUTRE équipe : ils périment les lectures préchargées. */
    private const MALUS_EFFECTS = [
        'opponent_stamina_drain', 'opponent_bench_starter', 'opponent_morale_drain', 'opponent_affinity_drain',
        'opponent_budget_drain', 'opponent_team_stat_debuff', 'opponent_key_stat_debuff',
    ];

    /**
     * Lectures de la ligue faites une fois pour toutes les équipes IA de la semaine (joueurs,
     * blessures, matchs prévus, cartes d'avant-match en attente). null = à (re)lire.
     */
    private ?array $snapshot = null;

    /**
     * Vrai quand un malus a pu modifier des joueurs (endurance, moral, relation coach) : seules les
     * fiches des joueurs sont à relire (une requête). Ni les blessures, ni les matchs, ni les
     * cartes en attente ne changent à cause d'un malus.
     */
    private bool $playersStale = false;

    /** Reçoit le service qui applique réellement les effets des cartes. */
    public function __construct(
        private BonusCardActivationService $activationService
    ) {}

    /**
     * Point d'entrée appelé dans simulateWeek et finishMatch pour toutes les équipes IA.
     */
    public function processWeek(GameSave $gameSave): void
    {
        $controlledTeamIds = $gameSave->controlledGameTeamIds();
        if (!$gameSave->gameState()->isShopFor((int) $gameSave->season, (int) $gameSave->week)) {
            return; // Boutique pas encore générée
        }

        $teams = $gameSave->gameTeams()->get();

        // Offres de chaque équipe IA, et fiches des cartes concernées lues d'un coup.
        $offersByTeam = [];
        foreach ($teams as $team) {
            if (in_array((int) $team->id, $controlledTeamIds, true)) continue; // Skip équipes joueurs
            $offersByTeam[$team->id] = $gameSave->gameState()->shopOffers((int) $team->id);
        }
        $catalog = BonusCard::whereIn('id', collect($offersByTeam)->flatten(1)->pluck('bonus_card_id')->filter()->unique()->values())
            ->get()->keyBy('id');

        $this->snapshot = null;
        $this->playersStale = false;
        try {
            foreach ($teams as $team) {
                if (! isset($offersByTeam[$team->id])) continue;

                $this->processTeam($gameSave, $team, $offersByTeam[$team->id], $catalog);
            }
        } finally {
            $this->snapshot = null; // ne rien garder d'une semaine à l'autre
        }
    }

    // ──────────────────────────────────────────────────────────
    //   LOGIQUE PAR ÉQUIPE IA
    // ──────────────────────────────────────────────────────────

    /** Traite une équipe IA : achète les offres rentables (débit du budget, création de la carte en base), active tout de suite les cartes immédiates utiles, puis active les cartes avant match si un match est à venir. */
    private function processTeam(GameSave $gameSave, GameTeam $team, array $offers, Collection $catalog): void
    {
        if (empty($offers)) return;

        $budget = $team->budget ?? 0;

        // Contexte de l'équipe pour prendre des décisions
        $context = $this->buildTeamContext($gameSave, $team);
        $boughtPreMatch = []; // cartes d'avant-match achetées pendant ce tour (elles s'ajoutent à celles déjà en attente)

        foreach ($offers as $offer) {
            $cost = BonusCard::SHOP_PRICE;

            // Ne jamais dépasser le budget - réserve
            if ($budget < $cost + self::TRANSFER_RESERVE) continue;

            // Évaluer si la carte vaut l'achat
            $score = $this->scoreOffer($offer, $context);

            // Seuil d'achat selon le tier, indépendamment du prix commun.
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

            // La fiche de la carte est déjà connue : inutile de la relire en base à l'activation.
            $card->setRelation('bonusCard', $catalog->get($offer['bonus_card_id']));
            $boughtPreMatch[] = $card;

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
        $this->activatePreMatchCards($gameSave, $context, $boughtPreMatch);
    }

    // ──────────────────────────────────────────────────────────
    //   SCORING D'UNE OFFRE (0-100)
    // ──────────────────────────────────────────────────────────

    /** Note une offre de 0 à 100 selon son effet et la situation de l'équipe (endurance, blessés, budget, moral, relation avec le coach, résultats, match à venir), avec un bonus selon le palier. */
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

    /** Active la carte seulement si elle sert vraiment (seuil d'endurance, joueur blessé, moral bas, match à venir, etc.) et choisit la cible quand l'effet en demande une. Les erreurs sont avalées : l'IA ne doit jamais faire planter la simulation. */
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
        } finally {
            // Un malus peut avoir touché les joueurs d'une autre équipe : les prochaines équipes
            // doivent lire l'état à jour, pas celui d'avant (c'est l'ordre des équipes qui compte).
            if (in_array($effectType, self::MALUS_EFFECTS, true)) {
                $this->playersStale = true;
            }
        }
    }

    /**
     * Active les cartes d'avant-match disponibles de l'équipe, uniquement si elle a un match prévu
     * cette semaine. Erreurs ignorées.
     *
     * Les cartes en attente sont celles lues avec le contexte de l'équipe, plus celles qu'elle vient
     * d'acheter à ce tour (toujours disponibles, et d'avant-match selon leur fiche) : même liste
     * que si on relisait la base, sans la requête.
     *
     * @param GameBonusCard[] $bought cartes achetées pendant ce tour
     */
    private function activatePreMatchCards(GameSave $gameSave, array $context, array $bought): void
    {
        $pendingCards = ($context['pending_pre_match_cards'] ?? collect())->concat(
            array_filter($bought, fn (GameBonusCard $card) => $card->status === 'available'
                && $card->bonusCard?->execution_phase === 'pre_match')
        );

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

    /** Résume la situation d'une équipe pour les décisions : endurance moyenne, blessés actifs, joueurs au moral et à la relation coach les plus bas, budget, victoires/défaites, présence d'un match prévu cette semaine et cartes d'avant-match déjà en attente. */
    private function buildTeamContext(GameSave $gameSave, GameTeam $team): array
    {
        $league = $this->leagueSnapshot($gameSave);

        // Stamina moyenne
        $players = $league['playersByTeam']->get($team->id, collect());

        $avgStamina = $players->isNotEmpty()
            ? (int) $players->avg('stamina')
            : 100;

        // Blessures actives (la plus lointaine d'abord), de cette équipe seulement
        $injuries = $league['injuries']->filter(fn ($injury) => $players->contains('id', $injury->game_player_id))->values();

        // Joueur blessé le plus impactant = celui dont le retour est le plus loin
        $bestInjuredId = $injuries->first()?->game_player_id;

        // Joueur au moral le plus bas / à la pire relation avec le coach
        $worstMoralePlayer   = $players->sortBy('morale')->first();
        $worstAffinityPlayer = $players->sortBy('coach_affinity')->first();

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
            'has_match_this_week'       => $league['teamsWithMatch']->has($team->id),
            'pending_pre_match_cards'   => $league['pendingPreMatch']->get($team->id, collect()),
        ];
    }

    /**
     * Lectures de toute la ligue en quelques requêtes (au lieu de quelques requêtes par équipe) :
     *   - playersByTeam    : joueurs ayant un contrat (de la saison) dans l'équipe, par id croissant ;
     *   - injuries         : blessures encore actives, retour le plus lointain d'abord ;
     *   - teamsWithMatch   : équipes (en clés) ayant un match prévu cette semaine ;
     *   - pendingPreMatch  : cartes d'avant-match disponibles, par équipe, avec leur fiche.
     *
     * Les fiches des joueurs sont relues (une requête) après un malus, qui peut les modifier ;
     * le reste est gardé pour toute la semaine.
     *
     * @return array{playersByTeam: Collection, injuries: Collection, teamsWithMatch: Collection, pendingPreMatch: Collection}
     */
    private function leagueSnapshot(GameSave $gameSave): array
    {
        if ($this->snapshot === null) {
            $contracts = GameContract::where('game_save_id', $gameSave->id)->get(['game_team_id', 'game_player_id']);

            $this->snapshot = [
                'teamPlayerIds' => $contracts->groupBy('game_team_id')
                    ->map(fn ($rows) => $rows->pluck('game_player_id')->unique()->values()),
                'injuries'      => GameInjury::query()
                    ->where('game_save_id', $gameSave->id)
                    ->where('week_return', '>', $gameSave->week)
                    ->orderByDesc('week_return')
                    ->get(),
                'teamsWithMatch' => GameMatch::where('game_save_id', $gameSave->id)
                    ->where('week', $gameSave->week)
                    ->where('status', 'scheduled')
                    ->get(['home_team_id', 'away_team_id'])
                    ->flatMap(fn ($match) => [(int) $match->home_team_id, (int) $match->away_team_id])
                    ->flip(),
                'pendingPreMatch' => GameBonusCard::query()
                    ->where('game_save_id', $gameSave->id)
                    ->where('status', 'available')
                    ->whereHas('bonusCard', fn ($q) => $q->where('execution_phase', 'pre_match'))
                    ->with('bonusCard')
                    ->get()
                    ->groupBy('game_team_id'),
            ];
            $this->playersStale = true; // les joueurs n'ont pas encore été lus
        }

        if ($this->playersStale) {
            $players = GamePlayer::where('game_save_id', $gameSave->id)
                ->whereIn('id', $this->snapshot['teamPlayerIds']->flatten()->unique()->values())
                ->get()->keyBy('id');

            $this->snapshot['playersByTeam'] = $this->snapshot['teamPlayerIds']->map(
                fn ($ids) => $ids->map(fn ($id) => $players->get($id))->filter()->sortBy('id')->values()
            );
            $this->playersStale = false;
        }

        return $this->snapshot;
    }
}
