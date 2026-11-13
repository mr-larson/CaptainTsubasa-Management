<?php

namespace App\Support;

use App\Models\GameSaves\GameSave;

/**
 * Accès typé au `state` JSON d'une sauvegarde (GameSave::gameState()).
 *
 * Chaque clé migrée a ses accesseurs ici : son nom, sa forme et sa valeur par
 * défaut ne vivent qu'à un endroit, les services n'écrivent plus `['lineup'][$id]['slots']`
 * à la main. La forme stockée est INCHANGÉE (aucune migration de données).
 *
 * Les écritures modifient le state en mémoire ; `save()` (ou `$gameSave->save()`)
 * persiste. Ne remplace pas le verrou de save : une écriture reste un
 * lecture-modification-écriture de tout le JSON (voir LocksGameSave).
 *
 * Toutes les clés du state ont leurs accesseurs : lineup, ai_rotation, midseason_break,
 * season_history / last_season_recap, free_agent_signings, declaration_match_boosts,
 * draft, challenges, training, training_history, ai_training_history, ai_training_week,
 * season_progression, match_progression_history, cartes (active_pre_match_cards,
 * pending_stat_debuffs, pending_malus, malus_shields, pending_sponsor_challenges,
 * sponsor_results), shop, tournament, career, contract_departures.
 * Seule `config` est à part : voir GameConfig. Une nouvelle clé s'ajoute ICI, avec sa
 * forme documentée, et ne se lit pas par chaîne brute ailleurs.
 */
final class GameState
{
    /**
     * Clés du state purement serveur, jamais lues par le front : retirées de la
     * sérialisation (props Inertia, JSON) pour alléger chaque page. La boutique
     * contient notamment les offres de toutes les équipes IA.
     */
    public const SERVER_ONLY_KEYS = [
        'shop',
        'ai_training_week',
        'season_progression',
        'match_progression_history',
        'training_history',
        // Historique des séances IA (toutes équipes, ~95 Ko en semaine 19) : le
        // front ne lit que la semaine affichée, via game-saves.training.ai-week.
        'ai_training_history',
    ];

    public function __construct(private GameSave $save)
    {
    }

    public function save(): void
    {
        $this->save->save();
    }

    // ── Accès brut (clés pas encore migrées) ──────────────────────────

    public function raw(): array
    {
        return $this->save->state ?? [];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->raw()[$key] ?? $default;
    }

    public function put(string $key, mixed $value): void
    {
        $state = $this->raw();
        $state[$key] = $value;
        $this->save->state = $state;
    }

    public function forget(string ...$keys): void
    {
        $state = $this->raw();
        foreach ($keys as $key) {
            unset($state[$key]);
        }
        $this->save->state = $state;
    }

    // ── lineup : compositions enregistrées, par équipe ────────────────
    //    [teamId => ['formation' => string, 'slots' => [slot 1..11 => gamePlayerId|null]]]

    /** @return array<int, array{formation?: string, slots?: array<int, int|null>}> */
    public function lineups(): array
    {
        return $this->get('lineup', []);
    }

    /** Slots enregistrés d'une équipe, ou null si aucune composition n'est enregistrée. */
    public function lineupSlots(int $teamId): ?array
    {
        return $this->lineups()[$teamId]['slots'] ?? null;
    }

    /** Enregistre (en fusionnant) la composition d'une équipe : formation et/ou slots. */
    public function setLineup(int $teamId, array $lineup): void
    {
        $lineups = $this->lineups();
        $lineups[$teamId] = array_replace($lineups[$teamId] ?? [], $lineup);
        $this->put('lineup', $lineups);
    }

    public function setLineupSlots(int $teamId, array $slots): void
    {
        $this->setLineup($teamId, ['slots' => $slots]);
    }

    /** Retire un joueur des slots d'une équipe (sans toucher aux autres). */
    public function removeFromLineup(int $teamId, int $playerId): void
    {
        $slots = $this->lineupSlots($teamId);
        if ($slots === null) return;

        $changed = false;
        foreach ($slots as $slot => $id) {
            if ((int) $id === $playerId) {
                $slots[$slot] = null;
                $changed = true;
            }
        }
        if ($changed) $this->setLineupSlots($teamId, $slots);
    }

