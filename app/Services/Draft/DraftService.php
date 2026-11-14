<?php

namespace App\Services\Draft;

use App\Enums\GamePhase;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use Illuminate\Support\Facades\DB;
use App\Services\Players\MoraleService;
use App\Services\Season\SeasonBootstrapService;
use App\Services\Season\SeasonService;

/**
 * Règles de la draft (phase de constitution des effectifs) en ordre serpent.
 * Chaque équipe dispose d'une enveloppe de budget propre, doit atteindre 14 joueurs et ne peut pas dépasser 18. Gère le coût d'un pick, l'exécution d'un pick (contrat, numéro de maillot, budget), l'avancement des tours, la clôture de la draft et la composition initiale des équipes.
 * Les méthodes qui écrivent dans l'état de la partie doivent être appelées sous le verrou de sauvegarde.
 */
class DraftService
{
    public const MIN_SQUAD   = 14;
    public const MAX_SQUAD   = 18;

    private const STANDARD_WAGE = 40;

    public function __construct(private DraftLineupOrganizer $lineups)
    {
    }

    /**
     * Ouvre une enveloppe de recrutement indépendante pour chaque équipe.
     */
    public function initDraft(GameSave $gameSave, array $teamIdsOrder): void
    {
        $humanBudget = (int) $gameSave->getConfig('draft_budget');
        $aiBudget = (int) $gameSave->getConfig('ai_draft_budget');
        $humanTeamIds = $gameSave->controlledGameTeamIds();
        $budgets = [];
        foreach ($teamIdsOrder as $teamId) {
            $budgets[$teamId] = in_array((int) $teamId, $humanTeamIds, true) ? $humanBudget : $aiBudget;
        }
        $gameSave->gameState()->startDraft($teamIdsOrder, $budgets);
        $gameSave->save();
    }

    /** Budget de draft restant d'une équipe : son enveloppe de draft, ou le budget de l'équipe pour les drafts commencées avec une caisse commune. */
    public static function budgetFor(GameSave $gameSave, GameTeam $team): int
    {
        $budgets = $gameSave->gameState()->draftBudgets();

        // Les drafts déjà entamées avec une caisse commune gardent leur solde.
        return (int) ($budgets === null ? $team->budget : ($budgets[$team->id] ?? 0));
    }

    /** Coût d'un pick : salaire x semaines, divisé par 2 en tarif standard. En tarification progressive, allège surtout les petits salaires (les gros gardent l'ancien tarif). Le salaire du contrat reste celui du joueur. */
    public function pickCost(GameSave $gameSave, int $salary, int $weeks): int
    {
        if ($gameSave->gameState()->draftPricing() !== 'progressive') {
            return intdiv($salary * $weeks, 2);
        }

        // Allège surtout les recrues abordables ; les plus gros salaires gardent
        // l’ancien tarif. Le salaire du contrat reste celui du joueur.
        $quarterUnits = min(2 * $salary, $salary + 2 * max(0, $salary - self::STANDARD_WAGE));

        return intdiv($quarterUnits * $weeks, 4);
    }

    /**
     * Exécute un pick : assigne un joueur libre à l'équipe courante.
     * Retourne le pick enregistré ou null si invalide.
     */
    public function executePick(GameSave $gameSave, int $playerId): ?array
    {
        $draft = $gameSave->gameState()->draft();

        if (!$draft || ($draft['completed'] ?? false)) return null;

        // Équipe courante (ordre serpent : les tours pairs sont inversés)
        $currentTeamId = $gameSave->gameState()->currentDraftTeamId();

        if (!$currentTeamId) return null;

        $team = GameTeam::where('game_save_id', $gameSave->id)->find($currentTeamId);
        if (!$team) return null;

        // Vérifier que l'équipe n'a pas atteint le max
        $squadSize = GameContract::where('game_save_id', $gameSave->id)
            ->where('game_team_id', $currentTeamId)
            ->count();

        if ($squadSize >= self::MAX_SQUAD) {
            // Skip ce pick, avancer
            return $this->advanceAndSkip($gameSave);
        }

        $player = $this->findPickablePlayer($gameSave, $playerId, (int) $currentTeamId);
        if (!$player) return null;

        // Coût = salaire hebdo (majoration polyvalence incluse) × semaines restantes × réduction draft
        $seasonLength = $this->getSeasonLength($gameSave);
        $salary       = $player->adjusted_cost;
        $totalCost    = $this->pickCost($gameSave, $salary, $seasonLength);

        $budget = self::budgetFor($gameSave, $team);
        if ($totalCost > $budget) return null;

        return DB::transaction(
            fn () => $this->recordPick($gameSave, $team, $player, $salary, $seasonLength, $squadSize, $totalCost, $budget, $draft)
        );
    }

