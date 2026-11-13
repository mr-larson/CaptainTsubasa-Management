<?php

namespace App\Services;

use App\Enums\GamePhase;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Support\ActionOutcome;

/**
 * Recrutement d'un AGENT LIBRE (joueur sans contrat en cours) par une équipe.
 *
 * Règles appliquées, dans cet ordre (la première qui échoue refuse la signature) :
 *   1. pas de transferts en Coupe du Monde ;
 *   2. la saison doit être en cours et la durée demandée tenir dans les semaines restantes ;
 *   3. le joueur ne doit pas déjà avoir un contrat en cours ;
 *   4. l'équipe doit avoir le budget (salaire × durée) ;
 *   5. un joueur révolté refuse de re-signer avec son ancien club.
 *
 * Si tout passe : le joueur reçoit un numéro de maillot libre, un contrat est créé,
 * le budget est débité, la signature est journalisée (défi « Recruter X joueurs »).
 *
 * IMPORTANT : ce service ne pose PAS de verrou. L'appelant doit déjà tenir le
 * verrou de la sauvegarde (LocksGameSave) pour éviter un double contrat ou un
 * double débit sur un double clic.
 */
class FreeAgentService
{
    /** Au-delà de 11 titulaires déjà sous contrat, le nouvel arrivant est remplaçant. */
    private const STARTERS_PER_TEAM = 11;

    public function __construct(
        private SeasonService $seasonService,
        private SeasonChallengeService $seasonChallenges,
    ) {
    }

    /**
     * Tente de signer $player dans $team pour $matchesTotal semaines.
     *
     * @param  int         $matchesTotal  durée du contrat, en semaines (= en matchs)
     * @param  string|null $reason        motif libre saisi par l'utilisateur (gardé dans l'historique)
     * @return ActionOutcome success si signé, info si le joueur a déjà un contrat, error sinon
     */
    public function sign(GameSave $gameSave, GamePlayer $player, GameTeam $team, int $matchesTotal, ?string $reason): ActionOutcome
    {
        // 1. Des sélections nationales ne recrutent pas.
        if ($gameSave->competition_type === 'world_cup') {
            return ActionOutcome::error('player', 'Pas de transferts en Coupe du Monde.');
        }

        // 2. Il faut une saison en cours et assez de semaines restantes pour la durée demandée.
        $startWeek      = $gameSave->week ?? 1;
        $remainingWeeks = max(0, $this->seasonService->getSeasonLength($gameSave) - $startWeek);

        if ($gameSave->currentPhase() !== GamePhase::Season || $remainingWeeks === 0) {
            return ActionOutcome::error('matches_total', 'Il ne reste plus de semaines disponibles pour signer un joueur.');
        }
        if ($matchesTotal > $remainingWeeks) {
            return ActionOutcome::error('matches_total', "La durée maximale restante est de {$remainingWeeks} semaine(s).");
        }

        // 3. Un joueur déjà sous contrat (dans n'importe quelle équipe) ne peut pas être signé.
        $alreadyHasContract = GameContract::where('game_save_id', $gameSave->id)
            ->where('game_player_id', $player->id)
            ->activeAt($startWeek)
            ->exists();

        if ($alreadyHasContract) {
            return ActionOutcome::info('Ce joueur a déjà un contrat en cours dans cette partie.');
        }

        // 4. Budget. Le salaire est calculé côté serveur : on ignore toute valeur
        //    envoyée par le navigateur (sinon n'importe qui signerait un joueur à 0).
        $salary    = (int) $player->adjusted_cost;
        $totalCost = $salary * $matchesTotal;
        $budget    = (int) ($team->budget ?? 0);

        if ($totalCost > $budget) {
            return ActionOutcome::error('contract', $this->insufficientBudgetMessage($player, $salary, $matchesTotal, $totalCost, $budget));
        }

        // 5. Moral : un joueur révolté refuse de re-signer avec son ancien club ;
        //    s'il signe ailleurs, il repart sur un moral neutre et sans rancune envers le coach.
        $lastContract = GameContract::where('game_save_id', $gameSave->id)
            ->where('game_player_id', $player->id)
            ->orderByDesc('end_week')
            ->first();
        $isSameTeam = $lastContract && (int) $lastContract->game_team_id === (int) $team->id;

        if ($isSameTeam && MoraleService::refusesToSign($player)) {
            return ActionOutcome::error('contract', "{$player->full_name} refuse de re-signer avec ce club (moral ou relation avec le coach au plus bas).");
        }
        if ($lastContract && ! $isSameTeam) {
            $player->morale         = MoraleService::NEUTRAL_MORALE;
            $player->coach_affinity = 0;
        }

        // Tout est bon : on enregistre.
        $this->completeSigning($gameSave, $player, $team, $startWeek, $matchesTotal, $salary, $totalCost, $reason);

        return ActionOutcome::success('Joueur signé et budget mis à jour.');
    }

