<?php

namespace App\Http\Controllers\GameSaves;

use App\Http\Controllers\Concerns\AuthorizesGameSave;
use App\Http\Controllers\Concerns\LocksGameSave;
use App\Http\Controllers\Controller;
use App\Http\Requests\GameSaves\RespondPlayerRequestRequest;
use App\Models\GameSaves\GamePlayerRequest;
use App\Models\GameSaves\GameSave;
use App\Services\Players\PlayerRequestService;
use Illuminate\Http\RedirectResponse;

/** Réponses de l'entraîneur aux demandes de ses joueurs (congé, temps de jeu, souci physique). */
class GamePlayerRequestController extends Controller
{
    use AuthorizesGameSave;
    use LocksGameSave;

    /**
     * Répond à une demande d'un joueur de l'équipe qui a la main : accepter / refuser,
     * ou l'un des trois choix d'un souci physique. Sous verrou de sauvegarde, pour
     * qu'une demande ne reçoive qu'une seule réponse.
     */
    public function respond(RespondPlayerRequestRequest $request, GameSave $gameSave, GamePlayerRequest $playerRequest): RedirectResponse
    {
        $this->authorizeGameSave('update', $gameSave, $playerRequest);

        $answer = $request->validated()['answer'];

        return $this->withSaveLock($gameSave, function (GameSave $gameSave) use ($playerRequest, $answer) {
            $playerRequest->refresh();

            // Uniquement l'équipe du manager qui a la main (hot-seat : jamais celle d'un autre).
            if (! $gameSave->canManageTeam((int) $playerRequest->game_team_id)) {
                return back()->withErrors(['player_request' => 'Cette demande ne concerne pas ton équipe.']);
            }

            $result = app(PlayerRequestService::class)->respond($gameSave, $playerRequest, $answer);

            if (is_string($result)) {
                return back()->withErrors(['player_request' => $result]);
            }

            return back()->with('success', match ($answer) {
                'accept', 'rest' => 'Promesse faite : à toi de la tenir.',
                'decline'        => 'Demande refusée.',
                'physio'         => 'Le kiné s\'occupe de lui.',
                default          => 'Il jouera sans soins.',
            });
        });
    }
}
