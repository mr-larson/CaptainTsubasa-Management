<?php

namespace App\Services\Draft;

use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Support\DraftTurnResult;
use App\Services\Ai\DraftAIService;

/**
 * Les ACTIONS d'un tour de draft, côté serveur : le choix d'un joueur par une
 * équipe IA, le choix d'un joueur par un humain, et la clôture de la draft d'une
 * équipe humaine.
 *
 * Le draft se joue tour à tour : chaque équipe pioche un joueur, l'ordre s'inverse
 * à chaque tour, et une équipe peut s'arrêter quand son effectif lui suffit. Quand
 * toutes les équipes ont fini, la draft est « finalisée » : les compositions sont
 * faites et la partie passe en phase de saison (DraftService::finalizeDraft).
 *
 * Ce service orchestre ; les règles de fond sont dans :
 *   - DraftService   : ordre des tours, coût d'un pick, exécution d'un pick, finalisation ;
 *   - DraftAIService : décisions de l'IA (quel joueur, quand s'arrêter).
 *
 * IMPORTANT : ce service ne pose PAS de verrou. L'appelant doit tenir le verrou de la
 * sauvegarde (LocksGameSave) : sans lui, deux picks simultanés pourraient signer le même
 * joueur ou consommer deux fois le même tour.
 */
class DraftTurnService
{
    public function __construct(
        private DraftService $draft,
        private DraftAIService $ai,
    ) {
    }

    // ──────────────────────────────────────────────────────────────
    //   TOUR D'UNE ÉQUIPE IA
    // ──────────────────────────────────────────────────────────────

    /**
     * Joue le tour de l'équipe IA actuellement « sur l'horloge » (le navigateur appelle
     * cette action pour chaque tour IA).
     *
     * Cas possibles :
     *   - refus (400) : la draft n'est pas en cours, ou c'est au tour d'un humain ;
     *   - le draft est déjà terminé : on le finalise (réponse « completed ») ;
     *   - l'IA décide d'arrêter (effectif suffisant) ou ne trouve aucun joueur abordable :
     *     son draft est clos et on renvoie « finished » ;
     *   - sinon elle pioche le meilleur joueur et on renvoie ce pick.
     */
    public function playAiTurn(GameSave $gameSave): DraftTurnResult
    {
        if (! $gameSave->isDraftPhase()) {
            return DraftTurnResult::error('Not in draft phase');
        }

        // Ce service ne joue que les tours IA : un tour humain se joue via playHumanPick().
        if ($this->draft->isHumanTurn($gameSave)) {
            return DraftTurnResult::error('It is your turn to pick');
        }

        // Plus personne à faire piocher : on finalise la draft.
        if (! $gameSave->gameState()->hasDraft() || $gameSave->gameState()->draftCompleted()) {
            $this->draft->finalizeDraft($gameSave);

            return DraftTurnResult::ok(['completed' => true]);
        }

        $team = GameTeam::find($this->draft->getCurrentTeamId($gameSave));
        if (! $team) {
            return DraftTurnResult::error('Team not found');
        }

        // L'IA s'arrête quand son effectif et son budget le justifient.
        $squadSize = GameContract::where('game_save_id', $gameSave->id)
            ->where('game_team_id', $team->id)
            ->count();
        if ($this->ai->shouldFinishDraft($team, $squadSize, DraftService::budgetFor($gameSave, $team))) {
            return $this->closeTeamDraft($gameSave, $team, force: false);
        }

        $playerId = $this->ai->chooseBestPlayer($gameSave, $team);
        if (! $playerId) {
            // Aucun joueur trouvable (budget insuffisant) : l'équipe a fini, même sous l'effectif
            // minimum, sinon le tour ne passerait jamais à l'équipe suivante.
            return $this->closeTeamDraft($gameSave, $team, force: true);
        }

        $pick = $this->draft->executePick($gameSave, $playerId);
        if ($pick === null) {
            // Pick refusé (joueur déjà pris, trop cher…) : l'IA perd son tour plutôt
            // que de bloquer le draft sur la même équipe.
            $this->draft->advancePick($gameSave);
        }

        return $this->afterPick($gameSave, $pick);
    }