    /**
     * Écrit la signature : numéro de maillot, contrat, débit du budget, historique,
     * puis verse la prime du défi « recrutements » si elle vient d'être atteinte.
     */
    private function completeSigning(
        GameSave $gameSave,
        GamePlayer $player,
        GameTeam $team,
        int $startWeek,
        int $matchesTotal,
        int $salary,
        int $totalCost,
        ?string $reason,
    ): void {
        // Titulaire s'il reste de la place dans le onze (moins de 11 titulaires sous contrat).
        $starterCount = GameContract::where('game_save_id', $gameSave->id)
            ->where('game_team_id', $team->id)
            ->where('is_starter', true)
            ->activeAt($startWeek)
            ->count();

        $player->number = $this->firstFreeJerseyNumber($gameSave, $team, $startWeek);
        $player->save();

        GameContract::create([
            'game_save_id'                    => $gameSave->id,
            'game_team_id'                    => $team->id,
            'game_player_id'                  => $player->id,
            'salary'                          => $salary,
            'start_week'                      => $startWeek,
            'end_week'                        => $startWeek + $matchesTotal - 1,
            'is_starter'                      => $starterCount < self::STARTERS_PER_TEAM,
            'is_captain'                      => false,
            'captain_rerolls_remaining'       => 3,
            'captain_reroll_used_this_action' => false,
        ]);

        $team->budget = (int) ($team->budget ?? 0) - $totalCost;
        $team->save();

        // Historique des signatures d'agents libres (consulté pour les défis de saison).
        $gameSave->gameState()->recordFreeAgentSigning([
            'player_id'     => $player->id,
            'team_id'       => $team->id,
            'salary'        => $salary,
            'matches_total' => $matchesTotal,
            'total_cost'    => $totalCost,
            'reason'        => $reason,
            'week'          => $gameSave->week,
        ]);
        $gameSave->save();

        $this->seasonChallenges->payCompleted($gameSave, [(int) $team->id], ['signings_min']);
    }

    /**
     * Plus petit numéro de maillot (à partir de 1) qu'aucun joueur sous contrat
     * dans l'équipe ne porte déjà à cette semaine.
     */
    private function firstFreeJerseyNumber(GameSave $gameSave, GameTeam $team, int $week): int
    {
        $used = GamePlayer::where('game_save_id', $gameSave->id)
            ->whereHas('contracts', fn ($q) => $q->where('game_team_id', $team->id)->activeAt($week))
            ->pluck('number')
            ->filter()
            ->toArray();

        $number = 1;
        while (in_array($number, $used)) {
            $number++;
        }

        return $number;
    }

    /** Message détaillé quand le budget ne suffit pas (montants formatés « 12 345 »). */
    private function insufficientBudgetMessage(GamePlayer $player, int $salary, int $matchesTotal, int $totalCost, int $budget): string
    {
        $totalFmt  = number_format($totalCost, 0, ',', ' ');
        $salaryFmt = number_format($salary, 0, ',', ' ');
        $budgetFmt = number_format($budget, 0, ',', ' ');

        return "Budget insuffisant : {$player->full_name} coûte {$totalFmt} "
            . "({$salaryFmt} × {$matchesTotal} match(s)), budget disponible {$budgetFmt}.";
    }
}