    /** Remplace un joueur par un autre dans les slots d'une équipe. */
    public function replaceInLineup(int $teamId, int $outId, int $inId): void
    {
        $slots = $this->lineupSlots($teamId);
        if ($slots === null) return;

        $changed = false;
        foreach ($slots as $slot => $id) {
            if ((int) $id === $outId) {
                $slots[$slot] = $inId;
                $changed = true;
            }
        }
        if ($changed) $this->setLineupSlots($teamId, $slots);
    }

    public function forgetLineups(): void
    {
        $this->forget('lineup');
    }

    // ── ai_rotation : repos tournant décidé pour une équipe IA ────────
    //    [teamId => ['season' => int, 'week' => int, 'resting_roles' => [playerId => role]]]

    public function aiRotation(int $teamId): array
    {
        return $this->get('ai_rotation', [])[$teamId] ?? [];
    }

    public function setAiRotation(int $teamId, int $season, int $week, array $restingRoles): void
    {
        $rotations = $this->get('ai_rotation', []);
        $rotations[$teamId] = ['season' => $season, 'week' => $week, 'resting_roles' => $restingRoles];
        $this->put('ai_rotation', $rotations);
    }

    /** Rôles au repos s'ils ont été décidés pour CETTE semaine, sinon []. */
    public function restingRoles(int $teamId, int $season, int $week): array
    {
        $rotation = $this->aiRotation($teamId);

        return ($rotation['week'] ?? null) === $week && ($rotation['season'] ?? null) === $season
            ? ($rotation['resting_roles'] ?? [])
            : [];
    }

    // ── midseason_break : pause de mi-saison ──────────────────────────
    //    ['return_week' => int, 'completed' => bool]

    public function midseasonBreak(): array
    {
        return $this->get('midseason_break', []);
    }

    public function scheduleMidseasonBreak(int $returnWeek): void
    {
        if ($this->get('midseason_break') === null) {
            $this->put('midseason_break', ['return_week' => $returnWeek, 'completed' => false]);
        }
    }

    public function completeMidseasonBreak(): void
    {
        $break = $this->midseasonBreak();
        $break['completed'] = true;
        $this->put('midseason_break', $break);
    }

    public function hasPendingMidseasonBreak(int $currentWeek): bool
    {
        $break = $this->midseasonBreak();

        return (int) ($break['return_week'] ?? 0) === $currentWeek && ! ($break['completed'] ?? true);
    }

    // ── season_history / last_season_recap ────────────────────────────

    public function recordSeasonRecap(int $season, array $recap): void
    {
        $history = $this->get('season_history', []);
        $history[$season] = $recap;
        $this->put('season_history', $history);
        $this->put('last_season_recap', $recap);
    }

    public function lastSeasonRecap(): ?array
    {
        return $this->get('last_season_recap');
    }

    // ── free_agent_signings : agents libres signés cette saison ───────

    public function recordFreeAgentSigning(array $signing): void
    {
        $signings = $this->get('free_agent_signings', []);
        $signings[] = $signing;
        $this->put('free_agent_signings', $signings);
    }

    public function forgetFreeAgentSignings(): void
    {
        $this->forget('free_agent_signings');
    }

    // ── declaration_match_boosts : [matchId][teamId][playerId] => modificateur

    public function declarationBoosts(int $matchId, int $teamId): array
    {
        return $this->get('declaration_match_boosts', [])[$matchId][$teamId] ?? [];
    }

    public function addDeclarationBoost(int $matchId, int $teamId, int $playerId, mixed $modifier): void
    {
        $boosts = $this->get('declaration_match_boosts', []);
        $boosts[$matchId][$teamId][$playerId] = $modifier;
        $this->put('declaration_match_boosts', $boosts);
    }

    /** Retire les boosts d'un match ; false s'il n'y en avait pas. */
    public function consumeDeclarationBoosts(int $matchId): bool
    {
        $boosts = $this->get('declaration_match_boosts', []);
        if (! isset($boosts[$matchId])) return false;

        unset($boosts[$matchId]);
        $this->put('declaration_match_boosts', $boosts);

        return true;
    }

    public function forgetDeclarationBoosts(): void
    {
        $this->forget('declaration_match_boosts');
    }