    /**
     * Le joueur demandé s'il peut être drafté par l'équipe courante : libre, draftable (non fictif),
     * visible selon la config (listes de joueurs actives/inactives) et sans rancune envers le
     * coach quand l'équipe est humaine. Null sinon.
     */
    private function findPickablePlayer(GameSave $gameSave, int $playerId, int $teamId): ?GamePlayer
    {
        $player = GamePlayer::where('game_save_id', $gameSave->id)
            ->excludingFictional()
            ->visibleForConfig($gameSave)
            ->find($playerId);
        if (!$player) return null;

        $hasContract = GameContract::where('game_save_id', $gameSave->id)
            ->where('game_player_id', $playerId)
            ->exists();

        if ($hasContract) return null;

        // Rancune envers le coach : refuse d'être drafté par l'équipe contrôlée
        if ($this->playerRefusesTeam($gameSave, $player, $teamId)) return null;

        return $player;
    }

    /**
     * Enregistre un pick validé (à appeler en transaction) : numéro de maillot, contrat, débit du
     * budget de draft (ou du budget de l'équipe pour une draft à caisse commune), puis entrée dans
     * l'historique des picks et passage au pick suivant. Retourne le pick enregistré.
     *
     * @param array $draft  état de la draft tel que lu avant le pick
     */
    private function recordPick(GameSave $gameSave, GameTeam $team, GamePlayer $player, int $salary, int $seasonLength, int $squadSize, int $totalCost, int $budget, array $draft): array
    {
        $isStarter = $squadSize < 11;

        // Drafté par un club IA (non humain) : la rancune envers le coach
        // repart à zéro (nouveau départ).
        if (!in_array((int) $team->id, $gameSave->controlledGameTeamIds(), true)
            && (int) ($player->coach_affinity ?? 0) !== 0) {
            $player->coach_affinity = 0;
        }

        $player->number = $this->firstFreeJerseyNumber($gameSave, $team);
        $player->save();

        GameContract::create([
            'game_save_id'                    => $gameSave->id,
            'game_team_id'                    => $team->id,
            'game_player_id'                  => $player->id,
            'salary'                          => $salary,
            'start_week'                      => 1,
            'end_week'                        => $seasonLength,
            'is_starter'                      => $isStarter,
            'is_captain'                      => false,
            'captain_rerolls_remaining'       => 3,
            'captain_reroll_used_this_action' => false,
        ]);

        if (isset($draft['budgets'])) {
            $draft['budgets'][$team->id] = $budget - $totalCost;
        } else {
            $team->budget = $budget - $totalCost;
            $team->save();
        }

        $pick = [
            'team_id'    => $team->id,
            'team_name'  => $team->name,
            'player_id'  => $player->id,
            'player_name'=> trim($player->firstname . ' ' . $player->lastname),
            'position'   => $player->position,
            'cost'       => $totalCost,
            'total_cost' => $totalCost,
            'round'      => $this->getCurrentRound($gameSave),
            'photo_path' => $player->photo_path,
        ];

        $draft['picks'][] = $pick;
        $gameSave->gameState()->putDraft($draft);
        $gameSave->save();
        $this->advancePick($gameSave);

        return $pick;
    }

    /** Plus petit numéro de maillot libre dans l'équipe (à partir de 1). */
    private function firstFreeJerseyNumber(GameSave $gameSave, GameTeam $team): int
    {
        $usedNumbers = GamePlayer::where('game_save_id', $gameSave->id)
            ->whereHas('contracts', fn($q) => $q->where('game_team_id', $team->id))
            ->pluck('number')
            ->filter()
            ->toArray();

        $nextNumber = 1;
        while (in_array($nextNumber, $usedNumbers)) {
            $nextNumber++;
        }

        return $nextNumber;
    }

    /**
     * Avance au pick suivant. Si fin de round, passe au round suivant.
     * Si le draft est terminé (toutes les équipes ont >= MIN_SQUAD), marque completed.
     */
    public function advancePick(GameSave $gameSave): void
    {
        $draft = $gameSave->gameState()->draft() ?? [];
        $order = $draft['order'] ?? [];
        $total = count($order);

        if ($total === 0) return;

        $pickIndex = ($draft['current_pick_index'] ?? 0) + 1;

        if ($pickIndex >= $total) {
            // Fin du round → vérifier si le draft est fini
            $draft['round']              = ($draft['round'] ?? 1) + 1;
            $draft['current_pick_index'] = 0;

            if ($this->isDraftComplete($gameSave)) {
                $draft['completed'] = true;
            }
        } else {
            $draft['current_pick_index'] = $pickIndex;
        }

        // Skip les équipes qui ont atteint MAX_SQUAD
        if (!($draft['completed'] ?? false)) {
            $draft = $this->skipFullTeams($gameSave, $draft);
        }

        $gameSave->gameState()->putDraft($draft);
        $gameSave->save();
    }