    /**
     * Clôt le draft d'une équipe IA et renvoie l'état. « force » = on la clôt même si
     * elle n'a pas atteint l'effectif minimum (cas où elle ne peut plus rien acheter).
     */
    private function closeTeamDraft(GameSave $gameSave, GameTeam $team, bool $force): DraftTurnResult
    {
        $this->draft->finishTeamDraft($gameSave, $team->id, force: $force);
        $gameSave->refresh();
        $draftState = $gameSave->gameState()->draft() ?? [];

        return DraftTurnResult::ok([
            'pick'       => null,
            'finished'   => true,
            'team_name'  => $team->name,
            'draftState' => $draftState,
            'completed'  => $draftState['completed'] ?? false,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    //   TOUR D'UN HUMAIN
    // ──────────────────────────────────────────────────────────────

    /**
     * Le manager humain dont c'est le tour choisit le joueur $playerId.
     *
     * Refus : draft non en cours ou pas son tour (400) ; le joueur refuse cette équipe à
     * cause d'une relation rompue avec le coach, ou le choix est impossible — joueur déjà
     * pris, trop cher, effectif complet (422).
     */
    public function playHumanPick(GameSave $gameSave, int $playerId): DraftTurnResult
    {
        if (! $gameSave->isDraftPhase()) {
            return DraftTurnResult::error('Not in draft phase');
        }
        if (! $this->draft->isHumanTurn($gameSave)) {
            return DraftTurnResult::error('Not your turn');
        }

        // Message explicite si le joueur refuse de rejoindre l'équipe (rancune envers le coach).
        $pickedPlayer  = GamePlayer::where('game_save_id', $gameSave->id)->find($playerId);
        $currentTeamId = $this->draft->getCurrentTeamId($gameSave);
        if ($pickedPlayer && $currentTeamId && $this->draft->playerRefusesTeam($gameSave, $pickedPlayer, (int) $currentTeamId)) {
            return DraftTurnResult::error("{$pickedPlayer->full_name} refuse de jouer pour ton équipe (relation en rupture avec le coach).", 422);
        }

        $pick = $this->draft->executePick($gameSave, $playerId);
        if (! $pick) {
            return DraftTurnResult::error('Sélection impossible : joueur déjà pris, trop cher ou effectif complet.', 422);
        }

        return $this->afterPick($gameSave, $pick);
    }

    /**
     * Clôture le draft de l'équipe humaine actuellement à son tour (hot-seat), ou à
     * défaut de l'équipe propriétaire. Refus (400) si la draft n'est pas en cours.
     *
     * Sous l'effectif minimum, la clôture n'est FORCÉE que si plus aucun joueur n'est
     * abordable pour cette équipe : sinon le manager resterait bloqué dans la draft.
     */
    public function finishHumanDraft(GameSave $gameSave): DraftTurnResult
    {
        if (! $gameSave->isDraftPhase()) {
            return DraftTurnResult::error('Not in draft phase');
        }

        // Équipe humaine actuellement « sur l'horloge », sinon l'équipe propriétaire.
        $current = $this->draft->getCurrentTeamId($gameSave);
        $teamId  = ($current !== null && in_array((int) $current, $gameSave->controlledGameTeamIds(), true))
            ? (int) $current
            : (int) $gameSave->controlled_game_team_id;

        $team  = GameTeam::where('game_save_id', $gameSave->id)->find($teamId);
        $force = $team && ! $this->draft->hasAffordablePlayer($gameSave, $team);

        $allDone = $this->draft->finishTeamDraft($gameSave, $teamId, force: $force);
        $gameSave->refresh();

        return DraftTurnResult::ok([
            'draftState' => $gameSave->gameState()->draft() ?? [],
            'completed'  => $allDone || $gameSave->gameState()->draftCompleted(),
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    //   FIN COMMUNE D'UN PICK
    // ──────────────────────────────────────────────────────────────

    /**
     * Après un pick (humain ou IA) : relit l'état du draft et, si toutes les équipes
     * ont fini, finalise la draft (compositions, passage en phase de saison).
     *
     * L'état renvoyé au navigateur est celui lu AVANT la finalisation : il décrit le
     * dernier pick, que l'écran de draft affiche une dernière fois.
     *
     * @param array|null $pick le pick effectué, ou null si l'IA a perdu son tour
     */
    private function afterPick(GameSave $gameSave, ?array $pick): DraftTurnResult
    {
        $gameSave->refresh();
        $draftState = $gameSave->gameState()->draft() ?? [];
        $completed  = $draftState['completed'] ?? false;

        if ($completed) {
            $this->draft->finalizeDraft($gameSave);
        }

        return DraftTurnResult::ok([
            'pick'       => $pick,
            'draftState' => $draftState,
            'completed'  => $completed,
        ]);
    }
}