    // ── draft : état de la draft en cours ─────────────────────────────
    //    ['order' => [teamId], 'budgets' => [teamId => int], 'pricing' => 'progressive',
    //     'current_pick_index' => int, 'round' => int, 'picks' => [...], 'completed' => bool,
    //     'finished_teams' => [teamId], 'exhausted_teams' => [teamId]]
    //    Absente hors draft (purgée à l'ouverture de Play, voir DraftService::purgeFinishedDraft).

    /** null s'il n'y a pas de draft. */
    public function draft(): ?array
    {
        return $this->get('draft');
    }

    public function hasDraft(): bool
    {
        return $this->draft() !== null;
    }

    public function putDraft(array $draft): void
    {
        $this->put('draft', $draft);
    }

    public function forgetDraft(): void
    {
        $this->forget('draft');
    }

    /** Ouvre une draft : ordre des équipes (premier tour) et enveloppe par équipe. */
    public function startDraft(array $order, array $budgets): void
    {
        $this->putDraft([
            'order'              => array_values($order),
            'budgets'            => $budgets,
            'pricing'            => 'progressive',
            'current_pick_index' => 0,
            'round'              => 1,
            'picks'              => [],
            'completed'          => false,
            'finished_teams'     => [],
        ]);
    }

    public function draftCompleted(): bool
    {
        return (bool) ($this->draft()['completed'] ?? false);
    }

    public function draftRound(): int
    {
        return (int) ($this->draft()['round'] ?? 1);
    }

    /** Enveloppes par équipe ; null pour une ancienne draft à caisse commune. */
    public function draftBudgets(): ?array
    {
        return $this->draft()['budgets'] ?? null;
    }

    public function draftPricing(): ?string
    {
        return $this->draft()['pricing'] ?? null;
    }

    /** Équipe qui doit choisir (ordre « serpent » : les tours pairs sont inversés) ; null si terminé. */
    public function currentDraftTeamId(): ?int
    {
        $draft = $this->draft();
        if (! $draft || ($draft['completed'] ?? false)) return null;

        $order = $draft['order'] ?? [];
        $effectiveOrder = ((int) ($draft['round'] ?? 1) % 2 === 0) ? array_reverse($order) : $order;
        $teamId = $effectiveOrder[$draft['current_pick_index'] ?? 0] ?? null;

        return $teamId === null ? null : (int) $teamId;
    }

    // ── challenges : défis de saison ──────────────────────────────────
    //    ['season' => int, 'drawn_at' => iso8601, 'teams' => [teamId => entrée]]
    //    entrée = ['baseline_budget', 'expected_rank', 'objectives' => [...], 'paid_rewards' => [...]]
    //    (détail de l'entrée : SeasonChallengeService)

    public function challenges(): ?array
    {
        return $this->get('challenges');
    }

    /** Saison pour laquelle les défis ont été tirés ; 0 s'ils ne l'ont pas encore été. */
    public function challengesSeason(): int
    {
        return (int) ($this->challenges()['season'] ?? 0);
    }

    /** @return array<int|string, array> entrées par équipe */
    public function challengeTeams(): array
    {
        return $this->challenges()['teams'] ?? [];
    }

    public function challengeEntry(int $teamId): ?array
    {
        $teams = $this->challengeTeams();

        return $teams[$teamId] ?? $teams[(string) $teamId] ?? null;
    }

    public function startChallenges(int $season, string $drawnAt, array $teams): void
    {
        $this->put('challenges', ['season' => $season, 'drawn_at' => $drawnAt, 'teams' => $teams]);
    }

    public function putChallenges(array $challenges): void
    {
        $this->put('challenges', $challenges);
    }

    // ── training : séances de la semaine ──────────────────────────────
    //    ['season' => int, 'week' => int, 'entries' => [séance], 'ai_entries' => [séance]]
    //    séance = ['team_id', 'player_id', 'stat', 'gain', 'stamina_cost', 'cost', 'created_at', ...]

    public function training(): ?array
    {
        return $this->get('training');
    }

    /** Séances enregistrées pour CETTE saison et semaine (toutes équipes), sinon []. */
    public function weekTrainingEntries(int $season, int $week): array
    {
        $training = $this->training();

        return $training
            && (int) ($training['season'] ?? 0) === $season
            && (int) ($training['week'] ?? 0) === $week
            ? ($training['entries'] ?? [])
            : [];
    }

