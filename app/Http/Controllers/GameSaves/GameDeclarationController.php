<?php

namespace App\Http\Controllers\GameSaves;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesGameSave;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use App\Services\DeclarationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GameDeclarationController extends Controller
{
    use AuthorizesGameSave;

    /**
     * Déclaration publique (féliciter / critiquer) sur un joueur de l'équipe contrôlée.
     */
    public function store(Request $request, GameSave $gameSave, GamePlayer $player): RedirectResponse
    {
        $this->authorizeGameSave('update', $gameSave, $player);

        $data = $request->validate([
            'type' => ['required', Rule::in(['praise', 'criticize'])],
        ]);

        $week = (int) ($gameSave->week ?? 1);

        $contract = GameContract::where('game_save_id', $gameSave->id)
            ->where('game_player_id', $player->id)
            ->activeAt($week)
            ->first();

        $controlledTeamId = (int) ($gameSave->controlled_game_team_id ?? 0);

        if (!$contract || (int) $contract->game_team_id !== $controlledTeamId) {
            return back()->withErrors([
                'declaration' => 'Ce joueur ne fait pas partie de ton équipe.',
            ]);
        }

        $result = app(DeclarationService::class)->declare($gameSave, $player, $data['type'], $controlledTeamId);

        if (is_string($result)) {
            return back()->withErrors(['declaration' => $result]);
        }

        return back()->with('success', 'Déclaration faite.');
    }
}