    /**
     * Skip les équipes ayant >= MAX_SQUAD joueurs.
     */
    protected function skipFullTeams(GameSave $gameSave, array $draft): array
    {
        $order = $draft['order'] ?? [];
        $total = count($order);
        $round = $draft['round'] ?? 1;
        $effectiveOrder = ($round % 2 === 0) ? array_reverse($order) : $order;
        $maxAttempts = $total; // Éviter boucle infinie

        for ($i = 0; $i < $maxAttempts; $i++) {
            $pickIndex     = $draft['current_pick_index'] ?? 0;
            $currentTeamId = $effectiveOrder[$pickIndex] ?? null;

            if (!$currentTeamId) break;

            $squadSize = GameContract::where('game_save_id', $gameSave->id)
                ->where('game_team_id', $currentTeamId)
                ->count();

            $finished = $draft['finished_teams'] ?? [];
            if ($squadSize < self::MAX_SQUAD && !in_array($currentTeamId, $finished)) break;

            // Skip
            $pickIndex++;
            if ($pickIndex >= $total) {
                $draft['round']              = ($draft['round'] ?? 1) + 1;
                $draft['current_pick_index'] = 0;

                if ($this->isDraftComplete($gameSave)) {
                    $draft['completed'] = true;
                    break;
                }

                // Recalculer l'ordre pour le nouveau round
                $round = $draft['round'];
                $effectiveOrder = ($round % 2 === 0) ? array_reverse($order) : $order;
            } else {
                $draft['current_pick_index'] = $pickIndex;
            }
        }

        return $draft;
    }

    /**
     * Le draft est terminé quand toutes les équipes ont >= MIN_SQUAD.
     */
    public function isDraftComplete(GameSave $gameSave): bool
    {
        $draft  = $gameSave->gameState()->draft() ?? [];
        $finished = $draft['finished_teams'] ?? [];

        $teamIds = GameTeam::where('game_save_id', $gameSave->id)->pluck('id');

        foreach ($teamIds as $teamId) {
            $count = GameContract::where('game_save_id', $gameSave->id)
                ->where('game_team_id', $teamId)
                ->count();

            // Terminé si : a atteint le max OU a dit "fini" (avec min 14)
            // OU a été clôturée faute de joueur abordable (exhausted).
            if ($count >= self::MAX_SQUAD) continue;
            if (in_array($teamId, $finished) && $count >= self::MIN_SQUAD) continue;
            if (in_array($teamId, $draft['exhausted_teams'] ?? [])) continue;

            // Cette équipe n'a pas fini
            return false;
        }

        return true;
    }

    /**
     * Marque une équipe comme ayant fini son draft.
     *
     * `$force` clôt une équipe sous MIN_SQUAD qui ne peut plus rien s'offrir
     * (pool vide ou budget épuisé) : sans cela, le pointeur ne bouge plus et
     * la boucle de picks IA côté front tourne indéfiniment.
     */
    public function finishTeamDraft(GameSave $gameSave, int $teamId, bool $force = false): bool
    {
        $count = GameContract::where('game_save_id', $gameSave->id)
            ->where('game_team_id', $teamId)
            ->count();

        if ($count < self::MIN_SQUAD && !$force) return false;

        $draft = $gameSave->gameState()->draft() ?? [];
        $finished = $draft['finished_teams'] ?? [];

        if (!in_array($teamId, $finished)) {
            $finished[] = $teamId;
        }

        if ($count < self::MIN_SQUAD) {
            $exhausted = $draft['exhausted_teams'] ?? [];
            if (!in_array($teamId, $exhausted)) {
                $exhausted[] = $teamId;
            }
            $draft['exhausted_teams'] = $exhausted;
        }

        $draft['finished_teams'] = $finished;
        $gameSave->gameState()->putDraft($draft);
        $gameSave->save();

        // Vérifier si tout le monde a fini
        if ($this->isDraftComplete($gameSave)) {
            $draft['completed'] = true;
            $gameSave->gameState()->putDraft($draft);
            $gameSave->save();
            $this->finalizeDraft($gameSave);
            return true;
        }

        // Avancer au prochain pick (skip cette équipe)
        $this->advancePick($gameSave);

        return false;
    }