    /** Remplace les séances de la semaine (les séances IA de l'état précédent sont abandonnées). */
    public function setWeekTraining(int $season, int $week, array $entries): void
    {
        $this->put('training', ['season' => $season, 'week' => $week, 'entries' => $entries]);
    }

    /** Enregistre les séances IA de la semaine en gardant les séances humaines de cette semaine. */
    public function setAiTraining(int $season, int $week, array $aiEntries): void
    {
        $this->put('training', [
            'season'     => $season,
            'week'       => $week,
            'entries'    => $this->weekTrainingEntries($season, $week),
            'ai_entries' => $aiEntries,
        ]);
    }

    // ── training_history : séances humaines de la saison (200 dernières) ─

    /** null tant qu'aucun historique n'a été constitué (anciennes sauvegardes). */
    public function trainingHistory(): ?array
    {
        return $this->get('training_history');
    }

    public function putTrainingHistory(array $history): void
    {
        $this->put('training_history', $history);
    }

    // ── ai_training_history / ai_training_week : séances des équipes IA ─

    public function aiTrainingHistory(): array
    {
        return $this->get('ai_training_history', []);
    }

    public function aiTrainingHistoryOfSeason(int $season): array
    {
        return array_values(array_filter(
            $this->aiTrainingHistory(),
            fn ($entry) => (int) ($entry['season'] ?? 0) === $season,
        ));
    }

    public function aiTrainingHistoryOfWeek(int $season, int $week): array
    {
        return array_values(array_filter(
            $this->aiTrainingHistory(),
            fn ($entry) => (int) ($entry['season'] ?? 0) === $season && (int) ($entry['week'] ?? 0) === $week,
        ));
    }

    public function putAiTrainingHistory(array $history): void
    {
        $this->put('ai_training_history', $history);
    }

    /** Dernière semaine pour laquelle l'entraînement IA a été traité (garde anti-doublon). */
    public function aiTrainingWeek(): mixed
    {
        return $this->get('ai_training_week');
    }

    public function markAiTrainingWeek(mixed $week): void
    {
        $this->put('ai_training_week', $week);
    }

    // ── season_progression : gains de stats par joueur sur la saison ──
    //    [season => [playerId => total gagné]] (plafonné par joueur et par saison)

    /** Ne garde que la saison en cours : les précédentes ne servent plus au plafond. */
    public function keepOnlySeasonProgression(int $season): void
    {
        $this->put('season_progression', [$season => $this->get('season_progression', [])[$season] ?? []]);
    }

    public function playerSeasonProgression(int $season, int $playerId): int
    {
        return (int) ($this->get('season_progression', [])[$season][$playerId] ?? 0);
    }

    public function setPlayerSeasonProgression(int $season, int $playerId, int $total): void
    {
        $progression = $this->get('season_progression', []);
        $progression[$season][$playerId] = $total;
        $this->put('season_progression', $progression);
    }

    // ── match_progression_history : derniers matchs traités (50) ──────

    /** @param array{match_id: int, week: int, season: int, count: int} $entry */
    public function recordMatchProgression(array $entry): void
    {
        $history = $this->get('match_progression_history', []);
        $history[] = $entry;
        $this->put('match_progression_history', array_slice($history, -50));
    }

    // ── cartes bonus / malus : effets en attente ──────────────────────
    //    Chaque liste est ajoutée à l'activation d'une carte et purgée une fois
    //    l'effet consommé (match joué, fin de semaine).

    /** Boosts « prochain match » : [['game_bonus_card_id', 'game_team_id', 'effect_type', 'effect_value']] */
    public function activePreMatchCards(): array
    {
        return $this->get('active_pre_match_cards', []);
    }

    public function addActivePreMatchCard(array $card): void
    {
        $this->put('active_pre_match_cards', [...$this->activePreMatchCards(), $card]);
    }

    public function putActivePreMatchCards(array $cards): void
    {
        $this->put('active_pre_match_cards', array_values($cards));
    }

    /** Debuffs de stats reçus par une équipe cible pour son prochain match. */
    public function pendingStatDebuffs(): array
    {
        return $this->get('pending_stat_debuffs', []);
    }

    public function addPendingStatDebuff(array $debuff): void
    {
        $this->put('pending_stat_debuffs', [...$this->pendingStatDebuffs(), $debuff]);
    }

    public function putPendingStatDebuffs(array $debuffs): void
    {
        $this->put('pending_stat_debuffs', array_values($debuffs));
    }

