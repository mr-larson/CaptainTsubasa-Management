<?php

namespace App\Http\Controllers\GameSaves;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesGameSave;
use App\Http\Requests\GameSaves\GameContractRequest;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameSave;


use App\Models\GameSaves\GamePlayer;
use App\Services\MoraleService;

class GameContractController extends Controller
{
    use AuthorizesGameSave;

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

    public function update(GameContractRequest $request, GameSave $gameSave, GameContract $contract)
    {
        $this->authorizeGameSave('update', $gameSave, $contract);

        $contract->update($request->validated());

        return back()->with('success', 'Contrat mis à jour.');
    }

    public function destroy(GameSave $gameSave, GameContract $contract)
    {
        $this->authorizeGameSave('update', $gameSave, $contract);

        $contract->delete();

        return back()->with('success', 'Contrat supprimé.');
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
