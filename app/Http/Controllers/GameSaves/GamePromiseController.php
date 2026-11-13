<?php

namespace App\Http\Controllers\GameSaves;

use App\Http\Requests\GameSaves\StorePromiseRequest;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesGameSave;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use App\Services\PromiseService;
use Illuminate\Http\RedirectResponse;

/** Promesses de temps de jeu faites à un joueur de l'équipe contrôlée. */
class GamePromiseController extends Controller
{
    use AuthorizesGameSave;

    /**
     * Promet du temps de jeu à un joueur de l'équipe contrôlée.
     */
    public function store(StorePromiseRequest $request, GameSave $gameSave, GamePlayer $player): RedirectResponse
    {
        $this->authorizeGameSave('update', $gameSave, $player);

        $data = $request->validated();

        $week = (int) ($gameSave->week ?? 1);

        $contract = GameContract::where('game_save_id', $gameSave->id)
            ->where('game_player_id', $player->id)
            ->activeAt($week)
            ->first();

        // Son équipe, ou une équipe IA avec l'option « Accès aux autres clubs ».
        $teamId = $contract ? (int) $contract->game_team_id : 0;

        if (!$contract || ! $gameSave->canManageTeam($teamId)) {
            return back()->withErrors([
                'promise' => 'Ce joueur ne fait pas partie de ton équipe.',
            ]);
        }

        $result = app(PromiseService::class)->create($gameSave, $player, $teamId, $data['type'] ?? 'playing_time');

        if (is_string($result)) {
            return back()->withErrors(['promise' => $result]);
        }

        return back()->with('success', 'Promesse faite.');
    }
}