    /**
     * L'équipe peut-elle encore s'offrir au moins un joueur libre du draft ?
     * (même règle de prix que executePick : coût ajusté × semaines × réduction draft)
     */
    public function hasAffordablePlayer(GameSave $gameSave, GameTeam $team): bool
    {
        $seasonLength = $this->getSeasonLength($gameSave);
        $budget       = self::budgetFor($gameSave, $team);

        // Équipe humaine ou non : décidé une fois, pas à chaque joueur examiné.
        $isHumanTeam = in_array((int) $team->id, $gameSave->controlledGameTeamIds(), true);

        return GamePlayer::where('game_save_id', $gameSave->id)
            ->whereDoesntHave('contracts')
            ->excludingFictional()
            ->visibleForConfig($gameSave)
            ->get()
            ->contains(fn ($p) => $this->pickCost($gameSave, $p->adjusted_cost, $seasonLength) <= $budget
                && !($isHumanTeam && $this->refusesHumanCoach($p)));
    }

    /** Un joueur en rupture avec le coach (affinité sous le seuil de refus) refuse d'être drafté par une équipe humaine. Les équipes IA ne sont pas concernées. */
    public function playerRefusesTeam(GameSave $gameSave, GamePlayer $player, int $teamId): bool
    {
        // La rancune envers le coach ne concerne que les équipes humaines.
        if (!in_array($teamId, $gameSave->controlledGameTeamIds(), true)) return false;

        return $this->refusesHumanCoach($player);
    }

    /** La relation du joueur avec le coach est-elle sous le seuil de refus ? (à appeler pour une équipe humaine) */
    private function refusesHumanCoach(GamePlayer $player): bool
    {
        return (int) ($player->coach_affinity ?? 0) <= MoraleService::AFFINITY_REFUSAL_THRESHOLD;
    }

    /** Identifiant de l'équipe dont c'est le tour de choisir (null si la draft est finie). */
    public function getCurrentTeamId(GameSave $gameSave): ?int
    {
        return $gameSave->gameState()->currentDraftTeamId();
    }

    /**
     * Vérifie si c'est au tour du joueur humain.
     */
    public function isHumanTurn(GameSave $gameSave): bool
    {
        $currentTeamId = $this->getCurrentTeamId($gameSave);
        return $currentTeamId !== null
            && in_array((int) $currentTeamId, $gameSave->controlledGameTeamIds(), true);
    }

    /** Numéro du tour de draft en cours. */
    protected function getCurrentRound(GameSave $gameSave): int
    {
        return $gameSave->gameState()->draftRound();
    }

    /** Nombre de semaines de la saison, déduit du nombre d'équipes de la partie. */
    protected function getSeasonLength(GameSave $gameSave): int
    {
        return SeasonService::lengthForTeamCount(GameTeam::where('game_save_id', $gameSave->id)->count());
    }

    /** Passe au pick suivant sans choisir (équipe déjà complète) et retourne un marqueur 'skipped'. */
    protected function advanceAndSkip(GameSave $gameSave): ?array
    {
        $this->advancePick($gameSave);
        return ['skipped' => true];
    }

    /**
     * Retire state.draft (ordre + picks, ~44 Ko) une fois la draft terminée : les
     * contrats portent le résultat, et initDraft recrée la clé à l'intersaison.
     * Appelé à l'ouverture de la page Play, après que l'écran de fin de draft a
     * pu lire l'état. Sans effet pendant une draft.
     */
    public function purgeFinishedDraft(GameSave $gameSave): void
    {
        if ($gameSave->isDraftPhase()) return;
        $state = $gameSave->gameState();
        if (!$state->hasDraft()) return;

        $state->forgetDraft();
        $gameSave->save();
    }

    /**
     * Finalise le draft : réorganise les lineups de toutes les équipes,
     * puis passe en phase season.
     */
    public function finalizeDraft(GameSave $gameSave): void
    {
        $teams = GameTeam::where('game_save_id', $gameSave->id)
            ->with(['contracts.gamePlayer'])
            ->get();

        $state = $gameSave->gameState();
        foreach ($teams as $team) {
            $this->lineups->organize($team, $gameSave, $state);
        }

        $draft = $state->draft() ?? [];
        unset($draft['budgets']);
        $state->putDraft($draft);
        $gameSave->phase = GamePhase::Season;
        $gameSave->save();

        // Ouvre la saison : mandat, défis, calendrier, compositions IA (sous le
        // verrou de la save, comme tous les appelants de finalizeDraft).
        app(SeasonBootstrapService::class)->prepare($gameSave);
    }
}