    /** Malus offensifs en attente (titulaire consigné…) : ['target_team_id', 'target_player_id', 'effect_type', ...] */
    public function pendingMalus(): array
    {
        return $this->get('pending_malus', []);
    }

    public function addPendingMalus(array $malus): void
    {
        $this->put('pending_malus', [...$this->pendingMalus(), $malus]);
    }

    public function putPendingMalus(array $malus): void
    {
        $this->put('pending_malus', array_values($malus));
    }

    /** @return array<int, int> ids des équipes protégées par un bouclier (un exemplaire par carte active) */
    public function malusShields(): array
    {
        return array_map('intval', $this->get('malus_shields', []));
    }

    public function addMalusShield(int $teamId): void
    {
        $this->put('malus_shields', [...$this->malusShields(), $teamId]);
    }

    /** Consomme un bouclier de l'équipe ; false si elle n'en a pas. */
    public function consumeMalusShield(int $teamId): bool
    {
        $shields = $this->malusShields();
        $index = array_search($teamId, $shields, true);
        if ($index === false) return false;

        unset($shields[$index]);
        $this->put('malus_shields', array_values($shields));

        return true;
    }

    /** Défis sponsors (cartes finance) en cours : ['game_team_id', 'challenge', 'reward', ...] */
    public function pendingSponsorChallenges(): array
    {
        return $this->get('pending_sponsor_challenges', []);
    }

    public function addSponsorChallenge(array $challenge): void
    {
        $this->put('pending_sponsor_challenges', [...$this->pendingSponsorChallenges(), $challenge]);
    }

    public function putPendingSponsorChallenges(array $challenges): void
    {
        $this->put('pending_sponsor_challenges', array_values($challenges));
    }

    /** Récap de la dernière clôture des défis sponsors. */
    public function sponsorResults(): array
    {
        return $this->get('sponsor_results', []);
    }

    public function putSponsorResults(array $results): void
    {
        $this->put('sponsor_results', $results);
    }

    // ── shop : offres de la boutique de la semaine ────────────────────
    //    ['season' => int, 'week' => int, 'offers_by_team' => [teamId => [offre]]]

    public function shop(): ?array
    {
        return $this->get('shop');
    }

    /** Les offres ont-elles été tirées pour CETTE saison et semaine ? */
    public function isShopFor(int $season, int $week): bool
    {
        $shop = $this->shop();

        return $shop !== null
            && (int) ($shop['season'] ?? 0) === $season
            && (int) ($shop['week'] ?? 0) === $week;
    }

    public function putShop(int $season, int $week, array $offersByTeam): void
    {
        $this->put('shop', ['season' => $season, 'week' => $week, 'offers_by_team' => $offersByTeam]);
    }

    public function shopOffers(int $teamId): array
    {
        return $this->shop()['offers_by_team'][(string) $teamId] ?? [];
    }

    // ── tournament : Coupe du Monde (poules puis élimination) ─────────
    //    ['groups' => [...], 'stage' => 'group'|...|'done', 'group_last_week' => int, 'champion_team_id' => ?int, ...]
    //    (détail de la structure : TournamentService)

    public function tournament(): ?array
    {
        return $this->get('tournament');
    }

    public function putTournament(array $tournament): void
    {
        $this->put('tournament', $tournament);
    }

    public function tournamentStage(): ?string
    {
        return $this->tournament()['stage'] ?? null;
    }

    // ── career : mandat de la direction, jauge de confiance ───────────
    //    (détail de la structure : CareerObjectiveService)

    public function career(): array
    {
        return $this->get('career', []);
    }

    public function putCareer(array $career): void
    {
        $this->put('career', $career);
    }

    // ── contract_departures : dernier passage des départs de joueurs ──

    /** @return array{season?: int, week?: int, entries?: array} */
    public function contractDepartures(): array
    {
        return $this->get('contract_departures', []);
    }

    public function contractDeparturesDoneFor(int $season, int $week): bool
    {
        $last = $this->contractDepartures();

        return ($last['season'] ?? null) === $season && ($last['week'] ?? null) === $week;
    }

    public function putContractDepartures(int $season, int $week, array $entries): void
    {
        $this->put('contract_departures', ['season' => $season, 'week' => $week, 'entries' => $entries]);
    }
}
