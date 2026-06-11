<?php

namespace App\Http\Controllers\GameSaves;

use App\Http\Controllers\Controller;
use App\Http\Requests\GameSaves\GameContractRequest;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameSave;


use App\Models\GameSaves\GamePlayer;
use App\Services\MoraleService;

class GameContractController extends Controller
{
    public function store(GameContractRequest $request, GameSave $gameSave, GamePlayer $player)
    {

        if ($gameSave->user_id !== auth()->id()) {
            abort(403);
        }

        if ($player->game_save_id !== $gameSave->id) {
            abort(403);
        }

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
        if ($gameSave->user_id !== auth()->id()) abort(403);
        if ($contract->game_save_id !== $gameSave->id) abort(403);

        $contract->update($request->validated());

        return back()->with('success', 'Contrat mis à jour.');
    }

    public function destroy(GameSave $gameSave, GameContract $contract)
    {
        if ($gameSave->user_id !== auth()->id()) abort(403);
        if ($contract->game_save_id !== $gameSave->id) abort(403);

        $contract->delete();

        return back()->with('success', 'Contrat supprimé.');
    }
}
