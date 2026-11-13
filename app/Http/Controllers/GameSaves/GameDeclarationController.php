<?php

namespace App\Http\Controllers\GameSaves;

use App\Http\Requests\GameSaves\StoreDeclarationRequest;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesGameSave;
use App\Http\Controllers\Concerns\LocksGameSave;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use App\Services\DeclarationService;
use Illuminate\Http\RedirectResponse;

class GameDeclarationController extends Controller
{
    use AuthorizesGameSave;
    use LocksGameSave;

    /**
     * Déclaration publique (féliciter / critiquer) sur un joueur de l'équipe contrôlée.
     */
    public function store(StoreDeclarationRequest $request, GameSave $gameSave, GamePlayer $player): RedirectResponse
    {
        $this->authorizeGameSave('update', $gameSave, $player);

        $data = $request->validated();

        return $this->withSaveLock($gameSave, function (GameSave $gameSave) use ($player, $data) {
            $player->refresh();
            $week = (int) ($gameSave->week ?? 1);

            $contract = GameContract::where('game_save_id', $gameSave->id)
                ->where('game_player_id', $player->id)
                ->activeAt($week)
                ->first();

            // Son équipe, ou une équipe IA avec l'option « Accès aux autres clubs ».
            $teamId = $contract ? (int) $contract->game_team_id : 0;

            if (!$contract || ! $gameSave->canManageTeam($teamId)) {
                return back()->withErrors([
                    'declaration' => 'Ce joueur ne fait pas partie de ton équipe.',
                ]);
            }

            $result = app(DeclarationService::class)->declare($gameSave, $player, $data['type'], $teamId);

            if (is_string($result)) {
                return back()->withErrors(['declaration' => $result]);
            }

            $message = match ($result->outcome) {
                'match_boost' => $result->type === 'praise'
                    ? 'Félicitations méritées : +2 en attaque, défense et vitesse pour le prochain match.'
                    : 'Réaction d’orgueil : +3 en attaque, défense et vitesse pour le prochain match, −3 de moral.',
                'match_malus' => 'Relâchement : −2 en attaque, défense et vitesse pour le prochain match.',
                default => 'Déclaration faite.',
            };

            return back()->with('success', $message);
        });
    }
}
