<?php

namespace App\Http\Controllers\GameSaves;

use App\Http\Requests\GameSaves\RenewContractRequest;
use App\Enums\GamePhase;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesGameSave;
use App\Http\Controllers\Concerns\LocksGameSave;
use App\Http\Requests\GameSaves\GameContractRequest;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameSave;


use App\Models\GameSaves\GamePlayer;
use App\Services\Players\MoraleService;
use App\Services\Players\PromiseService;
use App\Services\Season\SeasonService;
use Illuminate\Http\RedirectResponse;

/** Contrats des joueurs d'une sauvegarde : création, modification, suppression, prolongation et résiliation d'un contrat. */
class GameContractController extends Controller
{
    use AuthorizesGameSave;
    use LocksGameSave;

    /** Crée un contrat pour un joueur de la sauvegarde. Refuse si le joueur, trop mécontent, ne veut pas re-signer avec son club actuel ; s'il change de club, son moral repart à la valeur neutre et son affinité avec le coach à 0. Revient en arrière avec un message. */
    public function store(GameContractRequest $request, GameSave $gameSave, GamePlayer $player)
    {
        $this->authorizeGameSave('update', $gameSave, $player);

        $data = $request->validated();

        // Conséquences du moral : un révolté refuse de prolonger avec son club ;
        // changer de club repart sur un moral neutre.
        $lastContract = GameContract::where('game_save_id', $gameSave->id)
            ->where('game_player_id', $player->id)
            ->orderByDesc('end_week')
            ->first();
        $sameTeam = $lastContract && (int) $lastContract->game_team_id === (int) $data['game_team_id'];

        if ($sameTeam && MoraleService::refusesToSign($player)) {
            return back()->withErrors([
                'contract' => "{$player->full_name} refuse de signer un nouveau contrat avec ce club (moral ou relation avec le coach au plus bas).",
            ]);
        }

        if ($lastContract && !$sameTeam) {
            $player->morale         = MoraleService::NEUTRAL_MORALE;
            $player->coach_affinity = 0;
            $player->save();
        }

        $data['game_save_id'] = $gameSave->id;
        $data['game_player_id'] = $player->id;

        GameContract::create($data);

        return back()->with('success', 'Contrat créé.');
    }

    /** Modifie un contrat existant avec les données validées et revient en arrière avec un message de succès. */
    public function update(GameContractRequest $request, GameSave $gameSave, GameContract $contract)
    {
        $this->authorizeGameSave('update', $gameSave, $contract);

        $contract->update($request->validated());

        return back()->with('success', 'Contrat mis à jour.');
    }

    /** Supprime un contrat de la sauvegarde et revient en arrière avec un message de succès. */
    public function destroy(GameSave $gameSave, GameContract $contract)
    {
        $this->authorizeGameSave('update', $gameSave, $contract);

        $contract->delete();

        return back()->with('success', 'Contrat supprimé.');
    }

    /** Prolonge le contrat d'un joueur de l'équipe contrôlée de N semaines, sous le verrou de sauvegarde. Vérifie : phase de saison (pas en Coupe du monde), contrat actif, fin de prolongation dans la saison, proposition à jour, absence de chevauchement avec un autre contrat, joueur non réfractaire et budget suffisant. Débite le budget du club (salaire de renouvellement x semaines), met à jour le contrat et valide une éventuelle promesse de prolongation. */
    public function renew(RenewContractRequest $request, GameSave $gameSave, GameContract $contract, SeasonService $seasons): RedirectResponse
    {
        $this->authorizeGameSave('update', $gameSave, $contract);
        $data = $request->validated();

        return $this->withSaveLock($gameSave, function (GameSave $gameSave) use ($contract, $data, $seasons) {
            $contract = GameContract::whereKey($contract->id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $contract->game_team_id === (int) $gameSave->controlled_game_team_id, 403);

            $week = (int) ($gameSave->week ?? 1);
            $seasonEnd = $seasons->getSeasonLength($gameSave);
            if ($gameSave->competition_type === 'world_cup' || $gameSave->currentPhase() !== GamePhase::Season
                || !$contract->isActive($week) || $contract->end_week === null || $contract->end_week >= $seasonEnd) {
                return back()->withErrors(['weeks' => 'Ce contrat ne peut pas être prolongé.']);
            }
            if ($contract->end_week !== (int) $data['end_week']) {
                return back()->withErrors(['weeks' => 'Le contrat a déjà changé. Rouvre la proposition pour actualiser les dates.']);
            }
            if ($data['weeks'] > $seasonEnd - $contract->end_week) {
                return back()->withErrors(['weeks' => "La prolongation doit se terminer au plus tard en semaine {$seasonEnd}."]);
            }
            $endWeek = $contract->end_week + $data['weeks'];
            $overlap = GameContract::where('game_save_id', $gameSave->id)
                ->where('game_player_id', $contract->game_player_id)
                ->whereKeyNot($contract->id)
                ->where('start_week', '<=', $endWeek)
                ->where(fn ($query) => $query->whereNull('end_week')->orWhere('end_week', '>', $contract->end_week))
                ->exists();
            if ($overlap) {
                return back()->withErrors(['weeks' => 'Un autre contrat couvre déjà cette période.']);
            }

            $player = $contract->gamePlayer;
            if (MoraleService::refusesToSign($player)) {
                return back()->withErrors(['weeks' => "{$player->full_name} refuse de prolonger avec ce club."]);
            }
            $team = $contract->gameTeam;
            $salary = $player->renewal_cost;
            $totalCost = $salary * $data['weeks'];
            if ($totalCost > $team->budget) {
                return back()->withErrors(['weeks' => "Budget insuffisant pour cette prolongation ({$totalCost} GL)."]);
            }

            $team->decrement('budget', $totalCost);
            $contract->update(['end_week' => $endWeek, 'salary' => $salary]);
            $promiseKept = app(PromiseService::class)->fulfillRenewal($gameSave, $player, $team->id);

            $message = "Contrat de {$player->full_name} prolongé jusqu’en semaine {$endWeek}.";
            if ($promiseKept) {
                $message .= ' Promesse de prolongation tenue.';
            }

            return back()->with('success', $message);
        });
    }

    /**
     * Résilie un contrat en cours (action en jeu, équipe contrôlée).
     * Conséquences : aucun remboursement du salaire déjà payé d'avance, la
     * relation du joueur avec le coach est rompue (-100) et son moral chute.
     * Le joueur redevient agent libre et refusera de re-signer avec ce club.
     */
    public function release(GameSave $gameSave, GameContract $contract)
    {
        $this->authorizeGameSave('update', $gameSave, $contract);

        abort_unless(
            (int) $contract->game_team_id === (int) $gameSave->controlled_game_team_id,
            403
        );

        $player = $contract->gamePlayer;

        if ($player) {
            $player->coach_affinity = -100;
            $player->morale = max(0, (int) ($player->morale ?? MoraleService::NEUTRAL_MORALE) - 20);
            $player->number = null;
            $player->save();
        }

        // Aucun remboursement : le salaire versé d'avance est perdu.
        $contract->delete();

        $name = $player?->full_name ?? 'Le joueur';

        return back()->with('success', "{$name} a été libéré. Le salaire déjà versé n'est pas remboursé et sa relation avec le coach est rompue.");
    }
}
