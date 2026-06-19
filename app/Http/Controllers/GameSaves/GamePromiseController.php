<?php

namespace App\Http\Controllers\GameSaves;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesGameSave;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GamePromise;
use App\Models\GameSaves\GameSave;
use App\Services\PromiseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GamePromiseController extends Controller
{
    use AuthorizesGameSave;

    /**
     * Promet du temps de jeu à un joueur de l'équipe contrôlée.
     */
    public function store(Request $request, GameSave $gameSave, GamePlayer $player): RedirectResponse
    {
        $this->authorizeGameSave('update', $gameSave, $player);

        $data = $request->validate([
            'type' => ['nullable', \Illuminate\Validation\Rule::in(array_keys(PromiseService::TYPES))],
        ]);

        $week = (int) ($gameSave->week ?? 1);

        $contract = GameContract::where('game_save_id', $gameSave->id)
            ->where('game_player_id', $player->id)
            ->activeAt($week)
            ->first();

        $controlledTeamId = (int) ($gameSave->controlled_game_team_id ?? 0);

        if (!$contract || (int) $contract->game_team_id !== $controlledTeamId) {
            return back()->withErrors([
                'promise' => 'Ce joueur ne fait pas partie de ton équipe.',
            ]);
        }

        $result = app(PromiseService::class)->create($gameSave, $player, $controlledTeamId, $data['type'] ?? 'playing_time');

        if (is_string($result)) {
            return back()->withErrors(['promise' => $result]);
        }

        return back()->with('success', 'Promesse faite.');
    }
}
