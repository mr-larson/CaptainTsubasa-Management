<?php

namespace App\Http\Controllers\GameSaves;

use App\Http\Controllers\Controller;
use App\Http\Requests\GameSaves\GameContractRequest;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameSave;


use App\Models\GameSaves\GamePlayer;

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
